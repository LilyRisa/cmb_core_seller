# POSTUpdatePriceQuantity

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fprice_quantity%2Fupdate
> API path: /product/price_quantity/update
> Category: Product API
> Scraped: 2026-05-20T23:11:37.323Z

---

Latest update2022-07-28 17:14:55

24810

UpdatePriceQuantity

POST

/product/price\_quantity/update

Authorization Required

Description:Use this API to update the price and quantity of one or more existing products. The maximum number of products that can be updated is 50, but 20 is recommended.

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
| payload | Payload | Yes | [Parameter description](https://open.lazada.com/apps/doc/doc?nodeId=42713&docId=121234) |
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
| 4104 | BIZ\_CHECK\_PRICE\_PRECISION\_INVALID | Price accuracy check failed |
| 4105 | BIZ\_CHECK\_SELLER\_SKU\_DUPLICATE | SellerSku repeat |
| 4106 | CHK\_CATPROP\_CPV\_INPUT\_SIZE\_LIMIT | Item customization attributes exceeded the limit |
| 4107 | CHECK\_CAT\_PROP\_INVALID\_NUMBER | The category attribute value is invalid |
| 4108 | CHK\_BASIC\_REQUIRED | Basic attributes Mandatory verification |
| 4109 | CHK\_SKU\_PROPS\_NOT\_MATCH\_SALE\_PROP | Sku sales attributes do not match |
| 4110 | BIZ\_CHECK\_CAT\_PROP\_MANDATORY | Category attribute This parameter is mandatory |
| 4111 | CHK\_CATPROP\_CPV\_TEXT\_REPEAT | Category attribute content repeats |
| 4112 | CHK\_SKU\_PROPS\_DUPLICATE | Duplicate Sku attributes |
| 4113 | CHK\_SKU\_PROPS\_NOT\_IDENTICAL | Sales attribute is not filled in |
| 4114 | BIZ\_CHECK\_PRICE\_SAMPLE\_NON\_ZERO | The sample price is 0 |
| 4115 | CHK\_CATPROP\_CPV\_NOT\_ENUM | The CPV attribute is not one of the options provided by the category |
| 4116 | BIZ\_CHECK\_MAIN\_IMAGE\_DUPLICATE | Repeat check of master diagram |
| 4117 | BIZ\_CHECK\_SPECIAL\_PRICE\_FROM\_DATE\_AFTER\_TO\_DATE | Special offer date check |
| 4118 | BIZ\_CHECK\_PRICE\_IS\_ZERO | Price is not 0 check |
| 4119 | BIZ\_CHECK\_SPECIAL\_PRICE\_RATE\_OUT\_OF\_RANGE | Special price range check |
| 4120 | CHK\_CATPROP\_CPV\_MAX\_LEGNTH | Verify the maximum CPV value of a category |
| 4121 | BIZ\_CHECK\_SPECIAL\_PRICE\_PRECISION\_INVALID | Special accuracy check does not pass |
| 4122 | BIZ\_CHECK\_VIRTUAL\_BUNDLE\_SKU\_SUB\_OVER\_LIMIT | virtual bundle sku relation skuc over limit |
| 4123 | BIZ\_CHECK\_MANGROVE\_RULE | Restricted publication check |
| 4124 | BIZ\_CHECK\_MANGROVE\_RULE\_QC | MANGROVE rule verification |
| 4125 | THD\_IC\_F\_IC\_DOMAIN\_PROPERTY\_002 | IC Verification category Attribute This parameter is mandatory |
| 4126 | THD\_IC\_F\_IC\_INFRA\_PRODUCT\_036 | SellerSku repeat |
| 4127 | THD\_IC\_F\_IC\_SCENE\_PUBLISH\_012 | ProductId repeat |
| 4128 | THD\_IC\_F\_IC\_DOMAIN\_ACTOR\_006 | Seller lock cannot be edited |
| 4129 | BIZ\_CHECK\_PROP\_SPECIAL\_CHAR | Containssymbol/characterthatisnotallowed:"<".Pleaseremovethenre-upload |
| 4130 | BIZ\_CHECK\_OFFICIAL\_STORE\_BRAND\_UNAUTHORIZED | Uncertified brand |
| 4131 | BIZ\_CHECK\_CAT\_PROP\_SENSITIVE\_WORDS | description has sensitive words New brand |
| 4132 | Invalid Request Format | Invalid Request Format |
| 4133 | Invalid variation | Invalid variation |
| 501 | Update product failed | This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error. |
| 501 | Update product failed | This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error. |
| 901 | Limit service request speed in server side temporarily. | API level QPS limiting flow, please retry in the next second when you encounter this error. |
| 901 | Limit service request speed in server side temporarily. | API level QPS limiting flow, please retry in the next second when you encounter this error. |
| 501 | Update product failed | This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error. |
| 501 | Update product failed | This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error. |
| 901 | Limit service request speed in server side temporarily. | API level QPS limiting flow, please retry in the next second when you encounter this error. |
| 501 | Update product failed | This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error. |
| 901 | Limit service request speed in server side temporarily. | API level QPS limiting flow, please retry in the next second when you encounter this error. |
| 501 | Update product failed | This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error. |
| 4225 | Your product participated in semi-hosted program, please go to GSP to edit the product price/stock/package details information. | To modify the inventory of a Global Plus item call the AdjustSellableQuantity or UpdateSellableQuantity APIs. |
| 4225 | Your product participated in semi-hosted program, please go to GSP to edit the product price/stock/package details information. | To modify the inventory of a Global Plus item call the AdjustSellableQuantity or UpdateSellableQuantity APIs. |
| 4225 | Your product participated in semi-hosted program, please go to GSP to edit the product price/stock/package details information. | To modify the inventory of a Global Plus item call the AdjustSellableQuantity or UpdateSellableQuantity APIs. |
| 901 | Limit service request speed in server side temporarily. | API level QPS limiting flow, please retry in the next second when you encounter this error. |
| 4225 | Your product participated in semi-hosted program, please go to GSP to edit the product price/stock/package details information. | To modify the inventory of a Global Plus item call the AdjustSellableQuantity or UpdateSellableQuantity APIs. |
| 513 | Internal call exception | A small number of occurrences are normal, if you want to avoid this error as much as possible, reduce the number of SKUs in a single request to 20 or less. |
| 4225 | Your product participated in semi-hosted program, please go to GSP to edit the product price/stock/package details information. | To modify the inventory of a Global Plus item call the AdjustSellableQuantity or UpdateSellableQuantity APIs. |
| 4171 | The updated SKU quantity exceeds the maximum number 50, please do not update more than 50 SKUs at once | The number of SKUs included in a single request cannot exceed 50, and no more than 20 is recommended. |
| 4170 | During the Bday Mega campaign, there are restrictions for stock adjustments in effect between YYYY-MM-DD HH:MM:SS - YYYY-MM-DD HH:MM:SS. Sellers can increase stocks, but may not decrease stocks. | This SKU is participating in a special Campaign, so this SKU can't be updated to set stock less than current stock. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/price_quantity/update)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/product/price\_quantity/update

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/price_quantity/update");
request.addApiParameter("payload", "<Request>   <Product>     <Skus>       <Sku>         <ItemId>234234234</ItemId>         <SkuId>234</SkuId>         <SellerSku>Apple-SG-Glod-64G</SellerSku>         <Price>1099.00</Price>         <SalePrice>900.00</SalePrice>         <SaleStartDate>2017-08-08</SaleStartDate>         <SaleEndDate>2017-08-31</SaleEndDate>         <MultiWarehouseInventories>           <MultiWarehouseInventory>             <WarehouseCode>warehouseTest1</WarehouseCode>             <Quantity>20</Quantity>           </MultiWarehouseInventory>           <MultiWarehouseInventory>             <WarehouseCode>warehouseTest2</WarehouseCode>             <Quantity>30</Quantity>           </MultiWarehouseInventory>          </MultiWarehouseInventories>        </Sku>     </Skus>   </Product> </Request>");
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
