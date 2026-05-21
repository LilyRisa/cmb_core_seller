# POSTPackageJitPurchaseOrder

> Source: https://open.lazada.com/apps/doc/api?path=%2Fjit%2Fpurchase_order%2Fpackage
> API path: /jit/purchase_order/package
> Category: Choice Customized API
> Scraped: 2026-05-21T00:10:24.125Z

---

Latest update2024-01-24 17:58:07

1718

PackageJitPurchaseOrder

POST

/jit/purchase\_order/package

Authorization Required

Description:Package Jit Purchase Order.

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
| purchase\_order\_no\_list | String\[\] | Yes | 采购单列表，最大100个。{\["POJ1001","POJ1002"\]} |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| data | Object | data |
| status | String | success |
| success | Boolean | is success |
| error\_message | String | errror msg |
| error\_code | String | error code |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/jit/purchase_order/package)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/jit/purchase\_order/package

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/jit/purchase_order/package");
request.addApiParameter("purchase_order_no_list", "[\"POJ1001\",\"POJ1002\"]");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "error_message": "null",
    "data": {
      "status": "success"
    },
    "success": "true",
    "error_code": "null"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
