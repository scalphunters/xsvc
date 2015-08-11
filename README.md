# XSVC

## 1. Dependencies :
Nothing

## 2. Protocol
{start:'<start>', request : type_of_request , params : {...}, end:'<end>'}
-  req = {start:'<start>' , request : 'addNumbers' , params : {numbers : [1,2,3,4]} , end:'<end>'}

if request is sent to router, router pass it to the corresponding server and get the response.
-  res = {start:'<start>' , request : 'response' , result : {value:10} , end:'<end>'}

## 3. Service Registration (request from Service server to Router)
- request : 'register', params : {handlers : ['addNumbers','subNumbers',...]}    


## 4. Service Provider specification
 Get request and return response as request
1. Register to server router
2. Get request from router and return to router with {request : 'response'}, returning message must have {request:'response'}, otherwise service will fall into infinite loop.

## Dons and Todo

Done : 
- xsvc.js router, server and client
- xsvj.php : server and client

Todo :
- xsvc.cpp
- xsvc.py
- xsvc.go
...
