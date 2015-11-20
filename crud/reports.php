<?php
include('header.php');
include('../inc/functions.php');
include('../config.php');
		// unset($_REQUEST['buildings']);
if (isset($_REQUEST['submitted'])){

	// establish reporting building, or select all (note: contains 'AND' from actual query to avoid altogether)
	/* ----------------------------------------------------------------------------------------------------- */

	$selected_buildings = array();
	// default to ALL
	if ( isset($_REQUEST['buildings']) && $_REQUEST['buildings'] == array("ALL")){

		# grab from MySQL
		$all_buildings_query = "SELECT DISTINCT(building) AS building FROM $default_table_name";
		$all_buildings_result = mysqli_query($link, $all_buildings_query) or trigger_error(mysqli_error());
		$selected_buildings = array();
		while ($row = mysqli_fetch_assoc($all_buildings_result)) {
			if ($row['building'] != ""){
		    	array_push($selected_buildings, $row['building']);
			}
		}		
	// print_r($selected_buildings);
	}

	elseif ( isset($_REQUEST['buildings']) ) {
		
		// prepare SQL clause
		$building_where = "AND building IN ('".implode("', '",$_REQUEST['buildings'])."')";

		// prepare selected_buildings
		foreach($_REQUEST['buildings'] as $building){
			if ($location != "NOPE" && $building != "ALL" && $building != "MAIN_CAMPUS"){
				array_push($selected_buildings, $building);
			}
		}

	}

	else {
		$cookie_building_mod = substr($_COOKIE['location'], 0, strpos($_COOKIE['location'], '%7C'));
		$building_where = "AND building = {$cookie_building_mod}";
		$selected_buildings = array($_COOKIE['building']);
	}	

	// finish cleaning $selected_buildings
	$selected_buildings = array_unique($selected_buildings);

	// get date limitiers
	$date_start = date("Y-m-d", strtotime($_REQUEST['date_start']));
	$date_end = date("Y-m-d", strtotime($_REQUEST['date_end']));


	// All visits in date range (appropriate for csv export)
	/* ----------------------------------------------------------------------------------------------------- */
	$full_query = "SELECT gate_number, location, building, DAYNAME(timestamp) as day_of_week, DATE(timestamp) AS simple_date, timestamp AS ordering_timestamp FROM $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' $building_where ORDER BY ordering_timestamp DESC";
	// echo $full_query;


	$full_result = mysqli_query($link, $full_query) or trigger_error(mysqli_error());
	$all_rows = mysqli_fetch_row($full_result);	
	
	// Total amount of visits in date range
	/* ----------------------------------------------------------------------------------------------------- */
	$MMarray = array("min", "max");
	$min = '';
	$max = '';
	foreach($MMarray as $type) {
		$total_query = "SELECT gate_number FROM $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' AND timestamp IN (SELECT $type(timestamp) FROM $default_table_name GROUP BY DATE(timestamp)) $building_where";
		echo $total_query;
		$total_result = mysqli_query($link, $total_query) or trigger_error(mysqli_error());
		$previous_value = 0;
		if ($total_result){
			while($row = mysqli_fetch_array($total_result)) {
				$$type = (int)$row['gate_number'];
				$$type = $previous_value + $$type;
				$previous_value = $$type;
			}
		}
	}
	$total_people = $max - $min;
	$total_people = $total_people/2;


	// Building Counts
	/* ----------------------------------------------------------------------------------------------------- */
	// $MMarray = array("min", "max");
	// $min = '';
	// $max = '';
	// foreach($MMarray as $type) {
	// 	$total_query = "SELECT gate_number, building FROM $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' AND timestamp IN (SELECT $type(timestamp) FROM $default_table_name GROUP BY DATE(timestamp))";
	// 	$total_result = mysqli_query($link, $total_query) or trigger_error(mysqli_error());
	// 	$previous_value = 0;
	// 	if ($total_result){
	// 		while($row = mysqli_fetch_array($total_result)) {
	// 			$$type = (int)$row['gate_number'];
	// 			$$type = $previous_value + $$type;
	// 			$previous_value = $$type;
	// 		}
	// 	}
	// }
	// $total_people = $max - $min;
	// $total_people = $total_people/2;

	// Query for Building Totals
	/* ----------------------------------------------------------------------------------------------------- */
	// create buildings array for case calls
	$building_cases = '';
	foreach($selected_buildings as $building) {
		$building_cases .= ", COUNT(CASE WHEN building = '$building' THEN DATE(timestamp) END) AS $building";
	}

	$buildings_total_query = "SELECT gate_number, DATE(timestamp) AS date_string FROM $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' $building_where GROUP BY DATE(timestamp) ORDER BY date_string DESC";
	// echo $buildings_total_query;
	$buildings_total_result = mysqli_query($link, $buildings_total_query) or trigger_error(mysqli_error());

	// create arrays for each building
	$buildings_total_sorted = array();
	foreach($selected_buildings as $building) {
		if (!array_key_exists($building, $buildings_total_sorted)) {
			$buildings_total_sorted[$building] = array();
		}
	}

	// loop through rows
	while ($row = mysqli_fetch_assoc($buildings_total_result)) {	
		foreach($selected_buildings as $building) {
			array_push($buildings_total_sorted[$building], array( $row['date_string'], $row[$building] ) );
		}
		
	}
	


	// Transaction counts
	/* ----------------------------------------------------------------------------------------------------- */
	$type_query = "SELECT ref_type, COUNT(ref_type) AS ref_type_count FROM $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' $building_where GROUP BY ref_type";
	// echo $type_query;
	$type_result = mysqli_query($link, $type_query) or trigger_error(mysqli_error());
	$type_counts = array();
	while($row = mysqli_fetch_assoc($type_result)) {		
		$type_counts[$ref_type_hash[$row['ref_type']]] = $row['ref_type_count'];
	}


	// Busiest Day-of-the-week (dow)
	/* ----------------------------------------------------------------------------------------------------- */
	$dow_query = "SELECT DAYNAME(timestamp) AS dow_name, DAYOFWEEK(timestamp) AS dow_index, count(DAYOFWEEK(timestamp)) AS dow_count FROM $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' $building_where GROUP BY dow_index ORDER BY dow_index;";
	$dow_result = mysqli_query($link, $dow_query) or trigger_error(mysqli_error());
	$dow_counts = array();
	while($row = mysqli_fetch_assoc($dow_result)) {		
		$dow_counts[$row['dow_name']] = $row['dow_count'];
	}


	// Busiest Hours
	/* ----------------------------------------------------------------------------------------------------- */
	$hour_query = "SELECT HOUR(timestamp) AS hour, COUNT(CASE WHEN ref_type = 1 THEN ref_type END) AS Directional, COUNT(CASE WHEN ref_type = 2 THEN ref_type END) AS Brief, COUNT(CASE WHEN ref_type = 3 THEN ref_type END) AS Extended, COUNT(CASE WHEN ref_type = 4 THEN ref_type END) AS Consultation FROM $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' $building_where GROUP BY hour;";
	$hour_result = mysqli_query($link, $hour_query) or trigger_error(mysqli_error());
	$hour_counts = array();
	while($row = mysqli_fetch_assoc($hour_result)) {		
		$hour_counts[$row['hour']] = array(
			"Directional" => $row['Directional'],
			"Brief" => $row['Brief'],
			"Extended" => $row['Extended'],
			"Consultation" => $row['Consultation'],
		);
	}


	// Busiest Single Days
	/* ----------------------------------------------------------------------------------------------------- */
	$single_query = "SELECT DAYNAME(timestamp) as dow_name, DATE(timestamp) AS date, count(ref_type) AS ref_count FROM $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' $building_where GROUP BY date ORDER BY ref_count DESC limit 5;";
	$single_result = mysqli_query($link, $single_query) or trigger_error(mysqli_error());

	/* Data Explanations:
	Recommended to use json_encode() for each array to use with charts.js

	$full_result = MySQL result set for all queries
	$total_date_range_results = Total visits from all buildings.

	$buildings_total_result_sorted = array of arrays, with daily counts from buildings

	$building_totals = array of buildings, each an array full of timestamp.  Can be used to create graph.

	$trans_result = MySQL result set for counts of transaction types
	$type_counts = An associative array from ALL buildings - key is type as string, value is count from database

	$dow_counts = Associative array with Day-of-the-Week (dow) names and total visits counts for that day

	$hour_counts = Nested Associative array with hours of the day, and numbers for "Directional", "Brief", "Extended", and "Consultation"

	$busiest_day_counts 
	*/

	// set display flags
	$export_display = "block";
	$quickstats_display = "block";

}


?>

		<!-- hidden div for messages -->
		<div id="report_messages" class="row">
			<div class="col-md-12" id="report_message"></div>
		</div>

		<!-- Limiters -->
		<div id="limiters" class="row">
			<div class="col-md-12">
				<h3>Select Location(s) and Date Range</h3>		
				<form action="reports.php" method="GET" class="form" role="form">
					<div class="row">											

						<div class="form-group col-md-12">
							<ul class="checkbox_grid">
								<?php
									// select visits from DROPDOWN, or default to current tool building
									$current_report_building_array = array();									
									if (isset($_REQUEST['buildings'])) {																		
										$current_report_building_array = $_REQUEST['buildings'];										
									}
								?>

								<li>
									<div class="checkbox">
										<label>
											<input id="ALL_checkbox" type="checkbox" name="buildings[]" onclick="$('input').not(this).prop('checked', false);" value="ALL" <?php if ($_REQUEST['buildings'] == array("ALL")) { echo "checked";} ?> > All 
										</label>
									</div>
								</li>	
								<!-- <li>
									<div class="checkbox">
										<label>
											<input id="ALL_checkbox" type="checkbox" name="buildings[]" value="MAIN_CAMPUS" <?php if ( in_array("MAIN_CAMPUS", $_REQUEST['buildings'])) { echo "checked";} ?> > Main Campus 
										</label>
									</div>
								</li> -->
<!-- 								<li>
									<div class="checkbox">
										<label>
											<input id="ALL_checkbox" type="checkbox" name="buildings[]" value="PK" <?php if ( in_array("PK", $_REQUEST['buildings'])) { echo "checked";} ?> > Purdy/Kresge 
										</label>
									</div>
								</li> -->
														

								<?php  makeCheckboxGrid(False, $current_report_building_array); ?>
							</ul>
						</div>

					</div>
					<div class="row">
						<div class="form-group col-md-3">
							<label>Start Date:</label>
							<input type="text" class="form-control" id="date_start" name="date_start" placeholder="please click to set" value="<?php if (isset($_REQUEST['date_start'])) {echo $_REQUEST['date_start'];}  ?>" >
							<script>
								$(function() {
									$( "#date_start" ).datepicker(({altField: "#date_start"}));
								});
							</script>
						</div>
						<div class="form-group col-md-3">
							<label>End Date:</label>
							<input type="text" class="form-control" id="date_end" name="date_end" placeholder="please click to set" value="<?php if (isset($_REQUEST['date_end'])) {echo $_REQUEST['date_end'];}  ?>" >
							<script>
								$(function() {
									$( "#date_end" ).datepicker(({altField: "#date_end"}));
								});
							</script>
						</div>
					</div>					
					<div class="row">
						<div class="form-group col-md-1">
							<input type="hidden" name="submitted" value="true"/>
							<button type="submit" class="btn btn-default">Update</button>
						</div>
					</div>
				</form>
			</div>
		</div>


		<!-- QuickStats -->
		<div id="quickstats" class="row" style="display:<?php echo $quickstats_display; ?>">
			<div id="stats_results" class="col-md-12">

				<!-- top row -->
				<div class="row">
					<div class="col-md-6">
						<h3 style="text-align:center;">QuickStats</h3>
						<p><strong>Total Visits</strong>: <?php echo $total_people; ?></p>
					</div>
					<div class="col-md-6" style="text-align:center;">
						<h3>Export Data</h3>				
						<form action="export_csv.php" method="POST">
							<input type="hidden" name="params" value='<?php echo json_encode($_REQUEST);?>'/>
							<button id="csv_button" type="submit" class="btn btn-WSUgreen" onclick="loadingCSV('Working...','Download as CSV');">Download as Spreadsheet (.csv)</button>
						</form>
					</div>
				</div>

				<hr class="quickstats_dividers">

				<div class="row">
					
					<!-- Pie Chart -->
<!-- 					<div class="col-md-6">
						<div id="transBreakdown"></div>
						<script type="text/javascript">
							transBreakdown(<?php echo json_encode($type_counts); ?>);
						</script>
					</div> -->

					<!-- DOW Bar Chart -->
					<div class="col-md-6">
						<div id="busiestDOWChart"></div>
						<script type="text/javascript">
							busiestDOW(<?php echo json_encode($dow_counts); ?>);
							console.log("Day of Week");
							console.log(<?php echo json_encode($dow_counts); ?>);
						</script>					
					</div>
					
				</div>

				<hr class="quickstats_dividers">

				<div class="row">
					<!-- Line Chart -->
					<div class="col-md-6">
						<div id="transPerLocation"></div>
						<script type="text/javascript">
							transPerLocation(<?php echo json_encode($buildings_total_sorted); ?>,'<?php echo $date_start; ?>');
							console.log("Foot Traffic at Each Gate");
							console.log(<?php echo json_encode($buildings_total_sorted); ?>,'<?php echo $date_start; ?>');
						</script>	
					</div>			
					
					<!-- Hour Bar -->
<!-- 					<div class="col-md-6">
						<div id="busiestHoursChart"></div>
						<script type="text/javascript">
							busiestHours(<?php echo json_encode($hour_counts); ?>);
						</script>						
					</div> -->

			</div>
		</div>	

		<div class="row">
			<div class="col-md-12 spacer40"></div>
		</div>

		<!-- footer -->
		<?php include('footer.php') ?>

	<body>
</html>
