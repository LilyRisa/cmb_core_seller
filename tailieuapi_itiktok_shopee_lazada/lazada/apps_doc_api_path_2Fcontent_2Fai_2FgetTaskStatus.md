# GETgetTaskStatus

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcontent%2Fai%2FgetTaskStatus
> API path: /content/ai/getTaskStatus
> Category: Content API
> Scraped: 2026-05-21T00:19:18.881Z

---

Latest update2025-04-09 16:24:41

799

getTaskStatus

GET

/content/ai/getTaskStatus

No Authorization Required

Description:get task status

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
| task\_id | String | Yes | taskId |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| data | Object | data |
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code when the operation fails |
| result\_message | String | error message when the operation fails |
| fail\_message | String | fail message |
| status | String | task status |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/content/ai/getTaskStatus)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/content/ai/getTaskStatus

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/content/ai/getTaskStatus");
request.setHttpMethod("GET");
request.addApiParameter("task_id", "1234");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "data": {},
    "result_message": "ok",
    "success": "true",
    "result_code": "ok",
    "fail_message": "ok",
    "status": "waiting"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
