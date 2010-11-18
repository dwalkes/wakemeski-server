<?php
header( "Content-Type: text/plain" );

function create_hit($line)
{
	$parts = preg_split("/\t/", $line);
	$hit['id'] = $parts[0];
	$hit['time'] = $parts[1];
	$hit['page'] = $parts[2];
	$hit['location'] = $parts[3];
	
	if( $parts[4] == "cached\n" )
		$hit['cached'] = true;
	else
		$hit['cached'] = false;
	return (object)$hit;
}

function parse_hits_log($file)
{
	$fp = fopen($file, 'r');
	
	$hits = array();
	
	if( $fp && flock($fp, LOCK_SH) )
	{
		while( $line = fgets($fp) )
			array_push($hits, create_hit($line));
	
		flock($fp, LOCK_UN);
		fclose($fp);
	}
	else
	{
		print "Unable to open hit_log file: $file\n";
		return false;
	}

	return $hits;
}

// returns a touple of hashes:
//  item0: { 'id'=>'count' }
//  item1: { 'page'=>'count'}
//  item2: { 'resort'=>'count' }
function find_uniques($hits)
{
	$ids = array();
	$pages = array();
	$resort = array();
	foreach($hits as $hit)
	{
		$key = $hit->id;
		if( ! isset($ids[$key]) )
			$ids[$key] = 0;
		$ids[$key] += 1;

		if( $_GET['ignoreNA'] && $key == "n/a" )
			continue;

		$key = $hit->page;
		if( ! isset($pages[$key]) )
			$pages[$key] = 0;
		$pages[$key] += 1;

		$key = $hit->page."/".$hit->location;
		if( ! isset($resort[$key]) )
			$resort[$key] = 0;
		$resort[$key] += 1;
	}

	return array($ids, $pages, $resort);
}

$file = './hits_log';
$hits = parse_hits_log($file);

list($ids, $pages, $resorts) = find_uniques($hits);

print("Pages: ".count($pages)."\n");
foreach($pages as $page=>$hits)
	print("\t$page:\t$hits hits\n");

print("Resorts: ".count($resorts)."\n");
foreach($resorts as $resort=>$hits)
	print("\t$resort:\t$hits hits\n");

print("Uniques IDs: ".count($ids)."\n");
foreach($ids as $id=>$hits)
	print("\t$id:\t$hits hits\n");
?>
