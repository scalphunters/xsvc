<?php 

function xsvc_generate_request($service_name,$params_obj){
	$req=array();
	$req['start']='<start>';
	$req['request']=$service_name;
	$req['params']=$params_obj;
	$req['end']='<end>';
	return $req;
}//end of function generate_request
function xsvc_generate_response($requestobj,$resultobj){
	$res=array();
	$res['start']='<start>';
	$res['request']='response';
	$res['result']=$resultobj;
	$res['request_id']=$requestobj['request_id'];
	$res['end']='<end>';
	return $res;
}// end of function generate_response($requestobj,$resultobj)

function crop_requests_from_data($data_json){
	$valid_json_requests=array();
	$leftover='';
	if(json_decode($data_json,true)!=NULL){ // in case 1 json string
		array_push($valid_json_requests,$data_json);
		return array('requests'=>$valid_json_requests,'leftover'=>$leftover);
	}
	$pattern='/}\s*{/';
	$reqs=preg_split($pattern,$data_json);

	$leftover='';
	for($i=0;$i<count($reqs);$i++){
		if($i==0)
			$reqs[$i]=$reqs[$i]."}";
		else if($i==count($reqs)-1){
			$leftover="{".$reqs[$i];
			$reqs[$i]="{".$reqs[$i];
		}
		else
			$reqs[$i]="{".$reqs[$i]."}";

		if(json_decode($reqs[$i],true)!=NULL){
			array_push($valid_json_requests,$reqs[$i]);
			if($i==count($reqs)-1)
				$leftover='';
		}
	}


	return array('requests'=>$valid_json_requests,'leftover'=>$leftover);
}//end of function crop_requests_from_data($data_json)

class XSVC_Server{ //extensible socket service
	
	public $rh;
	public function __construct(){
	}
	public function __destruct(){
	}
	public function register_request_handler(&$requesthandler){
		$this->rh=$requesthandler;
	}
	public function run($address,$port,&$req_handler){
		
		$this->register_request_handler($req_handler);
		
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($socket === false) {
			echo "socket_create()   " . socket_strerror(socket_last_error()) . "\n";
			return -1;
		} else {
		}
		$result = socket_connect($socket, $address, $port);
		if ($result === false) {
			echo "socket_connect() failed .\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
			return -1;
		} else {
		}
		//register services
		$req_json=json_encode($this->rh->generate_registry_request());
		echo $req_json.PHP_EOL;
		socket_write($socket,$req_json,strlen($req_json));
		//looping
		$buffer='';
		while(true){
			$data = socket_read($socket, 1000) or die("Could not read from Socket\n");
			$buffer=$buffer.$data;
			//preprocess
			$req_arr=crop_requests_from_data($buffer);
			if(count($req_arr['requests'])>0)
				$buffer=$req_arr['leftover'];
			
			for($i=0;$i<count($req_arr['requests']);$i++){	
				$request_elm=$req_arr['requests'][$i];
				$request=json_decode($request_elm,true);
				switch($request['request']){
					case 'terminate':
						socket_close($socket);
						print('Terminating from remote request'."\n");
						return;
						break;
					case 'heartbeat':
						socket_write($socket,$request_elm,strlen($request_elm));
						break;
					case 'response':
						print($request_elm."\n");
						break;
					default:
						print("From router : ".$request_elm."\n");
						$response=$this->rh->serve($request);
						$response_json=json_encode($response);
						socket_write($socket,$response_json,strlen($response_json));
						print("To router : ".$response_json."\n");
						break;
				}//end switch		
			}//end for
		} //end of while
		socket_close($socket);
	}// end of function run();	
}//end of SocketService

class XSVC_Client{
	public $request;
	public $response;
	public function __construct(){
		$this->request=array();
		$this->response=array();
	}
	public function __destruct(){
	}
	public function set_request($service_name,$params){
		$this->request=xsvc_generate_request($service_name,$params);
	}
	public function request($address,$port,$options){ //options.request.service_name & options.request.params
		
		$service_name=$options['request'];
		$params=$options['params'];
		
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($socket === false) {
			echo "socket_create()   " . socket_strerror(socket_last_error()) . "\n";
		} else {
		}
		$result = socket_connect($socket, $address, $port);
		if ($result === false) {
			echo "socket_connect() failed .\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
		} else {
		}
		$this->set_request($service_name,$params);
		$req=$this->request;
		$req_json=json_encode($req);
		print("\nTo router : ".$req_json."\n");
		socket_write($socket,$req_json , strlen($req_json));	
		//receive response		
		$buffer='';
		while(true){
			$data=socket_read($socket,10000);
			
			$buffer=$buffer.$data;
			$req_arr=crop_requests_from_data($buffer);
			if(count($req_arr['requests'])>0)
				$buffer=$req_arr['leftover'];
			for($i=0;$i<count($req_arr['requests']);$i++){
				$res_json=$req_arr['requests'][$i];
				$res=json_decode($res_json,true);
				switch($res['request']){
					case 'terminate':
						print('Terminating from remote request'."\n");
						socket_close($socket);
						return null;
						break;
					case 'heartbeat':
						socket_write($socket,$res_json,strlen($res_json));
						break;
					case 'response':
						print("From router : ".$res_json."\n\n");
						$this->response=$res;
						socket_close($socket);
						return $this->response;
						break;
					default:
						print_r($res);
						print("Error....\N");
						socket_close($socket);
						return null;
						break;
				}//end switch
			}//end for
		}//end while
		socket_close($socket);
	}//end of request function
}

class XSVC_RequestHandler{
	public $service;  // manually register function by  : $rh->service['sum']=function($request){return $request['lhs'] + $request['rhs'];};	
	public function __construct(){
		$this->service=array();
	}
	public function __destruct(){
	}
	public function add_service($service_name,$anonymous_func_obj){ // add service using anonymous function, return value should be resultobject
		$this->service[$service_name]=$anonymous_func_obj;
	}	
	public function serve($request){ // get request object to return response object {request : service_name, params...}
		$result_obj=$this->service[$request['request']]($request);
		$response=xsvc_generate_response($request,$result_obj);
		return $response;
	}
	public function get_service_names(){ //get service name to register to router
		return array_keys($this->service);
	}
	public function generate_registry_request(){
		$keys=$this->get_service_names();
		$params_obj=array('handlers'=>$keys);
		return xsvc_generate_request("register",$params_obj);
	}
}//end of RequestHandler


?>