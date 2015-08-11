#!/bin/node

var net=require('net');
var xsvc=require('./xsvc/xsvc.js');

var routerHost=process.argv[2]==null? '':process.argv[2];
var routerPort=process.argv[3]==null? '':process.argv[3];

var tkr=process.argv[4]==null? '':process.argv[4];
var fld=process.argv[5]==null? '':process.argv[5];

var sd=process.argv[6]==null? '':process.argv[6];
var ed=process.argv[7]==null? '':process.argv[7];

if(tkr===''|| fld===''){
	console.log('Usage : node blp_client_refdata_argv.js [router host] [router port] [ticker] [field] [startdate] [enddate]');
	return;
}

var req={request:'blp_get_historical_data',params:{securities:[tkr],fields:[fld],startdate:sd,enddate:ed}};
xsvc.request(routerHost,routerPort,req,function(res){
	console.log('Result : ' + JSON.stringify(res.result));
});