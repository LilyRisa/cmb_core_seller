---
title: "Get posts under an identity"
doc_id: 1740218475032577
path: "API Reference / Identity / Get posts under an identity"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get posts under an identity

Use this endpoint to get all posts under an identity.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/identity/video/get/| /v1.3/identity/video/get/|
| Request parameter data type | `advertiser_id`: number <br> `cursor`: number | `advertiser_id` : string <br>`cursor`: string |
| New request parameters | / | `item_type`<br>`exclude_adsonly` |
| Response parameter name | `list`| `video_list`|
| Response parameter data type | `cursor` : number <br> `item_id` : number <br>| `cursor` : string <br>`item_id` : string|
| Response parameter name and data type | `id` : number |`anchor_id` : string |
| New response parameters | / | `item_type` <br>`carousel_info` (including all parameters within the object) |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/identity/video/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID. |
| identity_id {Required} | string | Identity ID.|
| identity_type {Required} | string | Identity type. Enum values: `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT`. See [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097) for details. |
| identity_authorized_bc_id{+Conditional} | string | ID of the Business Center that a TikTok Account User in Business Center identity is associated with. Required when `identity_type` is `BC_AUTH_TT`.  |
| item_type | string | The type of TikTok posts that you want to retrieve. <br><br>Enum values: <ul><li> `VIDEO`: video post. </li><li>`CAROUSEL`: photo post. </li></ul><br>Default value: `VIDEO`. |
|keyword|string| Valid only when `identity_type` is set to `AUTH_CODE`. Otherwise, an error will occur.<br><br>The text or TikTok post ID as a keyword to search TikTok posts by.<br><br><ul><li>If you want to search for TikTok posts based on text, provide a text string with a maximum length of 500 characters. This search type supports searching in multiple languages and fuzzy matches.<br> Example: "summer".</li><li> If you want to search for TikTok posts based on `item_id`, provide a numeric string with a minimum length of 19 characters. This search type supports exact matches.<br>  Example: "1234567891234567891" |
|exclude_adsonly|boolean|Whether to exclude ads only posts from the results.<br>Ads-only posts are those you publish from a TikTok account as ads, which remain hidden from the public on the account's profile page.<br><br>Supported values:<ul><li>`false`: To retrieve both organic and ads only posts.</li><li>`true`: To retrieve a maximum of 500 most recent organic posts.</li></ul><br>Default value: `false`.|
| cursor | string | Cursor for pagination.|
| count | number | Number of TikTok posts you want to get. <br><br>Value range: 1-20.<br>If you specify a value greater than 20, this field will be ignored and a maximum of 20 TikTok posts associated with the identity will be returned.|
```

### Example

```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/identity/video/get/?advertiser_id={{advertiser_id}}&identity_type=TT_USER&identity_id={{identity_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#| cursor | string | Timestamp cursor or cursor for pagination.<ul><li>If `identity_type` is `TT_USER` or `BC_AUTH_TT` :<ul><li>When `exclude_adsonly` is `false` in the request, `cursor` is the time value of the last item returned according to the current request. You need to use this cursor value in your subsequent request to get the next posts.</li><li>When `exclude_adsonly` is `true` in the request, `cursor` is the cursor for pagination. If `has_more` in the response is `true`, you need to use the returned `cursor` in the subsequent request to receive the next page of results.</li></ul></li><li>If `identity_type` is `AUTH_CODE`, `cursor` is the cursor for pagination. If `has_more` in the response is `true`, you need to use the returned `cursor` in the subsequent request to receive the next page of results.</li></ul> |
#| has_more | bool | Whether more data is available. |
#| video_list | object[] | List of posts. |
##| item_type | string | The type of TikTok post. <br><br>Enum values: <ul><li> `VIDEO`: video post. </li><li>`CAROUSEL`: photo post. </li></ul>|
##| item_id | string | TikTok post ID. |
##| status | string | Status of the TikTok post. <br><br>Enum values:<ul><li> `ITEM_STATUS_HESITATE_RECOMMEND`: visible to all. </li><li>`STATUS_ONLY_FRIEND_SEE`: visible to friends.</li><li>`ITEM_STATUS_ONLY_AUTHOR_SEE`: private.</li></ul>  |
##| text | string | Text for the TikTok post. |
##| auth_info | object | Authorization Information. |
###| ad_auth_status | string | Authorization status. |
###| auth_end_time | string | Time when the authorization code is valid from (UTC+0), in the format of 2017-01-01 00:00:00. |
###| auth_start_time | string | Time when the authorization code is valid from (time zone: UTC+0), in the format of 2017-01-01 00:00:00. |
###| invite_start_time| string | The time when the authorization starts (UTC+0), in the format of 2017-01-01 00:00:00. |
##| anchor_list | object | Information about anchors. |
###| anchor_id | string | Anchor ID. |
###| title | string | Anchor title. |
###| status | string | Anchor status. Enum values: `CHECK_ING`, `CHECK_FAILED`, `CHECK_SUCCESS`. |
###| url | string | Anchor URL. |
###| product_regions | string[] | Regions that the ad can be delivered to. This is a list of country or region codes. For example, `["DE"]`. |
##| video_info | object | Information about the video post.<br><br><p><span style="color:darkred"><b>Note</b></span>: When `item_type` is `CAROUSEL`, the value of this field will be null. |
###| bit_rate | integer| Bit rates, in bps. |
###| duration | float | Duration of the video, in seconds. |
###| size | integer | Video size, in bytes. |
###| height | integer | Video height. |
###| width | integer| Video width. |
###| poster_url | string | URL to the video poster. <br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds.  <br>When it expires, you need to call this endpoint to get a new URL. |
###| signature | string | MD5 for the video file. |
###| url | string | Video preview link. <br> It is valid for an hour.  <br>When it expires, you need to call the endpoint again to get a new URL. |
###| format | string | Format of the video. |
##| carousel_info | object | Information about the photo post. <br> <br><p><span style="color:darkred"><b>Note</b></span>: When `item_type` is `VIDEO`, the value of this field will be null. |
###| image_info | object[] | Information about the images used in the photo post. <br> <br>The images are returned in the same order as they are shown in the photo post. |
####| image_id | string | Image ID. |
####| image_url | string | The URL of the image. <br> <br>Validity period: 90 days. |
####| image_height | integer | The height of the image, measured in pixels. |
####| image_width | integer| The width of the image, measured in pixels. |
###| music_info | object | Information about the music used in the photo post. <br> <br><p><span style="color:darkred"><b>Note</b></span>: If the photo post is directly published in the TikTok App without music, the value of this field will be null.   |
####| music_id | string | Music ID. |
####|music_url | string | The URL of the music. <br> <br>Validity period: 90 days. |
####| music_duration |integer | The duration of the music, in seconds. |
|request_id|string|The log id of the request, which uniquely identifies a request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "cursor": "1780432720000",
        "has_more": true,
        "video_list": [
            {
                "anchor_list": null,
                "auth_info": null,
                "carousel_info": null,
                "item_id": "{{item_id}}",
                "item_type": "VIDEO",
                "status": "ITEM_STATUS_HESITATE_RECOMMEND",
                "text": "{{text}}",
                "video_info": {
                    "bit_rate": 15184988,
                    "duration": 13.8,
                    "format": "mp4",
                    "height": 1920,
                    "poster_url": "{{poster_url}}",
                    "signature": "{{signature}}",
                    "size": 26194106,
                    "url": "{{url}}",
                    "width": 1080
                }
            },
            {
                "anchor_list": null,
                "auth_info": null,
                "carousel_info": null,
                "item_id": "{{item_id}}",
                "item_type": "VIDEO",
                "status": "ITEM_STATUS_HESITATE_RECOMMEND",
                "text": "{{text}}",
                "video_info": {
                    "bit_rate": 990999,
                    "duration": 16,
                    "format": "mp4",
                    "height": 1280,
                    "poster_url": "{{poster_url}}",
                    "signature": "{{signature}}",
                    "size": 1981998,
                    "url": "{{url}}",
                    "width": 720
                }
            }
        ]
    }
}

(/code)
```


