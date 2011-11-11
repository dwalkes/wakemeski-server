<?php

$MULTI_REPORT = 1;
$CGI = array_key_exists('HTTP_ENV_VARS', $GLOBALS);

$script_classes = array();

function run_report($script, $loc, $first)
{
	global $script_classes;
	if( !$first )
		print "##NEXT_REPORT#####\n";
	require_once($script);
	if( array_key_exists($script, $script_classes) )
		$report_class = $script_classes[$script];
	else
		$script_classes[$script] = $report_class;
	$obj = new $report_class();
	$obj->run($loc);
}

if( $CGI )
{
	//running in webserver
	header( "Content-Type: text/plain" );
	$i = 0;
	foreach($_GET['url'] as $url)
	{
		$parts = parse_url($url);
		$script = $parts['path'];
		parse_str($parts['query'], $query);
		$loc = $query['location'];

		run_report($script, $loc, !$i++);
	}
}
else
{
	//running from command line
	if( $argc < 3 || $argc %2 != 1 )
	{
		print "Usage: multi-report.php [<resort.php> <location code>] ...\n";
		print "\n";
		print "Runs 1 or more reports\n";
		exit(1);
	}

	for( $i = 1; $i < $argc; $i+=2)
		run_report($argv[$i], $argv[$i+1], !$i);
}
?>
