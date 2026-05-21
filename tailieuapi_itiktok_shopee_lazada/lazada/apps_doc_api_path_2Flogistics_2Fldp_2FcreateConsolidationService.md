# POSTcreateConsolidationService

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fldp%2FcreateConsolidationService
> API path: /logistics/ldp/createConsolidationService
> Category: Logistics API
> Scraped: 2026-05-20T23:29:37.505Z

---

Latest update2024-11-04 17:21:19

1999

createConsolidationService

POST

/logistics/ldp/createConsolidationService

No Authorization Required

Description:create Consolidation Service

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
| unitCodes | String\[\] | Yes | unit codes |
| properties | Object | Yes | prop |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | String | data |
| success | Boolean | is success |
| errorCode | String | error code |
| errorMsg | String | error Msg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/ldp/createConsolidationService)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/ldp/createConsolidationService

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/ldp/createConsolidationService");
request.addApiParameter("unitCodes", "\"FU23900000000000007164132600\",   \"FU2386993\"");
request.addApiParameter("properties", "{\"sellerGroupName\": \"CN-Others\"}");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": "null",
  "success": "false",
  "errorCode": "lnp_ldm-fcp#biz-queryTargetFulfilUnitError-E",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "Query target fulfilUnit error"
}
```
