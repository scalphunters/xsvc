var xsvc=require('./xsvc/xsvc.js');

if(process.argv[2]==null || process.argv[3]==null){
	console.log('Usage : node <service> [host] [port] [service_name]');
	process.exit(0);
}

routerHost=process.argv[2];
routerPort=process.argv[3];
service_name=process.argv[4];

var req={request:service_name,params:{currencypair:'usdkrw',lhs:3,rhs:6}};
xsvc.request(routerHost,routerPort,req,function(res){
	console.log('Result : ' + JSON.stringify(res.result));
});