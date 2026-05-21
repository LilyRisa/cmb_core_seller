# POSTBatchUpdateSizeChart

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsize%2Fchart%2Fbatch%2Fupdate
> Scraped: 2026-05-20T22:48:26.979Z

---

Latest update2023-11-15 14:05:34

4794

BatchUpdateSizeChart

POST

/size/chart/batch/update

Authorization Required

Description:批量更新尺码表

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

product size chart

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

4174

 | 

E4174

 | 

The size template corresponding to this product does not exist

 |
| 

4175

 | 

E4175

 | 

The size chart image url incorrect

 |
| 

4177

 | 

E4177

 | 

Empty Product Id or Size Chart

 |
| 

4178

 | 

E4178

 | 

Invalid size chart format, Size Chart format must image url or template id

 |
| 

4179

 | 

E4179

 | 

Cannot exceed the maximum size chart，maximum is 50

 |
| 

4180

 | 

E4180

 | 

The product category not support size chart

 |
| 

4181

 | 

E4181

 | 

Update size chart all failed

 |
| 

4182

 | 

E4182

 | 

only local seller and IntraAsean seller can set size chart

 |
| 

4183

 | 

E4183

 | 

Update size chart part failed

 |
| 

4185

 | 

E4185

 | 

The third-party ic service invocation is error

 |
| 

4187

 | 

E4187

 | 

The size chart value is invalid,Please input correct template id or url

 |
| 

4189

 | 

E4189

 | 

One product only can set one size chart

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/size/chart/batch/update)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/size/chart/batch/update

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/size/chart/batch/update");
request.addApiParameter("payload", "{\"Request\":{\"Product\":{\"SizeCharts\":{\"SizeChart\":[{\"product_id\":\"2871948231\",\"size_chart\":\"123\"},{\"product_id\":\"2894116588\",\"size_chart\":\"234\"}]}}}}");
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
