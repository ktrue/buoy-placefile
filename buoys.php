<?php
#---------------------------------------------------------------------------
/*
Program: buoys.php

Purpose: generate a GRLevelX placefile to display buoy data

Usage:   invoke as a placefile in the GrlevelX placefile manager

Requires: decoded buoy data produced by get-buoy-data.php
          buoy-data-inc.php from https://www.ndbc.noaa.gov/data/latest_obs/latest_obs.txt
					buoy-info-inc.php from https://www.ndbc.noaa.gov/activestations.xml

Author: Ken True - webmaster@saratoga-weather.org

Acknowledgement:
  
   Special thanks to Mike Davis, W1ARN of the National Weather Service, Nashville TN office
   for his testing/feedback during development.   

Copyright (C) 2023  Ken True - webmaster@saratoga-weather.org

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	If you enhance or bug-fix the program, please share your modifications
  to the GitHub distribution so others can enjoy your updates.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <https://www.gnu.org/licenses/>.

Version 1.00 - 24-Sep-2023 - initial release

*/
#---------------------------------------------------------------------------

#-----------settings--------------------------------------------------------
date_default_timezone_set('UTC');
$timeFormat = "l, M d";  // time display for date() in popup
#-----------end of settings-------------------------------------------------

$Version = "buoys.php V1.00 - 24-Sep-2023 - webmaster@saratoga-weather.org";
global $Version,$timeFormat;

// self downloader
if (isset($_REQUEST['sce']) && strtolower($_REQUEST['sce']) == 'view' ) {
   //--self downloader --
   $filenameReal = __FILE__;
   $download_size = filesize($filenameReal);
   header('Pragma: public');
   header('Cache-Control: private');
   header('Cache-Control: no-cache, must-revalidate');
   header("Content-type: text/plain");
   header("Accept-Ranges: bytes");
   header("Content-Length: $download_size");
   header('Connection: close');
   
   readfile($filenameReal);
   exit;
}

header('Content-type: text/plain,charset=ISO-8859-1');

if(file_exists("buoy-info-inc.php")) {
	include_once("buoy-info-inc.php");
} else {
	print "Warning: buoy-info-inc.php file not found. Aborting.\n";
	exit;
}
if(file_exists("buoy-data-inc.php")) {
	include_once("buoy-data-inc.php");
} else {
	print "Warning: buoy-data-inc.php file not found. Aborting.\n";
	exit;
}

if(isset($_GET['lat'])) {$latitude = $_GET['lat'];}
if(isset($_GET['lon'])) {$longitude = $_GET['lon'];}
if(isset($_GET['version'])) {$version = $_GET['version'];}

if(isset($latitude) and !is_numeric($latitude)) {
	print "Bad latitude spec.";
	exit;
}
if(isset($latitude) and $latitude >= -90.0 and $latitude <= 90.0) {
	# OK latitude
} else {
	print "Latitude outside range -90.0 to +90.0\n";
	exit;
}

if(isset($longitude) and !is_numeric($longitude)) {
	print "Bad longitude spec.";
	exit;
}
if(isset($longitude) and $longitude >= -180.0 and $longitude <= 180.0) {
	# OK longitude
} else {
	print "Longitude outside range -180.0 to +180.0\n";
	exit;
}	
if(!isset($latitude) or !isset($longitude) or !isset($version)) {
	print "This script only runs via a GRlevelX placefile manager.";
	exit();
}

/*
Sample entry annotated:


*/

gen_header();

foreach ($BuoyData as $bkey => $M) {
	
  if(!isset($M['lat']) or !isset($M['lon'])) {
		continue;
	}
	list($miles,$km,$bearingDeg,$bearingWR) = 
	  GML_distance((float)$latitude, (float)$longitude,(float)$M['lat'], (float)$M['lon']);
	if($miles <= 250) {
		gen_entry($bkey,$M,$miles,$bearingWR);
	}
}

#---------------------------------------------------------------------------
function gen_header() {
	global $Version;
	$title = "NDBC Buoy Observations";
	print '; placefile with conditions generated by '.$Version. '
; Generated on '.gmdate('r').'
;
Title: '.$title.' - '.gmdate('r').' - Saratoga-Weather.org 
Refresh: 5
Color: 255 255 255
Font: 1, 12, 1, Arial
IconFile: 1, 19, 43, 2, 43, windbarbs-kt-white.png
IconFile: 2, 17, 17, 8, 8, buoy-icons.png
Threshold: 999

';
	
}

#---------------------------------------------------------------------------

function gen_entry($bkey,$M,$miles,$bearingWR) {
/*
  Purpose: generate the detail entry with popup for the buoy report

*/	
  global $BuoyInfo;
	static $iconByTypes = array (
  'fixed' => 2,
  'buoy' => 2,
  'usv' => 4,
  'dart' => 2,
  'tao' => 4,
  'other' => 4,
  'oilrig' => 4,
  );
/*
$BuoyInfo = array (
  'LEGEND' => 'lat|lon|elev|type|name|pgm|owner|met|currents|waterquality|dart',
  'UPDATED' => '2023-09-23T17:00:01UTC',
  'B-0Y2W3' => '44.794|-87.313|179|fixed|Sturgeon Bay CG Station, WI|IOOS Partners|U.S.C.G. Marine Reporting Stations|n|n|n|n',

$BuoyData = array(

  'B-41112' => 
  array (
    'lat' => '30.709',
    'lon' => '-81.292',
    'UTC' => '2023-09-23T16:00:00+00:00',
    'dwaveht' => '3.6',
    'waveht' => '3.6 ft (1.1 m)',
    'DPD' => '13 sec',
    'APD' => '4.8 sec',
    'MWD' => '78',
    'wavedir' => 'ENE',
    'dtemp' => '75.9',
    'temp' => '75.9F (24.4C)',
    'dwtemp' => '81.1',
    'wtemp' => '81.1F (27.3C)',
  ),
	
May have 
    'dwinddir' => '130',
    'wdir' => 'SE',
    'dwind' => '4',
    'wind' => '4 mph (7 km/h, 4 kt)',
    'dgust' => '6',
    'gust' => '7 mph (11 km/h, 6 kt)',

    'dbaro' => '1012.4',
    'baro' => '29.90 inHg (1012.4 hPa)',
    'tide' => '0.28ft (0.1 m)',
    'ddewpt' => '57.9',
    'dewpt' => '57.9F (14.4C)',
    'dvis' => '1.6',
    'vis' => '1.6 nm',


*/
  list($junk,$buoyid) = explode('-',$bkey);
	if(isset($BuoyInfo[$bkey])) {
		$BI = array_combine(explode('|',$BuoyInfo['LEGEND']),explode('|',$BuoyInfo[$bkey]));
	} else {
		return ("; no BuoyInfo for '$bkey' .. not generating icon/popup.\n");
	}
	
  print "; generate $buoyid ".$BI['name']." at ".$BI['lat'].','.$BI['lon']." at $miles miles $bearingWR \n";
	
  $output = 'Object: '.$BI['lat'].','.$BI['lon']. "\n";
  $output .= "Threshold: 999\n";
  if(isset($M['dwinddir'])) {
  	$barbno = isset($M['dwind'])?pick_wind_icon($M['dwind']):-1;
	  if($barbno > 0) {
      $output .= "Icon: 0,0,".$M['dwinddir'].",1,".$barbno."\n";
	  }
	}
	if(isset($M['dtemp'])) {
    $output .= "Text: -17, 13, 1, ".round($M['dtemp'],0)."\n";
	}
  if(isset($M['dwtemp'])) {
		$output .= "Color: 0 148 255\n";  
    $output .= "Text: -17, -13, 1, ".round($M['dwtemp'],0)."\n";
	  $output .= "Color: 255 255 255\n";
	}
	if(isset($M['dvis'])) {
		$tVis = ($M['dvis'] >= 2.0)?intval($M['dvis']):$M['dvis'];
		if($tVis == 0) {
		$output .= "Color: 250 0 248\n";  
		$output .= "Text: 17, -13, 1, ".$tVis."\n";
		}
		if($tVis <= 1) {
		$output .= "Color: 250 0 248\n";  
		$output .= "Text: 24, -13, 1, ".$tVis."\n";
		}
		if($tVis > 1 && $tVis < 3) {
		$output .= "Color: 247 11 15\n";  
		$output .= "Text: 17, -13, 1, ".$tVis."\n";
		}
		if($tVis >= 3 && $tVis <= 5) {
		$output .= "Color: 255 255 0\n";  
		$output .= "Text: 17, -13, 1, ".$tVis."\n";
		}
		if($tVis > 5) {
		$output .= "Color: 24 189 7\n";  
		$output .= "Text: 17, -13, 1, ".$tVis."\n";
		}
	  $output .= "Color: 255 255 255\n";
	}

	$icon = isset($iconByTypes[$BI['type']])?$iconByTypes[$BI['type']]:4;
	if($icon < 0) {$icon = 2; } # show missing icon if not found

  $output .= "Icon: 0,0,000,2,$icon,\"".gen_popup($buoyid,$BI,$M)."\"\n";
  $output .= "End:\n\n";

  print $output;	
	
}
#---------------------------------------------------------------------------

function pick_wind_icon($speed) {
	# return icon number based on speed in 5mph chunks using https://www.weather.gov/hfo/windbarbinfo
	# as a guide.for windbarbs_75_new.png image
	
	static $barbs = array(2,8,14,20,25,31,37,43,60,66,71,77,83,89,94,100,112,117/*,123*/); #in MPH
	static $barbs = array(2,7,12,17,22,27,32,37,52,47,52,57,62,67,77,82,87,92,97,102); # in KTS
	if($speed > 117) {return(17);}
	for ($i=0;$i<count($barbs);$i++){
	  if($speed <= $barbs[$i]) {break;}
  }

	if($i > 17) {$i = 17; }
	return($i);
	
}
#---------------------------------------------------------------------------

function gen_popup($buoyid,$BI,$M) {
	global $timeFormat;
	# note use '\n' to end each line so GRLevelX will do a new-line in the popup.
	
	$out = strtoupper($BI['type'])." $buoyid ".$BI['name'].'\n   ('.$M['lat'].",".$M['lon'];
	$out .= !empty($M['elev'])?" @ ".$BI['elev'].' ft)\n':')\n';
	$out .= "----------------------------------------------------------".'\n';
	$obsTime = strtotime($M['UTC']);
	$out .= "Time:  ".date($timeFormat,$obsTime)." (".gmdate('H:i',$obsTime).'Z)\n';
	$out .= isset($M['temp'])? "Tair:  ".$M['temp'].'\n':'';
	$out .= isset($M['dewpt'])?"Tdew:  ".$M['dewpt'].'\n':'';
	$out .= isset($M['wtemp'])?"Twtr:  ".$M['wtemp'].'\n':'';

  if(isset($M['wind']) ) {	
  	$out .= "Wind:  ";
		$out .= isset($M['wdir'])?$M['wdir']:'??';
		$out .= " ".$M['wind'].'\n';
  	$out .= isset($M['gust'])?'       gust '.$M['gust'].'\n':'';
	}
	if(isset($M['dbaro']) and $M['dbaro'] > 500) {
	  $out .= "Pres:  ".$M['baro'].'\n';
	}
	if(isset($M['vis'])) {
		$out .= "Vsby:  " .$M['vis'].'\n';
	}

	if(isset($M['waveht'])) {
		$out .= 'Waves: '.$M['waveht'].'\n';
	}
	if(isset($M['DPD'])) {
		$out .= 'DPD:   '.$M['DPD'].'\n';
	}
	if(isset($M['APD'])) {
		$out .= 'APD:   '.$M['APD'].'\n';
	}
	if(isset($M['wavedir'])) {
		$out .="WvDir: ".$M['wavedir'].'\n';
	}
	$out .= (isset($BI['pgm']) or isset($BI['owner']))?'\n':'';
	
  if(isset($BI['pgm'])) {
		$out .= "Pgm:   ".$BI['pgm'].'\n';
	}
  if(isset($BI['owner'])) {
		$out .= "Owner: ".$BI['owner'].'\n';
	}
	
# last line of popup
	$out .= "----------------------------------------------------------";
	$out = str_replace('"',"'",$out);
  return($out);	
}

#---------------------------------------------------------------------------

// ------------ distance calculation function ---------------------
   
    //**************************************
    //     
    // Name: Calculate Distance and Radius u
    //     sing Latitude and Longitude in PHP
    // Description:This function calculates 
    //     the distance between two locations by us
    //     ing latitude and longitude from ZIP code
    //     , postal code or postcode. The result is
    //     available in miles, kilometers or nautic
    //     al miles based on great circle distance 
    //     calculation. 
    // By: ZipCodeWorld
    //
    //This code is copyrighted and has
	// limited warranties.Please see http://
    //     www.Planet-Source-Code.com/vb/scripts/Sh
    //     owCode.asp?txtCodeId=1848&lngWId=8    //for details.    //**************************************
    //     
    /*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
    /*:: :*/
    /*:: This routine calculates the distance between two points (given the :*/
    /*:: latitude/longitude of those points). It is being used to calculate :*/
    /*:: the distance between two ZIP Codes or Postal Codes using our:*/
    /*:: ZIPCodeWorld(TM) and PostalCodeWorld(TM) products. :*/
    /*:: :*/
    /*:: Definitions::*/
    /*::South latitudes are negative, east longitudes are positive:*/
    /*:: :*/
    /*:: Passed to function::*/
    /*::lat1, lon1 = Latitude and Longitude of point 1 (in decimal degrees) :*/
    /*::lat2, lon2 = Latitude and Longitude of point 2 (in decimal degrees) :*/
    /*::unit = the unit you desire for results:*/
    /*::where: 'M' is statute miles:*/
    /*:: 'K' is kilometers (default):*/
    /*:: 'N' is nautical miles :*/
    /*:: United States ZIP Code/ Canadian Postal Code databases with latitude & :*/
    /*:: longitude are available at http://www.zipcodeworld.com :*/
    /*:: :*/
    /*:: For enquiries, please contact sales@zipcodeworld.com:*/
    /*:: :*/
    /*:: Official Web site: http://www.zipcodeworld.com :*/
    /*:: :*/
    /*:: Hexa Software Development Center � All Rights Reserved 2004:*/
    /*:: :*/
    /*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
  function GML_distance($lat1, $lon1, $lat2, $lon2) { 
    $theta = $lon1 - $lon2; 
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
    $dist = acos($dist); 
    $dist = rad2deg($dist); 
    $miles = $dist * 60 * 1.1515;
//    $unit = strtoupper($unit);
	$bearingDeg = fmod((rad2deg(atan2(sin(deg2rad($lon2) - deg2rad($lon1)) * 
	   cos(deg2rad($lat2)), cos(deg2rad($lat1)) * sin(deg2rad($lat2)) - 
	   sin(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($lon2) - deg2rad($lon1)))) + 360), 360);

	$bearingWR = GML_direction($bearingDeg);
	
    $km = round($miles * 1.609344); 
    $kts = round($miles * 0.8684);
	$miles = round($miles);
	return(array($miles,$km,$bearingDeg,$bearingWR));
  }

#---------------------------------------------------------------------------

function GML_direction($degrees) {
   // figure out a text value for compass direction
   // Given the direction, return the text label
   // for that value.  16 point compass
   $winddir = $degrees;
   if ($winddir == "n/a") { return($winddir); }

  if (!isset($winddir)) {
    return "---";
  }
  if (!is_numeric($winddir)) {
	return($winddir);
  }
  $windlabel = array ("N","NNE", "NE", "ENE", "E", "ESE", "SE", "SSE", "S",
	 "SSW","SW", "WSW", "W", "WNW", "NW", "NNW");
  $dir = $windlabel[ (integer)fmod((($winddir + 11) / 22.5),16) ];
  return($dir);

} // end function GML_direction	

# end buoys.php
