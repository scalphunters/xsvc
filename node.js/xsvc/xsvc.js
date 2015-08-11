'use strict';

var net=require('net');

// XSVC Router
exports.router=function(port,options){
	var clnt_id=0;
	var request_id=0;
	var clientList=[]; // connected sockets
	var clientStatus={}; //clientStatus[id]=last heartbeat; {id:lastheartbeat};
	var ServiceMap=[]; // {service : 'getHelp', server : <socket> }
	var RequestQue=[]; // {request_id : xx , client : <socket>}

	var serverPort=port;

	if(port==null){
		console.log('Usage : node <router> [port]');
		process.exit(0);
	}

	var sweepDeadClients=setInterval(function(){
		var cur_time=(new Date()).getTime();
		var tmplist=[];
		clientList.forEach(function(elm){		
			if( (cur_time-clientStatus[elm.id]) > 30*60*1000 ){
				console.log(cur_time+' : ' + clientStatus[elm.id]);
				console.log(elm.id + ' is to be deleted');
				elm.write(JSON.stringify(xsvc_generate_request('terminate',{}))); // force disconnect to remote client
				tmplist.push(elm.id);
				remove_server(ServiceMap,elm);
			}
		});
		tmplist.forEach(function(elm){
			remove_socket_by_id(clientList,elm);
		});
	},5*60*1000);

	var spreadHearbeat=setInterval(function(){
		var heartbeat={};
		heartbeat=xsvc_generate_request('heartbeat',{});
		broadcast(clientList,JSON.stringify(heartbeat));
	},5*60*1000);

	var server=net.createServer(function(c){
		c.on('end',function(){
			console.log('client ' + c.id +' disconnected');
			remove_socket(clientList,c);
			remove_server(ServiceMap,c);
		});
		c.on('data',function(data){
			//preprocess data

			var req={};
				c.buffer=c.buffer+data;
				var req_arr=crop_requests_from_data(c.buffer);
				if(req_arr.requests.length>0){
					c.buffer=req_arr.leftover;
				}
				req_arr.requests.forEach(function(req_json){
					try{
						 req=JSON.parse(req_json);
						 switch(req.request){
						 	case 'register' :
						 		console.log('From Server '+ c.id + ' : ' + JSON.stringify(req));
						 		req.params.handlers.forEach(function(elm){
						 			add_server(ServiceMap,elm,c);
						 		});
						 		break;
						 	case 'heartbeat' :
						 		clientStatus[c.id]=(new Date()).getTime(); //heartbeat updates
						 		break;
						 	case 'servicelist':
						 		console.log('From requestor '+ c.id + ' : ' + JSON.stringify(req));
						 		req.request_id=request_id++;
						 		var servicelist=[];
						 		ServiceMap.forEach(function(elm){
						 			servicelist.push(elm.service);
						 		});
					 			var res=xsvc_generate_response(req,servicelist);
					 			console.log('To requestor : '+c.id+' : '+JSON.stringify(res));
					 			c.write(JSON.stringify(res));					 		
						 		break;
						 	case 'response' : // response routing
						 		console.log('From server : '+c.id + ' msg : ' + JSON.stringify(req));
						 		c.status--; //server job status 
						 		for(var i=0;i<RequestQue.length;i++){
						 			if(req.request_id==RequestQue[i].request_id){
						 				RequestQue[i].client.write(req_json);
						 				console.log('To requestor '+RequestQue[i].client.id + ' msg sent ' + req_json);
						 				break;
						 			}
						 		}
						 		break;
						 	default:
						 		var requested=false;						 			
					 			var server=find_idler_server(ServiceMap,req.request);					 			
					 			if(server!==null){				 				
					 				console.log('From requestor '+ c.id + ' : ' + JSON.stringify(req));
					 				req.request_id=request_id++;
					 				server.write(JSON.stringify(req));
					 				requested=true;
					 				server.status++;
					 				RequestQue.push({request_id:req.request_id,client:c});
					 				console.log('To server '+server.id +' msg sent : '+ JSON.stringify(req));
					 				break;
					 			}
						 		if(!requested) {
						 			var res=xsvc_generate_response(req,{msg:'There is no available server'});
						 			console.log('To requestor : '+JSON.stringify(res));
						 			c.write(JSON.stringify(res));
						 		}
						 		break;
						 }
					}catch(e){
						console.log(req_json);
						console.log('not JSON format');
					}						
				});

		});
		c.on('error',function(err){
			console.log(err.stack);
			c.emit('end');
		});
	});

	server.on('end',function(){
		clearInterval(sweepDeadClients);
		clearInterval(spreadHearbeat);
	});

	server.on('error',function(e){
		console.log('Error');
	});
	server.on('connection',function(c){
		c.id=clnt_id++;
		c.status=0;
		c.buffer='';
		clientList.push(c);
		clientStatus[c.id]=(new Date()).getTime();		
		var req={};
	});

	server.listen(serverPort,function(){
			console.log('server bound at port : '+serverPort);
	});	
}//end of run

function crop_requests_from_data(data_json){ //returns array of json objects;
	var regex=/}\s*{/;
	var result=data_json.match(regex);
	var request_array=[];
	var leftover=data_json;
	while(result !== null){
		var req=leftover.substring(0,result.index+1);
		try{	
			JSON.parse(req);
			request_array.push(req);
		}catch(e){
		}
		leftover=leftover.substring(result.index+1,leftover.length);
		result=leftover.match(regex);
	}
	try{
		JSON.parse(leftover);
		request_array.push(leftover);	
		leftover='';
	}catch(e){}
	return {requests:request_array,leftover:leftover};
}

function add_server(list,service,server){
	var serv={service:service,server:server};
	server.status=0;
	list.push(serv);
}
function broadcast(clientList,msg){
	clientList.forEach(function(clnt){
		clnt.write(msg);
	});
}
function remove_socket(list,socket){
	var position=list.indexOf(socket);
	list.splice(position,1);
}
function remove_socket_by_id(list,id){
	var pos=0;
	for(var i=0;i<list.length;i++){
		if(list[i].id==id){
			pos=i;
			break;
		}
	}
	list.splice(pos,1);
}
function remove_server(list,server){
	
	var idx=[];
	for(var i=list.length-1;i>=0;i--){
		var elm=list[i];
		if(elm.server==server){
			idx.push(i);
		}
	}
	idx.forEach(function(id){
		list.splice(id,1);
	});

};
function find_idler_server(servicemap,service){
	var server=null;
	var previous_status=0;
	servicemap.forEach(function(elm){
		if(elm.service==service){
			if(server==null || elm.server.status<previous_status){
				server=elm.server;
				previous_status=elm.server.status;
			}
		}
	});
	return server;
};
function xsvc_generate_request(service_name,params){
	var req={};
	req.start='<start>';
	req.request=service_name;
	req.params=params;
	req.end='<end>';
	return req;
}
function xsvc_generate_response(request,result){
	var res={};
	res.start='<start>';
	res.request='response';
	res.result=result;
	res.request_id=request.request_id;
	res.end='<end>';
	return res;
}
//XSVC Server

exports.server=function(host,port,rh,options){ //rh : request handling object storing service name and functions
	var serverHost=host;
	var serverPort=port;
	var buffer='';
	//Registration and connection
	var client=net.connect({port:serverPort,host:serverHost},function(){
			console.log('connected to server');
			var req=xsvc_generate_request('register',{handlers:Object.keys(rh)});
			client.write(JSON.stringify(req));
		});
	//configure ends...
	client.on('data',function(data){

			var req={};	
			buffer=buffer+data;
			var req_arr=crop_requests_from_data(buffer);
			if(req_arr.requests.length>0)
				buffer=req_arr.leftover;
			req_arr.requests.forEach(function(req_json){
				try{
					req=JSON.parse(req_json);
					switch(req.request){
						case 'terminate':
							console.log('Terminating from remote request');
							client.end();
							break;
						case 'status':
							var res_json=JSON.stringify({start:'<start>',request: 'response' ,result:{status:true},end:'<end>'});
							client.write(res_json);
							console.log("MSG sent to server : " +res_json);
							break;
						case 'heartbeat':
							client.write(req_json);
							break;
						case 'response':
							console.log('From Server : ' + req_json.toString());
							break;											
						default:
							console.log('From Router : ' + req_json);
							rh[req.request](req,function(result){
								var response=xsvc_generate_response(req,result);
								var res_json=JSON.stringify(response);
								console.log('To Router : ' + res_json);
								client.write(res_json);							
							});
							break;
					}
				}catch(e){
					console.log(req_json);
					console.log(e);	
				}//end try	
			});//end foreach

	});

	client.on('end',function(){
		console.log('disconnected from server');
		client.end();
	});
	client.on('error',function(e){
		console.log(e.stack);
		client.emit('end');
	});
}

//XSVC Client Request (request = {service_name : ' ', params: ' '})
exports.request=function(host,port,request,callback){
	var buffer='';
	var serverHost=host;
	var serverPort=port;
	var req=xsvc_generate_request(request.request,request.params);
	
	//connection
	var client=net.connect({port:serverPort,host:serverHost},function(){
			console.log('connected to server');
			console.log('To Router : '+ JSON.stringify(req));
			client.write(JSON.stringify(req));
		});

	//event handling
	client.on('data',function(data){		
		var req={};
			buffer=buffer+data;
			var req_arr=crop_requests_from_data(buffer);
			if(req_arr.requests.length>0){
				buffer=req_arr.leftover;
			}
			//foreach
			req_arr.requests.forEach(function(request_elm){
				try{
					req=JSON.parse(request_elm);
					switch(req.request){
						case 'terminate':
							console.log('Terminating from remote request');
							client.end();
							break;
						case 'status':
							var res_json=JSON.stringify({start:'<start>',request: 'response' ,result:{status:true},end:'<end>'});
							client.write(res_json);
							break;
						case 'heartbeat':
							client.write(request_elm);						
							break;
						case 'response':
							console.log('From router : ' + request_elm.toString());
							callback(req); // callback when request_elm is all arived
							client.end();
							break;
						default:
							console.log("Error...");
							client.end();
							break;
					}//end switch
				}catch(e){
					console.log(request_elm);
					console.log(e);	
				}//and try-catch		
			});//end foreach
	});

	client.on('end',function(){
		console.log('disconnected from server');
		client.end();
	});
	client.on('error',function(e){
		console.log(e.stack);
		client.emit('end');
	});
} //end of Client