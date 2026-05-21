# POSTSemiProductUpdate

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fglobal%2Fsemi%2Fupdate
> Scraped: 2026-05-20T22:54:43.254Z

---

Latest update2024-03-11 10:13:31

3177

SemiProductUpdate

POST

/product/global/semi/update

Authorization Required

Description:SemiProductUpdate

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

request data

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

product\_id

 | 

Number

 | 

upgrade item id or global item id

 |
| 

success

 | 

Boolean

 | 

success or fail

 |
| 

error\_code

 | 

String

 | 

error code

 |
| 

error\_msg

 | 

String

 | 

error msg

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

10001

 | 

Illegal parameters

 | 

参数不合法

 |
| 

10002

 | 

System error

 | 

系统异常

 |
| 

10003

 | 

Item not found

 | 

商品未找到

 |
| 

10004

 | 

price need to be lower than the original price

 | 

价格需低于零售价

 |
| 

10005

 | 

商品已升级

 | 

商品已升级

 |
| 

10006

 | 

商品校验失败，无法升级

 | 

商品校验失败，无法升级

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/global/semi/update)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/product/global/semi/update

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/global/semi/update");
request.addApiParameter("payload", "{\"global_item_id\":null,\"item_id\":4003109638,\"country\":[\"my\"],\"skus\":[{\"item_id\":4003109638,\"seller_sku\":\"ly-testSKU-0-1709174493544\",\"sku_id\":22803519824,\"package_height\":\"1\",\"package_length\":\"1\",\"package_width\":\"1\",\"package_weight\":\"0.1\",\"country_info\":[{\"market\":\"LAZADA_MY\",\"quantity\":null,\"no_postage_price\":\"8\",\"price\":\"10\",\"currency\":\"MYR\"}]}]}");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "null",
  "code": "0",
  "data": {
    "product_id": "180226526"
  },
  "success": "true",
  "error_code": "null",
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
