---
title: "Publish a photo post to an owned account"
doc_id: 1803630424390658
path: "API Reference / Accounts / Posts / Publish a photo post to an owned account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Publish a photo post to an owned account

Use this endpoint to publish a photo post to an owned TikTok Account.

To learn about the post publishing Webhook events that you can subscribe to, see [Post publishing events](https://business-api.tiktok.com/portal/docs?id=1810515090228226). To configure a Webhook callback URL for your developer app, use the Webhook API endpoint [/business/webhook/update/](https://business-api.tiktok.com/portal/docs?id=1810521279479810). 

><span style="color:DodgerBlue">**Note**</span><br> 
> To promote authentic sharing and avoid spamming, TikTok limits the publishing of photo posts per TikTok account via the endpoint to six photo posts per minute with an upper limit of 15 per day.

## Request

**Endpoint**: https://business-api.tiktok.com/open_api/v1.3/business/photo/publish/

**Method**: POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators.<br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{10%}| Param Location{10%}|Description{50%}|
|--- |--- |--- |--- |
| business_id {Required} | string | Body | Application specific unique identifier for the TikTok account. <br><br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) to this field.|
|photo_images {Required} | string[] | Body | A list of up to 35 publicly accessible HTTP(s) URLs for the photo content to be published.<br><br><strong>Photo constraints</strong>: <ul><li> The maximum resolution of the image is 1080 x 1920 pixels or 1920 x 1080 pixels.</li><li>File size: within 20 MB for each image.</li><li>File type: JPG, JPEG, or WebP.</li></ul><strong>Example</strong>: https://tiktok.com/tiktok-images/image.jpg <br><br><p><span style="color:darkred"><b>Important</b></span>: <ul><li>You cannot publish photo posts using unverified photo URLs. To ensure a smooth API integration, we recommend you verify ownership of URL properties as soon as possible, and specify a URL that has been verified for ownership, either manually or automatically. <br>To manually verify ownership of a URL property, follow the steps outlined in [Manage URL properties](https://ads.tiktok.com/marketing_api/docs?id=1769324038780930).</li><li>Note that if you are a developer or an advertiser who used the [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234) endpoint to publish videos prior to April 7th, 2023, we have automatically set the corresponding domains of video URLs specified through the `video_url` parameter to the "verified" status. Therefore, we recommend that you use the [/business/property/list/](https://business-api.tiktok.com/portal/docs?id=1769325368603650) endpoint to confirm whether the domains you utilize for hosting images have been automatically verified.</li></ul> |
| photo_cover_index | number | Body | The index of the photo to be used as the cover for the post. <br><br>Default value: `0` (the first photo). <br><br>For example, if you want to use the second photo as the post cover, set this field to `1`. |
| post_info {Required} | object | Body | Information about the photo post. |
#| privacy_level {Required} | string  | | The privacy level of the photo post.  <br><br>Enum values: <ul><li>`PUBLIC_TO_EVERYONE`: Everyone (people on or off TikTok). </li><li>`MUTUAL_FOLLOW_FRIENDS`: Friends (followers you follow back). </li><li> `FOLLOWER_OF_CREATOR`: Followers (Visible only to followers on your private account).</li><li> `SELF_ONLY`: Only you.</li></ul><br>To determine the allowed values for your TikTok account's posts, use [/business/video/settings/](https://business-api.tiktok.com/portal/docs?id=1816387951979521) and obtain `privacy_level_options` from the response. |
#| title | string  | | The title of the post. <br><br>Length limit: 90 runes (UTF-16 code units). |
#| caption | string |  | Post caption/description - which can contain #hashtags and @mentions of friends (mutual followers) of the owned TikTok Account.<br><br> Length limit: 4,000 runes (UTF-16 code units), including a maximum of 30 mentions.<br><br>You can use "\n" as line break to display the post caption in multiple lines within the mobile TikTok App. However, for the line break to work effectively, the caption must contain enough characters to trigger truncation based on the current mobile phone's display settings and operating system. This truncation is characterized by an ellipsis mark (...) and the word "more", which users can click to expand and view the full caption. <br>If the caption is too short and does not trigger truncation, the "\n" character will be ignored, and the caption will be displayed on a single line without any line breaks. <br>Note that in the TikTok Web App "\n" will always be ignored. <br><br>Example: Me and me casa 🏠 #cat #kitten #britishshorthair <br><br><p><span style="color:darkred"><b>Note</b></span>: Any hashtags and mentions included in the post caption will render as plain text on the TikTok application. |
#| auto_add_music | boolean  | | Whether to automatically add recommended music to the post.<br><br>Enum values: `true`, `false`. Default value: `false`. <br><br>If you set this field to `true`, you can change the music later in the TikTok app. |
#| is_brand_organic {Required}| boolean  | |  Whether to enable the Brand Organic Content toggle for the video. <br><br> If this field is set to `true`, the video will be labeled as Brand Organic Content, indicating you are promoting yourself or your own business. A "Promotional content" label will be attached to the video. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If you set both `is_brand_organic` and `is_branded_content` to `true`, `is_brand_organic` will be ignored and the video will be labeled as Branded Content. </li></ul>|
#| is_branded_content {Required}| boolean |    | Whether to enable the [Branded Content](https://creatormarketplace.tiktok.com/help#/doc/9493/10008169) toggle for the video. <br><br>  If this field is set to `true`, the video will be labeled as Branded Content, indicating you are in a paid partnership with a brand. A "Paid partnership" label will be attached to the video. <br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>If you set both `is_brand_organic` and `is_branded_content` to `true`, `is_brand_organic` will be ignored and the video will be labeled as Branded Content.</li></ul>  |
#| is_draft | boolean | |Whether to upload the post as a draft.<br><br>Supported values: `true`, `false`.<br>Default value: `false`.<br><br>If you set this field to `true`, all the other fields within `post_info`, except for `title` and `caption`, will be ignored.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you want to use this parameter to upload the post as a draft, obtain a TikTok account access token with `video.upload` permission and the application-specific unique ID for the TikTok account by following the steps in [Authorization](https://business-api.tiktok.com/portal/docs?id=1738083939371009) and [Authentication](https://business-api.tiktok.com/portal/docs?id=1738084387220481).<ul><li>To confirm whether a TikTok account access token has `video.upload` permission, use [/tt_user/token_info/get/](https://business-api.tiktok.com/portal/docs?id=1765927978092545) and check the returned scope. If the access token doesn't have the `video.upload` permission, request the TikTok account owner to approve draft uploading permission through the [Authorization](https://business-api.tiktok.com/portal/docs?id=1738083939371009) workflow.</li></ul> |
#| disable_comment | boolean |   | Whether to disable the "Allow comments" setting for the published photo post. <br><br>Supported values: <ul><li>`false`: To enable the setting.</li><li>`true`: To disable the setting.</li></ul><br>To determine the allowed values for your TikTok account's posts, use [/business/video/settings/](https://business-api.tiktok.com/portal/docs?id=1816387951979521) and obtain `comment_disabled` from the response.<ul><li>If `comment_disabled` is `true`, you can only set this field to `true`.</li><li>If `comment_disabled` is `false`, you can set this field to `true` or `false`.</li></ul><br><strong>Default value</strong>: `false` <br><br><strong>Example</strong>: `false`|
#| music_sound_info | object |  | Information about the track that you want to use with a photo. |
##| music_sound_id {+Conditional} | string |  | Required when using `music_sound_info`.<br>The commercial music library ID of the track that you want to use with a photo. You can get this ID using the [/discovery/cml/trending_list/](https://business-api.tiktok.com/portal/docs?id=1825119063013505) endpoint.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/photo/publish/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '
{
    "business_id": "{{business_id}}",
    "photo_images": [
        "{{photo_image}}",
        "{{photo_image}}"
    ],
    "photo_cover_index": 0,
    "post_info": {
        "title": "{{title}}",
        "caption": "{{caption}}",
        "disable_comment": false,
        "privacy_level": "PUBLIC_TO_EVERYONE",
        "auto_add_music": true
    }
}'
(/code)
```
## Response
**Response Headers**

> <span style="color:DodgerBlue">**Note**</span><br>
> These are important headers to record for issue reports and troubleshooting. This is not an exhaustive list of response headers.

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| Date | string | Date and time (GMT) when the response was received. <br><strong>Example</strong>: Fri, 13 Aug 2024 08:04:42 GMT |
| X-Tt-Logid | string | Unique identifier for the API request. |
```
**Response Body**
``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
| code | Integer | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message| string | Response message. For details, see [Appendix - Return Information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
| request_id | string | Unique identifier for the API request. <br><br>Please record this field for all API requests because it's important for issue reports and troubleshooting. |
| data | object | Returned data. |
#|share_id| string | Unique identifier for the photo post publishing task. <br>You can use this ID as `publish_id` in [/business/publish/status/](https://business-api.tiktok.com/portal/docs?id=1816387106635778) to query the publishing status of the photo post.<br><br><strong>Example</strong>: p_pub_url~v1.2345123456789123456 <br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The [photo post publishing status Webhook](https://business-api.tiktok.com/portal/docs?id=1810515090228226) for the shared photo content will contain this identifier for developer applications to track the status of the photo upload. Once the photo publishing is complete, the success Webhook will also contain the newly created unique identifier for the photo post which can be used to access insights into the post.</li><li>To configure a Webhook callback URL for your developer app, use the Webhook API endpoint [/business/webhook/update/](https://business-api.tiktok.com/portal/docs?id=1810521279479810). </li></ul>|
```

### Example
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "share_id": "{{share_id}}"
    }
}
(/code)
```

