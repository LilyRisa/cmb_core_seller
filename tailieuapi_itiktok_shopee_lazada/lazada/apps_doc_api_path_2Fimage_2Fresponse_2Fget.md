# GETGetResponse

> Source: https://open.lazada.com/apps/doc/api?path=%2Fimage%2Fresponse%2Fget
> API path: /image/response/get
> Category: Product API
> Scraped: 2026-05-20T23:09:30.453Z

---

Latest update2022-07-28 17:06:03

9085

GetResponse

GET

/image/response/get

Authorization Required

Description:Use this API to get the returned information from the system for the MigrateImages API.

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
| access\_token | String | Yes | API interface call credentials |
| sign\_method | String | Yes | The HMAC hash algorithm you are using to calculate your signature |
| sign | String | Yes | Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details) |
## Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| batch\_id | String | Yes | Request ID from the MigrateImages request |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Response body |
| images | Object\[\] | Image information |
| url | String | The URL address of the migrated images. |
| hash\_code | String | The hash code of the images. |
| errors | Object\[\] | Image error information |
| field | String | The error url |
| msg | String | The error message |
| original\_url | String | The original url |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 5 | E005: Invalid Request Format | The format of the request URL is not valid. |
| 6 | E006: Unexpected internal error | Unexpected internal error. |
| 302 | Not supported URL | The server is unable to download the image from the link, please check that the image link you added to the MigrateImages API responds with an HTTP status code of 200 and that your image meets the requirements of this document |
| 301 | Migrate Image Failed | The server is unable to download the image from the link, please check that the image link you added to the MigrateImages API responds with an HTTP status code of 200 and that your image meets the requirements of this document |
| 1000 | Internal Application Error | Please check that you are uploading a JPG or PGN image that meets the requirements, and if you are sure that there is nothing wrong with the image but encounter this error frequently, please create a ticket to inquire about it. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/image/response/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/image/response/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/image/response/get");
request.setHttpMethod("GET");
request.addApiParameter("batch_id", "1e0bb81415173896232054839e");
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
    "images": [
      {
        "hash_code": "1f2ac2d7af810c498f88b2fc18686850",
        "url": "https://sg-staging.slatic.net/original/1f2ac2d7af810c498f88b2fc18686850.jpg"
      }
    ],
    "errors": [
      {
        "msg": "Error download image",
        "field": "http://static.somecdn.externalSite.com/img4.jpeg",
        "original_url": "http://pic4.nipic.com/20091217/3885730_124701000519_2.jpg"
      }
    ]
  },
  "request_id": "0ba2887315178178017221014"
}
```
