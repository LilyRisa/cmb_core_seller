# GET/POSTMcnSimilarProductSearch

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcontent%2Fmcn%2Fsimilar%2Fproduct%2Fsearch
> API path: /content/mcn/similar/product/search
> Category: LazLike API
> Scraped: 2026-05-21T00:14:07.556Z

---

Latest update2026-05-21 08:13:54

500

McnSimilarProductSearch

GET/POST

/content/mcn/similar/product/search

No Authorization Required

Description:相似商品搜索接口

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
| kolUserId | Number | No | user id |
| imageUrlList | String | No | image url list |
| shopId | Number | No | shop id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| productList | Object\[\] | product info |
| confidentialityStatement | String | confidentiality statement |
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code provided when the operation fails |
| result\_message | String | error message provided when the operation fails |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/content/mcn/similar/product/search)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/content/mcn/similar/product/search

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/content/mcn/similar/product/search");
request.addApiParameter("kolUserId", "12345");
request.addApiParameter("imageUrlList", "https://123.jpg");
request.addApiParameter("shopId", "12345");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "result_message": "success",
  "success": "true",
  "confidentialityStatement": "By accessing this API, you agree to treat the data with confidential",
  "result_code": "OK",
  "request_id": "0ba2887315178178017221014",
  "productList": [
    {
      "productId": 12123,
      "imageUrl": "https://",
      "productLink": "https://",
      "mainPicture": "https://",
      "confidentialityStatement": "By accessing this API, you agree to treat the data with confidential",
      "skuId": 21213
    }
  ]
}
```
