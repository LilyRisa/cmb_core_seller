---
title: "Get Smart Creative materials"
doc_id: 1739473020978177
path: "API Reference / Smart Creative / Get Smart Creative materials"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Smart Creative materials

Use this endpoint to get creative materials for Smart Creative ads, including call-to-actions, texts, ad names, images, or video materials. This is a totally new endpoint in v1.3.

The response of endpoints [/ad/aco/create/](https://ads.tiktok.com/marketing_api/docs?id=1739473063234626) and [/ad/aco/update/](https://ads.tiktok.com/marketing_api/docs?id=1739473077112833) are mock results which return the request parameters you've passed in, because the data processing is not finished instantly.  To obtain the actual accurate result, wait for 2 or 3 seconds after your call to the endpoint `/ad/aco/create/`or `/ad/aco/update/`and then use the endpoint `/ad/aco/get/`.
  
## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/ad/aco/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|adgroup_ids {Required}|string[]| A list of ad group IDs. Quantity: 1-100.|
|advertiser_id {Required}|string| Advertiser ID. |
| exclude_field_types_in_response| string[]|The type of fields that you want to remove from the response.<br>Allowed enum values: <ul><li>`NULL_FIELD`: Fields with null values.</li></ul>|
```

### Example

``` xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "adgroup_ids=[\"ADGROUP_IDS\"]" \
https://business-api.tiktok.com/open_api/v1.3/ad/aco/get/
(/code)
```

## Response

``` xtable
|Field{40%}|Data Type{20%}|Description{40%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data.  |
#|list|object[]|Data list. |
##|advertiser_id|string| Advertiser ID.|
##|adgroup_id |string| Ad group ID.|
##|media_info_list |object[]| List of media information. |
###|material_id|string|Material ID.|
###|material_operation_status|string|The status of the material. Enum values: `ENABLE` (the material can be used), `DISABLE`(the material cannot be used).|
###|media_info|object|Media Information.|
####|image_info |object[]| Image information. <ul><li>If the material is a video, the `image_info` will provide details about the video cover image.</li><li>If the material is an image, the `image_info` will provide details about the image material.</li></ul>|
#####|web_uri |string| Image ID. |
#####|file_name |string| The image name you specified to form the ad name when creating Smart Creative ads via [/ad/aco/create/](https://business-api.tiktok.com/portal/docs?id=1739473063234626).<br><br>If you didn't pass `file_name` within the `image_info` object array when creating Smart Creative ads, the value of this field will be an empty string.|
####|video_info |object| Returned only when the material is a video.<br><br>Video information. |
#####|video_id|string| Video ID. |
#####|file_name |string| The video name you specified to form the ad name when creating Smart Creative ads via [/ad/aco/create/](https://business-api.tiktok.com/portal/docs?id=1739473063234626).<br><br>If you didn't pass `file_name` within the `video_info` object when creating Smart Creative ads, the value of this field will be an empty string.|
####|aigc_disclosure_type | string | Whether to turn on the AIGC (Artificial Intelligence Generated Content) self-disclosure toggle to indicate the ad contains AI-generated content. After the toggle is turned on, your ad will carry an "Advertiser labeled as Al-generated" label when viewed in full. <br><br> Enum values: <ul><li>`SELF_DISCLOSURE`: To turn on the toggle to declare that the ad contains AI-generated content. </li><li>`NOT_DECLARED`: To not declare that the ad contains AI-generated content.</li></ul>|
####| tiktok_item_id |string| The ID of the TikTok post to be used as an ad (Spark Ad). |
####| material_name | string | Name of the material (image or video). <br><br>The `material_name` is used to determine the name displayed for a material in TikTok Ads Manager: <ul><li> If the material is an image, its name in TikTok Ads Manager is the same as the value of `material_name`. </li><li> If the material is a video, its name in TikTok Ads Manager is the value of either `file_name` (when the value of `file_name` is not empty) or `material_name` (when the value of `file_name` is empty).</li></ul>|
####| identity_id |string| Identity ID.|
####| identity_type |string|Identity type.  Enum values:  `AUTH_CODE`, `TT_USER`. For details about identities, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097). |
##|title_list |object[]|List of ad titles (also called ad texts). Ad titles are shown to your audience as part of your ad creative, to deliver the message you intend to communicate to them.|
###|title|string|Ad title (ad text).|
###|material_id|string|Material ID.|
###|material_operation_status|string|The status of the material. Enum values: `ENABLE` (the material can be used), `DISABLE`(the material cannot be used).|
##|call_to_action_list |object[]|Call-to-action list.|
###|call_to_action|string|Call-to-action text. For enum values, see [Enumeration - Call-to-action](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
##|deeplink_list|object[]| List of deeplinks.|
###|deeplink|string|The specific location where you want your audience to go if they have your app installed.|
###|deeplink_type|string|The deeplink type. Allowed values differs based on campaign objectives. Allowed values: `NORMAL` (supported in Traffic, Conversion), `DEFERRED_DEEPLINK` (supported in App Install). The default value is `NORMAL`. For an ad that will be included in an iOS 14 campaign, this field cannot be set to `DEFERRED_DEEPLINK`.|
##|display_name_list|object[]|Display names.|
###|app_name|string|App name.|
###|landing_page_name|string|Landing page name.|
##|avatar_icon_list|object[]|Avatar image information list.|
###|avatar_icon|object|Avatar image information.|
####|web_uri|string| Avatar image ID.|
##|page_list|object[]|Page ID list.|
###|page_id|string|Page ID.|
##|card_list|object[]|Card ID list. Length range: [0,1].|
###|card_id|string|Display Card ID, Gift Code Sticker ID, Countdown Sticker ID, or Download Card ID.|
##|landing_page_urls|string[]|Landing page URL list.|
###|landing_page_url|string|Landing page URL.|
##|common_material|object|Common material.|
###|ad_name|string|Ad name.|
###|call_to_action_id|string|The ID of the CTA portfolio that you want to use in your ads. |
###|creative_authorized|boolean|Whether you grant displaying some of your ads in our TikTok for Business Creative Center. Only valid for non-US advertisers, the default value is `false`.<br><p><span style="color:darkred"><b>Note</b></span>: `creative_authorized` can only be used for video ads. |
###|playable_url|string|Playable material url.|
###|fallback_type|string|Fallback Type. If the audience do not have the app installed, you can have them fall back to install the app, or to view a specific web page.  Allowed values: `APP_INSTALL `, `WEBSITE`, `UNSET`. <br><p><span style="color:darkred"><b>Note</b></span>: Not applicable for Deferred Deeplink. |
###|tracking_info|object|Tracking information.|
####|impression_tracking_urls|string[]|Default Impression Tracking URL.|
####| click_tracking_urls|string[]|Click Tracking URL.|
####|tracking_pixel_id|string|The pixel ID that you'd like to track. You can use this field to track offsite events.|
####|tracking_app_id | string| The ID of the application that is being tracked. <br><p><span style="color:darkred"><b>Note</b></span>: This field is currently an allowlist-only feature for the advertising objectives `APP_PROMOTION`, `PRODUCT_SALES`, and `ENGAGEMENT`. If you would like to access it for these three objectives, please contact your TikTok representative. |
####| tracking_offline_event_set_ids |string[]|A list of Offline Event set IDs that are tracked. <br>See [here](https://ads.tiktok.com/marketing_api/docs?id=1758051319816193) to learn more about how to create and manage Offline Event sets.<br><p><span style="color:darkred"><b>Note</b></span>: Offline Events Tracking is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. |
###| identity_id|string|Identity ID when you are not using Spark Ads (`tiktok_item_id`).|
###| identity_type|string|Identity type when you are not using Spark Ads (`tiktok_item_id`). The only supported value is `CUSTOMIZED_USER`. |
###|is_smart_creative|boolean|Whether Smart Creative is enabled for the ad group. |
|request_id |string|The log id of a request, which uniquely identifies the request. |
```


### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "list": [
            {
                "open_url": null,
                "title_list": [
                    {
                        "title": "Testing"
                    }
                ],
                "open_url_list": [
                    {
                        "open_url": ""
                    }
                ],
                "app_name": null,
                "avatar_icon_list": null,
                "click_tracking_url": "",
                "landing_page_urls": null,
                "profile_image": null,
                "pixel_id": null,
                "landing_page_url": null,
                "impression_tracking_url": "",
                "page_list": null,
                "external_action": null,
                "call_to_action_list": [],
                "display_name": null,
                "display_name_list": [
                    {
                        "source": "20210531132544123456",
                        "app_name": ""
                    }
                ],
                "ad_id": 1234573413994497,
                "page_id": null,
                "media_info_list": [
                    {
                        "media_info": {
                            "image_info": [
                                {
                                    "file_name": null,
                                    "web_uri": "v0201/aa4785144d3894fcce25033c86123456"
                                }
                            ],
                            "video_info": {
                                "file_name": "12345096e244f1dab24a7e40870cc.MP4",
                                "video_id": "v12345270000bvgsmvthr46s2t1dgm20"
                            },
                            "image_mode": "IMAGE_MODE_VIDEO"
                        }
                    }
                ]
            }
        ]
    },
    "request_id": "xxx"
}
(/code);
```

