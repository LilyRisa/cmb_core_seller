# GET/POSTRssGetOnePickupJob

> Source: https://open.lazada.com/apps/doc/api?path=%2Frss%2Fpickup-job%2Fget
> API path: /rss/pickup-job/get
> Category: RedMart API
> Scraped: 2026-05-20T23:59:26.031Z

---

Latest update2024-03-15 11:00:48

2393

RssGetOnePickupJob

GET/POST

/rss/pickup-job/get

Authorization Required

Description:Get details of a pickup job

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
| pickupJobId | Number | Yes | pickup job id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| data | Object | pickup job details |
| success | Boolean | success |
| errorMessage | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/rss/pickup-job/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/rss/pickup-job/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/rss/pickup-job/get");
request.addApiParameter("storeId", "414");
request.addApiParameter("pickupJobId", "3588214");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "data": {
      "preferredPickupTime": "13:00-17:00",
      "amendabilityCutOffDate": 1684135989000,
      "pickedAt": 1684136189000,
      "qtyFulfilledCount": 10,
      "id": 123,
      "category": "Dry",
      "items": [
        {
          "shipmentsInfo": [
            {
              "orderId": "49e74qjnkprp1to4",
              "qty": 5
            },
            {
              "orderId": "49e74qjn1prp1to4",
              "qty": 6
            }
          ],
          "qtyFulfilled": 10,
          "size": "2.5kg",
          "rpc": 123456,
          "qty": 11,
          "imageUrl": "http://media.redmart.com/newmedia/1600x/i/m/xxx.jpg",
          "name": "Salmon",
          "vpc": "19739731408",
          "minimumExpiryDate": 1770357600000,
          "sku": "19739731408"
        }
      ],
      "scheduledAt": 1684135189000,
      "status": "pickedup",
      "qtyCount": 11
    },
    "success": "true",
    "errorMessage": "\"ERROR\""
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
