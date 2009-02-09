<?php

	header( "Content-Type: text/plain" );

    $location = $_GET['location'];
    //first validate the location:
	if(!get_readable_location($location))
	{
		print "err.msg=invalid location: $location\n";
        exit(1);
	}

    $found_cache = have_cache($location);
	if( !$found_cache )
	{
		write_report($location);
	}

	print file_get_contents("co_$location.txt");
	print "cache.found=$found_cache\n";

function have_cache($location)
{
	$file = "co_$location.txt";
	if( is_readable($file))
	{
		//get modification time stamp. If its less than
		//120 minutes old, use that copy
		$mod = filemtime($file);
		if( time() - $mod < 72000 ) //=60*120 = 120 minutes
		{
			return 1;
		}
	}
	return 0;
}

function write_report($loc)
{
    $fp = fopen("co_$loc.txt", 'w');

	fwrite($fp, "location =  $loc\n");

    $readable = get_readable_location($loc);
    $report = get_location_report($readable);
    if( $report )
    {
        $props = get_report_props($report);
        $keys = array_keys($props);
		for($i = 0; $i < count($keys); $i++)
		{
			$key = $keys[$i];
			fwrite($fp, $key.' = '.$props[$key]."\n");
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
 * Takes the report's XML node and parse the information out into a hashtable
 */
function get_report_props($report)
{
    $props = array();
    $data = $report->getElementsByTagName('description')->item(0)->nodeValue;
    $lines = split("<br />", $data);
    $snow_24 = '';
    $snow_48 = '';

    for($i = 0; $i < count($lines); $i++)
    {
        $line = trim($lines[$i]);
        list($key, $val) = split(":", $line);
        if( $key == 'Lifts open' )
        {
            list($open, $total) = split("/", $val);
            $props['lifts.open'] = $open;
            $props['lifts.total'] = $total;
        }
        else if( $key == 'Mid-mountain depth')
        {
            $props['snow.total'] = $val;
        }
        else if( $key == 'New snow last 24 Hours' )
        {
            $snow_24 = $val;
        }
        else if( $key == 'New snow last 48 hours' )
        {
            $snow_48 = $val;
        }
        else if( $key == 'Surface conditions')
        {
            $props['snow.conditions'] = $val;
        }
    }

    $props['snow.daily'] = "24hr: $snow_24, 48hr: $snow_48";

    $date = $report->getElementsByTagName('pubDate')->item(0)->nodeValue;
    $date = strtotime($date);
    $props['date'] = date("h:ia M j", $date);

    $props['details.url'] = $report->getElementsByTagName('link')->item(0)->nodeValue;

    return $props;
}

/**
 * Returns the XML node containing the report for a given location or false
 * if one is not found
 */
function get_location_report($loc)
{
    $dom = get_report_xml();

    $items = $dom->getElementsByTagName('item');
    for ($i = 0; $i < $items->length; $i++)
    {
        $title_node = $items->item($i)->getElementsByTagName('title')->item(0);
        $title = trim($title_node->firstChild->nodeValue);
        if(preg_match("/$loc/", $title) )
        {
            return $items->item($i);
        }
    }

    return false;
}

function get_report_xml()
{
    //TODO we could cache this
	$xml = file_get_contents("http://feeds.feedburner.com/snowreport");
    $sxe = simplexml_load_string($xml);
	return dom_import_simplexml($sxe);
}

/**
 * Turns a 2 digit code like AB into Arapohoe Basin
 */
function get_readable_location($loc)
{
	if( $loc == 'AB')
		return 'Arapahoe Basin';
    if( $loc =='AH' )
        return 'Aspen Highlands';
    if( $loc =='AM' )
        return 'Aspen Mountain';
    if( $loc =='BM' )
        return 'Buttermilk';
    if( $loc =='CM' )
        return 'Copper Mountain';
    if( $loc =='CB' )
        return 'Crested Butte';
    if( $loc =='EM' )
        return 'Echo Mountain';
    if( $loc =='EL' )
        return 'Eldora';
    if( $loc =='HW' )
        return 'Howelsen';
    if( $loc =='LV' )
        return 'Loveland';
    if( $loc =='MM' )
        return 'Monarch Mountain';
    if( $loc =='PH' )
        return 'Powderhorn';
    if( $loc =='PG' )
        return 'Purgatory';
    if( $loc == 'SM')
        return 'Silverton Mountain';
    if( $loc == 'SC')
        return 'Ski Cooper';
    if( $loc == 'SN')
        return 'Snowmass';
    if( $loc == 'SV')
        return 'Sol Vista Basin';
    if( $loc == 'ST')
        return 'Steamboat';
    if( $loc == 'SL')
        return 'Sunlight';
    if( $loc == 'TD')
        return 'Telluride';
    if( $loc == 'WP')
        return 'Winter Park';
    if( $loc == 'WC')
        return 'Wolf Creek';

	//hope this doesn't happen, but be graceful at the least
	return $loc;
}

function get_lat_lon($loc)
{
    if( $loc == 'AB')
		return array(39.6448, -105.871);
    if( $loc =='AH' )
        return array(39.181711, -106.856121);
    if( $loc =='AM' )
        return array(39.18428, -106.821903);
    if( $loc =='BM' )
        return array(39.205167, -106.859294);
    if( $loc =='CM' )
        return array(39.4944, -106.138732);
    if( $loc =='CB' )
        return array(38.899932, -106.964249);
    if( $loc =='EM' )
        return array(37.591389, -107.571726);
    if( $loc =='EL' )
        return array(39.937341, -105.5853);
    if( $loc =='HW' )
        return array(40.480533, -106.840605);
    if( $loc =='LV' )
        return array(39.680191, -105.898114);
    if( $loc =='MM' )
        return array(38.512285, -106.332957);
    if( $loc =='PH' )
        return array(39.068912, -108.15068);
    if( $loc =='PG' )
        return array(37.629261, -107.815288);
    if( $loc == 'SM')
        return array(37.791067, -107.666171);
    if( $loc == 'SC')
        return array(39.358897, -106.299256);
    if( $loc == 'SN')
        return array(39.162132, -106.787847);
    if( $loc == 'SV')
        return array(40.04784, -105.898969);
    if( $loc == 'ST')
        return array(40.458905, -106.802092);
    if( $loc == 'SL')
        return array(39.398121, -107.339174);
    if( $loc == 'TD')
        return array(37.9392, -107.8163);
    if( $loc == 'WP')
        return array(39.886791, -105.764279);
    if( $loc == 'WC')
        return array(37.472654, -106.793116);
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