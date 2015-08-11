
from socket import *
import sys
import json
import re

BUFFSIZE=1024

def crop_a_request_from_data(req_json):   
    try:
        json.loads(req_json)
        ret={'request':req_json,'leftover':''}
        return ret
    except ValueError:       
        reg=re.compile('}\s*{')
        tmp=reg.search(req_json)
        if tmp!=None:     
            print(tmp.group(),tmp.start(),tmp.end())
            req_str=req_json[:tmp.start()+1] 
            lo=req_json[tmp.start()+1:]
            try:
                json.loads(req_str)
                return {'request':req_str,'leftover':lo}
            except:
                return {'request':None,'leftover':lo}
        else:
            return{'request':None,'leftover':req_json}

def xsvc_generate_request(req,param):
    return ({'request':req,'params':param})
    
def xsvc_generate_response(req,result):
    return ({'request':'response','result':result,'request_id':req['request_id']})

class XSVC:
    servicelist=[]
    request_handler={}
    def connect(self,host,port):
        self.clientSocket=socket(AF_INET,SOCK_STREAM)
        try:
            self.clientSocket.connect((host,port))
        except:
            print("Couldn't connect to server\n")
            sys.exit()
        finally:
            print("Connected to server")
    def serve(self):
        buff=''
        req_json=json.dumps(xsvc_generate_request('register',{'handlers':self.servicelist}))
        self.clientSocket.send(req_json)
        while True:
            buff+=self.clientSocket.recv(BUFFSIZE)
            req=crop_a_request_from_data(buff)
            buff=req['leftover']
            if req['request']!=None:
                try:
                    req=json.loads(buff)
                    if req['request']=='terminate':
                        print("Terminating")
                        self.close()
                    elif req['request']=='heartbeat':
                        clientSocket.send(buff)
                    elif req['request']=='response':
                        print('From Router : %s ' % buff)                
                    else:
                        result=self.request_handler[req['request']](req)
                        msg=xsvc_generate_response(req,result)
                        self.clientSocket.send(json.dumps(msg))
                except:
                    print("Exception occurred")
                finally:
                    pass           
    def registerService(self,servicename,func): # function should have request argument
        self.request_handler[servicename]=func
        self.servicelist.append(servicename)
    def request(self,req):
        self.clientSocket.send(json.dumps(req))
        buff_cl=self.clientSocket.recv(BUFFSIZE)
        while crop_a_request_from_data(buff_cl)['request']==None:
            buff_cl+=self.clientSocket.recv(BUFFSIZE)
        return crop_a_request_from_data(buff_cl)['request']
    def close(self):
        self.clientSocket.close();
        print("Connection Closed")


