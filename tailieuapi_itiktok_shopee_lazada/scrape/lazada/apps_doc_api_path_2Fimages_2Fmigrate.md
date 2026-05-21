# POSTMigrateImages

> Source: https://open.lazada.com/apps/doc/api?path=%2Fimages%2Fmigrate
> Scraped: 2026-05-20T22:51:41.703Z

---

Latest update2022-07-28 17:09:17

7842

MigrateImages

POST

/images/migrate

Authorization Required

Description:Use this API to migrate multiple images from an external site to Lazada site. Allowed image formats are JPG and PNG. The maximum size of an image file is 1MB. A single call can migrate 8 images at most.

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

Request body

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

batch\_id

 | 

String

 | 

The returned request ID is used by the GetResponse API to get the migrated image information.

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

5

 | 

E005: Invalid Request Format

 | 

The format of the request URL is not valid.

 |
| 

6

 | 

E006: Unexpected internal error

 | 

Unexpected internal error.

 |
| 

30

 | 

E030: Empty Request

 | 

The request is not complete.

 |
| 

301

 | 

E301: Migrate Image Failed

 | 

Failed to migrate the images.

 |
| 

302

 | 

E302: Not supported URL

 | 

The image URL is not supported.

 |
| 

303

 | 

E303: The image is too large

 | 

The size of the migrated image exceeds the 1M limit.

 |
| 

901

 | 

E901: The request is too frequent, or the requested functionality is temporarily disabled.

 | 

Failed to return the requested data due to high calling frequency or disabled functionality. Please try again later.

 |
| 

1000

 | 

Internal Application Error

 | 

Internal system error.

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/images/migrate)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/images/migrate

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/images/migrate");
request.addApiParameter("payload", "<?xml version=\"1.0\" encoding=\"UTF-8\" ?> <Request>     <Images>         <Url>http://pic4.nipic.com/20091217/3885730_124701000519_2.jpg</Url>         <Url>http://img.taopic.com/uploads/allimg/120727/201995-120HG1030762.jpg</Url>     </Images> </Request>");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "batch_id": "1e0bb81415173896232054839e",
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
