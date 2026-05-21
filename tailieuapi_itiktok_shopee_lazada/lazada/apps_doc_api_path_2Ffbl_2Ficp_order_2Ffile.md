# GETGetIcpOrderFile

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Ficp_order%2Ffile
> API path: /fbl/icp_order/file
> Category: FBL API
> Scraped: 2026-05-20T23:39:15.650Z

---

Latest update2022-07-29 17:39:31

2405

GetIcpOrderFile

GET

/fbl/icp\_order/file

Authorization Required

Description:Get Inbound/Outbound order print PDF file

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
| order\_number | String | Yes | Inbound/Outbound order number |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| error\_code | String | Error code. |
| error\_message | String | Error message. |
| data | Object | File data |
| url | String | Pdf file download url |
| success | Boolean | Success or not. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/icp_order/file)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/icp\_order/file

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/icp_order/file");
request.setHttpMethod("GET");
request.addApiParameter("order_number", "OO02200XXX");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "Cancel inbound failed!",
  "code": "0",
  "data": {
    "url": "http://test.misc.oss-ap-southeast-1.aliyuncs.com/testFile"
  },
  "success": "true",
  "error_code": "ERROR_SYSTEM",
  "request_id": "0ba2887315178178017221014"
}
```
