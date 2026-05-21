# GETGetQCAlertProducts

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fqc%2Falert%2Flist
> Scraped: 2026-05-20T22:50:38.788Z

---

Latest update2022-12-13 10:09:17

5400

GetQCAlertProducts

GET

/product/qc/alert/list

Authorization Required

Description:Getting seller's products that have been alerted by quality control.

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

offset

 | 

String

 | 

Yes

 | 

Number of QC alert products to skip

 |
| 

limit

 | 

String

 | 

Yes

 | 

The maximum number of QC alert products that can be returned.

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

Object\[\]

 | 

Response data list

 |
| 

productId

 | 

Number

 | 

product Id

 |
| 

categoryId

 | 

Number

 | 

product category id

 |
| 

deactivationTime

 | 

Number

 | 

Date to execute deactivation

 |
| 

suggestionCategories

 | 

Number\[\]

 | 

suggested categories

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

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/qc/alert/list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/product/qc/alert/list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/qc/alert/list");
request.setHttpMethod("GET");
request.addApiParameter("offset", "1");
request.addApiParameter("limit", "10");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": [
    {
      "productId": "0",
      "suggestionCategories": [
        0,
        1
      ],
      "categoryId": "0",
      "deactivationTime": "0"
    }
  ],
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
