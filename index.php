<?php
include('inc/password_protect.php');
include('inc/functions.php');
include('config.php');
global $user_arrays;
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>DoorStats Tool - Wayne State University Libraries</title>
	<link rel="icon" href="../../inc/img/favicon.ico" type="image/x-icon" />
	<!-- jQuery -->
	<script src="inc/jquery-1.11.1.min.js"></script>

	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="inc/bootstrap-3.2.0-dist/css/bootstrap.min.css">

	<!-- Local CSS -->
	<link rel="stylesheet" href="inc/main.css">
	<link rel="stylesheet" href="inc/shared.css">

	<!-- Latest compiled and minified JavaScript -->
	<script src="inc/bootstrap-3.2.0-dist/js/bootstrap.min.js"></script>

	<!-- Local JS -->
	<script src="inc/functions.js"></script>
</head>

<body onBlur="window.focus();">	
	<div class="container tool">

		<div class="row-fluid">
			<div id="header" class="col-md-12">								
				<a class="no_dec" href=".">
					<img class="refstats_logo" src="inc/logo_small.png" >
				</a>
			</div>
		</div>
		
		<!-- Message reporting and action logging -->
		<?php		
		session_start();		
		locationSetter();			
		userSetter();

		// if get
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			if (isset($_SESSION['result']) && $_SESSION['result'] == "success") {
				$current_hour_block = date('H');
				reporter("green", "<a style='color:green;'href='crud/edit.php?id={$_SESSION['last_trans_id']}&origin=index'>Count <strong>".number_format($_SESSION['gate_count_string'])."</strong>, recorded for {$hour_blocks[$current_hour_block]}</a>");
			}
			elseif (isset($_SESSION['result']) && $_SESSION['result'] == "fail") {
				reporter("red", "Error: Submission Failed", " ");
			}
			elseif (isset($_SESSION['result']) && $_SESSION['result'] == "location") {
				reporter("orange", "You Changed Your Location", " ");
			}
			else {
				reporter("white", "Nothing to report.", "visible");
			}
			session_destroy();
			// RESET user_group cookie
			userSetter();
		}

		// else, if post
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && !array_key_exists("login_refer",$_REQUEST)) {
			
			// set location
			if (isset($_POST['location'])) {
				$_COOKIE['location'] = $_POST['location'];
				setcookie('location', $_POST['location']);				
				$_SESSION['result'] = "location";				
				header('Location: ./', true, 302);
			}	

			// get location
			$location = $_COOKIE['location'];

			// checks if hour block has count
			if (isset($_POST['next_hour_submission'])) {
				$current_hour = date('H') + 1;
			}
			else {
				$current_hour = date('H');
			}	
			$query = "SELECT id, HOUR(timestamp) AS hour, gate_number FROM `$default_table_name` WHERE DATE(timestamp)=DATE(NOW()) AND HOUR(timestamp)=$current_hour AND location = '$location'";
			$result = mysqli_query($link, $query) or trigger_error(mysqli_error()); 
			$total_results = mysqli_num_rows($result);

			// check if counts submitted are equal and valid
			if ($_POST['count1'] != $_POST['count2'] || !is_numeric($_POST['count1']) || !is_numeric($_POST['count2'])) {
				reporter("red", "Error: Counts do not match or are not numbers.", " ");
			}
			// check that count does not exceed 999,999
			elseif ($_POST['count1'] > 999999 || $_POST['count2'] > 999999) {
				reporter("red", "Error: Count cannot exceed 999,999.", " ");
			}	
			// check location set
			elseif ($_COOKIE['location'] == 'NOPE') {
				reporter("red", "Please Set Your Location", " ");
			}
			// check if count exists for current hour
			elseif ($total_results > 0){

				// if not quietly submitting count for next hour block
				
				$current_hour_block = date('H');
				$next_hour_block = $current_hour_block + 1;

				// check if count is within 15 minutes of hour block, and offer to save
				if (date('i') > 15 && !isset($_POST['next_hour_submission'])){
					echo reporter("red", "Error: Count already recorded for this hour.", " ");
					echo reporter("green", "<form id='near_hour_block_form' action='.' method='POST'><input type='hidden' name='count1' value='{$_POST['count1']}'><input type='hidden' name='count2' value='{$_POST['count2']}'><input type='hidden' name='next_hour_submission' value='1'><strong><a href='#' onclick='document.getElementById(\"near_hour_block_form\").submit()'>NOTE: We are close to the next hour.<br>Click to submit count '{$_POST['count1']}' for {$hour_blocks[$next_hour_block]}.</a></form></strong>", " ");
				}

				else {
					reporter("red", "Error: Count already recorded for this hour, please select a different hour block.", " ");
				}				
			}

			// submit count
			else {	

				// SUBMIT GATE COUNT

				/* generate timestamps: 
					$hour_block_timestamp --> dropping back to 00 minute of current hour (only one allowed for per hour block)
					$original_timestamp --> actual time of transaction, preserved in DB
				*/
				if (isset($_POST['next_hour_submission'])) {
					$hour_block_timestamp = date("Y-m-d H", strtotime('+1 hours'));
				}
				else {
					$hour_block_timestamp = date("Y-m-d H");
				}
				$original_timestamp = date("Y-m-d H:i:s");

				// truncate count
				$count = $_POST['count1'];

				// get ip
				$IP = IPgrabber();

				// get location
				$location = $_COOKIE['location'];

				$query = "INSERT INTO $default_table_name(gate_number, location, timestamp, original_timestamp, ip) VALUES ('$count', '$location', '$hour_block_timestamp', '$original_timestamp', '$IP')";

				if($stmt = mysqli_prepare($link, $query)) {

				    $insert_result = mysqli_stmt_execute($stmt);				    

					if ($insert_result === TRUE) {
						$_SESSION['result'] = "success";
						$_SESSION['date'] = date("h:i:sa");
						$_SESSION['gate_count_string'] = "$count";
						$_SESSION['last_trans_id'] = mysqli_insert_id($link);
					}
					else {						
						$_SESSION['result'] = "success";
					}
				    mysqli_stmt_close($stmt);

				    // redirect to avoid multiple submissions
				    header('Location: ./', true, 302);

		   		}
				// if it fails
				else {
					reporter("red", "Error: Submission Failed.  Failed on query:<br><br>$query", " ");
				}
			}
   		}

		userSetter();
		
		?>

		<div id="ref_actions">
			
			<!-- location choosing -->
			<div class="row-fluid">
				<div class="col-md-12">
					<form action="" method="POST">
					<!-- <label for="location">Select Location</label> -->
					<select class="form-control" id="location" name="location" onchange=this.form.submit()>
						<?php 
						makeLocationDropdown(True,$_COOKIE['location']);						
						?>
					</select>
					</form>
				</div>
			</div> <!-- row -->		


			<!-- transaction recording -->	

			<div class="row-fluid">
				<div class="col-md-12">
					<form action="." method="POST">
						<div class="form-group">
							<!-- <label for="count1">Enter Counts</label> -->
							<input type="text" class="form-control" id="count1" name="count1" id="count1" placeholder="Enter count here">
						</div>
						<div class="form-group">
							<!-- <label for="exampleInputEmail1">Enter Door Counts</label> -->
							<input type="text" class="form-control" name="count2" id="count2" placeholder="Re-enter to confirm">
						</div>	
						<button type="submit" class="btn ref_type_button btn-block">Submit</button>
					</form>
				</div>
			</div>

			<!-- edit buttons -->
			<div class="row-fluid">
				<div class="col-md-12">
					<p>
						<a href="crud/list.php"><button type="button" class="btn btn-sm btn-WSUgreen"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span> Manage</button></a>
						<a href="index.php?logout=True"><button type="button" class="btn btn-sm btn-WSUgreen"><span class="glyphicon glyphicon-log-out" aria-hidden="true"></span> Logout</button></a>
					</p>
				</div>
			</div>

		</div>
		<hr>

		<div id="ref_graph">
			<div class="row-fluid">	
				<div class="col-md-12" id="refreport">				
					<h4 id="toggle_graph">Today's Counts <span style="font-size:50%;">(click to toggle)</span></h4>	
					<div id="table_wrapper">
						<table class="table table-striped table-condensed">						
							<?php						
							statsGraph($link,"index",'','');							
							?>
						</table>
					</div>
				</div>
			</div> 
		</div> 

		<div class="row-fluid">
			<div id="footer" class="col-md-12">
				<a href="http://library.wayne.edu"><img id="logo" src="inc/library_system_w.jpg"/></a>
			</div>
		</div>

	</div> <!-- container -->
</body>
</html>
