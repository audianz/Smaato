<?php
$poiIndex=null;
function smaato_request($request)
{
	//$con= mysql_connect("localhost","root","rgbxyz");
	//mysql_select_db("audianz", $con)or die(mysql_error());
	//$con = mysqli_connect("localhost","root","rgbxyz","audianz");
    global $log;
	global $con;	
	if (!$con)
	{
     		$log->logError("Failed to connect to MYSQL");
     		echo "Failed to connect to MySQL: " . mysqli_connect_error();
                exit;
	}
	else
	{

		$request_array = unserialize($request);
		$bid_floor=empty($request_array['imp'][0]['bidfloor'])?0:$request_array['imp'][0]['bidfloor'];
		$width=empty($request_array['imp'][0]['banner']['w'])?0:$request_array['imp'][0]['banner']['w'];
		$height=empty($request_array['imp'][0]['banner']['h'])?0:$request_array['imp'][0]['banner']['h'];

		$response_array=array();
		$ad_response=0;
		$second_highestbid=0;
		$first_record=0;
		$get_bannerRow=0;



		if(!empty($request_array['imp']))
		{

			$get_bannerInfo= mysqli_query($con,"SELECT b.bannerid,b.campaignid,b.master_banner,b.filename,b.url,
                                                            b.bannertext,c.revenue,c.weight,c.clientid,bal.accbalance,d.dailybudget
                                        FROM ox_banners as b, ox_campaigns as c, ox_clients as a, oxm_accbalance as bal, oxm_budget as d
					WHERE
					b.width=".$width." AND
					b.height=".$height." AND
					b.master_banner!=-2 AND  b.master_banner!=-1 AND  b.master_banner!=-3
					AND b.campaignid IN (SELECT Campaign_id FROM Campaign_Target_Impressions_Mix WHERE Mix_id = ( SELECT Mix_id FROM `Campaign_Optimize_Mix` WHERE LOWER(Network) = 'smaato' ) And Impressions_served < Target_impressions)
					AND c.revenue_type=1 AND
					c.status=0
					AND (UNIX_TIMESTAMP(c.expire_time) > UNIX_TIMESTAMP() OR c.expire_time IS NULL )
					AND b.status=0
					AND bal.clientid=c.clientid
					AND bal.accbalance>0 AND
					d.campaignid=b.campaignid
					GROUP BY b.master_banner");

	 	/**
	 	 *  $get_bannerInfo query checked the width and height exact and less than exact size
	 	 *
	 	 * group by master banner
	 	 *
	 	* */
	 	

			if(mysqli_num_rows($get_bannerInfo)>0)
			{
					
				$date=date("Y-m-d");
				$date=date("Y-m-d");
				$query=mysqli_query($con,"SELECT cpm_rate FROM `Campaign_Optimize_Mix` WHERE LOWER(Network) = 'smaato' ");
				$cpm=mysqli_fetch_array($query);
				$banner_rate=$cpm['cpm_rate'];


				while($get_bannerRow=mysqli_fetch_array($get_bannerInfo))
				{
					//$banner_rate=$get_bannerRow['revenue'];
					$bannerid=$get_bannerRow['bannerid'];
					if($get_bannerRow['revenue']<0.1)
					{
						$banner_rate =sprintf('%f',$banner_rate);
					}
					$campaign_query=mysqli_query($con,"SELECT SUM(amount) as daily_amount FROM oxm_report WHERE campaignid='".$get_bannerRow['campaignid']."' AND DATE(date)='".$date."'");

					if(mysqli_num_rows($campaign_query)>0)
					{
						$campaign_amt=mysqli_fetch_array($campaign_query);
						if(empty($campaign_amt['daily_amount']) || $campaign_amt['daily_amount']=='NULL')
						{
							$campaign_amt['daily_amount']=0;
						}
					}
					else
					{
						$campaign_amt['daily_amount']=0;
					}
                                        $banner_Infoarray=null;
					if(($banner_rate>=$bid_floor) && ($get_bannerRow['accbalance']>=$banner_rate) && ($campaign_amt['daily_amount']<$get_bannerRow['dailybudget'])){
							
							
						$banner_Infoarray[$bannerid]= array(
								"bannerid"=>$get_bannerRow['bannerid'],
								"master_banner"=>$get_bannerRow['master_banner'],
								"campaignid"=>$get_bannerRow['campaignid'],
								"banner_rate"=>$banner_rate,
								"clientid"=>$get_bannerRow['clientid'],
								"filename"=>$get_bannerRow['filename'],
								"weight" => $get_bannerRow['weight'],
								"url" =>  $get_bannerRow['url'],
								"bannertext"=>$get_bannerRow['bannertext'],
								"revenue"=>$banner_rate,
								"second_highestbid"=>''
						);
					}
				}
					
				// Targeting feature start
			//	$log->logDebug("bannerInfo before targeting time");

				$targe_ban=array();
				$targe_ban=$banner_Infoarray;
				if(!empty($request_array['device']['make']))
					$make=strtolower($request_array['device']['make']);
				else
					$make='';
				$os=empty($request_array['device']['os'])?'':$request_array['device']['os'];

				$model=empty($request_array['device']['model'])?'':$request_array['device']['model'];

				$lat=empty($request_array['device']['geo']['lat'])?0:$request_array['device']['geo']['lat'];

				$lon=empty($request_array['device']['geo']['lon'])?0:$request_array['device']['geo']['lon'];

				$ip=empty($request_array['device']['ip'])?'':$request_array['device']['ip'];
			//	$log->logDebug("\n make is ".$make."\n os is ".$os."\n model is ".$model."\n lat is ".$lat."\n lon is ".$lon."\n ip is".$ip."\n ");
			 		
			     if(!empty($banner_Infoarray))
			     {
				foreach($targe_ban as $binfo)
				{

					$banid=$binfo['bannerid'];
					$advid=$binfo['clientid'];
                                        $campid = $binfo['campaignid']; 
					
					/* Manufacturer Targeting 	*/
					$camtagreting=mysqli_query($con,"SELECT * FROM djx_targeting_limitations WHERE campaignid=".$binfo['campaignid']);
					$cam_targetvalue=mysqli_fetch_array($camtagreting);
					if(!empty($make))
					{
					//	$camtagreting=mysqli_query($con,"SELECT manufacturer FROM djx_targeting_limitations WHERE campaignid=".$binfo['campaignid']);							
					//	$cam_targetvalue=mysqli_fetch_array($camtagreting);
						$banner_make= strtolower($cam_targetvalue['manufacturer']);
						$banner_make=explode(',',$banner_make);
						if(!empty($banner_make[0]) && $banner_make[0]!='NULL' && $banner_make[0]!='null' && $banner_make[0]!='')
						{

							if(!in_array($make,$banner_make))
							{
									
								unset($banner_Infoarray[$banid]);
							}

						}

					}

					/*  location targeting          */

					if($lat!=0 && $lon!=0)
					{
							
				//		$camtagreting=mysqli_query($con,"SELECT enable_loc,landing_page,intermediate FROM djx_targeting_limitations WHERE campaignid=".$binfo['campaignid']);
				//		$cam_targetvalue=mysqli_fetch_array($camtagreting);
						$banner_enable_loc = strtolower($cam_targetvalue['enable_loc']);
							
						if(!empty($banner_enable_loc) && $banner_enable_loc!='NULL' && $banner_enable_loc!='null' && $banner_enable_loc!='')
						{
							if($banner_enable_loc=='spec')
							{
								$res=_adLocationMatches($banid,$advid,$lat,$lon,$campid);
								
								if($res==true)
								{
									//putlog("\n res is true");
								}
								else
								{
									unset($banner_Infoarray[$banid]);
								}	
							}
							
						}
							
					}
					else
					{
				//		$camtagreting=mysqli_query($con,"SELECT enable_loc FROM djx_targeting_limitations WHERE campaignid=".$binfo['campaignid']);
							
				//		$cam_targetvalue=mysqli_fetch_array($camtagreting);
							
						$banner_enable_loc = strtolower($cam_targetvalue['enable_loc']);
						if(!empty($banner_enable_loc) && $banner_enable_loc!='NULL' && $banner_enable_loc!='null' && $banner_enable_loc!='')
						{
							if($banner_enable_loc=='spec')
							{
								unset($banner_Infoarray[$banid]);
							}
						}
				 }

				 /* Device OS  Targeting 	*/
				 if(!empty($os)){
				// 	$camtagreting=mysqli_query($con,"SELECT devices FROM djx_targeting_limitations WHERE campaignid=".$binfo['campaignid']);
				 		
				// 	$cam_targetvalue=mysqli_fetch_array($camtagreting);
				 		
				 	$banner_os= strtolower($cam_targetvalue['devices']);
				 		
				 	$banner_os=explode(',',$banner_os);
				 		

				 	if(!empty($banner_os[0]) && $banner_os[0]!='NULL' && $banner_os[0]!='null' && $banner_os[0]!=''){

				 		if(!in_array(strtolower($os),$banner_os)){
				 			 
				 			unset($banner_Infoarray[$banid]);
				 		}

				 	}

				 }

				 /* Device model Targeting 	*/
				 if(!empty($model)){
			//	 	$camtagreting=mysqli_query($con,"SELECT model FROM djx_targeting_limitations WHERE campaignid=".$binfo['campaignid']);
				 		
			//	 	$cam_targetvalue=mysqli_fetch_array($camtagreting);
				 		
				 	$banner_model= strtolower($cam_targetvalue['model']);
				 		
				 	$banner_model=explode(',',$banner_model);
				 		

				 	if(!empty($banner_model[0]) && $banner_model[0]!='NULL' && $banner_model[0]!='null' && $banner_model[0]!=''){

				 		if(!in_array($model,$banner_model)){
				 			 
				 			unset($banner_Infoarray[$banid]);				 			
				 		}

				 	}

				 }

				 /* Maudit Targeting based on IP address */
				 if(!empty($ip)){
			//	 	$camtagreting=mysqli_query($con,"SELECT carriers FROM djx_targeting_limitations WHERE campaignid=".$binfo['campaignid']);
				 		
			//	 	$cam_targetvalue=mysqli_fetch_array($camtagreting);
				 		
				 	$banner_carrier=explode(',',$cam_targetvalue['carriers']);
				 	if(!empty($cam_targetvalue['carriers']) && $cam_targetvalue['carriers']!='NULL' && $cam_targetvalue['carriers']!='null' && $cam_targetvalue['carriers']!=''){
				 		$sql = "SELECT * FROM ox_carrier_detail WHERE  id IN (".$cam_targetvalue['carriers'].") AND INET_ATON(start_ip) <= INET_ATON('".$ip."') AND INET_ATON(end_ip) >= INET_ATON('".$ip."')";
				 		$ip_carrier_Info=mysqli_query($con,"SELECT * FROM ox_carrier_detail WHERE  id IN (".$cam_targetvalue['carriers'].") AND INET_ATON(start_ip) <= INET_ATON('".$ip."') AND INET_ATON(end_ip) >= INET_ATON('".$ip."')");
				 		if(mysqli_num_rows($ip_carrier_Info)>0)
				 		{
				 			$ip_carrier=mysqli_fetch_array($ip_carrier_Info);
				 		}

				 		if(empty($ip_carrier['id'])){
				 			 
				 			unset($banner_Infoarray[$banid]);
				 		}


				 	}
				 		
				 }

				 /* Geo Targeting based on Latitude and logitude from da_country table	*/
				 if(!empty($lat) && !empty($lon)){
				 		
		//		 	$camtagreting=mysqli_query($con,"SELECT locations FROM djx_targeting_limitations WHERE campaignid=".$binfo['campaignid']);
				 		
		//		 	$cam_targetvalue=mysqli_fetch_array($camtagreting);
				 		
				 	$banner_geo=explode(',',$cam_targetvalue['locations']);
				 		
				 	if(!empty($cam_targetvalue['locations']) && $cam_targetvalue['locations']!='NULL' && $cam_targetvalue['locations']!='null' && $cam_targetvalue['locations']!=''){

				 		$country_count=0;

				 		foreach($banner_geo as $country){
				 			 

				 			$ip_carrier=mysqli_query($con,"SELECT * FROM da_country WHERE country_code = '".$country."' AND latitude = '".$lat."' AND longitude = '".$lon."'");
				 			 
				 			if(mysqli_num_rows($ip_carrier)>0){
				 				$country_count=$country_count+1;
				 			}
				 			 
				 		}

				 		if(empty($country_count) || $country_count=='0'){
				 			unset($banner_Infoarray[$banid]);
				 		}


				 	}
				 		
				 }

				}
                            }
			 	global $poiIndex;
				// Targeting feature end
			//	$log->logDebug("bannerinfo arr after targeting time is ");
				
				$new_ads = array();
					
				if(!empty($banner_Infoarray)){


					$desc_order_revenue= msort($banner_Infoarray, array('banner_rate','weight'));

					krsort($desc_order_revenue); //sort the array in descending order based on key.


					$i=0;
					foreach($desc_order_revenue as $individual_value){

						$sorted_array[$i]=$individual_value;
						$i++;
					}



					//check if the fisrt array bid and second array bid equal

					$l=0;
					foreach($sorted_array as $ad){
						$select_ad[$l]=$ad;
						$l++;
					}

					$t=1;
					foreach($select_ad as $ad){


						if($select_ad[0]['banner_rate']==$ad['banner_rate'])
						{
							$same_bidad[$t]=$ad;

						}
						$t++;
					}

					if(count($same_bidad)>1){

						foreach($same_bidad as $ad){

							$revenue_query=mysqli_query($con,"SELECT sum(o.amount) as amount, o.campaignid as cid, c.* FROM oxm_report as o, oxm_budget as c WHERE c.campaignid='".$ad['campaignid']."' AND c.campaignid=o.campaignid");
							$revenue_row=mysqli_fetch_array($revenue_query);
							if(!empty($revenue_row['amount'])>0){

								/*$date=date("Y-m-d 00:00:00");

								$campaign_query=mysql_query("SELECT SUM(amount) as daily_amount FROM oxm_report WHERE campaignid='".$ad['campaignid']."' AND date='".$date."'")or die(mysql_error());
								$campaign_amt=mysql_fetch_array($campaign_query);

								if($revenue_row['dailybudget']>$campaign_amt['daily_amount']){*/

								$ads[$ad['bannerid']]=$ad;
									
								$ads[$ad['bannerid']]['revenue']=$revenue_row['amount'];
									
								//}

							}else{
								$ads[$ad['bannerid']]=$ad;
								$ads[$ad['bannerid']]['revenue']=0;
							}
						}
						$new_ads=msort($ads, array('revenue','weight'));
					}

					if(empty($new_ads)){

						$sortlisted_array=$sorted_array;
					}else{

						$sortlisted_array=$new_ads; 
						// win ad from more than one highest banner bid rate ad , based on revenue (lowest)
					}
					$first_record = reset($sortlisted_array);

					$response_bid=$first_record['banner_rate'];

					
					$admin_share='';

					if($response_bid>=$bid_floor){
						global $poiIndex;
						$poiVal = -1;
						
						for($i = 0; $i < count($poiIndex); $i++)
						{
							if($poiIndex[$i]['adId'] == $first_record['bannerid'])
							{
								if(!empty($poiIndex[$i]['poiId']) && ($poiIndex[$i]['poiId'] != -1))
								{
									$poiVal = $poiIndex[$i]['poiId'];
									break;
								}
							 }
						}	
						$clickurl="http://54.235.252.159/ads/www/delivery/ck.php?oaparams=2__bannerid=".$first_record['master_banner']."__zoneid=0__cb=6576b158d9__oadest=".$first_record['url']."&amp;lat=".$lat."&amp;lon=".$lon."&amp;poi=".$poiVal;
					$beaconurl=null;//"http://54.235.173.134/ads/www/delivery/lg.php?bannerid=".$first_record['master_banner']."&amp;campaignid=".$first_record['campaignid']."&amp;zoneid=0&amp;cb=3dd71a6049";
							
						$imageurl= "http://54.235.252.159/ads/www/images/".$first_record['filename'];
							

							
						$response_array = array(
								"id"=>$request_array['id'],
								"bid"=>"368986290101875502942021904441292",
								"impid" => $request_array['imp'][0]['id'],
								"price" => $response_bid,
								"adid" => $first_record['master_banner'],
								"nurl" => 'http://54.235.252.159/ads/www/delivery/lg.php?bannerid='.$first_record['master_banner'].'&campaignid='.$first_record['campaignid'].'&poi='.$poiVal.'&banner_rate='.$first_record['banner_rate'].'&zoneid=0&cb=3dd71a6049&clientid='.$first_record['clientid'].'&auctionId=${AUCTION_ID}&bidid=${AUCTION_BID_ID}&price=${AUCTION_PRICE}&impid=${AUCTION_IMP_ID}&seatid=${AUCTION_SEAT_ID}&adid=${AUCTION_AD_ID}&cur=${AUCTION_CURRENCY}',
								"click_url"=>$clickurl,
								"image_url"=>$imageurl,
								"additional_text"=>$first_record['bannertext'],
								"beacon_url" => $beaconurl,
								"adomain"=>"54.235.252.159",
								"iurl"=>"",
								"cid"=>$first_record['campaignid'],
								"crid"=>$first_record['bannerid'],
								"attr"=>"",
								"ext"=>"",
								"tooltip"=>"",
								"seat"=>"8a809449012f2f0744180791edfc0003",
								"group"=>0,
								"bidid"=>$request_array['id'],
								"cur"=>"USD",
								"customdata"=>"",
								"ext"=>"",
								"width" =>$width,
								"height"=>$request_array['imp'][0]['banner']['h']
						);
					}else{
						$ad_response=1;
					}
				}else{
					$ad_response=1; // null response due to revenue amount less than floor price
				}
			}
			else{

				$ad_response=1;  // If there is no matching ads, or in inactive status returns null array
				
					
			}
			$cur_date = date('Y-m-d H:i:s');

			$requset_id =0;//$request_row['id'];

			$admin_share =empty($admin_share)?0:$admin_share;
			$response_bid=empty($response_bid)?0:$response_bid;
                       	
			mysqli_query($con,"INSERT INTO  `aff_smaato_response` (
					`datetime`,
					`requset_id`,
					`id` ,
					`imp_id` ,
					`imp_width` ,
					`imp_height` ,
					`seat` ,
					`floor_price` ,
					`advertiser_bid_price` ,
					`smaato_bid_price` ,
					`admin_rev` ,
					`adid` ,
					`bannerid` ,
					`campaign_id` ,
					`type`
					)
					VALUES (
					'".$cur_date."',
					'".$requset_id."',
					'".$request_array['id']."',
					'".$request_array['imp'][0]['id']."',
					'".$width."',
					'".$height."',
					'8a809449012f2f0744180791edfc0003',
					'".$bid_floor."',
					'".$first_record['banner_rate']."',
					'".$response_bid."',
					'".$admin_share."',
					'".$first_record['master_banner']."',
					'".$get_bannerRow['bannerid']."',
					'".$first_record['campaignid']."',
					'".$request_array['device']['devicetype']."')");

			if($ad_response==1){
				$response_array=array("id"=>$request_array['id'],
						"bid"=>"368986290101875502942021904441292",
						"impid" => $request_array['imp'][0]['id'],
						"price" => 0,
						"adid" => "",
						"nurl" => 'http://54.235.252.159/smaato/ads/winnotice.php?auctionId=${AUCTION_ID}&bidid=${AUCTION_BID_ID}&price=${AUCTION_PRICE}&impid=${AUCTION_IMP_ID}&seatid=${AUCTION_SEAT_ID}&adid=${AUCTION_AD_ID}&cur=${AUCTION_CURRENCY}',
						"click_url"=>"",
						"image_url"=>"",
						"additional_text"=>"",
						"beacon_url" =>"",
						"adomain"=>"54.235.252.159",
						"iurl"=>"",
						"cid"=>"",
						"crid"=>"",
						"attr"=>"",
						"ext"=>"",
						"tooltip"=>"",
						"seat"=>"8a809449012f2f0744180791edfc0003",
						"group"=>0,
						"bidid"=>$request_array['id'],
						"cur"=>"USD",
						"customdata"=>"",
						"ext"=>"",
						"width" =>$width,
						"height"=>$height

				);
			}

			return $response_array;

		}


	}
}


function msort($array, $key, $sort_flags = SORT_REGULAR) {
	if (is_array($array) && count($array) > 0) {
		if (!empty($key)) {
			$mapping = array();
			foreach ($array as $k => $v) {
				$sort_key = '';
				if (!is_array($key)) {
					$sort_key = $v[$key];
				} else {
					// @TODO This should be fixed, now it will be sorted as string
					foreach ($key as $key_key) {
						$sort_key .= $v[$key_key];
					}
					$sort_flags = SORT_STRING;
				}
				$mapping[$k] = $sort_key;
			}
			asort($mapping, $sort_flags);
			$sorted = array();
			foreach ($mapping as $k => $v) {
				$sorted[] = $array[$k];
			}

			return $sorted;
		}
	}

	return $array;
}
function  _adLocationMatches($adId,$clientId,$lat,$lon,$campId)
{

	global $con;
	global $log;
	$_distanceQuery = mysqli_query($con,"SELECT * FROM `storefrontdata` WHERE id IN (SELECT `store_id` FROM `campaign_location_table` WHERE campaign_id = '".$campId."')");
	$result = false;
	
	if(mysqli_num_rows($_distanceQuery) > 0)
	{
		$listofMatchedPoi;

		$radiusVal=10;
		
		$_radiusQuery = mysqli_query($con,"select * from storefront_radius where (advid='".$clientId."')");
	
		if(mysqli_num_rows($_radiusQuery) > 0)
		{
			while ($radiusRow = mysqli_fetch_array($_radiusQuery))
			{
				$radiusVal = $radiusRow['radius'];
			}
				
		}
		while ($row = mysqli_fetch_array($_distanceQuery))
		{
			$dis = distance($row['lat'], $row['lon'], $lat, $lon, 'k') ;
			if( ($dis != -1) && ($dis <= $radiusVal))
			{
				$temp['poiId'] = $row['id'];
				$temp['distance'] = $dis;
				$listofMatchedPoi[] = $temp;
			}
		}

		global $poiIndex;
		$tempPoiIndx['adId'] = $adId;
		if(!empty($listofMatchedPoi))
		{
			$tempPoiIndx['poiId'] = getShortestDistancePoi($listofMatchedPoi);
			$result =true;
		}
		else
		{
			//$log->logDebug("\n no matching poi ");
		}
		$poiIndex[] = $tempPoiIndx;
	}
	else
	{
		//$log->logDebug("\n  _adLocationMatches() poi data not available for client id ".$clientId);
	}
	return $result;
}

function distance($lat1, $lon1, $lat2, $lon2, $unit)
{
	if(is_null($lat1) || is_null($lon1) || is_null($lat2) || is_null($lon2))
	{

		return -1;
	}
	else if(empty($lat1) || empty($lon1) || empty($lat2) || empty($lon2))
	{

		return -1;
	}
	else if(($lat1 == 0) || ($lon1 == 0) || ($lat2 == 0) || ($lon2 == 0))
	{

		return -1;
	}
	$theta = $lon1 - $lon2;
	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	$dist = acos($dist);
	$dist = rad2deg($dist);
	$miles = $dist * 60 * 1.1515;
	$unit = strtoupper($unit);

	if ($unit == "K") {
		return ($miles * 1.609344);
	} else if ($unit == "N") {
		return ($miles * 0.8684);
	} else {
		return $miles;
	}
}

function getShortestDistancePoi($list)
{

	$tempPoi;
	$tempDistance;
	$tempPoi = $list[0]['poiId'];
	$tempDistance = $list[0]['distance'];
	for($i = 1; $i < count($list); $i++)
	{
		if($list[$i]['distance'] < $tempDistance)
		{
			$tempDistance = $list[$i]['distance'];
			$tempPoi = $list[$i]['poiId'];
		}
	}
	return $tempPoi;
}
//"nurl" => "http://23.21.111.209/smaato/ads/winnotice.php?impid=".$request_array['imp'][0]['id'],
?>
