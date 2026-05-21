# GETGetPlatformProductsV2

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Fplatform_products%2Fget2
> API path: /fbl/platform_products/get2
> Category: FBL API
> Scraped: 2026-05-20T23:41:05.883Z

---

Latest update2026-05-21 07:40:52

500

GetPlatformProductsV2

GET

/fbl/platform\_products/get2

Authorization Required

Description:Search products list

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
| per\_page | Number | No | Maximum number of results Per Page |
| seller\_id | Number | Yes | sellerId |
| marketplace | String | Yes | Marketplace |
| seller\_sku | String | No | sellerSku |
| platform\_sku\_name | String | No | Platform SKU Name |
| ready\_for\_inbound | Boolean | No | Products that have binding stock in warsehouse |
| platform\_sku | String | No | List of Platform SKU. Separate By Comma (,) |
| page | Number | No | Page Number |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object\[\] | List of products data |
| platform\_sku\_name | String | Platform SKU Name |
| status | String | status from ASC |
| marketplace | String | marketplace Fields |
| source | String | source |
| product\_id | String | productId |
| skus | Object\[\] | List of sku data |
| fulfillment\_sku\_name | String | FulfillmentSkuName |
| fulfillment\_sku | String | FulfillmentSkuCode |
| sku\_status | String | status from ASC |
| platform\_sku | String | PlatformSkuCode |
| seller\_sku | String | sellerSku |
| extend\_fields | String | extend\_fields |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/platform_products/get2)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/platform\_products/get2

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/platform_products/get2");
request.setHttpMethod("GET");
request.addApiParameter("per_page", "50");
request.addApiParameter("seller_id", "100056775");
request.addApiParameter("marketplace", "LAZADA_SG");
request.addApiParameter("seller_sku", "341355");
request.addApiParameter("platform_sku_name", "normal product name   ");
request.addApiParameter("ready_for_inbound", "true");
request.addApiParameter("platform_sku", "222103860_TH-339012944");
request.addApiParameter("page", "1");
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
      "skus": [
        {
          "fulfillment_sku": "OE702OTABNQVRKSGAMZ-132588140",
          "seller_sku": "xxxxxxx",
          "extend_fields": "{\"k\":\"v\"}",
          "sku_status": "actice",
          "platform_sku": "OE702OTABNQVRKSGAMZ-132588140",
          "fulfillment_sku_name": "Pampers Baby Dry Diaper New Born 40s - 4 Packs "
        }
      ],
      "marketplace": "LAZADA_SG",
      "product_id": "222103860 ",
      "platform_sku_name": "Pampers Baby Dry Diaper New Born 40s - 4 Packs ",
      "source": "ascp-item-center",
      "status": "actice"
    }
  ],
  "request_id": "0ba2887315178178017221014"
}
```
