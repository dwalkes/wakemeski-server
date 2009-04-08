<?php
/*
 * Copyright (c) 2008 nombre.usario@gmail.com
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require_once('mail.inc');

	header( "Content-Type: text/plain" );

	$location = get_location_name($_GET['location']);
	if(!location)
	{
		print "err.msg=invalid location: $location\n";
		exit(1);
	}

    $found_cache = have_cache($location);
	if( !$found_cache )
	{
		write_report($location);
	}

	print file_get_contents("nm_$location.txt");
	print "cache.found=$found_cache\n";

function have_cache($location)
{
	$file = "nm_$location.txt";
	if( is_readable($file))
	{
		//get modification time stamp. If its less than
		//120 minutes old, use that copy
		$mod = filemtime($file);
		if( time() - $mod < 7200 ) //=60*120 = 120 minutes
		{
			return 1;
		}
	}
	return 0;
}

function write_report($location)
{
    $fp = fopen("nm_$location.txt", 'w');

	//find the latest snow report email
	$body = Mail::get_most_recent('info@skinewmexico.com', 'Ski New Mexico Mail', true);
	if( $body )
	{
		list($total48, $depth, $conditions, $trails, $lifts)
			= get_report($body, $location);

		fwrite($fp, "snow.total = $depth\n");
		fwrite($fp, "snow.daily = 48hr($total48)\n");
		fwrite($fp, "snow.conditions = $conditions\n");
		fwrite($fp, "trails.open = $trails\n");
		fwrite($fp, "lifts.open = $lifts\n");
		fwrite($fp, "date = ".get_report_date($body)."\n");
		fwrite($fp, "details.url=".get_details_url($location)."\n");

		list($lat, $lon, $icon, $url) = get_weather_report($location);
		fwrite($fp, "location.latitude=$lat\n");
		fwrite($fp, "location.longitude=$lon\n");
		fwrite($fp, "weather.url=$url\n");
		fwrite($fp, "weather.icon=$icon\n");
	}
    else
    {
        fwrite($fp, "err.msg=No ski report data found\n");
    }

    fclose($fp);
}

//the 2nd line contains the location in a string like:
// <td align='center' class='medblu' width='18%' height='25'><b>Taos Ski Valley</b></td>
function get_location($lines)
{
	$location = '';

	$idx1 = strpos($lines[1], '<b>');
	if( $idx1 !== false )
	{
		$idx1 += 3; //get it past the <b>
		$idx2 = strpos($lines[1], '</b>');
		if( $idx2 !== false && $idx2 > $idx1 )
		{
			$location = substr($lines[1], $idx1, $idx2-$idx1);
		}
	}

	return $location;
}

//get the value of the content of the "<td>content</td>" tag
function get_reading($line)
{
	$reading = '';

	$idx1 = strpos($line, '>');
	if( $idx1 !== false )
	{
		$idx1 += 1; //get it past the >
		$idx2 = strrpos($line, '<');
		if( $idx2 !== false && $idx2 > $idx1 )
		{
			$reading = substr($line, $idx1, $idx2-$idx1);
		}
	}

	$idx = strpos($reading, '&quot;');
	if( $idx !== false )
		$reading = substr($reading, 0, $idx);

	return $reading;
}

//returns a list of (48hr, depth, conditions, trails, lifts)
function get_totals($lines)
{
	$total48 = get_reading($lines[2]);
	$depth = get_reading($lines[3]);
	$conditions = get_reading($lines[4]);
	$trails = get_reading($lines[5]);
	$lifts = get_reading($lines[6]);

	return array($total48, $depth, $conditions, $trails, $lifts);
}

//returns a list of (48hr, depth, conditions, trails, lifts)
function get_report($body, $location)
{
	//<tr bgcolor='#EAEAEA'>
	$parts = explode("<tr bgcolor='#EAEAEA'>", $body);
	if( count($parts) > 1 )
	{
		//ignore the first part - its the junk before what we need
		for($i = 1; $i < count($parts); $i++ )
		{
			$lines = explode("\n", $parts[$i]);
			if( strpos(get_location($lines), $location) !== false )
			{
				return get_totals($lines);
			}
		}
	}

	print "err.msg= Unable to find report for $location\n";
	exit(1);
}

//Look for the line that looks like:
// Current report issued: January 27, 2009 at 7:01AM MST<br />
function get_report_date($body)
{
	$date = "?";

	$idx1 = strpos($body, 'Current report issued:');
	if( $idx1 !== false )
	{
		$idx1 += 22; //get it past the search string
		$idx2 = strpos($body, '<', $idx1);
		if( $idx2 !== false && $idx2 > $idx1 )
		{
			$date = substr($body, $idx1, $idx2-$idx1);
			$parts = preg_split('/\s+/', $date);
			$date = $parts[1].' '.$parts[2].' '.$parts[5].' '.$parts[6];
		}
	}

	return $date;
}

function get_details_url($loc)
{
	if( $loc == 'Angel Fire')
		return 'http://www.angelfireresort.com/winter/mountain-snow-report.php';
	if( $loc == 'Enchanted Forest')
		return 'http://www.enchantedforestxc.com/';
	if( $loc == 'Pajarito Mountain')
		return 'http://www.skipajarito.com/conditions.php';
	if( $loc == 'Red River')
		return 'http://redriverskiarea.com/page.php?pname=mountain/snow';
	if( $loc == 'Sandia Peak')
		return 'http://www.sandiapeak.com/index.php?page=snow-report';
	if( $loc == 'Sipapu')
		return 'http://www.sipapunm.com/index.php?option=com_snowreport&view=helloworld&Itemid=73';
	if( $loc == 'Ski Apache')
		return 'http://www.skiapache.com/';
	if( $loc == 'Ski Santa Fe')
		return 'https://www.skisantafe.com/index.php/snow_report';
	if( $loc == 'Taos')
		return 'http://www.skitaos.org/snow_reports/index';
	if( $loc == 'Valles Caldera Nordic')
		return 'http://www.vallescaldera.gov/comevisit/skisnow/';
}

function get_lat_lon($loc)
{
	if( $loc == 'Angel Fire')
		return array(36.3903, -105.2875);
	if( $loc == 'Enchanted Forest')
		return array(36.7063, -105.4053);
	if( $loc == 'Pajarito Mountain')
		return array(35.895129, -106.391785);
	if( $loc == 'Red River')
		return array(36.70859, -105.409924 );
	if( $loc == 'Sandia Peak')
		return array(35.207831, -106.41354 );
	if( $loc == 'Sipapu')
		return array(36.153595, -105.54824);
	if( $loc == 'Ski Apache')
		return array(33.397455, -105.789198);
	if( $loc == 'Ski Santa Fe')
		return array(35.796793, -105.80166);
	if( $loc == 'Taos')
		return array(36.35, -105.27);
	if( $loc == 'Valles Caldera Nordic')
		return array(35.9, -106.55);
}

function get_weather_xml_dom($lat, $lon)
{
	$now = time();
	$tomorrow = $now + (24 * 60 * 60);
	$start = date('Y-m-d', $now);
	$end = date('Y-m-d', $tomorrow);

	$url = "http://www.weather.gov/forecasts/xml/SOAP_server/ndfdXMLclient.php?whichClient=NDFDgen&lat=$lat&lon=$lon&listLatLon=&lat1=&lon1=&lat2=&lon2=&resolutionSub=&listLat1=&listLon1=&listLat2=&listLon2=&resolutionList=&endPoint1Lat=&endPoint1Lon=&endPoint2Lat=&endPoint2Lon=&listEndPoint1Lat=&listEndPoint1Lon=&listEndPoint2Lat=&listEndPoint2Lon=&zipCodeList=&listZipCodeList=&centerPointLat=&centerPointLon=&distanceLat=&distanceLon=&resolutionSquare=&listCenterPointLat=&listCenterPointLon=&listDistanceLat=&listDistanceLon=&listResolutionSquare=&citiesLevel=&listCitiesLevel=&sector=&gmlListLatLon=&featureType=&requestedTime=&startTime=&endTime=&compType=&propertyName=&product=glance&begin=$start&end=$end&icons=icons";
	$xml = file_get_contents($url);

	$sxe = simplexml_load_string($xml);
	return dom_import_simplexml($sxe);
}

//returns a list($lat, $long, $icon, $url)
function get_weather_report($loc)
{
	list($lat, $lon) = get_lat_lon($loc);

	$dom = get_weather_xml_dom($lat, $lon);

	//get the weather report URL
	$node = $dom->getElementsByTagName('moreWeatherInformation')->item(0);
	$url = $node->firstChild->nodeValue;

	//get the icon for the weather description
	$node = $dom->getElementsByTagName('icon-link')->item(0);
	$icon = $node->firstChild->nodeValue;

	return array($lat, $lon, $icon, $url);
}

//these values are tied to the values defined in location_finder.php
function get_location_name($code)
{
	if( $code == 'AF' )
		return 'Angel Fire';
	if( $code == 'EF' )
		return "Enchanted Forest";
	if( $code == 'PM' )
		return "Pajarito Mountain";
	if ( $code == 'RR' )
		return "Red River";
	if( $code == 'SP' )
		return "Sandia Peak";
	if( $code == 'SI' )
		return "Sipapu";
	if( $code == 'SA' )
		return "Ski Apache";
	if( $code == 'SF' )
		return "Ski Santa Fe";
	if( $code == 'TS' )
		return "Taos";
	if( $code == 'VC' )
		return "Valles Caldera Nordic";

	return '';
}

?>
