<? 
include('header.php');
include('../inc/functions.php');

// get current page					
if (isset($_REQUEST['page'])) {
	$page = $_REQUEST['page'];
}
else { 
	$page = 0;						
}						

// establish editing location, or select all
if (isset($_REQUEST['edit_location']) && $_REQUEST['edit_location'] == "ALL"){
	$location_where = "location = ANY(select location FROM $default_table_name)";
}
elseif (isset($_REQUEST['edit_location'])) {
	$location_where = "location = '{$_REQUEST['edit_location']}'";
}
elseif (isset($_COOKIE['location']) && $_COOKIE['location'] != "NOPE"){	
	// default to current location
	$location_where = "location = '{$_COOKIE['location']}'";
}
else {
	$_REQUEST['edit_location'] = "ALL";
	$location_where = "location = ANY(select location FROM $default_table_name)";	
}					

// perform query
$query = "SELECT id, gate_number, location, building, ip, DATE_FORMAT(timestamp, '%r') AS hour_block, timestamp AS ordering_timestamp, original_timestamp FROM $default_table_name WHERE DATE(timestamp) = DATE_ADD(CURDATE(), INTERVAL $page DAY) AND $location_where ORDER BY ordering_timestamp DESC";
$result = mysqli_query($link, $query) or trigger_error(mysqli_error());
$total_day_stats = mysqli_num_rows($result);
$results_date = date('l\, m\-j\-y', strtotime( ($page)." days" ));
$graph_date = date('m d Y', strtotime( ($page)." days" ));

?>		
		
		<?php
		if ($_COOKIE['location'] != "NOPE"){
		?>
			<div class="row">
				<div class="col-md-12">
					<h3>Add Count</h3>				
					<a class="btn btn-WSUgreen" href="./new.php">New Count</a></p>	
				</div>				
			</div>		
		<?php
		}	
		?>

		<div class="row">

			<div class="col-md-12">
				<div class="col-md-3">					
					<form action="./list.php" method="GET">	
						<div class="form-group">		
							<label>What location would you like to view? </label>														
							<select class="form-control" id="edit_location" name="edit_location" onchange='this.form.submit()'>
								<?php
								// select count from dropdown, or default to current tool location
								if (isset($_REQUEST['edit_location'])) {									
									$current_edit_location = $_REQUEST['edit_location'];
								}
								else {									
									$current_edit_location = $_COOKIE['location'];
								}
								?>
								<!-- adding "All Locations" option element -->								
								<option <?php if ( $current_edit_location=="ALL") echo 'selected="selected"'; ?> value="ALL">All Locations</option>
								<?php makeLocationDropdown(False, $current_edit_location); ?>							

							</select>	
							<!-- hidden input to maintain current page -->
							<input type="hidden" name="page" value="<?php echo $page; ?>"/>
						</div>
					</form>					
				</div>

				<div class="col-md-6">
					<ul class="pager">											
						<li class=""><a data-toggle="tooltip" data-placement="top" title='<?php echo date('l\, m\-j-y', strtotime( ($page-1)." days" )); ?>' href="list.php?page=<?php echo ($page-1).'&edit_location='.$current_edit_location; ?>">&lt;</a></li>
						<li class=""><a href="list.php?page=0&edit_location=<?php echo $current_edit_location; ?>"><strong>Today</strong></a></li>						
						<li class="<?php if ($page >= 0) {echo 'disabled'; }?>"><a data-toggle="tooltip" data-placement="top" title='<?php echo date('l\, m\-j\-y', strtotime( ($page+1)." days" )); ?>' href="list.php?page=<?php echo ($page+1).'&edit_location='.$current_edit_location;; ?>">&gt;</a></li>
					</ul>				
				</div>

				<div id="transactions_total" class="col-md-3">
					<h4 class="text-center">
						<?php echo "Location: $current_edit_location<br>$results_date<br>Gate counts recorded today: $total_day_stats"; ?>
					</h4>
				</div>		
			
			</div>
		</div>

		<?php
		if ($total_day_stats > 0) {
		?>

			<!-- Table Row -->
			<div id="edit_table" class="row">
				<div class="col-md-6">
					<h4 id="toggle_table">Edit Table <span style="font-size:50%;">(click to toggle)</span></h4>	
				</div>				
				<div class="col-md-12" id="transactions_table">				
					<table class="table table-striped">
						<tr>
							<td><b>Id</b></td> 
							<td><b>Recorded Count</b></td> 
							<td><b>Location</b></td>
							<td><b>Building</b></td> 
							<td><b>Ip</b></td>
							<td><b>Hour Block</b></td>
							<td><b>Orig Record Time</b></td>
							<td><b>Actions</b></td> 
						</tr>
						<?php	
						if ($total_day_stats > 0) {
							while($row = mysqli_fetch_array($result)){ 
								foreach($row AS $key => $value) { 
									$row[$key] = stripslashes($value);
									if ($row['user_group'] == "NOPE"){
										$row['user_group'] = "None";
									} 
								}
								echo "<tr>";  
								echo "<td>" . nl2br( $row['id']) . "</td>";  
								echo "<td class='gate_number{$row['gate_number']}'>" . nl2br( number_format($row['gate_number']) ) . "</td>";  
								echo "<td>" . nl2br( $row['location']) . "</td>";
								echo "<td>" . nl2br( $row['building']) . "</td>";  
								echo "<td>" . nl2br( $row['ip']) . "</td>";  
								echo "<td>" . nl2br( $row['hour_block']) . "</td>";
								echo "<td>" . nl2br( $row['original_timestamp']) . "</td>";  
								echo "<td><a href=edit.php?id={$row['id']}>Edit Count</a> / <a href=delete.php?id={$row['id']}>Delete</a></td> "; 
								echo "</tr>"; 
							}	
						}
						?>					
					</table>				
					<?php	
					if ($total_day_stats == 0) {			
						echo "<h4 style='text-align:center;'>No counts recorded for this day.</h4>";
					}
					?>	
				</div>				
			</div>

			<!-- graph -->
			<?php 
				if ($current_edit_location != 'ALL'){
			?>					
			<div id="stats_graph" class="row">	
				<div class="col-md-12" id="refreport">				
					<h4 id="toggle_graph">Hourly Breakdown <span style="font-size:50%;">(click to toggle)</span></h4>	
					<div id="table_wrapper">
						<table class="table table-striped table-condensed">						
							<?php						
							statsGraph($link, "crud", $current_edit_location, $graph_date);														
							?>
						</table>
					</div>
				</div>
			</div>
			<?php
				} //close else block
			?>

		<?php
		}
		else {
		?>
		<div id="no_trans" class="row">
			<div class="col-md-12">
				<h4>No counts recorded for this day.</h4>
			</div>
		</div>
		<?php
		}
		?>

		<!-- footer -->
		<?php include('footer.php') ?>

	</div>
	<script type="text/javascript">
	$(function () {
	  $('[data-toggle="tooltip"]').tooltip()
	})
	</script>	
</body>
</html>
