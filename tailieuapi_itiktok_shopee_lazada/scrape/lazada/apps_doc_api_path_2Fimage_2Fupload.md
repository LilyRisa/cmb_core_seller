# POSTUploadImage

> Source: https://open.lazada.com/apps/doc/api?path=%2Fimage%2Fupload
> Scraped: 2026-05-20T22:53:26.750Z

---

Latest update2022-07-29 12:35:51

12123

UploadImage

POST

/image/upload

Authorization Required

Description:Use this API to upload a single image file to Lazada site. Allowed image formats are JPG and PNG. The maximum size of an image file is 1MB.

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

image

 | 

byte\[\]

 | 

Yes

 | 

Upload an image file

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

The URL address of the uploaded image.

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

30

 | 

E030: Empty Request

 | 

The request is not complete.

 |
| 

300

 | 

E300: Upload Image Failed

 | 

Failed to upload the image.

 |
| 

303

 | 

E303: The image is too large

 | 

The size of the uploaded image exceeds the 1M limit.

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

The image field should be passed in as a stream rather than a string, check that you are not passing in data that is not of a stream type，or if the image matches the requirements of this document.or if the image matches the requirements of this document.

 |
| 

302

 | 

Not supported URL

 | 

The image field should be passed in as a stream rather than a string, check that you are not passing in data that is not of a stream type，or if the image matches the requirements of this document.or if the image matches the requirements of this document.

 |
| 

302

 | 

Not supported URL

 | 

The image field should be passed in as a stream rather than a string, check that you are not passing in data that is not of a stream type，or if the image matches the requirements of this document.or if the image matches the requirements of this document.

 |
| 

302

 | 

Not supported URL

 | 

The image field should be passed in as a stream rather than a string, check that you are not passing in data that is not of a stream type，or if the image matches the requirements of this document.or if the image matches the requirements of this document.

 |
| 

302

 | 

Not supported URL

 | 

The image field should be passed in as a stream rather than a string, check that you are not passing in data that is not of a stream type，or if the image matches the requirements of this document.or if the image matches the requirements of this document.

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

The image field should be passed in as a stream rather than a string, check that you are not passing in data that is not of a stream type，or if the image matches the requirements of this document.or if the image matches the requirements of this document.

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

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/image/upload)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/image/upload

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/image/upload");
request.addFileParameter("image",new FileItem("/Users/D ocuments/book.jpg"));
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
      "hash_code": "61bdf049525b7d4c2cf79257ec7c2c56",
      "url": "http://my-live-01.slatic.net/p/orange_yellow.jpg"
    }
  },
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
