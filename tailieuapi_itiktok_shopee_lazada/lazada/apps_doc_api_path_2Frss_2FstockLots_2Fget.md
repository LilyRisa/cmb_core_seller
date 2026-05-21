# GETRssGetStockLots

> Source: https://open.lazada.com/apps/doc/api?path=%2Frss%2FstockLots%2Fget
> API path: /rss/stockLots/get
> Category: RedMart API
> Scraped: 2026-05-21T00:00:46.468Z

---

Latest update2026-05-21 08:00:33

500

RssGetStockLots

GET

/rss/stockLots/get

Authorization Required

Description:rss get stockLots

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
| pickupLocationId | Number | Yes | pickupLocation id |
| productId | Number | Yes | product id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| data | Object\[\] | data |
| success | Boolean | success |
| errorMessage | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/rss/stockLots/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/rss/stockLots/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/rss/stockLots/get");
request.setHttpMethod("GET");
request.addApiParameter("storeId", "414");
request.addApiParameter("pickupLocationId", "1837");
request.addApiParameter("productId", "1026158");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "data": [
      {
        "quantityAvailableForSale": 8,
        "quantityScheduledForPickup": 5,
        "id": "12345",
        "quantityAtPickupLocation": 13
      }
    ],
    "success": "true",
    "errorMessage": "error message"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
