<?php
	$con= mysql_connect("localhost","root","rgbxyz");
	mysql_select_db("audianz", $con)or die(mysql_error());

	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}else{
		$auctionId 	= $_REQUEST['auctionId'];
		$bidid 		= $_REQUEST['bidid'];
		$price 		= $_REQUEST['price'];
		$impid 		= $_REQUEST['impid'];
		$seatid 	= $_REQUEST['seatid'];
		$adid 	= $_REQUEST['adid'];
		$cur 	= $_REQUEST['cur'];
		/*$jsonStr 	= "AuctionID-".$auctionId."?Bid ID-".$bidid."?Price-".$price."&&&";
		$file 		= '/var/www/smaato/ads/notice.txt';
		$current 	= file_get_contents($file);
		file_put_contents($file, $jsonStr);*/
		$cur_date = date('Y-m-d H:i:s');
		mysql_query("INSERT INTO  `aff_smaato_win_notice` (
											`datetime`,
											`auctionID`,
											`bidid` ,
											`price` ,
											`currency` ,
											`impid` ,
											`seatid` ,
											`adid`
										)
										VALUES (
											 '".$cur_date."',
											 '".$auctionId."',
											 '".$bidid."',
											 '".$price."',
											 '".$cur."',
											 '".$impid."',
											 '".$seatid."',
											 '".$adid."'
											 
											 )") or die(mysql_error());
	}
?>
