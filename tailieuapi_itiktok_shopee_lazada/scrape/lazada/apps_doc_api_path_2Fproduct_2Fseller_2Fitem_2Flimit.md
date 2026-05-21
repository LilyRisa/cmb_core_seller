# GETGetSellerItemLimit

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fseller%2Fitem%2Flimit
> Scraped: 2026-05-20T22:51:05.280Z

---

Latest update2022-07-28 17:06:03

8007

GetSellerItemLimit

GET

/product/seller/item/limit

Authorization Required

Description:The platform will provide the product quantity limit information by this interface. The qps will be limited by seller, 10 qps per seller.

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

No Data

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

The result of this request,true or false.

 |
| 

errorCodes

 | 

String\[\]

 | 

If the request failed, errorCodes will be returned.

 |
| 

errorMsgs

 | 

String\[\]

 | 

The error msg, may be null even though the result is failed.

 |
| 

data

 | 

Object

 | 

The data

 |
| 

onlineItemCount

 | 

Number

 | 

The count of online item, oos included.

 |
| 

itemLimit

 | 

Number

 | 

The item limit. T + 2 refresh.

 |
| 

payItemCnt

 | 

Number

 | 

the number of selling item in last 90 days.T + 2 refresh.

 |
| 

payByrCnt

 | 

Number

 | 

the number of buyer in last 90 days.T + 2 refresh.

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

HOT\_KEY\_BLOCK\_EXCEPTION

 | 

hot key protect

 | 

10 qps promised for each seller

 |
| 

SELLER\_SERVICE\_FAIL

 | 

inner service fail

 | 

inner system error, please retry

 |
| 

ONLY\_CB\_SELLER\_SUPPORTED

 | 

For now, only cb seller supported

 | 

For local seller, we will support later.

 |
| 

THIRD\_SERVICE\_ERROR

 | 

inner service fail

 | 

inner system error, please retry

 |
| 

SYS\_ERROR

 | 

inner service fail

 | 

inner system error, please retry

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/seller/item/limit)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/product/seller/item/limit

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/seller/item/limit");
request.setHttpMethod("GET");
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
    "payByrCnt": "30",
    "payItemCnt": "20",
    "itemLimit": "500",
    "onlineItemCount": "100"
  },
  "success": "true",
  "errorCodes": [],
  "errorMsgs": [],
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
