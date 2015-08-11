var xsvc=require('./xsvc/xsvc.js');

if(process.argv[2]==null || process.argv[3]==null){
	console.log('Usage : node <service> [host] [port]');
	process.exit(0);
}
routerHost=process.argv[2];
routerPort=process.argv[3];

var rh={}; //request handler object
rh.addNumbers=function(request,cb){
	//pre
	var params=request.params;
	//process
	result=params.lhs+params.rhs;
	//cleanup
	return cb(result);
};
rh.subNumbers=function(request,cb){
	//pre
	var params=request.params;
	//process
	result=params.lhs-params.rhs;
	//cleanup
	return cb(result);
};

//attach server to router
xsvc.server(routerHost,routerPort,rh);