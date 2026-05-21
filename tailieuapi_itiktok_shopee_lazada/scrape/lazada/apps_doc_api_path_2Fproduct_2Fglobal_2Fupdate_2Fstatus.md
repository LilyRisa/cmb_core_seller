# POSTupdateProductStatus

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fglobal%2Fupdate%2Fstatus
> Scraped: 2026-05-20T22:57:09.964Z

---

Latest update2024-03-29 10:46:19

2729

updateProductStatus

POST

/product/global/update/status

Authorization Required

Description:product up shelf or down shelf，(CrossBoarderSellersOnly)

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
| 

status

 | 

String

 | 

Yes

 | 

update product type

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

body，updateGspProductResult is true，mean update gsp product success。when updateICProductResult is false，mean update IC product fail，and updateIcProductFailResultList will show the reason

 |
| 

update\_gsp\_product\_result

 | 

Boolean

 | 

update gsp product result

 |
| 

update\_ic\_product\_result

 | 

Boolean

 | 

update ic product result，if it is false，deleteIcProductFailResultList will show reason

 |
| 

update\_ic\_product\_fail\_result\_list

 | 

Object\[\]

 | 

update ic fail result list

 |
| 

product\_id

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

update\_result

 | 

Boolean

 | 

update ic product result

 |
| 

update\_msg

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

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/global/update/status)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/product/global/update/status

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/global/update/status");
request.addApiParameter("type", "global/single");
request.addApiParameter("country", "SG/VN/PH/TH/MY");
request.addApiParameter("product_id", "1234  \t");
request.addApiParameter("status", "upShelf / downShelf");
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
    "update_ic_product_result": "false",
    "update_gsp_product_result": "true",
    "update_ic_product_fail_result_list": [
      {
        "market": "LAZADA_MY",
        "product_id": "3042450256",
        "update_result": "false",
        "update_msg": "Product is not found in repository,scenario:UpShelf,productId:3042450256,serverIP:33.65.56.178  \t"
      }
    ]
  },
  "success": "true",
  "error_code": "E0019",
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
