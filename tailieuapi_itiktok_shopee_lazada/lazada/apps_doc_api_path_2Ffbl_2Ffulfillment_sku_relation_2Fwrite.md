# POSTBuildFulfillmentSkuRelation

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Ffulfillment_sku_relation%2Fwrite
> API path: /fbl/fulfillment_sku_relation/write
> Category: FBL API
> Scraped: 2026-05-20T23:34:32.843Z

---

Latest update2022-07-26 00:15:47

4082

BuildFulfillmentSkuRelation

POST

/fbl/fulfillment\_sku\_relation/write

Authorization Required

Description:build the relation between platformSku and fulfillmentSku

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
| sc\_item\_id | Number | No | fulfillmentSkuId |
| fulfillment\_sku | String | No | fulfillmentSku |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result DTO |
| success | Boolean | if success |
| failure | Boolean | if failure |
| error\_code | String | error\_code |
| error\_msg | String | error\_msg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| PARAM\_ILLEGAL | "sku not exists" | SKU incoming error please verify if it exists in the corresponding shop |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/fulfillment_sku_relation/write)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/fbl/fulfillment\_sku\_relation/write

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/fulfillment_sku_relation/write");
request.addApiParameter("site", "SG");
request.addApiParameter("item_id", "710230731");
request.addApiParameter("sku_id", "1551058427");
request.addApiParameter("sc_item_id", "610412175368");
request.addApiParameter("fulfillment_sku", "CB-2230148066-12811407956");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "error_msg": "relation exists",
    "success": "false",
    "failure": "true",
    "error_code": "PARAM_ILLEGAL"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
