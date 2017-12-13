<?php
/* 
 * PHP code snippet to calculate the distance and bearing between two
 * maidenhead QTH locators. 
 *
 * Written by Fabian Kurz, DJ1YFK; losely based on wwl+db by VA3DB.
 *
 * You can do whatever you want with this code.
 *
 */


function valid_locator ($loc) {
	if (ereg("^[A-R]{2}[0-9]{2}[A-X]{2}$", $loc)) {
		return 1;
	}
	else {
		return 0;
	}
}

function loc_to_latlon ($loc) {
	/* lat */
	$l[0] = 
	(ord(substr($loc, 1, 1))-65) * 10 - 90 +
	(ord(substr($loc, 3, 1))-48) +
	(ord(substr($loc, 5, 1))-65) / 24 + 1/48;
	$l[0] = deg_to_rad($l[0]);
	/* lon */
	$l[1] = 
	(ord(substr($loc, 0, 1))-65) * 20 - 180 +
	(ord(substr($loc, 2, 1))-48) * 2 +
	(ord(substr($loc, 4, 1))-65) / 12 + 1/24;
	$l[1] = deg_to_rad($l[1]);

	return $l;
}



function deg_to_rad ($deg) {
	return (M_PI * $deg/180);
}

function rad_to_deg ($rad) {
	return (($rad/M_PI) * 180);
}

function bearing_dist($loc1, $loc2) {

	if (!valid_locator($loc1) || !valid_locator($loc2)) {
		return 0;
	}
		
	$l1 = loc_to_latlon($loc1);
	$l2 = loc_to_latlon($loc2);

	$co = cos($l1[1] - $l2[1]) * cos($l1[0]) * cos($l2[0]) +
			sin($l1[0]) * sin($l2[0]);
	$ca = atan2(sqrt(1 - $co*$co), $co);
	$az = atan2(sin($l2[1] - $l1[1]) * cos($l1[0]) * cos($l2[0]),
				sin($l2[0]) - sin($l1[0]) * cos($ca));

	if ($az < 0) {
		$az += 2 * M_PI;
	}

	$ret[km] = round(6371*$ca);
	$ret[deg] = round(rad_to_deg($az));

	return $ret;
}

/* Example usage: Distance and heading from JO60LK to JO61UA: */
$bd = bearing_dist("JO60LK", "JO61UA");
echo "$bd[km]km, $bd[deg]deg";

?>