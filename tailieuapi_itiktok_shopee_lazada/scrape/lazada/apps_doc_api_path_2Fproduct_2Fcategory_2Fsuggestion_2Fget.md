# GETGetCategorySuggestion

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fcategory%2Fsuggestion%2Fget
> Scraped: 2026-05-20T22:49:27.193Z

---

Latest update2022-07-28 17:02:37

13886

GetCategorySuggestion

GET

/product/category/suggestion/get

Authorization Required

Description:Get product's category suggestion by product title

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

product\_name

 | 

String

 | 

Yes

 | 

Product Name

 |
| 

image\_url

 | 

String

 | 

Yes

 | 

image url

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

data

 |
| 

categorySuggestions

 | 

Object\[\]

 | 

Category Beans

 |
| 

categoryPath

 | 

String

 | 

categoryPath

 |
| 

categoryId

 | 

Number

 | 

categoryId

 |
| 

categoryName

 | 

String

 | 

categoryName

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

701

 | 

E701: Empty category suggestion.

 | 

Empty category suggestion.

 |
| 

1000

 | 

Internal Application Error

 | 

Internal Application Error.

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

701

 | 

Empty category suggestion.

 | 

The API is unable to provide suggestions, please change the product name and retry.

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/category/suggestion/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/product/category/suggestion/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/category/suggestion/get");
request.setHttpMethod("GET");
request.addApiParameter("product_name", "Man T-Shirt Summer");
request.addApiParameter("image_url", "https://laz-img-cdn.alicdn.com/images/ims-web/TB19SB7aMFY.1VjSZFnXXcFHXXa.png");
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
    "categorySuggestions": [
      {
        "categoryPath": "categoryPath",
        "categoryName": "T-Shirt",
        "categoryId": "2342"
      }
    ]
  },
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
