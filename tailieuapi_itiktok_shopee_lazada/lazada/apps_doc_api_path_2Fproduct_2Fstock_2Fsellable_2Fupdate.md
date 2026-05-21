# GET/POSTUpdateSellableQuantity

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fstock%2Fsellable%2Fupdate
> API path: /product/stock/sellable/update
> Category: Product API
> Scraped: 2026-05-20T23:11:53.864Z

---

Latest update2022-07-28 17:14:55

18479

UpdateSellableQuantity

GET/POST

/product/stock/sellable/update

Authorization Required

Description:Use this API to update sellable quantity of one or more existing products. The maximum number of products that can be updated is 50, but 20 is recommended.

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
| payload | String | Yes | Please take demo as reference. |
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
| 501 | E501: Update product failed | Failed to update the product price or stock. |
| 901 | E901: The request is too frequent, or the requested functionality is temporarily disabled. | Failed to return the requested data due to high calling frequency or disabled functionality. Please try again later. |
| 1000 | Internal Application Error | Internal system error. |
| 501 | Update product failed | This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error. |
| 501 | Update product failed | This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error. |
| 501 | Update product failed | This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error. |
| 901 | Limit service request speed in server side temporarily. | API level QPS limiting flow, please retry in the next second when you encounter this error. |
| 501 | Update product failed | This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error. |
| 501 | Update product failed | This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error. |
| 901 | Limit service request speed in server side temporarily. | API level QPS limiting flow, please retry in the next second when you encounter this error. |
| 901 | Limit service request speed in server side temporarily. | API level QPS limiting flow, please retry in the next second when you encounter this error. |
| 501 | Update product failed | This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error. |
| 4170 | During the Bday Mega campaign, there are restrictions for stock adjustments in effect between YYYY-MM-DD HH:MM:SS - YYYY-MM-DD HH:MM:SS. Sellers can increase stocks, but may not decrease stocks. | This SKU is participating in a special Campaign, so this SKU can't be updated to set stock less than current stock. |
| 901 | Limit service request speed in server side temporarily. | API level QPS limiting flow, please retry in the next second when you encounter this error. |
| 6 | Unexpected internal error | System fluctuation please retry, if you encounter this error frequently, please create a ticket to consult. |
| 513 | Internal call exception | A small number of occurrences are normal, if you want to avoid this error as much as possible, reduce the number of SKUs in a single request to 20 or less. |
| 4170 | During the Bday Mega campaign, there are restrictions for stock adjustments in effect between YYYY-MM-DD HH:MM:SS - YYYY-MM-DD HH:MM:SS. Sellers can increase stocks, but may not decrease stocks. | This SKU is participating in a special Campaign, so this SKU can't be updated to set stock less than current stock. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/stock/sellable/update)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/product/stock/sellable/update

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/stock/sellable/update");
request.addApiParameter("payload", "<Request>   <Product>      <Skus>   <!--single warehouse demo-->  <Sku>         <ItemId>234234234</ItemId>         <SkuId>234</SkuId>         <SellerSku>Apple-SG-Glod-64G</SellerSku>                                     <SellableQuantity>20</SellableQuantity>    </Sku>   <!--multi warehouse demo-->   <Sku>         <ItemId>234234234</ItemId>         <SkuId>234</SkuId>         <SellerSku>Apple-SG-Glod-64G</SellerSku>                <MultiWarehouseInventories>           <MultiWarehouseInventory>             <WarehouseCode>warehouseTest1</WarehouseCode>             <SellableQuantity>20</SellableQuantity>           </MultiWarehouseInventory>           <MultiWarehouseInventory>             <WarehouseCode>warehouseTest2</WarehouseCode>             <SellableQuantity>30</SellableQuantity>           </MultiWarehouseInventory>          </MultiWarehouseInventories>        </Sku>   </Skus>   </Product> </Request>");
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
