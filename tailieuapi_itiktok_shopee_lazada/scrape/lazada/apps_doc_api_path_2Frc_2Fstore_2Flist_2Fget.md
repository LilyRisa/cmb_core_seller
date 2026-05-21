# GET/POSTGetPickUpStoreList

> Source: https://open.lazada.com/apps/doc/api?path=%2Frc%2Fstore%2Flist%2Fget
> Scraped: 2026-05-20T22:45:04.013Z

---

Latest update2022-07-28 17:14:44

7500

GetPickUpStoreList

GET/POST

/rc/store/list/get

Authorization Required

Description:return the list of pick up store infomation for requested Seller

## Service Endpoints

| 
Region

 | 

Endpoint

 |
| --- | --- |
| 

Vietnam

 | 

https://api.lazada.vn/rest

 |
| 

Singapore

 | 

https://api.lazada.sg/rest

 |
| 

Philippines

 | 

https://api.lazada.com.ph/rest

 |
| 

Malaysia

 | 

https://api.lazada.com.my/rest

 |
| 

Thailand

 | 

https://api.lazada.co.th/rest

 |
| 

Indonesia

 | 

https://api.lazada.co.id/rest

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

Yes

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

No Data

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

result

 | 

String

 | 

result

 |
| 

headers

 | 

Object

 | 

xx

 |
| 

success

 | 

Boolean

 | 

true/false

 |
| 

model

 | 

Object

 | 

result DTO

 |
| 

biz\_ext\_map

 | 

Object

 | 

xx

 |
| 

mapping\_code

 | 

String

 | 

xx

 |
| 

msg\_info

 | 

String

 | 

msg\_info

 |
| 

msg\_code

 | 

String

 | 

msg\_code

 |
| 

http\_status\_code

 | 

Number

 | 

http\_status\_code

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

IllegalAccessToken

 | 

The specified access token is invalid or expired

 | 

access token is invalid or expired

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/rc/store/list/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/rc/store/list/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/rc/store/list/get");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "biz_ext_map": {},
    "headers": {},
    "msg_code": "FAIL_BIZ_SELLERID_NULL",
    "http_status_code": "xx",
    "success": "true/false",
    "msg_info": "sellerId can\u0027t be null",
    "model": {},
    "mapping_code": "xx"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
