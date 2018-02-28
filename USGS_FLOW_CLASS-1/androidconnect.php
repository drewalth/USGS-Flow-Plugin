<?php
echo 'starting<br/>';
$mysqli = new mysqli("localhost","dckayakc_wrd2","TmXLz4noBY", "dckayakc_wrd2");
 
// Oh no! A connect_errno exists so the connection attempt failed!
if ($mysqli->connect_errno) {
    // The connection failed. What do you want to do? 
    // You could contact yourself (email?), log the error, show a nice page, etc.
    // You do not want to reveal sensitive information

    // Let's try this:
    echo "Sorry, this website is experiencing problems.";

    // Something you should not do on a public site, but this example will show you
    // anyways, is print out MySQL error related information -- you might log this
    echo "Error: Failed to make a MySQL connection, here is why: \n";
    echo "Errno: " . $mysqli->connect_errno . "\n";
    echo "Error: " . $mysqli->connect_error . "\n";
    
    // You might want to show them something nice, but we will simply exit
    exit;
}

$q=$mysqli->query("SELECT id, name, latestlevel, flowtype, upperlevel, lowerlevel FROM wp_flowrates where latestlevel IS NOT NULL and name != ' Order By name ASC'");
while($e=$q->fetch_assoc())
        $output[]=$e;

echo 'Number of records '. $q->num_rows;
echo $output;
echo json_encode($output);
echo '<br/>complete';
$mysqli->close();
?>