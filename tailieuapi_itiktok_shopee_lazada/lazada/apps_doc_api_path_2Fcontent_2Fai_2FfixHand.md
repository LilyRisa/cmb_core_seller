# POSTfixHand

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcontent%2Fai%2FfixHand
> API path: /content/ai/fixHand
> Category: Content API
> Scraped: 2026-05-21T00:19:07.331Z

---

Latest update2025-04-09 16:11:33

681

fixHand

POST

/content/ai/fixHand

No Authorization Required

Description:fixHand using lazada AI algorithm

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
| raw\_image\_url | String | Yes | raw\_image\_url |
| batch\_size | Number | No | batch size |
| base\_ref | Boolean | No | base\_ref |
| model\_reference\_image\_url | String | Yes | model\_reference\_image\_url |
| ratio | String | No | ratio |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code when the operation fails |
| result\_message | String | error message when the operation fails |
| task\_id | String | task\_id |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/content/ai/fixHand)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/content/ai/fixHand

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/content/ai/fixHand");
request.addApiParameter("raw_image_url", "https://lzd-img-global.slatic.net/us/media/e35ea7da89a197fa2fc2432c59e13365-750-400.jpg");
request.addApiParameter("batch_size", "3");
request.addApiParameter("base_ref", "false");
request.addApiParameter("model_reference_image_url", "https://lzd-img-global.slatic.net/us/media/e35ea7da89a197fa2fc2432c59e13365-750-400.jpg");
request.addApiParameter("ratio", "1:1");
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
    "result_code": "ok",
    "task_id": "1234"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
