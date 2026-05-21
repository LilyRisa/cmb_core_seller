# POSTEpisXspaceCreate

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fepis%2Fxspace%2Fcreate
> API path: /logistics/epis/xspace/create
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:50:18.062Z

---

Latest update2025-01-13 17:25:14

1816

EpisXspaceCreate

POST

/logistics/epis/xspace/create

No Authorization Required

Description:Create Xspace case

## Service Endpoints

| Region | Endpoint |
| --- | --- |
| Vietnam | https://api.lazada.vn/rest |
| Singapore | https://api.lazada.sg/rest |
| Philippines | https://api.lazada.com.ph/rest |
| Malaysia | https://api.lazada.com.my/rest |
| Thailand | https://api.lazada.co.th/rest |
| Indonesia | https://api.lazada.co.id/rest |
## Common Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| app\_key | String | Yes | Unique app ID issued by LAZADA Open Platform console when you apply for an app category |
| timestamp | String | Yes | The time stamp of the request e.g. 1517820392000 (which translates to 5 February 2018 08:46:32) with less than 7200s difference from UTC time |
| access\_token | String | No | API interface call credentials |
| sign\_method | String | Yes | The HMAC hash algorithm you are using to calculate your signature |
| sign | String | Yes | Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details) |
## Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| caseTemplateId | Number | No | case template id |
| categoryId | Number | No | cat id |
| subject | String | Yes | subject |
| description | String | Yes | description |
| sellerName | String | No | sellerName |
| sellerEmail | String | No | sellerEmail |
| sellerPhoneNo | String | No | sellerPhoneNo |
| buyerName | String | No | buyerName |
| buyerEmail | String | No | buyerEmail |
| trackingNumber | String | No | trackingNumber |
| orderId | String | No | orderId |
| casePriority | String | No | casePriority |
| attachments | String | No | attachments |
| attributes | String | No | attributes |
| platformName | String | No | platformName |
| externalSellerId | String | No | externalSellerId |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| retryable | Boolean | retryable |
| success | Boolean | success or not |
| traceId | String | traceId |
| errorMessage | String | errorMessage |
| errorCode | String | errorCode |
| data | Object | response data |
| caseId | Number | xspace case id |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/epis/xspace/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/epis/xspace/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/epis/xspace/create");
request.addApiParameter("caseTemplateId", "123302");
request.addApiParameter("categoryId", "11111");
request.addApiParameter("subject", "subject");
request.addApiParameter("description", "description");
request.addApiParameter("sellerName", "Bambooship");
request.addApiParameter("sellerEmail", "email@mail.com");
request.addApiParameter("sellerPhoneNo", "09887878998");
request.addApiParameter("buyerName", "buyerName");
request.addApiParameter("buyerEmail", "buyerEmail");
request.addApiParameter("trackingNumber", "trackingNumber");
request.addApiParameter("orderId", "orderId");
request.addApiParameter("casePriority", "Normal");
request.addApiParameter("attachments", "url1,url2");
request.addApiParameter("attributes", "attributes");
request.addApiParameter("platformName", "OneLink");
request.addApiParameter("externalSellerId", "109001");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "retryable": "false",
  "traceId": "traceId",
  "code": "0",
  "data": {
    "caseId": "2500000127480474"
  },
  "success": "true",
  "errorMessage": "errorMessage",
  "errorCode": "errorCode",
  "request_id": "0ba2887315178178017221014"
}
```
