# GET/POSTSynchronizeSellerItemArConfig

> Source: https://open.lazada.com/apps/doc/api?path=%2Fseller%2Far%2Fconfig%2Fsyn
> API path: /seller/ar/config/syn
> Category: Seller API
> Scraped: 2026-05-20T23:04:40.164Z

---

Latest update2022-07-28 17:14:55

4916

SynchronizeSellerItemArConfig

GET/POST

/seller/ar/config/syn

No Authorization Required

Description:synchronize seller item ar config

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
| siteId | String | Yes | site Id |
| source | String | Yes | ar config isv |
| uid | String | Yes | uid |
| contents | String | Yes | syn sku ar config info |
| synDate | String | Yes | synDate |
| business | String | No | business |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | success |
| errorCode | String | errorCode |
| model | Object | syn result |
| uid | String | uid |
| errorMsg | String | errorMsg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| IllegalAccessToken | The specified access token is invalid or expired | access token is invalid or expired |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/seller/ar/config/syn)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/seller/ar/config/syn

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/seller/ar/config/syn");
request.addApiParameter("siteId", "sg");
request.addApiParameter("source", "PERFECT");
request.addApiParameter("uid", "123456");
request.addApiParameter("contents", "[]");
request.addApiParameter("synDate", "synDate");
request.addApiParameter("business", "LAZADA\u3001ARISE");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "success": "success",
  "errorCode": "errorCode",
  "model": {
    "uid": "uid"
  },
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "errorMsg"
}
```
