---
title: "Get info about TikTok posts"
doc_id: 1740218522178562
path: "API Reference / Identity / Get info about TikTok posts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get info about TikTok posts

Use this endpoint to get the information about one or more TikTok posts that you published using the `AUTH_CODE`, `TT_USER` or `BC_AUTH_TT` identity.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/identity/video/info/| /v1.3/identity/video/info/|
| Request parameter data type | `advertiser_id`: number | `advertiser_id`: string |
| Request parameter data type | `item_id`: number |  `item_id`: string |
| New request parameters | / | `item_ids`<br>`item_type` |
| Response parameter name | `item_info` | `video_detail` |
| Response parameter name and data type | `id`: number  | `anchor_id`: string |
| Response parameter data type | `item_id`: number |  `item_id`: string |
| New response parameters | / | `video_details`<br>`item_type` <br>`carousel_info` (including all parameters within the object) |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/identity/video/info/

**Method** GET

**Header**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID. |
| identity_type {Required} | string | Identity type. <br><br>Enum values: `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT`. <br><br>See [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097) for details. |
| identity_id {Required} | string | Identity ID. <br><br>To get the identities within an ad account, use [/identity/get/](https://business-api.tiktok.com/portal/docs?id=1740218420781057).|
| identity_authorized_bc_id {+Conditional} | string | Required when `identity_type` is `BC_AUTH_TT`. <br><br> ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
| item_id{+Conditional} | string | Either `item_id` or `item_ids` must be specified. <br><br><ul><li>If you want to retrieve the information for a single TikTok post, pass `item_id`. </li><li>If you want to retrieve the information for multiple TikTok posts, pass `item_ids`.</li></ul> <br> The TikTok post ID that is associated with the provided Identity ID (`identity_id`). <br><br> To get the TikTok posts associated with an identity, use [/identity/video/get/](https://business-api.tiktok.com/portal/docs?id=1740218475032577) and extract the TikTok post ID (`item_id`) from the response. |
| item_ids {+Conditional} | string[] | Either `item_id` or `item_ids` must be specified.<br><br><ul><li>If you want to retrieve the information for a single TikTok post, pass `item_id`. </li><li>If you want to retrieve the information for multiple TikTok posts, pass `item_ids`.</li></ul> <br> A list of TikTok post IDs that are associated with the provided Identity ID (`identity_id`). <br><br>Max size: 20. <br><br>To get the TikTok posts associated with an identity, use [/identity/video/get/](https://business-api.tiktok.com/portal/docs?id=1740218475032577) and extract the TikTok post ID (`item_id`) from the response. |
| item_type | string | The type of TikTok posts that you want to retrieve. <br><br>Enum values: <ul><li> `VIDEO`: video post. </li><li>`CAROUSEL`: photo post. </li></ul><br>Default value: `VIDEO`. |
```

### Example
#### Get info about one TikTok post associated with a `TT_USER` identity
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/identity/video/info/?advertiser_id={{advertiser_id}}&identity_type=TT_USER&identity_id={{identity_id}}&item_id={{item_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

#### Get info about one TikTok post associated with a `AUTH_CODE` identity
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/identity/video/info/?advertiser_id={{advertiser_id}}&identity_type=AUTH_CODE&identity_id={{identity_id}}&item_ids=["{{item_id}}"]' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

#### Get info about multiple TikTok posts associated with a `BC_AUTH_TT` identity
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/identity/video/info/?advertiser_id={{advertiser_id}}&identity_type=BC_AUTH_TT&identity_id={{identity_id}}&identity_authorized_bc_id={{identity_authorized_bc_id}}&item_ids=["{{item_id}}","{{item_id}}"]' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The log id of the request, which uniquely identifies a request. |
|data |object| Returned data. |
#| video_detail | object | Returned when `item_id` is passed in the request.<br><br>Information about the TikTok post. |
##| item_type | string | The type of TikTok post. <br><br>Enum values: <ul><li> `VIDEO`: video post. </li><li>`CAROUSEL`: photo post. </li></ul>|
##| item_id | string | TikTok post ID. |
##| status | string | Visibility status of the TikTok post. <br><br>Enum values: <ul><li>`ITEM_STATUS_HESITATE_RECOMMEND`: visible to all. </li><li>`STATUS_ONLY_FRIEND_SEE`: visible to friends.</li><li>`ITEM_STATUS_ONLY_AUTHOR_SEE`: private.</li></ul> |
##| text | string | Text for the TikTok post. |
##| anchor_list | object[] | Anchor information. |
###| anchor_id | string | Anchor ID. |
###| title | string | Anchor title. |
###| status | string | Anchor status. <br><br>Enum values: `CHECKING`, `CHECK_FAILED`, `CHECK_SUCCESS`. |
###| url | string | Anchor URL. |
###| product_regions | string[] | Countries and regions that the ad can be delivered to. This is a list of country or region codes. For example, ` ["DE"]`. |
##| auth_info | object | Returned only when `identity_type` is set to `AUTH_CODE` in the request. <br><br>Authorization status. |
###| ad_auth_status | string | The authorization status. |
###| auth_start_time | string | Time when the authorization code is valid from (UTC+0), in the format of `"YYYY-MM-DD HH:MM:SS"`. |
###| auth_end_time | string | Time when the authorization code expires (UTC+0), in the format of `"YYYY-MM-DD HH:MM:SS"`. |
###| invite_start_time_stamp | string | The time when the authorization starts (UTC+0), in the format of `"YYYY-MM-DD HH:MM:SS"`. |
##| video_info | object | Information about the video post.<br><br><p><span style="color:darkred"><b>Note</b></span>: When `item_type` is `CAROUSEL`, the value of this field will be null.  |
###| bit_rate | integer| Bit rates, in bps. |
###| duration | float| The duration of the video, in seconds. |
###| size | integer | The size of the video, in bytes. |
###| height | integer | The height of the video. |
###| width | integer | The width of the video. |
###| poster_url | string | The URL to the video poster. <br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds.  <br>When it expires, you need to call this endpoint to get a new URL. |
###| signature | string |The MD5 for the video file. |
###| url | string | Video preview link. <br><br>Validity period: an hour. When it expires, you need to call the endpoint again to get a new URL. |
##| carousel_info | object | Information about the photo post. <br> <br><p><span style="color:darkred"><b>Note</b></span>: When `item_type` is `VIDEO`, the value of this field will be null. |
###| image_info | object[] | Information about the images used in the photo post. <br> <br>The images are returned in the same order as they are shown in the photo post. |
####| image_id | string | Image ID. |
####| image_url | string | The URL of the image. <br> <br>Validity period: 90 days. |
####| image_height | integer | The height of the image, measured in pixels. |
####| image_width | integer | The width of the image, measured in pixels. |
###| music_info | object | Information about the music used in the photo post. <br> <br><p><span style="color:darkred"><b>Note</b></span>: If the photo post is directly published in the TikTok App without music, the value of this field will be null.   |
####| music_id | string | Music ID. |
####|music_url | string | The URL of the music. <br> <br>Validity period: 90 days. |
####| music_duration | integer | The duration of the music, in seconds. |
#| video_details | object[] | Returned when `item_ids` is passed in the request. <br><br>Information about the TikTok posts. |
##| item_type | string | The type of TikTok post. <br><br>Enum values: <ul><li> `VIDEO`: video post. </li><li>`CAROUSEL`: photo post. </li></ul>|
##| item_id | string | TikTok post ID. |
##| status | string | Visibility status of the TikTok post. <br><br>Enum values: <ul><li>`ITEM_STATUS_HESITATE_RECOMMEND`: visible to all. </li><li>`ITEM_STATUS_ONLY_AUTHOR_SEE`: visible to friends.</li><li>`STATUS_ONLY_FRIEND_SEE`: private.</li></ul> |
##| text | string | Text for the TikTok post. |
##| auth_info | object | Returned only when `identity_type` is set to `AUTH_CODE` in the request. <br><br>Authorization status. |
###| ad_auth_status | string | The authorization status. |
###| auth_start_time | string | Time when the authorization code is valid from (UTC+0), in the format of `"YYYY-MM-DD HH:MM:SS"`. |
###| auth_end_time | string | Time when the authorization code expires (UTC+0), in the format of `"YYYY-MM-DD HH:MM:SS"`. |
###| invite_start_time_stamp | string | The time when the authorization starts (UTC+0), in the format of `"YYYY-MM-DD HH:MM:SS"`. |
##| video_info | object | Information about the video post.<br><br><p><span style="color:darkred"><b>Note</b></span>: When `item_type` is `CAROUSEL`, the value of this field will be null.  |
###| bit_rate | integer | Bit rates, in bps. |
###| duration | float | The duration of the video, in seconds. |
###| size | integer | The size of the video, in bytes. |
###| height | integer | The height of the video. |
###| width | integer | Width of the video. |
###| poster_url | string | URL to the video poster. <br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds.  <br>When it expires, you need to call this endpoint to get a new URL. |
###| signature | string | The MD5 for the video file. |
###| url | string | Video preview link. <br><br>Validity period: an hour. When it expires, you need to call the endpoint again to get a new URL. |
##| carousel_info | object | Information about the photo post. <br> <br><p><span style="color:darkred"><b>Note</b></span>: When `item_type` is `VIDEO`, the value of this field will be null. |
###| image_info | object[] | Information about the images used in the photo post. <br> <br>The images are returned in the same order as they are shown in the photo post. |
####| image_id | string | Image ID. |
####| image_url | string | The URL of the image. <br> <br>Validity period: 90 days. |
####| image_height | integer | The height of the image, measured in pixels. |
####| image_width | integer | The width of the image, measured in pixels. |
###| music_info | object | Information about the music used in the photo post. <br> <br><p><span style="color:darkred"><b>Note</b></span>: If the photo post is directly published in the TikTok App without music, the value of this field will be null.   |
####| music_id | string | Music ID. |
####|music_url | string | The URL of the music. <br> <br>Validity period: 90 days. |
####| music_duration |integer | The duration of the music, in seconds. |
```

### Example
#### Get info about one TikTok post associated with a `TT_USER` identity
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "video_detail": {
            "auth_info": null,
            "anchor_list": null,
            "item_id": "{{item_id}}",
            "text": "",
            "status": "ITEM_STATUS_HESITATE_RECOMMEND",
            "video_info": {
                "duration": 1.6,
                "width": 720,
                "url": "{{url}}",
                "size": 244797,
                "bit_rate": 1223985,
                "height": 1280,
                "poster_url": "{{poster_url}}",
                "format": "mp4",
                "signature": "{{signature}}"
            }
        }
    }
}
(/code)
```

#### Get info about one TikTok post associated with an `AUTH_CODE` identity
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "video_details": [
            {
                "item_type": "VIDEO",
                "anchor_list": null,
                "item_id": "{{item_id}}",
                "carousel_info": null,
                "status": "ITEM_STATUS_HESITATE_RECOMMEND",
                "text": "{{text}}",
                "auth_info": {
                    "auth_end_time": "2025-05-20T11:40:50Z",
                    "ad_auth_status": "AUTHORIZED",
                    "auth_start_time": "2024-05-20T11:40:50Z",
                    "invite_start_time": "2024-05-20T11:43:00Z"
                },
                "video_info": {
                    "height": 1920,
                    "width": 1080,
                    "url": "{{url}}",
                    "signature": "{{signature}}",
                    "size": 8119495,
                    "bit_rate": 9897296,
                    "format": "mp4",
                    "poster_url": "{{poster_url}}",
                    "duration": 6.563
                }
            }
        ]
    }
}
(/code)
```

#### Get info about multiple TikTok posts associated with a `BC_AUTH_TT` identity
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "video_details": [
            {
                "text": "{{text}}",
                "anchor_list": null,
                "status": "ITEM_STATUS_HESITATE_RECOMMEND",
                "auth_info": null,
                "video_info": {
                    "url": "{{url}}",
                    "height": 1024,
                    "format": "mp4",
                    "signature": "{{signature}}",
                    "width": 576,
                    "poster_url": "{{poster_url}}",
                    "duration": 8.474,
                    "size": 774110,
                    "bit_rate": 730809
                },
                "item_id": "{{item_id}}"
            },
            {
                "text": "",
                "anchor_list": null,
                "status": "ITEM_STATUS_HESITATE_RECOMMEND",
                "auth_info": null,
                "video_info": {
                    "url": "{{url}}",
                    "height": 1024,
                    "format": "mp4",
                    "signature": "{{signature}}",
                    "width": 576,
                    "poster_url": "{{poster_url}}",
                    "duration": 6.245,
                    "size": 913323,
                    "bit_rate": 1169989
                },
                "item_id": "{{item_id}}"
            }
        ]
    }
}
(/code)
```


