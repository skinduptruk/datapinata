
<?php
include_once('./simple_html_dom.php');


//open database
$connect = mysqli_connect ("localhost", "dp", "pinata*3");
mysqli_select_db($connect, "datapinata"); //select database


echo "Creating DB fields/concepts list/counts<BR>";
flush();

$htmlpage = 'http://stat.abs.gov.au/itt/query.jsp?method=GetDatasetList';
$curl = curl_init(); 
curl_setopt($curl, CURLOPT_URL, $htmlpage);  
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);  
$str = curl_exec($curl);  
curl_close($curl);  
 
$html= str_get_html($str); 
$json = $html;
//echo $html . "<BR><BR>";

$decoded = json_decode($json,true);
//echo $decoded . "<BR><BR>";
//var_dump($decoded);
$count = 0;
$tables = 1000;
$tableIds = null;
foreach( $decoded as $dataset => $data )
{ echo "<BR><BR>";

	//echo "<BR>DD $dataset => $data" ;
	foreach( $data as $dataset2 => $data2 )
	{
		//echo "<BR>DD2  $dataset2 => $data2" ;
		foreach( $data2 as $dataset3 => $data3 )
		{
			echo "<BR>DD3  $dataset3 => $data3" ;
			flush();
			if( $dataset3 == 'id')
			{
				if(!isset($tableIds[$count]))
				{
					$tableIds[$count] = $data3;
					fetchConcepts( $count );
					updateIDs();
					$count++;
				}
			}
			if($count>$tables)
				break;
			//foreach( $data3 as $dataset4 => $data4 )
			//	echo "<BR>    $dataset4 => $data4" ;
		}
		if($count>$tables)
			break;

	}
	if($count>$tables)
		break;

}
updateDBcounts();
commitConcepts();
commitCounts();
commitIDs();


function fetchconcepts($num)
{
	global $tableIds;
	echo "<BR>id=$num " .$tableIds[$num];
	$htmlpage = 'http://stat.abs.gov.au/itt/query.jsp?method=GetDatasetConcepts&datasetid='.$tableIds[$num];
	$curl = curl_init(); 
	curl_setopt($curl, CURLOPT_URL, $htmlpage);  
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);  
	$str = curl_exec($curl);  
	curl_close($curl);  
	$json= str_get_html($str); 
//	echo $json;
	$decoded = json_decode($json,true);
	//var_dump($decoded);
	foreach( $decoded as $dataset2 => $data2 )
	{
	//	echo "<BR>DD2  $dataset2 => $data2" ;
		foreach( $data2 as $dataset3 => $concept )
		{
			//echo "<BR>DD3  $dataset3 => $concept" ;
			if($concept == "AGE" || $concept == "SEX" || $concept=="STATE")
			{
				fetchCodeList( $num, $concept );
				global $fields;
				if( isset( $fields[$concept] ) )
					$fields[$concept]++;	
				else
					$fields[$concept]=1;
			}
	
//exit();

		//	updateDB( $id, $concept );
		}
//	exit();
	}
}

function fetchCodeList($num, $concept)
{
	global $tableIds;

	echo "<BR>$concept id=$num " .$tableIds[$num];
	$htmlpage = 'http://stat.abs.gov.au/itt/query.jsp?method=GetCodeListValue&datasetid='.$tableIds[$num] . "&concept=$concept" . "&format=json";
	$curl = curl_init(); 
	curl_setopt($curl, CURLOPT_URL, $htmlpage);  
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);  
	$str = curl_exec($curl);  
	curl_close($curl);  
	$json= str_get_html($str); 
//	echo $json;
	$decoded = json_decode($json,true);
//	var_dump($decoded);
	
	foreach( $decoded as $dataset2 => $data2 )
	{
		//echo "<BR>DD2  $dataset2 => $data2" ;
		foreach( $data2 as $dataset3 => $data3 )
		{
			//echo "<BR>DD3  $dataset3 => $data3" ;
			$codes = null;
			foreach( $data3 as $dataset4 => $data4 )
			{
				$codes[$dataset4] = $data4;
			//	echo "<BR>DD4  $dataset4 => $data4" ;
			}
			if( strlen($codes['code']) <15 )
				updateDB ($num, $concept, $codes );
			//exit();
		}
	}
}

$fields = null;
$valuesIDsText = $valuesCountsText= $valuesConceptsText="";

function updateDB( $num, $concept, $codes )
{
	global $connect,$valuesConceptsText;
	if( $valuesConceptsText !="" ) $valuesConceptsText .= ",";
	$valuesConceptsText .= " ( '$num', '$concept', '" . $codes['code']. "', \"" . $codes['description'] . "\")  ";
//	echo "<BR> $valuesConceptsText";
	if( strlen($valuesConceptsText) > 70) //500 )
	{
		commitConcepts();
	}
}
function updateDBcounts(  )
{
	global $fields, $connect;
	global $valuesCountsText;
	foreach ($fields as $field => $count ) 
	{
		if( $valuesCountsText !="" ) $valuesCountsText .= ",";
		$valuesCountsText .= " ( '$field', '$count') ";
	}
	if( strlen($valuesCountsText) > 500 )
	{
		commitCounts();
	}
}

function updateIDs(  )
{
	global $tableIds, $connect;
	global $valuesIDsText;
	foreach ($tableIds as $num => $id ) 
	{
		if( $valuesIDsText !="" ) $valuesIDsText .= ",";
		$valuesIDsText .= " ( '$num', '$id') ";
	}
//	if( strlen($valuesIDsText) > 500 )
	{
		commitIDs();
	}
}

function commitIDs()
{
	global $connect, $valuesIDsText;
	$queryreg = mysqli_query($connect, "	     		
		INSERT INTO ids ( `num`, `id` ) 
	      	VALUES  $valuesIDsText  ");
	echo $valuesIDsText;
	$valuesIDsText ="";
}

function commitConcepts()
{
	global $connect, $valuesConceptsText;
	
	$sql = "	     		
		INSERT INTO concepts ( `id`, `concept`, `code`, `description` ) 
	      	VALUES  $valuesConceptsText  ";
	$queryreg = mysqli_query($connect, $sql) 
		or trigger_error("Query Failed! SQL: $sql - Error: ".mysqli_error(), E_USER_ERROR);;
//	echo $valuesConceptsText;
//	exit();
	$valuesConceptsText ="";
}

function commitCounts()
{
	global $connect, $valuesCountsText;
	$queryreg = mysqli_query($connect, "	     		
		INSERT INTO counts  ( `field`, `count` )
	      	VALUES  $valuesCountsText  ");
	$valuesCountsText ="";
}




?>

