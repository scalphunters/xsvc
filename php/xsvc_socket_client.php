<?php 
include('./xsvc/xsvc.php');

if($argv[1]==NULL || $argv[2]==NULL){
	print("Usage : php <servicefile> [host] [port] [servicename] \n");
	return;
}else{
	$host=$argv[1];
	$port=$argv[2];
	$service_name=$argv[3];
}


//Client 
$client=new XSVC_Client;

$req=xsvc_generate_request($service_name,array('currencypair'=>'eurkrw','securities'=>array('usdkrw curncy'),
		'fields'=>array('px_last'),'startdate'=>'20140325','enddate'=>'20150630'));
var_dump($client->request($host,$port,$req));

?>