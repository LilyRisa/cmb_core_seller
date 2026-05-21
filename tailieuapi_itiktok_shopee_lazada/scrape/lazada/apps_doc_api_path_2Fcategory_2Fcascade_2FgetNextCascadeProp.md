# GET/POSTGetNextCascadeProp

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcategory%2Fcascade%2FgetNextCascadeProp
> Scraped: 2026-05-20T22:49:48.138Z

---

Latest update2022-12-20 10:30:36

4438

GetNextCascadeProp

GET/POST

/category/cascade/getNextCascadeProp

Authorization Required

Description:Use this API to query next cascade prop.

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

categoryId

 | 

Number

 | 

Yes

 | 

Category id

 |
| 

cascadeId

 | 

Number

 | 

Yes

 | 

Cascade id. Query from https://open.lazada.com/apps/doc/api?path=%2Fcategory%2Fattributes%2Fget

 |
| 

path

 | 

String

 | 

No

 | 

current cascade property path

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

Response body

 |
| 

prop

 | 

Object

 | 

cascade property

 |
| 

id

 | 

Number

 | 

property id

 |
| 

name

 | 

String

 | 

property name

 |
| 

required

 | 

Boolean

 | 

Whether the attribute is mandatory

 |
| 

propValue

 | 

Object\[\]

 | 

cascade property value

 |
| 

id

 | 

Number

 | 

property value id

 |
| 

name

 | 

String

 | 

property value name

 |
| 

leaf

 | 

String

 | 

whether is leaf node

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

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/category/cascade/getNextCascadeProp)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/category/cascade/getNextCascadeProp

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/category/cascade/getNextCascadeProp");
request.addApiParameter("categoryId", "62094240");
request.addApiParameter("cascadeId", "26");
request.addApiParameter("path", "120013644:162,100006867:160387;120013642:163,100006864:164");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {
    "prop": {
      "name": "Car Brand",
      "id": "120013644",
      "required": "false"
    },
    "propValue": [
      {
        "name": "Ariel",
        "id": "20100",
        "leaf": "false"
      }
    ]
  },
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
