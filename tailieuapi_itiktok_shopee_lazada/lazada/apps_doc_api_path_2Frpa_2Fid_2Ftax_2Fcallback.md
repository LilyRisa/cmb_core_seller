# GET/POSTLazadaCFOInvoiceRpaCallback

> Source: https://open.lazada.com/apps/doc/api?path=%2Frpa%2Fid%2Ftax%2Fcallback
> API path: /rpa/id/tax/callback
> Category: LazPay API
> Scraped: 2026-05-20T23:56:20.530Z

---

Latest update2023-02-27 14:57:20

2153

LazadaCFOInvoiceRpaCallback

GET/POST

/rpa/id/tax/callback

No Authorization Required

Description:Call RPA and return the official invoice

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
| country | String | Yes | Country |
| batch\_id | String | Yes | Batch ID |
| status | String | Yes | status |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| is\_success | Boolean | true or false |
| res\_code | String | if success,it is null |
| content | String | if success,it is null |
| res\_msg | String | Error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/rpa/id/tax/callback)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/rpa/id/tax/callback

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/rpa/id/tax/callback");
request.addApiParameter("country", "ID");
request.addApiParameter("batch_id", "916a7f17-21d5-48d8-b952-d6d39662649e");
request.addApiParameter("status", "RPA_PROC_DONE");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "res_msg": "null",
  "code": "0",
  "is_success": "true",
  "request_id": "0ba2887315178178017221014",
  "res_code": "null",
  "content": "null"
}
```
