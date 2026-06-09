---
title: "(Deprecated) Create Dynamic Showcase Ads"
doc_id: 1739470947488770
path: "Use Cases / Campaign creation / (Deprecated) Create Dynamic Showcase Ads / (Deprecated) Create Dynamic Showcase Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Create Dynamic Showcase Ads

To create Dynamic Showcase Ads, please follow the steps:
1. Create a catalog.
2. Upload products to the catalog.
3. Optional: Create a product set to filter products that you want to promote.
4. Bind event sources to the catalog.
5. Create a DSA campaign.
6. Create an ad group.
7. Create an ad.

> <span style="color:DodgerBlue">**Note**</span><br>Starting from Feb.16, 2023, you can no longer create Dynamic Showcase Ads.  To ensure a smooth API integration, we recommend you migrate to [Video Shopping Ads](https://ads.tiktok.com/marketing_api/docs?id=1741942291730434&rid=tv5sdeafqp)  under Product Sales objective. The modification of existing Dynamic Showcase Ads through API will be supported till the end of March, and may be extended on a case-by-case basis. If you need extra transition time, please contact your TikTok representatives.

# 1. Create a catalog

A catalog is an asset that allows you to store information about the products you want to promote on TikTok. When you create a catalog you can create ads that showcase specific products with up-to-date information about each product, like price, availability, sizes, and more.

## Request

**Address**:  https://business-api.tiktok.com/open_api/v1.3/catalog/create/

**Method**: POST

**Request Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request content type. <br>Accepted Value: `"application/json"`.  |
```

**Request parameters**

``` xtable
| Field | Type | Description |
|--- |--- |--- |
| bc_id {Required} | string | BC ID. |
| name {Required} | string | Catalog name. |
| catalog_type {Required} | string | Catalog type. Currently, only `ECOM` (e-commerce) is supported. |
| catalog_conf {Required} | object | Catalog configuration information. |
#|region_code {Required} | string | The targeted region. See [Appendix - Location code](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010). |
#|currency {Required} | string | Currency. For supported currencies, see [Appendix - Currency](https://ads.tiktok.com/marketing_api/docs?id=1737585839634433). |
#|channel |string| Channel for creating the catalog. Enum values: `PARTNER`: Partners; `CLIENT`: Direct advertisers. |
```


### Example
```xcodeblock
(code)
{
   "bc_id": 1659674186450949,
   "name": "Demo catalog",
   "catalog_type": "ECOM",
   "catalog_conf": {
       "country": "US",
       "currency": "USD"
    }
}
(/code)
```

## Response
```xcodeblock
(code)
{
    "message": "OK",
    "code": 0,
    "data": {
        "catalog_id": "6942905196443895554",
    },
    "request_id": "2021032322113001011515319002015A36"
}
(/code)
```

**Result**
<br>
The catalog should now be visible in the Product Catalog section in the Assets tab of your TikTok Ads Manager account.

# 2. Upload products to the catalog

After the catalog is created, you can upload products. You can add products to the catalog by either connecting a feed or by a batch product upload. 

If you have a large quantity of products to upload or you want to upload the full catalog of products, use a catalog feed. Only catalog feeds in CSV format are supported.

If you need to update the catalog frequently, use the batch upload endpoint (`/catalog/product/upload/`) instead. In this case, you need to include the product data in the payload.

In this guide, we'll use a catalog feed to upload products. Ensure you have uploaded your catalog feed CSV file to a location that is accessible on the Internet.

## Request

**Address**: https://business-api.tiktok.com/open_api/v1.3/catalog/product/file/

**Method**: POST

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
|Content-Type {Required}|string|Request content type. <br>Allowed value: `"application/json"`.  |
```

**Parameters**

Only required and relevant parameters are listed below. For the complete list of parameters, see [Upload product via file URL](https://ads.tiktok.com/marketing_api/docs?id=1740496787164161).

```xtable
| Field | Type | Description |
|--- |--- |--- |
|bc_id {Required}|string| Business Center ID. |
|catalog_id {Required}|string|Catalog ID. |
|feed_id|string|Feed ID.|
|file_url {Required}|string|The download address of the csv file. The number of product lines in the file should be less than 20 million. |
```

### Example

The format is "multipart/form-data".
> bc_id: 1659674186450949<br>
> catalog_id: 6942905196443895554<br>
> file_url: https://docs.google.com/spreadsheets/d/e/2PACX-1vSTEJI_xFDoV_zSCCafAfHgiuG0bP8Wre9zyVKvJKO3BXQ6rEJkxm309MMx1-LB6Hzk9cil2FCHiA8b/pub?output=csv

## Response
```xcodeblock
(code)
{
    "message": "OK",
    "code": 0,
    "data": {
        "feed_log_id": 478517
    },
    "request_id": "20210324195813010115153193168759A5"
}
(/code)
```

**Result**
<br>
Your added products should be visible in the Products section under **Assets** > **Catalog** in your TikTok Ads Manager account.

# 3. Optional: Create a product set

If you want to promote certain products but not the others, you can create a product set to include only these products, and then associate this product set with the ad group. 

## Request

**Address**

https://business-api.tiktok.com/open_api/v1.3/catalog/set/create/

**Method**: POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request content type. Allowed Value:"application/json"|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required}|string|Business Center ID|
|catalog_id {Required}|string|The ID of the catalog that you want to get product sets from.|
|product_set_name {Required}|string|The product set name. It must be less than 28 characters.|
|conditions|object|The filter conditions.|
#|and/or|object[]|The logical operator that will be applied to the filter conditions. You must specify either and or or. and stands for the AND logical operator. or stands for the OR logical operator.|
##|field|string|The field that the filter will be applied to. For enum values, see [Appendix - Product Set - Filter fields](https://ads.tiktok.com/marketing_api/docs?id=1740673108747265).|
##|operation|string|The filter operator. For enum values, see [Appendix - Product Set - Operators](https://ads.tiktok.com/marketing_api/docs?id=1740673108747265).|
##|value|object|The expected value.|
```

### Example

```xcodeblock
(code)
  {
      "bc_id": 1659674186450949,
      "catalog_id": 6942905196443895554,
      "name": "Test Product Set",
      "conditions": {
            "and": [
                {
                    "field": "gender",
                    "operation": "EQUAL",
                    "value": "MALE"
                },
                {
                    "field": "price",
                    "operation": "GTE",
                    "value": 50
                },
                {
                    "field": "brand",
                    "operation": "I_CONTAIN",
                    "value": ["TikTok", "xxxxx"]
                }
            ]
        }
   }
   (/code)
  ```
 
 ## Response
```xcodeblock
(code)
{
    "message": "OK",
    "code": 0,
    "data": {
        "count": 20,
        "id": 1566,
        "name": "Test Product Set"
    },
    "request_id": "20210228131918010231010121650793"
}
(/code)
```

# 4. Bind event sources with the catalog

After app or website events are bound with your catalog, ads will be dynamically shown to people based on how they interacted with your app or website.

Use the `/catalog/eventsource/bind/` endpoint to bind app or website events with your catalog. You need to specify `app_id` for app events or `pixel_code` for website events. 

## Request

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request content type. Allowed Value:"application/json"|
```
**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID |
|bc_id {Required}|string| Business Center ID|
|catalog_id {Required}|nstring Catalog ID|
|app_id| string| Mobile application ID. You can find the mobile app ID by navigating to Assets > Event > App Event in TikTok Ads Manager. You need to specify at least the mobile app ID or website Pixel code. You can specify both if you want to bind both events to the same catalog.|
|pixel_code|string| Website Pixel code. You can find the pixel code by navigating to Assets > Event > Website Pixel in TikTok Ads Manager. You need to specify at least the mobile app ID or website Pixel code. You can specify both if you want to bind both events to the same catalog.|
```

# 5.Create a DSA campaign

When creating a DSA campaign, ensure that the campaign objective is set to `CATALOG_SALES`.

## Request

**Address**  https://business-api.tiktok.com/open_api/v1.3/campaign/create/

**Method** POST

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request content type. Allowed Value:"application/json"|
```

**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|budget|number|Campaign Budget. For the minimum budget amounts, see [ Budget](https://ads.tiktok.com/help/article?aid=9688). |
|budget_mode {Required}|string|Budget mode. See [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114) for enum values.|
|campaign_name {Required}|string|Campaign name. Character limit is 512 and cannot contain emoji. Note: Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character.|
|objective_type {Required}|string|`CATALOG_SALES`.|
```

### Example
```xcodeblock
(code)
{
    "advertiser_id": 6927303038499569666,
    "budget": 50000,
    "budget_mode": "BUDGET_MODE_TOTAL",
    "campaign_name": "Demo Campaign",
    "objective_type": "CATALOG_SALES" 
}
(/code)
```

## Response

```xcodeblock
(code)
{
    "message": "OK",
    "code": 0,
    "data": {
        "campaign_id": 1695154014921745
        },
    "request_id": "2021032422284001011515319207A3ADE2"   
}
(/code)
```

**Result**
<br>
Your newly created campaign should be visible in the **Campaign** section of your ads manager account.

# 6. Create an ad group

Creating a DSA ad group follows a similar process as creating other ad groups. You need to pass in the `catalog_id` to the request. For a retargeting campaign, you need to set a value for the `shopping_ads_retargeting_type` parameter.

## Retargeting settings at the ad group level

DSA retargeting is configured via the `shopping_ads_retargeting_type` field and several other fields. Possible configurations are:

|Shopping Ads Retargeting Type|Description|Settings for other fields|
|---|---|---|
|OFF|No retargeting. When this type is selected, the ad will direct to the app install.|No need to specify values for `included_custom_actions`, `excluded_custom_actions`, and `shopping_ads_retargeting_actions_days`.|
|LAB1|Viewed product or added product to cart but not purchased|Need to pass in `shopping_ads_retargeting_actions_days`. No need to pass in the other two fields.|
|LAB2|Added product to cart but not purchased|Need to pass in `shopping_ads_retargeting_actions_days`. No need to pass in the other two fields.|
|LAB3|Custom combination|Pass in `included_custom_actions`, `excluded_custom_actions`. Specify the custom actions you want to include and exclude for `included_custom_actions` and `excluded_custom_actions` fields.|

## Request 

**Address** https://business-api.tiktok.com/open_api/v1.3/adgroup/create/

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request content type. Allowed Value:"application/json"|
```

**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id|string|Advertiser ID|
|campaign_id|string|Campaign ID|
|placement_type|string|PLACEMENT_TYPE_NORMAL|
|placements|string[]|["PLACEMENT_TIKTOK", "PLACEMENT_PANGLE]|
|promotion_type{+Conditional}|string|Promotion type and you can decide where you'd like to promote your products using this field. You need to specify the field when advertising objective (`objective_type`) for your campaign is NOT set as `REACH`, `VIDEO_VIEWS`, or `ENGAGEMENT`.<br>See [Enumeration - Promoted Object Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for details.|
|location_ids|string[]|List of codes for targeted countries or regions. The locations here must be consistent with the locations that you have configured for the catalog. See [Appendix-Location IDs](https://ads.tiktok.com/marketing_api/docs?id=1739311040498689) for details.|
|budget|number|Advertising budget|
|budget_mode|string|Ad budget type. Enum values: BUDGET_MODE_DAY,  BUDGET_MODE_TOTAL|
|schedule_type|string|Ad schedule type. Allowed values: SCHEDULE_FROM_NOW,SCHEDULE_START_END. For details,  see [Enumeration - Schedule type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
|schedule_start_time|datetime|Ad delivery start time (UTC+0), in the format of "YYYY-MM-DD HH:MM:SS". For example, "2017-01-01 00:00:00"|
|schedule_end_time|datetime|Ad delivery end time (UTC+0), in the format of "YYYY-MM-DD HH:MM:SS". For example, "2017-01-01 00:00:00" Only required when `schedule_type` is `SCHEDULE_START_END`.|
|optimization_goal|string|Optimization goal. See [Enumeration - Ad Management - Optimization Goal](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for details.|
|pacing|string|Ad delivery speed type. Allowed value include: `PACING_MODE_FAST`, `PACING_MODE_SMOOTH`|
|billing_event|string|Billing event. See [Enumeration -Billing event](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for optional values|
|catalog_id|string|Catalog ID|
|product_set_id|string|Product Set ID of the catalog, pass 0 means select all. The default value is 0.|
|shopping_ads_retargeting_type|string|The types of ad redirection in the catalog: `LAB1`，`LAB2`, `LAB3`,  `OFF`.|
|shopping_ads_retargeting_actions_days|number|The effective number of days for the ad redirection condition of the catalog . Only Required when `shopping_ads_retargeting_type` is `LAB1` or `LAB2`. Value range: 1, 2, 3, 7, 14, 30, 60, 90, 180.|
|skip_learning_phase|boolean|Whether to skip the learning phase. It only takes effect when the corresponding campaign `objective_type` is `CATALOG_SALES` and `bid_type` is `OCPM`. |
```

### Example

```xcodeblock
(code)
{
    "advertiser_id": 6927303038499569666,
    "campaign_id": 1695154014921745,
    "adgroup_name": "Demo Adgroup",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placement": ["PLACEMENT_TIKTOK"],
    "external_type": "APP_ANDROID",
    "location": [6252001],
    "budget": 50000,
    "budget_mode": "BUDGET_MODE_TOTAL",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "2021-10-10 00:00:00",
    "schedule_end_time": "2021-11-10 00:00:00",
    "optimize_goal": "CLICK",
    "external_action": "IN_APP_ORDER",
    "pacing": "PACING_MODE_SMOOTH",
    "billing_event": "CPC",
    "catalog_id": 6942905196443895554,
    "product_set_id": 1566,
    "dap_retargeting_type": "OFF",
    "operating_system": ["IOS"],
    "app_id": 6734586828965281797,
    "bid": 10
    }
(/code)
```

## Response
```xcodeblock
(code)
{
    "message": "OK",
    "code": 0,
    "data": {
        "adgroup_id": 1695246111250434
    },
    "request_id": "20210325225218010115153190019DA7A4"
}
(/code)
```

**Result**
<br>
You should see the created ad group in **Campaign** > **Ad Group** in TikTok Ads Manager.

# 7. Create an ad

When creating a DSA ad, do not pass in values for the `image_ids` or `video_id` fields. Otherwise, errors may occur. 

See [Create a video package](https://ads.tiktok.com/marketing_api/docs?id=1739470992872450) to find out how to create video packages. To use video packages, pass in the video package ID to the `shopping_ads_video_package_id` field.  To get video package IDs, make a request to the `/catalog/video/get/` endpoint.

## Targeting settings at the ad level

DSA supports two promotion types, Apps and Websites, and two targeting types, targeting (for new users) and retargeting (for returning users). Depending on the promotion type and targeting type, you need to pass in different data fields.

- For apps retargeting DSA, `shopping_ads_deeplink_type` can be set to `SHOPPING_ADS` or `CUSTOM`. `shopping_ads_fallbacktype` can be set to `DEFAULT`, `CUSTOM`, or `SHOPPING_ADS`.
- For apps targeting DSA, `shopping_ads_deeplink_type` can be set to `SHOPPING_ADS`, or `CUSTOM`, or not specified. `shopping_ads_fallbacktype` should be set to DEFAULT.
- For websites retargeting DSA, `shopping_ads_deeplink_type` should not be specified. `shopping_ads_fallbacktype` can be set to `CUSTOM` or `SHOPPING_ADS`.
- For websites targeting DSA, `shopping_ads_deeplink_type` should not be specified. `shopping_ads_fallbacktype` can be set to `CUSTOM` or `SHOPPING_ADS`.

## Using variables in DSA ad texts

For ad texts in DSA, you can use variables to stand for brands, product names, or prices that are different from product to product. When deployed, variables in the ad texts will be replaced with the values for the product. 
Note that variables are not counted against the 1-100 character list for a single ad text.

To use variables in a DSA ad text:

1. Get the list of variables for the product catalog by making a request to the `/catalog/lexicon/get/` endpoint. As each product catalog has its own list of variables, you need to specify catalog ID in the request. Note down the variable names that you want to use in your ad texts.

Below is an example response.
```xcodeblock
(code)
{
    "message": "OK",
    "code": 0,
    "data": {
        "list": [
            {
                "default_value": "",
                "update_time": "2020-04-15 15:07:43",
                "meta_field": "brand.brandName",
                "name": "brand",
                "catalog_id": 6815921224925923077,
                "type": "META",
                "id": 2894
            },
            {
                "default_value": "",
                "update_time": "2020-04-15 15:07:43",
                "meta_field": "description",
                "name": "description",
                "catalog_id": 6815921224925923077,
                "type": "META",
                "id": 2901
            },
            {
                "default_value": "",
                "update_time": "2021-02-02 02:47:34",
                "meta_field": "price.stdPrice",
                "name": "price",
                "catalog_id": 6815921224925923077,
                "type": "META",
                "id": 2902
            },
            {
                "default_value": "",
                "update_time": "2021-02-02 02:47:34",
                "meta_field": "price.stdSalePrice",
                "name": "sale_price",
                "catalog_id": 6815921224925923077,
                "type": "META",
                "id": 2903
            },
            {
                "default_value": "",
                "update_time": "2020-04-15 15:07:43",
                "meta_field": "profession.condition",
                "name": "condition",
                "catalog_id": 6815921224925923077,
                "type": "META",
                "id": 2895
            },
            {
                "default_value": "",
                "update_time": "2020-04-15 15:07:43",
                "meta_field": "title",
                "name": "title",
                "catalog_id": 6815921224925923077,
                "type": "META",
                "id": 2904
            }
        ]
    },
    "request_id": "202105120520240102360412204705D83E"
}
(/code)
```

2. Embed the variable names in you ad texts in the format of `{$variable$}`, and pass the whole ad text to the `ad_text` field when creating the ad (with `/ad/create/` endpoint).

For example, you can set an ad text "Don't miss the sale on {$product_name$} from {$brand$}. When the ad is deployed, the product name and brand for the product being promoted will be automatically used.

## Request

**Address** https://business-api.tiktok.com/open_api/v1.3/ad/create/

**Method** POST

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request content type. Allowed Value:"application/json"|
```

**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id|string|Advertiser ID|
|adgroup_id|string|Ad Group ID
|creatives|object[]|Advertising Creatives|
|ad_name|string|Ad Name. Character limit is 512 and cannot contain emoji. Note: Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character.|
|ad_text|string|An ad text. It is shown to your audience as part of your ad creative, to deliver the message you intend to communicate to them. You can use variables in your ad text to stand for product names, brands, or prices that are different from product to product.|
|call_to_action|string|Call-to-action text|
|shopping_ads_deeplink_type|string|See the above "Targeting settings at the ad level" section for details.|
|shopping_ads_video_package_id|string|Video template ID|
```

### Example
```xcodeblock
(code)
{
    "advertiser_id": 6927303038499569666,
    "adgroup_id": 1695246111250434,
    "creatives": [{
        "adgroup_id": 1695246111250434,
        "call_to_action": "SHOP_NOW",
        "ad_name": "Demo DSA Creative",
        "ad_text": "Demo DSA text",
        "display_name": "TikTok Ads",
        "dpa_fallback_type": "DEFAULT",
        "dpa_open_url_type": "NONE",
        "ad_format": "SINGLE_IMAGE"
    }] 
}
(/code)
```

## Response

```xcodeblock
(code)
{
    "message": "OK",
    "code": 0,
    "data": {
        "need_audit": false,
        "ad_ids": [
            1695253611756562
            ]
     },
    "request_id": "20210325225218010115153190019DA7A4"
}
(/code)
```

**Result**
<br>
The ad should be available in the **Ad** section under the **Campaign** tab.
