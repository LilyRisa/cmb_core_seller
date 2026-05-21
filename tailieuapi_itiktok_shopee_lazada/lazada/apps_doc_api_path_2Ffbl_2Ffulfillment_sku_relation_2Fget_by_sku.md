# GET/POSTGetFulfillmentSkuRelationBySku

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Ffulfillment_sku_relation%2Fget_by_sku
> API path: /fbl/fulfillment_sku_relation/get_by_sku
> Category: FBL API
> Scraped: 2026-05-20T23:38:34.409Z

---

Latest update2022-07-29 17:58:18

2389

GetFulfillmentSkuRelationBySku

GET/POST

/fbl/fulfillment\_sku\_relation/get\_by\_sku

Authorization Required

Description:get the relation between platformSku and fulfillmentSku by sku

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
| site | String | Yes | site |
| item\_id | Number | Yes | itemId |
| sku\_id | Number | Yes | skuId |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result dto |
| data | Object | data dto |
| item\_id | Number | itemId |
| site | String | site |
| seller\_id | Number | sellerId |
| sc\_item\_user\_id | String | scItem userId |
| sc\_item\_id | Number | scItemId/fulfillment\_sku\_id |
| source | String | platform source |
| sku\_id | Number | skuId |
| fulfillment\_sku | String | fulfillment\_sku |
| failure | Boolean | if failure |
| success | Boolean | if success |
| error\_code | String | error\_code |
| error\_msg | String | error\_msg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/fulfillment_sku_relation/get_by_sku)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/fbl/fulfillment\_sku\_relation/get\_by\_sku

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/fulfillment_sku_relation/get_by_sku");
request.addApiParameter("site", "SG");
request.addApiParameter("item_id", "710230731");
request.addApiParameter("sku_id", "1551058427");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "error_msg": "null",
    "data": {
      "site": "TH",
      "item_id": "151579598",
      "fulfillment_sku": "CB-885710187-1305210866",
      "sc_item_user_id": "null",
      "sku_id": "177256899",
      "source": "Lazada",
      "seller_id": "100011046",
      "sc_item_id": "567148194446"
    },
    "failure": "false",
    "success": "true",
    "error_code": "null"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
