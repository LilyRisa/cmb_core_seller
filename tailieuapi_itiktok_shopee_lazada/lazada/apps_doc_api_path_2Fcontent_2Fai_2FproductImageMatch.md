# GET/POSTproductImageMatch

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcontent%2Fai%2FproductImageMatch
> API path: /content/ai/productImageMatch
> Category: Content API
> Scraped: 2026-05-21T00:19:26.371Z

---

Latest update2025-04-09 16:19:39

708

productImageMatch

GET/POST

/content/ai/productImageMatch

No Authorization Required

Description:match product image

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
| match\_num | Number | Yes | match num |
| image\_url | String | Yes | image url |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code when the operation fails |
| result\_message | String | error message when the operation fails |
| match\_image\_urls | String\[\] | match image urls |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/content/ai/productImageMatch)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/content/ai/productImageMatch

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/content/ai/productImageMatch");
request.addApiParameter("match_num", "1");
request.addApiParameter("image_url", "https://lzd-img-global.slatic.net/us/media/e35ea7da89a197fa2fc2432c59e13365-750-400.jpg");
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
    "match_image_urls": [],
    "result_code": "ok"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
