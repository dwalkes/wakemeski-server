<?php

	require_once('mail.inc');
	
	header( "Content-Type: text/plain" );
	
	$location = $_GET['location'];
	
	//first validate the location:
	if(!getReadableLocation($location))
	{
		print "err.msg=invalid location: $location\n";
	}
	
	//find the latest snow report email
	$body = Mail::get_most_recent('Ski Utah <info@mailing.skiutah.com>', 'Ski Report for');
	if( $body )
	{
		$summary = get_summaries($body);
		$report = $summary[$location];
		$keys = array_keys($report);
		for($i = 0; $i < count($keys); $i++)
		{
			$key = $keys[$i];
			print $key.' = '.$report[$key]."\n";
		}
		
		list($lat, $lon, $icon, $url) = get_weather_report($location);
		print "location.latitude=$lat\n";
		print "location.longitude=$lon\n";
		print "weather.url=$url\n";
		print "weather.icon=$icon\n";
	}
	else
	{
		print "err.msg=No ski report data found\n";
	}

/**
 * Parses the email body into a hash map of hashmaps like:
 *  LOCATION=(hash of {snow.daily='24hr, 48hr', snow.total="total", ..)
 */
function get_summaries($body)
{
	$summary = array();

	$lines = split("\n", $body);
	for($i = 0; $i < count($lines); $i++)
	{
		//looking for someting like: ATA [12/01/08]
		preg_match_all("/^\S{3}\s*\[(\d+\/\d+\/\d+)]/", $lines[$i], $matches, PREG_OFFSET_CAPTURE);
		if( count($matches[1]) == 1 )
		{
			$data = array();
			
			$date = $matches[1][0][0];
			$data['date'] = $date;
			
			$loc = substr($lines[$i++], 0, 3);
			$data['location'] = getReadableLocation($loc);
			$data['location.info'] = $lines[$i++];
			
			//now look at the report data:
			$parts = split("\|", $lines[$i+5]);
			$data['snow.total'] = trim($parts[1]);
			$data['snow.daily'] = 'Today('.trim($parts[2]).') Yesterday('.trim($parts[3]).')';

			$runs = trim($parts[4]);
			list($open, $total) = split("\/", $runs);
			$data['trails.open'] = $open;
			$data['trails.total'] = $total;
			
			$lifts = trim($parts[5]);
			list($open, $total) = split("\/", $lifts);
			$data['lifts.open'] = $open;
			$data['lifts.total'] = $total;
			
			$summary[$loc] = $data;
		}
	}
	
	return $summary;
}

/**
 * Turns a 3 digit code like ATA into Alta
 */
function getReadableLocation($loc)
{
	if( $loc == 'ATA')
		return 'Alta';
	if( $loc == 'BVR')
		return 'Beaver Mountain';
	if( $loc == 'BHR')
		return 'Brian Head';
	if( $loc == 'BRT')
		return 'Brighton';
	if( $loc == 'CNY')
		return 'The Canyons';
	if( $loc == 'DVR')
		return 'Deer Valley';
	if( $loc == 'PCM')
		return 'Park City';
	if( $loc == 'POW')
		return 'Powder Mountain';
	if( $loc == 'SBN')
		return 'Snowbasin';
	if( $loc == 'SBD')
		return 'Snowbird';
	if( $loc == 'SOL')
		return 'Solitude';
	if( $loc == 'SUN')
		return 'Sundance';
	if( $loc == 'WLF')
		return 'Wolf Creek';
	
	//hope this doesn't happen, but be graceful at the least
	return $loc;
}

function get_lat_lon($loc)
{
	if( $loc == 'ATA')
		return array(40.57972, -111.6375);
	if( $loc == 'BVR')
		return array(41.96833, -111.54083);
	if( $loc == 'BHR')
		return array(37.69194, -112.83722);
	if( $loc == 'BRT')
		return array(40.6, -111.58278 );
	if( $loc == 'CNY')
		return array(40.685257, -111.556375);
	if( $loc == 'DVR')
		return array(40.63139, -111.47861 );
	if( $loc == 'PCM')
		return array(40.64361, -111.50417 );
	if( $loc == 'POW')
		return array(41.37778, -111.77111);
	if( $loc == 'SBN')
		return array(41.21194, -111.85111);
	if( $loc == 'SBD')
		return array(40.578052, -111.666755 );
	if( $loc == 'SOL')
		return array(40.62556, -111.59444 );
	if( $loc == 'SUN')
		return array(40.38583, -111.58083 );
	if( $loc == 'WLF')
		return array(40.47667, -111.02361 );
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