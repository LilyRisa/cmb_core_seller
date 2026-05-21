# GET/POSTgetSubAddress

> Source: https://open.lazada.com/apps/doc/api?path=%2Fseller%2Fcb%2Fcountry%2Flocation%2Fget
> Scraped: 2026-05-20T22:47:11.617Z

---

Latest update2023-04-07 17:16:21

3424

getSubAddress

GET/POST

/seller/cb/country/location/get

No Authorization Required

Description:get location info

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

location\_id

 | 

String

 | 

Yes

 | 

\*

 |
| 

level

 | 

Number

 | 

Yes

 | 

\*

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

\*

 |
| 

label

 | 

String

 | 

country label

 |
| 

value

 | 

String

 | 

country value

 |
| 

success

 | 

Boolean

 | 

if success

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

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/seller/cb/country/location/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/seller/cb/country/location/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/seller/cb/country/location/get");
request.addApiParameter("location_id", "CN");
request.addApiParameter("level", "1");
LazopResponse response = client.execute(request);
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
      "label": "Japan",
      "value": "JP"
    }
  ],
  "success": "false",
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
