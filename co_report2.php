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

	$location = $_GET['location'];

	//first validate the location:
	if(!get_readable_location($location))
	{
		print "err.msg=invalid location: $location\n";
	}

    $found_cache = have_cache($location);
	if( !$found_cache )
	{
		write_report($location);
	}

	print file_get_contents("co2_$location.txt");
	print "cache.found=$found_cache\n";

function have_cache($location)
{
	$file = "co2_$location.txt";
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

function write_report($loc)
{
    $fp = fopen("co2_$loc.txt", 'w');

	fwrite($fp, "location =  $loc\n");

    $readable = get_readable_location($loc);
    //find the latest snow report email
    $body = Mail::get_most_recent('"SkiMail@snow.com" <SkiMail@snow.com>', '');
    if( $body )
    {
        $summary = get_summaries($body);
        $report = $summary[$loc];
        $keys = array_keys($report);
        for($i = 0; $i < count($keys); $i++)
        {
            $key = $keys[$i];
            fwrite($fp, $key.' = '.$report[$key]."\n");
        }

        list($lat, $lon, $icon, $url) = get_weather_report($loc);
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

/**
 * Parses the email body into a hash map of hashmaps like:
 *  LOCATION=(hash of {snow.daily='24hr, 48hr', snow.total="total", ..)
 */
function get_summaries($body)
{
	$summary = array();

    //find the report date
    $date = "unknown";

    //look for a string like:
    //Here are the current snow conditions as of 5:30 AM MST this morning, for Monday, April 6, 2009:
    $idx = strpos($body, "Here are the current snow conditions as of");
    if($idx !== false )
    {
        $body = substr($body, $idx);
        $idx = strpos($body, "\n");
        $buf = substr($body, 0, $idx);
        $idx = strrpos($buf, ','); //the comma AFTER April 6
        $buf = substr($body, 0, $idx);
        $idx = strrpos($buf, ','); //the comma BEFORE April 6
        $date = substr($buf, $idx+1);
    }

    //The report is surrounded by the string:
    //***************************************
    $start = strpos($body, '***************************************');
    if( $start !== false )
    {
        //39 = length of "*" string
        $start += 39;
        $end = strpos($body, '***************************************', $start);
        $body = substr($body, $start, $end-$start);

        $lines = split("\n", $body);

        for($i = 0; $i < count($lines); $i++)
        {
            //looking for someting like: Beaver Creek   (www.beavercreek.com)
            preg_match_all("/(www.)/", $lines[$i], $matches, PREG_OFFSET_CAPTURE);
            if( count($matches[1]) == 1 )
            {
                $data = array();

                $data['date'] = $date;

                $idx = $matches[1][0][1];
                $loc = trim(substr($lines[$i], 0, $idx-1));
                $url = substr($lines[$i], $idx, strlen($lines[$i])-$idx-1);

                $data['location'] = $loc;
                $data['details.url'] = 'http://'.$url;

                //Temp. at 5am MST: 4 F/-16C
                $conditions = $lines[$i+1];
                $parts = preg_split('/:/', $conditions);
                $idx = strpos($parts[1], 'F');
                $data['temp.readings'] = substr($parts[1], 0, $idx).'F';

                //conditions look like: Surface Conditions: Powder/Packed Powder
                $conditions = $lines[$i+2];
                $parts = preg_split('/:/', $conditions);
                $data['snow.conditions'] = $parts[1];

                //24 hr snow looks like: Snowfall in last 24 hours: 5 in.
                $conditions = $lines[$i+3];
                $parts = preg_split('/:/', $conditions);
                $snow24 = $parts[1];

                $conditions = $lines[$i+4];
                $parts = preg_split('/:/', $conditions);
                $snowWeek = $parts[1];

                $data['snow.daily'] = "24hr($snow24) Week($snowWeek)";

                //Mid-Mountain Base: 85 in.
                $conditions = $lines[$i+5];
                $parts = preg_split('/:/', $conditions);
                $data['snow.total'] = $parts[1];

                //Percent of Terrain Open: 100%
                $conditions = $lines[$i+6];
                $parts = preg_split('/:/', $conditions);
                $data['trails.open'] = $parts[1];

                $summary[get_location_code($loc)] = $data;
            }
        }
    }

	return $summary;
}

//performs the reverse of get_readable_location
function get_location_code($loc)
{
    if( $loc == 'Vail')
		return 'VA';
	if( $loc == 'Beaver Creek')
		return 'BC';
	if( $loc == 'Keystone')
		return 'KS';
	if( $loc == 'Breckenridge')
		return 'BK';

	//hope this doesn't happen, but be graceful at the least
	return $loc;
}

/**
 * Turns a 3 digit code like ATA into Alta
 */
function get_readable_location($loc)
{
	if( $loc == 'VA')
		return 'Vail';
	if( $loc == 'BC')
		return 'Beaver Creek';
	if( $loc == 'KS')
		return 'Keystone';
	if( $loc == 'BK')
		return 'Breckenridge';

	//hope this doesn't happen, but be graceful at the least
	return $loc;
}

function get_lat_lon($loc)
{
	if( $loc == 'VA')
		return array(39.639423, -106.371);
	if( $loc == 'BC')
		return array(39.60253, -106.51711);
	if( $loc == 'KS')
		return array(39.60402, -105.954336);
	if( $loc == 'BK')
		return array(39.474249, -106.048871 );
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
?>
