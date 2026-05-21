# POSTcancelTask

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcontent%2Fai%2FcancelTask
> API path: /content/ai/cancelTask
> Category: Content API
> Scraped: 2026-05-21T00:18:34.913Z

---

Latest update2026-05-21 08:18:26

500

cancelTask

POST

/content/ai/cancelTask

No Authorization Required

Description:cancel tasks

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
| task\_ids | String\[\] | Yes | task\_ids |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code when the operation fails |
| result\_message | String | error message when the operation fails |
| canceled\_task\_count | Number | canceled task count |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/content/ai/cancelTask)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/content/ai/cancelTask

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/content/ai/cancelTask");
request.addApiParameter("task_ids", "1234,5678");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "result_message": "ok",
    "success": "true",
    "canceled_task_count": "5",
    "result_code": "ok"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
