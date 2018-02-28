<?php
$mysqli = new mysqli("localhost","dckayakc_wrd2","TmXLz4noBY", "dckayakc_wrd2");
 
$q=$mysqli->query("Select id, name, description, upperlevel, lowerlevel, ideal, latitude, longitude FROM wp_playspots");
while($e=$q->fetch_assoc())
        $playspots[]=$e;


$levelQuery=$mysqli->query("SELECT latestlevel FROM `wp_flowrates` WHERE siteid = 01646500 LIMIT 1");
$latestLevel= $levelQuery->fetch_object()->latestlevel;

$fp = fopen('playspots.csv', 'w');

fputcsv($fp, array('id', 'name', 'description', 'upperlevel', 'lowerlevel', 'ideal', 'latitude', 'longitude', 'display'));

 foreach ($playspots as $spot) {
 	if ($latestLevel < $spot['lowerlevel']) {
 		$spot['display']= 'red';
 	} elseif ($latestLevel >= $spot['lowerlevel'] and $latestLevel <= $spot['upperlevel']) {
 		$spot['display'] = 'green';
 	} elseif ($latestLevel > $spot['upperlevel']) {
 		$spot['display'] = 'blue';
 	}
 	fputcsv($fp, $spot);
}
 
fclose($fp);
 
$mysqli->close();
?>