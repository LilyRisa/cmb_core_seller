# GETGetGlobalProductStatus

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fglobal%2Fstatus%2Fget
> Scraped: 2026-05-20T22:53:58.454Z

---

Latest update2022-07-29 12:17:23

5456

GetGlobalProductStatus

GET

/product/global/status/get

Authorization Required

Description:Use this API to query the status of the specified global product. It takes several minutes for the global product to be created on each site. (CrossBoarderSellersOnly)

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

params

 | 

Object

 | 

Yes

 | 

put the "sellerSku" as the key

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

data

 | 

String

 | 

result json type string

 |
| 

success

 | 

Boolean

 | 

success flag

 |
| 

error\_code

 | 

String

 | 

error code

 |
| 

error\_msg

 | 

String

 | 

error msg

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

"E0207"

 | 

"E207: SKU not exist"

 | 

This SKU can not be found under your shop account.

 |
| 

E0208

 | 

Product not exist

 | 

The requested seller sku does not exist in the current store, please check the correctness of the seller sku.

 |
| 

E1000

 | 

Internal Application Error

 | 

Endpoint exception, please use MY endpoint for GSP related requests.

 |
| 

E0208

 | 

Product not exist

 | 

The requested seller sku does not exist in the current store, please check the correctness of the seller sku.

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/global/status/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/product/global/status/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/global/status/get");
request.setHttpMethod("GET");
request.addApiParameter("params", "{\"sellerSku\" : \"123\"}");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "get SKU failed",
  "code": "0",
  "data": "{}",
  "success": "true",
  "error_code": "E207",
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
