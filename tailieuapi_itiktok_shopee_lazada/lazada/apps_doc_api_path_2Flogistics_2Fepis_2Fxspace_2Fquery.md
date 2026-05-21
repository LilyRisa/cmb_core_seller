# GET/POSTEpisXspaceQuery

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fepis%2Fxspace%2Fquery
> API path: /logistics/epis/xspace/query
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:50:41.711Z

---

Latest update2025-01-15 14:15:43

1820

EpisXspaceQuery

GET/POST

/logistics/epis/xspace/query

No Authorization Required

Description:Query Xspace case

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
| caseIds | String\[\] | No | caseIds |
| trackingNumbers | String\[\] | No | trackingNumbers |
| createTimeFrom | String | No | createTimeFrom |
| createTimeTo | String | No | createTimeTo |
| pageSize | String | No | pageSize |
| pageNo | String | No | pageNo |
| sortBy | String | No | sortBy |
| sortOrder | String | No | sortOrder |
| statuses | String\[\] | No | statuses |
| platformName | String | No | platformName |
| externalSellerId | String | No | externalSellerId |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| retryable | Boolean | retryable |
| success | Boolean | success |
| traceId | String | traceId |
| errorCode | String | errorCode |
| errorMessage | String | errorMessage |
| data | Object | data |
| content | Object\[\] | content |
| id | String | id |
| caseId | String | caseId |
| caseTemplateId | String | caseTemplateId |
| categoryId | String | categoryId |
| merchantId | String | merchantId |
| subject | String | subject |
| description | String | description |
| contactName | String | contactName |
| sellerName | String | sellerName |
| sellerEmail | String | sellerEmail |
| sellerPhoneNo | String | sellerPhoneNo |
| buyerName | String | buyerName |
| buyerEmail | String | buyerEmail |
| trackingNumber | String | trackingNumber |
| orderId | String | orderId |
| attachments | String | attachments |
| status | String | status |
| attributes | String | attributes |
| gmtCreate | String | gmtCreate |
| gmtModified | String | gmtModified |
| gmtDeleted | String | gmtDeleted |
| ratingStar | Number | ratingStar |
| ratingReasons | String\[\] | ratingReasons |
| ratingRemark | String | ratingRemark |
| page | Object | page |
| pageNo | String | pageNo |
| pageSize | String | pageSize |
| totalRecords | String | totalRecords |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/epis/xspace/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/logistics/epis/xspace/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/epis/xspace/query");
request.addApiParameter("caseIds", "[]");
request.addApiParameter("trackingNumbers", "[]");
request.addApiParameter("createTimeFrom", "1718606165000");
request.addApiParameter("createTimeTo", "1718606165000");
request.addApiParameter("pageSize", "10");
request.addApiParameter("pageNo", "1");
request.addApiParameter("sortBy", "createdTime");
request.addApiParameter("sortOrder", "DESC");
request.addApiParameter("statuses", "[\"New\"]");
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
    "page": {
      "totalRecords": "15",
      "pageNo": "1",
      "pageSize": "10"
    },
    "content": [
      {
        "gmtModified": "1719289602000",
        "attachments": "attachments",
        "orderId": "orderId",
        "subject": "subject",
        "contactName": "contactName",
        "buyerEmail": "buyerEmail@gmail.com",
        "sellerName": "sellerName",
        "description": "description",
        "buyerName": "buyerName",
        "gmtCreate": "1719289602000",
        "sellerEmail": "sellerEmail@gmail.com",
        "ratingStar": "4",
        "gmtDeleted": "1719289602000",
        "ratingRemark": "remark",
        "merchantId": "merchantId",
        "caseId": "caseId",
        "caseTemplateId": "caseTemplateId",
        "sellerPhoneNo": "+8412938213",
        "attributes": "attributes",
        "id": "id",
        "trackingNumber": "LZD1231231",
        "ratingReasons": [
          "reason 1",
          "reason 2"
        ],
        "categoryId": "categoryId",
        "status": "PENDING"
      }
    ]
  },
  "success": "true",
  "errorMessage": "errorMessage",
  "errorCode": "errorCode",
  "request_id": "0ba2887315178178017221014"
}
```
