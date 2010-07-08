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

require_once('nwac.inc');

header( "Content-Type: text/plain" );

	$location = $_GET['location'];
$location = "OSOSNO";
	$resorts = resorts_nwac_get();
	$resort = resort_get_location($resorts, $location);

	$url = 'http://www.nwac.us/products/'.$location;
	$url48 = 'http://www.nwac.us/products/archive/'.$location.'.1';

	$cache_file = 'nwac_'.$location.'.txt';
	$found_cache = cache_available($cache_file, 1200); //60*20 = 20 minutes
	if( !$found_cache )
	{
		//report 1 (the current day's info)
		$lines = get_report_as_lines($url);
		list($data_start, $columns) = get_report_columns($lines);
		$report = get_report_summary($lines, $data_start, $columns);

		//report 2 (the 48 hour info)
		$lines = get_report_as_lines($url48);
		list($data_start, $columns) = get_report_columns($lines);
		$tmp = $report_date; //the next function will ovewrite the value
		$report2 = get_report_summary($lines, $data_start, $columns);
		$report_date = $tmp;

		cache_summary($resort, $cache_file, $report_date, $report, $report2);
	}

	cache_dump($cache_file, $found_cache);

function cache_summary($resort, $cache_file, $report_date, $report, $report2)
{
	$summary = array();
	$summary['snow.daily'] = "";
	$summary['snow.total'] = "";
	$summary['wind.avg'] = "";
	$summary['temp.readings'] = "";
	for( $i = 0; $i < count($report); $i++ )
	{
		list($name, $val) = $report[$i];
		if(preg_match("/^Total Snow/", $name) )
		{
			$summary['snow.total'] .= $val.' ';
		}
		else if(preg_match("/^24/", $name) || preg_match("/^12/", $name))
		{
			//NOTE: There is a bug in the NWAC report for OSOSK9 (Steven's)
			// it reports 12hr rather than 24hr. However its still a 24hr total
			$summary['snow.daily'] .= "Fresh($val)";
			$summary['snow.fresh'] = $val;
		}
		else if(preg_match("/Temp/", $name) )
		{
			$summary['temp.readings'] .= $val.' ';
		}
		else if(preg_match("/Wind/", $name) )
		{
			$summary['wind.avg'] .= $val.' ';
		}
	}

	for( $i = 0; $i < count($report2); $i++ )
	{
		list($name, $val) = $report2[$i];
		if(preg_match("/^24/", $name) )
		{

			$summary['snow.daily'] .= " Yesterday($val)";
		}
	}

	$summary['date'] = $report_date;
	$summary['snow.units'] = 'inches';
	$summary['details.url'] = "http://www.nwac.us/products/$resort->code";

	cache_create($resort, $cache_file, $summary);
}

// returns an array of list(metric, measurment)
function get_report_summary($lines, $data_start, $columns)
{
	global $report_date;

	//find each row data and parse its column info
	$report_data = array();
	for( $i = $data_start; $i < count($lines); $i++ )
	{
		if( trim($lines[$i]) == '' )
		{
			//end of report data
			break;
		}

		$report_cols = array();
		for( $j = 0; $j < count($columns); $j++ )
		{
			$data = substr($lines[$i], $columns[$j][1], $columns[$j][2]);
			array_push($report_cols, trim($data));
		}
		array_push($report_data, $report_cols);
	}

	//now get the current report time from the last row of data
	$parts = preg_split("/\s+/", $lines[$i-1]);
	$hour = $parts[3];
	if( $hour != 0 ) $hour = $hour/100;
	$report_date = $parts[1].'/'.$parts[2].' '.$hour.':00';

	//build summaries for each column
	$report = array();
	for( $i = 0; $i < count($columns); $i++ )
	{
		if( preg_match("/snow/i", $columns[$i][0]) ||
		    preg_match("/wind avg/i", $columns[$i][0]) )
		{
			$vals = array();
			for( $j = 0; $j < count($report_data); $j++ )
			{
				array_push($vals, $report_data[$j][$i]);
			}
			$val = get_average($vals, true);
			array_push($report, array($columns[$i][0], $val));
		}
		else if( preg_match("/temp/i", $columns[$i][0]) )
		{
			$high = 0;
			$low = 0;
			for( $j = 0; $j < count($report_data); $j++ )
			{
				$val = $report_data[$j][$i];
				if( is_numeric($val) )
				{
					if( $j == 0 )
					{
						$high = $low = $report_data[$j][$i];
					}

					if( $val > $high )
						$high = $val;
					if( $val < $low )
						$low = $val;
				}
			}

			array_push($report, array($columns[$i][0], "$high/$low"));
		}
	}

	return $report;
}

// looks through the data set to eliminate "bad" values and then returns an
// average of the good ones.
function get_average($numbers=array(), $partial=false)
{
	if( $partial )
	{
		//this is going to allow us to only look at the last 6 hours
		// of snow data. It should be enough to get good data
		list($ign1, $ign2, $ign3, $numbers) = array_chunk($numbers, count($numbers)/4);
	}

	rsort($numbers);
	$mid = (count($numbers) / 2);
	$median = ($mid % 2 != 0) ? $numbers{$mid-1} : (($numbers{$mid-1}) + $numbers{$mid}) / 2;

	//just to prevent a divide by zero problem
	if( $median == 0 )
		$median = 0.1;

	$num = 0;
	$total = 0;
	$max = 0;

	for( $i = 0; $i < count($numbers); $i++ )
	{
		//if the number is within 200% of the median value
		if( (abs($numbers[$i]-$median)*100/$median) < 200 )
		{
			$num++;
			$total += $numbers[$i];

			if( $numbers[$i] > $max )
				$max = $numbers[$i];
		}
	}

	//now we will return the average as the (average+max)/2
	$val = (($total/$num)+$max)/2;

	//round the val to the nearest tenth
	$val = round($val*10)/10;
	return $val;
}

function get_report_as_lines($url)
{
	$contents = file_get_contents($url);
        $start = strstr($contents, "<pre>");
        $endidx = strrpos($start, "</pre>");
	$contents = substr($start, 0, $endidx);

	return split("\n", str_replace("&#39;", "'", $contents));
}

// returns an array:
//  [0] = row where data starts
//  [1] = list(column_name, column_start, column_end)
function get_report_columns($lines)
{
	for( $i = 0; $i < count($lines); $i++)
	{
		$columns = array();
		if( preg_match('/MM\/DD\s+Hour/', $lines[$i]) )
		{
			//we use $line + 2 since the first two lines
			// for the headers are weird to parse
			preg_match_all("/\S+/", $lines[$i+2], $matches, PREG_OFFSET_CAPTURE);

			for( $j = 0; $j < count($matches[0]); $j++ )
			{
				$col_start = $matches[0][$j][1];
				if( $j + 1 <count($matches[0]) )
				{
					$col_end = $matches[0][$j+1][1] - 1;
				}
				else
				{
					//we've found the last column
					$col_end = strlen($lines[$i]);
				}
				$p1 = substr($lines[$i], $col_start, $col_end-$col_start);
				$p1 = trim($p1);
				$p2 = substr($lines[$i+1], $col_start, $col_end-$col_start);
				$p2 = trim($p2);
				$p3 = substr($lines[$i+2], $col_start, $col_end-$col_start);
				$p3 = trim($p3);

				array_push($columns, array("$p1 $p2 $p3", $col_start, $col_end-$col_start));
			}

			return array($i+4, $columns);
		}
	}

	return 0;
}

?>
