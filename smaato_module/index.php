<?php
//header("HTTP/1.0 204");
//exit;
$starttime=round(microtime(true) * 1000);
require_once 'ads/oxm_rtbads.php';
include 'ELogger.php';
$log = new ELogger( "log" , ELogger::DEBUG );
$con = mysqli_connect("localhost","root","rgbxyz","audianz");
// Check connection
if (mysqli_connect_errno())
{
     $log->logError("Failed to connect to MYSQL");
     echo "Failed to connect to MySQL: " . mysqli_connect_error();
     exit;
}
else
{	
	
	if(!isset( $HTTP_RAW_POST_DATA)) 
	{
		$HTTP_RAW_POST_DATA =file_get_contents( 'php://input' );
	}
	$jsonStr = $HTTP_RAW_POST_DATA;
 		
	$req_arr = json_decode($jsonStr, true);
	if(!empty($req_arr))
	{
			$geo_lat = empty($req_arr['device']['geo']['lat'])?'0.0':$req_arr['device']['geo']['lat'];
			$geo_lon= empty($req_arr['device']['geo']['lon'])?'0.0':$req_arr['device']['geo']['lon'];
			$dgc=empty($req_arr['device']['geo']['country'])?'':$req_arr['device']['geo']['country'];

			if($dgc!='IND' or $geo_lat==0.0 or $geo_lon==0.0 )
			{
			//	$log->logDebug("Data is outside india or non location ");
				header("HTTP/1.0 204");
				exit;
			}
	
		// Auction Type
			if($req_arr['at']!=0 || $req_arr['at']!='')
			{
				$at 	= $req_arr['at'];
			} else { $at = 0; }
			
			//Bid request Id
			if($req_arr['id']!='')
			{
				$bid_req_id	= $req_arr['id'];
			} else { $bid_req_id = ''; }
			
			// Device Array
			if(!empty($req_arr['device']))
			{
				$device_arr = $req_arr['device'];
			} else { $device_arr = array(); }
			
			// IMP Array
			if(!empty($req_arr['imp']))
			{
				$imp_arr = $req_arr['imp'];
			} else { $imp_arr = array(); }
			
			// Site Array
			if(!empty($req_arr['site']))
			{
				$site_arr = $req_arr['site'];
			} else { $site_arr = array(); }
			
			// App Array
			if(!empty($req_arr['app']))
			{
				$app_arr = $req_arr['app'];
			} else { $app_arr = array(); }
			
			// User Array
			if(!empty($req_arr['user']))
			{
				$user_arr = $req_arr['user'];
			} else { $user_arr = array(); }
			
			// Ext Array
			if(!empty($req_arr['ext']))
			{
				$ext_arr = $req_arr['user'];
			} else { $ext_arr = array(); }
			
			// tmax Array
			if(!empty($req_arr['tmax']))
			{
				$tmax_arr = $req_arr['tmax'];
			} else { $tmax_arr = array(); }
			
			// wseat Array
			if(!empty($req_arr['wseat']))
			{
				$wseat_arr = $req_arr['wseat'];
			} else { $wseat_arr = array(); }
			
			// allimps Value - Flag to indicate whether Exchange can verify that all impressions

			if(!empty($req_arr['allimps']))
			{
				$allimps = $req_arr['allimps'];
			} else { $allimps = 0; }
			
			// Cur Array - Array of allowed currencies for bids on this bid request using ISO-4217 alphabetic codes.

			if(!empty($req_arr['cur']))
			{
				$cur_arr = $req_arr['cur'];
			} else { $cur_arr = array(); }
			
			// bcat Array - Blocked Advertiser Categories.
			if(!empty($req_arr['bcat']))
			{
				$bcat_arr = $req_arr['bcat'];
			} else { $bcat_arr = array(); }
			
			// badv Array - Array of strings of blocked top-level domains of advertisers.
			if(!empty($req_arr['badv']))
			{
				$badv_arr = $req_arr['badv'];
			} else { $badv_arr = array(); }
			
			$data['at'] = $at;
			$data['id'] = $bid_req_id;
			$data['device'] = $device_arr;
			$data['imp'] = $imp_arr;
			$data['site'] = $site_arr;
			$data['app'] =  $app_arr;
			$data['user'] = $user_arr;
			$data['ext'] = $ext_arr;
			$data['tmax'] = $tmax_arr;
			$data['wseat'] = $wseat_arr;
			$data['allimps'] = $allimps;
			$data['cur'] = $cur_arr;
			$data['bcat'] = $bcat_arr;
			$data['badv'] = $badv_arr;

			// Inserting informations to table Smaato request
			$con_type =empty($req_arr['device']['connectiontype'])?0:$req_arr['device']['connectiontype'];
			$dev_type = empty($req_arr['device']['devicetype'])?0:$req_arr['device']['devicetype'];
			$geo_type= empty($req_arr['device']['geo']['type'])?0:$req_arr['device']['geo']['type'];
			$dev_ip 	= empty($req_arr['device']['ip'])?'':$req_arr['device']['ip'];
			$dev_js 	= empty($req_arr['device']['js'])?'':$req_arr['device']['js'];
			$dev_make 	= empty($req_arr['device']['make'])?'':$req_arr['device']['make'];
			$dev_model = empty($req_arr['device']['model'])?'':$req_arr['device']['model'];
			$dev_os		=empty($req_arr['device']['os'])?'':$req_arr['device']['os'];
			$dev_ua 	= empty($req_arr['device']['ua'])?'':$req_arr['device']['ua'];
			
			if(!empty($req_arr['ext']['udi']))
			{
				$ext_udi = '';//$req_arr['ext']['udi'];
			} else {$ext_udi='';}
			$b_id 	= $req_arr['id'];
			if(!empty($req_arr['imp']))
			{
				foreach($req_arr['imp'] as $imp)
				{
					if(!empty($imp['banner']['btype']))
					{
						foreach($imp['banner']['btype'] as $btype)
						{
							$ban_type[] = $btype;
						}
					}
					if(!empty($btype))
					{
						$b_type=implode('|',$ban_type);
					}
					else
					{
						$b_type =null;// implode('|',$ban_type);
					}
					$b_height = empty($imp['banner']['h'])?0:$imp['banner']['h'];
					$b_width = empty($imp['banner']['w'])?0:$imp['banner']['w'];
					if(!empty($imp['banner']['mimes']))
					{
						foreach($imp['banner']['mimes'] as $bmime)
						{
							$ban_mime[] = $bmime;
						}
					}
					$b_mime = implode('|',$ban_mime);
					$bid_floor = empty($imp['bidfloor'])?0:$imp['bidfloor'];
					$display_manager = $imp['displaymanager'];
					$bid = $imp['id'];
				}
			}
			if(!empty($req_arr['site']['cat']))
			{
				foreach($req_arr['site']['cat'] as $cat)
				{
					$s_cat[] = $cat;
				}
			}
			
			if(!empty($s_cat))
			{
				$scat = implode('|',$s_cat);
			}
			else
			{
                		$scat = null;
			}
			$s_domain = empty($req_arr['site']['domain'])?'':$req_arr['site']['domain'];
			$s_id = empty($req_arr['site']['id'])?0:$req_arr['site']['id'];
			$s_name = empty($req_arr['site']['name'])?'':$req_arr['site']['name'];
			$s_p_id = empty($req_arr['site']['publisher']['id'])?0:$req_arr['site']['publisher']['id'];
			$gender = empty($req_arr['user']['gender'])?'':$req_arr['user']['gender'];
			$dob = empty($req_arr['user']['yob'])?'':$req_arr['user']['yob'];
			$impbp='0';
			
		$cur_date = date('Y-m-d H:i:s');
		mysqli_query($con,"INSERT INTO  `djax_smaato_bid_request` (
			`datetime` ,
			`at` ,
			`device_connectiontype` ,
			`device_devicetype` ,
			`device_geo_country` ,
			`device_geo_latitude` ,
			`device_geo_longitude` ,
			`device_geo_type` ,
			`device_ip` ,
			`device_js` ,
			`device_make` ,
			`device_model` ,
			`device_os` ,
			`device_ua` ,
			`ext_udi` ,
			`bid_request_id` ,
			`imp_banner_type` ,
			`imp_banner_height` ,
			`imp_banner_mimes` ,
			`imp_banner_position` ,
			`imp_banner_width` ,
			`imp_bidfloor` ,
			`imp_displaymanager` ,
			`imp_id` ,
			`site_category` ,
			`site_domain` ,
			`site_id` ,
			`site_name` ,
			`publisher_id` ,
			`user_gender` ,
			`user_yob`
			)
			VALUES (
			'".$cur_date."', '".$at."', '".$con_type."', '".$dev_type."', '".$dgc."', '".$geo_lat."', '".$geo_lon."', '".$geo_type."', '".$dev_ip."', '".$dev_js."', '".$dev_make."', '".$dev_model."', '".$dev_os."', '".$dev_ua."', '".$ext_udi."', '".$b_id."', '".$b_type."', '".$b_height."', '".$b_mime."', 0, '".$b_width."', '".$bid_floor."', '".$display_manager."', '".$bid."', '".$scat."', '".$s_domain."', '".$s_id."', '".$s_name."', '".$s_p_id."', '".$gender."', '".$dob."')");
			
			
	}
	else
	{
		$endtime=round(microtime(true) * 1000);
		$totaltime = $endtime-$starttime;
		if($totaltime > 60 and $totaltime < 140)
		{
			$log->logDebug("No json data provided by smato total time is greate than 60 <100   ".$totaltime);
                }
		else if($totaltime <60)
		{
			$log->logDebug("No json provided total time is < 60    ".$totaltime);
		}
		else
		{
		        $log->logDebug("No json provided total time is > 140   ".$totaltime);
		}
		header("HTTP/1.0 204");
                exit;
	} 
}

$arr_serialize = serialize($data);
$output = smaato_request($arr_serialize);

$new_adm = "<?xml version=\"1.0\" ?>";
$new_adm .="<ad xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"smaato_ad_v0.9.xsd\" modelVersion=\"0.9\" >";
$new_adm .="<imageAd>";
$new_adm .="<clickUrl>".$output['click_url']."</clickUrl>";
$new_adm .="<imgUrl>".$output['image_url']."</imgUrl>";
$new_adm .="<width>".$output['width']."</width>";
$new_adm .="<height>".$output['height']."</height>";
$new_adm .="<additionalText></additionalText>";
$new_adm .="<beacons>";
$new_adm .="<beacon>".$output['beacon_url']."</beacon>";
$new_adm .="</beacons>";
$new_adm .="</imageAd>";
$new_adm .="</ad>";


if($output['price']!=0){

$value=array(
		'id'=>$output['id'],
		'seatbid'=>array(
					array('bid'=>array(
								  array('id'=>$output['bid'],
										'impid'=>$output['impid'],
										'price'=>$output['price'],
										'adid'=>$output['adid'],
										'nurl'=>$output['nurl'],
										'adm'=>$new_adm,
										'adomain'=>array($output['adomain']),
										'iurl'=>null,
										'cid'=>$output['cid'],
										'crid'=>$output['crid'],
										'attr'=>array(),
										'ext'=>null,
										)
								),
							'seat'=>$output['seat'],
							'group'=>'0'
						)
					),
			'bidid'=>$output['bidid'],
			'customdata'=>null,
			'cur'=>'USD',
			'ext'=>null
	 );

        $response = json_encode($value);
        
        $search[] = "\/";
	$replace[] = "/";
        $finalres = str_replace($search, $replace, $response);

//	$log->logDebug("final res is ".$finalres);
/*	$response1 = stripslashes($response);

	$slash1 = "\\";
	$pos1 = "Location=";
	$response2 = insertstring($response1,$slash1,$pos1);

	$response13 = insertstring($response12,$slash1,$pos13);
*/
	$endtime=round(microtime(true) * 1000);
	$totaltime=$endtime-$starttime;


	if($totaltime > 60 and $totaltime < 140)
	{
		$log->logDebug("final res time is greater than 60 <100   ".$totaltime);
	}
	else if($totaltime <60)
	{
		$log->logDebug("final res  time is < 60    ".$totaltime);
	}
	else
	{
		$log->logDebug("final res time is > 140   ".$totaltime);
	}
    //   $log->logDebug("response is ".$finalres);
	print_r($finalres);


	 
}else{
        $endtime=round(microtime(true) * 1000);	
	$totaltime =$endtime-$starttime;

	if($totaltime > 60 and $totaltime < 140)
        {
                $log->logDebug("res is no bid  total time is greate than 60 <100   ".$totaltime);
        }
        else if($totaltime <60)
        {
                $log->logDebug("res is no bid total time is < 60   ".$totaltime);
        }
        else
        {
               $log->logDebug("res is no bid total time is > 140    ".$totaltime);
        }
	header("HTTP/1.0 204");
	exit;	
}
//empty bid response end
mysqli_close($con);
function insertstring($string, $slash, $pos) {
   return  str_replace($pos, $pos.$slash ,$string);
}


?>

