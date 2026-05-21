# POSTRemoveSku

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fsku%2Fremove
> Scraped: 2026-05-20T22:52:23.853Z

---

Latest update2022-07-29 14:22:25

7160

RemoveSku

POST

/product/sku/remove

Authorization Required

Description:Use this API to delete SKUs and sales attributes of corresponding products.

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

String

 | 

Yes

 | 

1911687838 color\_family 1911687838-1627269303789-1

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

Invalid Request Format

 | 

The request parameter is not formatted correctly, check that you are using the correct format against the RemoveSKU section of the Custom sales attributes documentation.

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/sku/remove)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/product/sku/remove

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/sku/remove");
request.addApiParameter("payload", "<Request>     <Product>         <ItemId>4911096929</ItemId>         <variation>             <Variation1>                 <name>color family</name>             </Variation1>         </variation>         <Skus>             <Sku>                 <SkuId>20691153083</SkuId>             </Sku>             <Sku>                 <SkuId>20690462002</SkuId>             </Sku>         </Skus>     </Product> </Request>");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {},
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
