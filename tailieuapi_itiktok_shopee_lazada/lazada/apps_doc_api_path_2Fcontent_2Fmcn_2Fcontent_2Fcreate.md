# POSTMcnContentCreate

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcontent%2Fmcn%2Fcontent%2Fcreate
> API path: /content/mcn/content/create
> Category: LazLike API
> Scraped: 2026-05-21T00:12:15.090Z

---

Latest update2026-05-21 08:12:02

500

McnContentCreate

POST

/content/mcn/content/create

No Authorization Required

Description:create content

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
| kolUserId | Number | No | buyer account of kol |
| contentType | String | Yes | should be 'video' for video content |
| description | String | Yes | text part |
| imageList | String | No | image urls splitted by comma |
| itemList | String | No | itemId list splitted by comma |
| videoId | Number | No | return by calling McnContentCompleteCreateVideo |
| categoryId | Number | No | category id |
| tags | String | No | contents brief tags |
| voiceLang | String | Yes | language of voice |
| subtitleLang | String | Yes | language of subtitle |
| descriptionLang | String | No | language of description |
| publishTimeMillis | Number | No | Content release time, if it is to be released immediately, you can not pass it or pass 0. If you want to publish it regularly, pass > a timestamp of the current time, milliseconds and must be an hour. |
| shopId | Number | No | shop account |
| proxyFlag | Boolean | No | proxy flag |
| title | String | No | title |
| extraTagIds | String | No | Additional tags that need to be added, such as fashion tags and sale tags |
| channel | String | No | mcn\_aigc or mcn\_content |
| bizType | String | No | LazMall or LazLive |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result of api |
| contentId | Number | content id |
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code provided when the operation fails |
| result\_message | String | error message provided when the operation fails |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/content/mcn/content/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/content/mcn/content/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/content/mcn/content/create");
request.addApiParameter("kolUserId", "123456");
request.addApiParameter("contentType", "video");
request.addApiParameter("description", "hello");
request.addApiParameter("imageList", "http://a.jpg,http://b.jpg");
request.addApiParameter("itemList", "111111,222222");
request.addApiParameter("videoId", "123456");
request.addApiParameter("categoryId", "9527");
request.addApiParameter("tags", "skirt,summer");
request.addApiParameter("voiceLang", "en");
request.addApiParameter("subtitleLang", "en");
request.addApiParameter("descriptionLang", "en");
request.addApiParameter("publishTimeMillis", "0");
request.addApiParameter("shopId", "123456");
request.addApiParameter("proxyFlag", "false");
request.addApiParameter("title", "this is title");
request.addApiParameter("extraTagIds", "1234");
request.addApiParameter("channel", "mcn_aigc");
request.addApiParameter("bizType", "LazMall");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "result_message": "success",
    "success": "true",
    "contentId": "12345678",
    "result_code": "OK"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
