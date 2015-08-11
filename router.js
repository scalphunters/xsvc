#!/bin/env node     

var xsvc=require('./node.js/xsvc/xsvc.js');

if(process.argv[2]===null){
	console.log('Usage : node <service> [port]');
	process.exit(0);
}
xsvc.router(process.argv[2]);