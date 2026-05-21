# POSTdeleteMerchantProduct

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fglobal%2Fdelete
> Scraped: 2026-05-20T22:56:56.933Z

---

Latest update2024-03-29 10:46:20

2192

deleteMerchantProduct

POST

/product/global/delete

Authorization Required

Description:Use this API to delete the product。(CrossBoarderSellersOnly)

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

type

 | 

String

 | 

Yes

 | 

Product Types

 |
| 

country

 | 

String

 | 

No

 | 

country,if type is "global", this field will be ignored

 |
| 

product\_id

 | 

Number

 | 

Yes

 | 

When type is "global", it is the global product ID, when type is "single", product id is the IC product ID.

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

body，deleteGspProductResult is true，mean update gsp product success。when deleteICProductResult is false，mean update IC product fail，and deleteIcProductFailResultList will show the reason

 |
| 

deleteGspProductResult

 | 

Boolean

 | 

update gsp product result

 |
| 

deleteICProductResult

 | 

Boolean

 | 

update ic product result，if it is false，deleteIcProductFailResultList will show reason

 |
| 

deleteIcProductFailResultList

 | 

Object\[\]

 | 

update ic fail result list

 |
| 

productId

 | 

Number

 | 

ic product id

 |
| 

market

 | 

String

 | 

market

 |
| 

updateResult

 | 

Boolean

 | 

update ic product result

 |
| 

updateMsg

 | 

String

 | 

update fail message

 |
| 

success

 | 

Boolean

 | 

process result，If this is true, it doesn't mean that everything is processed successfully

 |
| 

error\_code

 | 

String

 | 

exists when success is false

 |
| 

error\_msg

 | 

String

 | 

exists when success is false

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

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/global/delete)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/product/global/delete

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/global/delete");
request.addApiParameter("type", "global/single");
request.addApiParameter("country", "SG/VN/PH/TH/MY");
request.addApiParameter("product_id", "1234  \t");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "Invalid Limit",
  "code": "0",
  "data": {
    "deleteICProductResult": "false",
    "deleteIcProductFailResultList": [
      {
        "market": "LAZADA_MY",
        "productId": "3042450256",
        "updateMsg": "Product is not found in repository,scenario:UpShelf,productId:3042450256,serverIP:33.65.56.178",
        "updateResult": "false"
      }
    ],
    "deleteGspProductResult": "true"
  },
  "success": "true",
  "error_code": "E0019",
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
