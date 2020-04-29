<html>
	<head>

		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Reprocessing Profit</title>
		<link type="text/css" href="css/ui-lightness/jquery-ui-1.8.22.custom.css" rel="stylesheet" />
		<script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
		<script type="text/javascript" src="js/jquery-ui-1.8.22.custom.min.js"></script>
		<script type="text/javascript">
			$(function(){
				//hover states on the static widgets
				$('#dialog_link, ul#icons li').hover(
                    function() { $(this).addClass('ui-state-hover'); },
					function() { $(this).removeClass('ui-state-hover'); }
				);
			});
		</script>
        <script>
	$(function() {
		function log( message ) {
			$( "<div/>" ).text( message ).prependTo( "#log" );
			$( "#log" ).scrollTop( 0 );
		}

		$( "#item" ).autocomplete({
			source: "search.php",
			minLength: 2,
			select: function( event, ui ) {
                $( "#itemID" ).attr('value', ui.item.typeID);
				log( ui.item ?
					"Selected: " + ui.item.typeName + " aka " + ui.item.typeID :
					"Nothing selected, input was " + this.typeName );
			}
		});
	});
	</script>
		<style type="text/css">
			/*demo page css*/
			body{ font: 62.5% "Trebuchet MS", sans-serif; margin: 50px;}
			.demoHeaders { margin-top: 2em; }
			#dialog_link {padding: .4em 1em .4em 20px;text-decoration: none;position: relative;}
			#dialog_link span.ui-icon {margin: 0 5px 0 0;position: absolute;left: .2em;top: 50%;margin-top: -8px;}
			ul#icons {margin: 0; padding: 0;}
			ul#icons li {margin: 2px; position: relative; padding: 4px 0; cursor: pointer; float: left;  list-style: none;}
			ul#icons span.ui-icon {float: left; margin: 0 4px;}
		</style>
    </head>
    <body>

<?php
include 'DB.php';
$DB = new DB(parse_ini_file('db-eve.ini'));

function get_data($url, $post) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_USERAGENT,'ladar thingy');
	curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

if(isset($_POST['itemID'])) {
    
    $results = $DB->qa('
SELECT a.typeID, a.parentTypeID, a.metaGroupID, b.typeName, IFNULL(c.valueInt, c.valueFloat) AS metaLevel, GROUP_CONCAT( d.materialTypeID ) AS materialID, GROUP_CONCAT( d.quantity ) AS quantity
FROM  `invMetaTypes` a
INNER JOIN  `invTypes` b ON ( b.typeID = a.typeID ) 
INNER JOIN  `dgmTypeAttributes` c ON ( c.attributeID = 633 AND c.typeID = a.typeID ) 
INNER JOIN  `invTypeMaterials` d ON ( d.typeID = b.typeID ) 
WHERE a.`parentTypeID` = ? AND a.metaGroupID =1
GROUP BY a.typeID
ORDER BY  metaLevel ASC 
LIMIT 0 , 30', array($_POST['itemID']));

    $minerals = array(34, 35, 36, 37, 38, 39, 40, 11399);
    $types = array();
    foreach ($results AS $item) {
        $types[] = $item[0]; }

    $types = implode(array_merge($minerals, $types), "&typeid=");
    $fields = "typeid=$types&usesystem=30000142";
    $data = get_data('https://api.evemarketer.com/ec/marketstat', $fields);
    $xml = new SimpleXMLElement($data);   
    echo "<pre>";

    // print_r($results);
    // print_r($xml);
    // display in table, along with 
    echo "</pre>"; 
    
    $amount=1; // the amount to show
    $percentage = 0.02;
    $yield = 0.929;
    
    echo "
    <p>The data below represents cost/profit for ".$amount."x the product listed in order to get a better representation of the data. Buy data is marked up by ".($percentage*100)."% of the max buy order, and sell is maked down by ".($percentage*100)."% of the lowest sell order.</p>
    <table border='1' cellpadding='2'>
	<thead>
	<tr>
	<th>Item</th>
	<th>Meta level</th>
	<th>Expense</th>
	<th>Profit</th>
	<th>Net</th>
	</tr>
	</thread>
	";
	
    foreach ($results AS $item) {
		$buy = $xml->xpath('/exec_api/marketstat/type[@id="'.$item[0].'"]/buy/max'); $buy = ((int)$buy[0] + ((int)$buy[0] * $percentage));
        
        $combined = array_combine(explode(',',$item[5]), explode(',',$item[6]));
        
        $totalSell = 0;
		
        foreach ($combined AS $mineral => $quantity) {
			
            $sell = $xml->xpath('/exec_api/marketstat/type[@id="'.$mineral.'"]/sell/min'); 
			$sell = ((int)$sell[0] - ((int)$sell[0] * $percentage));
            $totalSell += ($sell*($quantity*$yield));
        }
        
        $expense = $buy*$amount;
        $profit = $totalSell*$amount;

        echo "
        <tr>
            <td>".$item[3]."</td>
            <td>".$item[4]."</td>
            <td>".number_format($expense)."</td>
            <td>".number_format($profit)."</td>
            <td>".number_format($profit - $expense)."</td>
            
        </tr>";
    }
    echo "</table>";
}

echo "<hr />";

?>
<form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post'>
<input type='text' name='item' id='item' />
<input type='hidden' value='' name='itemID' id='itemID' />
<button type='submit'>Go!</button>
<div id='log'> </div>
</form>

</body>
</html>
