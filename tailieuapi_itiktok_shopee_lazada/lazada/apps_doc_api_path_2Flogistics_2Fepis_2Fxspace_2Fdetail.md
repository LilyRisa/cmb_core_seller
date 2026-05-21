# POSTEpisXspaceGetDetail

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fepis%2Fxspace%2Fdetail
> API path: /logistics/epis/xspace/detail
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:50:33.841Z

---

Latest update2025-01-15 14:15:36

1803

EpisXspaceGetDetail

POST

/logistics/epis/xspace/detail

No Authorization Required

Description:Get Xspace case detail

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
| caseId | Number | No | case id |
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
| gmtDeleted | Number | gmtDeleted |
| actions | Object\[\] | actions |
| mails | Object\[\] | mails |
| caseId | Number | xspace case id |
| caseTemplateId | Number | caseTemplateId |
| categoryId | Number | categoryId |
| ratingStar | Number | ratingStar |
| merchantId | Number | merchantId |
| ratingReasons | String\[\] | ratingReasons |
| subject | String | subject |
| ratingRemark | String | ratingRemark |
| description | String | description |
| contactName | String | contactName |
| sellerName | String | sellerName |
| sellerPhoneNo | String | sellerPhoneNo |
| buyerName | String | buyerName |
| buyerEmail | String | buyerEmail |
| trackingNumber | String | trackingNumber |
| orderId | String | orderId |
| attachments | String | attachments |
| status | String | status |
| attributes | String | attributes |
| gmtCreate | Number | gmtCreate |
| gmtModified | Number | gmtModified |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/epis/xspace/detail)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/epis/xspace/detail

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/epis/xspace/detail");
request.addApiParameter("caseId", "2500000152175706");
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
    "gmtModified": "gmtModified",
    "attachments": "attachments",
    "orderId": "orderId",
    "subject": "subject",
    "contactName": "contactName",
    "buyerEmail": "buyerEmail@gmail.com",
    "sellerName": "sellerName",
    "description": "description",
    "buyerName": "buyerName",
    "gmtCreate": "gmtCreate",
    "ratingStar": "4",
    "gmtDeleted": "gmtDeleted",
    "mails": [],
    "ratingRemark": "remark",
    "merchantId": "merchantId",
    "caseId": "2500000127480474",
    "caseTemplateId": "caseTemplateId",
    "sellerPhoneNo": "+84192163123",
    "attributes": "attributes",
    "actions": [],
    "ratingReasons": [
      "reason 1",
      "reason 2"
    ],
    "trackingNumber": "trackingNumber",
    "categoryId": "categoryId",
    "status": "status"
  },
  "success": "true",
  "errorMessage": "errorMessage",
  "errorCode": "errorCode",
  "request_id": "0ba2887315178178017221014"
}
```
