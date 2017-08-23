<?php

/*
	configuration file for DoorStats
	updated 08/15/2017
*/

// location is in 'inc/dbs/'
$config_file = "door_stats_config.php";

// primary table name
$default_table_name = "door_stats_dev"; //remove _dev for production

// location array used to populate location dropdowns around app
/*
	Note: For gate locations, the structure of the key (first value in the associative array below) is important.  
	When data is inserted into the database, a MySQL trigger fires splitting on the pipe "|" character, using the first part
	as the building name.

	For example, "PURDY|EAST" will split on the pipe, grabbing "PURDY" as the building name.  The location will remain, "PURDY|EAST"
*/
$location_array = array(
	"NOPE" => "Please Select Your Door",
	"PK|GATE1" => "Purdy (Gate 1)",
	"PK|GATE2" => "Purdy (Gate 2)",
	"PK|KRESGE" => "Kresge" ,
	"UGL|MAIN" => "UGL Main",
	"UGL|OVERNIGHT" => "UGL Overnight",
	"NEEF" => "Arthur Neef Law Library",	
	"SHIFFMAN" => "Shiffman Medical Library",	
	"PHARMACY" => "Pharmacy LRC",	
);

// location array used to populate location dropdowns around app
$simple_location_array = array();
foreach (array_keys($location_array) as $location) {
	if ($location != "NOPE") {
		array_push($simple_location_array, $location);
	}
}

?>
