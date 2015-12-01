<?php
include('../inc/functions.php');

// grab all params from quick reports
$_REQUEST = json_decode($_REQUEST['params'],true);


$selected_locations = array();
	// default to ALL
	if ( isset($_REQUEST['buildings']) && $_REQUEST['buildings'] == array("ALL")){
		$location_where = "location = ANY(select location from ref_stats)";
		$selected_locations = $simple_location_array; // from config.php
	}

	elseif ( isset($_REQUEST['buildings']) ) {
		
		// prepare SQL clause
		$location_where = "location IN ('".implode("', '",$_REQUEST['buildings'])."')";

		// // prepare selected_locations
		foreach($_REQUEST['buildings'] as $location){
			if ($location != "NOPE" && $location != "ALL" && $location != "MAIN_CAMPUS"){
				array_push($selected_locations, $location);
			}
		}

	}

	else {
		$location_where = "location = {$_COOKIE['location']}";
		$selected_locations = array($_COOKIE['location']);
	}	

	// finish cleaning $selected_locations
	$selected_locations = array_unique($selected_locations);
	$selected_locations_string = implode('_',$selected_locations);

	// get date limitiers
	$date_start = date("Y-m-d", strtotime($_REQUEST['date_start']));
	$date_end = date("Y-m-d", strtotime($_REQUEST['date_end']));


// All transactions in date range (appropriate for csv export)
$full_query = "SELECT gate_number, location, building, DAYNAME(timestamp) as day_of_week, DATE(timestamp) AS simple_date, timestamp AS ordering_timestamp FROM $default_table_name WHERE DATE(timestamp) >= '$date_start' AND DATE(timestamp) <= '$date_end' $building_where ORDER BY ordering_timestamp DESC";
$result = mysqli_query($link, $full_query) or trigger_error(mysqli_error());

$fp = fopen('php://output', 'w');
if ($fp && $result) {
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename='DoorStats_Report-$date_start_$date_end-$selected_locations_string.csv'");
    
    // write column names
    $fields = mysqli_num_fields ( $result );
	for ( $i = 0; $i < $fields; $i++ )
	{
	    $header .= mysqli_fetch_field_direct($result, $i)->name . ",";
	}
	echo $header."\n";
    while ($row = $result->fetch_assoc()) {
		$row['ref_type'] = $ref_type_hash[$row['ref_type']];
		fputcsv($fp, array_values($row));
	}
    die;
}

?>