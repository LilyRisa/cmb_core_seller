# GET/POSTSellerCenterMsgList

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsellercenter%2Fmsg%2Flist
> Scraped: 2026-05-20T22:46:05.728Z

---

Latest update2024-05-07 15:27:44

2776

SellerCenterMsgList

GET/POST

/sellercenter/msg/list

Authorization Required

Description:seller center msg box

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

language

 | 

String

 | 

No

 | 

Set the language for returned messages.(en/vn/id/sg/ph...)

 |
| 

page

 | 

String

 | 

No

 | 

Paged query.

 |
| 

pageSize

 | 

String

 | 

No

 | 

Paged query, with a maximum return of one hundred records.

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

result

 |
| 

success

 | 

Object

 | 

success

 |
| 

type

 | 

String

 | 

type

 |
| 

errorCode

 | 

String

 | 

error code

 |
| 

error

 | 

String

 | 

error msg

 |
| 

data

 | 

Object

 | 

{}

 |
| 

dataSource

 | 

Object\[\]

 | 

\[\]

 |
| 

id

 | 

String

 | 

msg id

 |
| 

time

 | 

String

 | 

send time

 |
| 

message\_content

 | 

Object

 | 

message content

 |
| 

title

 | 

String

 | 

title

 |
| 

description

 | 

String

 | 

description

 |
| 

categoryName

 | 

String

 | 

msg category name

 |
| 

picture

 | 

String

 | 

msg img url

 |
| 

webLink

 | 

String

 | 

web jump link

 |
| 

appLink

 | 

String

 | 

app jump link

 |
| 

pageInfo

 | 

Object

 | 

pageInfo

 |
| 

current

 | 

Number

 | 

page

 |
| 

pageSize

 | 

Number

 | 

pageSize

 |
| 

total

 | 

Number

 | 

tatal

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

No Data

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sellercenter/msg/list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sellercenter/msg/list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sellercenter/msg/list");
request.addApiParameter("language", "en_EN");
request.addApiParameter("page", "1");
request.addApiParameter("pageSize", "10");
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
      "pageInfo": {
        "current": "1",
        "total": "20",
        "pageSize": "10"
      },
      "dataSource": [
        {
          "message_content": {
            "appLink": "app jump link",
            "webLink": "web jump link",
            "description": "description",
            "title": "title",
            "categoryName": "msg category name",
            "picture": "msg img url"
          },
          "id": "msg id",
          "time": "send time"
        }
      ]
    },
    "success": {},
    "errorCode": "code",
    "type": "type",
    "error": "error"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
