import requests
import random
import hashlib

def rand_base36(n):
	return ''.join(random.choice('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ') for i in range(n))



def call_deposit():
    oid = rand_base36(10)
    deposit_url = 'https://midpay.cc/dev/ben/api/deposits/' + oid
    print deposit_url
    request_body = {
        "amount": 500,
        "ip": '127.0.0.1',
        "callback": "https://midpay.cc/dev/ben/callback",
        "testStatus": 'SUCCESS',
        "testResponseType": 'URL'
    }
    response = requests.put(deposit_url, data = request_body,
                    headers={
                        "API-Key": "OH_VERY_SECRET_API_KEY",
                    }
                )
    print response.text

def call_callback():
    callback_url = 'https://midpay.cc/dev/ben/api/callback/' + 'DHPD8PKNRU'
    request_body = {
        "amount": 11.9900,
        "closed": "1580297204",
        "orderId": "DHPD8PKNRU",
        "status": "SUCCESS"
    }
    request_body["sign"] = hashlib.sha256('amount='+str(request_body["amount"])+
        '&closed='+request_body["closed"]+'&orderId='+request_body["orderId"]+
            '&status='+request_body["status"]+'&'+"OH_VERY_SECRET_API_KEY").hexdigest()

    print(request_body)
    response = requests.post(callback_url, data = request_body,

                )
    print response.text

#call_deposit()
call_callback()