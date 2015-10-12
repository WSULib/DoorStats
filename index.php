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

	<!-- User Login Testing -->
	<script src="inc/jquery.cookie.js" type="text/javascript"></script>
	<script src="inc/userData.js"></script>	



</head>

<body onBlur="window.focus();">	
	<div class="container tool">

		<div class="row-fluid">
			<div id="header" class="col-md-12">								
				<a class="no_dec" href=".">
					<img class="refstats_logo" src="inc/refstats_logo.png" >
				</a>
			</div>
		</div>
		
		<!-- Message reporting and action logging -->
		<?php		
		session_start();		
		locationSetter();			
		userSetter();

		// 
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			if (isset($_SESSION['result']) && $_SESSION['result'] == "success") {								
				reporter("green", "<a style='color:green;'href='crud/edit.php?id={$_SESSION['last_trans_id']}&origin=index'>Submitted '{$_SESSION['ref_type_string']}' at ".$_SESSION['date']."</a>");
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

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && !array_key_exists("login_refer",$_REQUEST)) {
			
			// set location
			if (isset($_POST['location'])) {
				$_COOKIE['location'] = $_POST['location'];
				setcookie('location', $_POST['location']);				
				$_SESSION['result'] = "location";				
				header('Location: ./', true, 302);
			}			
			elseif ($_COOKIE['location'] == 'NOPE') {
				reporter("red", "Please Set Your Location", " ");		
			}
			elseif ($_COOKIE['user_group'] == 'NOPE' && array_key_exists($_COOKIE['location'], $user_arrays) ) {
				reporter("red", "Please Select Your User Type", " ");
			}

			// register reference transaction
			else {
			  	$type = $_POST['type'];
			  	$ip = ipGrabber();
				$location = $_COOKIE['location'];
				$user_group = $_COOKIE['user_group'];

				if (mysqli_connect_errno()) {
					reporter("red", "Error: Submission Failed" . mysqli_connect_error());
				}

				$query = "INSERT into ref_stats(ref_type, location, user_group, ip) VALUES ('$type', '$location', '$user_group', '$ip')";

				if($stmt = mysqli_prepare($link, $query)) {

				    $insert_result = mysqli_stmt_execute($stmt);				    

					if ($insert_result === TRUE) {
						$_SESSION['result'] = "success";
						$_SESSION['date'] = date("h:i:sa");
						$_SESSION['ref_type_string'] = $ref_type_hash[$_POST['type']];
						$_SESSION['last_trans_id'] = mysqli_insert_id($link);
					}
					else {						
						$_SESSION['result'] = "success";
					}
				    mysqli_stmt_close($stmt);
				    header('Location: ./', true, 302);

			   }
			   // if it fails
			   else {
					reporter("error", "Error: Submission Failed ", " ");
			   }				
			} // cookie
			userSetter();
		} // post

		
		?>

		<div id="ref_actions">
			
			<!-- location choosing -->
			<div class="row-fluid">
				<div class="col-md-12">
					<form action="" method="POST">
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
				<form action="" method="POST">
					<input name="type" type="number" value="1"></input>
					<button type="submit" class="btn ref_type_button btn-primary btn-block btn-lg">Directional</button>
				</form>
				</div>
			</div> 

			<div class="row-fluid">
				<div class="col-md-12">
				<form action="" method="POST">
					<input name="type" type="number" value="2">
					<button type="submit" class="btn ref_type_button btn-primary btn-block btn-lg">Brief Reference</button>
				</form>
				</div>
			</div>

			<div class="row-fluid">
				<div class="col-md-12">
				<form action="" method="POST">
					<input name="type" type="number" value="3">
					<button type="submit" class="btn ref_type_button btn-primary btn-block btn-lg">Extended Reference</button>
				</form>
				</div>
			</div>

			<?php
				// Populate dropdown with users if Law or Med
				if ( startsWith($_COOKIE['location'], "MED") ) {
			?>
			<div class="row-fluid">
				<div class="col-md-12">
				<form action="" method="POST">
					<input name="type" type="number" value="4">
					<button type="submit" class="btn ref_type_button btn-primary btn-block btn-lg">Consultation</button>
				</form>
				</div>
			</div> 
			<?php
				} //end if MED button
			?>

			<?php
				// Populate dropdown with users if Law or Med
				if ( array_key_exists($_COOKIE['location'], $user_arrays) ) {
			?>
				<div class="row-fluid">
					<div class="col-md-12">
						<form action="" method="POST">
							<select class="form-control" id="user_group" name="user_group" onchange="userCookie(this.value)">';
								<?php makeUserDropdown($_COOKIE['location']); ?>
							</select>
						</form>
					</div>
				</div>

			<?php
				} //end if user
			?>

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
					<h4 id="toggle_graph">Today's Stats <span style="font-size:50%;">(click to toggle)</span></h4>	
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
