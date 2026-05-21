# GETRssGetPickupLocations

> Source: https://open.lazada.com/apps/doc/api?path=%2Frss%2FpickupLocations%2Fget
> API path: /rss/pickupLocations/get
> Category: RedMart API
> Scraped: 2026-05-20T23:59:43.154Z

---

Latest update2024-03-14 17:00:51

1942

RssGetPickupLocations

GET

/rss/pickupLocations/get

Authorization Required

Description:rss get pickupLocations by storeId

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
| storeId | Number | Yes | store id |
| page | Number | Yes | page |
| pageSize | Number | Yes | pageSize |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| data | Object\[\] | pickup location detail |
| page | Number | page |
| pageSize | Number | page size |
| total | Number | total |
| success | Boolean | success |
| errorMessage | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/rss/pickupLocations/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/rss/pickupLocations/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/rss/pickupLocations/get");
request.setHttpMethod("GET");
request.addApiParameter("storeId", "414");
request.addApiParameter("page", "1");
request.addApiParameter("pageSize", "10");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "total": "15",
    "data": [
      {
        "country": "SG",
        "city": "SG",
        "postalCode": "12345",
        "name": "test",
        "addressLine1": "123 Main Street",
        "addressLine2": "Apt 4B",
        "id": "1"
      }
    ],
    "success": "true",
    "errorMessage": "error message",
    "pageSize": "10",
    "page": "1"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
