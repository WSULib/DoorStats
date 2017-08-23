<?php
include($_SERVER['DOCUMENT_ROOT'].'inc/dbs/ref_stats_config.php'); 
include('header.php');
include('../inc/functions.php');
?>

		<div class="row">
			<div class="col-md-12">
				<h2>Edit Count</h2>				
			</div>
		</div>
		
<? 
if (isset($_GET['id']) ) { 
	$id = (int) $_GET['id']; 
	if ($_SERVER['REQUEST_METHOD'] == "POST") {
		if ($_POST['count1'] == $_POST['count2'] && is_numeric($_POST['count1']) && is_numeric($_POST['count2'])) {		

			if ( isset($_POST['date']) ){
				$date = date("Y-m-d", strtotime($_POST['date']));
			}
			else {
				$date = date("Y-m-d");
			}	
			$timestamp = $date . " {$_REQUEST['hour']}";			
			foreach($_POST AS $key => $value) { $_POST[$key] = mysqli_real_escape_string($link, $value); } 
			$sql = "UPDATE `$default_table_name` SET  `gate_number` =  '{$_POST['count1']}' ,  `location` =  '{$_POST['location']}' , `ip` =  '{$_POST['ip']}' ,  `timestamp` =  '$timestamp'   WHERE `id` = '$id' ";			
			mysqli_query($link, $sql) or die(mysqli_error());


			// if coming from index.php, return
			if (isset($_REQUEST['origin']) && $_REQUEST['origin'] == 'index' ){
				header('Location: ../', true, 302);
			}

			// report success
			reporter("green", "<div class='row'><div class='col-md-6'>Gate count edited. <a href='list.php'>Back to gate count management.</a>", " ");

		} 
		else {
			reporter("red","Could not edit gate count.  <a href='list.php'>Back to gate count management.</a>"," ");		
		}
	}
	else {	
		$row = mysqli_fetch_array ( mysqli_query($link, "SELECT * FROM `$default_table_name` WHERE `id` = '$id' ")); 

?>

		<!-- <div class="row">
			<div class="col-md-12">
				<h4 class="alert"><strong>Previous Count:</strong> <?php echo number_format($row['gate_number']); ?></h4>
			</div>
		</div> -->

		<div class="row">
			<div class="col-md-6">
				<form action='' method='POST' class="form" role="form">

					<!-- edit count -->
					<div class="form-group">
						<label class="alert_color" for="count1">Update Count (previous count has been automatically entered)</label>
						<input type="text" class="form-control" id="count1" name="count1" id="count1" value="<?php echo $row['gate_number']; ?>">
					</div>
					<div class="form-group">
						<!-- <label for="exampleInputEmail1">Enter Door Counts</label> -->
						<input type="text" class="form-control" name="count2" id="count2" value="<?php echo $row['gate_number']; ?>">
					</div>

					<!-- location -->
					<input type="hidden" id="location" name="location" value="<?php echo $row['location']; ?>"></input>					

					<div class="form-group">						
						<label>IP Address (override only if necessary)</label>
						<input type='text' name='ip' class="form-control" value='<?= stripslashes($row['ip']) ?>'/>
					</div>	

					<div class="form-group">
						<label>Time (hour window)</label>						
						<select class="form-control" id="hour" name="hour">
							<?php

							// derive previous hour							
							$timestamp_linux = strtotime($row['timestamp']);
							$timestamp_hour = date("H",$timestamp_linux);
							$hour = 8;							

							while ($hour < 24) {								
								$startHour = date("g a", strtotime("$hour:00"));
								$endHour = date("g a", strtotime(($hour+1).":00"));

								if ($timestamp_hour != $hour){
									echo "<option id='hour_$hour' value='$hour'>$startHour - $endHour</option>";	
								}
								else {
									// mark selected hour
									echo "<option id='hour_$hour' value='$hour' selected>$startHour - $endHour</option>";									
								}
								$hour ++;
							}
							?>
						</select>
					</div>

					<div class="form-group">
						<label>Date</label>
						<input type="hidden" id="date" name="date">
						<div id="datepicker"></div>
						<script>
							$(function() {
								$( "#datepicker" ).datepicker(({altField: "#date"}));
							});
						</script>
					</div>


					<button type="submit" class="btn btn-default">Submit</button> 
				</form>
			</div>
		</div>

		<!-- footer -->
		<?php include('footer.php') ?>

	</div>

<? 
	} 
}
?> 












