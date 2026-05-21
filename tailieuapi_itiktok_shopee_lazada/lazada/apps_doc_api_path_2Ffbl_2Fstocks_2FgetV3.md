# GETGetWarehouseStockV3

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Fstocks%2FgetV3
> API path: /fbl/stocks/getV3
> Category: FBL API
> Scraped: 2026-05-20T23:42:16.069Z

---

Latest update2026-05-21 07:42:03

500

GetWarehouseStockV3

GET

/fbl/stocks/getV3

Authorization Required

Description:Get SKU list and stock by warehouse code, this version separates pending inbound and stock in transit in return json.

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
| seller\_sku | String | No | Seller SKU, required when fulfilment\_sku is empty |
| marketplace | String | Yes | Marketplace should be "LAZADA\_MY","LAZADA\_ID","LAZADA\_VN","LAZADA\_SG","LAZADA\_TH","LAZADA\_PH" |
| fulfilment\_sku | String | No | List of shop SKU, Comma separated list in square brackets, required when seller\_sku is empty |
| store\_code | String | No | Warehouse Code List：https://www.yuque.com/u1990121/kb/exh5go#B4gg |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object\[\] | Response body |
| fulfilment\_sku | String | Shop SKU |
| store\_stocks | Object\[\] | Stock Warehouse Detail |
| store\_code | String | Warehouse Code |
| stocks | Object | Stock Items Detail |
| expiredUnsellable | Object | Expired Unsellable Stock |
| available | Number | Expired Unsellable Available Stock |
| reserved | Number | Expired Unsellable Reserved Stock |
| sellable | Object | Sellable Stock |
| available | Number | Sellable available Stocks |
| reserved | Number | Sellable reserved Stocks |
| unsellable | Object | Unsellable Stock |
| available | Number | Unsellable available Stocks |
| reserved | Number | Unsellable reserved Stocks |
| pending | Object | Pending Stock |
| available | Number | Pending available Stocks |
| reserved | Number | Pending reserved Stocks |
| transfer | Object | Transfer Stock |
| available | Number | Transfer available Stocks |
| reserved | Number | Transfer reserved Stocks |
| damagedUnsellable | Object | Damaged Unsellable Stock |
| reserved | Number | Damaged Unsellable Reserved Stock |
| available | Number | Damaged Unsellable Reserved Stock |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| HttpConnectError | Request failed, due to \[%s\] | The connection timed out or failed and needs to be retried. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/stocks/getV3)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/stocks/getV3

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/stocks/getV3");
request.setHttpMethod("GET");
request.addApiParameter("seller_sku", "AP082ELAAXCXJVANID");
request.addApiParameter("marketplace", "LAZADA_ID");
request.addApiParameter("fulfilment_sku", "AP082ELAAXCXJVANID-75574360,WA362HBABISOANID-77873");
request.addApiParameter("store_code", "OMS-LAZADA-SG-W-1");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": [
    {
      "fulfilment_sku": "AP082ELAAXCXJVANID-75574360",
      "store_stocks": [
        {
          "store_code": "OMS-LAZADA-ID-W-1",
          "stocks": {
            "damagedUnsellable": {
              "reserved": "2",
              "available": "10"
            },
            "transfer": {
              "reserved": "2",
              "available": "10"
            },
            "pending": {
              "reserved": "2",
              "available": "10"
            },
            "unsellable": {
              "reserved": "2",
              "available": "10"
            },
            "expiredUnsellable": {
              "reserved": "2",
              "available": "10"
            },
            "sellable": {
              "reserved": "2",
              "available": "10"
            }
          }
        }
      ]
    }
  ],
  "request_id": "0ba2887315178178017221014"
}
```
