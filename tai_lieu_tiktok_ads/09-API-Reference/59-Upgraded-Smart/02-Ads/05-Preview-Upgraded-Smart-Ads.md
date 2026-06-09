---
title: "Preview Upgraded Smart+ Ads"
doc_id: 1843317445798914
path: "API Reference / Upgraded Smart+ / Ads / Preview Upgraded Smart+ Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Preview Upgraded Smart+ Ads

Use this endpoint to preview an Upgraded Smart+ Ad that you want to create or an existing Upgraded Smart+ Ad.

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/preview/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request content type.<br> Allowed Value: `application/json`.|
```

**Parameters**

### Preview ads that you plan to create
This allows you to preview your ad before creating it.

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|preview_type {Required}|string|Preview type.<br><br>Enum value: <ul><li>`ADS_CREATION`: Preview an ad by ad creation parameters.</li></ul>|
|catalog_enabled|boolean|Whether to use catalog in the campaign.<br><br>Supported values: `true`, `false`.|
|catalog_id {+Conditional}|string|Required when `catalog_enabled` is `true`.<br><br>The ID of the catalog to use in the ad group.<br><br>To retrieve the catalogs within your Business Center, use [/catalog/get/](https://business-api.tiktok.com/portal/docs?id=1740315452868610).|
|catalog_authorized_bc_id {+Conditional}|string|Required when `catalog_enabled` is `true`.<br><br>The ID of the Business Center that the catalog (`catalog_id`) belongs to.|
|creative_list {Required}|object[]|A list of creatives.<br><br>Size range: 1- 31.|
#|creative_info {Required}|object|Creative information.|
##|video_info {+Conditional}|object|Required for the following types of ads:<ul><li>Non-Spark Ads Single Video ads. </li><li>Spark Ads Single Video ads through Spark Ads Push.</li></ul><br>Video information.|
###|video_id {+Conditional}|string|Required when `video_info` is specified.<br><br>Video ID.<br><br>To upload a video and obtain the video ID, use [/file/video/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1737587322856449).<br>To search for videos within your ad account, use [/file/video/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740050472224769).|
##|image_info {+Conditional}|object[]|<ul><li>Required for the following types of ads:<ul><li>Non-Spark Ads Single Video ads. You need to specify a video cover.</li><li>Spark Ads Single Video ads through Spark Ads Push. You need to specify a video cover.</li><li>Non-Spark Ads Standard Carousel ads. You need to specify one to 35 carousel images.</li><li>Spark Ads Standard Carousel ads through Spark Ads Push. You need to specify one to 35 carousel images.</li></ul></li><li>Not supported for Catalog Carousel ads.</li></ul><br>Image information.|
###|web_uri {+Conditional}|string|Required when `image_info` is specified.<br><br>Image ID.<br><br>To upload an image and obtain the image ID, use [/file/image/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1739067433456642).<br>To search for images within your ad account, use [/file/image/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740052016789506).|
##|music_info {+Conditional}|object|<ul><li>Required for the following scenarios:<ul><li>When you create Standard Carousel Ads, including:<ul><li>Non-Spark Ads Standard Carousel ads</li><li>Spark Ads Standard Carousel ads through Spark Ads Push</li><li>Spark Ads Standard Carousel ads through Spark Ads Pull</li></ul></li><li>When `objective_type` is `WEB_CONVERSIONS` and `catalog_creative_toggle` is `true`. The system will automatically generate Catalog Carousel ads.</li></ul></li></ul>Music information.|
###|music_id {+Conditional}|string|Required when `music_info` is specified.<br><br>The ID of the piece of music to use in the carousel ads.|
##|tiktok_item_id {+Conditional}|string|<ul><li>Required when you create Spark Ads through Spark Ads Pull, including:<ul><li>Spark Ads Single Video ads through Spark Ads Pull. You need to specify a TikTok video post.</li><li>Spark Ads Standard Carousel ads through Spark Ads Pull. You need to specify a TikTok photo post.</li></ul></li><li>Not supported when `catalog_creative_toggle` is `true`.</li></ul><br>The ID of the TikTok post to be used as an ad (Spark Ads).<br><br>Pass in the `item_id` you get from the response of the [/tt_video/info/](https://business-api.tiktok.com/portal/docs?id=1738376324021250) and [/identity/video/get/](https://business-api.tiktok.com/portal/docs?id=1740218475032577) endpoints.<br><br>When you pass in `tiktok_item_id`, you don't need to pass in the objects `image_info`, `video_info`, and `title_list`.<br><br><p><span style="color:darkred"><b>Note</b></span>: By using Spark Ads, you confirm that you have the rights to use the music in the videos for commercial purposes.|
##|identity_type {+Conditional}|string|Required when you create Spark Ads without using catalog creatives.<br><br>Identity type.<br><br>Enum values: `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT`.<br><br>For details about identities, see [Identities](https://business-api.tiktok.com/portal/docs?id=1738958351620097).<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If you want to create Spark Ads using catalog creatives from an E-commerce catalog, specify the `identity_type` and `identity_id` within `ad_configuration`.</li><li>If this field is set to `TT_USER`, `BC_AUTH_TT`, or `AUTH_CODE` and the following conditions are both met, you need to specify a dynamic CTA via `call_to_action_id`.<ul><li>At the campaign level, `objective_type` is `APP_PROMOTION` or `WEB_CONVERSIONS`.</li><li>At the ad group level, `placement_type` is `PLACEMENT_TYPE_NORMAL` and `placements` includes `PLACEMENT_TIKTOK`, or `placement_type` is `PLACEMENT_TYPE_AUTOMATIC`.</li></ul></li></ul>|
##|identity_id {+Conditional}|string|Required when you create Spark Ads without using catalog creatives.<br><br>Identity ID.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you want to create Spark Ads using catalog creatives from an E-commerce catalog, specify the `identity_type` and `identity_id` within `ad_configuration`.</p>|
##|identity_authorized_bc_id {+Conditional}|string|Required when `identity_type` within `creative_info` is `BC_AUTH_TT`.<br><br>ID of the Business Center that a TikTok Account User in Business Center identity is associated with.|
|ad_text_list {+Conditional}|object[]|Required when `tiktok_item_id` is not specified.<br><br>List of ad texts.<br>Ad texts are shown to your audience as part of your ad creatives, to deliver the message you intend to communicate to them.<br><br>Max size: 5.|
#|ad_text {+Conditional}|string|Required when `ad_text_list` is specified.<br><br>Ad text.|
|call_to_action_list|object[]|Call-to-action list.<br><br>Max size: 3.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>This field is not supported for Upgraded Smart+ Lead Generation Campaigns.</li><li>The preview link will only display the first call-to-action in this list.</li></ul>|
#|call_to_action {+Conditional}|string|Required when `call_to_action_list` is specified.<br><br>Call-to-action text.<br><br>For enum values, see [Enumeration - Call-to-action](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Call-to-action).|
|ad_configuration|object|Additional configurations.|
#|identity_type {+Conditional}|string|Required in any of the following scenarios:<ul><li>You are creating non-Spark Ads on non-TikTok placements. Non-Spark Ads on the TikTok placement are no longer supported.</li><li>You are creating Spark Ads using catalog creatives from an E-commerce catalog. Learn more about how you can create such ads in [Create Upgraded Smart+ E-commerce Catalog Ads](https://business-api.tiktok.com/portal/docs?id=1847302895272962).</li></ul><br>Identity type.<ul><li>Enum values for non-Spark Ads on non-TikTok placements: `CUSTOMIZED_USER`.</li><li>Enum values for Upgraded Smart+ E-commerce Catalog Spark Ads (with `catalog_creative_toggle` as `true`): `TT_USER`, `BC_AUTH_TT`.</li></ul><br>For details about identities, see [Identities](https://business-api.tiktok.com/portal/docs?id=1738958351620097).|
#|identity_id {+Conditional}|string|Required in any of the following scenarios:<ul><li>You are creating non-Spark Ads on non-TikTok placements. Non-Spark Ads on the TikTok placement are no longer supported.</li><li>You are creating Spark Ads using catalog creatives from an E-commerce catalog. Learn more about how you can create such ads in [Create Upgraded Smart+ E-commerce Catalog Ads](https://business-api.tiktok.com/portal/docs?id=1847302895272962).</li></ul><br>Identity ID.|
#|identity_authorized_bc_id {+Conditional}|string|Required when `identity_type` within `ad_configuration` is `BC_AUTH_TT`.<br><br>ID of the Business Center that a TikTok Account User in Business Center identity is associated with.|
#|product_specific_type {+Conditional}|string|Required when `catalog_enabled` is `true` at the campaign level.<br><br><br>Different dimensions to choose products.<br><br>Enum values:<ul><li>`ALL`: Allow TikTok to dynamically choose from all products.</li><li>`PRODUCT_SET`: Specify a product set. TikTok will dynamically choose products from this set.</li><li>`CUSTOMIZED_PRODUCTS`: Specify a customized number of products.</li></ul><br><ul><li> If this field is set to `ALL`, you don't need to input `product_set_id` and `product_ids`.</li><li> If this field is set to `PRODUCT_SET`, you need to pass in `product_set_id`.</li><li> If this field is set to `CUSTOMIZED_PRODUCTS`, `product_ids` is required.</li></ul>|
#|product_set_id {+Conditional}|string|Required when `product_specific_type` is `PRODUCT_SET`.<br><br>The ID of a product set.<br><br><ul><li>To retrieve the product sets within your ad account, use [/catalog/set/get/](https://business-api.tiktok.com/portal/docs?id=1740570556295169).</li><li> To create a product set, use [/catalog/set/create/](https://business-api.tiktok.com/portal/docs?id=1740572891104257).</li></ul>|
#|product_ids {+Conditional}|string[]|Required when `product_specific_type` is `CUSTOMIZED_PRODUCTS`.<br><br>The product IDs of the catalog products.<br><br>Max size: 20.<br><br>To retrieve the product ID (`product_id`) of each catalog product, use [/catalog/product/get/](https://business-api.tiktok.com/portal/docs?id=1740564136678402).|
#|catalog_creative_toggle|boolean|Valid only when `catalog_enabled` is `true`.<br><br>Whether to enable auto-selection of creatives from your catalog.<br><br>Supported values: `true`, `false`.<br>Default value: `false`.<br><br>If you set this field to `true`, you need to provide a `creative_info` with only `music_info` in it to specify the music to be used in Catalog Carousel.|
#|catalog_creative_info|object|Valid only when the following conditions are met:<ul><li>The ad is an [Upgraded Smart+ E-commerce Catalog Ad](https://business-api.tiktok.com/portal/docs?id=1847302895272962) or [Upgraded Smart+ Streaming Ad](https://business-api.tiktok.com/portal/docs/create-upgraded-smart-streaming-ads/v1.3).</li><li>`catalog_creative_toggle` is `true`.</li></ul><br>Additional settings for catalog creatives to use in your ads.|
##|catalog_media_settings|string[]|The types of creatives from your E-commerce or entertainment catalog to use in the ad.<br><br>Enum values for an [Upgraded Smart+ E-commerce Catalog Ad](https://business-api.tiktok.com/portal/docs?id=1847302895272962):<ul><li>`VIDEO`: Video.</li><li>`IMAGE`: Image.</li><li>`TEMPLATE_VIDEO`: Video templates. If you include this value in `catalog_media_settings`, you can optionally specify `catalog_template_video_id` at the same time.<ul><li>If `catalog_template_video_id` is not specified, all video templates and video URLs from your catalog will be used to generate the ad.</li><li>If `catalog_template_video_id` is specified, the selected video template will be used to generate the ad.</li></ul></li></ul><br>Enum values for an [Upgraded Smart+ Streaming Ad](https://business-api.tiktok.com/portal/docs/create-upgraded-smart-streaming-ads/v1.3):<ul><li>`VIDEO`: Video.</li><li>`TEMPLATE_VIDEO`: Video templates. If you include this value in `catalog_media_settings`, you can optionally specify `catalog_template_video_id` at the same time.<ul><li>If `catalog_template_video_id` is not specified, all video templates and video URLs from your catalog will be used to generate the ad.</li><li>If `catalog_template_video_id` is specified, the selected video template will be used to generate the ad.</li></ul></li><li>`MULTI_SHOW`: Multi-show. Multi-Show Experience is an auto-play video carousel experience designed to drive user exploration and engagement across a breadth of personally-relevant title offerings within your content library. Multi-Show Experience consists of 4 video tiles delivered from your catalog. Each tile's clip plays sequentially (starting in the upper left-hand corner), and offers you the ability to assign a corresponding title-specific landing page.</li></ul>|
##|catalog_template_video_id|string|Valid only when `TEMPLATE_VIDEO` is included in `catalog_media_settings`.<br><br>The ID of a video template in your catalog to use in the ad.<br><br>To obtain the IDs of video templates (video packages) in your catalog, use [/catalog/video_package/get/](https://business-api.tiktok.com/portal/docs?id=1740574099715073).<br>To learn about how to create video packages on TikTok Ads Manager, see [How to create video packages in a Catalog](https://ads.tiktok.com/help/article/how-to-create-video-packages-in-a-catalog).|
```

#### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/preview/' \
--header 'Access-Token: "{{Access-Token}}"' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "ADS_CREATION",
    "catalog_enabled": true,
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "creative_list": [
        {
            "creative_info": {
                "video_info": {
                    "video_id": "{{video_id}}"
                }
            },
            "image_info": [
                {
                    "web_uri": "{{web_uri}}"
                }
            ]
        },
        {
            "creative_info": {
                "tiktok_item_id": "{{tiktok_item_id}}",
                "identity_id": "{{identity_id}}",
                "identity_type": "BC_AUTH_TT",
                "identity_bc_id": "{{identity_bc_id}}"
            }
        },
        {
            "creative_info": {
                "image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    },
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ],
                "music_info": {
                    "music_id": "{{music_id}}"
                }
            }
        },
        {
            "creative_info": {
                "music_info": {
                    "music_id": "{{music_id}}"
                }
            }
        }
    ],
    "ad_text_list": [
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        }
    ],
    "call_to_action_list": [
        {
            "call_to_action": "APPLY_NOW"
        },
        {
            "call_to_action": "DOWNLOAD_NOW"
        }
    ],
    "ad_configuration": {
        "product_specific_type": "ALL",
        "catalog_creative_toggle": true
    }
}'
(/code)
```

### Preview existing ads

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|preview_type {Required}|string|Preview type.<br><br>Enum value:<ul><li>`AD`: preview an existing ad.</li></ul>|
|smart_plus_ad_id {Required}|string|ID of the Upgraded Smart+ Ad that you want to preview.<br><br>To obtain a list of Upgraded Smart+ Ad IDs, use [/smart_plus/ad/get/](https://business-api.tiktok.com/portal/docs?id=1843317378982914).|
```

#### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/preview/' \
--header 'Access-Token: "{{Access-Token}}"' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "preview_type": "AD",
    "smart_plus_ad_id": "{{smart_plus_ad_id}}"
}'
(/code)
```

## Response

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code|number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data.|
#|preview_link|string|Preview link.<br><br>Validity period: 30 days.|
#|iframe|string|The iframe code snippet with the preview link. You can embed the iframe code into your HTML file.|
```

### Example
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "iframe": "<iframe src=\"https://ads.tiktok.com/ad_preview_tool?ad_preview_id={{ad_preview_id}}\"></iframe>",
        "preview_link": "{{preview_link}}"
    }
}
(/code)
```

