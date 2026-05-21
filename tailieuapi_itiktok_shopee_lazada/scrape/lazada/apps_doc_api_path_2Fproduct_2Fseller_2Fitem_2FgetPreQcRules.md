# GET/POSTGetPreQcRules

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fseller%2Fitem%2FgetPreQcRules
> Scraped: 2026-05-20T22:49:56.863Z

---

Latest update2022-09-10 15:10:38

4807

GetPreQcRules

GET/POST

/product/seller/item/getPreQcRules

Authorization Required

Description:query pre qc rules

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

option

 | 

Number

 | 

Yes

 | 

query qc option

 |
| 

option\_set

 | 

Number\[\]

 | 

Yes

 | 

query qc rules option.\[1\] return item limit, \[2\] return restricted category id, \[1,2\] return both

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

values

 | 

Object

 | 

response value

 |
| 

restricted\_cate\_ids

 | 

Number\[\]

 | 

restricted category id which can not publish

 |
| 

item\_limit

 | 

Number

 | 

item quantity limit

 |
| 

item\_count

 | 

Number

 | 

current item count

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

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/seller/item/getPreQcRules)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/product/seller/item/getPreQcRules

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/seller/item/getPreQcRules");
request.addApiParameter("option", "1");
request.addApiParameter("option_set", "[1]");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "values": {
    "item_limit": "1000000",
    "item_count": "191",
    "restricted_cate_ids": []
  },
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
