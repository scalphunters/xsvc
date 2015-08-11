import sys
sys.path.append('./xsvc')
import xsvc
import json
import datetime
import dateutil.parser


def _adder(request):
    return 100

xs=xsvc.XSVC()
tmpreq={'request':'blp_get_historical_data','params':{'securities':['usdkrw curncy'],'fields':['px_last'],'startdate':'20150601','enddate':'20150701'}}
xs.connect('localhost',2000)
tmp2=json.loads(xs.request(tmpreq))['result'][0]['field_data']

ts={}
for elm in tmp2:
    date=dateutil.parser.parse(elm['date'])
    date_str=date.strftime('%Y-%m-%d')
    ts[date_str]=elm['px_last']

print(ts)
xs.registerService('adder',_adder)
#xsvc.serve()
xs.close()

