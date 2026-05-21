# GETGetSellerMetricsById

> Source: https://open.lazada.com/apps/doc/api?path=%2Fseller%2Fmetrics%2Fget
> Scraped: 2026-05-20T22:45:26.132Z

---

Latest update2022-07-29 12:00:37

10444

GetSellerMetricsById

GET

/seller/metrics/get

Authorization Required

Description:Provide seller metrics data of the specific seller, like positive seller rating, ship on time rate and etc.

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

data

 | 

Object

 | 

response data

 |
| 

main\_category\_name

 | 

String

 | 

main\_category\_name

 |
| 

seller\_id

 | 

Number

 | 

seller\_id

 |
| 

response\_rate

 | 

String

 | 

response\_rate

 |
| 

response\_time

 | 

String

 | 

response\_time

 |
| 

ship\_on\_time

 | 

String

 | 

ship\_on\_time

 |
| 

main\_category\_id

 | 

Number

 | 

main\_category\_id

 |
| 

positive\_seller\_rating

 | 

String

 | 

positive\_seller\_rating

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

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/seller/metrics/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/seller/metrics/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/seller/metrics/get");
request.setHttpMethod("GET");
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
    "main_category_name": "Furniture \u0026 Décor",
    "ship_on_time": "93.62",
    "positive_seller_rating": "67.0",
    "response_time": "10.3333",
    "seller_id": "1000038888",
    "response_rate": "1.0000",
    "main_category_id": "10000336"
  },
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
