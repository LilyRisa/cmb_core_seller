---
title: "Cards"
doc_id: 1749019652141058
path: "Marketing API / Creatives / Guides / Creative assets / Interactive Add-ons / Cards"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Cards

You can enhance your ads with any of the following six Card types as interactive add-ons.

- Display Cards (also known as image cards) let you include customized images within your in-feed video ads. Once the Display Card appears in the ad, it will function as a CTA button that drives traffic to your website or app download page.  See [here](https://ads.tiktok.com/help/article?aid=10007089) to find out the detailed introductions and screenshots of Display Card. 
- Website Info Cards direct people to visit your website to learn more about your brand and products.
- Download Cards let you showcase elements of the app you are promoting, such as rating, review count, and industry.  It is a clickable element within the ad that will direct you to the app's download store. See [here](https://ads.tiktok.com/help/article?aid=10007086) to find out the detailed introductions and screenshots of Download Card. 
- Inventory Cards are the final cards within your ads that encourage people to browse your inventory.
- Product Cards display product information with tags on a customizable image card.
- Product Tiles display multiple products in a scrollable format to showcase more of your products in a single ad.

# Use cards in ads

Cards are innovative add-on formats in TikTok Auction Ads. They can make ad creatives more visually captivating and facilitate user engagement to achieve better ad performance.


## Display Card
### Prerequisites
- To use Display Cards in your ads, ensure that the ads meet the following requirements:
 	- For objectives `REACH`, `TRAFFIC`, `VIDEO_VIEWS`, `APP_PROMOTION`, `WEB_CONVERSIONS`, or `PRODUCT_SALES`:
 		- `placement_type` is `PLACEMENT_TYPE_AUTOMATIC`, or `placement_type` is `PLACEMENT_TYPE_NORMAL` and `PLACEMENT_TIKTOK` is included in the value of `placements`
	- For the objective `LEAD_GENERATION`:
    	- At the ad group level:
    		- `promotion_type` is `LEAD_GENERATION`
    		- `placement_type` is `PLACEMENT_TYPE_AUTOMATIC`, or `placement_type` is `PLACEMENT_TYPE_NORMAL` and `PLACEMENT_TIKTOK` is included in the value of `placements`
    	- At the ad level: `ad_format` is `SINGLE_VIDEO`
- Ensure that you have an image card ready to be used in the ad. The image must be 750 px in width and 421 px in height. 
	 - Upload an image using [/file/image/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642). The `image_id` in the response will be used to create a card portfolio. 
 
 ### Steps
 1. Use [/creative/portfolio/create/](https://ads.tiktok.com/marketing_api/docs?id=1739091950439426) to create a Display Card portfolio.
- Set `creative_portfolio_type` to `CARD`.
- Set `card_type` to `IMAGE`.
- Pass in your image ID to the `image_id` field. This endpoint returns the ID of the portfolio (`creative_portfolio_id`) that has been created. 
2.  Use the portfolio in your ad.
- For Upgraded Smart+ Ads: Pass the value of `creative_portfolio_id` to the `card_id` field when you use [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522) or [/smart_plus/ad/update/](https://business-api.tiktok.com/portal/docs?id=1843317411665921) to create or update your ad.
- For Manual Ads: Pass the value of `creative_portfolio_id` to the `card_id` field when you use [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) or [/ad/update/](https://ads.tiktok.com/marketing_api/docs?id=1739953405142018) to create or update your ad.


## Website info card
### Prerequisites
- To use Website info cards in your ads, ensure that the ads meet the following requirement:
  - Campaign objective: `WEB_CONVERSIONS` or `TRAFFIC` (with `promotion_type` set to `WEBSITE`).
  
  
### Steps
1. Use [/creative/portfolio/create/](https://ads.tiktok.com/marketing_api/docs?id=1739091950439426) to create a Download Card portfolio.
	- Set `creative_portfolio_type` to `WEB_INFO_CARD`.
	- Configure the settings of the Website info card via `image_id`, `title`, and `selling_points`. This endpoint returns the ID of the portfolio (`creative_portfolio_id`) that has been created. 
2. Use [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) or [/ad/update/](https://ads.tiktok.com/marketing_api/docs?id=1739953405142018) to create or update your ad.  
	- To use the portfolio in your ad, pass the value of `creative_portfolio_id` to the `card_id` field.  
			 
## Download Card
### Prerequisites
- To use Downcard Cards in your ads, ensure that the ads meet the following requirements:
 	- Objectives: `TRAFFIC`, `APP_PROMOTION`, or `WEB_CONVERSIONS`
 	- Promotion type: WEBSITE or App
	- Placement: TikTok

### Steps
1. Use [/creative/portfolio/create/](https://ads.tiktok.com/marketing_api/docs?id=1739091950439426) to create a Download Card portfolio.
	- Set `creative_portfolio_type` to `DOWNLOAD_CARD`.
	- Configure the settings of the Download Card via `layouts`, `description`, `tags`,  `category_label` (when the value of `tags` includes `CATEGORIES`), `app_id`, `profile_image`  `call_to_action`, `mobile_app_id`, `country_code`. This endpoint returns the ID of the portfolio (`creative_portfolio_id`) that has been created. 
2. Use [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) or [/ad/update/](https://ads.tiktok.com/marketing_api/docs?id=1739953405142018) to create or update your ad.  
	- To use the portfolio in your ad, pass the value of `creative_portfolio_id` to the `card_id` field.  

## Inventory Card
### Steps
1. Use [/creative/portfolio/create/](https://ads.tiktok.com/marketing_api/docs?id=1739091950439426) to create an Inventory Card portfolio.
  - Set `creative_portfolio_type` to `INVENTORY_CARD`.
  - Configure the settings of the Inventory Card via `image_id` and `description`. This endpoint returns the ID of the portfolio (`creative_portfolio_id`) that has been created.
2.  Use the portfolio in your ad.
- For Upgraded Smart+ Ads: Pass the value of `creative_portfolio_id` to the `card_id` field when you use [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522) or [/smart_plus/ad/update/](https://business-api.tiktok.com/portal/docs?id=1843317411665921) to create or update your ad.
- For Manual Ads: Pass the value of `creative_portfolio_id` to the `card_id` field when you use [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) or [/ad/update/](https://ads.tiktok.com/marketing_api/docs?id=1739953405142018) to create or update your ad.

> <span style="color:DodgerBlue">**Note**</span><br>
>Inventory Cards can only be used in Automotive Ads.

## Product Tiles
### Prerequisites
- Before creating a Product Tiles portfolio, ensure that you have a catalog containing at least four products that have passed the review. To verify whether you have a catalog with enough eligible products for the Product Tile portfolio, use [/catalog/product/get/](https://business-api.tiktok.com/portal/docs?id=1740564136678402) and confirm that the `audit_status` of at least four products is `approved`.
- Using a Product Tiles portfolio to create ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.
- To use Product Tiles in your ads, ensure that the ads meet the following requirements:
  - Objective:  Product sales  (`objective_type` is `PRODUCT_SALES` at the campaign level)
  - Product source: Catalog  (`campaign_product_source` is `CATALOG` at the campaign level)
  - Shopping ads type: Video Shopping ads (`shopping_ads_type` is `VIDEO` at the ad group level)  

### Steps
1. Use [/creative/portfolio/create/](https://business-api.tiktok.com/portal/docs?id=1739091950439426) to create a Product Tiles portfolio.
  - Set `creative_portfolio_type` to `PRODUCT_TILE`. 
  - Configure the settings of the Product Tiles via the following parameters.
    - `product_source` (set to `CATALOG`)   
    - `identity_id`   
    - `identity_type`   
    - `identity_authorized_bc_id` (when `identity_type` is `BC_AUTH_TT`)   
    - `catalog_id`   
    - `catalog_authorized_bc_id`   
    - `product_specific_type`   
    - Any of `product_set_id`, `item_group_ids`, or `sku_ids`   
    - `card_image_index` (Optional)  

  This endpoint returns the ID of the portfolio (`creative_portfolio_id`) that has been created.
  
2. Use [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) or [/ad/update/](https://ads.tiktok.com/marketing_api/docs?id=1739953405142018) to create or update your ad. Note that the following requirements must be met.

```xtable
| Parameter {40%} | How to configure the parameter {60%}  |
|---|---|
| `card_id` | Pass the ID of a Product Tiles portfolio. |
| `ad_format` | `SINGLE_VIDEO` |
| `vertical_video_strategy` | Any of the following values: <ul><li>`SINGLE_VIDEO` </li><li>`CATALOG_UPLOADED_VIDEOS`</li></ul>|
| `dynamic_format` | `UNSET` |
| <ul><li>`catalog_id` </li><li>`product_specific_type`</li><li>Any of `item_group_ids`, `product_set_id`, or `sku_ids` </li></ul>| Pass the same fields and values you used to create the Product Tiles portfolio. |
```

## Product Card
### Prerequisites
To use Product Cards in your ads, ensure that the ads meet the following requirements:
- Objective:  Product sales (`objective_type` is `PRODUCT_SALES` at the campaign level) 
- Shopping ads type: Video Shopping ads  (`shopping_ads_type` is `VIDEO` at the ad group level) 
  
### Steps
1. Use [/creative/portfolio/create/](https://business-api.tiktok.com/portal/docs?id=1739091950439426) to create a Product Card portfolio.
  - Set `creative_portfolio_type` to `PRODUCT_CARD`. 
  - Specify the settings of the Product Card based on the product source.
  
```xtable
| Product source {20%} | Catalog {40%} | TikTok Shop{40%}  |
|---|---|---|
| Settings |<ul><li>`product_source` (set to `CATALOG`) </li><li>`identity_id`</li><li>`identity_type` </li><li>`identity_authorized_bc_id` (when `identity_type` is `BC_AUTH_TT`) </li><li> `catalog_id`</li><li>`catalog_authorized_bc_id`</li><li>`product_specific_type`</li><li>Any of `product_set_id`, `item_group_ids`, or `sku_ids`</li><li> `dynamic_format`</li><li> `vertical_video_strategy`</li><li>`ad_text`</li><li> `card_show_price` </li><li>`card_tags`</li><li>`card_image_index` (Optional) </li></ul>| <ul><li>`product_source` (set to `STORE`) </li><li>`identity_id`</li><li>`identity_type` </li><li>`identity_authorized_bc_id` (when `identity_type` is `BC_AUTH_TT`) </li><li> `store_id`</li><li>`store_authorized_bc_id` </li><li>`item_group_ids`</li><li>`ad_text`</li><li>`card_show_price` </li><li> `card_tags`</li><li>`card_image_index` (Optional)</li></ul><br><br><br><br><br> |
```

This endpoint returns the ID of the portfolio (`creative_portfolio_id`) that has been created.

2. Use [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) or [/ad/update/](https://ads.tiktok.com/marketing_api/docs?id=1739953405142018) to create or update your ad.
  - To use the portfolio in your ad, pass the value of `creative_portfolio_id` to the `card_id` field.


