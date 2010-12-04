<?php

$ACTIVE_THRESHOLD=12;
if( $_GET['active'] ) 
	$ACTIVE_THRESHOLD=$_GET['active'];

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
//  item3: { 'location_finder'=>'count'}
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
		if( strstr($hit->page, 'location_finder.php') )
		{
			if( ! isset($location[$key]) )
				$location[$key] = 0;
			$location[$key] += 1;
		}
		else
		{
			if( ! isset($resort[$key]) )
				$resort[$key] = 0;
			$resort[$key] += 1;
		}
	}

	return array($ids, $pages, $resort, $location);
}

$file = './hits_log';
$hits = parse_hits_log($file);

list($ids, $pages, $resorts, $locations) = find_uniques($hits);
arsort($ids);
arsort($pages);
arsort($resorts);
arsort($locations);
?>
<html>
<head>
<title>Hit Statistics</title>

<style type="text/css">
h2 {
	background-color: #C6DEFF;
	border-top: 1px solid blue;
	color: black;
	padding-left: 5px;
}
table {
	border-collapse: collapse;
	border: 1px solid blue;
}
tr {
	border-bottom: 1px solid blue;
}
tr.title {
	background-color: #C6DEFF;
	color: black;
	text-align: left;
}
th {
	padding-left: 5px;
}
td {
	padding-left: 5px;
	padding-right: 10px;
	padding-bottom: 5px;
}
</style>
</head>
<body>

<h2>Page Usage</h2>
<p>There are two parameters that can be passed to this page using the HTTP "get" values</p>
<ul>
	<li><b>ignoreNA=y: </b>Ignores hits from automated code testing like test.sh</li>
	<li><b>active=X: </b>Allows you to configure how many hits from a unique ID are required to be considerderd "active"</li>
</ul>
For example:<br/>
<pre>hit_stats.php?ignoreNA=y&amp;active=20</pre>
<p>Will display stats not counting "real" users. A user will only be counted as active if they've had more than 20 hits.</p>

<h2>Pages Hit: (<?php print(count($pages)) ?> Unique)</h2>
<table>
	<tr class="title"><th>Page</th><th>Hits</th></tr>
<?php
	foreach($pages as $page=>$hits)
		print("<tr><td>$page</td><td>$hits</td></tr>\n"); 
?>
</table>

<h2>Location Finder Hits: (<?php print(count($locations)) ?> Regions)</h2>
<table>
	<tr class="title"><th>Location</th><th>Hits</th></tr>
<?php
	foreach($locations as $loc=>$hits)
		print("<tr><td>$loc</td><td>$hits</td></tr>\n"); 
?>
</table>

<h2>Resort Hits: (<?php print(count($resorts)) ?> Resorts)</h2>
<table>
	<tr class="title"><th>Resort</th><th>Hits</th></tr>
<?php
	foreach($resorts as $resort=>$hits)
		print("<tr><td>$resort</td><td>$hits</td></tr>\n"); 
?>
</table>

<?php
	$active = 0;
	foreach($ids as $user=>$hits) {
		if( $hits > $ACTIVE_THRESHOLD )
			$active++;
	}
?>
<h2>Unique Users: <?php print(count($ids))?>, Active Users: <?php print($active)?></h2>
<table>
	<tr class="title"><th>ID</th><th>Hits</th></tr>
<?php
	foreach($ids as $user=>$hits)
		print("<tr><td>$user</td><td>$hits</td></tr>\n"); 
?>
</table>

</body>
</html>

