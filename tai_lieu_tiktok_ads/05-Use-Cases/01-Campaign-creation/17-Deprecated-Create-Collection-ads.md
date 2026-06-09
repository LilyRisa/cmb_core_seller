---
title: "(Deprecated) Create Collection ads"
doc_id: 1738963942132738
path: "Use Cases / Campaign creation / (Deprecated) Create Collection ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Create Collection ads



Collection ads are a type of in-feed video ad on TikTok that leads your customers to an instant storefront page, where they can shop for your products without ever leaving the app.

> <span style="color:DodgerBlue">**Note**</span><br>Starting from Feb.16,2023, you can no longer create Collection Ads. To ensure a smooth API integration, we recommend you migrate to [Video Shopping Ads](https://ads.tiktok.com/marketing_api/docs?id=1741942291730434&rid=tv5sdeafqp)  under Product Sales objective. The modification of existing Collection Ads through API will be supported till the end of March, and may be extended on a case-by-case basis. If you need extra transition time, please contact your TikTok representatives.

Collection ads allow retailers to show off their inspirational branded video with a few key product tiles at the bottom that lead to a quick-load storefront. This makes for an engaging browsing and shopping experience.

> <span style="color: dodgerblue">**Note**</span><br>Collection Ads is currently in Beta. If you would like access to this feature, please contact your TikTok representative to apply for allowlisting.

Creating a collection ad involves the following steps:

1. Set up your product catalog. You need to create a product catalog and upload products to your product catalog.
2. Create and configure a storefront page.
3. Create a collection ad.

# 1. Set up the product catalog

**Note**: Catalog, Products, and Product Sets are allowlist-only features. If you are already on the allowlist for Collection Ads, these features will be enabled for you automatically.

## 1.1 Create a product catalog

Use the [/catalog/create/](https://ads.tiktok.com/marketing_api/docs?id=1740306481704961) endpoint to create a product catalog. Specify the name, targeting country, and currency in the request. 
Here is an example of the data part in the request.

``` xcodeblock
(code JSON json)
{
  "bc_id": 123,
  "name": "my_catalog",
  "catalog_type": "ECOM",
  "catalog_conf": {
    "region_code": "US",
    "currency": "USD",
	"channel": "CLIENT"
  }
}
(/code)
```

## 1.2 Upload products to the product catalog

Use the [/catalog/product/file/](https://ads.tiktok.com/marketing_api/docs?id=1740496787164161) or [/catalog/product/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740497429681153) endpoints to add your products to the product catalog. 

The example below uses the [/catalog/product/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740497429681153) endpoint.

``` xcodeblock
(code JSON json)
{
  "bc_id": 123,
  "catalog_id": 456,
  "dpa_products": [
    {
      "sku_id": "tiktok_item_1111",
      "title": "qgs test product1",
      "description": "qgs test product description",
      "availability": "AVAILABLE_FOR_ORDER",
      "image_link": "http://p1.ipstatp.com/origin/ad-site-i18n/xxxxxxx",
      "video_link": "https://www.tiktok.com/tiktok_t_shirt",
      "brand": "xiaoru",
      "additional_image_link": ["xxxxxxx", "http://p1.ipstatp.com/origin/ad-site-i18n/xxxxxxx"],
      "item_group_id": "1.11",
      "google_product_category": "2.22",
      "gtin": "3.33",
      "mpn": "4.44",
      "landing_url": {
        "link": "https://www.tiktok.com",
        "android_url": "https://www.android.com",
        "android_package": "a.b.com",
        "android_app_name": "android_app_name",
        "ios_url": "https://www.ios.com",
        "ios_app_store_id": "https://ios_app_store.com",
        "ios_app_name": "ios_app_name",
        "ipad_url": "https://ipad.com",
        "ipad_app_store_id": "https://ipad_app_store.com",
        "ipad_app_name": "ipad_app_name",
        "iphone_url": "https://iphone.com",
        "iphone_app_store_id": "https://iphone_app_store.com",
        "iphone_app_name": "iphone_app_name"

      },
      "price": {
        "price": "100",
        "sale_price": 30,
        "currency": "USD",
        "sale_price_effective_date": "2021-10-01T0:00/2021-10-31T0:00"
      },
      "profession": {
        "condition": "USED",
        "age_group": "NEW_BORN",
        "color": "red",
        "gender": "MALE",
        "shipping": "US:CA:Ground:9.99 USD",
        "shipping_weight": "1 kg",
        "size": "L",
        "tax": "xxx",
        "material": "cotton",
        "pattern": "stripes1",
        "product_type": "Apparel &amp; Accessories &gt; Clothing &gt; Shirts"
      },
      "ext": {
        "custom_label_0": "a",
        "custom_label_1": "b",
        "custom_label_2": "c",
        "custom_label_3": "d",
        "custom_label_4": "e"
      }
    }
  ]
}
(/code)
```

# 2. Create a storefront page

## 2.1 Create and configure a storefront page

Create a storefront page. A storefront page is a template for collection ads that can showcase multiple products in one place. 

## 2.2 Get the page ID

Use the [/page/get/](https://ads.tiktok.com/marketing_api/docs?id=1737719918220290) endpoint to get the page_id of the storefront page. 


**Note**
1. Please set `business_type` to `STORE_FRONT`. 
2. Please note that a single storefront page can be tied only to one ad. You can determine whether a storefront page has already been tied to an ad by the value of the `is_associated` field in the reponse of the `/page/get/` endpoint.

``` xcodeblock
(code Bash json)
curl --location --request GET 'https://ads.tiktok.com/open_api/v1.3/page/get/?advertiser_id=123&amp;business_type=STORE_FRONT' \
--header 'Access-Token: YOUR_ACCESS_TOKEN'
(/code)
```

# 3. Create a collection ad

## 3.1 Create a campaign

Use the [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602) endpoint to create a campaign. The `objective_type` can be set to `TRAFFIC`, `CONVERSION`, `VIDEO_VIEWS`, or `REACH`. 

Here is an example of the data in the request.

``` xcodeblock
(code JSON json)
{
    "advertiser_id": 123,
    "campaign_name": "my-collection-ads-campaign",
    "objective_type": "TRAFFIC",
    "budget_mode": "BUDGET_MODE_INFINITE"
}
(/code)
```

## 3.2 Create an ad group

Use the [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114) endpoint to create an ad group. 

Field settings:

- Set `promotion_type` to `WEBSITE`.
- Set `placements` to `PLACEMENT_TIKTOK`.

Here is an example of the data in the request. 

``` xcodeblock
(code JSON json)
{
    "advertiser_id": 123,
    "campaign_id": 456,
    "optimization_goal": "CLICK",
    "adgroup_name": "test-wjm-collection_ads",
    "location": [
        1668284
    ],
    "promotion_type": "WEBSITE",
    "budget_mode": "BUDGET_MODE_DAY",
    "billing_event": "CPC",
    "bid": 1,
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "schedule_start_time": "2021-03-15 00:00:00",
    "schedule_end_time": "2021-03-16 00:00:00",
    "schedule_type": "SCHEDULE_START_END",
    "budget": 50,
    "pacing": "PACING_MODE_SMOOTH",
    "bid_type": "BID_TYPE_CUSTOM"
}
(/code)
```

## 3.3 Create an ad

Use the [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) endpoint to create an ad.  

Field settings:

- You must specify the `page_id`, which has been obtained in step 2.2.
- Set `ad_format`  to `SINGLE_VIDEO`.
- You do not need to specify the page URL or CTA, as they are already included in the `page_id`.

``` xcodeblock
(code JSON json)
{
    "advertiser_id": 123,
    "adgroup_id": 456,
    "creatives": [
        {
            "ad_format": "SINGLE_VIDEO",
            "image_ids": ["your_image_id"],
            "video_id": "your_video_id",
            "ad_text": "your ad text",
            "ad_name": "your_ad_name",
            "display_name": "your display name",
            "page_id": 789
        }
    ]
}
(/code)
```
