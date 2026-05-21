# POSTCreateGlobalProduct

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fglobal%2Fcreate
> API path: /product/global/create
> Category: Cross Boarder Product API
> Scraped: 2026-05-20T23:12:20.321Z

---

Latest update2022-07-28 16:57:50

11682

CreateGlobalProduct

POST

/product/global/create

Authorization Required

Description:Use this API to create a single new global product to multiple Lazada sites. (For cross boarder sellers ONLY)

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
| payload | Payload | Yes | [Parameter description](https://open.lazada.com/apps/doc/doc?nodeId=30715&docId=121751) |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Response body |
| sku\_list | Object\[\] | SKU information |
| seller\_sku | String | The SellerSku that is defined,There are no two identical seller SKUs in the same store, |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| "500" | "E500: Create product failed" | The product was not created, please check the detailed error message. |
| ServiceTimeout | The request has failed due to service timeout | The request has failed due to service timeout |
| "6" | "E006: Unexpected internal error" | There is internal error, please contact our tech support team for assistance. |
| "5" | "E005: Invalid Request Format" | There is something wrong in the request format, please check the detailed error message |
| IllegalAccessToken | The specified access token is invalid or expired | Your access token is either expired or invalid. Pleaes refresh your access token and contact our tech support team to renew the token. |
| 4136 | SYSTEM\_BUSY | System is busy,try later |
| 4137 | SYSTEM\_TIMEOUT | System is timeout,try later |
| 4138 | SYSTEM\_EXCEPTION | System is under maintenance |
| 4139 | UNKNOWN\_ERROR | System is upgrading... |
| 4140 | CATEGORY\_CANNOT\_FIND | The specified category cannot be found |
| 4141 | CATEGORY\_NOT\_PERMITTED | The category is not permitted |
| 4142 | CATEGORY\_IS\_INACTIVE | The category is inactive |
| 4143 | NO\_TARGET\_USER\_BIND | Do not have access to target market |
| 4144 | LOCAL\_CATEGORY\_CANNOT\_FIND | The specified local category cannot be found |
| 4145 | GLOBAL\_PRODUCT\_CANNOT\_FIND | The global product cannot be found |
| 4146 | LOCAL\_PRODUCT\_CANNOT\_FIND | Cannot find product at local venture: %s |
| 4147 | LOCAL\_SKU\_CANNOT\_FIND | Cannot find any SKUs at local venture: %s |
| 4148 | LOCAL\_PRODUCT\_HAS\_NOT\_BEEN\_SYNCED | This product has not been published to venture: %s |
| 4149 | LOCAL\_SKU\_HAS\_NOT\_BEEN\_SYNCED | The SKUs is still synchronizing to venture: %s... |
| 4150 | PRODUCT\_DOES\_NOT\_BELONG\_TO\_USER | Target product does not belong to the user account |
| 4151 | DAO\_NOT\_SUPPORT\_BIZ\_TYPE | The biz type hasn't been supported |
| 4152 | DAO\_GLOBAL\_PDT\_NOT\_FOUND | Can not find the global product in the data base |
| 4153 | DAO\_GLOBAL\_SKU\_NOT\_FOUND | Can not find the global SKUs in the data base |
| 4154 | DAO\_LOCAL\_ITEM\_RELATION\_NOT\_FOUND | Can not find the local items in the data base |
| 4155 | DAO\_LOCAL\_SKU\_RELATION\_NOT\_FOUND | Can not find the local skus in the data base |
| 4156 | NO\_CREATE\_PRODUCT\_PERMISSION | You do not have permission to create product |
| 4157 | PRICE\_GENERAL\_TOO\_LOW\_ERROR | Retail price & Sale price at %s cannot be lower than %s %s |
| 4158 | PRICE\_GENERAL\_TOO\_HIGH\_ERROR | Retail price & Sale price at %s cannot be higher than %s %s |
| 4159 | PRICE\_GENERAL\_DISCOUNT\_TOO\_HIGH\_ERROR | Sale price discount at %s should not be more than or equal to %s. Currently, price is %s %s, and sale price is %s %s |
| 4160 | PRICE\_GENERAL\_DISCOUNT\_TOO\_LOW\_ERROR | Sale price discount at %s should not be less than or equal to %s. Currently, price is %s %s, and sale price is %s %s |
| 4161 | PB\_SALE\_PROP\_RENDER\_ILLEGAL\_SALE\_PROP | The product has illegal sale properties |
| 4162 | PB\_SKU\_DESC\_RENDER\_ILLEGAL\_SKU\_DESC | The product has illegal sku description properties |
| 4163 | PB\_VENTURE\_NO\_VENTURE\_SELECT | No venture has been selected |
| 4164 | PB\_VENTURE\_MY\_NOT\_PUBLISHED | Malaysia venture is a must for Cross-Border publishing |
| 4165 | PB\_NAME\_NAME\_CANNOT\_BE\_NULL | Title cannot be empty |
| 4166 | PB\_NAME\_NAME\_CANNOT\_BE\_TOO\_LONG | Title cannot be longer than %d |
| 4167 | PB\_NAME\_NAME\_TRAN\_TOO\_LONG | publish failed cause by title translation words overflow |
| 4168 | PB\_BRAND\_ILLEGAL | The brand is invalid |
| 4169 | PB\_DETAIL\_ATTRIBUTE\_REQUIRED | This attribute cannot be empty |
| 4170 | PB\_SHORTDESC\_REQUIRED | Highlights cannot be empty |
| 4171 | PB\_DETAIL\_LENGTH\_ERROR | This attribute cannot be longer than 255 characters |
| 4172 | PB\_SKU\_PROP\_REQUIRED | This attribute cannot be empty |
| 4173 | PB\_NO\_PROPER\_SKU | No proper sku found |
| 4174 | PB\_NO\_PC\_DECO | PC decoration cant be empty |
| 4175 | PB\_NO\_WIRELESS\_DECO | wireless decoration cant be empty |
| 4176 | PB\_IMG\_CANNOT\_BE\_EMPTY | Please upload at least one image for every SKU |
| 4177 | PB\_IMG\_URL\_INVALID | the img url is invalid |
| 4178 | PB\_IMG\_CANNOT\_FETCH | the img could not be fetch caused by network reason or firewall,push the img to where we can access and download |
| 4179 | PB\_SALE\_PROP\_SUBMIT\_SALE\_PROP\_CANNOT\_BE\_EMPTY | The sale property cannot be empty |
| 4180 | PB\_SALE\_PROP\_SUBMIT\_SALE\_PROP\_ILLEGAL\_VAL | The sale property value is invalid |
| 4181 | PB\_SALE\_PROP\_SUBMIT\_SALE\_PROP\_INVALID\_INPUT\_VAL | The sale property value cannot contain illegal character \\"\*^~<>/|\\ |
| 4182 | PB\_SALE\_PROP\_SUBMIT\_SALE\_PROP\_TOO\_LONG\_INPUT\_VAL | The sale property value cannot be longer than 255 characters |
| 4183 | PB\_SALE\_PROP\_SUBMIT\_TOO\_MORE\_SKU | The SKUs are too more for these sale properties |
| 4184 | PB\_CURRENCY\_CANNOT\_BE\_EMPTY | The SKU does not have legal currency |
| 4185 | PB\_ORIGIN\_PRICE\_CANNOT\_BE\_EMPTY | The SKU's original price is empty |
| 4186 | PB\_ORIGIN\_SALE\_PRICE\_CANNOT\_BE\_EMPTY | The SKU's original sale price is empty |
| 4187 | PB\_ORIGIN\_SALE\_PRICE\_CANNOT\_BE\_HIGH | The SKU's sale price must be lower than original price |
| 4188 | PB\_MARKET\_PRICE\_CANNOT\_BE\_EMPTY | The %s SKU's original price is empty |
| 4189 | PB\_MARKET\_SALE\_PRICE\_CANNOT\_BE\_EMPTY | The %s SKU's original sale price is empty |
| 4190 | PB\_MARKET\_SALE\_PRICE\_TOO\_HIGH | The %s SKU's sale price must be lower than retail price |
| 4191 | PB\_STOCK\_CANNOT\_BE\_EMPTY | The %s SKU's stock cannot be empty |
| 4192 | PB\_STOCK\_INVALID | The %s SKU's stock is invalid |
| 4193 | PB\_WARRANTY\_INVALID | Warranty Period has not been selected while Warranty Type is not \\"No warranty\\" |
| 4194 | PB\_SELLER\_SKU\_EXIST | Seller SKU exists |
| 4195 | PB\_SELLER\_SKU\_LENGTH\_ERROR | Seller SKU should be 1-50 characters |
| 4196 | PB\_SELLER\_SKU\_DUPLICATE | Seller SKU duplicates |
| 4197 | PB\_SELLER\_SKU\_INVALID | Seller SKU must consist of \\"A-Z\\", \\"a-z\\", \\"0-9\\", \\"-\\", \\"\_\\" |
| 4198 | PB\_SELLER\_SKU\_CANNOT\_BE\_REVISED | Seller SKU cannot be revised, it should be kept as: %s |
| 4199 | PB\_PACKAGE\_UNMATCHED | Package parameters should be all the same for one product |
| 4200 | IMAP\_BRAND\_NOT\_MATCHED | Brand doesn't match at local venture |
| 4201 | IMAP\_SALE\_PROP\_UNMATCHED | Sale properties unmatched |
| 4202 | IMAP\_SALE\_PROP\_ERR\_MATCHED | Sale properties error matched |
| 4203 | IMAP\_DEST\_SALE\_PROP\_IS\_SPU | Dest sale property is SPU property |
| 4204 | IMAP\_SALE\_PROP\_VAL\_ERR\_MATCHED | Sale properties values error matched |
| 4205 | INVALID\_IMAGE\_FORMAT | Invalid image format |
| 4206 | INVALID\_IMAGE\_DIMENSION | Image resolution shoule be from 330 \* 330 to 5000 \* 5000 |
| 4207 | IMPORT\_SELLER\_SKU\_EMPTY | Import empty seller sku |
| 4208 | IMPORT\_SELLER\_SKU\_INVALID | Import invalid seller sku |
| 4209 | INVALID\_CATEGORY | Invalid category |
| 4210 | FAIL\_TO\_GET\_CATEGORY\_ID | Fail to get category id |
| 4211 | HAZMAT\_WARN | HAZMAT\_WARN |
| 4212 | PDT\_LIMIT\_REACH | Your product list has reached the limit at %s |
| 4213 | MIGRAGE\_IMAGE\_FAILED | Fail to migrate |
| 4214 | PG\_NOT\_PERMIT | QC rule checking failed |
| 4215 | DECO\_CREATE\_ERROR | Fail to create decoration |
| 4216 | DECO\_NO\_TARGET\_USER\_BIND | cant find target user |
| 4217 | DECO\_SOURCE\_QUERY\_ERROR | Fail to query for source decoration |
| 4218 | DECO\_TRANSLATE\_ERROR | Decoration translation error |
| 4219 | DECO\_SYNC\_ERROR | Decoration sync error |
| 4220 | TRANSLATE\_OVER\_FLOW | Translate over flow,and will retry auto |
| 4221 | ITEM\_NEVER\_PUBLISH\_SUCCESSED | item have not pulish success to market before |
| 4222 | NO\_SKU\_COULD\_BE\_UPDATE | no sku could be update |
| 4223 | PRODUCT\_NUM\_REACH\_LIMITATION | Seller's online product quantity has reach limit in all site |
| 4224 | SELLER\_STATUS\_INVALID | Seller's account are inactive or not verified in all publish venture. |
| 4225 | PRICE\_NOT\_VALID | Please review product price to ensure accuracy. |
| 4226 | SELLER\_PUNISHMENT\_INVALID | Seller is under punishment of blocking edit venture. |
| 4227 | SKU\_IMAGE\_INVALID | If you upload a sku picture, then all sku must be uploaded. |
| 4228 | PRODUCT\_IMAGE\_INVALID | Product or sku Image is missing for live product |
| 4229 | PROHIBITED\_BRAND | Product has problem with brand. |
| 4230 | PROHIBITED\_KEYWORD | Product content exist keyword. |
| 4223 | Seller's online product quantity has reach limit in all publish venture. | The number of products with active status in GPS has exceeded the limit. Please call updateProductStatus API to drop the product or call deleteMerchantProduct API to delete the product with active status and then call this API to publish a new product. |
| 500 | Create product failed | This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error. |
| 5 | Invalid Request Format | Please refer to the “CreateGlobalProduct payload and parameter description” document to check if the payload in your request follows the requirements and format in the document. |
| 4214 | Create product failed | This is a generalized error, this error indicates that there is a sales/logistics policy in your item that does not comply with the current country or region, please check thedetail field for details. |
| 4194 | Seller SKU exists. | This seller sku already exists in the current store, please change to another seller sku to post the item. |
| 4178 | Fail to migrate image | Please do not include external image links in the payload, use the MigrateImage API to migrate the images to Lazada image links first. |
| 4169 | This attribute cannot be empty. | Mandatory attributes are not used in the Payload, call the GetCategoryAttributes API to check if you missed any attributes with an is\_mandatory field value of 1. |
| 4159 | Create product failed | Local price limit. sale price and sepcial price can't be less than the specified percentage, please check the detail field for more information. |
| 309 | Video id status is not audit success | Only videos that are in the AUDIT SUCCESSS state can be used in payload. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/global/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/product/global/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/global/create");
request.addApiParameter("payload", "<?xml version=\"1.0\" encoding=\"utf-8\"?><Request><Product><PrimaryCategory>11069</PrimaryCategory><SPUId/><AssociatedSku/><AutoAllocateStock>false</AutoAllocateStock><Ventures><Venture>MY</Venture><Venture>SG</Venture><Venture>TH</Venture></Ventures><Images><Image>http://imgsrc.baidu.com/imgad/pic/item/37d12f2eb9389b508e646c9b8f35e5dde6116e64.jpg</Image><Image>http://imgsrc.baidu.com/imgad/pic/item/37d12f2eb9389b508e646c9b8f35e5dde6116e64.jpg</Image></Images><Attributes><name>api create product test sample</name><video>video id</video><short_description>This is a nice product</short_description><description>This is a nice product description</description><brand>Remark</brand><model>asdf</model><kid_years>Kids (6-10yrs)</kid_years><package_length>11</package_length><package_height>22</package_height><package_weight>1</package_weight><package_width>44</package_width><package_content>this is what's in the box</package_content></Attributes><Skus><Sku><SellerSku>api-create-test1-14</SellerSku><color_family>Green</color_family><size>40</size><quantity>120</quantity><sg_retail_price>388.50</sg_retail_price><sg_sales_price>308.50</sg_sales_price><retail_price>388.50</retail_price><sales_price>308.50</sales_price><tax_class>default</tax_class><Images><Image>http://imgsrc.baidu.com/imgad/pic/item/37d12f2eb9389b508e646c9b8f35e5dde6116e64.jpg</Image><Image>http://imgsrc.baidu.com/imgad/pic/item/37d12f2eb9389b508e646c9b8f35e5dde6116e64.jpg</Image></Images></Sku></Skus></Product></Request>");
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
    "sku_list": [
      {
        "seller_sku": "api-create-test-111"
      }
    ]
  },
  "request_id": "0ba2887315178178017221014"
}
```
