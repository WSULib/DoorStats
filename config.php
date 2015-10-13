<?php

/*
	configuration file for RefStats
	Production - updated 11/19/2014
*/

// location is in 'inc/dbs/'
$config_file = "door_stats_config.php";

// primary table name
$default_table_name = "door_stats_gh_dev";

// location array used to populate location dropdowns around app
$location_array = array(
	"NOPE" => "Please Select Your Door",
	"PURDYEAST" => "Purdy East (PK)",
	"PURDYWEST" => "Purdy West (PK)",
	"KRESGEWEST" => "Kresge West (PK)" ,
	"UGLEAST" => "Undergraduate East (UGL)",
	"UGLWEST" => "Undergraduate West (UGL)"	
);

// location array used to populate location dropdowns around app
$simple_location_array = array();
foreach (array_keys($location_array) as $location) {
	if ($location != "NOPE") {
		array_push($simple_location_array, $location);
	}
}


// wide-open, reference locations
$ip_white_list = array(
	"141.217.54.36", // GH 
	"141.217.54.38", // GH 
	"141.217.172.161",
	"141.217.175.115",
	"141.217.175.55",
	"141.217.175.58",
	"141.217.208.25",
	"141.217.54.89", // CH
	"141.217.84.120",
	"141.217.84.130",
	"141.217.84.146",
	"141.217.84.164",
	"141.217.84.165",
	"141.217.84.182",
	"141.217.84.183",
	"141.217.84.187",
	"141.217.84.239",
	"141.217.84.44",
	"141.217.98.26",
	"146.9.153.130",
	"146.9.153.138",
	"146.9.153.150",
	"146.9.153.151",
	"146.9.153.172",
	"146.9.153.191",
	"146.9.153.192",
	"35.16.92.182",
	"50.249.166.130"
);


?>
