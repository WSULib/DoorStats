<?php
include($_SERVER['DOCUMENT_ROOT'].'inc/dbs/ref_stats_config.php'); 
include('header.php');
include('../inc/functions.php');
?>

		<div class="row">
			<div class="col-md-12">
				<h2>Edit Transaction</h2>				
			</div>
		</div>
		
<? 
if (isset($_GET['id']) ) { 
	$id = (int) $_GET['id']; 
	if ($_SERVER['REQUEST_METHOD'] == "POST") { 
		if ( isset($_POST['date']) ){
			$date = date("Y-m-d", strtotime($_POST['date']));
		}
		else {
			$date = date("Y-m-d");
		}	
		$insert_date = $date." {$_REQUEST['hour']}";
		foreach($_POST AS $key => $value) { $_POST[$key] = mysqli_real_escape_string($link, $value); } 
		$sql = "UPDATE `ref_stats` SET  `ref_type` =  '{$_POST['ref_type']}' ,  `location` =  '{$_POST['location']}' , `user_group` =  '{$_POST['user_group']}' , `ip` =  '{$_POST['ip']}' ,  `timestamp` =  '$insert_date'   WHERE `id` = '$id' "; 
		mysqli_query($link, $sql) or die(mysqli_error());

		// if coming from index.php, return
		if (isset($_REQUEST['origin']) && $_REQUEST['origin'] == 'index' ){
			header('Location: ../', true, 302);
		}
?>

		<div class="row">
			<div class="col-md-10">
				<p style="color:green;">Success!  Transaction edited.</p>
				<a class="btn btn-default" href='list.php'>Back to Transactions</a> 
			</div>
		</div>

<?php
} 
else {	
	$row = mysqli_fetch_array ( mysqli_query($link, "SELECT * FROM `ref_stats` WHERE `id` = '$id' ")); 

?>

		<div class="row">
			<div class="col-md-6">
				<form action='' method='POST' class="form" role="form">		

					<?php
						if ( array_key_exists($_COOKIE['location'], $user_arrays) ) {
					?>
						<div class="form-group">
							<label>Select User Group for this transaction</label>
							<select class="form-control" id="user_group" name="user_group">
								<?php makeUserDropdown(False,$row['user_group']); ?>
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
						<label>Reference Type:</label>		
						<div class='radio'>
							<label>
								<input type='radio' name='ref_type' value='1' <?php if ( $row['ref_type'] == 1) { echo "checked='checked'"; } ?>><span class="btn btn-primary ref_type_button">Directional</span>
							</label>
						</div>
						<div class='radio'>
							<label>
								<input type='radio' name='ref_type' value='2' <?php if ( $row['ref_type'] == 2) { echo "checked='checked'"; } ?>><span class="btn btn-primary ref_type_button">Brief Reference</span>
							</label>
						</div>
						<div class='radio'>
							<label>
								<input type='radio' name='ref_type' value='3' <?php if ( $row['ref_type'] == 3) { echo "checked='checked'"; } ?>><span class="btn btn-primary ref_type_button">Extended Reference</span>
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

					<!-- location -->
					<input type="hidden" id="location" name="location" value="<?php echo $row['location']; ?>"></input>					

					<div class="form-group">						
						<label>IP Address (automatically populated, override only if necessary)</label>
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












