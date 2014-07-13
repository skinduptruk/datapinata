
<?php


//open database
$connect = mysqli_connect ("localhost", "dp", "pinata*3");
mysqli_select_db($connect, "datapinata"); //select database

$fields = null;
function getDBcounts(  )
{
	// "Measure" is what is measured eg "estimated resident population"
	// "frequency" is how often data was collected eg "Annual"
	global $fields, $connect;
	$queryreg = mysqli_query($connect, "
	      	SELECT * from counts ORDER BY count DESC");

	while($row = mysqli_fetch_array($queryreg,MYSQLI_ASSOC))
	{
		$fields[$row['field']] = $row['count']; 
		echo "<BR>" . $row['field'] . ' = ' . $row['count'];
	}
}


getDBcounts();

?>

