# GET/POSTGetProductContentScore

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fcontent%2Fscore%2Fget
> Scraped: 2026-05-20T22:50:06.823Z

---

Latest update2023-05-24 11:02:04

5587

GetProductContentScore

GET/POST

/product/content/score/get

Authorization Required

Description:get product content score

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

item\_id

 | 

Number

 | 

Yes

 | 

Call this API; "Item Id" must be selected as the request parameter.

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

result

 | 

Object

 | 

Result

 |
| 

data

 | 

Object

 | 

Response body

 |
| 

productTitle

 | 

String

 | 

Product title

 |
| 

score

 | 

Number

 | 

Product's latest content score

 |
| 

image

 | 

String

 | 

The main image of the selected product for a quick product information summary

 |
| 

total

 | 

Number

 | 

The total full content score

 |
| 

productId

 | 

Number

 | 

Product ID

 |
| 

items

 | 

Object\[\]

 | 

Issue list that need to be optimized of the selected product

 |
| 

key

 | 

String

 | 

Unique key to identify each issue

 |
| 

score

 | 

Number

 | 

The current score got in this issue

 |
| 

total

 | 

Number

 | 

The total score of this issue

 |
| 

group

 | 

String

 | 

Indicates the group of the issue, such as Content Completeness and Content Quality name The name of each issue to be displayed to sellers.

 |
| 

label

 | 

String

 | 

The extra high-level guidance and suggestion for this issue.

 |
| 

latest

 | 

Boolean

 | 

Optional field. Will only return for certain issues which need offline calculation. Value "false" means this issue has been optimized but still under system calculation and may update in 2 days.

 |
| 

indicators

 | 

Object\[\]

 | 

Optional field. Some extra details (usually for sub issues) and will only return for certain issues. Please check API response examples for more information.

 |
| 

critical

 | 

Boolean

 | 

To mark the critical issues which leads to 0 product score and represents potential content policy violations

 |
| 

text

 | 

String

 | 

Detailed name and description of the sub issue.

 |
| 

key

 | 

String

 | 

Unique key of the sub issue.

 |
| 

imageList

 | 

Object\[\]

 | 

Optional field. Only available for product with Image Quality issue. List the issue and image breakdown.

 |
| 

score

 | 

Number

 | 

The score of each image.

 |
| 

imageUrl

 | 

String

 | 

The url of the image with image quality issue

 |
| 

text

 | 

String

 | 

The image name, e.g product image/SKU image/long image/square image.

 |
| 

type

 | 

String

 | 

The score level of each image, e.g. low/moderate. This level is mapped to score.

 |
| 

imageType

 | 

Number

 | 

The enum of image type, e.g product image/SKU image/long image/square image.

 |
| 

indicators

 | 

Object\[\]

 | 

Optional field. Some extra details (usually for sub issues) and will only return for certain issues. Please check API response examples for more information.

 |
| 

text

 | 

String

 | 

Detailed name and description of the sub issue.

 |
| 

key

 | 

String

 | 

Unique key of the sub issue.

 |
| 

itemTitle

 | 

String

 | 

The name of each issue to be displayed to sellers.

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

901

 | 

Limit service request speed in server side temporarily.

 | 

API level QPS limiting flow, please retry in the next second when you encounter this error.

 |
| 

901

 | 

Limit service request speed in server side temporarily.

 | 

API level QPS limiting flow, please retry in the next second when you encounter this error.

 |
| 

901

 | 

Limit service request speed in server side temporarily.

 | 

API level QPS limiting flow, please retry in the next second when you encounter this error.

 |
| 

901

 | 

Limit service request speed in server side temporarily.

 | 

API level QPS limiting flow, please retry in the next second when you encounter this error.

 |
| 

901

 | 

Limit service request speed in server side temporarily.

 | 

API level QPS limiting flow, please retry in the next second when you encounter this error.

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/content/score/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/product/content/score/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/content/score/get");
request.addApiParameter("item_id", "692345699");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "data": {
      "productTitle": "test product",
      "score": "50",
      "image": "https://my-live-01.slatic.net/p/9ae4f50591def4f9b91f3fa069d566f6.jpg",
      "total": "110",
      "productId": "2581192583",
      "items": [
        {
          "score": "0",
          "total": "13",
          "itemTitle": "Image Quality",
          "label": "Fill attributes in key product information",
          "indicators": [
            {
              "critical": "false",
              "text": "Suggest title length between 20 and 150.",
              "key": "wordLength"
            }
          ],
          "imageList": [
            {
              "score": "1",
              "imageUrl": "https://my-live-01.slatic.net/p/9ae4f50591def4f9b91f3fa069d566f6.jpg",
              "text": "Main Image",
              "type": "low",
              "indicators": [
                {
                  "text": "Pure text image detected. Please upload another image.",
                  "key": "flagPuretxt"
                }
              ],
              "imageType": "1"
            }
          ],
          "key": "productIndicatorCatProperties",
          "group": "completeness",
          "latest": "true"
        }
      ]
    }
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
