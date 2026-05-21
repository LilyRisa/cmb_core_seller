# POSTEpisUploadAwbFulfillment

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fepis%2Ffulfillment%2Fupload_awb
> API path: /logistics/epis/fulfillment/upload_awb
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:50:03.408Z

---

Latest update2025-10-31 17:18:52

1725

EpisUploadAwbFulfillment

POST

/logistics/epis/fulfillment/upload\_awb

No Authorization Required

Description:External partner call EPIS to upload awb for fulfillment

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
| logisticsOrderId | String | Yes | logisticsOrderId |
| trackingNumber | String | No | trackingNumber |
| waybill | byte\[\] | No | Waybill content |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| retryable | Boolean | Is request retryable? |
| traceId | String | Trace id for debugging |
| success | Boolean | Is success? |
| errorMessage | String | Error message |
| errorCode | String | Error code |
| errors | Object\[\] | Error details |
| field | String | When validation failed on field, the error field path will be included, begin with "$." as root object |
| errorMessage | String | Detail error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/epis/fulfillment/upload_awb)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/epis/fulfillment/upload\_awb

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/epis/fulfillment/upload_awb");
request.addApiParameter("logisticsOrderId", "logisticsOrderId");
request.addApiParameter("trackingNumber", "trackingNumber");
request.addFileParameter("waybill",new FileItem("/Users/D ocuments/book.jpg"));
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "retryable": "false",
  "traceId": "0ba2887315172940728551014",
  "code": "0",
  "success": "false",
  "errorMessage": "Bad request",
  "errorCode": "BAD_REQUEST",
  "request_id": "0ba2887315178178017221014",
  "errors": [
    {
      "field": "$.items.name",
      "errorMessage": "name must not be blank"
    }
  ]
}
```
