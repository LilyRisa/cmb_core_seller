# GET/POSTstartExportByDataset

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbi%2Fdownload%2FstartExportByDataset
> API path: /fbi/download/startExportByDataset
> Category: System API
> Scraped: 2026-05-20T23:02:31.641Z

---

Latest update2025-03-26 15:45:51

1004

startExportByDataset

GET/POST

/fbi/download/startExportByDataset

No Authorization Required

Description:Open the download operation

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
| oldSystemId | String | No | 222 |
| useNewEngine | String | No | true |
| appName | String | Yes | 1 |
| secret | String | Yes | 1 |
| workId | String | Yes | 1 |
| datasetId | String | Yes | 1 |
| fileType | String | Yes | 1 |
| uploadType | String | Yes | 1 |
| dispatchUserInfo | String\[\] | No | 1 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | 1 |
| returnCode | Number | 1 |
| returnValue | Object | 1 |
| returnErrorStackTrace | String | 1 |
| returnMessage | String | 1 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbi/download/startExportByDataset)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/fbi/download/startExportByDataset

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbi/download/startExportByDataset");
request.addApiParameter("oldSystemId", "22");
request.addApiParameter("useNewEngine", "true");
request.addApiParameter("appName", "1");
request.addApiParameter("secret", "1");
request.addApiParameter("workId", "1");
request.addApiParameter("datasetId", "1");
request.addApiParameter("fileType", "1");
request.addApiParameter("uploadType", "1");
request.addApiParameter("dispatchUserInfo", "1");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "returnCode": "1",
    "returnValue": {},
    "returnErrorStackTrace": "1",
    "returnMessage": "1"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
