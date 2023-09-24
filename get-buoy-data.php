<?php
#---------------------------------------------------------------------------
/*
Program: get-buoy-data.php

Purpose: generate datat for buoys.php to generate a GRLevelX placefile to display buoy data

Usage:   invoke as a cron job at 5 minute intervals in the same directory as buoys.php

Creates: decoded buoy info and data:
           buoy-data-inc.php from https://www.ndbc.noaa.gov/data/latest_obs/latest_obs.txt
					 buoy-info-inc.php from https://www.ndbc.noaa.gov/activestations.xml
				 Those files are included by buoys.php to generate the GRLeelX placefile

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
$Version = "get-buoy-data.php V1.00 - 24-Sep-2023 - webmaster@saratoga-weather.org";
#---------------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-type: text/plain;charset=ISO-8859-1');

#-----------settings (don't change)--------------------------------------------------------
#
$infoXMLURL   = 'https://www.ndbc.noaa.gov/activestations.xml';
$infoXMLfile  = 'activestations.xml';  # cache file name
$BuoyInfoFile = 'buoy-info-inc.php';   # this will be included in buoys.php
#
$dataURL      = 'https://www.ndbc.noaa.gov/data/latest_obs/latest_obs.txt';
$dataTXTfile  = 'latest_obs.txt';      # cache file name
$dataFile     = 'buoy-data-inc.php';   # this will be included in buoys.php
#-----------end of settings-------------------------------------------------
print "$Version\n";

$STRopts = array(
	'http' => array(
		'method' => "GET",
		'protocol_version' => 1.1,
		'header' => "Cache-Control: no-cache, must-revalidate\r\n" . 
			"Cache-control: max-age=0\r\n" . 
			"Connection: close\r\n" . 
			"User-agent: Mozilla/5.0 (get-buoy-info - saratoga-weather.org)\r\n" . 
			"Accept: text/plain,application/xml\r\n"
	) ,
	'ssl' => array(
		'method' => "GET",
		'protocol_version' => 1.1,
		'verify_peer' => false,
		'header' => "Cache-Control: no-cache, must-revalidate\r\n" . 
			"Cache-control: max-age=0\r\n" . 
			"Connection: close\r\n" . 
			"User-agent: Mozilla/5.0 (get-buoy-info - saratoga-weather.org)\r\n" . 
			"Accept: text/plain,application/xml\r\n"
	)
);
$STRcontext = stream_context_set_default($STRopts);

#---------------------------------------------------------------------------
#  get/process the buoy information XML file
#---------------------------------------------------------------------------

$rawXML = file_get_contents($infoXMLURL);
print ".. Loaded $infoXMLURL which has ".strlen($rawXML)." bytes.\n";

if(file_put_contents($infoXMLfile,$rawXML)){
	print ".. saved XML to $infoXMLfile\n";
} else {
	print "-- unable to save to $infoXMLfile\n";
}

$XML = simplexml_load_file($infoXMLURL);
$XMLarray = objectsIntoArray($XML);

#print "XML=".var_export($XML,true)."\n";

$created = $XMLarray['@attributes']['created'];
$count   = $XMLarray['@attributes']['count'];

print ".. $infoXMLfile created='$created' buoy count='$count'\n";
/*
XMLarray=array (
  '@attributes' => 
  array (
    'created' => '2023-09-23T01:25:02UTC',
    'count' => '1323',
  ),
  'comment' => 
  array (
  ),
  'station' => 
  array (
    0 => 
    array (
      '@attributes' => 
      array (
        'id' => '0y2w3',
        'lat' => '44.794',
        'lon' => '-87.313',
        'elev' => '179',
        'name' => 'Sturgeon Bay CG Station, WI',
        'owner' => 'U.S.C.G. Marine Reporting Stations',
        'pgm' => 'IOOS Partners',
        'type' => 'fixed',
        'met' => 'n',
        'currents' => 'n',
        'waterquality' => 'n',
        'dart' => 'n',
      ),
    ),
*/

#print "XMLarray=".var_export($XMLarray,true)."\n";
$BuoyInfo = array();
$varnames = array('lat','lon','elev','type','name','pgm','owner','met','currents','waterquality','dart');
$BuoyInfo['LEGEND'] = implode('|',$varnames);
$BuoyInfo['UPDATED'] = $created;
$types = array();

foreach($XMLarray['station'] as $i => $D) {
	$v = $D['@attributes'];
	$t = array();
	foreach ($varnames as $i=> $key) {
		if(isset($v[$key])) {
			$t[] = trim($v[$key]);
		} else {
			$t[] = '';
		}
	}
	if(isset($v['type'])) {
		if(isset($types[$v['type']])) {
			$types[$v['type']]++;
		} else {
			$types[$v['type']] = 1;
		}
	}
	$BuoyInfo['B-'.strtoupper(trim($v['id']))] = implode('|',$t);
	unset($t);
}
ksort($types);


print ".. buoy counts by type\n";
foreach ($types as $type => $count) {
	print str_pad((string)$count,6,' ',STR_PAD_LEFT)."\t$type\n";
}

$success = file_put_contents($BuoyInfoFile,
"<?php\n# Buoy Info updated $created\n".
"\$BuoyInfo = ".var_export($BuoyInfo,true).";\n"
);
if($success) {
	print ".. saved $BuoyInfoFile with ".count($BuoyInfo). " entries (includes 2 legend entries).\n";
} else {
	print "-- unable to save $BuoyInfoFile\n";
}

#---------------------------------------------------------------------------
#  get/process the current buoy cnditions data
#---------------------------------------------------------------------------

$rawTXT = file_get_contents($dataURL);
print ".. Loaded $dataURL which has ".strlen($rawTXT)." bytes.\n";
$success = file_put_contents($dataTXTfile,$rawTXT);
if($success) {
  print ".. saved raw TXT data to $dataTXTfile.\n";
} else {
	print "-- unable to save TXT data to $dataTXTfile\n";
}

$rawDATA = explode("\n",$rawTXT);
/*
#STN       LAT      LON  YYYY MM DD hh mm WDIR WSPD   GST WVHT  DPD APD MWD   PRES  PTDY  ATMP  WTMP  DEWP  VIS   TIDE
#text      deg      deg   yr mo day hr mn degT  m/s   m/s   m   sec sec degT   hPa   hPa  degC  degC  degC  nmi     ft
13009     8.000  -38.000 2023 09 23 15 00  MM    MM    MM   MM  MM   MM  MM     MM    MM  33.2    MM    MM   MM     MM
1801589  37.37  -122.86  2023 09 23 15 30 183   1.6   2.0  1.1  10   MM  MM 1017.7    MM  14.6  16.8  10.8   MM     MM
22101    37.24   126.02  2023 09 23 14 00 340   5.0    MM  0.0   5   MM  MM     MM    MM  23.7  23.0    MM   MM     MM
22102    34.79   125.78  2023 09 23 16 00  90   3.0    MM  0.5   4   MM  MM     MM    MM  22.1  22.3    MM   MM     MM
  0         1        2    3    4  5  6  7   8     9    10   11  12   13  14     15    16   17    18     19   20     21
*/
$BuoyData = array();

foreach ($rawDATA as $i => $rec) {
	$D = array();
	
	if(substr($rec,0,1) == '#') { continue; }
  $v = preg_split("/\s+/",$rec);
	if(!isset($v[21])) { continue; }
	$key = "B-".$v[0];  # buoy ID
	$lat = $v[1];
	$lon = $v[2];
	$D['lat'] = $lat;
	$D['lon'] = $lon;
	
	$updated = $v[3].'-'.$v[4].'-'.$v[5].'T'.$v[6].':'.$v[7].':'.'00+00:00'; # ISO 8601 date
	$D['UTC'] = $updated;
	
	if(is_numeric($v[8])) {
		list($D['dwinddir'],$D['wdir']) = getWindDir($v[8]);
	}
	if(is_numeric($v[9])){
		list($D['dwind'],$D['wind']) = convertWind($v[9]);
	}
	if(is_numeric($v[10])){
		list($D['dgust'],$D['gust']) = convertWind($v[10]);
	}
	if(is_numeric($v[11])){
		list($D['dwaveht'],$D['waveht']) = convertHeight($v[11]);
	}
	if(is_numeric($v[12])) {
		$D['DPD'] = $v[12].' sec';
	}
	if(is_numeric($v[13])) {
		$D['APD'] = $v[13].' sec';
	}
	if(is_numeric($v[14])) {
		list($D['MWD'],$D['wavedir']) = getWindDir($v[14]);
	}
	if(is_numeric($v[15])) {
		list($D['dbaro'],$D['baro']) = convertBaro($v[15]);
	}
	#$ptdy  = is_numeric($v[16])?$v[16]:'';
	if(is_numeric($v[17])) {
		list($D['dtemp'],$D['temp']) = convertTemp($v[17]);
	}
	if(is_numeric($v[18])) {
		list($D['dwtemp'],$D['wtemp']) = convertTemp($v[18]);
	}
	if(is_numeric($v[19])) {
		list($D['ddewpt'],$D['dewpt']) = convertTemp($v[19]);
	}
	if(is_numeric($v[20])){ 
	  $D['dvis'] = $v[20];
	  $D['vis'] = $v[20].' nm';
	}
	if(is_numeric($v[21])) {
		$D['tide'] = $v[21]. 'ft ('. sprintf("%01.1f", round($v[21] / 3.28084,1))." m)";
	}

  $BuoyData[$key] = $D;
	unset($D);
}
ksort($BuoyData);

$success = file_put_contents($dataFile,
"<?php\n# Buoy Data updated " . gmdate('r')."\n".
"\$BuoyData = ".var_export($BuoyData,true).";\n"
);
if($success) {
	print ".. saved $dataFile with ".count($BuoyData). " buoy entries.\n";
} else {
	print "-- unable to save $dataFile\n";
}


print ".. Done\n";

#---------------------------------------------------------------------------
# functions
#---------------------------------------------------------------------------

# example from PHP.net documentation:
function objectsIntoArray($arrObjData, $arrSkipIndices = array())
 {
     $arrData = array();
     
     // if input is object, convert into array
     if (is_object($arrObjData)) {
         $arrObjData = get_object_vars($arrObjData);
     }
     
     if (is_array($arrObjData)) {
         foreach ($arrObjData as $index => $value) {
             if (is_object($value) || is_array($value)) {
                 $value = objectsIntoArray($value, $arrSkipIndices); // recursive call
             }
             if (in_array($index, $arrSkipIndices)) {
                 continue;
             }
             $arrData[$index] = $value;
         }
     }
     return $arrData;
 }

#---------------------------------------------------------------------------

function convertTemp ($rawtemp) {
	 # input in C
   $dpTemp=1;
	 if(!is_numeric($rawtemp)) {return(array('',''));}
		$tF = sprintf("%01.{$dpTemp}f",round((1.8 * $rawtemp) + 32.0,$dpTemp));
		$tC = sprintf("%01.{$dpTemp}f", round($rawtemp*1.0,$dpTemp));
		return(array($tF,
		"{$tF}F ({$tC}C)")
		);
}
#---------------------------------------------------------------------------

function convertWind  ( $rawwind ) {
	 # input in m/s
   $dpWind=0;
  
   $using = '';
   $WIND = '';
   if(!is_numeric($rawwind)) {return(array('','')); }

   
 // now $WINDkts is wind speed in Knots  convert to desired form and decimals
	 $WINDkts = $rawwind * 1.94384449;
 
   $WINDkmh = sprintf($dpWind?"%02.{$dpWind}f":"%d",round($WINDkts * 1.85200,$dpWind));
   $WINDmph = sprintf($dpWind?"%02.{$dpWind}f":"%d",round($WINDkts * 1.15077945,$dpWind));
	 $WINDkts = sprintf($dpWind?"%02.{$dpWind}f":"%d",round($WINDkts * 1.0,$dpWind));

   return(array(
	   $WINDkts,
	   "$WINDmph mph ($WINDkmh km/h, $WINDkts kt)")
	 );
}
#---------------------------------------------------------------------------

function convertBaro ( $rawpress) {
	# input in hPa
  $dpBaro=2;
	if(!is_numeric($rawpress)) { return(array('','')); }
	$BAROinHg = sprintf("%02.{$dpBaro}f",round($rawpress  / 33.86388158,$dpBaro));

	$BAROhPa  = sprintf("%02.1f",round($rawpress * 1.0,1)); // leave in hPa
	return(array(
	 $BAROhPa,
	 "$BAROinHg inHg ($BAROhPa hPa)"
	));
}
#---------------------------------------------------------------------------

function convertHeight ( $rawheight ) {
	# input in meters
	if(!is_numeric($rawheight)) { return(array('','')); }
	$HEIGHTft = sprintf("%02.1f",round($rawheight * 3.28084 ,1));
	$HEIGHTm  = sprintf("%02.1f",round($rawheight * 1.0 ,1));
	return(array(
	  $HEIGHTft,
		"$HEIGHTft ft ($HEIGHTm m)")
	);
}
#---------------------------------------------------------------------------

function getWindDir ($degrees) {
   // figure out a text value for compass direction
// Given the wind direction, return the text label
// for that value.  16 point compass
  
  if (!is_numeric($degrees)) { return(array(0,'?')); }
  static $windlabel = array ("N","NNE", "NE", "ENE", "E", "ESE", "SE", "SSE", "S",
	 "SSW","SW", "WSW", "W", "WNW", "NW", "NNW");
  $dir = $windlabel[ (integer)fmod((($degrees + 11) / 22.5),16) ];
  return(array($degrees,$dir));

} // end function getWindDir

# end of get-buoy-data.php