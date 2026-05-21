# GET/POSTScanParcel

> Source: https://open.lazada.com/apps/doc/api?path=%2Fdop%2Fscan
> API path: /dop/scan
> Category: Logistics API
> Scraped: 2026-05-20T23:28:53.225Z

---

Latest update2023-04-03 11:32:13

4217

ScanParcel

GET/POST

/dop/scan

No Authorization Required

Description:DOP Scan Parcel

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
| cageNumber | String | Yes | test |
| trackingNumber | String | Yes | test |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| trackingNumber | String | test |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/dop/scan)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/dop/scan

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/dop/scan");
request.addApiParameter("cageNumber", "case1");
request.addApiParameter("trackingNumber", "MYMPA092974023");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "trackingNumber": "MYMPA092974023",
  "request_id": "0ba2887315178178017221014"
}
```
