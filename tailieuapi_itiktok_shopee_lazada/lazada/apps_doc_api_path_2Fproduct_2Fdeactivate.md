# POSTDeactivateProduct

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fdeactivate
> API path: /product/deactivate
> Category: Product API
> Scraped: 2026-05-20T23:07:07.681Z

---

Latest update2022-07-28 17:05:41

11795

DeactivateProduct

POST

/product/deactivate

Authorization Required

Description:Use this API to deactivate Product or SKUs corresponding to the product

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
| apiRequestBody | String | Yes | Parameter ItemId is mandatory, Skus is optional |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Response body |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| E0001 | Parameter ItemId is mandatory | Parameter ItemId is mandatory |
| E0002 | Product not exists | Product not exists |
| E0003 | Seller Sku not exists | Seller Sku not exists |
| E0004 | Product Status not online | Product Status not online |
| E0006 | Unexpected internal error | Unexpected internal error |
| E0004 | Product Status not online | The current item is already in the Inactive state and does not need to call this API. |
| E0004 | Product Status not online | The current item is already in the Inactive state and does not need to call this API. |
| E0004 | Product Status not online | The current item is already in the Inactive state and does not need to call this API. |
| E0004 | Product Status not online | The current item is already in the Inactive state and does not need to call this API. |
| E0004 | Product Status not online | The current item is already in the Inactive state and does not need to call this API. |
| E0002 | Product:item id not exist | The item id in your request does not exist with the current store, please call GetProducts/GetProductItem API to check. |
| 901 | Limit service request speed in server side temporarily. | API level QPS limiting flow, please retry in the next second when you encounter this error. |
| 4193 | The SellerSku parameter is no longer supported. Please update your parameter to use SkuId and try again | Seller sku field does not have uniqueness, so it cannot be used as a key field for editing products, please add SkuId field as product editing Key. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/deactivate)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/product/deactivate

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/deactivate");
request.addApiParameter("apiRequestBody", "<Request><Product><ItemId>234234234</ItemId><Skus><SkuId>20690462002</SkuId><SellerSku>5000</SellerSku></Skus></Product></Request>");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {},
  "request_id": "0ba2887315178178017221014"
}
```
