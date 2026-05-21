# UNKNOWNMY - Pickupp createManifest

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpublic%2Fintegrations%2Flazada
> API path: /public/integrations/lazada
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:51:36.839Z

---

Latest update2022-11-01 21:10:42

2112

MY - Pickupp createManifest

UNKNOWN

/public/integrations/lazada

Authorization Required

Description:Call pickupp create manifest

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
| manifestId | Number | Yes | manifest id |
| locationId | String | Yes | node id |
| subConId | Number | Yes | sub-con virtual courier id |
| fileUrl | String | Yes | link to download manifest json file |
| traceId | String | Yes | trace id to track request |
| authToken | String | Yes | Authorization header |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | data |
| resCode | Number | 200 |
| resMessage | String | message |
| success | Boolean | success or not |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/public/integrations/lazada)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

UNKNOWN

/public/integrations/lazada

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/public/integrations/lazada");
request.addApiParameter("manifestId", "106942011");
request.addApiParameter("locationId", "dfb73c93-0213-491f-9707-9c8ad8515cc2");
request.addApiParameter("subConId", "9330");
request.addApiParameter("fileUrl", "http://downloadable_file.com");
request.addApiParameter("traceId", "trace_id_should_be_here");
request.addApiParameter("authToken", "Basic ...");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {
    "success": "true",
    "resCode": "200",
    "resMessage": "message"
  },
  "request_id": "0ba2887315178178017221014"
}
```
