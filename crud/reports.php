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

	}

	elseif ( isset($_REQUEST['buildings']) ) {
		
		// prepare SQL clause
		$building_where = "AND building IN ('".implode("', '",$_REQUEST['buildings'])."')";

		// prepare selected_buildings
		foreach($_REQUEST['buildings'] as $building){
			if ($location != "NOPE" && $building != "ALL"){
				array_push($selected_buildings, $building);
			}
		}

	}

	else {

		$cookie_building_mod = substr($_COOKIE['location'], 0, strpos($_COOKIE['location'], '%7C'));
		$building_where = "AND building = {$cookie_building_mod}";
		$selected_buildings = array($_COOKIE['buildings']);

	}	

	// finish cleaning $selected_buildings
	$selected_buildings = array_unique($selected_buildings);

	// get date limitiers
	$date_start = date("Y-m-d", strtotime($_REQUEST['date_start']));
	$date_end = date("Y-m-d", strtotime($_REQUEST['date_end']));


	// All visits in date range (appropriate for csv export)
	/* ----------------------------------------------------------------------------------------------------- */
	$full_query = "SELECT gate_number, location, building, DAYNAME(timestamp) as day_of_week, DATE(timestamp) AS simple_date, timestamp AS ordering_timestamp FROM $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' $building_where ORDER BY ordering_timestamp DESC";
	$full_result = mysqli_query($link, $full_query) or trigger_error(mysqli_error());
	$all_rows = mysqli_fetch_row($full_result);	
	

	// Total amount of visits (total)
	/* ----------------------------------------------------------------------------------------------------- */
	$total_query = "SELECT (MAX(gate_number) - MIN(gate_number))/2 as visits FROM $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' $building_where GROUP BY location";
	// echo $total_query;
	$total_result = mysqli_query($link, $total_query) or trigger_error(mysqli_error());
	$total_visits = 0;
	while($row = mysqli_fetch_assoc($total_result)) {	
		$total_visits += $row['visits'];
	}


	// Gate Location counts (gate)
	/* ----------------------------------------------------------------------------------------------------- */
	$gate_query = "SELECT (MAX(gate_number) - MIN(gate_number))/2 as visits, location FROM $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' $building_where GROUP BY location";
	$gate_result = mysqli_query($link, $gate_query) or trigger_error(mysqli_error());
	$gate_counts = array();
	while($row = mysqli_fetch_assoc($gate_result)) {	
		$gate_counts[$row['location']] += $row['visits'];
	}


	// Building counts (building)
	/* ----------------------------------------------------------------------------------------------------- */
	$building_query = "SELECT (MAX(gate_number) - MIN(gate_number))/2 as visits, building FROM $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' $building_where GROUP BY location";
	$building_result = mysqli_query($link, $building_query) or trigger_error(mysqli_error());
	$building_counts = array();
	while($row = mysqli_fetch_assoc($building_result)) {	
		$building_counts[$row['building']] += $row['visits'];
	}


	// Busiest Day-of-the-week (dow)
	/* ----------------------------------------------------------------------------------------------------- */
	$dow_query = "SELECT (MAX(gate_number) - MIN(gate_number))/2 as visits, DAYNAME(timestamp) as dow from $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' $building_where GROUP BY location, DATE(timestamp)";
	$dow_result = mysqli_query($link, $dow_query) or trigger_error(mysqli_error());
	$dow_counts = array();
	while($row = mysqli_fetch_assoc($dow_result)) {	
		$dow_counts[$row['dow']] += $row['visits'];
	}
	// ???
	// Find each Friday for each gate; then find the min and max for that specific gate's Friday....so you'd end up with a Friday for each gate that would contain a difference and then repeat if it's a different Friday (like a week later)

	// Busiest Hours (hour)
	/* ----------------------------------------------------------------------------------------------------- */
	// At each gate, select all the hours for each unique day
	$hour_query = "SELECT gate_number as visits, HOUR(timestamp) as hour, location, DATE(timestamp) as simple_date from $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' $building_where";
	$hour_result = mysqli_query($link, $hour_query) or trigger_error(mysqli_error());

	// Organize them into an array where each day contains the hours and vists (already divided by 2) as key, value pairs
	$hour_counts = array();
	while($row = mysqli_fetch_assoc($hour_result)) {
		$location = $row['location'];
		$hour = $row['hour'];
		$simple_date = $row['simple_date'];
		$hour_counts[$location][$simple_date][$hour] = $row['visits'];
		ksort($hour_counts[$location][$simple_date],SORT_NUMERIC);
	}

	// Now, iterate through each gate and find the total in an hour by subtracting new hour - old hour. If no new hour, then ignore the old hour..
	// aka If you have an 8am entry but don't have a 9am entry, then ignore it because you don't know the difference...making it useless
	$hour_counts = figure_hours($hour_counts);


	/* Data Explanations:
	Recommended to use json_encode() for each array to use with charts.js

	$full_result = MySQL result set for all queries
	$total_date_range_results = Total visits from all buildings.

	$buildings_total_result_sorted = array of arrays, with daily counts from buildings

	$building_totals = array of buildings, each an array full of timestamp.  Can be used to create graph.

	$trans_result = MySQL result set for counts of transaction types
	$type_counts = An associative array from ALL buildings - key is type as string, value is count from database

	$dow_counts = Associative array with Day-of-the-Week (dow) names and total visits for that day

	$hour_counts = Associative array with hour and total visits for that hour

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
						<p><strong>Total Visits</strong>: <?php echo $total_visits; ?></p>
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
					<div class="col-md-6">
						<div id="gateBreakdown"></div>
						<script type="text/javascript">
							gateBreakdown(<?php echo json_encode($gate_counts); ?>);
						</script>
					</div>

					<!-- Pie Chart -->
					<div class="col-md-6">
						<div id="buildingBreakdown"></div>
						<script type="text/javascript">
							buildingBreakdown(<?php echo json_encode($building_counts); ?>);
						</script>
					</div>
					
				</div>

				<hr class="quickstats_dividers">

				<div class="row">

					<!-- DOW Bar Chart -->
					<div class="col-md-6">
						<div id="busiestDOWChart"></div>
						<script type="text/javascript">
							busiestDOW(<?php echo json_encode($dow_counts); ?>);
						</script>					
					</div>
					<!-- Hours Bar Chart -->
					<div class="col-md-6">
						<div id="busiestHoursChart"></div>
						<script type="text/javascript">
							busiestHours(<?php echo json_encode($hour_counts); ?>);
						</script>					
					</div>

			</div>
		</div>
		<div class="test col-md-12"></div>

		<?php 
			if ($_SERVER['REMOTE_ADDR'] == "141.217.54.89" || $_SERVER['REMOTE_ADDR'] == "141.217.54.91" || $_SERVER['REMOTE_ADDR'] == "141.217.54.97" || $_GET['fullview'] == "true") {
				echo "<b>SQL for total visits</b>";
	 			echo "<br/>";
	 			echo $total_query;
	 			echo "<br/>";
				echo "<b>SQL for building visit breakdown</b>";
	 			echo "<br/>";
	 			echo $building_query;
	 			echo "<br/>";				
				echo "<b>SQL for hourly visit breakdown</b>";
	 			echo "<br/>";
	 			echo $hour_query;
	 			echo "<br/>";
				echo "<b>SQL for day of the week visit breakdown</b>";
	 			echo "<br/>";
	 			echo $dow_query;
	 			echo "<br/>";
				echo "<b>SQL for gate visit breakdown</b>";
	 			echo "<br/>";
	 			echo $gate_query;
			}
		?>
		<div class="row">
			<div class="col-md-12 spacer40"></div>
		</div>

		<!-- footer -->
		<?php include('footer.php') ?>

	<body>
</html>
