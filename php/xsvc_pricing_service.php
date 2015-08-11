<?php

include ('./xap/_core_mysqlheader.php');
include ('./xap/_core_pricing.php');
include ('./xap/_core_pricing_db.php');
include ('./xsvc/xsvc.php');

if($argv[1]==NULL || $argv[2]==NULL){
	print("Usage : php <servicefile> [host] [port]\n");
	return;
}else{
	$host=$argv[1];
	$port=$argv[2];
}

//register request handlers
$rh=new XSVC_RequestHandler;


//Implementation of services <ToDos>

$rh->add_service('xap_fx_getspotrate',function($request){
	//initialize
	$params=$request['params'];	
	global $_gbl_mysql_host,$_gbl_user_id,$_gbl_password,$_gbl_db_name;
	$conn=new mysqli($_gbl_mysql_host,$_gbl_user_id,$_gbl_password,$_gbl_db_name);	
	
	//process
	$ccypair=$params['currencypair'];
	$baseccy=substr($ccypair,0,3);
	$quoteccy=substr($ccypair,3,3);
	$result=get_spot_rate($conn,$baseccy,$quoteccy);
	
	//cleanup
	$conn->close();
	return $result;
});
$rh->add_service('xap_admin_bootstrap_all',function($request){
	//initialize
	$params=$request['params'];
	global $_gbl_mysql_host,$_gbl_user_id,$_gbl_password,$_gbl_db_name;
	$conn=new mysqli($_gbl_mysql_host,$_gbl_user_id,$_gbl_password,$_gbl_db_name);

	//process
	$tz_int=9;//Korea
	$tdy=intval((time()+3600*$tz_int)/86400);
	$tdy_str=serial_to_date($tdy);
	update_all_formula_rates_in_db($conn);
	echo 'Bootstrapping...'.PHP_EOL;
	bootstrap_all_curves_in_db($conn,$tdy_str);
	echo 'Completed.'.PHP_EOL;
	$result="finished";
	//cleanup
	$conn->close();
	return $result;
});

//server run
$ss=new XSVC_Server;
$ss->register_request_handler($rh);
$ss->run($host,$port,$rh);

?>