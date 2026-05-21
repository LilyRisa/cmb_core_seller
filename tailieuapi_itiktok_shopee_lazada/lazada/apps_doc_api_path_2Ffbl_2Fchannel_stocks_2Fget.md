# GETGetChannelStocksForMCL

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Fchannel_stocks%2Fget
> API path: /fbl/channel_stocks/get
> Category: FBL API
> Scraped: 2026-05-20T23:37:44.692Z

---

Latest update2022-07-29 17:39:22

2971

GetChannelStocksForMCL

GET

/fbl/channel\_stocks/get

Authorization Required

Description:Query Channel Stocks

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
| platform\_name | String | Yes | Platform Name |
| fulfillment\_sku\_id | Number | Yes | Fulfillment Sku ID |
| warehouse\_code | String | No | Warehouse Code |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Success Flag |
| error\_code | String | Error Code |
| error\_message | String | Error Message |
| data | Object | Result Data |
| fulfillment\_sku\_id | Number | Fulfillment Sku ID |
| stocks | Object\[\] | Stocks by Warehouses |
| warehouse\_code | String | Warehouse Code |
| channel\_stocks | Object\[\] | Stocks by Channel |
| quantity | Number | Quantity |
| channel | String | Channel Value: EXTERNAL |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/channel_stocks/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/channel\_stocks/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/channel_stocks/get");
request.setHttpMethod("GET");
request.addApiParameter("platform_name", "LAZADA_TH");
request.addApiParameter("fulfillment_sku_id", "222222");
request.addApiParameter("warehouse_code", "OMS-LAZADA-TH-W-1");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "Error Message",
  "code": "0",
  "data": {
    "fulfillment_sku_id": "222222",
    "stocks": [
      {
        "warehouse_code": "OMS-LAZADA-TH-W-1",
        "channel_stocks": [
          {
            "quantity": "16",
            "channel": "EXTERNAL"
          }
        ]
      }
    ]
  },
  "success": "TRUE",
  "error_code": "Error Code",
  "request_id": "0ba2887315178178017221014"
}
```
