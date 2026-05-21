# GET/POSTGetChoiceSkuItemRelationBySku

> Source: https://open.lazada.com/apps/doc/api?path=%2Fchoice%2Fsku_item_relation%2Fget_by_sku
> API path: /choice/sku_item_relation/get_by_sku
> Category: Choice Customized API
> Scraped: 2026-05-21T00:10:13.916Z

---

Latest update2026-05-21 08:10:07

500

GetChoiceSkuItemRelationBySku

GET/POST

/choice/sku\_item\_relation/get\_by\_sku

Authorization Required

Description:get the relation between platformSku and item by sku

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
| item\_id | String | Yes | itemId |
| sku\_id | String | Yes | skuId |
| site | String | Yes | The country site of the queried Product item |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Response |
| item\_id | Number | itemId |
| site | String | site |
| seller\_id | Number | sellerId |
| sc\_item\_user\_id | String | scItemUserId, always null |
| sc\_item\_id | Number | scItemId |
| source | String | source |
| sku\_id | Number | skuId |
| barcode | String | barcode |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/choice/sku_item_relation/get_by_sku)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/choice/sku\_item\_relation/get\_by\_sku

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/choice/sku_item_relation/get_by_sku");
request.addApiParameter("item_id", "2934199168");
request.addApiParameter("sku_id", "14293663022");
request.addApiParameter("site", "MY");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {
    "site": "MY",
    "item_id": "2934199168",
    "sc_item_user_id": "null",
    "sku_id": "14293663022",
    "source": "Lazada",
    "barcode": "\"121311313\"",
    "seller_id": "1000382765",
    "sc_item_id": "685313917795"
  },
  "request_id": "0ba2887315178178017221014"
}
```
