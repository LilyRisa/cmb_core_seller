# POSTPackageStatusUpdateForDBS

> Source: https://open.lazada.com/apps/doc/api?path=%2Forder%2Fpackage%2Fsof%2Fstatus%2Fupdate
> API path: /order/package/sof/status/update
> Category: Fulfillment API
> Scraped: 2026-05-20T23:27:13.417Z

---

Latest update2024-08-08 15:06:24

4477

PackageStatusUpdateForDBS

POST

/order/package/sof/status/update

Authorization Required

Description:DBS package status update. This interface is only open to some stores

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
| trackingNumber | String | Yes | waybill no |
| source | String | Yes | OPENAPI |
| carrierCode | String | No | SF |
| tag | String | Yes | package no |
| trackInfo | Object | Yes | track info |
| latestStatus | Object | Yes | latest status |
| status | String | Yes | status |
| subStatus | String | Yes | subStatus |
| subStatusDesc | String | No | subStatusDesc |
| latestEvent | Object | Yes | latestEvent |
| eventTime | Number | Yes | 1723012167919 |
| description | String | No | description |
| location | String | No | location |
| stage | String | No | stage |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | api result |
| module | Object | content |
| result | Boolean | business result |
| errorCode | Object | error msesage |
| displayMessage | String | error msesage |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/order/package/sof/status/update)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/order/package/sof/status/update

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/order/package/sof/status/update");
request.addApiParameter("trackingNumber", "SOF123456");
request.addApiParameter("source", "OPENAPI");
request.addApiParameter("carrierCode", "SF");
request.addApiParameter("tag", "FP043412484186001");
request.addApiParameter("trackInfo", "{\"latestStatus\":{\"subStatusDesc\":\"subStatusDesc\",\"subStatus\":\"subStatus\",\"status\":\"status\"},\"latestEvent\":{\"stage\":\"stage\",\"eventTime\":\"1723012167919\",\"description\":\"description\",\"location\":\"location\"}}");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "success": "true",
  "module": {
    "result": "true"
  },
  "errorCode": {
    "displayMessage": "error msesage"
  },
  "request_id": "0ba2887315178178017221014"
}
```
