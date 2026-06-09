---
title: "Get Spark Ad posts"
doc_id: 1738376465972226
path: "API Reference / Spark Ads Using Authorized Posts / Get Spark Ad posts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Spark Ad posts

Use this endpoint to get a list of Spark Ads posts that have been authorized to an ad account.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 
  ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/tt_video/list/|/v1.3/tt_video/list/|
|Request parameter data type|`advertiser_id`: number|`advertiser_id`: string|
|New request parameters| / | ` item_types` |
|Response parameter name|`url`(in `video_info`): string|`preview_url`(in `video_info`): string|
|Response parameter data type|`invite_start_time`: datetime<br>`auth_end_time`: datetime<br>`auth_start_time`: datetime<br>`id`(in `anchor_list`): number<br>`item_id`(in `item_info`): number|`invite_start_time`: string<br>`auth_end_time`:string<br>`auth_start_time`: string<br>`id`(in `anchor_list`):string<br>`item_id`(in `item_info`): string|
|New response parameters|/|`url`(in `anchor_list`)<br>`item_type`<br>`carousel_info`(including all parameters within the object)<br>`spu_id`<br>`spu_name`<br>`store_id`|
```

## Request

**Endpoint**  
https://business-api.tiktok.com/open_api/v1.3/tt_video/list/

**Method** GET

**Header**

``` xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {required}|string| The Advertiser ID. |
| item_types | string[] | The types of Spark Ads posts to filter. <br><br>Enum values:<ul><li>`VIDEO`: video post.</li> <li>`CAROUSEL`: photo post.</li></ul> <br>Default value: `["VIDEO", "CAROUSEL"]`. |
|keyword|string| The text or TikTok post ID as a keyword to search TikTok posts by.<br><br><ul><li>If you want to search for TikTok posts based on text, provide a text string with a maximum length of 500 characters. This search type supports searching in multiple languages and fuzzy matches.<br> Example: "summer".</li><li> If you want to search for TikTok posts based on `item_id`, provide a numeric string with a minimum length of 19 characters. This search type supports exact matches.<br>  Example: "1234567891234567891" |
|page |number|The current page number. The default value is `1`. The value must be ≥ 1. |
|page_size |number|The page size. The default value: `20`. The value must be in the range of 1-50. |
```

### Example

``` xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "page=PAGE" \
--data-urlencode "page_size=PAGE_SIZE" \
https://business-api.tiktok.com/open_api/v1.3/tt_video/list/
(/code)
```

## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|The returned data.|
#|list |object[]|  Information about the list of Spark Ads posts.|
##|item_info |object| Information about the Spark Ads post. |
###|auth_code |string| The authorization code for the Spark Ads post. |
###|item_id |string| The ID of the Spark Ads post. |
###|text |string| The description of the Spark Ads post. |
###|status | string | Item status. For enum values, see [Enumerations-TikTok Post Item Status](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
###| item_type | string | The type of Spark Ads post.<br><br> Enum values: <ul><li>`VIDEO`: video post. </li><li>`CAROUSEL`: photo post.</li></ul>|
###| carousel_info| object | Information about the photo post.<br><br><p><span style="color:darkred"><b>Note</b></span>: When `item_type` is `VIDEO`, the value of this field will be null. |
####| image_info | object[] | Information about the images used in the photo post. <br> The images are returned in the same order as they are shown in the photo post. |
#####| image_url | string | The URL of the image. <br><br>Validity period: 90 days. |
#####| image_height | integer | The height of the image, measured in pixels. |
#####| image_width | integer | The width of the image, measured in pixels. |
####| music_info | object | Information about the music used in the photo post.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the photo post is directly published in the TikTok App without music, the value of this field will be null. |
#####| music_url | string | The URL of the music.<br><br> Validity period: 90 days. |
#####| music_duration | integer | The duration of the music, in seconds. |
###|anchor_list | object[]|Information about anchors in the post (video). |
####|id|string | Anchor ID. |
####|title |string | Anchor title.|
####|status | string | Anchor status. Enum values: `CHECK_ING` (anchor review in progress), `CHECK_FAILED` (anchor review failed), `CHECK_SUCCESS` (anchor review successful). |
####|product_regions |string[] | Regions where the ad can be deployed.|
####|url| string | Item URL. |
####| spu_id | string | Returned only when the post contains a product anchor link <br><br> The SPU ID of the product that the post is associated with. |
####| spu_name | string | Returned only when the post contains a product anchor link <br><br> The name of the product that the post is associated with. |
####| store_id | string | Returned only when the post contains a product anchor link <br><br> The ID of the TikTok Shop that the product belongs to. |
##|video_info |object| Information about the video post. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When `item_type` is `CAROUSEL`, the values for the parameters within the `video_info` object will be null. </li><li>When `item_type` is `VIDEO` and status is not `HESITATE_RECOMMEND`, `ONLY_AUTHOR_SEE`, or `ONLY_FRIEND_SEE`, there will be no data for parameters within the `video_info` object and the values for these parameters might be null, `0`, or `""` (empty string).</li></ul> |
###|bit_rate |integer| Video bit rate, in bps. |
###|duration |float| The duration of the video, in seconds.  |
###|size |integer|The size of the video, in bytes.  |
###|height |integer|The height of the video.  |
###|width |integer| The width of the video.  |
###|poster_url |string|The URL to the video poster. It is valid for an hour. When it expires, you need to call the endpoint again to get a new URL.  |
###|preview_url |string|The preview URL for the video. <br>It is valid for an hour. When it expires, you need to call the endpoint again to get a new URL.  <br><p><span style="color:darkred"><b>Note</b></span>: Returns empty string `""` if the authorization expires or if the TikTok Post Item Status (value of `status` under `item_info`) is not `HESITATE_RECOMMEND` (Made public). For the enum values of TikTok Post Item Status, see [Enumerations-TikTok Post Item Status](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
###|signature |string|MD5 for the video file.  |
##|user_info |object| Information about the TikTok account.  |
###|tiktok_name |string| The user name of the TikTok account. |
###|identity_id |string| Identity ID. |
###|identity_type |string| Identity Type. |
##|auth_info |object| Information about the authorization. |
###|invite_start_time |string| The time when the authorization starts (UTC+0), in the format of 2017-01-01 00:00:00. |
###|auth_start_time |string| The time when the authorization code becomes valid (UTC+0), in the format of 2017-01-01 00:00:00. |
###|ad_auth_status |string| The authorization status. For allowed values, see  [Enumeration-TikTok Post Authorization Status](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
###|auth_end_time |string| The time when the authorization code expires (UTC+0), in the format of 2017-01-01 00:00:00. |
#|page_info |object | The information about pagination. |
##|page |number | The current page number.|
##|page_size |number | The size of the page. |
##|total_number |number | The total number of results. |
##|total_page |number | The total number of pages. |
|request_id |string|The unique log ID of the request.  |
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "xxx",
    "data": {
        "page_info": {
            "total_page": 15,
            "total_number": 300,
            "page": 1,
            "page_size": 20
        },
        "list": [
            {
                "user_info": {
                    "tiktok_name": "{{tiktok_name}}",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "AUTH_CODE"
                },
                "item_info": {
                    "anchor_list": [
                        {
                            "id": "{{id}}",
                            "status": "CHECK_FAILED",
                            "product_regions": [
                                "ID"
                            ],
                            "url": "",
                            "title": "{{title}}"
                        }
                    ],
                    "text": "{{text}}",
                    "status": "HESITATE_RECOMMEND",
                    "auth_code": "{{auth_code}",
                    "item_id": "{{item_id}}",
					"item_type": "VIDEO",
					"carousel_info": null
                },
                "auth_info": {
                    "auth_start_time": "2021-05-23 11:26:45",
                    "ad_auth_status": "AUTHORIZED",
                    "invite_start_time": "2021-05-23 11:28:02",
                    "auth_end_time": "2022-07-22 11:26:45"
                },
                "video_info": {
                    "duration": 8.081,
                    "preview_url": "{{preview_url}}",
                    "bit_rate": {{bit_rate}},
                    "height": {{height}},
                    "width": {{width}},
                    "signature": "{{signature}}",
                    "size": 2379969,
                    "poster_url": "{{poster_url}}"
                }
            },
            ...
           ]
}
(/code);
```

