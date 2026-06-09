---
title: "Update Smart Creative materials"
doc_id: 1739473077112833
path: "API Reference / Smart Creative / Update Smart Creative materials"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update Smart Creative materials

Use this endpoint to modify Smart Creative ad creatives. You can modify call-to-action, ad names, images, and video materials. This is a totally new endpoint in v1.3.

This endpoint supports incremental updates. To modify an updateable setting, you can set `patch_update` to `true` and pass only the field to be updated and the required fields, including the Header parameters, `advertiser_id`, and `adgroup_id`.

> <span style="color:DodgerBlue">**Note**</span><br>
>   If you want to update one or more parameters in an array type field, regardless of whether you set `patch_update` to `true`, you need to pass all original parameters from when you created the Smart Creative ad in that array type field, along with their updated values. If you only pass some parameters, it may result in errors or cause unspecified parameters to be overwritten with default values or null values. <ul><li>For example, if you initially passed `tiktok_item_id`, `identity_id`, and `identity_type` in `media_info_list` when creating the ad, and you want to update only `tiktok_item_id`, you still need to pass all three fields (`tiktok_item_id`, `identity_id`, and `identity_type`) in `media_info_list`. The values for `identity_id` and `identity_type` should remain unchanged.</li><li> If you specified multiple creative materials in `media_info_list` when creating the ad, and you want to update only one creative material, you still need to provide information for all creative materials in `media_info_list`. </li></ul>

  

## Notes
See the table below to learn about the notes about returned data, supported update scope, update mode, and creative requirements for this endpoint.

``` xtable
| Type {20%} | Details {70%} |
|---|---|---|
| Returned data | The response of endpoint `/ad/aco/update/` is a mock result which returns the request parameters you've passed in, because the data processing is not finished instantly. To obtain the actual accurate result, wait for 2 or 3 seconds after your call to the endpoint `/ad/aco/update/`and then use the endpoint [/ad/aco/get/]( https://ads.tiktok.com/marketing_api/docs?id=1739473020978177).|
| Supported update scope | The endpoint `/ad/aco/update/` doesn't support updating Smart Creative ad creatives for a Smart+ Campaign. Automatically generated ad names cannot be modified. |
| Creative requirements | Avoid duplicated title, creative material, and call-to-action. |
```


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/ad/aco/update/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type<br data-tomark-pass>Allowed format: `"application/json"`  |
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|adgroup_id {Required}|string| Ad group ID. <br> <br><p><span style="color:darkred"><b>Note</b></span>: Pass the ID of an ad group that you have enabled Smart Creative for. The `creative_material_mode` for the ad group should be `SMART_CREATIVE`.|
| patch_update | boolean | Whether to use incremental update mode. <br><br>For example, to update the ad texts by using incremental update mode, configure the following settings only: <ul><li> Set `patch_update` to `true`. </li><li> Set `title_list` to the new ad texts.</li><li> Pass the required fields, including the Header parameters, `advertiser_id`, and `adgroup_id`. </li></ul>|
|media_info_list|object[]|List of media information.|
#|media_info |object | Material information. |
##|video_info  |object| Video information. |
###|video_id {+Conditional}|string| Required when `video_info` is passed.<br><br> Video ID. <br><br>You can get the video ID when you upload a video using the [/file/video/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449) endpoint.<br><br><p><span style="color:darkred"><b>Note</b></span>: To ensure a smooth integration journey, we recommned that you use [/file/video/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449) ( with `upload_type` as `UPLOAD_BY_VIDEO_ID`) to bind video ID with your advertiser ID. |
###|file_name |string| Video name. |
##|image_info |object[]|  Image information. <ul><li>When you want to upload image materials (`video_info` is NOT included in the request), this field will be used as image materials. </li><li>When you want to upload video materials (`video_info` is included in the request), this field will be used as the video cover. Only one picture can be submitted in each `image_info` object. </li></ul> |
###|web_uri {+Conditional}|string|Required when `image_info` is passed. <br><br>Image ID. <br><br>You can find the image ID in the response after you upload an image via the [/file/image/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642) endpoint. |
###|file_name |string| Image name. If image material is used, this field is used to form ad's name.|
##| aigc_disclosure_type | string | Valid only when `identity_type` within `common_material` is `CUSTOMIZED_USER`. <br><br>Whether to turn on the AIGC (Artificial Intelligence Generated Content) self-disclosure toggle to indicate the ad contains AI-generated content. After the toggle is turned on, your ad will carry an "Advertiser labeled as Al-generated" label when viewed in full. <br><br> Enum values: <ul><li>`SELF_DISCLOSURE`: To turn on the toggle to declare that the ad contains AI-generated content. </li><li>`NOT_DECLARED`: To not declare that the ad contains AI-generated content.</li></ul>Default value: `NOT_DECLARED`.<br><br>To learn about the supported advertising objectives and ad formats for the toggle and the detailed steps for using the toggle, see [AIGC self-disclosure toggle](https://business-api.tiktok.com/portal/docs?id=1799186631031809). <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>You can only update this field when you update the video (`video_id`).</li></ul>|
##| tiktok_item_id |string|The ID of the TikTok post to be used as an ad (Spark Ad). <br><br>Pass in the `tiktok_item_id` you get from the response of the [/tt_video/info/](https://ads.tiktok.com/marketing_api/docs?id=1738376324021250) and [/identity/video/get/](https://ads.tiktok.com/marketing_api/docs?id=1740218475032577) endpoints. <br><br><p><span style="color:darkred"><b>Note</b></span>: By using Spark Ads, you confirm that you have the rights to use the music in the videos for commercial purposes.<br>Once you pass in `tiktok_item_id`, you don't have to pass in the objects `image_info`, `video_info`, and `title_list`.|
##| identity_id |string|Identity ID for Spark Ads. |
##| identity_type |string|Identity type for Spark Ads.<br><br> Enum values: `AUTH_CODE`, `TT_USER`. For details about identities, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097).|
|title_list|object[]|List of ad titles (also called ad texts). Ad titles are shown to your audience as part of your ad creative, to deliver the message you intend to communicate to them.|
#|title{+Conditional}|string|Required when `title_list` is passed.<br><br> Ad title (ad text). If you don't know how to create effective ad texts, you can try the [Smart Text](https://ads.tiktok.com/marketing_api/docs?id=1739084248002626) feature, which generates ad text recommendations based on the industry and language. <ul><li> An ad text must be 12-40 characters long and cannot contain emoji.</li><li>Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character. </li></ul>|
|call_to_action_list |object[]|Call-to-action list. <br><p><span style="color:darkred"><b>Note</b></span>: You can include at most three call-to-action texts (`call_to_action`) in each `call_to_action_list` object. |
#|call_to_action{+Conditional}|string|Required when `call_to_action_list` is passed.<br><br>Call-to-action text. For enum values, see [Enumeration - Call-to-action](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
|deeplink_list|object[]|List of deeplinks. Length range: [0,1].<br><p><span style="color:darkred"><b>Note</b></span>: Only one `deeplink` and corresponding `deeplink_type`  can be submitted in each `deeplink_list` object.|
#|deeplink|string|The specific location where you want your audience to go if they have your app installed.|
#|deeplink_type|string|The deeplink type. Enum values differs based on campaign objectives. Enum values: `NORMAL` (supported in Traffic, Conversion), `DEFERRED_DEEPLINK` (supported in App Install). The default value is `NORMAL`. For an ad that will be included in an iOS 14 campaign, this field cannot be set to `DEFERRED_DEEPLINK`.|
|display_name_list {+Conditional}|object[]|Display names. <br><p><span style="color:darkred"><b>Note</b></span>: You can pass only one `app_name` or `landing_page_name`  into each `display_name_list` object.|
#|app_name {+Conditional}|string| App name that is displayed in the ad. If not specified, the name in the app store will be used. Number of characters allowed: 1-40. If the name in the app store exceeds 40 characters, you must pass in a new app name with this field.|
#|landing_page_name|string|The display name of the landing page, required when the promotion type is landing page. Length limit: 1-40 characters. |
|avatar_icon_list|object[]|Avatar image list.<br><p><span style="color:darkred"><b>Note</b></span>: When you pass in  `identity_id` in `media_info` or `common_material`, you don't need to pass in this object, and when you do, it'll be ignored. <br>You can only pass in only one avatar image ID (`web_uri`). |
#|avatar_icon|object| Avatar image.|
##|web_uri|string| ID of the avatar image. It can be uploaded through the [Upload an image](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642) endpoint (picture ratio requirement is 1: 1)|
|page_list|object[]|Page ID list.|
#|page_id{+Conditional}|string|Required when `page_list` is passed.<br><br>Page ID.<br><br><ul><li>To obtain pages within your ad account, use [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586).</li><li>To create instant pages, use [Instant Page Editor SDK](https://business-api.tiktok.com/portal/docs?id=1740307202170881).</li></ul>|
|card_list|object[]|Card ID list. Length range: [0,1].|
#|card_id|string|Display Card ID, Gift Code Sticker ID, Countdown Sticker ID, or Download Card ID.<ul><li> To learn about how to get a Display Card ID or Download Card ID, please see [Cards](https://business-api.tiktok.com/portal/docs?id=1749019652141058).</li><li>To learn about how to get a Countdown Sticker ID, see [Stickers](https://ads.tiktok.com/marketing_api/docs?id=1749019667506177).|
|common_material|object|Common material.|
#|ad_name |string|Ad name. Set as " " (Empty string) for it to be automatically generated. The format of auto-generated ad name is: creative name + creation time (e.g. adcreative20210111190739). Character limit is 512 and cannot contain emoji.<br><p><span style="color:darkred"><b>Note</b></span>: Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character.<br>If you don't specify ad name here, you will not be able to change the name via `/ad/aco/update/` endpoint.|
#|call_to_action_id|string|The ID of the CTA portfolio that you want to use in your ads. A CTA portfolio is a group of auto-optimized CTAs. For details about auto-optimized CTAs, see [CTA recommendations > Dynamic CTAs](https://ads.tiktok.com/marketing_api/docs?id=1740307296329730). <br><p><span style="color:darkred"><b>Note</b></span>: If both this field and `call_to_action_list` are specified, `call_to_action_list` will be ignored. |
#|creative_authorized|boolean|Whether you grant displaying some of your ads in our TikTok for Business Creative Center. Only valid for non-US advertisers, the default value is `false`.<br><p><span style="color:darkred"><b>Note</b></span>: `creative_authorized` can only be used for video ads. |
#|playable_url|string|Playable material url, valid only in Pangle placement. You can get the url via the [/playable/get/](https://ads.tiktok.com/marketing_api/docs?id=1737732758495234) endpoint. Note that all ads in the same ad group share the same playable material.|
#|fallback_type|string|Fallback Type. If the audience do not have the app installed, you can have them fall back to install the app, or to view a specific web page.  Enum values: `APP_INSTALL `, `WEBSITE`, `UNSET`. If website is chosen, you need to specify the url via `landing_page_urls` field.<br><p><span style="color:darkred"><b>Note</b></span>: Not applicable for Deferred Deeplink. |
#|tracking_info|object|Tracking information.|
##|impression_tracking_urls|string[]|Default Impression Tracking URL. URL generated by your data partner to track impression events in your ads. Generally you can find and copy the URL from their platform. <ul><li> If the partner ID for the App (`app_id` specified at the ad group level ) that you want to track is `44` (TikTok Business SDK) or `49` (TikTok App API), you don't need to pass in this field. If you do, this field will be ignored. You can obtain the partner ID of an App through `partner_id` returned from [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) or [/app/info/](https://ads.tiktok.com/marketing_api/docs?id=1740859272887297).</li><li> If self-attribution has been enabled for the App (`app_id` specified at the ad group level ) that you want to track and the partner ID for the App is not `44` (TikTok Business SDK) or `49` (TikTok App API), you don't need to pass in this field because this field will default to the Default Impression Tracking URL you have configured for the App, and updates to the URL are not supported. You can check whether self-attribution has been enabled for the App through `self_attribution_enabled` returned from [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) or [/app/info/](https://ads.tiktok.com/marketing_api/docs?id=1740859272887297).</li></ul>You can pass in only one URL.|
##| click_tracking_urls|string[]|Click Tracking URL. URL generated by your data partner to track click events in your ads. Generally you can find and copy the URL from their platform. <ul><li> If the partner ID for the App (`app_id` specified at the ad group level ) that you want to track is `44` (TikTok Business SDK) or `49` (TikTok App API), you don't need to pass in this field. If you do, this field will be ignored. You can obtain the partner ID of an App through `partner_id` returned from [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) or [/app/info/](https://ads.tiktok.com/marketing_api/docs?id=1740859272887297).</li><li> If self-attribution has been enabled for the App (`app_id` specified at the ad group level ) that you want to track and the partner ID for the App is not `44` (TikTok Business SDK) or `49` (TikTok App API), you don't need to pass in this field because this field will default to the Click Tracking URL you have configured for the App, and updates to the URL are not supported. You can check whether self-attribution has been enabled for the App through `self_attribution_enabled` returned from [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) or [/app/info/](https://ads.tiktok.com/marketing_api/docs?id=1740859272887297).</li></ul>You can pass in only one URL. <br>Currently Pangle does not support DCM, Sizmek or Flashtalking.|
##|tracking_pixel_id|string| The pixel ID that you'd like to track. You can use this field to track offsite events. It supports the campaign objectives for both Auction ads (`REACH`, `VIDEO_VIEWS`, `TRAFFIC`, `WEB_CONVERSIONS`, `LEAD_GENERATION`, `APP_PROMOTION`, `PRODUCT_SALES`, `ENGAGEMENT`) and Reach & Frequency ads (`RF_REACH`).<ul><li>For Auction objectives<br>If `pixel_id`is specified at the ad group level <strong>and</strong> you want to use pixel to track offsite events, then the pixel ID you pass in the `tracking_pixel_id` field must be the same ID that you specified in the `pixel_id` field for the ad group. Otherwise, you can pass in any pixel ID that you'd like to track in the `tracking_pixel_id` field.<br><p><span style="color:darkred"><b>Note</b></span>: This field is an allowlist feature if your campaign objective is `ENGAGEMENT`/ `PRODUCT_SALES` (when `product_source` = `CATALOG` / `STORE` and `shopping_ads_type` = `VIDEO`) /`APP_PROMOTION`(when `app_promotion_type` = `APP_RETARGETING` ). If you want to use the field, please reach out to your TikTok representative.</li><li>For Reach & Frequency objectives <br>You can pass in any pixel ID that you'd like to track in the `tracking_pixel_id` field.|
##|tracking_app_id | string| The ID of the application that you want to track. You can use this field to track offsite app events. <br>This field supports the campaign objectives for both Auction ads (`REACH`, `VIDEO_VIEWS`, `TRAFFIC`,  `WEB_CONVERSIONS`, `LEAD_GENERATION`, `APP_PROMOTION`, `PRODUCT_SALES`, `ENGAGEMENT`) and Reach & Frequency ads (`RF_REACH`).<br>If `app_id` is specified at the ad group level and you want to track offsite app events, then the application ID you pass via this field must be the same ID that you specified at the ad group level. Otherwise, you can pass in any application ID that you'd like to track via this field.<br>You can get application ID (`app_id`) via [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786). <br><p><span style="color:darkred"><b>Note</b></span>: This field is currently an allowlist-only feature for the advertising objectives `APP_PROMOTION`, `PRODUCT_SALES`, and `ENGAGEMENT`. If you would like to access it for these three objectives, please contact your TikTok representative. Otherwise, you will get an error. |
##| tracking_offline_event_set_ids |string[]| A list of Offline Event set IDs that you want to track. You can use this field to track and measure offline activity from people that see or interact with your ads. <br>Max size: 50. <br>See [here](https://ads.tiktok.com/marketing_api/docs?id=1758051319816193) to learn more about how to create and manage Offline Event sets. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Offline Events Tracking is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li><li> If you are allowlisted for Offline Events Tracking, then existing auto-tracking Offline Event sets have to be tracked under a newly created ad: <ul><li> You can omit this parameter, then this field will automatically default to the IDs of all the <strong>existing</strong> auto-tracking Offline Event sets under the advertiser account, or </li><li> You can manually pass in this parameter. You need to specify via this field at least all the <strong>existing</strong> auto-tracking Offline Event set IDs, and non-auto-tracking ones are optional. To get the IDs of current auto-tracking Offline Event sets, call [/offline/get/](https://ads.tiktok.com/marketing_api/docs?id=1765596808589313) to get all Offline Event set IDs and then select all the auto-tracking ones according to the returned `auto_tracking` parameter. </li><li> An Offline Event set that has been changed from an auto-tracking one into a non-auto-tracking one, or has been deleted, is not regarded as one of the current auto-tracking Offline Event sets.</li></ul></li><li> This field supports the following campaign objectives: `REACH`, `VIDEO_VIEWS`, `TRAFFIC`, `LEAD_GENERATION`, `APP_PROMOTION`, `WEB_CONVERSIONS`, and `ENGAGEMENT`. </li></ul>|
#| identity_id{+conditional}|string| Identity ID. Required when you are not using Spark Ads (`tiktok_item_id`).|
#| identity_type{+conditional}|string|Identity type. Required when you are not using Spark Ads (`tiktok_item_id`). The only supported value is `CUSTOMIZED_USER`.  |
```

### Example
#### Update one video creative of a Smart Creative ad
``` xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/aco/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "patch_update":true,
    "media_info_list": [
        {
            "media_info": {
                "video_info": {
                    "video_id": "{{video_id}}" //updated video
                    },
                    "image_info": [
                        {
                        "web_uri": "{{web_uri}}" //updated video cover
                        }
                    ]
                }
        },
        {   
            "media_info": {
                "video_info": {
                    "video_id": "{{video_id}}" //original video 
                    },
                "image_info": [
                    {
                        "web_uri": "{{web_uri}}" //original video cover
                    }
                ]
            }
        }
    ]
}'
(/code);
```

#### Update the ad texts of a Smart Creative ad
``` xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/aco/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "patch_update":true,
    "title_list": [
        {
            "title": "{{title}}",
            "title": "{{title}}"
        }
    ]
}'
(/code);
```
## Response

``` xtable
|Field{35%}|Data Type{15%}|Desciption{50%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data.  |
#|advertiser_id|string| Advertiser ID.|
#|adgroup_id |string| Ad group ID.|
#|media_info_list |object[]| List of media information. |
###|material_id|string|Material ID.|
###|material_operation_status|string|The status of the material. Enum values: `ENABLE` (the material can be used), `DISABLE`(the material cannot be used).|
###|media_info|object|Media Information.|
####|image_info |object[]| Image information. |
#####|web_uri |string| Image ID. |
#####|file_name |string| Image name. |
####|video_info |object| Video information. |
#####|video_id|string| Video ID. |
#####|file_name |string| Video name. |
####|aigc_disclosure_type | string | Whether to turn on the AIGC (Artificial Intelligence Generated Content) self-disclosure toggle to indicate the ad contains AI-generated content. After the toggle is turned on, your ad will carry an "Advertiser labeled as Al-generated" label when viewed in full. <br><br> Enum values: <ul><li>`SELF_DISCLOSURE`: To turn on the toggle to declare that the ad contains AI-generated content. </li><li>`NOT_DECLARED`: To not declare that the ad contains AI-generated content.</li></ul>|
####| tiktok_item_id |string| The ID of the TikTok post to be used as an ad (Spark Ad). |
####| identity_id |string|Identity ID when you are using Spark Ads (`tiktok_item_id`).|
####| identity_type |string|Identity type when you are using Spark Ads (`tiktok_item_id`).  Enum values:  `AUTH_CODE`, `TT_USER`. For details about identities, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097). |
#|title_list |object[]|List of ad titles (also called ad texts). Ad titles are shown to your audience as part of your ad creative, to deliver the message you intend to communicate to them.|
##|title|string|Ad title (ad text).|
#|call_to_action_list |object[]|Call-to-action list.|
##|call_to_action|string|Call-to-action text. For enum values, see [Enumeration - Call-to-action](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
#|deeplink_list|object[]| List of deeplinks.|
##|deeplink|string|The specific location where you want your audience to go if they have your app installed.|
##|deeplink_type|string|The deeplink type. Enum values differs based on campaign objectives. Enum values: `NORMAL` (supported in Traffic, Conversion), `DEFERRED_DEEPLINK` (supported in App Install ). The default value is `NORMAL`. For an ad that will be included in an iOS 14 campaign, this field cannot be set to `DEFERRED_DEEPLINK`.|
#|display_name_list|object[]|Display names.|
##|app_name|string|App name.|
##|landing_page_name|string|Landing page name.|
#|avatar_icon|object|Avatar image information.|
##|web_uri|string| Avatar image ID.|
#|page_list|object[]|Page ID list.|
##|page_id|string|Page ID.|
#|card_list|object[]|Card ID list. Length range: [0,1].|
##|card_id|string|Display Card ID, Gift Code Sticker ID, Countdown Sticker ID, or Download Card ID.|
#|landing_page_urls|object[]|Landing page URL list.|
##|landing_page_url|string|Landing page URL.|
#|common_material|object|Common material.|
##|ad_name|string|Ad name.|
##|call_to_action_id|string|The ID of the CTA portfolio that you want to use in your ads. |
##|creative_authorized|boolean|Whether you grant displaying some of your ads in our TikTok for Business Creative Center. Only valid for non-US advertisers, the default value is `false`.<br><p><span style="color:darkred"><b>Note</b></span>: `creative_authorized` can only be used for video ads. |
##|playable_url|string|Playable material url.|
##|fallback_type|string|Fallback Type. If the audience do not have the app installed, you can have them fall back to install the app, or to view a specific web page.  Enum values: `APP_INSTALL`, `WEBSITE`, `UNSET`. <br><p><span style="color:darkred"><b>Note</b></span>: Not applicable for Deferred Deeplink. |
##|tracking_info|object|Tracking information.|
###|impression_tracking_urls|string[]|Default Impression Tracking URL.|
###| click_tracking_urls|string[]|Click Tracking URL.|
###|tracking_pixel_id|string|The pixel ID that is tracked. You can use this field to track offsite events.|
###|tracking_app_id | string| The ID of the application that is tracked. |
###| tracking_offline_event_set_ids |string[]|A list of Offline Event set IDs that are tracked.|
##| identity_id|string|Identity ID when you are not using Spark Ads (`tiktok_item_id`).|
##| identity_type|string|Identity type when you are not using Spark Ads (`tiktok_item_id`). The only supported value is `CUSTOMIZED_USER`. |
##|is_smart_creative|boolean|Whether Smart Creative is enabled for the ad group.  |
|request_id |string|The log id of a request, which uniquely identifies the request. |
```


### Example
#### Update one video creative of a Smart Creative ad
``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "call_to_action_list": [],
        "deeplink_list": [
            {
                "deeplink_type": "NORMAL",
                "deeplink": ""
            }
        ],
        "media_info_list": [
            {
                "media_info": {
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "video_info": {
                        "video_id": "{{video_id}}"
                    }
                }
            },
            {
                "media_info": {
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "video_info": {
                        "video_id": "{{video_id}}"
                    }
                }
            }
        ],
        "advertiser_id": "{{advertiser_id}}",
        "common_material": {
            "fallback_type": null,
            "tracking_info": {
                "click_tracking_urls": [],
                "tracking_app_id": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_ids}}",
                    "{{tracking_offline_event_set_ids}}"
                ],
                "impression_tracking_urls": [],
                "vast_moat_enabled": false,
                "tracking_pixel_id": "0"
            },
            "identity_type": "CUSTOMIZED_USER",
            "is_smart_creative": true,
            "creative_authorized": false,
            "call_to_action_id": "{{call_to_action_id}}",
            "playable_url": "",
            "identity_id": "{{identity_id}}",
            "ad_name": null
        },
        "card_list": null,
        "avatar_icon_list": [
            {
                "avatar_icon": {
                    "web_uri": "{{web_uri}}"
                }
            }
        ],
        "adgroup_id": "{{adgroup_id}}",
        "display_name_list": [
            {
                "app_name": "",
                "landing_page_name": "{{landing_page_name}}"
            }
        ],
        "title_list": [
            {
                "material_operation_status": "ENABLE",
                "title": "{{title}}",
                "material_id": "{{material_id}}"
            }
        ],
        "landing_page_urls": [
            {
                "landing_page_url": "{{landing_page_url}}"
            }
        ],
        "page_list": null
    }
}'
(/code);
```
#### Update the ad texts of a Smart Creative ad
``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "landing_page_urls": [
            {
                "landing_page_url": "{{landing_page_url}}"
            }
        ],
        "call_to_action_list": [],
        "media_info_list": [
            {
                "media_info": {
                    "image_info": [
                        {
                            "file_name": null,
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "aigc_disclosure_type": null,
                    "tiktok_item_id": null,
                    "identity_id": null,
                    "identity_type": null,
                    "material_name": "",
                    "video_info": {
                        "file_name": "",
                        "video_id": "{{video_id}}"
                    }
                }
            }
        ],
        "card_list": null,
        "title_list": [
            {
                "title": "{{title}}",
                "title": "{{title}}"
            }
        ],
        "adgroup_id": "{{adgroup_id}}",
        "advertiser_id": "{{advertiser_id}}",
        "avatar_icon_list": [
            {
                "avatar_icon": {
                    "web_uri": "{{web_uri}}"
                }
            }
        ],
        "display_name_list": [
            {
                "app_name": "",
                "landing_page_name": "{{landing_page_name}}"
            }
        ],
        "common_material": {
            "fallback_type": null,
            "is_smart_creative": true,
            "creative_authorized": false,
            "playable_url": "",
            "identity_id": "{{identity_id}}",
            "tracking_info": {
                "tracking_app_id": null,
                "impression_tracking_urls": [],
                "click_tracking_urls": [],
                "tracking_pixel_id": "0",
                "vast_moat_enabled": false,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ]
            },
            "call_to_action_id": "{{call_to_action_id}}",
            "identity_type": "CUSTOMIZED_USER",
            "ad_name": null
        },
        "deeplink_list": [
            {
                "deeplink_type": "NORMAL",
                "deeplink": ""
            }
        ],
        "page_list": null
    }
}'
(/code);
```
