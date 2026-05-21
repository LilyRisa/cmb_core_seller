# GETGetStockRule

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Fstock_rule%2Fget
> API path: /fbl/stock_rule/get
> Category: FBL API
> Scraped: 2026-05-20T23:41:21.279Z

---

Latest update2026-05-21 07:41:15

500

GetStockRule

GET

/fbl/stock\_rule/get

Authorization Required

Description:Get SKU stock rule by sku and warehouse

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
| fulfillment\_sku\_ids | String | No | fulfilment sku id list |
| store\_code | String | Yes | warehouse code |
| page | String | No | page index, default: 1 |
| per\_page | String | No | page size, default: 50 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | String | result |
| error\_code | String | error code |
| error\_message | String | error message |
| page | Number | page |
| per\_page | Number | page size |
| total\_count | Number | total count |
| data | Object\[\] | data list |
| fulfillment\_sku\_id | String | fulfillment sku id |
| store\_code | String | warehouse code |
| auto\_balancing | Boolean | enable auto balance |
| channel\_ratio | Object\[\] | channel ratio list |
| ratio | Number | channel ratio |
| channel\_code | String | EXTERNAL channel or LAZADA channel |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/stock_rule/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/stock\_rule/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/stock_rule/get");
request.setHttpMethod("GET");
request.addApiParameter("fulfillment_sku_ids", "638646125507,654069263100");
request.addApiParameter("store_code", "OMS-LAZADA-WH3");
request.addApiParameter("page", "1");
request.addApiParameter("per_page", "50");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "shipper error",
  "per_page": "50",
  "code": "0",
  "data": [
    {
      "store_code": "OMS-LAZADA-SG-W-1",
      "channel_ratio": [
        {
          "channel_code": "EXTERNAL",
          "ratio": "100"
        }
      ],
      "fulfillment_sku_id": "1234",
      "auto_balancing": "true"
    }
  ],
  "success": "true",
  "total_count": "150",
  "error_code": "SHIPPER_ERROR",
  "page": "1",
  "request_id": "0ba2887315178178017221014"
}
```
