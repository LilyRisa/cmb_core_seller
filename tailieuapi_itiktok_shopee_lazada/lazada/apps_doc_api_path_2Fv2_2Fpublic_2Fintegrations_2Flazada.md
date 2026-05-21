# UNKNOWNMY - Pickupp addJobsToManifest

> Source: https://open.lazada.com/apps/doc/api?path=%2Fv2%2Fpublic%2Fintegrations%2Flazada
> API path: /v2/public/integrations/lazada
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:51:29.089Z

---

Latest update2022-11-01 20:53:15

2228

MY - Pickupp addJobsToManifest

UNKNOWN

/v2/public/integrations/lazada

No Authorization Required

Description:Add job to manifest request

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
| stops | Object\[\] | Yes | na |
| customerName | String | Yes | na |
| phone | String | Yes | na |
| address | String | Yes | na |
| type | String | Yes | na |
| jobs | Object\[\] | Yes | na |
| type | String | Yes | na |
| trackingNumber | String | Yes | na |
| widthInCm | String | Yes | na |
| lengthInCm | String | Yes | na |
| heightInCm | String | Yes | na |
| weightInGram | String | Yes | na |
| traceId | String | Yes | na |
| authToken | String | Yes | auth |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | data |
| success | Boolean | success |
| resCode | Number | 200 |
| resMessage | String | message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/v2/public/integrations/lazada)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

UNKNOWN

/v2/public/integrations/lazada

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/v2/public/integrations/lazada");
request.addApiParameter("manifestId", "1");
request.addApiParameter("stops", "[{\"address\":\"na\",\"phone\":\"na\",\"jobs\":[{\"weightInGram\":\"na\",\"heightInCm\":\"na\",\"lengthInCm\":\"na\",\"type\":\"na\",\"trackingNumber\":\"na\",\"widthInCm\":\"na\"},{\"weightInGram\":\"na\",\"heightInCm\":\"na\",\"lengthInCm\":\"na\",\"type\":\"na\",\"trackingNumber\":\"na\",\"widthInCm\":\"na\"}],\"type\":\"na\",\"customerName\":\"na\"}]");
request.addApiParameter("traceId", "na");
request.addApiParameter("authToken", "auth");
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
