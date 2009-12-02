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

	header( "Content-Type: text/plain" );

	$location = $_GET['location'];

	//first validate the location:
	if(!getReadableLocation($location))
	{
		print "err.msg=invalid location: $location\n";
		exit(1);
	}

	$found_cache = have_cache($location);
	if( !$found_cache )
	{
		write_report($location);
	}

	$cache_file = "mt_$location.txt";
	if( is_readable($cache_file) )
	{
		print file_get_contents("mt_$location.txt");
		print "cache.found=$found_cache\n";
	}
	else
	{
		print "err.msg=No ski report data found\n";
	}	


function have_cache($location)
{
	$file = "mt_$location.txt";
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
	$reports = get_reports();
	$report = $reports[$loc];
	if( $report )
	{
		$fp = fopen("mt_$loc.txt", "w");
		
		$keys = array_keys($report);
		for($j = 0; $j < count($keys); $j++)
		{
			$key = $keys[$j];
			fwrite($fp, $key.' = '.$report[$key]."\n");
		}

		list($lat, $lon, $icon, $url) = get_weather_report($loc);
		fwrite($fp, "location.latitude=$lat\n");
		fwrite($fp, "location.longitude=$lon\n");
		fwrite($fp, "weather.url=$url\n");
		fwrite($fp, "weather.icon=$icon\n");
		
		fclose($fp);
	}
}

/**
 * Grabs the RSS feed turns the values for the given report into a hash of:
 *  loc => {snow.daily='24hr, 48hr', snow.total="total", ..}
 */
function get_reports()
{
	//note the content is technically XML, but its a pretty loose form.
	//its actually easier to break up with regular expressions than dealing
	//with it as a DOM
    $contents = file_get_contents("http://feeds.visitmt.com/rss/?feedid=15");

	//make everything one line for the regular expressions to work
	$contents = str_replace("\n", "\t",  $contents);
	
	//each location is in an <item> tag
	$locations = preg_split("/<item>/", $contents);
	//the first item is header junk we can ignore
	array_shift($locations);

	$reports = array();
	for($i = 0; $i < count($locations); $i++)
	{
		$report = get_report($locations[$i]);
		$loc = getLocation($report['location']);
		if( $reports[$loc] )
		{
			//we've hit a duplicate report, make sure we use the latest
			$t1 = strtotime($report['date']);
			$t2 = strtotime($reports[$loc]['date']);
			if( $t2 > $t1 )
				continue;
		}
		$reports[$loc] = $report;
	}	
	
	return $reports;
}

function get_report($body)
{
	$data = array();
	preg_match_all("/<title>(.*)<\/title>/", $body, $matches, PREG_OFFSET_CAPTURE);
	$loc = getLocation($matches[1][0][0]);
	$data['location'] = getReadableLocation($loc);
	
	preg_match_all("/<link>(.*)<\/link>/", $body, $matches, PREG_OFFSET_CAPTURE);
	$data['location.info'] = $matches[1][0][0];

	preg_match_all("/<pubDate>(.*)<\/pubDate>/", $body, $matches, PREG_OFFSET_CAPTURE);	
	$data['date'] = $matches[1][0][0];
	
	preg_match_all("/<STRONG>New Snow:<\/STRONG><\/TD><TD ALIGN='RIGHT'>(.*?)<\/TD>/", $body, $matches, PREG_OFFSET_CAPTURE);
	$new = $matches[1][0][0];
	preg_match_all("/Snow in Last 24 Hours<\/STRONG><\/TD><TD ALIGN='RIGHT'>(\d+)<\/TD>/", $body, $matches, PREG_OFFSET_CAPTURE);
	$day = $matches[1][0][0];
	if( !$new ) $new = 0;
	if( !$day ) $day = 0;
	$data['snow.daily'] .= "New($new) 24hr($day)";	
	
	preg_match_all("/<STRONG>Snow Depth:<\/STRONG><\/TD><TD ALIGN='RIGHT'>(\d+)<\/TD><\/TR>/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['snow.total'] = $matches[1][0][0];
	else
		$data['snow.total'] = '--';
	
	preg_match_all("/Tempurature:<\/STRONG><\/TD><TD ALIGN='RIGHT'>(.*?)<\/TD>/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( !$matches[1][0][0] )
		preg_match_all("/Temperature:<\/STRONG><\/TD><TD ALIGN='RIGHT'>(.*?)<\/TD>/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['temp.readings'] = $matches[1][0][0];
	
		
	preg_match_all("/Surface Conditions:<\/STRONG><\/TD><TD ALIGN='RIGHT'>(.*?)<\/TD><\/TR>/", $body, $matches, PREG_OFFSET_CAPTURE);
	if( $matches[1][0][0] )
		$data['snow.conditions'] = $matches[1][0][0];
	
	return $data;
}

/**
 * Turns a 3 digit code like ATA into Alta
 */
function getReadableLocation($loc)
{
	if( $loc == 'TR') return 'Turner Mountain';
	if( $loc == 'MM') return 'Maverick Mountain';
	if( $loc == 'LM') return 'Lost Trail Powder Mountain';
	if( $loc == 'BP') return 'Bear Paw Ski Bowl';
	if( $loc == 'LP') return 'Lookout Pass';
	if( $loc == 'DS') return 'Discovery';
	if( $loc == 'BT') return 'Blacktail Mountain';
	if( $loc == 'BS') return 'Big Sky';
	if( $loc == 'GD') return 'Great Divide';
	if( $loc == 'SD') return 'Showdown';
	if( $loc == 'TP') return 'Teton Pass';
	if( $loc == 'WF') return 'Whitefish';
	if( $loc == 'RL') return 'Red Lodge';
	if( $loc == 'BB') return 'Bridger Bowl';
	if( $loc == 'MS') return 'Montana Snowbowl';
	if( $loc == 'MB') return 'Moonlight Basin';
}

//turns something like "Alta" into ATA
function getLocation($resort)
{
	if( strstr($resort, "Turner Mountain") ) return "TR";
	if( strstr($resort, "Maverick Mountain") ) return "MM";
	if( strstr($resort, "Lost Trail Powder Mountain") ) return "LM";
	if( strstr($resort, "Bear Paw Ski Bowl") ) return "BP";
	if( strstr($resort, "Lookout Pass") ) return "LP";
	if( strstr($resort, "Discovery") ) return "DS";
	if( strstr($resort, "Blacktail Mountain") ) return "BT";
	if( strstr($resort, "Big Sky") ) return "BS";
	if( strstr($resort, "Great Divide") ) return "GD";
	if( strstr($resort, "Showdown") ) return "SD";
	if( strstr($resort, "Teton Pass") ) return "TP";
	if( strstr($resort, "Whitefish") ) return "WF";
	if( strstr($resort, "Red Lodge") ) return "RL";
	if( strstr($resort, "Bridger Bowl") ) return "BB";
	if( strstr($resort, "Montana Snowbowl") ) return "MS";
	if( strstr($resort, "Moonlight Basin") ) return "MB";
}

function get_lat_lon($loc)
{
	if( $loc == 'TR') return array(48.604996, -115.630793 );
	if( $loc == 'MM') return array(45.4349243, -113.1294876 );
	if( $loc == 'LM') return array(45.692912, -113.95166 );
	if( $loc == 'BP') return array(48.164456, -109.670357 );
	if( $loc == 'LP') return array(47.456233, -115.696404 );
	if( $loc == 'DS') return array(46.248783, -113.239448 );
	if( $loc == 'BT') return array(48.014908, -114.369712  );
	if( $loc == 'BS') return array(45.284, -111.402151);
	if( $loc == 'GD') return array(46.752688, -112.312891 );
	if( $loc == 'SD') return array(46.838133, -110.698483 );
	if( $loc == 'TP') return array(47.928807, -112.805196 );
	if( $loc == 'WF') return array(48.484887, -114.353367 );
	if( $loc == 'RL') return array(45.190749, -109.336372  );
	if( $loc == 'BB') return array(45.817659, -110.8958  );
	if( $loc == 'MS') return array(47.013869, -113.999649  );
	if( $loc == 'MB') return array(45.311904, -111.436659  );
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
