
<html lang="en">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta charset="utf-8">
	<script	src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script>  
	function getDatasets(){
		var server = 'http://stat.abs.gov.au/itt/';
		$.ajax( { url:server+'query.jsp?<?php
$values["age"] = "30";
//echo "<BR>Age =30 test query ";
$request="";
$id="Undefined";
//open database
$connect = mysqli_connect ("localhost", "dp", "pinata*3");
mysqli_select_db($connect, "datapinata"); //select database
matches($values);
$requestExtra ="&TIME_FORMAT=P1M&format=json";
$requestCallback = "&callback=?";
echo $request . $requestExtra . $requestCallback;
?>',
		dataType : 'jsonp',  
			success : function (data){
				$("#data").append( "<table><thead><tr><th>Time</th><th>Value</th></tr></thead>" );
				$("#data").append('<tbody>');
				for( d in data.series){
					$("#data").append( '<tr><td>' + ( d == 0 ? 'Male' : 'Female' ) + '</td><td>Unemployment</td></tr>' );
					for( obs in data.series[d].observations ){
						$("#data").append( '<tr><td>' + data.series[d].observations[obs].Time + '</td><td>' + data.series[d].observations[obs].Value + '</td></tr>' );
					}
				}
				$("#data").append( '</tbody></table>' );
			},
			fail : function( jqxhr, textStatus, error )
			{
				var err = textStatus + ", " + error;
				$("#datasets").html( "Request Failed: " + err  ) 
			}
		});
	}
	$(document).ready(function(){
		getDatasets();
	});
	</script>
	<style>
		#data td {
			width:10em;
		}
	</style>
</head>
<body>
	<h1><?php echo $id ?></h1>
	<div id='data'></div>
</body>
</html>
<?php


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
	//	echo "<BR>" . $row['field'] . ' = ' . $row['count'];
	}
}

function fetchMatches($id, $values)
{
	global $request;
	//$valuesMatch = "";
	foreach($values as $concept => $value)
	{
		if(!isset( $valuesMatch ))
			$requestAND = "&and=$concept.$value";
		else
			$requestAND .= ",$concept.$value";
	}

	$htmlpage = 'http://stat.abs.gov.au/itt/query.jsp?';
	$request = "method=GetGenericData&datasetid=".$id;
	
	// add other fields
		$queryreg = mysqli_query($connect, "
	      	SELECT * from concepts where `id` = '$id' and `concept` != '$concept'");
	$requestOR ="";
	while($row = mysqli_fetch_array($queryreg,MYSQLI_ASSOC))
	{
		if( !isset($concepts[$row['concept']] )) 
			$requestAND .= "," . $row['concept'] "." . $row['value'];
		//else
		//	$requestOR .= .... ;
		$concepts[$row['concept']] = true;
	}
	$request .= $requestAND;

	return ;
	






	$curl = curl_init(); 
	curl_setopt($curl, CURLOPT_URL, $htmlpage);  
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);  
	$str = curl_exec($curl);  
	curl_close($curl);
echo $str;  
	$json= str_get_html($str); 
//	echo $json;
//	$decoded = json_decode($json,true);
//	var_dump($decoded);
//	foreach( $decoded as $dataset2 => $data2 )
//	{
//		echo "<BR>DD2  $dataset2 => $data2" ;
//		foreach( $data2 as $dataset3 => $data3 )
//		{
//			echo "<BR>DD3  $dataset3 => $data3" ;
//		}
//	}
}

function matches( $values )
{
	global $fields, $connect, $id;
	foreach($values as $concept => $value)
	{
		if(!isset( $conceptsMatch ))
			$conceptsMatch = "Where `concept` = '$concept' AND 'code' = '$code' " ;
		else
			$conceptsMatch .= " OR `concept` = '$concept'  AND 'code' = '$code' ";
	}


	$queryreg = mysqli_query($connect, "
	      	SELECT * from concepts $conceptsMatch");

	while($row = mysqli_fetch_array($queryreg,MYSQLI_ASSOC))
	{
		$datasets[$row['id']] = $row['concept']; 
	//	echo "<BR>" . $row['field'] . ' = ' . $row['count'];
	}
	foreach($datasets as $id => $concept)
	{
		
		//echo "<BR><BR>ID=$id matches pinata";
		fetchMatches( $id, $values );
		break;
	}
}



?>


