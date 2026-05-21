# GET/POSTInstallServiceCallBack

> Source: https://open.lazada.com/apps/doc/api?path=%2Fdigital%2Finstall%2Fservicecallback
> API path: /digital/install/servicecallback
> Category: Lazada DG API
> Scraped: 2026-05-21T00:01:15.266Z

---

Latest update2022-11-21 14:46:20

2795

InstallServiceCallBack

GET/POST

/digital/install/servicecallback

No Authorization Required

Description:Install the service callback interface

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
| orderNo | String | Yes | service provider company orderId |
| thirdOrderNo | String | Yes | LZD orderLineId |
| type | String | Yes | type = 1 (mean install sevice finish) type = 2(mean install update). type =3 (mean cancel install service) |
| servicePrice | String | No | install service price |
| serviceDate | String | No | install service date |
| jobStatus | String | No | The installation status of the external company |
| jobReason | String | No | Reasons for success or failure |
| extendInfo | String | No | extendInfo |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| resultCode | String | result code |
| resultMsg | String | result message |
| transactionId | String | LZD orderLineId |
| extendInfo | String | extendInfo |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 00 | transaction success | transaction success |
| 01 | cancel success | cancel success |
| 02 | update serviceDate success | update serviceDate success |
| 99 | fail | fail |
| 11 | orderNo is empty | orderNo is empty |
| 12 | thirdOrderNo is empty | thirdOrderNo is empty |
| 13 | type is empty | type is empty |
| 14 | type not exist | type not exist |
| 15 | servicePrice is empty | servicePrice is empty |
| 16 | jobStatus is empty | jobStatus is empty |
| 17 | serviceDate is empty | serviceDate is empty |
| 21 | order processing | order processing |
| 31 | parse extendInfo to map fail | parse extendInfo to map fail |
| 32 | date format is wrong | date format is wrong |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/digital/install/servicecallback)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/digital/install/servicecallback

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/digital/install/servicecallback");
request.addApiParameter("orderNo", "9827281687778");
request.addApiParameter("thirdOrderNo", "9827281687778");
request.addApiParameter("type", "1");
request.addApiParameter("servicePrice", "2000");
request.addApiParameter("serviceDate", "2022-11-18");
request.addApiParameter("jobStatus", "created");
request.addApiParameter("jobReason", "success");
request.addApiParameter("extendInfo", "{\"xxx\":\"xxx\"}");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "resultCode": "00",
  "extendInfo": "{\"xxx\":\"xxx\"}",
  "request_id": "0ba2887315178178017221014",
  "transactionId": "9827281687778",
  "resultMsg": "success"
}
```
