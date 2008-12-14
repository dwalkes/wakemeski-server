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
		preg_match_all("/^\S{3}\s*\[(\d{2}\/\d{2}\/\d{2})]/", $lines[$i], $matches, PREG_OFFSET_CAPTURE);
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
		return 'Sunbird';
	if( $loc == 'SOL')
		return 'Solitude';
	if( $loc == 'SUN')
		return 'Sundance';
	if( $loc == 'WLF')
		return 'Wolf Creek';
	
	//hope this doesn't happen, but be graceful at the least
	return $loc;
}
?>
