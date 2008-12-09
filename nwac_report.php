<?php
	
	header( "Content-Type: text/plain" );
	
	//TODO Add some caching logic to store a file up to 20 minutes so that
	// we don't pound the NWAC site.

	$location = $_GET['location'];
	$url = 'http://www.nwac.us/products/'.$location;

	$lines = get_report_as_lines($url);
	
	$report_date = trim($lines[1]);
	
	list($data_start, $columns) = get_report_columns($lines);
	
	$report = get_report_summary($lines, $data_start, $columns);

	print_summary($location, $report_date, $report);

function print_summary($location, $report_date, $report)
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
			$summary['snow.total'] .= $val." ";
		}
		else if(preg_match("/Snow/", $name) )
		{
			$summary['snow.daily'] .= $val." ";
		}
		else if(preg_match("/Temp/", $name) )
		{
			$summary['temp.readings'] .= $val." ";
		}
		else if(preg_match("/Wind/", $name) )
		{
			$summary['wind.avg'] .= $val." ";
		}
	}
	
	print "location =  $location\n";
	print "date = $report_date\n";
	print "snow.total = ".$summary['snow.total']."\n";
	print "snow.daily = ".$summary['snow.daily']."\n";
	print "temp.readings = ".$summary['temp.readings']."\n";
	print "wind.avg = ".$summary['wind.avg']."\n";
}

// returns an array of list(metric, measurment)
function get_report_summary($lines, $data_start, $columns)
{
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
			$val = get_average($vals);
			array_push($report, array($columns[$i][0], $val));
		}
		else if( preg_match("/temp/i", $columns[$i][0]) )
		{
			$high = 0;
			$low = 0;
			for( $j = 0; $j < count($report_data); $j++ )
			{
				if( $j == 0 )
				{
					$high = $low = $report_data[$j][$i];
				}
				if( $report_data[$j][$i] > $high )
					$high = $report_data[$j][$i];
				if( $report_data[$j][$i] < $low )
					$low = $report_data[$j][$i];
			}

			array_push($report, array($columns[$i][0], "$high/$low"));
		}
	}
	
	return $report;
}

// looks through the data set to eliminate "bad" values and then returns an
// average of the good ones.
function get_average($numbers=array())
{
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
	return split("\n", file_get_contents($url));
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
