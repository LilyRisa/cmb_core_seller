# POSTupdateLastMile

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fldp%2FupdateLastmile
> API path: /logistics/ldp/updateLastmile
> Category: Logistics API
> Scraped: 2026-05-20T23:29:45.443Z

---

Latest update2024-11-04 15:55:25

2107

updateLastMile

POST

/logistics/ldp/updateLastmile

No Authorization Required

Description:跨境场景，物流末端预报信息

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
| unitCode | String | Yes | unitCode |
| shippingProviderCode | String | Yes | shippingProviderCode |
| trackingNumber | String | Yes | trackingNumber |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | is success |
| data | String | data |
| errorCode | String | errorCode |
| errorMsg | String | errorMsg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/ldp/updateLastmile)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/ldp/updateLastmile

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/ldp/updateLastmile");
request.addApiParameter("unitCode", "FU20202020001");
request.addApiParameter("shippingProviderCode", "057_***_****");
request.addApiParameter("trackingNumber", "TN_0001");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": "*****",
  "success": "false",
  "errorCode": "lnp_ldm-fcp#illegalPkgCode-E",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "package code is not LDM order!"
}
```
