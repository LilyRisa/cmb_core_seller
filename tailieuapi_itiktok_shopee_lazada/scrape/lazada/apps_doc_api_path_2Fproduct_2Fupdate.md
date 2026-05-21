# POSTUpdateProduct

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fupdate
> Scraped: 2026-05-20T22:52:59.489Z

---

Latest update2022-07-29 12:51:21

32601

UpdateProduct

POST

/product/update

Authorization Required

Description:Use this API to update attributes or SKUs of an existing product. if need update inventory, offline, price, not recommended to use this API. The iteration 25/6/2020 Updated for DBS changes. Refer to Input Parameters Payload

## Service Endpoints

| 
Region

 | 

Endpoint

 |
| --- | --- |
| 

Vietnam

 | 

https://api.lazada.vn/rest

 |
| 

Singapore

 | 

https://api.lazada.sg/rest

 |
| 

Philippines

 | 

https://api.lazada.com.ph/rest

 |
| 

Malaysia

 | 

https://api.lazada.com.my/rest

 |
| 

Thailand

 | 

https://api.lazada.co.th/rest

 |
| 

Indonesia

 | 

https://api.lazada.co.id/rest

 |

Did this chapter help you?

YesNo

## Common Parameters

| 
Name

 | 

Type

 | 

Required or not

 | 

Description

 |
| --- | --- | --- | --- |
| 

app\_key

 | 

String

 | 

Yes

 | 

Unique app ID issued by LAZADA Open Platform console when you apply for an app category

 |
| 

timestamp

 | 

String

 | 

Yes

 | 

The time stamp of the request e.g. 1517820392000 (which translates to 5 February 2018 08:46:32) with less than 7200s difference from UTC time

 |
| 

access\_token

 | 

String

 | 

Yes

 | 

API interface call credentials

 |
| 

sign\_method

 | 

String

 | 

Yes

 | 

The HMAC hash algorithm you are using to calculate your signature

 |
| 

sign

 | 

String

 | 

Yes

 | 

Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details)

 |

Did this chapter help you?

YesNo

## Parameters

| 
Name

 | 

Type

 | 

Required or not

 | 

Description

 |
| --- | --- | --- | --- |
| 

payload

 | 

String

 | 

Yes

 | 

[Parameter description](https://open.lazada.com/apps/doc/doc?nodeId=30715&docId=121228)

 |

Did this chapter help you?

YesNo

## Response Parameters

| 
Name

 | 

Type

 | 

Description

 |
| --- | --- | --- |
| 

data

 | 

Object

 | 

Response body

 |
| 

variation

 | 

Object

 | 

self define attributes

 |
| 

Variation1

 | 

Object

 | 

self define attributes

 |
| 

name

 | 

String

 | 

self define attributes

 |
| 

has\_image

 | 

Boolean

 | 

self define attributes

 |
| 

customize

 | 

Boolean

 | 

self define attributes

 |
| 

options

 | 

String\[\]

 | 

self define attributes

 |
| 

Variation2

 | 

Object

 | 

self define attributes

 |
| 

name

 | 

String

 | 

self define attributes

 |
| 

has\_image

 | 

Boolean

 | 

self define attributes

 |
| 

customize

 | 

Boolean

 | 

self define attributes

 |
| 

options

 | 

String\[\]

 | 

self define attributes

 |
| 

Variation3

 | 

Object

 | 

self define attributes

 |
| 

name

 | 

String

 | 

self define attributes

 |
| 

has\_image

 | 

Boolean

 | 

self define attributes

 |
| 

customize

 | 

Boolean

 | 

self define attributes

 |
| 

options

 | 

String\[\]

 | 

self define attributes

 |
| 

Variation4

 | 

Object

 | 

self define attributes

 |
| 

name

 | 

String

 | 

self define attributes

 |
| 

has\_image

 | 

Boolean

 | 

self define attributes

 |
| 

customize

 | 

Boolean

 | 

self define attributes

 |
| 

options

 | 

String\[\]

 | 

self define attributes

 |
| 

item\_status

 | 

String

 | 

The status of product updated, including Active, InActive, and Pending QC. if a product is in Pending status, it needs to be reviewed and will be processed within 24 hours.

 |

Did this chapter help you?

YesNo

## Error Code

| 
Error Code

 | 

Error Message

 | 

Solution

 |
| --- | --- | --- |
| 

1

 | 

E001: Parameter %s is mandatory

 | 

The parameter is mandatory but not specified.

 |
| 

5

 | 

E005: Invalid Request Format

 | 

The request format is not valid.

 |
| 

6

 | 

E006: Unexpected internal error

 | 

Unexpected internal error.

 |
| 

30

 | 

E030: Empty Request

 | 

The request URL is not complete.

 |
| 

201

 | 

E201: %s Invalid CategoryId

 | 

The specified category ID is not valid.

 |
| 

202

 | 

E202: %s Invalid SPUId

 | 

The specified SPU ID is not valid.

 |
| 

501

 | 

E501: Update product failed

 | 

Failed to update the product.

 |
| 

512

 | 

E512: BIZ\_CHECK\_MANGROVE\_RULE\_QC

 | 

The request failed because the category was banned

 |
| 

901

 | 

E901: The request is too frequent, or the requested functionality is temporarily disabled.

 | 

Failed to return the requested data due to high calling frequency or disabled functionality. Please try again later.

 |
| 

1000

 | 

Internal Application Error

 | 

Internal system error.

 |
| 

4104

 | 

BIZ\_CHECK\_PRICE\_PRECISION\_INVALID

 | 

Price accuracy check failed

 |
| 

4105

 | 

BIZ\_CHECK\_SELLER\_SKU\_DUPLICATE

 | 

SellerSku repeat

 |
| 

4106

 | 

CHK\_CATPROP\_CPV\_INPUT\_SIZE\_LIMIT

 | 

Item customization attributes exceeded the limit

 |
| 

4107

 | 

CHECK\_CAT\_PROP\_INVALID\_NUMBER

 | 

The category attribute value is invalid

 |
| 

4108

 | 

CHK\_BASIC\_REQUIRED

 | 

Basic attributes Mandatory verification

 |
| 

4109

 | 

CHK\_SKU\_PROPS\_NOT\_MATCH\_SALE\_PROP

 | 

Sku sales attributes do not match

 |
| 

4110

 | 

BIZ\_CHECK\_CAT\_PROP\_MANDATORY

 | 

Category attribute This parameter is mandatory

 |
| 

4111

 | 

CHK\_CATPROP\_CPV\_TEXT\_REPEAT

 | 

Category attribute content repeats

 |
| 

4112

 | 

CHK\_SKU\_PROPS\_DUPLICATE

 | 

Duplicate Sku attributes

 |
| 

4113

 | 

CHK\_SKU\_PROPS\_NOT\_IDENTICAL

 | 

Sales attribute is not filled in

 |
| 

4114

 | 

BIZ\_CHECK\_PRICE\_SAMPLE\_NON\_ZERO

 | 

The sample price is 0

 |
| 

4115

 | 

CHK\_CATPROP\_CPV\_NOT\_ENUM

 | 

The CPV attribute is not one of the options provided by the category

 |
| 

4116

 | 

BIZ\_CHECK\_MAIN\_IMAGE\_DUPLICATE

 | 

Repeat check of master diagram

 |
| 

4117

 | 

BIZ\_CHECK\_SPECIAL\_PRICE\_FROM\_DATE\_AFTER\_TO\_DATE

 | 

Special offer date check

 |
| 

4118

 | 

BIZ\_CHECK\_PRICE\_IS\_ZERO

 | 

Price is not 0 check

 |
| 

4119

 | 

BIZ\_CHECK\_SPECIAL\_PRICE\_RATE\_OUT\_OF\_RANGE

 | 

Special price range check

 |
| 

4120

 | 

CHK\_CATPROP\_CPV\_MAX\_LEGNTH

 | 

Verify the maximum CPV value of a category

 |
| 

4121

 | 

BIZ\_CHECK\_SPECIAL\_PRICE\_PRECISION\_INVALID

 | 

Special accuracy check does not pass

 |
| 

4122

 | 

BIZ\_CHECK\_VIRTUAL\_BUNDLE\_SKU\_SUB\_OVER\_LIMIT

 | 

virtual bundle sku relation skuc over limit

 |
| 

4123

 | 

BIZ\_CHECK\_MANGROVE\_RULE

 | 

Restricted publication check

 |
| 

4124

 | 

BIZ\_CHECK\_MANGROVE\_RULE\_QC

 | 

MANGROVE rule verification

 |
| 

4125

 | 

THD\_IC\_F\_IC\_DOMAIN\_PROPERTY\_002

 | 

IC Verification category Attribute This parameter is mandatory

 |
| 

4126

 | 

THD\_IC\_F\_IC\_INFRA\_PRODUCT\_036

 | 

SellerSku repeat

 |
| 

4127

 | 

THD\_IC\_F\_IC\_SCENE\_PUBLISH\_012

 | 

ProductId repeat

 |
| 

4128

 | 

THD\_IC\_F\_IC\_DOMAIN\_ACTOR\_006

 | 

Seller lock cannot be edited

 |
| 

4129

 | 

BIZ\_CHECK\_PROP\_SPECIAL\_CHAR

 | 

Containssymbol/characterthatisnotallowed:"<".Pleaseremovethenre-upload

 |
| 

4130

 | 

BIZ\_CHECK\_OFFICIAL\_STORE\_BRAND\_UNAUTHORIZED

 | 

Uncertified brand

 |
| 

4131

 | 

BIZ\_CHECK\_CAT\_PROP\_SENSITIVE\_WORDS

 | 

description has sensitive words New brand

 |
| 

4132

 | 

Invalid Request Format

 | 

Invalid Request Format

 |
| 

4133

 | 

Invalid variation

 | 

Invalid variation

 |
| 

4134

 | 

CHK\_CATEGORY\_ID\_NOT\_LEAF\_CATEGORY

 | 

The category Id is Invalid

 |
| 

4135

 | 

THD\_IC\_ERR

 | 

IC service error reported

 |
| 

4136

 | 

SELLER\_SKU\_NOT\_FOUND

 | 

Seller Sku is not found

 |
| 

4137

 | 

ITEM\_NOT\_FOUND

 | 

item not found

 |
| 

4138

 | 

BIZ\_CHECK\_EXIST\_OUTER\_IMAGE

 | 

The picture exists in the outer chain

 |
| 

4139

 | 

BIZ\_CHECK\_MAIN\_IMAGE\_REQUIRE

 | 

Main image is require

 |
| 

4140

 | 

CHK\_ENUM\_PROP\_VALUE\_NOT\_IN\_OPTION

 | 

Class does not have this attribute

 |
| 

4141

 | 

THD\_IC\_ERR\_F\_IC\_INFRA\_PRODUCT\_036

 | 

SellerSku repeat

 |
| 

4142

 | 

THD\_BRAND\_ID\_IS\_NOT\_VALID\_IN\_CATEGORY

 | 

This brand is not valid in the category package

 |
| 

4143

 | 

BIZ\_CHECK\_SALEPROP\_ATTRIBUTE\_INVALID

 | 

The selling attributes are not defined in the variation

 |
| 

4144

 | 

BIZ\_CHECK\_SKU\_NOT\_CONTAIN\_SALEPROP

 | 

The sku does not contain the saleProp tag

 |
| 

4145

 | 

BIZ\_CHECK\_SALEPROP\_AND\_OLD\_PARAM\_REPEAT

 | 

You can't put sales properties in both saleProp and sku

 |
| 

4146

 | 

BIZ\_CHECK\_SALEPROP\_NOT\_SUPPORT\_THUMBNAIL

 | 

Thumbnails are not supported for this sale attribute

 |
| 

10002

 | 

Incorrect/missing/unavailable product attributes

 | 

Please check the details in the API response in order to confirm the properties that are causing the problem and the cause.And according to the content of the error, correct the attributes, options or delete the attributes without permission.

 |
| 

901

 | 

Limit service request speed in server side temporarily.

 | 

API level QPS limiting flow, please retry in the next second when you encounter this error.

 |
| 

10006

 | 

the control price is not pass

 | 

Global Plus products have a price control logic: the price limit is: sku without postal price ≤ (pre-upgrade retail price - LGS shipping cost) + (LGS shipping cost - Global Plus shipping cost) \* 50%; beyond the upper limit, it is impossible to adjust the sku without postal price

 |
| 

501

 | 

Update product failed

 | 

This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error.

 |
| 

10002

 | 

System error update fail

 | 

Please check the details in the API response in order to confirm the properties that are causing the problem and the cause.And according to the content of the error, correct the attributes, options or delete the attributes without permission.

 |
| 

10006

 | 

the control price is not pass

 | 

Global Plus products have a price control logic: the price limit is: sku without postal price ≤ (pre-upgrade retail price - LGS shipping cost) + (LGS shipping cost - Global Plus shipping cost) \* 50%; beyond the upper limit, it is impossible to adjust the sku without postal price

 |
| 

10006

 | 

the control price is not pass

 | 

Global Plus products have a price control logic: the price limit is: sku without postal price ≤ (pre-upgrade retail price - LGS shipping cost) + (LGS shipping cost - Global Plus shipping cost) \* 50%; beyond the upper limit, it is impossible to adjust the sku without postal price

 |
| 

10006

 | 

the control price is not pass

 | 

Global Plus products have a price control logic: the price limit is: sku without postal price ≤ (pre-upgrade retail price - LGS shipping cost) + (LGS shipping cost - Global Plus shipping cost) \* 50%; beyond the upper limit, it is impossible to adjust the sku without postal price

 |
| 

10002

 | 

System error update fail

 | 

Please check the details in the API response in order to confirm the properties that are causing the problem and the cause.And according to the content of the error, correct the attributes, options or delete the attributes without permission.

 |
| 

10002

 | 

System error update fail

 | 

Please check the details in the API response in order to confirm the properties that are causing the problem and the cause.And according to the content of the error, correct the attributes, options or delete the attributes without permission.

 |
| 

10002

 | 

System error update fail

 | 

Please check the details in the API response in order to confirm the properties that are causing the problem and the cause.And according to the content of the error, correct the attributes, options or delete the attributes without permission.

 |
| 

4137

 | 

The item id entered in the request does not exist on the current country and store, please call the GetProducts/GetProductItem API to query for the correct item id.

 | 

The item id in the request does not belong to the current store or country, please call the GetProduct/GetProductItem API to check the item id in the response again.

 |
| 

501

 | 

Update product failed

 | 

This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error.

 |
| 

501

 | 

Update product failed

 | 

This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error.

 |
| 

4218

 | 

Update product failed

 | 

The product has been penalized down to prohibit editing, if the seller has a problem with this product, please let the seller in the seller center to seller customer service or appeal.

 |
| 

4137

 | 

The item id entered in the request does not exist on the current country and store, please call the GetProducts/GetProductItem API to query for the correct item id.

 | 

The item id entered in the request does not exist on the current country and store, please call the GetProducts/GetProductItem API to query for the correct item id.

 |
| 

4137

 | 

The item id entered in the request does not exist on the current country and store, please call the GetProducts/GetProductItem API to query for the correct item id.

 | 

The item id entered in the request does not exist on the current country and store, please call the GetProducts/GetProductItem API to query for the correct item id.

 |
| 

10006

 | 

the control price is not pass

 | 

Global Plus products have a price control logic: the price limit is: sku without postal price ≤ (pre-upgrade retail price - LGS shipping cost) + (LGS shipping cost - Global Plus shipping cost) \* 50%; beyond the upper limit, it is impossible to adjust the sku without postal price

 |
| 

4137

 | 

The item id entered in the request does not exist on the current country and store, please call the GetProducts/GetProductItem API to query for the correct item id.

 | 

The item id entered in the request does not exist on the current country and store, please call the GetProducts/GetProductItem API to query for the correct item id.

 |
| 

SellerNotActive

 | 

Seller not active,please check seller status

 | 

The seller's store status is inactive can not call the commodity API, you can call the GetSeller API and based on the Status field to confirm the current status of the store, if the seller has questions about this status, please want to seller center seller customer service consulting how to modify the status.

 |
| 

901

 | 

Limit service request speed in server side temporarily.

 | 

API level QPS limiting flow, please retry in the next second when you encounter this error.

 |
| 

501

 | 

Update product failed

 | 

This error code is an overview error code and cannot be used to determine the detailed cause of the error, please check the detail field in the API response to understand the SKU where the error occurred and the cause of the error.

 |
| 

4218

 | 

Update product failed

 | 

The product has been penalized down to prohibit editing, if the seller has a problem with this product, please let the seller in the seller center to seller customer service or appeal.

 |
| 

4216

 | 

skuId is a mandatory field and must be filled in.

 | 

Sku id is a mandatory parameter when updating a product.

 |
| 

4155

 | 

Update product failed

 | 

The product is locked by the penalty does not support the update, please create a new product or appeal in the seller center

 |
| 

4152

 | 

THD\_INVENTORY\_ERR\_INV\_PARAM\_ILLEGAL:illegal parameter:quantity

 | 

Negative numbers are not allowed in the quantity field.

 |
| 

4137

 | 

The item id entered in the request does not exist on the current country and store, please call the GetProducts/GetProductItem API to query for the correct item id.

 | 

The item id entered in the request does not exist on the current country and store, please call the GetProducts/GetProductItem API to query for the correct item id.

 |
| 

4115

 | 

Attribute value that you input is not included in the dropdown list given. Please select from dropdown to avoid error

 | 

A value in a single or multiple select attribute does not exist in the Option provided by Lazada, call the GetCategoryAttributes API to check the attribute in question and verify the Otpion.

 |
| 

4113

 | 

CHK\_SKU\_PROPS\_NOT\_IDENTICAL

 | 

The custom variant attribute you are using is not declared in the variation tag, so please declare the variant attribute first according to case3 in the UpdateProduct section of the Custom sales attributes document.

 |
| 

4108

 | 

CHK\_BASIC\_REQUIRED

 | 

The current product may have unfilled mandatory attributes due to category update, please call GetCategoryAttributes API first to query the latest category attribute list and confirm the new mandatory attributes and add the attributes in the payload.

 |
| 

209

 | 

Invalid variation

 | 

The number of variants in the payload exceeds the upper limit or does not meet the requirements, please check the message in the message to understand the detailed reasons and modify the payload.

 |
| 

1001

 | 

The parameters are not in JSON format

 | 

Make sure that your payload is JSON compliant, that the attributes and structure are filled out correctly according to the documentation and that all field values in the SKU are of string type.

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/update)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/product/update

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/update");
request.addApiParameter("payload", "<?xml version=\"1.0\" encoding=\"UTF-8\" ?> <Request>   <Product>     <ItemId>234234234</ItemId>     <Attributes>       <name>api update product sample</name>       <short_description>This is an amazing product</short_description>       <delivery_option_sof>Yes</delivery_option_sof>       <name_engravement>Yes</name_engravement>       <gift_wrapping>Yes</gift_wrapping> <!--should be set as \u2018Yes\u2019 only for products to be delivered by seller-->     </Attributes>     <Skus>       <Sku>         <SkuId>234</SkuId>         <coming_soon>2024-11-11 00:00:00</coming_soon>         <delay_delivery_days>20</delay_delivery_days>         <SellerSku>api-create-test-1</SellerSku>         <quantity>88</quantity>         <price>350</price>         <package_length>12</package_length>         <package_height>23</package_height>         <package_weight>34</package_weight>         <package_width>45</package_width>         <Images></Images>       </Sku>       <Sku>         <SkuId>235</SkuId>         <SellerSku>api-create-test-2</SellerSku>         <quantity>44</quantity>         <price>488.88</price>         <package_length>10</package_length>         <package_height>21</package_height>         <package_weight>32</package_weight>         <package_width>43</package_width>         <package_content>this is what's in the box, update</package_content>         <Images>           <Image>http://sg.s.alibaba.lzd.co/original/59046bec4d53e74f8ad38d19399205e6.jpg</Image>           <Image>http://sg.s.alibaba.lzd.co/original/179715d3de39a1918b19eec3279dd482.jpg</Image>           <Image>http://sg.s.alibaba.lzd.co/original/e2ae2b41afaf310b51bc5764c17306cd.jpg</Image>         </Images>       </Sku>     </Skus>     <trialProduct>false</trialProduct>   </Product> </Request>");
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
    "item_status": "Active",
    "variation": {
      "Variation1": {
        "has_image": "false",
        "name": "color_family",
        "options": [],
        "customize": "true"
      },
      "Variation2": {
        "has_image": "false",
        "name": "color_family",
        "options": [],
        "customize": "true"
      },
      "Variation3": {
        "has_image": "false",
        "name": "color_family",
        "options": [],
        "customize": "false"
      },
      "Variation4": {
        "has_image": "false",
        "name": "color_family",
        "options": [],
        "customize": "false"
      }
    }
  },
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
