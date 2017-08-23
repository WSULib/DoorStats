<? 
include($_SERVER['DOCUMENT_ROOT'].'inc/dbs/ref_stats_config.php'); 
include('header.php');
include('../inc/functions.php');
?>



		<div class="row">
			<div class="col-md-12">
				<h2>Add Count</h2>
			</div>
		</div>

<?php
if (isset($_REQUEST['submitted']) & $_REQUEST['location'] != "NOPE") {

	// checks if hour block has count					
	$query = "SELECT id, HOUR(timestamp) AS hour, gate_number FROM `$default_table_name` WHERE HOUR(timestamp)={$_REQUEST['hour']} AND location = '{$_REQUEST['location']}'";
	$result = mysqli_query($link, $query) or trigger_error(mysqli_error()); 
	$total_results = mysqli_num_rows($result);

	// check if count exists for current hour
	if ($total_results > 0){
		$row = mysqli_fetch_assoc($result);
		reporter("red", "Error: Count already recorded for this hour, please <a href='edit.php?id={$row['id']}'>edit</a>", " ");						
	}

	// check if counts submitted are equal and valid
	elseif ($_POST['count1'] != $_POST['count2'] || !is_numeric($_POST['count1']) || !is_numeric($_POST['count2'])) {
		reporter("red", "Error: Counts do not match or are not numbers.  <a href='list.php'>Back to gate count management.</a>  ", " ");						
	}

	// if all passes, submit
	else{

		foreach($_REQUEST AS $key => $value) { $_REQUEST[$key] = mysqli_real_escape_string($link, $value); } 
		$IP = IPgrabber();

		if ( isset($_POST['date']) ){
			$date = date("Y-m-d", strtotime($_POST['date']));
		}
		else {
			$date = date("Y-m-d");
		}	
		$timestamp = $date . " {$_REQUEST['hour']}";
		$original_timestamp = date("Y-m-d H:i:s");

		$sql = "INSERT INTO `$default_table_name` ( `gate_number` ,  `location` , `ip`, `timestamp`, `original_timestamp` ) VALUES(  '{$_REQUEST['count1']}' ,  '{$_REQUEST['location']}' , '$IP', '$timestamp', '$original_timestamp' ) ";
		$result = mysqli_query($link, $sql) or die(mysqli_error());

		// report success
		reporter("green", "<div class='row'><div class='col-md-6'>Gate count submitted. <a href='list.php'>Back to gate count management.</a>", " ");
	
	}

} 
else {	

?>
		
		<div class="row">
			<div class="col-md-6">
				<form action='new.php' method='POST' class="form" role="form">
					
					<!-- location -->
					<input type="hidden" id="location" name="location" value="<?php echo $_COOKIE['location']; ?>"></input>					

					<!-- add count -->
					<div class="form-group">
						<label for="count1">Enter New Count</label>
						<input type="text" class="form-control" id="count1" name="count1" id="count1" placeholder="Enter count here">
					</div>
					<div class="form-group">
						<!-- <label for="exampleInputEmail1">Enter Door Counts</label> -->
						<input type="text" class="form-control" name="count2" id="count2" placeholder="Re-enter to confirm">
					</div>

					<div class="form-group">
						<label>Time (hour window)</label>						
						<select class="form-control" id="hour" name="hour">
							<?php
							$current_hour = date("H");							
							$hour = 8;							

							while ($hour < 24) {								
								$startHour = date("g a", strtotime("$hour:00"));
								$endHour = date("g a", strtotime(($hour+1).":00"));

								if ($current_hour != $hour){
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
						<label>Date (default is today)</label>
						<input type="hidden" id="date" name="date">
						<div id="datepicker"></div>
						<script>
							$(function() {
								$( "#datepicker" ).datepicker(({altField: "#date"}));
							});
						</script>
					</div>

					<input type="hidden" name="submitted" value="true"/>
					<button type="submit" class="btn btn-default">Submit</button> 
				</form>


				
			</div>			
		</div>

		<!-- footer -->
		<?php include('footer.php') ?>

	</div>


<?php
}
?>

</body>
</html>

