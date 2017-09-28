<?php

include(__DIR__."/../config.php"); //imports relative to "inc/functions.php"
include($_SERVER['DOCUMENT_ROOT'].'inc/dbs/'.$config_file);



function reporter($color, $msg, $visibility) {
		echo '<div id="feedback" class="row-fluid" style="color: '.$color.'; visibility:'.$visibility.';">';
		echo '<div id="msg" class="col-md-12">';
		echo '<h4>'.$msg.'</h4>';
		echo '</div>';
		echo '</div> <!-- row -->';

}

function ipGrabber() {
	$ip = getenv('HTTP_CLIENT_IP')?:
	getenv('HTTP_X_FORWARDED_FOR')?:
	getenv('HTTP_X_FORWARDED')?:
	getenv('HTTP_FORWARDED_FOR')?:
	getenv('HTTP_FORWARDED')?:
	getenv('REMOTE_ADDR');
	return $ip;
}


function locationSetter() {
	if (!isset($_COOKIE['location'])) {
		setcookie('location', 'NOPE');
	}
}

function userSetter() {
	setcookie('user_group', 'NOPE');
}


# function to create location dropdown selections
function makeLocationDropdown($please_select=True, $preset) {	
	
	# get location array from config.php
	global $location_array;
	$array = $location_array;

	if ($please_select == False){
		unset($array['NOPE']);
	}

	// $preset overrides even Cookie location
	foreach ($array as $key => $value) {
		if ($key == $preset) {			
			echo '<option value="'.$key.'" selected>'.$value.'</option>';
		}			
		else {
			echo '<option value="'.$key.'">'.$value.'</option>';
		}
	}
}

# function to create location dropdown selections
function makeCheckboxGrid($please_select=True, $preset_array) {	
	
	# get location array from config.php
	global $location_array;
	$array = $location_array;

	if ($please_select == False){
		unset($array['NOPE']);
	}

	// $preset overrides even Cookie location
	foreach ($array as $key => $value) {
		$location_exceptions = array("PK1","PK2");
		if ( !in_array($key, $location_exceptions) ){
			if ( in_array($key, $preset_array) ){			
				echo '<li><div class="checkbox"><label><input type="checkbox" onclick="$(\'#ALL_checkbox\').not(this).prop(\'checked\', false);" name="locations[]" value="'.$key.'" checked> '.$value.'</label></div></li>';
			}			
			else {
				echo '<li><div class="checkbox"><label><input type="checkbox" onclick="$(\'#ALL_checkbox\').not(this).prop(\'checked\', false);" name="locations[]" value="'.$key.'"> '.$value.'</label></div></li>';
			}	
		}
	}
}


# function to create user dropdown selections
function makeUserDropdown($please_select=True, $preset) {	
	
	# get user array from config.php
	global $user_arrays;
	$array = $user_arrays[$_COOKIE['location']];	

	if ($please_select == False){
		unset($array['NOPE']);
	}

	foreach ($array as $key => $value) {
		if($key == $preset) {
			echo '<option value="'.$key.'" selected>'.$value.'</option>';
		}
		else {
			echo '<option value="'.$key.'">'.$value.'</option>';
		}
	}

}

// function to report 12am - 11pm table rows showing counts
function statsGraph($link, $context, $current_edit_location, $graph_date){		

	// globalize table name
	global $default_table_name;	

	// get location
	$location = $_COOKIE['location'];	

	# main index, current time	
	if ($context == "index") {
		$query = "SELECT id, DATE(timestamp) as date, HOUR(timestamp) AS hour, gate_number FROM `$default_table_name` WHERE DATE(timestamp)=DATE(NOW()) AND location = '$location'";
		$result = mysqli_query($link, $query) or trigger_error(mysqli_error());
		$crud_prefix = "crud/";
	}

	# crud, based on $graph_date
	if ($context == "crud") {
		if ($current_edit_location != "ALL") {
			$location_filter = "AND location = '$current_edit_location'";			
		}		
		$query = "SELECT id, DATE(timestamp) as date, HOUR(timestamp) AS hour, gate_number FROM `$default_table_name` WHERE DATE_FORMAT(timestamp, '%m %d %Y') = '$graph_date' $location_filter";				
		$result = mysqli_query($link, $query) or trigger_error(mysqli_error());
		$crud_prefix = "";
	}	

	// prepare results array
	$shown_hours = array(
		0 => array("12am",""),
		1 => array("1am",""),
		2 => array("2am",""),
		3 => array("3am",""),
		4 => array("4am",""),
		5 => array("5am",""),
		6 => array("6am",""),
		7 => array("7am",""),
		8 => array("8am",""),
		9 => array("9am",""),
		10 => array("10am",""),
		11 => array("11am",""),
		12 => array("12pm",""),
		13 => array("1pm",""),
		14 => array("2pm",""),
		15 => array("3pm",""),
		16 => array("4pm",""),
		17 => array("5pm",""),
		18 => array("6pm",""),
		19 => array("7pm",""),
		20 => array("8pm",""),
		21 => array("9pm",""),
		22 => array("10pm",""),
		23 => array("11pm",""),
	);			

	// push counts to graph
	while($row = mysqli_fetch_array($result)) {		
		$shown_hours[(int)$row['hour']][1].= "<a class='graph_link' href='".$crud_prefix."edit.php?id={$row['id']}'>".number_format($row['gate_number'])."</a>"; 		
	}

	// push to page
	foreach($shown_hours as $hour){
		echo "<tr>";
		echo "<td class='gray_green time_col'>{$hour[0]}</td>";
		echo "<td><strong>{$hour[1]}</strong></td>";
		echo "</tr>";
	}
}

// reference type
$ref_type_hash = array(
	1 => "Directional",
	2 => "Brief",
	3 => "Extended",
	4 => "Consultation"
);


function startsWith($haystack, $needle) {	
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}

function endsWith($haystack, $needle) {
    return $needle === "" || strpos($haystack, $needle, strlen($haystack) - strlen($needle)) !== FALSE;
}

// prepare results array
$hour_blocks = array(
	0 => "12am - 1am",
	1 => "1am - 2am",
	2 => "2am - 3am",
	3 => "3am - 4am",
	4 => "4am - 5am",
	5 => "5am - 6am",
	6 => "6am - 7am",
	7 => "7am - 8am",
	8 => "8am - 9am",
	9 => "9am - 10am",
	10 => "10am - 11am",
	11 => "11am - 12pm",
	12 => "12pm - 1pm",
	13 => "1pm - 2pm",
	14 => "2pm - 3pm",
	15 => "3pm - 4pm",
	16 => "4pm - 5pm",
	17 => "5pm - 6pm",
	18 => "6pm - 7pm",
	19 => "7pm - 8pm",
	20 => "8pm - 9pm",
	21 => "9pm - 10pm",
	22 => "10pm - 11pm",
	23 => "11pm - 12am",
);

?>
