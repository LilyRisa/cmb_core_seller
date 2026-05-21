# GET/POSTUploadWaybill

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Fwaybill%2Fupload
> API path: /fbl/waybill/upload
> Category: FBL API
> Scraped: 2026-05-20T23:44:35.696Z

---

Latest update2022-08-01 16:00:13

2602

UploadWaybill

GET/POST

/fbl/waybill/upload

Authorization Required

Description:Use this API to upload a waybill pdf to Lazada site. The maximum size of an pdf file is 1MB.

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
| waybill | byte\[\] | Yes | waybill pdf |
| package\_code | String | Yes | package code |
| tracking\_number | String | Yes | tracking number |
| extends\_field | String | No | extend fields |
| store\_code | String | Yes | warehouse\_code |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | whether success |
| error\_message | String | error message |
| error\_code | String | error code |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/waybill/upload)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/fbl/waybill/upload

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/waybill/upload");
request.addFileParameter("waybill",new FileItem("/Users/D ocuments/book.jpg"));
request.addApiParameter("package_code", "HU2005191006185");
request.addApiParameter("tracking_number", "LEXPU0017101924");
request.addApiParameter("extends_field", "none");
request.addApiParameter("store_code", "STORE_188564");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "WaybillUpladServiceImpl failed! lack the necessary Params errorMsg\u003d store code can\u0027t null",
  "code": "0",
  "success": "true/false",
  "error_code": "A0410",
  "request_id": "0ba2887315178178017221014"
}
```
