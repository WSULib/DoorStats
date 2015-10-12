<? 
include($_SERVER['DOCUMENT_ROOT'].'inc/dbs/ref_stats_config.php'); 
include('header.php');
include('../inc/functions.php');
?>



		<div class="row">
			<div class="col-md-12">
				<h2>Add Transaction</h2>
			</div>
		</div>

<?php
if (isset($_REQUEST['submitted']) & $_REQUEST['location'] != "NOPE") {
	foreach($_REQUEST AS $key => $value) { $_REQUEST[$key] = mysqli_real_escape_string($link, $value); } 
	$IP = IPgrabber();
	if ( isset($_REQUEST['date']) ){
		$date = date("Y-m-d", strtotime($_REQUEST['date']));
	}
	else {
		$date = date("Y-m-d");
	}	
	$insert_date = $date." {$_REQUEST['hour']}";
	$sql = "INSERT INTO `ref_stats` ( `ref_type` ,  `location` , `user_group`,  `ip`, `timestamp` ) VALUES(  '{$_REQUEST['ref_type']}' ,  '{$_REQUEST['location']}' , '{$_REQUEST['user_group']}' ,  '$IP', '$insert_date'  ) ";
	$result = mysqli_query($link, $sql) or die(mysqli_error());
	?>

		<div class="row">
			<div class="col-md-10">
				<p style="color:green;">Success!  Transaction added.</p>
				<a class="btn btn-default" href='list.php'>Back to Transactions</a> 
			</div>
		</div>

<?php
} 
else {	

?>
		
		<div class="row">
			<div class="col-md-6">
				<form action='new.php' method='POST' class="form" role="form">
					
					<!-- location -->
					<input type="hidden" id="location" name="location" value="<?php echo $_COOKIE['location']; ?>"></input>					

					<!-- if location requires users, show user_group dropdown -->
					<?php
					if ( array_key_exists($_COOKIE['location'], $user_arrays) ) {
					?>
						<!-- user_group -->
						<div class="form-group">	
							<label>Select User Group for this transaction
							</label>													
							<select class="form-control" id="user_group" name="user_group">		
								<?php makeUserDropdown($_COOKIE['location']); ?>						
							</select>
						</div>
					<?php
					}
					else{
						?>
						<input type="hidden" name="user_group" value="NOPE"/>
						<?php
					}
					?>	

					<div class="form-group">
						<label>Reference Type</label>		
						<div class='radio'>
							<label>
								<input type='radio' name='ref_type' value='1'><span class="btn btn-primary ref_type_button">Directional</span>
							</label>
						</div>
						<div class='radio'>
							<label>
								<input type='radio' name='ref_type' value='2'><span class="btn btn-primary ref_type_button">Brief Reference</span>
							</label>
						</div>
						<div class='radio'>
							<label>
								<input type='radio' name='ref_type' value='3'><span class="btn btn-primary ref_type_button">Extended Reference</span>
							</label>
						</div>
						<!-- Shiffman only -->
						<?php
							// Populate dropdown with users if Law or Med
							if ( startsWith($_COOKIE['location'], "MED") ) {
						?>
						<div class='radio'>
							<label>
								<input type='radio' name='ref_type' value='3' <?php if ( $row['ref_type'] == 4) { echo "checked='checked'"; } ?>><span class="btn btn-primary ref_type_button">Extended Reference</span>
							</label>
						</div>
						<?php
							} //end if MED button
						?>
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

