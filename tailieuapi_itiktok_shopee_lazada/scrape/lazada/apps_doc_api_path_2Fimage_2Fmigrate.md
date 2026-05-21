# POSTMigrateImage

> Source: https://open.lazada.com/apps/doc/api?path=%2Fimage%2Fmigrate
> Scraped: 2026-05-20T22:51:32.460Z

---

Latest update2022-07-28 17:05:35

10109

MigrateImage

POST

/image/migrate

Authorization Required

Description:Use this API to migrate a single image from an external site to Lazada site. Allowed image formats are JPG and PNG. The maximum size of an image file is 1MB.

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

data

 | 

Object

 | 

Response body

 |
| 

image

 | 

Object

 | 

image info

 |
| 

url

 | 

String

 | 

The URL address of the migrated image.

 |
| 

hash\_code

 | 

String

 | 

The hash code of the image.

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

The request URL is not valid.

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

Failed to migrate the image.

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
| 

302

 | 

Not supported URL

 | 

The server could not download the image from the link, please check that your link responds with an HTTP status code of 200 and that your image meets the requirements of this document

 |
| 

302

 | 

Not supported URL

 | 

The server could not download the image from the link, please check that your link responds with an HTTP status code of 200 and that your image meets the requirements of this document

 |
| 

5

 | 

Invalid Request Format

 | 

Please check that the payload is documented and conforms to the XML formatting requirements. If you have URLs with “&” in them, then please use Cdata tags to avoid XML parsing problems.

 |
| 

304

 | 

Get Response Failed

 | 

Please check if the URL of the image you provided is externally accessible or if the HTTP status code of the response is 200.

 |
| 

303

 | 

The image is too large

 | 

Please make sure your image size is less than 5000\*5000px and file size is less than 3145728B.

 |
| 

302

 | 

Not supported URL

 | 

Please check if the http status code in response to the image link in the request is 200, and check if your image meets the requirements based on this document.

 |
| 

1000

 | 

Internal Application Error

 | 

Please check that you are uploading a JPG or PGN image that meets the requirements, and if you are sure that there is nothing wrong with the image but encounter this error frequently, please create a ticket to inquire about it.

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/image/migrate)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/image/migrate

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/image/migrate");
request.addApiParameter("payload", "<?xml version=\"1.0\" encoding=\"UTF-8\" ?> <Request>     <Image>         <Url>http://pic4.nipic.com/20091217/3885730_124701000519_2.jpg</Url>     </Image> </Request>");
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
    "image": {
      "hash_code": "1e8bb2499d38084ffe31f155c68e0d1f",
      "url": "https//sg.s.alibaba.lzd.co/original/1e8bb2499d38084ffe31f155c68e0d1f.jpg"
    }
  },
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
