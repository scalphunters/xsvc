import sys
sys.path.append('./xsvc')
import xsvc

def _adder(request):
    return 100

xs=xsvc.XSVC()
xs.connect('localhost',2000)
xs.registerService('adder',_adder)
xs.serve()
xs.close()
