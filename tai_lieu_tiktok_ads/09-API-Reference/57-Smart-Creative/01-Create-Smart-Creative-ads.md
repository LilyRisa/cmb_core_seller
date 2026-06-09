---
title: "Create Smart Creative ads"
doc_id: 1739473063234626
path: "API Reference / Smart Creative / Create Smart Creative ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Smart Creative ads

Use this endpoint to create Smart Creative ads by uploading necessary ad creatives to the library. With this endpoint, you can upload multiple ad texts, titles, materials, and call-to-actions to the library. The platform automatically combines your ad creatives and prepares them for delivery. `ad_id` is not returned in the response. This is a totally new endpoint in v1.3.

> <span style="color:DodgerBlue">**Note**</span><br>
> ACO ads are no longer supported in API. To ensure a smooth API integration, we recommend you migrate to [Smart Creative ads](https://business-api.tiktok.com/portal/docs?id=1767322784934914).  

## Notes
See the table below to learn about the notes about returned data, maximum number of ads, maximum number of materials, creative requirements and the required ad group setting for this endpoint, as well as the supported advertising objectives for Smart Creative endpoints.
 
``` xtable
| Type {35%} | Details {65%} |
|---|---|---|
| Returned data | The response of endpoint `/ad/aco/create/` is a mock result which returns the request parameters you've passed in, because the data processing is not finished instantly. To obtain the actual accurate result, wait for 2 or 3 seconds after your call to the endpoint `/ad/aco/create/`and then use the endpoint [/ad/aco/get/]( https://ads.tiktok.com/marketing_api/docs?id=1739473020978177).|
| Maximum number of ads | You can create a maximum of 450 Smart Creative ads each time you call `/ad/aco/create/`, and you can create a maximum of 5,000 ads for an advertiser account per day. |
| Maximum number of materials | <ul><li> Titles: 1-5.</li><li> Call-to-action texts: 1-3. </li><li> Media info: 1-30. </li><li> Page IDs : 1-3. </li></ul> |
| Creative requirements | Do not use duplicated ad titles, materials, or call-to-action texts in a Smart Creative ad. |
| Supported advertising objectives | Smart Creative endpoints are supported only when you have selected one of the following as the Advertising Objective (`objective_type`) for your campaign: <ul><li>App promotion (<code>APP_PROMOTION</code>)</li><li>Web conversions (<code>WEB_CONVERSIONS</code>)</li><li>Traffic (<code>TRAFFIC</code>)</li><li>Lead generation ( <code>LEAD_GENERATION</code>)</li></ul> |
```

## Ad name generation rules

* If a valid string is passed in to the `ad_name` field, and `file_name` in `image_info` or `video_info` is also passed in, then ad names will be generated in the format of "adName_" followed by sequence numbers, such as "adName_001".
* If a blank string is passed in to the `ad_name` field, and `file_name` in `image_info` or `video_info` is also passed in, then ad names will be generated in the format of "{file_name}_" followed by sequence numbers, for example, "image_001" or "video_002".
* If If a blank string is passed in to the `ad_name` field, but the `file_name` in `image_info` or `video_info` is not passed in, then ad names will be generated in the format of "_" followed by sequence numbers, for example, "_001". Try to avoid this case so that ad names can be more meaningful.




## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/ad/aco/create/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
|Content-Type {Required}|string|Request message type<br data-tomark-pass>Allowed format:`"application/json"`  |
```

**Parameters**

``` xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|adgroup_id {Required}|string| Ad group ID. <br><br><p><span style="color:darkred"><b>Note</b></span>: The ID should be of the ad group that you will enable Smart Creative for, and `creative_material_mode` for the ad group must be `SMART_CREATIVE`.|
|media_info_list{Required}|object[]|List of media information. <br><br>Max size: 30.|
#|media_info {Required}|object | Material information. |
##|video_info{+Conditional}  |object| Required when the material type is video and `tiktok_item_id` is not passed.<br><br>Video information. |
###|video_id {+Conditional} |string| Required when `video_info` is passed.<br><br>Video ID. <br><br>You can get the video ID when you upload a video using the [/file/video/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449) endpoint. <br><br><p><span style="color:darkred"><b>Note</b></span>: To ensure a smooth integration journey, we recommend that you use [/file/video/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449) (with `upload_type` as `UPLOAD_BY_VIDEO_ID`) to bind video ID with your advertiser ID. |
###|file_name |string| Video name. |
##|image_info{+Conditional} |object[]| Required when the material type is image or video and `tiktok_item_id` is not passed.<br><br> Image information. <ul><li>When you want to upload image materials (`video_info` is NOT included in the request), this field will be used as image materials.</li><li>When you want to upload video materials (`video_info` is included in the request), this field will be used as the video cover. Only 1 picture can be submitted in each `image_info` object.</li></ul>   |
###|web_uri {+Conditional}|string|Required when `image_info` is passed.<br><br> Image ID.<br><br> You can find the image ID in the response after you upload an image via the [/file/image/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642) endpoint. |
###|file_name |string| Image name. If image material is used, this field is used to form ad's name.|
##| aigc_disclosure_type | string | Valid only when `identity_type` within `common_material` is `CUSTOMIZED_USER`. <br><br>Whether to turn on the AIGC (Artificial Intelligence Generated Content) self-disclosure toggle to indicate the ad contains AI-generated content. After the toggle is turned on, your ad will carry an "Advertiser labeled as Al-generated" label when viewed in full. <br><br> Enum values: <ul><li>`SELF_DISCLOSURE`: To turn on the toggle to declare that the ad contains AI-generated content. </li><li>`NOT_DECLARED`: To not declare that the ad contains AI-generated content.</li></ul>Default value: `NOT_DECLARED`.<br><br>To learn about the supported advertising objectives and ad formats for the toggle and the detailed steps for using the toggle, see [AIGC self-disclosure toggle](https://business-api.tiktok.com/portal/docs?id=1799186631031809). <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>You can only update this field when you update the video (`video_id`).</li></ul>|
##| tiktok_item_id{+Conditional}|string|Required when `image_info` and `video_info` are not passed.<br><br>The ID of the TikTok post to be used as an ad (Spark Ad).<br><br> Pass in the `tiktok_item_id` you get from the response of the [/tt_video/info/](https://ads.tiktok.com/marketing_api/docs?id=1738376324021250) and  [/identity/video/get/](https://ads.tiktok.com/marketing_api/docs?id=1740218475032577) endpoints. <br><br>Once you pass in `tiktok_item_id`, you don't have to pass in the objects `image_info`, `video_info`, and `title_list`. <br><br><p><span style="color:darkred"><b>Note</b></span>: By using Spark Ads, you confirm that you have the rights to use the music in the videos for commercial purposes.|
##| identity_id{+Conditional}|string|Required when you use Spark Ads (`tiktok_item_id`).<br><br>Identity ID.|
##| identity_type{+Conditional}|string|Required when you use Spark Ads (`tiktok_item_id`). <br><br>Identity type. <br><br>Enum values: `AUTH_CODE`, `TT_USER`. <br><br>For details about identities, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097).|
|title_list|object[]|List of ad titles (also called ad texts). Ad titles are shown to your audience as part of your ad creative, to deliver the message you intend to communicate to them. <br><br>When you pass in `tiktok_item_id`, you don't need to pass in the objects `image_info`, `video_info`, and `title_list`.|
#|title {+Conditional}|string|Required when `title_list` is passed.<br><br>Ad title (ad text). If you don't know how to create effective ad texts, you can try the [Smart Text](https://ads.tiktok.com/marketing_api/docs?id=1739084248002626) feature, which generates ad text recommendations based on the industry and language. <ul><li> An ad text must be 12-40 characters long and cannot contain emoji.</li><li>Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character. </li></ul>|
|call_to_action_list {+Conditional} |object[]|Call-to-action list. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>For TikTok ads, either this field or `call_to_action_id` must be specified. If both are specified, this field will be ignored. </li><li>You can include at most three call-to-action texts (`call_to_action`) in each `call_to_action_list` object.</li></ul>|
#|call_to_action {+Conditional} |string|Required when `call_to_action_list` is passed.<br><br>Call-to-action text. For enum values, see [Enumeration - Call-to-action](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
|deeplink_list|object[]|List of deeplinks. Length range: [0,1].<br><p><span style="color:darkred"><b>Note</b></span>: Only one `deeplink` and corresponding `deeplink_type`  can be submitted in each `deeplink_list` object.|
#|deeplink|string|The specific location where you want your audience to go if they have your app installed.|
#|deeplink_type|string|The deeplink type. Allowed values differs based on campaign objectives. Allowed values: `NORMAL` (supported in Traffic, Conversion), `DEFERRED_DEEPLINK` (supported in App Install ). The default value is `NORMAL`. For an ad that will be included in an iOS 14 campaign, this field cannot be set to `DEFERRED_DEEPLINK`.|
|display_name_list {+Conditional}|object[]|Display names. <br><p><span style="color:darkred"><b>Note</b></span>: You can pass only one `app_name` or `landing_page_name`  into each `display_name_list` object.|
#|app_name {+Conditional}|string| App name that is displayed in the ad. If not specified, the name in the app store will be used. Number of characters allowed: 1-40. If the name in the app store exceeds 40 characters, you must pass in a new app name with this field.|
#|landing_page_name {+Conditional}|string|The display name of the landing page, required when the promotion type is landing page. Length limit: 1-40 characters. |
|page_list {+Conditional}|object[]|Page ID list.|
#|page_id  {+Conditional}|string|Required when `page_list` is passed.<br><br>Page ID.<br><br><ul><li>To obtain pages within your ad account, use [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586).</li><li>To create instant pages, use [Instant Page Editor SDK](https://business-api.tiktok.com/portal/docs?id=1740307202170881).</li></ul>|
|card_list|object[]|Card ID list. Length range: [0,1].|
#|card_id|string|Display Card ID, Gift Code Sticker ID, Countdown Sticker ID, or Download Card ID.<ul><li> To learn about how to get a Display Card ID or Download Card ID, please see [Cards](https://business-api.tiktok.com/portal/docs?id=1749019652141058).</li><li>To learn about how to get a Countdown Sticker ID, see [Stickers](https://ads.tiktok.com/marketing_api/docs?id=1749019667506177).|
|landing_page_urls {+Conditional}|object[]|Multiple landing page URLs.  <br><br>You can pass only one `landing_page_url` into each `landing_page_urls` object.<br><br><p><span style="color:darkred"><b>Note</b></span>: Once set, this field cannot be updated.|
#|landing_page_url{+Conditional}|string|Required when `landing_page_urls` is passed.<br><br>Landing page URL.|
|common_material|object|Common material.|
#|ad_name|string|Ad name. Set as " " (Empty string) for it to be automatically generated. The format of auto-generated ad name is: creative name + creation time (e.g. adcreative20210111190739). Character limit is 512 and cannot contain emoji.  See the section <strong>"Ad name generation rules"</strong> above to learn about how the ad name is generated.<br><p><span style="color:darkred"><b>Note</b></span>: Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character.<br>If you don't specify ad name here, you will not be able to change the name via `/ad/aco/update/` endpoint.|
#|call_to_action_id|string|The ID of the CTA portfolio that you want to use in your ads. A CTA portfolio is a group of auto-optimized CTAs. For details about auto-optimized CTAs, see [CTA recommendations > Dynamic CTAs](https://ads.tiktok.com/marketing_api/docs?id=1740307296329730). <br><p><span style="color:darkred"><b>Note</b></span>: If both this field and `call_to_action_list` are specified, `call_to_action_list` will be ignored. |
#| creative_authorized|boolean|Whether you grant displaying some of your ads in our TikTok for Business Creative Center. Only valid for non-US advertisers, the default value is `false`.<br><p><span style="color:darkred"><b>Note</b></span>: `creative_authorized` can only be used for video ads. |
#|playable_url|string|Playable material url, valid only in Pangle placement. You can get the url via the [/playable/get/](https://ads.tiktok.com/marketing_api/docs?id=1737732758495234) endpoint. Note that all ads in the same ad group share the same playable material.|
#|fallback_type|string|Fallback Type. If the audience do not have the app installed, you can have them fall back to install the app, or to view a specific web page.  Allowed values: `APP_INSTALL `, `WEBSITE`. If website is chosen, you need to specify the url via `landing_page_urls` field.<br><p><span style="color:darkred"><b>Note</b></span>: Not applicable for Deferred Deeplink. |
#|tracking_info|object|Tracking information.|
##|impression_tracking_urls|string[]|Default Impression Tracking URL. URL generated by your data partner to track impression events in your ads. Generally you can find and copy the URL from their platform. <ul><li> If the partner ID for the App (`app_id` specified at the ad group level ) that you want to track is `44` (TikTok Business SDK) or `49` (TikTok App API), you don't need to pass in this field. If you do, this field will be ignored. You can obtain the partner ID of an App through `partner_id` returned from [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) or [/app/info/](https://ads.tiktok.com/marketing_api/docs?id=1740859272887297).</li><li> If self-attribution has been enabled for the App (`app_id` specified at the ad group level ) that you want to track and the partner ID for the App is not `44` (TikTok Business SDK) or `49` (TikTok App API), you don't need to pass in this field because this field will default to the Default Impression Tracking URL you have configured for the App, and updates to the URL are not supported. You can check whether self-attribution has been enabled for the App through `self_attribution_enabled` returned from [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) or [/app/info/](https://ads.tiktok.com/marketing_api/docs?id=1740859272887297).</li></ul>You can pass in only one URL.|
##| click_tracking_urls|string[]|Click Tracking URL. URL generated by your data partner to track click events in your ads. Generally you can find and copy the URL from their platform. <ul><li> If the partner ID for the App (`app_id` specified at the ad group level ) that you want to track is `44` (TikTok Business SDK) or `49` (TikTok App API), you don't need to pass in this field. If you do, this field will be ignored. You can obtain the partner ID of an App through `partner_id` returned from [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) or [/app/info/](https://ads.tiktok.com/marketing_api/docs?id=1740859272887297).</li><li> If self-attribution has been enabled for the App (`app_id` specified at the ad group level ) that you want to track and the partner ID for the App is not `44` (TikTok Business SDK) or `49` (TikTok App API), you don't need to pass in this field because this field will default to the Click Tracking URL you have configured for the App, and updates to the URL are not supported. You can check whether self-attribution has been enabled for the App through `self_attribution_enabled` returned from [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) or [/app/info/](https://ads.tiktok.com/marketing_api/docs?id=1740859272887297).</li></ul>You can pass in only one URL.<br>Currently Pangle does not support DCM, Sizmek or Flashtalking. |
##|tracking_pixel_id|string| The pixel ID that you'd like to track. You can use this field to track offsite events. It supports the campaign objectives for both Auction ads (`REACH`, `VIDEO_VIEWS`, `TRAFFIC`, `WEB_CONVERSIONS`, `LEAD_GENERATION`, `APP_PROMOTION`, `PRODUCT_SALES`, `ENGAGEMENT`) and Reach & Frequency ads (`RF_REACH`).<ul><li>For Auction objectives<br>If `pixel_id` is specified at the ad group level <strong>and</strong> you want to use pixel to track offsite events, then the pixel ID you pass in the `tracking_pixel_id` field must be the same ID that you specified in the `pixel_id` field for the ad group. Otherwise, you can pass in any pixel ID that you'd like to track in the `tracking_pixel_id` field.<br><p><span style="color:darkred"><b>Note</b></span>: This field is an allowlist feature if your campaign objective is `ENGAGEMENT`/ `PRODUCT_SALES` (when `product_source` = `CATALOG` / `STORE` and `shopping_ads_type` = `VIDEO`) /`APP_PROMOTION`(when `app_promotion_type` = `APP_RETARGETING` ). If you want to use the field, please reach out to your TikTok representative.</li><li>For Reach & Frequency objectives <br>You can pass in any pixel ID that you'd like to track in the `tracking_pixel_id` field.|
##|tracking_app_id | string| The ID of the application that you want to track. You can use this field to track offsite app events. <br>This field supports the campaign objectives for both Auction ads (`REACH`, `VIDEO_VIEWS`, `TRAFFIC`, `WEB_CONVERSIONS`, `LEAD_GENERATION`, `APP_PROMOTION`, `PRODUCT_SALES`, `ENGAGEMENT`) and Reach & Frequency ads (`RF_REACH`).<br>If `app_id` is specified at the ad group level and you want to track offsite app events, then the application ID you pass via this field must be the same ID that you specified at the ad group level. Otherwise, you can pass in any application ID that you'd like to track via this field.<br>You can get application ID (`app_id`) via [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786). <br><p><span style="color:darkred"><b>Note</b></span>: This field is currently an allowlist-only feature for the advertising objectives `APP_PROMOTION`, `PRODUCT_SALES`, and `ENGAGEMENT`. If you would like to access it for these three objectives, please contact your TikTok representative. Otherwise, you will get an error. |
##| tracking_offline_event_set_ids |string[]| A list of Offline Event set IDs that you want to track. You can use this field to track and measure offline activity from people that see or interact with your ads. <br>Max size: 50. <br>See [here](https://ads.tiktok.com/marketing_api/docs?id=1758051319816193) to learn more about how to create and manage Offline Event sets. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Offline Events Tracking is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li><li> If you are allowlisted for Offline Events Tracking, then existing auto-tracking Offline Event sets have to be tracked under a newly created ad: <ul><li> You can omit this parameter, then this field will automatically default to the IDs of all the <strong>existing</strong> auto-tracking Offline Event sets under the advertiser account, or </li><li> You can manually pass in this parameter. You need to specify via this field at least all the <strong>existing</strong> auto-tracking Offline Event set IDs, and non-auto-tracking ones are optional. To get the IDs of current auto-tracking Offline Event sets, call [/offline/get/](https://ads.tiktok.com/marketing_api/docs?id=1765596808589313) to get all Offline Event set IDs and then select all the auto-tracking ones according to the returned `auto_tracking` parameter. </li></ul></li><li> This field supports the following campaign objectives: `REACH`, `VIDEO_VIEWS`, `TRAFFIC`, `LEAD_GENERATION`, `APP_PROMOTION`, `WEB_CONVERSIONS`, and `ENGAGEMENT`. </li></ul>|
#| identity_id {+Conditional}|string| Required when you are not using Spark Ads (`tiktok_item_id`).<br><br>Identity ID. |
#| identity_type {+Conditional}|string|Required when you are not using Spark Ads (`tiktok_item_id`).<br><br>Identity type. <br><br>The only supported value is `CUSTOMIZED_USER`.  |
#|is_smart_creative|boolean|Whether to enable Smart Creative for the ad group.<br><br>Supported value: `true`.<br><br>To learn about how to create a Smart Creative ad, refer to [Smart Creative](https://business-api.tiktok.com/portal/docs?id=1767322784934914).|
```
### Example
``` xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/aco/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "media_info_list": [
        {
            "media_info": {
                "video_info":{
                    "video_id":"{{video_id}}"
                    },
                "image_info":[
                    {"web_uri":"{{web_uri}}"}
                ]
            }
        }
    ],
    "title_list": [
        {
            "title": "{{title}}"
        }
    ],
    "landing_page_urls": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ],
     "common_material": {
        "identity_type": "CUSTOMIZED_USER",
        "identity_id": "{{identity_id}}",
        "call_to_action_id": "{{call_to_action_id}}",
        "is_smart_creative":true
    }
}'
(/code);
```


## Response

``` xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data.  |
#|advertiser_id|string| Advertiser ID.|
#|adgroup_id |string| Ad group ID.|
#|media_info_list |object[]| List of media information. |
##|material_id|string|Material ID.|
##|material_operation_status|string|The status of the material. Enum values: `ENABLE` (the material can be used), `DISABLE`(the material cannot be used).|
##|media_info|object|Media Information.|
###|image_info |object[]| Image information. |
####|web_uri |string| Image ID. |
####|file_name |string| Image name. |
###|video_info |object| Video information. |
####|video_id|string| Video ID. |
####|file_name |string| Video name. |
###|aigc_disclosure_type | string | Whether to turn on the AIGC (Artificial Intelligence Generated Content) self-disclosure toggle to indicate the ad contains AI-generated content. After the toggle is turned on, your ad will carry an "Advertiser labeled as Al-generated" label when viewed in full. <br><br> Enum values: <ul><li>`SELF_DISCLOSURE`: To turn on the toggle to declare that the ad contains AI-generated content. </li><li>`NOT_DECLARED`: To not declare that the ad contains AI-generated content.</li></ul>|
###| tiktok_item_id |string| The ID of the TikTok post to be used as an ad (Spark Ad). |
###| identity_id |string| Identity ID when you are using Spark Ads (`tiktok_item_id`).|
###| identity_type |string|Identity type when you are using Spark Ads (`tiktok_item_id`).  Enum values:  `AUTH_CODE`, `TT_USER`. For details about identities, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097). |
#|title_list |object[]|List of ad titles (also called ad texts). Ad titles are shown to your audience as part of your ad creative, to deliver the message you intend to communicate to them.|
##|title|string|Ad title (ad text).|
#|call_to_action_list |object[]|Call-to-action list.|
##|call_to_action|string|Call-to-action text. For enum values, see [Enumeration - Call-to-action](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
#|deeplink_list|object[]| List of deeplinks.|
##|deeplink|string|The specific location where you want your audience to go if they have your app installed.|
##|deeplink_type|string|The deeplink type. Allowed values differs based on campaign objectives. Allowed values: `NORMAL` (supported in Traffic, Conversion), `DEFERRED_DEEPLINK` (supported in App Install ). For an ad that will be included in an iOS 14 campaign, this field cannot be set to `DEFERRED_DEEPLINK`.|
#|display_name_list|object[]|Display names.|
##|app_name|string|App name.|
##|landing_page_name|string|Landing page name.|
#|page_list|object[]|Page ID list.|
##|page_id|string|Page ID.|
#|card_list|object[]|Card ID list. Length range: [0,1].|
##|card_id|string|Display Card ID, Gift Code Sticker ID, Countdown Sticker ID, or Download Card ID.|
#|landing_page_urls|object[]|Landing page URL list.|
##|landing_page_url|string|Landing page URL.|
#|common_material|object|Common material.|
##|ad_name|string|Ad name.|
##|call_to_action_id|string|The ID of the CTA portfolio that you want to use in your ads. |
##| creative_authorized|boolean|Whether you grant displaying some of your ads in our TikTok for Business Creative Center. Only valid for non-US advertisers, the default value is `false`.<br><p><span style="color:darkred"><b>Note</b></span>: `creative_authorized` can only be used for video ads. |
##|playable_url|string|Playable material url.|
##|fallback_type|string|Fallback Type. If the audience do not have the app installed, you can have them fall back to install the app, or to view a specific web page.  Allowed values: `APP_INSTALL `, `WEBSITE`, `UNSET`. <br><p><span style="color:darkred"><b>Note</b></span>: Not applicable for Deferred Deeplink. |
##|tracking_info|object|Tracking information.|
###|impression_tracking_urls|string[]|Default Impression Tracking URL.|
###| click_tracking_urls|string[]|Click Tracking URL.|
###|tracking_pixel_id|string|The pixel ID that is tracked.|
###|tracking_app_id | string| The ID of the application that is tracked. |
###| tracking_offline_event_set_ids |string[]|A list of Offline Event set IDs that are tracked.|
##| identity_id|string|Identity ID when you are not using Spark Ads (`tiktok_item_id`).|
##| identity_type|string|Identity type when you are not using Spark Ads (`tiktok_item_id`). The only supported value is `CUSTOMIZED_USER`. |
##|is_smart_creative|boolean|Whether Smart Creative is enabled for the ad group. |
|request_id |string|The log id of a request, which uniquely identifies the request. |
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "title_list": [
            {
                "title": "{{title}}"
            }
        ],
        "common_material": {
            "call_to_action_id": "{{call_to_action_id}}",
            "identity_id": "{{identity_id}}",
            "is_smart_creative": true,
            "identity_type": "CUSTOMIZED_USER"
        },
        "landing_page_urls": [
            {
                "landing_page_url": "{{landing_page_url}}"
            }
        ],
        "adgroup_id": "{{adgroup_id}}",
        "media_info_list": [
            {
                "media_info": {
                    "video_info": {
                        "video_id": "{{video_id}}"
                    },
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ]
                }
            }
        ],
        "advertiser_id": "{{advertiser_id}}"
    }
}
(/code);
```

