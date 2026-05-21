# GET/POSTqueryContentReviewRecords

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcontent%2Fmcn%2Fcontent%2FqueryReviewRecords
> API path: /content/mcn/content/queryReviewRecords
> Category: LazLike API
> Scraped: 2026-05-21T00:14:18.729Z

---

Latest update2026-05-21 08:14:10

500

queryContentReviewRecords

GET/POST

/content/mcn/content/queryReviewRecords

No Authorization Required

Description:Query content audit records. Currently, querying records with audit results of low (block) is supported.The number of query contents is limited to 500 (adjustable).

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
| contentIds | String | Yes | 内容id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| success | Boolean | success |
| resultCode | String | resultCode |
| resultMessage | String | resultMessage |
| reviewRecords | Object\[\] | reviewRecords |
| reviewedType | String | reviewedType |
| reason | String | reason |
| reviewedTime | Number | reviewedTime |
| contentId | Number | contentId |
| currentContentBaseState | Number | currentContentBaseState |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/content/mcn/content/queryReviewRecords)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/content/mcn/content/queryReviewRecords

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/content/mcn/content/queryReviewRecords");
request.addApiParameter("contentIds", "59519659,59519679");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "success": "true",
    "resultCode": "\"\"",
    "resultMessage": "\"\"",
    "reviewRecords": [
      {
        "reviewedType": "AUDIT_FAILED",
        "reason": "impolite behaviour",
        "reviewedTime": "1747880084145",
        "contentId": "64570814",
        "currentContentBaseState": "2"
      }
    ]
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
