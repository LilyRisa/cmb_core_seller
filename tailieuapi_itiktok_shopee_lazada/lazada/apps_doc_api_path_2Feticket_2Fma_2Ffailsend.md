# POSTGlobalEticketMerchantMaFailsend

> Source: https://open.lazada.com/apps/doc/api?path=%2Feticket%2Fma%2Ffailsend
> API path: /eticket/ma/failsend
> Category: E-Tickets API
> Scraped: 2026-05-20T23:52:42.503Z

---

Latest update2026-05-21 07:52:35

500

GlobalEticketMerchantMaFailsend

POST

/eticket/ma/failsend

Authorization Required

Description:the callback interface when send code failed

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
| biz\_type | Number | Yes | biz type |
| sub\_code | String | Yes | fail reason code |
| outer\_id | String | Yes | order id |
| sub\_msg | String | Yes | fail reason desc |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| resp\_body | Object | response body |
| ret\_code | String | result code |
| ret\_msg | String | result info |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/eticket/ma/failsend)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/eticket/ma/failsend

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/eticket/ma/failsend");
request.addApiParameter("biz_type", "3001");
request.addApiParameter("sub_code", "isv.fail-send-no-stock");
request.addApiParameter("outer_id", "193962300049720");
request.addApiParameter("sub_msg", "inventory not enough");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "resp_body": {},
  "ret_msg": "success",
  "ret_code": "isv.success-all",
  "request_id": "0ba2887315178178017221014"
}
```
