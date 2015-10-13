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
	<title>RefStats Tool - Wayne State University Libraries</title>
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
				reporter("green", "<a style='color:green;'href='crud/edit.php?id={$_SESSION['last_trans_id']}&origin=index'>".number_format($_SESSION['gate_count_string']).", recorded for ".date("Ha")."-".date((date("H") + 1)."a")."</a>");
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
				


			// checks if hour block has transaction					
			$query = "SELECT id, HOUR(timestamp) AS hour, gate_number FROM `$default_table_name` WHERE HOUR(timestamp)=HOUR(NOW()) AND location = '$location'";
			$result = mysqli_query($link, $query) or trigger_error(mysqli_error()); 
			$total_results = mysqli_num_rows($result);

			// check if count exists for current hour
			if ($total_results > 0){
				$row = mysqli_fetch_assoc($result);
				reporter("red", "Error: Count already recorded for this hour, please <a href='crud/edit.php?id={$row['id']}'>edit</a>", " ");						
			}
			// check if counts submitted are equal and valid
			elseif ($_POST['count1'] != $_POST['count2'] || !is_numeric($_POST['count1']) || !is_numeric($_POST['count2'])) {
				reporter("red", "Error: Counts do not match or are not numbers.", " ");						
			}	
			// check location set
			elseif ($_COOKIE['location'] == 'NOPE') {
				reporter("red", "Please Set Your Location", " ");		
			}							
			else {	

				// SUBMIT GATE COUNT

				/* generate timestamps: 
					$hour_block_timestamp --> dropping back to 00 minute of current hour (only one allowed for per hour block)
					$original_timestamp --> actual time of transaction, preserved in DB
				*/
				$hour_block_timestamp = date("Y-m-d H");
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
				    // header('Location: ./', true, 302);

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
						<a href="#" onclick="window.open('./index.php','ref_stats','menubar=0,resizable=0,width=350,height=880');"><button type="button" class="btn btn-sm btn-WSUgreen"><span class="glyphicon glyphicon-new-window" aria-hidden="true"></span> Launch Pop-Up</button></a>
						<a href="RefStats_Tool_Documentation.html" ><button type="button" class="btn btn-sm btn-WSUgreen"><span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span></button></a>
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
