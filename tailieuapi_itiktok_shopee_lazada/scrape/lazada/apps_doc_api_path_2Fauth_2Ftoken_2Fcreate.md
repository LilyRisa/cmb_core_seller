# GET/POSTGenerateAccessToken

> Source: https://open.lazada.com/apps/doc/api?path=%2Fauth%2Ftoken%2Fcreate
> Scraped: 2026-05-20T22:43:56.231Z

---

Latest update2026-05-21 06:43:32

500

GenerateAccessToken

GET/POST

/auth/token/create

No Authorization Required

Description:generate access\_token for call api

## Service Endpoints

| 
Region

 | 

Endpoint

 |
| --- | --- |
| 

All

 | 

https://auth.lazada.com/rest

 |

Did this chapter help you?

YesNo

## Common Parameters

| 
Name

 | 

Type

 | 

Required or not

 | 

Description

 |
| --- | --- | --- | --- |
| 

app\_key

 | 

String

 | 

Yes

 | 

Unique app ID issued by LAZADA Open Platform console when you apply for an app category

 |
| 

timestamp

 | 

String

 | 

Yes

 | 

The time stamp of the request e.g. 1517820392000 (which translates to 5 February 2018 08:46:32) with less than 7200s difference from UTC time

 |
| 

access\_token

 | 

String

 | 

No

 | 

API interface call credentials

 |
| 

sign\_method

 | 

String

 | 

Yes

 | 

The HMAC hash algorithm you are using to calculate your signature

 |
| 

sign

 | 

String

 | 

Yes

 | 

Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details)

 |

Did this chapter help you?

YesNo

## Parameters

| 
Name

 | 

Type

 | 

Required or not

 | 

Description

 |
| --- | --- | --- | --- |
| 

code

 | 

String

 | 

Yes

 | 

oauth code, get from app callback URL

 |
| 

uuid

 | 

String

 | 

No

 | 

This field is currently invalid, do not use this field please

 |

Did this chapter help you?

YesNo

## Response Parameters

| 
Name

 | 

Type

 | 

Description

 |
| --- | --- | --- |
| 

expires\_in

 | 

Number

 | 

The expiring time of the access token, in seconds

 |
| 

account\_id

 | 

String

 | 

Account ID，Allow null. if(account\_platform=seller\_center) account\_id=null

 |
| 

country

 | 

String

 | 

The country ID (sg:Singapore, my:Malaysia, ph:Philippines, th:Thailand, id:Indonesia, vn:Vietnam)

 |
| 

country\_user\_info

 | 

Object\[\]

 | 

Country user details

 |
| 

country

 | 

String

 | 

The country ID,(sg:Singapore, my:Malaysia, ph:Philippines, th:Thailand, id:Indonesia, vn:Vietnam)

 |
| 

seller\_id

 | 

String

 | 

Seller Id

 |
| 

user\_id

 | 

String

 | 

User Id

 |
| 

short\_code

 | 

String

 | 

Seller short code

 |
| 

account\_platform

 | 

String

 | 

Account platform

 |
| 

access\_token

 | 

String

 | 

Access token

 |
| 

account

 | 

String

 | 

User account(login user)

 |
| 

refresh\_expires\_in

 | 

String

 | 

The expiring time of th refresh token

 |
| 

refresh\_token

 | 

String

 | 

Refresh token, used to refresh the token when “refresh\_expires\_in”>0.

 |

Did this chapter help you?

YesNo

## Error Code

| 
Error Code

 | 

Error Message

 | 

Solution

 |
| --- | --- | --- |
| 

MissingParameter

 | 

the input parameter “sign” that is mandatory for processing this request is not supplied

 | 

1

 |
| 

IncompleteSignature

 | 

The request signature does not conform to lazop standards

 | 

1

 |
| 

InvalidCode

 | 

Invalid authorization code

 | 

Possible causes, incorrect authorisation url; authorisation code more than half an hour old

 |
| 

InvalidCode

 | 

Invalid authorization code

 | 

1、please check if your Code is from the callback URL;2、Please check if your Code has already been used, each Code can only be used once;3、Code is valid for 30 minutes, it will expire after 30 minutes;4、Please check whether you are using the endpoints required by the API documentation;5、Please check whether the client id and the appkey of the request are the same when authorizing.

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/auth/token/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/auth/token/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/auth/token/create");
request.addApiParameter("code", "0_100132_2DL4DV3jcU1UOT7WGI1A4rY91");
request.addApiParameter("uuid", "This field is currently invalid,  do not use this field please");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "access_token": "50000601c30atpedfgu3LVvik87Ixlsvle3mSoB7701ceb156fPunYZ43GBg",
  "country": "sg",
  "refresh_token": "500016000300bwa2WteaQyfwBMnPxurcA0mXGhQdTt18356663CfcDTYpWoi",
  "account_id": "7063844",
  "code": "0",
  "account_platform": "seller_center",
  "refresh_expires_in": "60",
  "country_user_info": [
    {
      "country": "SG",
      "user_id": "1001",
      "seller_id": "1001",
      "short_code": "SG1001"
    }
  ],
  "expires_in": "10",
  "request_id": "0ba2887315178178017221014",
  "account": "xxx@126.com"
}
```

Please rate this article

Popular Articles
