# GET/POSTCancelVasOrder4FBL

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Fvas%2FcancelVasOrder
> API path: /fbl/vas/cancelVasOrder
> Category: FBL API
> Scraped: 2026-05-20T23:35:22.487Z

---

Latest update2026-01-09 11:48:05

664

CancelVasOrder4FBL

GET/POST

/fbl/vas/cancelVasOrder

Authorization Required

Description:取消增值服务

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
| platform\_name | String | Yes | laz店铺所属的前台租户,例如: LAZADA\_VN |
| vas\_order\_no | String | Yes | 增值服务单号 |
| cancel\_reason | String | No | 取消原因 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | String | 取消结果 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/vas/cancelVasOrder)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/fbl/vas/cancelVasOrder

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/vas/cancelVasOrder");
request.addApiParameter("platform_name", "LAZADA_VN");
request.addApiParameter("vas_order_no", "VAS123456");
request.addApiParameter("cancel_reason", "cancelReason");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": "{\"data\":{\"retryable\":false,\"fail\":false,\"success\":true,\"succAndNotNull\":false,\"message\":\"OK\"},\"code\":\"0\",\"request_id\":\"2101773f17679306722446960\",\"_trace_id_\":\"2108037917679306720795016e1eaa\"}",
  "request_id": "0ba2887315178178017221014"
}
```
