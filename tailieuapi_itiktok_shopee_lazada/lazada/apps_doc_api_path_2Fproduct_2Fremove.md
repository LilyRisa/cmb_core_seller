# POSTRemoveProduct

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fremove
> API path: /product/remove
> Category: Product API
> Scraped: 2026-05-20T23:10:59.953Z

---

Latest update2022-07-29 14:54:03

9284

RemoveProduct

POST

/product/remove

Authorization Required

Description:Use this API to remove an existing product, some SKUs in one product, or all SKUs in one product. System supports a maximum number of 50 SellerSkus in one request.

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
| seller\_sku\_list | String | No | sellerSku in a json list to be removed. System supports a maximum number of 50 sellerSku in one request.;for example: itemid: 1269656765 sellerSku: test00111 、test00222、test00333, then Param should be: \["test00111","test00222","test00333"\] |
| sku\_id\_list | String | No | Highest priority,skuId in a json list to be removed. System supports a maximum number of 50 skuId in one request.; for example: itemid: 1269656765 skuid: 5230534246, then Param should be: \["SkuId\_1269656765\_5230534246"\] |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Response body |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 5 | E005: Invalid Request Format | The request format is not valid. |
| 6 | E006: Unexpected internal error | Unexpected internal error. |
| 30 | E030: Empty Request | The request URL is not complete. |
| 204 | E204: Too many SKU in one request | The number of SKUs exceeds the limit. |
| 503 | E503: Remove product failed | Failed to remove the product. |
| 512 | E512: BIZ\_CHECK\_MANGROVE\_RULE\_QC | The request failed because the category was banned |
| 1000 | Internal Application Error | Internal system error. |
| 901 | Limit service request speed in server side temporarily. | API level QPS limiting flow, please retry in the next second when you encounter this error. |
| 6 | Unexpected internal error | The seller\_sku\_list field has been deprecated, please use the sku\_id\_list field, if you still encounter this issue frequently with the sku\_id\_list field, please create a ticket inquiry. |
| 503 | Remove product failed | This is a generalized error code, it is not possible to determine the specific problem based on this error code, please check the message information in the detail field to understand the details of the error, for example, the product cannot be found or the SKU has been deleted. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/remove)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/product/remove

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/remove");
request.addApiParameter("seller_sku_list", "[\"test00111\",\"test00222\",\"test00333\"]");
request.addApiParameter("sku_id_list", "[\"SkuId_1269656765_5230534246\",\"SkuId_1269656765_5230534246\",\"SkuId_1269656765_5230534246\"]");
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
