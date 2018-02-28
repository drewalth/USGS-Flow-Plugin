<?php
if(!$_POST){
?>

<html>
<body>
<form method="post">
<input type="text" name="sitecode"/>
<input type="submit"/>
</form>
</body>
</html>
<?php
}
else{
include_once('includes/USGSFlowClass.php');

$flow = new newFlow;

echo $flow->GetCFS($_POST["sitecode"],time())."<br/><br/>";

//print_r($flow->GetChange($_POST["sitecode"], 'cfs'));
}
?>