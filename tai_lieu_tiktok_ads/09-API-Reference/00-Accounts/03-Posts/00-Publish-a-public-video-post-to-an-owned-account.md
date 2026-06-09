---
title: "Publish a public video post to an owned account"
doc_id: 1762228496095234
path: "API Reference / Accounts / Posts / Publish a public video post to an owned account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Publish a public video post to an owned account

Use this endpoint to publish a public video post to an owned TikTok Account.

You need to specify the video to be published, and optionally the video caption and whether to enable different forms of user engagement (comments/stitches/duets).  

To learn about the post publishing Webhook events that you can subscribe to, see [Post publishing events](https://business-api.tiktok.com/portal/docs?id=1810515090228226). To configure a Webhook callback URL for your developer app, use the Webhook API endpoint [/business/webhook/update/](https://business-api.tiktok.com/portal/docs?id=1810521279479810). 

> <span style="color:DodgerBlue">**Note**</span><br>
>  <ul><li>To promote authentic sharing and avoid spamming, TikTok limits the publishing of video posts per TikTok account via the endpoint to six video posts per minute with an upper limit of 15 per day.</li><li>After publishing the video to your TikTok account, you might notice a decrease in resolution and bit rate compared to the original video file when viewing the post on your account profile or downloading it. This is a result of the video transcoding process, which can lead to variations in resolution and bit rate depending on factors such as the device's display capabilities and network conditions.</li></ul> 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/business/videos/publish/| /v1.3/business/video/publish/|
| New request parameters |/|`thumbnail_offset`<br>`is_brand_organic`<br>`is_branded_content`<br>`is_ai_generated`<br>`upload_to_draft`<br>`tto_invite_link` <br>`is_ads_only`<br>`custom_thumbnail_url`<br>`music_sound_info`<br>`music_sound_id`<br>`music_sound_volume`<br>`music_sound_start`<br>`music_sound_end`<br>`video_original_sound_volume` |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/video/publish/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators.<br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041). |
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{25%}|Data Type{10%}| Param Location{10%}|Description{55%}|
|--- |--- |--- |--- |
| business_id {Required} | string | Body | Application specific unique identifier for the TikTok account. <br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) to this field.|
| video_url{Required} | string | Body | A publicly accessible HTTP(s) URL for the video content to be published - with a minimum recommended TTL of 30 minutes. <br><br><strong> Video constraints </strong>: <ul><li> The audio-video content must be contained in .mp4, .mov or .webm format. </li><li> The maximum video size is 1 GB. </li><li> The minimum video duration must be 3 seconds, and the maximum video duration is 600 seconds. <ul><li>To check the maximum video duration allowed for a TikTok account, use [/business/video/settings/](https://business-api.tiktok.com/portal/docs?id=1816387951979521) and obtain `max_video_post_duration_sec` from the response.</li></ul></li><li> The minimum height and minimum width of the video must be 360 pixels.</li><li> The minimum frame rate of the video must be 23 FPS, and maximum frame rate is 60 FPS.</li></ul><strong>Example</strong>: https://www.example.com/tiktok-videos/video.mp4 <br><br><p><span style="color:darkred"><b>Important</b></span>: <ul><li>Starting from November 16th, 2023, you can no longer publish videos using unverified video URLs. To ensure a smooth API integration, we recommend you verify ownership of URL properties as soon as possible, and specify a URL that has been verified for ownership, either manually or automatically. <br> To manually verify ownership of a URL property, follow the steps outlined in [Manage URL properties](https://ads.tiktok.com/marketing_api/docs?id=1769324038780930).</li><li>After November 16th, 2023, if you want to publish a test video through [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234) using a test video URL that doesn't require verification, use the [test URL](https://sf16-va.tiktokcdn.com/obj/eden-va2/uvpapzpbxjH-aulauvJ-WV[[/ljhwZthlaukjlkulzlp/3min.mp4) `https://sf16-va.tiktokcdn.com/obj/eden-va2/uvpapzpbxjH-aulauvJ-WV&#91;&#91;/ljhwZthlaukjlkulzlp/3min.mp4`.</li><li>Note that if you are a developer or an advertiser who used the [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234) endpoint to publish videos prior to April 7th, 2023, we have automatically set the corresponding domains of video URLs specified through the `video_url` parameter to the "verified" status. Therefore, we recommend that you use the [/business/property/list/](https://business-api.tiktok.com/portal/docs?id=1769325368603650) endpoint to confirm whether the domains you utilize for hosting videos have been automatically verified.</li></ul> |
| custom_thumbnail_url | string | Body  | A publicly accessible HTTP(s) URL for a custom image to be used as the video’s cover photo. <br><br> Photo constraints: <ul><li>Maximum resolution: 1080 x 1920 pixels or 1920 x 1080 pixels.</li><li>Minimum resolution: 360 x 360 pixels.</li><li>File size: within 20 MB.</li><li>File type: JPG, JPEG, WebP, or PNG.</li></ul> <br> Example: `https://www.example.com/tiktok-images/image.png`. <br> <br> When `custom_thumbnail_url` is specified, `thumbnail_offset` will be ignored. <br><br><p><span style="color:darkred"><b>Note</b></span>: Specify a URL that has been verified for ownership, either manually or automatically. To manually verify ownership of a URL property, follow the steps outlined in [Manage URL properties](https://business-api.tiktok.com/portal/docs?id=1769324038780930).|
| post_info {Required} | object | Body | Information about the post.<br><br>This field is required, even if you don't want to use any of the additional parameters within `post_info`, including `caption`, `is_brand_organic`, `is_branded_content`, `disable_comment`, `disable_duet`, `disable_stitch`, `thumbnail_offset`, `is_ai_generated`, `upload_to_draft`, `tto_invite_link`, and `is_ads_only`. In such cases, set this field to an empty object (`{}`). |
#| caption | string |  | Video caption/description - which can contain #hashtags and @mentions of friends (mutual followers) of the owned TikTok Account.<br><br>  You can use "\n" as line break to display the video caption in multiple lines within the mobile TikTok App. However, for the line break to work effectively, the caption must contain enough characters to trigger truncation based on the current mobile phone's display settings and operating system. This truncation is characterized by an ellipsis mark (...) and the word "more", which users can click to expand and view the full caption. <br>If the caption is too short and does not trigger truncation, the "\n" character will be ignored, and the caption will be displayed on a single line without any line breaks.<br>Note that  in the TikTok Web App "\n" will be ignored.<br><br> Length limit: 2,200 characters (UTF-16 encoding), including a maximum of 30 mentions. <br><br><strong>Example</strong>: Me and me casa 🏠 #cat #kitten #britishshorthair<br><p><span style="color:darkred"><b>Note</b></span>: Any hashtags and mentions included in the video caption will render as plain text on the TikTok application.|
#| is_brand_organic {Required} | boolean | | Whether to enable the Brand Organic Content toggle for the video. <br><br> If this field is set to `true`, the video will be labeled as Brand Organic Content, indicating you are promoting yourself or your own business. A "Promotional content" label will be attached to the video. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If you set both `is_brand_organic` and `is_branded_content` to `true`, `is_brand_organic` will be ignored and the video will be labeled as Branded Content.</li></ul> |
#| is_branded_content {Required}| boolean|    | Whether to enable the [Branded Content](https://creatormarketplace.tiktok.com/help#/doc/9493/10008169) toggle for the video. <br><br>  If this field is set to `true`, the video will be labeled as Branded Content, indicating you are in a paid partnership with a brand. A "Paid partnership" label will be attached to the video. <br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li> If you set both `is_brand_organic` and `is_branded_content` to `true`, `is_brand_organic` will be ignored and the video will be labeled as Branded Content.</li></ul> |
#| tto_invite_link | string | |Valid only when the following conditions are both met:<ul><li>`is_branded_content` is set to `true`.</li><li>The TikTok account (creator) has been invited to a TikTok One (TTO) Creator Marketplace campaign.</li><li>The TikTok account has joined the campaign. To join a campaign, you can use [/tto/creator/campaign/join/](https://business-api.tiktok.com/portal/docs?id=1836584318657537).</li></ul><br>The invite link for a TTO Creator Marketplace campaign.<br><br>When you specify this field, the video will be published and linked to the TTO Creator Marketplace campaign that is associated with the invite link.<br><br>Brands can retrieve the invite link for a TTO Creator Marketplace campaign by using [/tto/tcm/campaign/](https://business-api.tiktok.com/portal/docs?id=1815693562720258).|
#| disable_comment | boolean | | Whether to disable the "Allow comments" setting for the published video post. <br><br>Supported values: <ul><li>`false`: To enable the setting.</li><li>`true`: To disable the setting.</li></ul><br>To determine the allowed values for your TikTok account's posts, use [/business/video/settings/](https://business-api.tiktok.com/portal/docs?id=1816387951979521) and obtain `comment_disabled` from the response.<ul><li>If `comment_disabled` is `true`, you can only set this field to `true`.</li><li>If `comment_disabled` is `false`, you can set this field to `true` or `false`.</li></ul><br><strong>Example</strong>: `false`<br><br><strong>Default value</strong>: `false` |
#| disable_duet | boolean | |Whether to disable the "Allow Duet" setting for the published video post. <br><br> Supported values: <ul><li>`false`: To enable the setting.</li><li>`true`: To disable the setting.</li></ul><br>To determine the allowed values for your TikTok account's posts, use [/business/video/settings/](https://business-api.tiktok.com/portal/docs?id=1816387951979521) and obtain `duet_disabled` from the response. <ul><li> If `duet_disabled` is `true`, you can only set this field to `true`.</li><li>If `duet_disabled` is `false`, you can set this field to `true` or `false`.</li></ul><br><strong>Example</strong>: `false`<br><br><strong>Default value</strong>: `false` |
#| disable_stitch | boolean | |Whether to disable the "Allow Stitch" setting for the published video post. <br><br>Supported values: <ul><li>`false`: To enable the setting.</li><li>`true`: To disable the setting. </li></ul><br>To determine the allowed values for your TikTok account's posts, use [/business/video/settings/](https://business-api.tiktok.com/portal/docs?id=1816387951979521) and obtain `stitch_disabled` from the response. <ul><li>If `stitch_disabled` is `true`, you can only set this field to `true`.</li><li>If `stitch_disabled` is `false`, you can set this field to `true` or `false`. </li></ul><br><strong>Example</strong>: `false`<br><br><strong>Default value</strong>: `false` |
#| thumbnail_offset | integer | | Setting to choose a frame of the published video as the cover photo, in the format of a timestamp in ms. <br><br><strong>Example</strong>: 8000. <br><br><strong>Default value</strong>: 0. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When `custom_thumbnail_url` is specified, `thumbnail_offset` will be ignored.</li><li> A failure Webhook will be sent if you pass in a timestamp greater than the video length, and then the video will not be published. </li><li> The timestamp you pass in will be rounded up to find the closest frame. For instance, if the video is a 2FPS video that lasts one second, the timestamp 499 (ms) will return the first frame and the timestamp 500 (ms) will return the second frame. </li></ul>|
#| is_ai_generated | boolean |  |Whether to enable the AI-generated content toggle for the video post. <br>If you enable the toggle, your video will be labeled as <b>Creator labeled as AI-generated</b> once posted and can't be changed. The "Creator labeled as AI-generated" label indicates that the content was completely AI-generated or significantly edited with AI. <br><br>Supported values: `true`, `false`. <br>Default value: `false`. <br><br><p><span style="color:darkred"><b>Note</b></span>: Turning on the AI-generated content setting won't affect the distribution of your video as long as it doesn't violate our [Community Guidelines](https://www.tiktok.com/community-guidelines/en/). |
#| upload_to_draft | boolean |  | Whether to upload the post as a draft.<br><br>Supported values: `true`, `false`. Default value: `false`. <br><br>If you set this field to `true` , all the other fields within `post_info` will be ignored.<br><br>After you successfully upload the post as a draft, you can find the draft through the inbox notification within the TikTok app. <br><br><p><span style="color:darkred"><b>Note</b></span>: If you want to use this parameter to upload the post as a draft, obtain a TikTok account access token with `video.upload` permission and the Application specific unique ID for the TikTok account by following the steps in [Authorization](https://business-api.tiktok.com/portal/docs?id=1738083939371009) and [Authentication](https://business-api.tiktok.com/portal/docs?id=1738084387220481).<ul><li>To confirm whether a TikTok account access token has `video.upload` permission, use [/tt_user/token_info/get/](https://business-api.tiktok.com/portal/docs?id=1765927978092545) and check the returned `scope`. If the access token doesn't have the `video.upload` permission, request the TikTok account owner to approve draft uploading permission through the [Authorization](https://business-api.tiktok.com/portal/docs?id=1738083939371009) workflow. </li></ul>|
#| is_ads_only | boolean | |Whether to publish the video as an "Only show in ads" video.<br><br>Supported values:<ul><li>`true`: To publish the video as an ad that won't be visible to the public on your profile page.</li><li>`false`: Not to publish the video as an ad.</li></ul><br>Default value: `false`. <br><br><p><span style="color:darkred"><b>Note</b></span>: If "Only show in ads" videos that have been used as Spark Ads, you can access the details of these videos through [/business/video/list/](https://business-api.tiktok.com/portal/docs?id=1762228421622786). |
#| music_sound_info | object | | Information about the commercial sound (track) that you want to use with a video. |
##| music_sound_id {+Conditional} | object | | Required when using `music_sound_info`. <br>The ID of the commercial sound that you want to use with a video. <br>You can get this ID using the [/discovery/cml/trending_list/](https://business-api.tiktok.com/portal/docs?id=1825119063013505) endpoint. |
##| music_sound_volume {+Conditional}| integer | | Required when using `music_sound_info`. <br>The volume of the commercial sound. <br>Default value: 0. <br>Value range: 0-100.<br><br><p><span style="color:darkred"><b>Note</b></span>:  When using the TikTok App, the default volume is 50. |
##| music_sound_start | integer |  |The starting point of the sound to be used for the video, measured in milliseconds.<br>For example, if you input a value of 2000, the commercial sound will start from the `0:02` (2-second) time mark.<br>If not provided, this field defaults to `0` (the track's beginning).|
##| music_sound_end | integer |  | The ending point of the sound to be used for the video, measured in milliseconds.<br>If not provided, this field defaults to the video’s full duration expressed in milliseconds.<br>For example, if your video is 2 seconds long (2000 ms) and the commercial sound is 10 seconds long (10000 ms), omitting both `music_sound_start` and `music_sound_end` will result in the addition of the first 2000 milliseconds of the commercial sound to your video.|
##| video_original_sound_volume | integer |  | The background volume of the original video.<br>Default value: 0.<br>Value range: 0-100.<br><br><p><span style="color:darkred"><b>Note</b></span>:  When using the TikTok App, the default volume is 50. |

```
### Example
```xcodeblock
(code curl http)
curl -L -X POST 'https://business-api.tiktok.com/open_api/v1.3/business/video/publish/' \
-H 'Content-Type: application/json' \
-H 'Access-Token: 1234a16d2e08c3f17d1984a1be07d00406p3LIAF7vEpxliox8GRpCINv70x' \
--data-raw '{
  "business_id": "20f5994f-4a67-44d4-a48f-2ce494a853b8",
  "video_url": "https://s3.amazonaws.com/tiktok-videos/video.mp4",
  "post_info": {
      "caption": "Me and me casa 🏠 #cat #kitten #britishshorthair",
      "disable_comment": false,
      "disable_duet": false,
      "disable_stitch": false
      }
}'
(/code)
```
## Response
**Header**

These are important headers to record for issue reports and troubleshooting. This is not an exhaustive list of response headers.

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| Date | string | Date and time (GMT) when the response was received. <br><strong>Example</strong>: Fri, 13 Aug 2021 08:04:42 GMT |
| X-Tt-Logid | string | Unique identifier for the API request. |
```
**Body**
``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
| request_id | string | Unique identifier for the API request. <br><br>Please record this field for all API requests. Important for issue reports and troubleshooting. |
| code | Integer | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message| string | Response message. For details, see [Appendix - Return Information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
| data | object | Returned data. |
#|share_id| string | Unique identifier for the video post publishing task. <br>You can use this ID as `publish_id` in [/business/publish/status/](https://business-api.tiktok.com/portal/docs?id=1816387106635778) to query the publishing status of the video post.<br><br><strong>Example</strong>: v_pub_url~v1.2345123456789123456<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> The [video publishing status Webhook](https://business-api.tiktok.com/portal/docs?id=1810515090228226) for the shared video content will contain this identifier for developer applications to track the status of the video upload. Once the video publishing is complete, the success Webhook will also contain the newly created unique identifier for the video which can be used to access insights into the video.</li><li>To configure a Webhook callback URL for your developer app, use the Webhook API endpoint [/business/webhook/update/](https://business-api.tiktok.com/portal/docs?id=1810521279479810). </li></ul>|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
  "code": 0,
  "message": "Ok",
  "request_id": "20210817034316010245031056097316BA",
  "data": {
    "share_id": "v_pub_url~v1.2345123456789123456"
  }
}
(/code)
```

