# POSTUpdateGlobalProductAttribute

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fglobal%2Fattribute%2Fupdate
> Scraped: 2026-05-20T22:56:43.637Z

---

Latest update2022-07-29 14:54:09

3793

UpdateGlobalProductAttribute

POST

/product/global/attribute/update

Authorization Required

Description:update global product attribute (For cross boarder sellers only)

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

the content want to update

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

success

 | 

Boolean

 | 

success or fail

 |
| 

error\_detail

 | 

String

 | 

error detail

 |
| 

error\_code

 | 

String

 | 

error code

 |
| 

errors

 | 

String

 | 

all errors

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

501

 | 

E501: Update product failed

 | 

Update product failed

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/global/attribute/update)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/product/global/attribute/update

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/global/attribute/update");
request.addApiParameter("payload", "<?xml version=\"1.0\" encoding=\"utf-8\"?> <Request>   <Product>     <item_id>624525348185548</item_id>     <AssociatedSku>wensong_test_cb_hk</AssociatedSku>     <PrimaryCategory>4339</PrimaryCategory>     <Attributes>       <pants_length>Full Length,Cropped</pants_length>       <fa_pattern>Plaid</fa_pattern>     </Attributes>   </Product> </Request>");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "E501: Update product failed",
  "code": "0",
  "success": "true",
  "error_detail": "{\\\"success\\\":false,\\\"error\\\":{\\\"global_error\\\":[\\\"global seller not exist. my sellerId:1000567751\\\"]}}",
  "error_code": "501",
  "request_id": "0ba2887315178178017221014",
  "errors": "{\\\"success\\\":false,\\\"error\\\":{\\\"global_error\\\":[\\\"global seller not exist. my sellerId:1000567751\\\"]}}"
}
```

Please rate this article

Popular Articles
