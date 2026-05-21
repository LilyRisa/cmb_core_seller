# GET/POSTGetFulfillmentSkuRelationsByScItems

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Ffulfillment_sku_relation%2Fget_by_sc_items
> API path: /fbl/fulfillment_sku_relation/get_by_sc_items
> Category: FBL API
> Scraped: 2026-05-20T23:38:49.573Z

---

Latest update2022-07-29 18:03:28

2174

GetFulfillmentSkuRelationsByScItems

GET/POST

/fbl/fulfillment\_sku\_relation/get\_by\_sc\_items

Authorization Required

Description:get fulfillmentSku Relations By ScItems

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
| biz\_name | String | Yes | bizName |
| seller\_ids | Number\[\] | Yes | sellerIds |
| sc\_item\_ids | Number\[\] | No | scItemIds |
| fulfillment\_skus | String\[\] | No | fulfillmentSkus |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| data | Object\[\] | data |
| item\_id | Number | itemId |
| site | String | site |
| seller\_id | Number | sellerId |
| sc\_item\_user\_id | Number | shipperId |
| sc\_item\_id | Number | fulfillentSkuId |
| source | String | source |
| fulfillment\_sku | String | fulfillmentSku |
| sku\_id | Number | skuId |
| failure | Boolean | is failed |
| success | Boolean | is success |
| error\_code | String | error |
| error\_msg | String | error |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/fulfillment_sku_relation/get_by_sc_items)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/fbl/fulfillment\_sku\_relation/get\_by\_sc\_items

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/fulfillment_sku_relation/get_by_sc_items");
request.addApiParameter("biz_name", "Lazada/Daraz");
request.addApiParameter("seller_ids", "[100056775,100131295,1000029719,100076220,100191757]");
request.addApiParameter("sc_item_ids", "[677725310474]");
request.addApiParameter("fulfillment_skus", "[CB-720504627-1582768814]");
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
    "data": [
      {
        "site": "MY",
        "item_id": "3068365057",
        "fulfillment_sku": "CB-720504627-1582768814",
        "sc_item_user_id": "3944706226",
        "sku_id": "15291207605",
        "source": "Lazada",
        "seller_id": "100131295",
        "sc_item_id": "610346434581"
      }
    ],
    "failure": "false",
    "success": "true",
    "error_code": "null"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
