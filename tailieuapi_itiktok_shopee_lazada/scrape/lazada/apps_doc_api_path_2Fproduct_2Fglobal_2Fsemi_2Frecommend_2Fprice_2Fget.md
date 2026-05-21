# GET/POSTGetRecommendPrice

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fglobal%2Fsemi%2Frecommend%2Fprice%2Fget
> Scraped: 2026-05-20T22:54:13.697Z

---

Latest update2024-02-28 22:44:17

2872

GetRecommendPrice

GET/POST

/product/global/semi/recommend/price/get

Authorization Required

Description:get recommend price

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

payload

 | 

Payload

 | 

Yes

 | 

request data

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

Object

 | 

data

 |
| 

item\_id

 | 

Number

 | 

0

 |
| 

skus

 | 

Object\[\]

 | 

1

 |
| 

seller\_sku

 | 

String

 | 

seller sku

 |
| 

country\_price

 | 

Object\[\]

 | 

country info

 |
| 

market

 | 

String

 | 

LAZADA\_SG

 |
| 

no\_postage\_price

 | 

String

 | 

10.01

 |
| 

currency

 | 

String

 | 

SGB

 |
| 

sku\_id

 | 

Number

 | 

0

 |
| 

global\_item\_id

 | 

Number

 | 

0

 |
| 

success

 | 

Boolean

 | 

true

 |
| 

error\_code

 | 

String

 | 

null

 |
| 

error\_msg

 | 

String

 | 

null

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

No Data

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/global/semi/recommend/price/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/product/global/semi/recommend/price/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/global/semi/recommend/price/get");
request.addApiParameter("payload", "request data");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "null",
  "code": "0",
  "data": {
    "global_item_id": "0",
    "skus": [
      {
        "seller_sku": "wangyi-test-sku-0308-001-1",
        "sku_id": "0",
        "country_price": [
          {
            "market": "LAZADA_SG",
            "no_postage_price": "10.01",
            "currency": "SGB"
          }
        ]
      }
    ],
    "item_id": "0"
  },
  "success": "true",
  "error_code": "null",
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
