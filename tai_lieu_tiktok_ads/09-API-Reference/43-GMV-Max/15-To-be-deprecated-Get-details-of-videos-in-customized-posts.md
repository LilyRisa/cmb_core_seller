---
title: "(To be deprecated) Get details of videos in customized posts"
doc_id: 1830215925061633
path: "API Reference / GMV Max / (To be deprecated) Get details of videos in customized posts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To be deprecated) Get details of videos in customized posts

Use this endpoint to retrieve the details of videos that have been used in the customized post collection for a Product GMV Max Campaign.

> <span style="color:DarkRed">**Important**</span><br>
> This endpoint will be deprecated in the next API version. To ensure a seamless integration, retrieve the details of videos used in customized posts using [/gmv_max/creation/custom_anchor_video_list/get/](https://business-api.tiktok.com/portal/docs/get-customized-tiktok-posts/v1.3) and [/gmv_max/creation/shop_video/video_anchors/](https://business-api.tiktok.com/portal/docs/get-product-linkage-details-of-videos-in-customized-posts/v1.3).

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/gmv_max/custom_anchor_video_list/get/

**Method** GET

**Header**

```xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|--- |--- |--- |
| advertiser_id  {Required}| string | Advertiser ID. |
| campaign_id {Required} | string | The ID of a GMV Max Campaign.<br><br>To filter existing GMV Max Campaigns within your ad account, use [/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177).<br><br><p><span style="color:darkred"><b>Important</b></span>: Starting April 30th, 2026, this parameter will be required to enhance security. To ensure a smooth API integration, we recommend that you update your integration to specify this parameter in all future calls.</p> |
| campaign_custom_anchor_video_id {Required}| string | The ID of the collection of customized posts created in a Product GMV Max Campaign.<br><br>To obtain the customized post collection associated with a Product GMV Max Campaign, use [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762) and check the returned `campaign_custom_anchor_video_id`. |
| custom_anchor_video_list {Required} | object[] | The list of videos with customized product links (custom anchor videos) to filter the results by.<br><br>Max size: 20.<br><br>To obtain the customized posts associated with a Product GMV Max Campaign, use [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762) and check the returned `custom_anchor_video_list`. |
#| item_id  {Required}| string |  The ID of the TikTok video. |
#| identity_info  {Required}| object | Information about the identity associated with the post. |
##|identity_id  {Required}| string | Identity ID.|
##|identity_type {Required} | string | Identity type.<br><br>Enum values:<ul><li>`AUTH_CODE`: Authorized User. This type of identity is created when you use the authorization code to access a TikTok account or a TikTok post.</li><li>`TT_USER`: TikTok User. This type of identity is created when you bind your TikTok For Business account with your TikTok Business Account, or when you bind your TikTok For Business account with your regular TikTok account and then upgrade the account to Business Account.</li><li>`BC_AUTH_TT`: TikTok Account User in Business Center. This type of identity is created when you add a TikTok account to your Business Center, and the TikTok account owner approves your request.</li><li>`TTS_TT`: TikTok Account User for TikTok Shop. This type of identity is created when you set an official TikTok account for the TikTok Shop.</li></ul> |
##|identity_authorized_bc_id {+Conditional} | string | Required when `identity_type` is `BC_AUTH_TT`.<br><br>The ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
##|identity_authorized_shop_id  {+Conditional}| string | Required only when `identity_type` is `BC_AUTH_TT` and `identity_authorized_shop_id` is returned for the identity from [/gmv_max/identity/get/](https://business-api.tiktok.com/portal/docs?id=1822001101474882).<br><br>The ID of the TikTok Shop that a TikTok Account User in Business Center identity is associated with. |
##|store_id {+Conditional}| string |  Required when `identity_type` is `TTS_TT`.<br><br>The ID of the TikTok Shop that a TikTok Account User for TikTok Shop identity is associated with. |
#| spu_id_list | string[] | The SPU IDs of the products associated with the customized product anchor link in the TikTok video.<br><br>Max size: 1.<br><br>If you want to have `spu_id_list` included in the response, specify `spu_id_list` in the request. |
```

### Example
#### Include `spu_id_list` in the response
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/custom_anchor_video_list/get/?advertiser_id={{advertiser_id}}&campaign_id={{campaign_id}}&campaign_custom_anchor_video_id={{campaign_custom_anchor_video_id}}&custom_anchor_video_list=[{"item_id":"{{item_id}}","identity_info":{"identity_id":"{{identity_id}}","identity_type":"TT_USER"},"spu_id_list":["{{spu_id}}"]}]'
--header 'Access-Token: {{Access-Token}}'
(/code)
```
#### Exclude `spu_id_list` from the response
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/custom_anchor_video_list/get/?advertiser_id={{advertiser_id}}&campaign_id={{campaign_id}}&campaign_custom_anchor_video_id={{campaign_custom_anchor_video_id}}&custom_anchor_video_list=[{"item_id":"{{item_id}}","identity_info":{"identity_id":"{{identity_id}}","identity_type":"TT_USER"}}]'
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|  custom_anchor_video_list | object[] | The list of custom anchor videos. |
##| item_id | string | The ID of the TikTok video.<br><br>Example: `1234567891234567891`. |
##| text | string | The caption of the TikTok post.<br><br>Example: `summer`. |
##| spu_id_list |  string[]| Returned only when `spu_id_list` is specified in the request.<br><br>The list of Product SPU IDs that the TikTok post is associated with. |
##| identity_info | object | Information about the identity associated with the TikTok post. |
###| identity_id | string | Identity ID.<br><br>Example: `identity_12345`. |
###| identity_type | string | Identity type.<br><br>Enum values:<ul><li>`AUTH_CODE`: Authorized User. This type of identity is created when you use the authorization code to access a TikTok account or a TikTok post.</li><li>`TT_USER`: TikTok User. This type of identity is created when you bind your TikTok For Business account with your TikTok Business Account, or when you bind your TikTok For Business account with your regular TikTok account and then upgrade the account to Business Account.</li><li>`BC_AUTH_TT`: TikTok Account User in Business Center. This type of identity is created when you add a TikTok account to your Business Center, and the TikTok account owner approves your request.</li><li>`TTS_TT`: TikTok Account User for TikTok Shop. This type of identity is created when you set an official TikTok account for the TikTok Shop.</li></ul> |
###| identity_authorized_bc_id | string | Returned only when `identity_type` is `BC_AUTH_TT`.<br><br>The ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
###| identity_authorized_shop_id | string | Returned for some `BC_AUTH_TT` identities.<br><br>The ID of the TikTok Shop that the TikTok Account User in Business Center identity is associated with. |
###| store_id | string | Returned only when `identity_type` is `TTS_TT`.<br><br>The ID of the TikTok Shop that a TikTok Account User for TikTok Shop identity is associated with. |
###| profile_image | string | Temporary profile image URL for the TikTok account that is associated with the identity.<br><br>Validity period: around 48 hours. The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds.<br><br>Once the URL expires, you need to call [/gmv_max/custom_anchor_video_list/get/](https://business-api.tiktok.com/portal/docs?id=1830215925061633) to obtain a new URL. |
###| user_name | string | The username of the TikTok account that is associated with the identity. |
##| video_info | object | Details of the video in the post. |
###| video_id | string | The ID of the video.<br><br>Example: `video_12345`. |
###| video_cover_url | string | Temporary URL for the video cover.<br><br>Validity period: around 24 hours. The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds.<br><br>Once the URL expires, you need to call [/gmv_max/custom_anchor_video_list/get/](https://business-api.tiktok.com/portal/docs?id=1830215925061633) to obtain a new URL. |
###| preview_url | string | Temporary preview URL for the video.<br><br>Validity period: six hours.<br><br>Once the URL expires, you need to call [/gmv_max/custom_anchor_video_list/get/](https://business-api.tiktok.com/portal/docs?id=1830215925061633) to obtain a new URL. |
###| height | number | The height of the video in pixels.<br><br>Example: 1920. |
###| width | number | The width of the video in pixels.<br><br>Example: 1080. |
###| bit_rate | number | The bit rate of the video in bps.<br><br>Example: 8500323. |
###| duration | number | The duration of the video in seconds.<br><br>Example: 3.669. |
###| size | number | The size of the video in bytes.<br><br>Example: 3898461. |
###| signature | string | The MD5 of the video.<br><br>Example: `ab1cd2345e678fghi91jkl23b456m7no`. |
###| format | string | The format of the video.<br><br>Example: `mp4`. |
###| definition | string | The definition of the video.<br><br>Example: `1080p`. |
###| fps | number | The frames per second (FPS) of the video.<br><br>Example: 27. |
```

### Example
#### Include `spu_id_list` in the response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "custom_anchor_video_list": [
            {
                "identity_info": {
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TT_USER",
                    "profile_image": "{{profile_image}}",
                    "user_name": "{{user_name}}"
                },
                "item_id": "{{item_id}}",
                "spu_id_list": [
                    "{{spu_id}}"
                ],
                "text": "{{text}}",
                "video_info": {
                    "bit_rate": 824497,
                    "definition": "540p",
                    "duration": 10.444,
                    "format": "mp4",
                    "fps": 30,
                    "height": 960,
                    "preview_url": "{{preview_url}}",
                    "signature": "{{signature}}",
                    "size": 1076382,
                    "video_cover_url": "{{video_cover_url}}",
                    "video_id": "{{video_id}}",
                    "width": 540
                }
            }
        ]
    }
}
(/code)
```
#### Exclude `spu_id_list` from the response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "custom_anchor_video_list": [
            {
                "identity_info": {
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TT_USER",
                    "profile_image": "{{profile_image}}",
                    "user_name": "{{user_name}}"
                },
                "item_id": "{{item_id}}",
                "text": "{{text}}",
                "video_info": {
                    "bit_rate": 824497,
                    "definition": "540p",
                    "duration": 10.444,
                    "format": "mp4",
                    "fps": 30,
                    "height": 960,
                    "preview_url": "{{preview_url}}",
                    "signature": "{{signature}}",
                    "size": 1076382,
                    "video_cover_url": "{{video_cover_url}}",
                    "video_id": "{{video_id}}",
                    "width": 540
                }
            }
        ]
    }
}
(/code)
```

