---
title: "Preview an ad or a creative"
doc_id: 1739403070695426
path: "API Reference / Creative Tools / Preview an ad or a creative"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Preview an ad or a creative

Use this endpoint to preview an ad or a creative.



## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/creative/ads_preview/create/|/v1.3/creative/ads_preview/create/|
|Request parameter name|`material_type` <br> `dpa_video_package_id`<br> `placement` |`preview_type` <br> `shopping_ads_video_package_id`<br>`placements`|
|Request parameter data type | `advertiser_id`: number  <br>`bc_id`: number <br> `ad_id`: number<br> `catalog_id`: number <br> `product_ids`: number[] <br> `call_to_action_id`: number | `advertiser_id`: string  <br> `bc_id`: string <br> `ad_id`: string<br> `catalog_id`: string <br> `product_ids`: string[] <br> `call_to_action_id`: string |
|New request parameters|/|`identity_id`<br> `identity_type`<br>`identity_authorized_bc_id`<br> `objective_type`<br>`shopping_ads_type`<br> `promotion_type`<br>`dynamic_format`<br>`card_id`<br>`card_type`<br>`product_set_id`<br>`sku_ids`<br>`product_specific_type`<br>`item_group_ids`<br>`vertical_video_strategy`<br>`card_tags`<br>`card_show_price`<br>`card_image_id`<br>`page_id`<br>`dynamic_destination`<br>`instant_product_page_used`<br>`catalog_authorized_bc_id`<br>`placement`(in <strong>Preview existing ads</strong> and <strong>Preview ads that you plan to create</strong>)<br>`preview_format`<br>`ad_format`<br>`image_id`<br>`carousel_image_index`(in <strong>Preview ads that you plan to create</strong>)<br>`music_id`(in <strong>Preview ads that you plan to create</strong>)<br>`is_smart_performance_campaign`(in <strong>Preview ads that you plan to create</strong>)<br>`image_ids`(in <strong>Preview ads that you plan to create</strong>) <br>`product_source`<br>`store_id`<br>`store_authorized_bc_id`<br>`showcase_products`<br>`vehicle_ids`<br>`auto_disclaimer_types`<br>`end_card_cta`<br>`tiktok_subplacement`(in <strong>Preview ads that you plan to create</strong>)|
```



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/ads_preview/create/

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|--- |--- |--- |
|Content-Type {Required}|string|Request content type. Allowed Value: `application/json`.  |
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

### Preview ads that you plan to create

This allows you to preview your ad before creating it. 

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|-|-|-|
| advertiser_id {Required} | string | Advertiser ID. <br><br><p><span style="color:darkred"><b>Note</b></span>: You need to have Operator or Admin permission for the ad account. To check your permission for an ad account, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401). The returned `advertiser_role` should be `ADMIN` or `OPERATOR`. |
| preview_type {Required} | string | Preview type.<br>To preview ads by ads creation parameters, use `ADS_CREATION`. |
| objective_type {Required}|string| Advertising objective. For enum values and descriptions, see [Objectives](https://ads.tiktok.com/marketing_api/docs?id=1737585562434561). <br><p><span style="color:darkred"><b>Note</b></span>: Currently the supported objectives are: `REACH`,`TRAFFIC`,  `VIDEO_VIEWS`, `ENGAGEMENT`, `APP_PROMOTION`, `LEAD_GENERATION`, `WEB_CONVERSIONS`, `PRODUCT_SALES`, and `RF_REACH`. |
| is_smart_performance_campaign| boolean|Whether the campaign is a Smart+ Campaign.<br>Default value: `false`.<br><p><span style="color:darkred"><b>Note</b></span>: This field can only be set to `false` when any of the following conditions is met:<ul><li>`ad_fomat` is set to `CAROUSEL_ADS` or `CATALOG_CAROUSEL`.</li><li>`placement` is set to `PLACEMENT_PANGLE` or `ALL`. </li><li>`objective_type` is set to `TRAFFIC`, `REACH`, `VIDEO_VIEWS`, or `PRODUCT_SALES`. </li></ul> |
| placement | string |The app where you want to deliver your ads. <br> When `ad_format` is set to `CAROUSEL_ADS` or `CATALOG_CAROUSEL`,  specify this field as `PLACEMENT_TIKTOK`, or do not pass this field.<br><br> Enum values: <ul><li>`PLACEMENT_TIKTOK`: Supported only when `objective_type` is `REACH`,`TRAFFIC`,  `VIDEO_VIEWS`, `ENGAGEMENT`, `APP_PROMOTION`, `LEAD_GENERATION`, `WEB_CONVERSIONS`,  or `PRODUCT_SALES`.</li><li>`PLACEMENT_PANGLE`: Supported only when `objective_type` is `TRAFFIC`, `LEAD_GENERATION` (only when `promotion_type` is `LEAD_GENERATION`), `APP_PROMOTION`, or `WEB_CONVERSIONS`. </li><li>`PLACEMENT_GLOBAL_APP_BUNDLE`: Suppported only when `objective_type` is `REACH`.</li><li>`ALL`: Supported only when `objective_type` is `TRAFFIC`, `LEAD_GENERATION` (only when `promotion_type` is `LEAD_GENERATION`), `APP_PROMOTION`, or `WEB_CONVERSIONS`. </li></ul>|
|tiktok_subplacement|string|Valid only when the following conditions are both met:<ul><li>`objective_type` is `REACH`.</li><li>`placement` is `PLACEMENT_TIKTOK`.</li></ul><br>The subplacements within TikTok for your ads, allowing you to choose where your ads will appear.<br><br>Enum values:<ul><li>`LEMON8`: Lemon8, a community app for lifestyle content, focusing on real-life experiences, tips, guides, and product reviews. By including Lemon8 as a subplacement, your ads will appear in its For You feed, Search feed, and Immersive Video feed. [Learn more about Lemon8](https://ads.tiktok.com/help/article/about-lemon8-in-tiktok-ads-manager). </li><li>`UNSET`: Unset.</li></ul>|
| preview_format | string | Based on the `placement` you've selected, the allowed preview formats are different.<ul><li>When `placement` is `PLACEMENT_TIKTOK`, the allowed preview formats are:  <ul><li>`IN_FEED`: In-feed. Ads will be placed in the For You feed and might also be placed in Profile Page and Following feeds.</li><li>`SEARCH_RESULTS`: Search result. </li><li>`SEARCH_FEED`: Search feed. </li><li>`TIKTOK_LITE`: TikTok Lite, a streamlined version of TikTok that features a smaller app size and faster video loading speed. The TikTok Lite subplacement is currently available for Japan or South Korea as targeting locations.</li><li>`PRODUCT_SEARCH`: Search (Shopping Center). Show your products in the Shop Tab search results, as well as general search results in the TikTok App. Learn more about [Shop Tab placements for Shop Ads](https://ads.tiktok.com/help/article/about-onsite-shopping-placements).</li><li>`PRODUCT_SHOP_CENTER`: Recommendations (Shopping Center). Show your products to people who are likely to buy them in a Recommended for you section. </li></ul><li>When `placement` is `PLACEMENT_TIKTOK` and `tiktok_subplacement` is `LEMON8`, the allowed preview formats are:<ul><li>`IN_FEED`: In-feed. The ad will be displayed in a full-screen format, providing an immersive and undistracted viewing experience.</li><li>`IN_FEED_TWO_COLUMNS`: In feed (two columns). The ad will be displayed in a two-column layout within the For You feed, allowing multiple content pieces to be displayed side by side. </li></ul></li><li>When `placement` is `PLACEMENT_GLOBAL_APP_BUNDLE`, the allowed preview formats are:<ul><li>`APP_OPEN`: App Open. The ad will be displayed when the Capcut app is opened, marking the beginning of each user session. This premium full-screen placement ensures visibility as the first screen users see.</li><li>`IN_FEED`: In-feed. The ad will be displayed in a full-screen format, providing an immersive and undistracted viewing experience.</li><li>`IN_FEED_TWO_COLUMNS`: In feed (two columns). The ad will be displayed in a two-column layout within the For You feed, allowing multiple content pieces to be displayed side by side. </li></ul></li></ul>Default value: `IN_FEED`. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The values `SEARCH_FEED` and `TIKTOK_LITE` are valid only when the following conditions are met: <ul><li>`objective_type` is `REACH`, `VIDEO_VIEWS`, or `ENGAGEMENT`.</li><li>`placement` is `PLACEMENT_TIKTOK`.</li></ul></li><li>The values `PRODUCT_SEARCH` and `PRODUCT_SHOP_CENTER` are only available for Video Shopping Ads and Product Shopping Ads. </li><li>When `placement` is `PLACEMENT_PANGLE`, this field is not supported.</li></ul>|
| shopping_ads_type {+Conditional}  | string | Required when `objective_type` is `PRODUCT_SALES` and `catalog_id` is not specified.<br><br>Shopping ads type. <br><br>Enum values: <ul><li>`VIDEO`: Video Shopping Ads. </li><li>`LIVE`: Live Shopping Ads.</li><li>`PRODUCT_SHOPPING_ADS`: Product Shopping Ads. </li></ul> <br>When `ad_format` is set to `CATALOG_CAROUSEL`, specify this field as `VIDEO`, or do not pass this field.|
| product_source {+Conditional} | string | Required only for Video Shopping Ads and Product Shopping Ads. <br><br>Product source where you want to get products for promotion. <br><br>Enum values: `STORE` (TikTok Shop),`SHOWCASE`(TikTok Showcase). <ul><li>For Video Shopping Ads, you can set this field to `STORE` or `SHOWCASE`.</li><li>For Product Shopping Ads, set this field to `STORE`.</li></ul> |
| store_id {+Conditional} | string | <ul><li>Required in any of the following scenarios:<ul><li>`shopping_ads_type` is `VIDEO` and `product_source` is `STORE`.</li><li>`shopping_ads_type` is `PRODUCT_SHOPPING_ADS` and `product_source` is `STORE`. </li></ul></li><li>Optional when `shopping_ads_type` is `LIVE`. </li></ul> <br>ID of the TikTok Shop.<br><br> To get the TikTok Shop ID, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401) and set `asset_type` to `TIKTOK_SHOP`. The returned `asset_id` will be the TikTok Shop ID. |
| store_authorized_bc_id {+Conditional} | string | Required when `store_id` is passed.<br><br> ID of the Business Center that is authorized to access the TikTok Shop (`store_id`). |
| showcase_products {+Conditional} | object[] | Required when `shopping_ads_type` is `VIDEO` and `product_source` is `SHOWCASE`.<br><br> See [Create Video Shopping Ads with products from Showcase](https://business-api.tiktok.com/portal/docs?id=1759232259360770) to learn more. <br><br>The list of Showcase products that you want to use in your ad. <br><br> Max size: 20.<br><br> You can get the available Showcase products via [/showcase/product/get/](https://business-api.tiktok.com/portal/docs?id=1759233576199169). |
#| item_group_id {+Conditional} | string | Required when `showcase_products` is passed. <br><br>SPU ID of the product. |
#| store_id {+Conditional} | string | Required when `showcase_products` is passed. <br><br>The ID of the TikTok Shop that the product (`item_group_id`) belongs to.|
| promotion_type {+Conditional} |string|Promotion type. You can decide where you'd like to promote your products using this field. <br><br> Enum values: <ul><li>`APP_ANDROID`: Android application.</li><li>`APP_IOS`: iOS application.</li><li>`WEBSITE`: landing page.</li><li>`LEAD_GENERATION`: Instant Form or Website.</li><li>`LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE`: TikTok direct messages.</li><li>`LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE`: instant messaging apps.</li><li> `LEAD_GEN_CLICK_TO_CALL`: phone call. </li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: The following enum values are valid only when `objective_type` is `LEAD_GENERATION`: <ul><li>`LEAD_GENERATION`</li><li>`LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE`</li> <li> `LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE` </li><li>`LEAD_GEN_CLICK_TO_CALL` </li></ul>|
| identity_id {Required} | string | Identity ID.<br><br>If you are not using Spark Ads, `identity_id` and `identity_type` (`CUSTOMIZED_USER`) can be used for better management of ad information. |
| identity_type {Required} | string| Identity type. <br><br>Enum values: `CUSTOMIZED_USER`, `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT`. <br>Use `CUSTOMIZED_USER` for custom identity and use `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT` in Spark Ads. <br>For details about identities, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097).<br><br><ul><li>When `ad_format` is `CATALOG_CAROUSEL`, you can only set this field to `CUSTOMIZED_USER`.</li><li> If `objective_type` is `LEAD_GENERATION`: <ul><li> When `promotion_type` is `LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE`, Spark Ads must be used and `identity_type` must be `TT_USER` or `BC_AUTH_TT`. </li><li> When `promotion_type` is not `LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE`, both Spark Ads & non-Spark Ads are supported, so `identity_type` can be set to any of the enum values. </li></ul> </li><li> If `objective_type` is `ENGAGEMENT`, only Spark Ads are supported and `identity_type` must be `AUTH_CODE`, `TT_USER` or `BC_AUTH_TT`.</li></ul> |
| identity_authorized_bc_id {+Conditional} |string|Required when `identity_type` is `BC_AUTH_TT`. <br>ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
| ad_format{Required} |string|Ad format.<br><ul><li> For single video ads, set this field to `SINGLE_VIDEO`. </li><li> For single image ads, set this field to `SINGLE_IMAGE`. Supported only on Pangle.</li><li>For TikTok Standard Carousel ads, set this field to `CAROUSEL_ADS`. <ul><li>To use this ad format, you need to set `objective_type` to `REACH`, `WEB_CONVERSIONS`, `APP_PROMOTION`, `LEAD_GENERATION` or `TRAFFIC`.</li></ul> </li><li>For TikTok Catalog Carousel ads, set this field to `CATALOG_CAROUSEL`.<br>See [here](https://business-api.tiktok.com/portal/docs?id=1766217791987713) to learn about the steps of creating TikTok Carousel Ads. </li><li> For Live Shopping Ads, set this field to `LIVE_CONTENT`.</li></ul> |
| video_id {+Conditional} | string | Video ID. <br>Required when `ad_format` is `SINGLE_VIDEO`.<br> Not supported when `ad_format` is `SINGLE_IMAGE`, `CAROUSEL_ADS` or `CATALOG_CAROUSEL`.<br><ul><li>To create video [Spark Ads](https://ads.tiktok.com/marketing_api/docs?id=1739470744631298) using Spark Ads Push or create video Non-Spark Ads, use `video_id`. You can find video IDs using [/file/video/ad/search/](https://ads.tiktok.com/marketing_api/docs?id=1740050472224769). </li><li>To create video Spark Ads using Spark Ads Pull, use `tiktok_item_id` to specify the TikTok post ID. </li></ul>|
| image_ids{+Conditional} | string[] | Required in any of the following scenarios:<ul><li> `ad_format` is `SINGLE_IMAGE`.</li><li> `ad_format` is `CAROUSEL_ADS` and you want to create Standard Carousel Spark Ads via Spark Ads Push or Standard Carousel Non-Spark Ads.  </li><li>`ad_format` is `CATALOG_CAROUSEL`, `catalog_enabled` is set to `true`, and an Auto-Model catalog is specified through `catalog_id`.</li></ul><br>A list of image IDs (images used in an ad or as a video cover). <br><br><ul><li>When `ad_format` is set to `CAROUSEL_ADS`, the size range for this field is &#91;1, 35&#93;, and the sequence of the image IDs specified via this field is the order in which the images are shown in the Carousel Ads.  <br> See [Create Carousel Ads](https://ads.tiktok.com/marketing_api/docs?id=1766217791987713) to learn about the requirements for images that can be used in TikTok Carousel Ads. </li><li>When `ad_format` is set to `CATALOG_CAROUSEL` and `catalog_enabled` is set to `true`, the value of this field will be used as the summary photo in the Automotive Ads for Models (only one value is allowed).<br>To learn about the requirements for the summary photo that can be used in Automotive Ads for Models, see [Automotive Ads for Models](https://business-api.tiktok.com/portal/docs?id=1829635758164994).</li><li>When `ad_format` is set to `SINGLE_IMAGE`, you can only assign one image ID to this field.<br>  To learn about the requirements for images that can be used in single image ads, see [Create single image ads](https://business-api.tiktok.com/portal/docs?id=1777633230937090).</li></ul>|
| end_card_cta {+Conditional}|string[]|Required when the following conditions are met:<ul><li>`objective_type` is `LEAD_GENERATION`.</li><li>`catalog_enabled` is `true`.</li><li>An Auto-Model catalog is specified through `catalog_id`.</li><li>`ad_format` is `CATALOG_CAROUSEL`.</li></ul><br>Call-to-action for the end card (`image_ids`) of an [Automotive Carousel Ad for Models](https://business-api.tiktok.com/portal/docs?id=1829635758164994#item-link-Ad%20format%20as%20Catalog%20Carousel).<br><br>Enum values:<ul><li>`SEARCH_INVENTORY` (Recommended): Search inventory.</li><li>`LEARN_MORE`: Learn more.</li><li>`SHOP_NOW`: Shop now.</li><li>`SIGN_UP`: Sign up.</li><li>`CONTACT_US`: Contact us.</li><li>`BOOK_NOW`: Book now.</li><li>`READ_MORE`: Read more.</li><li>`VIEW_MORE`: View now.</li><li>`ORDER_NOW`: Order now.</li></ul> |
| music_id{+Conditional}| string|  Required in any of the following scenarios:<ul><li>`ad_format` is `CAROUSEL_ADS` and you create Standard Carousel Spark Ads via Spark Ads Push or Standard Carousel Non-Spark Ads. </li><li>`ad_format` is `CATALOG_CAROUSEL`.</li></ul>  <br>The ID of the piece of music that you want to use in the TikTok Carousel Ad.|
|tiktok_item_id {+Conditional}|string|<ul><li>Required when `identity_type` is `AUTH_CODE` or `BC_AUTH_TT` and you want to create Spark Ads through Spark Ads Pull.</li><li>Not supported when `identity_type` is `CUSTOMIZED_USER`.</li></ul> <br>The ID of the TikTok post to be used as Spark Ad. <br> <br>To learn more about Spark Ads, see [Create Spark Ads](https://business-api.tiktok.com/portal/docs?id=1739470744631298). <br> <br> Pass in the `item_id` you get from the response of the [/tt_video/info/](https://ads.tiktok.com/marketing_api/docs?id=1738376324021250) or [/identity/video/get/](https://ads.tiktok.com/marketing_api/docs?id=1740218475032577) endpoint. <br><br><p><span style="color:darkred"><b>Note</b></span>: By using Spark Ads, you confirm that you have the rights to use the music in the videos for commercial purposes.|
| carousel_image_index | integer | Valid only when you set `ad_format` to `CATALOG_CAROUSEL`, and a list of additional images has been configured for the specified products. <br> <br>The index that specifies the additional images to be used in the VSA Carousel ad. If this field is not passed, the main images of catalog products will be used in the VSA Carousel ad.<br> Value range: &#91;0, 9&#93;. <ul><li> The number you set via this field represents the position of the product image within the additional image list (`additional_image_urls`) for each catalog product. For instance, `carousel_image_index` = <strong>1</strong> means that you will use all the images specified via the <strong>second</strong> image URL in each `additional_image_urls` value as images in the Carousel Ad. You can retrieve the additional images configured for a product via `additional_image_urls` returned by [/catalog/product/get/](https://ads.tiktok.com/marketing_api/docs?id=1740564136678402). </li><li> If you set a number greater than the total number of additional Image URLs specified for the product, this field will be ignored, and the main image (`image_url`) for the product will be used. You can retrieve the main image for a product via `image_url` returned by [/catalog/product/get/](https://ads.tiktok.com/marketing_api/docs?id=1740564136678402). </li></ul> |
| ad_text {+Conditional}| string | An ad text. It is shown to your audience as part of your ad creative, to deliver the message you intend to communicate to them. If you don't know how to create effective ad texts, you can try the [Smart Text](https://ads.tiktok.com/marketing_api/docs?id=1739084248002626) feature, which generates ad text recommendations based on the industry and language. <ul><li>This field is required if your ad is non-Spark Ads. </li><li>Ad text must be 1-100 characters long and cannot contain emoji.</li><li>Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character.</li></ul>|
| call_to_action {+Conditional} | string | Call-to-action text. <br><br>For TikTok ads, either this field or `call_to_action_id` must be specified. If both are specified, this field will be ignored. <br>For enum values, see [Enumeration - Call-to-Action](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). <br>For live shopping ads, the call to action must be `WATCH_LIVE`. <br><br><p><span style="color:darkred"><b>Note</b></span><ul><li>If `objective_type` is `REACH`or `VIDEO_VIEW`, and `landing_page_url` or `page_id` has been specified, then either `call_to_action` or `call_to_action_id` is required. </li><li>If `objective_type` is `APP_PROMOTION`, `WEB_CONVERSIONS`, or `TRAFFIC`, then either `call_to_action` or `call_to_action_id` is required.</li><li>If `objective_type` is `LEAD_GENERATION`: <ul><li> When `promotion_type` is `LEAD_GENERATION`, either `call_to_action` or `call_to_action_id` is required.</li><li> When `promotion_type` is `LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE`, `call_to_action` is required and must be set to `SEND_MESSAGE`.</li><li> When `promotion_type` is `LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE`, `call_to_action` is required and cannot be set to `CALL_NOW`. </li><li> When `promotion_type` is `LEAD_GEN_CLICK_TO_CALL`, `call_to_action` is required. </li></ul></li><li>If `objective_type` is `PRODUCT_SALES`, `call_to_action` is required.</li></ul>|
| call_to_action_id {+Conditional} | string | The ID of the CTA portfolio that you want to use in your ads. A CTA portfolio is a group of auto-optimized CTAs. If both this field and `call_to_action` are specified, `call_to_action` will be ignored. For details about auto-optimized CTAs, see [CTA recommendations > Dynamic CTAs](https://ads.tiktok.com/marketing_api/docs?id=1740307296329730).<br><p><span style="color:darkred"><b>Note</b></span><ul><li>If `objective_type` is `REACH`or `VIDEO_VIEW`, and `landing_page_url` or `page_id` has been specified, then either `call_to_action` or `call_to_action_id` is required. </li><li>If `objective_type` is `APP_PROMOTION`, `WEB_CONVERSIONS`, or `TRAFFIC`, then either `call_to_action` or `call_to_action_id` is required.</li><li> If `ad_format` is `CAROUSEL_ADS` or `CATALOG_CAROUSEL`, `call_to_action_id` is not supported.</li><li>If `objective_type` is `LEAD_GENERATION`: <ul><li> When `promotion_type` is `LEAD_GENERATION`, either `call_to_action` or `call_to_action_id` is required.</li><li> When `promotion_type` is `LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE`, `LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE`, or `LEAD_GEN_CLICK_TO_CALL`, `call_to_action_id` is not supported.</li></ul></li></ul>|
| card_id | string | Creative portfolio ID. <br><br>Pass the ID of one of the following creative portfolio types:<ul><li>Display Card.</li><li> Website info card.</li><li>Product Card.</li><li>Product Tiles.</li><li>Countdown Sticker. </li><li>Gift Code Sticker.</li><li>Gesture.</li><li>Superlike. </li></ul><br>To create a creative portfolio, use [/creative/portfolio/create/](https://business-api.tiktok.com/portal/docs?id=1739091950439426).<br><ul><li>To learn about how to get the ID of a Display Card, Website info card, Product Card, or Product Tiles portfolio, see [Cards](https://business-api.tiktok.com/portal/docs?id=1749019652141058).</li><li>To learn about how to get the ID of a Countdown Sticker or Giftcode Sticker portfolio, see [Stickers](https://business-api.tiktok.com/portal/docs?id=1749019667506177).</li><li>To learn about how to get the ID of a Gesture or Superlike portfolio, see [Premium Add-ons](https://business-api.tiktok.com/portal/docs?id=1749019676181505).</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can use this field in the following scenarios. <ul><li> When `objective_type`= `REACH`, `promotion_type`= `WEBSITE`, `placements`= `PLACEMENT_TIKTOK`, and `ad_format` = `SINGLE_VIDEO`.</li><li>When `objective_type`= `TRAFFIC`, `promotion_type`= `WEBSITE`, `placements`= `PLACEMENT_TIKTOK`, and `ad_format` = `SINGLE_VIDEO`.</li><li>When `objective_type`= `VIDEO_VIEWS`, `placements`= `PLACEMENT_TIKTOK`, and `ad_format` = `SINGLE_VIDEO`.</li><li>When `objective_type`=  `ENGAGEMENT`, you can only specify a Countdown Sticker portfolio through this field.</li><li>When `objective_type`= `APP_PROMOTION`, `promotion_type`= `WEBSITE`,  `placements`= `PLACEMENT_TIKTOK`,  and `ad_format` = `SINGLE_VIDEO`.</li><li>When `objective_type`= `LEAD_GENERATION`, you can only specify a Display Card portfolio through this field. </li><li>When `objective_type`=`WEB_CONVERSIONS`, `placements`= `PLACEMENT_TIKTOK`, and `ad_format` = `SINGLE_VIDEO`.</li><li>When `objective_type`=`PRODUCT_SALES`, `placements`= `PLACEMENT_TIKTOK`, and `ad_format` = `SINGLE_VIDEO`.|
| landing_page_url {+Conditional} | string | Landing page URL. <br><p><span style="color:darkred"><b>Note</b></span><ul><li>If `objective_type` is  `APP_PROMOTION`, `WEB_CONVERSIONS` or `TRAFFIC`, and `promotion_type` is `WEBSITE`, then either `page_id` or `landing_page_url` is required.</li><li>If `objective_type` is `APP_PROMOTION` or `TRAFFIC`, and `promotion_type` is `APP_ANDROID`/`APP_IOS` , then neither `page_id` nor `landing_page_url` can be used.|
| page_id {+Conditional} | string | Used to preview TikTok Instant Page. <br><p><span style="color:darkred"><b>Note</b></span><ul><li>If `objective_type` is  `APP_PROMOTION`, `WEB_CONVERSIONS` or `TRAFFIC`, and `promotion_type` is `WEBSITE`, then either `page_id` or `landing_page_url` is required.</li><li>If `objective_type` is  `APP_PROMOTION`, or `TRAFFIC`, and `promotion_type` is `APP_ANDROID`/`APP_IOS` , then neither `page_id` nor `landing_page_url` can be used.</li><li> If `ad_format` is `CATALOG_CAROUSEL`, this field is not supported.</li></ul>|
| catalog_id {+Conditional} | string | Product catalog ID. Products whose links are shown in the ad are from this catalog. <br>When `ad_format` is `CATALOG_CAROUSEL`, this field is required.<br><br><p><span style="color:darkred"><b>Note</b></span>: You need to have Ad promotion or Catalog management permission for the catalog. To check your permission for a catalog, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401). The returned `catalog_role` should be `ADMIN` or `AD_PROMOTE`.|
|product_specific_type{+Conditional} |string|Different dimensions to choose products. <br><br>When `ad_format` is `CATALOG_CAROUSEL`, this field is required. The Carousel images will be dynamically chosen from the images for the specified catalog products, and the specified product range needs to include at least two products.<br><br>Enum values: <ul><li>`ALL`: Allow TikTok to dynamically choose from all catalog products.</li><li>`PRODUCT_SET`: Specify a product set. TikTok will dynamically choose products from this set.</li><li>`CUSTOMIZED_PRODUCTS`:Specify up to 20 products from your catalog. </li></ul><br><ul><li>If this field is set to `ALL`, you don't need to input `sku_ids` or  `item_group_ids`  or `product_set_id`.  </li><li>If this field is set to `PRODUCT_SET`, you need to pass in either `item_group_ids` or `product_set_id`.</li><li>If this field is set to `CUSTOMIZED_PRODUCTS`, you need to pass in `sku_ids` or `vehicle_ids`.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>When `shopping_ads_type` is `PRODUCT_SHOPPING_ADS` and `product_source` is `STORE`, you can set this field to `ALL` or `CUSTOMIZED_PRODUCTS`.<ul><li> If this field is set to `ALL`, you don't need to input `sku_ids`,`vehicle_ids`, `item_group_ids`, and `product_set_id`. </li><li>If this field is set to `CUSTOMIZED_PRODUCTS`, `item_group_ids` is required. </li></ul> </li><li>When `shopping_ads_type` is `VIDEO` and `product_source` is `STORE`, specify the products through `item_group_ids`. </li><li>When `shopping_ads_type` is `VIDEO` and `product_source` is `SHOWCASE`, specify the products through `showcase_products`.</li><li>When `shopping_ads_type` is `LIVE`, you don't need to specify the products. </li></ul>|
|item_group_ids {+Conditional}|string[]|<ul><li>If `catalog_id` is specified and `product_specific_type` is set to `PRODUCT_SET`, you need to pass in either `item_group_ids` or `product_set_id`.</li><li>Required in any of the following scenarios:<ul><li>`shopping_ads_type` is `VIDEO` and `product_source` is `STORE`. </li><li> `shopping_ads_type` is `PRODUCT_SHOPPING_ADS`,`product_source` is `STORE`, and `product_specific_type` is `CUSTOMIZED_PRODUCTS`.</li></ul></li></ul><br>Product SPU IDs. |
|product_set_id {+Conditional}|string|If `catalog_id` is specified and `product_specific_type` is set to `PRODUCT_SET`, you need to pass in either `item_group_ids` or `product_set_id`.<br><br>The ID of the product set.|
| sku_ids {+Conditional}|string[]| Required when the following conditions are both met:<ul><li>An E-commerce catalog is specified through `catalog_id`.</li><li>`product_specific_type` is set to `CUSTOMIZED_PRODUCTS`.</li></ul><br>IDs of the SKUs.<br><br>Max size: 20. |
| vehicle_ids{+Conditional}|string[]|Required when the following conditions are both met:<ul><li>An Auto-Model catalog is specified through `catalog_id`.</li><li>`product_specific_type` is set to `CUSTOMIZED_PRODUCTS`.</li></ul><br>IDs of vehicles.<br><br>Max size: 20. |
| auto_disclaimer_types | string[] |Valid only when the following conditions are met:<ul><li>`objective_type` is `LEAD_GENERATION`.</li><li>`catalog_enabled` is true.</li><li>An Auto-Model catalog is specified through `catalog_id`.</li><li>`ad_format` is `CATALOG_CAROUSEL`.</li></ul><br>The type of disclaimer to show in the [Automotive Carousel Ads for Models](https://business-api.tiktok.com/portal/docs?id=1829635758164994#item-link-Ad%20format%20as%20Catalog%20Carousel).<br><br>Enum values:<ul><li>`EMISSION`: Emission disclaimer.</li><li>`DISCOUNT`: Discount or offer disclaimer.</li></ul><br>Max size: 1. |
| catalog_authorized_bc_id | string | Same as `bc_id`. Please use this field when previewing catalog items. |
|dynamic_format|string|Not supported when `ad_format` is `CAROUSEL_ADS` or `CATALOG_CAROUSEL`.<br>Dynamic format. <br>Enum values: <ul><li>`UNSET`: Unset.</li><li>`DYNAMIC_CREATIVE`: smart creative. To display different videos, product cards and destinations to each person based on purchase intent, maximizing conversions. </li></ul> |
|vertical_video_strategy|string|Not supported when `ad_format` is `CAROUSEL_ADS` or `CATALOG_CAROUSEL`.<br><br>Video strategy.<br><br>Enum values:<ul><li>`SINGLE_VIDEO`: Use a video creative to promote your products.</li><li>`CATALOG_VIDEOS`: Generate a dynamic video based on information from the product catalog.</li><li>`LIVE_STREAM`: Use livestream to promote your products. Supported only for Live Shopping Ads with ad format as Real-time LIVE.</li><li>`UNSET`: Unset.</li></ul>It must be `UNSET` if `dynamic_format` = `DYNAMIC_CREATIVE`.|
|shopping_ads_video_package_id|string| Product video package ID. <br>Use the [/catalog/video/get/](https://ads.tiktok.com/marketing_api/docs?id=1740574099715073) endpoint to get video package IDs.|
|shopping_ads_fallback_type {+Conditional}|string| In the Shopping Ads retargeting scenario, the fallback website type when the deeplink fails to be triggered.<br><br>Enum values:<ul><li>`DEFAULT`:  Unset.</li><li>`CUSTOM`: Custom link. Fall back to the web page link you provide. You need to pass in `landing_page_url` at the same time.</li><li> `SHOPPING_ADS`:  Product link. Fall back to the web page link you've provided for each product in the catalog. </li></ul><br>The `SHOPPING_ADS` value is not supported when the following conditions are met: <ul><li> `shopping_ads_type` is set to `VIDEO` (Video Shopping Ads)</li><li> `product_source` is set to `CATALOG`</li><li>`ad_format` is set to `SINGLE_VIDEO` and `vertical_video_strategy` is set to `SINGLE_VIDEO` or `ad_format` is set to `CATALOG_CAROUSEL`</li></ul> <br>To learn about the supported scenarios for fallback types, see the "[Deeplink - Availability - Fallback type](https://business-api.tiktok.com/portal/docs?id=1779541971843073#item-link-Fallback type)" section.|
|dynamic_destination|string|Not supported when `ad_format` is `CAROUSEL_ADS` or `CATALOG_CAROUSEL`.<br>Dynamic destination strategy.<br>Enum values:<ul><li>`DLP`: Dynamic Landing Page. Displaying different destinations including a custom website or Instant Product Page to each person based on their profile, behavior, and intent to maximize ad conversion.</li><li> `UNSET`: Unset.</li></ul> |
|instant_product_page_used|boolean|Not supported when `ad_format` is `CAROUSEL_ADS` or `CATALOG_CAROUSEL`.<br>Whether to use TikTok Instant Product Page, an auto-generated TikTok instant page that displays a range of products and provides a native experience landing page. <ul><li>When `dynamic_destination `= `DLP`, you don't need this parameter. </li><li>When `dynamic_destination` = `UNSET`, you can set this field to `true` to use TikTok Instant Product Page, and you don't need to pass in `page_id`.</li></ul>|
```

#### Example
##### Preview Video Shopping Ads with product source as TikTok Shop
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "ADS_CREATION",
    "objective_type": "PRODUCT_SALES",
    "shopping_ads_type": "VIDEO",
    "product_source": "STORE",
    "placement": "PLACEMENT_TIKTOK",
    "identity_id": "{{identity_id}}",
    "identity_type": "TT_USER",
    "ad_format": "SINGLE_VIDEO",
    "item_group_ids": ["{{item_group_id_1}}", "{{item_group_id_2}}"],
    "vertical_video_strategy": "SINGLE_VIDEO",
    "video_id": "{{video_id}}",
    "ad_text": "{{ad_text}}"
}'
(/code)
```
##### Preview Video Shopping Ads with product source as Showcase
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "ADS_CREATION",
    "objective_type": "PRODUCT_SALES",
    "shopping_ads_type": "VIDEO",
    "product_source": "SHOWCASE",
    "placement": "PLACEMENT_TIKTOK",
    "identity_id": "{{identity_id}}",
    "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
    "identity_type": "BC_AUTH_TT",
    "showcase_products" : [
        {
            "item_group_id": "{{item_group_id_1}}",
            "store_id": "{{store_id_1}}"
        },
        {
            "item_group_id": "{{item_group_id_2}}",
            "store_id": "{{store_id_2}}"
        }
    ],
    "ad_format": "SINGLE_VIDEO",
    "vertical_video_strategy": "SINGLE_VIDEO",
    "video_id": "{{video_id}}",
    "call_to_action": "BOOK_NOW",
    "ad_text": "{{ad_text}}",
    "card_id": "{{card_id}}"
}'
(/code)
```
##### Preview Product Shopping Ads

```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "ADS_CREATION",
    "objective_type": "PRODUCT_SALES",
    "shopping_ads_type": "PRODUCT_SHOPPING_ADS",
    "product_source": "STORE",
    "store_id": "{{store_id}}",
    "placement": "PLACEMENT_TIKTOK",
    "product_specific_type": "ALL",
    "item_group_ids": ["{{item_group_id_1}}", "{{item_group_id_2}}"]
}'
(/code)
```
##### Preview Live Shopping Ads with ad format as Single Video

```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "ADS_CREATION",
    "objective_type": "PRODUCT_SALES",
    "shopping_ads_type": "LIVE",
    "placement": "PLACEMENT_TIKTOK",
    "identity_id": "{{identity_id}}",
    "identity_type": "TT_USER",
    "ad_format": "LIVE_CONTENT",
    "vertical_video_strategy": "SINGLE_VIDEO",
    "video_id": "{{video_id}}",
    "ad_text": "{{ad_text}}"
}'
(/code)
```
##### Preview Live Shopping Ads with ad format as Real-time LIVE

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "ADS_CREATION",
    "objective_type": "PRODUCT_SALES",
    "placement": "PLACEMENT_TIKTOK",
    "identity_id": "{{identity_id}}",
    "identity_type": "TT_USER",
    "shopping_ads_type": "LIVE",
    "ad_format": "LIVE_CONTENT",
    "vertical_video_strategy": "LIVE_STREAM"
}'
(/code)
```

##### Preview using ALL placements
```xcodeblock
(code curl http)
curl --location 'https://ads.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "ADS_CREATION",
    "identity_id": "{{identity_id}}",
    "identity_type": "CUSTOMIZED_USER",
    "objective_type": "TRAFFIC",
    "ad_format": "SINGLE_VIDEO",
    "promotion_type": "WEBSITE",
    "ad_text": "{{ad_text}}",
    "call_to_action": "SHOP_NOW",
    "placement": "ALL",
    "video_id": "{{video_id}}"
}'
(/code)
```
##### Preview using a single placment
```xcodeblock
(code curl http)
curl --location 'https://ads.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "ADS_CREATION",
    "identity_id": "{{identity_id}}",
    "identity_type": "CUSTOMIZED_USER",
    "objective_type": "TRAFFIC",
    "ad_format": "SINGLE_VIDEO",
    "promotion_type": "WEBSITE",
    "ad_text": "{{ad_text}}",
    "call_to_action": "SHOP_NOW",
    "placement": "PLACEMENT_TIKTOK",
    "preview_format": "SEARCH_RESULTS",
    "video_id": "{{video_id}}"
}'
(/code)
```

##### Preview non-VSA Carousel Ads you plan to create
```xcodeblock
(code curl http)
curl --location 'https://business-api.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "ADS_CREATION",
    "objective_type": "APP_PROMOTION",
    "is_smart_performance_campaign": false,
    "promotion_type": "APP_ANDROID",
    "placement": "PLACEMENT_TIKTOK",
    "ad_format": "CAROUSEL_ADS",
    "ad_text": "{{ad_text}}",
    "call_to_action": "DOWNLOAD_NOW",
    "instant_product_page_used": false,  
    "image_ids": ["{{image_id}}", "{{image_id}}"],
    "music_id": "{{music_id}}",
    "identity_id": "{{identity_id}}",
    "identity_type": "CUSTOMIZED_USER"
}'
(/code)
```

##### Preview VSA Carousel Ads you plan to create
```xcodeblock
(code curl http)
# VSA Carousel Ads with images from All products
curl --location 'https://ads.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "ADS_CREATION",
    "objective_type": "PRODUCT_SALES",
    "placement": "PLACEMENT_TIKTOK",
    "preview_format": "IN_FEED",
    "ad_format": "CATALOG_CAROUSEL",
    "identity_id": "{{identity_id}}",
    "identity_type": "CUSTOMIZED_USER",
    "call_to_action": "SHOP_NOW",
    "music_id": "{{music_id}}",
    "catalog_id": "{{catalog_id}}",
    "product_specific_type": "ALL",
    "carousel_image_index": 2,
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "ad_text": "{{ad_text}}",
}'

# VSA Carousel Ads with images from Customized products
curl --location 'https://ads.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "ADS_CREATION",
    "objective_type": "PRODUCT_SALES",
    "placement": "PLACEMENT_TIKTOK",
    "preview_format": "IN_FEED",
    "ad_format": "CATALOG_CAROUSEL",

    "identity_id": "{{identity_id}}",
    "identity_type": "CUSTOMIZED_USER",
        "call_to_action": "SHOP_NOW",
    "music_id": "{{music_id}}",
    "catalog_id": "{{catalog_id}}",
    "product_specific_type": "CUSTOMIZED_PRODUCTS",
    "sku_ids": [
        "{{sku_id}}",
        "{{sku_id}}"
    ],
    "carousel_image_index": 0,
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "ad_text": "AD_TEXT Testing"
}'

# VSA Carousel Ads with images from Product Set
curl --location 'https://ads.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "ADS_CREATION",
    "objective_type": "PRODUCT_SALES",
    "placement": "PLACEMENT_TIKTOK",
    "preview_format": "IN_FEED",
    "ad_format": "CATALOG_CAROUSEL",
    "identity_id": "{{identity_id}}",
    "identity_type": "CUSTOMIZED_USER",
    "call_to_action": "SHOP_NOW",
    "music_id": "{{music_id}}",
    "catalog_id": "{{catalog_id}}",
    "product_specific_type": "PRODUCT_SET",
    "product_set_id": "{{product_set_id}}",
    "carousel_image_index": 0,
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "ad_text": "AD_TEXT Testing"
}'
(/code)
```

### Preview existing ads

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>You need to have Operator or Admin permission for the ad account. To check your permission for an ad account, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401). The returned `advertiser_role` should be `ADMIN` or `OPERATOR`. </li><li>If the ad uses a catalog, you need to have Ad promotion or Catalog management permission for the catalog. To check your permission for a catalog, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401). The returned `catalog_role` should be `ADMIN` or `AD_PROMOTE`.</li></ul>|
|preview_type {Required}|string| Type of the ad you want to preview. To preview existing regular ads, please use `AD`. <br> <br><p><span style="color:darkred"><b>Note</b></span>: Currently the supported objectives are: `REACH`, `TRAFFIC`, `VIDEO_VIEWS` , `ENGAGEMENT`, `APP_PROMOTION`, `LEAD_GENERATION`, `WEB_CONVERSIONS`, `PRODUCT_SALES`, and `RF_REACH`. |
|ad_id {Required}|string| ID of the ad that you want to preview.|
|device|string[]| List of device models. <br><br>Use [/tool/device_model/](https://ads.tiktok.com/marketing_api/docs?id=1737172880570369) to get the enum values of device models.|
|language|string| Language for the ad. <br><br>Enum values: `ENGLISH`, `CHINESE`, `JAPANESE`. <br>Default value:`ENGLISH`.|
| placement | string |The app where you want to deliver your ads.  <br><br>Enum values: <ul><li>`PLACEMENT_TIKTOK`: Supported only when `objective_type` is  `REACH`, `TRAFFIC`,  `VIDEO_VIEWS`, `ENGAGEMENT`, `APP_PROMOTION`, `LEAD_GENERATION`,`WEB_CONVERSIONS`, or `PRODUCT_SALES`. </li><li>`PLACEMENT_PANGLE`: Supported only when `objective_type` is `TRAFFIC`,  `APP_PROMOTION`, `LEAD_GENERATION` (only when `promotion_type` is `LEAD_GENERATION`), or `WEB_CONVERSIONS`. </li><li>`ALL`: Supported only when `objective_type` is `TRAFFIC`,  `APP_PROMOTION`, `LEAD_GENERATION` (only when `promotion_type` is `LEAD_GENERATION`), or `WEB_CONVERSIONS`. </li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You need to pass in this field if you want to set non-TikTok placement for your ad. Otherwise, this field will be set as TikTok placement (`PLACEMENT_TIKTOK`) by default.|
```
#### Example

```xcodeblock
(code curl http)
curl --location 'https://ads.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "AD",
    "ad_id": "{{ad_id}}"
}'
(/code)
```

### Preview an existing interactive add-on

```xtable
|Field|Data Type|Description|
|-|-|-|
| advertiser_id {Required} | string | Advertiser ID. |
| preview_type {Required} | string |Preview type. <br>To preview an existing interactive add-on, use `CARD`. |
| card_id | string | Creative portfolio ID. <br><br>Pass the ID of one of the following creative portfolio types:<ul><li>Display Card.</li><li>Countdown Sticker.</li><li>Gift Code Sticker.</li></ul><br>To create a creative portfolio, use [/creative/portfolio/create/](https://business-api.tiktok.com/portal/docs?id=1739091950439426).<ul><li>To learn about how to get the ID of a Display Card portfolio, see [Cards](https://business-api.tiktok.com/portal/docs?id=1749019652141058). </li><li>To learn about how to get the ID of a Countdown Sticker or Giftcode Sticker portfolio, see [Stickers](https://business-api.tiktok.com/portal/docs?id=1749019667506177). </li></ul> |
```
#### Example
```xcodeblock
(code curl http)
curl --location 'https://ads.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "CARD",
    "card_id": "{{card_id}}"
}'
(/code)
```

### Preview existing instant page

```xtable
|Field|Data Type|Description|
|-|-|-|
| advertiser_id {Required} | string | Advertiser ID. |
| preview_type {Required} | string | Type of the ad that you want to preview. To preview instant product landing page, use `PAGE`. |
| page_id {+Conditional} | string | Page ID. <br><br><ul><li>To create instant pages, use [Instant Page Editor SDK](https://business-api.tiktok.com/portal/docs?id=1740307202170881).</li><li>After you create an instant page, call [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586) to get the page ID.</li></ul>. |
```

#### Example

```xcodeblock
(code curl http)
curl --location 'https://ads.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "PAGE",
    "page_id": "{{page_id}}"
}'
(/code)
```

### Preview videos

```xtable
|Field|Data Type|Description|
|-|-|-|
| advertiser_id{Required} | string | Advertiser ID. |
| preview_type{Required} | string | Type of the ad that you want to preview. To preview video ads based on parameters, use `SINGLE_VIDEO`. |
| video_id{Required} | string | Video ID. Different placements have different creative specifications. See the [Ad Formats](https://ads.tiktok.com/help/article/image-ads-specification?redirected=1) chapter on TikTok Business Help Center for details. |
| thumbnail | string | ID of the thumbnail. You can get suggested thumbnails by using the [Get suggested thumbnails](https://ads.tiktok.com/marketing_api/docs?id=1740051189071873) endpoint. |
| profile_image | string | ID of the profile image. |
| display_name{Required} | string | Brand name. Number of characters allowed: 1-40 English characters, or 1-20 Chinese/Japanese/Korean characters.|
| ad_text{Required} | string | Ad text. Number of characters allowed: 1-100. |
| call_to_action{+Conditional} | string | Call-to-action text. For TikTok ads, either this field or `call_to_action_id` must be specified. If both are specified, this field will be ignored. For enum values, see [Enumeration - Call-to-action](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). For live shopping events, the call to action must be `WATCH_LIVE`.|
| call_to_action_id{+Conditional} | string | The ID of the CTA portfolio that you want to use in your ads. A CTA portfolio is a group of auto-optimized CTAs. If both this field and `call_to_action` are specified, this field will be used, and `call_to_action` will be ignored. For details about auto-optimized CTAs, see [CTA recommendations > Dynamic CTAs](https://ads.tiktok.com/marketing_api/docs?id=1740307296329730).|
| placements{Required} | string[] | Placement. For enum values, see [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). Different placements have different creative specifications. See the [Ad Formats](https://ads.tiktok.com/help/article/image-ads-specification?redirected=1) chapter on TikTok Business Help Center for details.|
|device|string[]| List of device models. Use [Get device models](https://ads.tiktok.com/marketing_api/docs?id=1737172880570369) to get the enum values of device models.|
|language|string| Language for the ad. Enum values: `ENGLISH`, `CHINESE`, `JAPANESE`. Default: `ENGLISH`.|
```

#### Example
```xcodeblock
(code curl http)
curl --location 'https://ads.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "SINGLE_VIDEO",
    "video_id": "{{video_id}}",
    "display_name": "{{display_name}}",
    "ad_text": "{{ad_text}}",
    "call_to_action": "APPLY_NOW",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "device": [
        "{{device}}"
    ],
    "language": "CHINESE"
}'
(/code)
```

### Preview images

```xtable
|Field|Data Type|Description|
|-|-|-|
| advertiser_id{Required} | string | Advertiser ID. |
| preview_type{Required} | string | Type of the ad that you want to preview. To preview image ads based on parameters, use `SINGLE_IMAGE`. |
| image_id{Required} | string | Image ID. Different placements have different creative specifications. See the [Ad Formats](https://ads.tiktok.com/help/article/image-ads-specification?redirected=1) chapter on TikTok Business Help Center for details.|
| display_name{Required} | string | Brand name. Number of characters allowed: 1-40 English characters, or 1-20 Chinese/Japanese/Korean characters.|
| ad_text{Required} | string | Ad text. Number of characters allowed: 1-100. |
| call_to_action{+Conditional} | string | Call-to-action text. For TikTok ads, either this field or `call_to_action_id` must be specified. If both are specified, this field will be ignored. For enum values, see [Enumeration - Call-to-action](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). For live shopping events, the call to action must be `WATCH_LIVE`.|
| call_to_action_id{+Conditional} | string | The ID of the CTA portfolio that you want to use in your ads. A CTA portfolio is a group of auto-optimized CTAs. If both this field and `call_to_action` are specified, this field will be used, and `call_to_action` will be ignored. For details about auto-optimized CTAs, see [CTA recommendations > Dynamic CTAs](https://ads.tiktok.com/marketing_api/docs?id=1740307296329730).|
| placements{Required}| string[] | Placement. For enum values, see [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). Different placements have different creative specifications. See the [Ad Formats](https://ads.tiktok.com/help/article/image-ads-specification?redirected=1) chapter on TikTok Business Help Center for details.|
|device|string[]| List of device models. Use [Get device models](https://ads.tiktok.com/marketing_api/docs?id=1737172880570369) to get the enum values of device models.|
|language|string| Language for the ad. Enum values: `ENGLISH`, `CHINESE`, `JAPANESE`. Default: `ENGLISH`.|
```

#### Example
```xcodeblock
(/code)
(code curl http)
curl --location 'https://ads.tiktok.com/open_api/v1.3/creative/ads_preview/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "SINGLE_IMAGE",
    "image_id": "{{image_id}}",
    "ad_text": "{{ad_text}}",
    "display_name": "{{display_name}}",
    "call_to_action": "CONTACT_US",
    "placements": [
        "PLACEMENT_PANGLE"
    ],
    "device": [
        "{{device}}"
    ],
    "language": "CHINESE"
}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1701890984602626).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1701890984602626).|
|data|json|Returned data.|
#| preview_link | string | Preview link.<br><br>Validity period: <ul><li>30 days when `preview_type` is `ADS_CREATION` or `AD`.</li><li>24 hours when `preview_type` is `CARD`, `PAGE`, `SINGLE_VIDEO` or `SINGLE_IMAGE`. </li></ul>|
#|iframe|string| iframe code snippet with the preview link. You can embed the iframe code to your HTML file.|
#| tips | list | Adjustment advice. Modify the materials according to the prompts to obtain a better display effect. |
##| placement{+ Conditional} | string | Incorrect placement. For enum values, see [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1701890985340929). If it is a general prompt message (applicable to all placements), then this field will not be returned. |
##| messages | string[] | Messages. |
|request_id|string|Request ID.|
```

### Example

```xcodeblock
(code Success-Response python)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "tips": [
            {
                "messages": [
                    "The resolution should be 1280*720 at least."
                ],
                "placement": "PLACEMENT_TIKTOK"
            }
        ],
        "preview_link": "https://ads.tiktok.com/ad_preview_tool?ad_preview_id=5681cba0fcd211eab33c351a98xxxxx",
        "iframe": "<iframe src="https://ads.tiktok.com/ad_preview_tool?ad_preview_id=5681cba0fcd211eab33c351a98xxxxx"></iframe>"
    },
    "request_id": "202009221251300101151531921B1D3F96"
}
(/code)
```
