# GETGetSizeChartTemplate

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsize%2Fchart%2Ftemplate%2Fget
> Scraped: 2026-05-20T22:51:15.298Z

---

Latest update2023-11-15 14:07:40

3724

GetSizeChartTemplate

GET

/size/chart/template/get

Authorization Required

Description:获取尺码模板列表

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

template\_id

 | 

Number

 | 

No

 | 

size chart template id

 |
| 

template\_name

 | 

String

 | 

No

 | 

size chart name

 |
| 

page\_no

 | 

Number

 | 

Yes

 | 

page no

 |
| 

page\_size

 | 

Number

 | 

Yes

 | 

page size

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

total

 | 

Number

 | 

total

 |
| 

pageNo

 | 

Number

 | 

page no

 |
| 

pageSize

 | 

Number

 | 

page size

 |
| 

totalPage

 | 

Number

 | 

total page

 |
| 

sizeChartResponses

 | 

Object\[\]

 | 

\[{"sizeChartName":"test template","linkProductIds":null,"class":"com.taobao.sellglobal.service.api.response.SingleSizeChartResponse","sizeChartId":40010},{"sizeChartName":"sssss 1233","linkProductIds":null,"class":"com.taobao.sellglobal.service.api.response.SingleSizeChartResponse","sizeChartId":40007},{"sizeChartName":"test dup sizeGroup","linkProductIds":null,"class":"com.taobao.sellglobal.service.api.response.SingleSizeChartResponse","sizeChartId":38014},{"sizeChartName":"test publish sizechart template","linkProductIds":null,"class":"com.taobao.sellglobal.service.api.response.SingleSizeChartResponse","sizeChartId":38013},{"sizeChartName":"from publish template","linkProductIds":null,"class":"com.taobao.sellglobal.service.api.response.SingleSizeChartResponse","sizeChartId":40003},{"sizeChartName":"asasa","linkProductIds":null,"class":"com.taobao.sellglobal.service.api.response.SingleSizeChartResponse","sizeChartId":40002},{"sizeChartName":"test","linkProductIds":null,"class":"com.taobao.sellglobal.service.api.response.SingleSizeChartResponse","sizeChartId":42005},{"sizeChartName":"copy-test dress templatetest dress templatetest dress t","linkProductIds":null,"class":"com.taobao.sellglobal.service.api.response.SingleSizeChartResponse","sizeChartId":38010},{"sizeChartName":"test template 2","linkProductIds":null,"class":"com.taobao.sellglobal.service.api.response.SingleSizeChartResponse","sizeChartId":42001},{"sizeChartName":"winni test -1","linkProductIds":null,"class":"com.taobao.sellglobal.service.api.response.SingleSizeChartResponse","sizeChartId":36003}\]

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

4184

 | 

E4184

 | 

Size chart template id must be a number and greater than 0

 |
| 

4190

 | 

E4190

 | 

getSizeChartTemplate pageSize maximum value is 100

 |
| 

4176

 | 

E4176

 | 

Size chart list query fail

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/size/chart/template/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/size/chart/template/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/size/chart/template/get");
request.setHttpMethod("GET");
request.addApiParameter("template_id", "123");
request.addApiParameter("template_name", "test");
request.addApiParameter("page_no", "1");
request.addApiParameter("page_size", "20");
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
    "total": "50",
    "pageNo": "1",
    "totalPage": "2",
    "pageSize": "20",
    "sizeChartResponses": []
  },
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
