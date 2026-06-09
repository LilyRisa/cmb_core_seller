---
title: "Get review info of ads"
doc_id: 1740559669226498
path: "API Reference / Ad Review / Get review info of ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get review info of ads

Use this endpoint to get information about the review of ads. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/ad/review_info/| /v1.3/ad/review_info/|
|Request parameter data type |`advertiser_id`: number <br> `ad_ids`: number[]|`advertiser_id`: string<br> `ad_ids`: string[]
|New request parameter |/|`lang`: string|
|Response parameter name| `is_pass` |`is_approved`|
|Response parameter data type | `reject_info`: object|  `reject_info`: object[]|
|Response parameter name and data type|`id`: number |`ad_id`: string|
|New response parameters |/|`carousel_music_content` <br>`music_id`|
```

## Request
**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/ad/review_info/

**Method** GET

**Header**
```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```
**Parameters**
```xtable
|Field|Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID.|
|ad_ids {Required}|string[]|The list of ad IDs. Quantity: 1-100.|
|lang|string|Language.|
```

### Example
```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id={{ADVERTISER_ID}}" \
--data-urlencode "ad_ids=[\"AD_IDS\"]" \
--data-urlencode "lang=[\"LANG\"]" \
https://business-api.tiktok.com/open_api/v1.3/ad/review_info/
(/code)

```

## Response
```xtable
|Field{30%}|Type{12%}|Description{58%}|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|The returned data.  |
#|ad_review_map |object|Review data at the ad level.|
##|ad_id |string|The ad ID.|
##|is_approved |boolean|Whether the ad has been approved or not.|
##|review_status |string|The ad review status. <br>Enum values：<ul><li>`ALL_AVAILABLE`: The ad has been reviewed and can be delivered.</li><li>`PART_AVAILABLE`: The ad has been reviewed and can only be delivered to some of the specified targeting countries.</li><li>`UNAVAILABLE`: The ad has been reviewed and cannot be delivered.</li></ul>|
##|appeal_status {-To-be-deprecated}|string| Ad appeal status. <br><br><p><span style="color:darkred"><b>Note</b></span>: The value of this field will always be null.|
##|forbidden_placements|string[]|The placements that failed the review. For enum values, see [Enumeration - Ad Management - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|forbidden_ages|string[]|The audience age ranges that failed the review. For enum values, see [Enumeration - Audience Age Range](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|forbidden_locations|string[]|The targeted regions that failed the review. For enum values, see [Appendix - location codes](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010). |
##|forbidden_operation_systems|string[]|The audience operating systems that failed the review. For enum values, see [Enumeration - Audience Operating System](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|last_audit_time|string|<p><span style="color:darkred"><b>Note</b></span>: The value of this field will always be null.<br>The last time when the ad was reviewed.|
##|reject_info|object[]|Details about the rejection.|
###|suggestion|string|The review suggestion.|
###|reasons|string[]|The list of rejection reasons.|
###|forbidden_ages|string[]|The audience age ranges that failed the review. For enum values, see [Enumeration - Audience Age Range](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
###|forbidden_locations|string[]|The targeted regions that failed the review. For enum values, see [Appendix - location codes](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010). |
###|forbidden_placements|string[]|The placements that failed the review. For enum values, see [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
###|content_info|object|The content of the ad that has been reviewed.|
####|image_content|object|The image content.|
#####|image_id|string|The ID of the image that has been reviewed.|
####|video_content|object|The video content that has been reviewed.|
#####|video_id|string|The ID of the video that is being reviewed.|
####|text_content|string|The text.|
####|content_type|string|The type of the content that has been reviewed，<br>Enum values：`MODE_STRING`，`MODE_IMAGE`，`MODE_VIDEO`, `MODE_CAROUSEL_MUSIC` (Music in the Carousel Ads).|
####| carousel_music_content | object | Returned when `content_type` is `MODE_CAROUSEL_MUSIC`.   <br>The content of the music in the Carousel Ads that has been reviewed.   |
#####| music_id | string | The ID of the piece of music used in the Carousel Ads.   |
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "LOG_ID",
    "data": {
        "ad_review_map": {
            "AD_ID": {
                "reject_info": [
                    {
                        "content_info": {
                            "text_content": null,
                            "content_type": "MODE_VIDEO",
                            "image_content": {
                                "image_id": "IMAGE_ID"
                            },
                            "video_content": {
                                "video_id": "VIDEO_ID",

                            }
                        },
                        ],
                        "suggestion": "Please add background audio or modify the unclear audio. For more policy details, please check \" Ad Caption/Text, Image and Video\" in \"TikTok Advertising Policies - Ad Creatives\".",
                        "reasons": [
                            "The ad or video has no background audio or the background audio is incoherent/unclear. "
                        ]
                    }
                ],
                "is_approved": true,
                "last_audit_time": 1596023135,
                "forbidden_placements": [
                    "PLACEMENT_HELO",
                    "PLACEMENT_TIKTOK",
                    "PLACEMENT_TOPBUZZ"
                ],
                "ad_id": AD_ID,
                "review_status": "PART_AVAILABLE",
                "appeal_status": null,
                "forbidden_locations": [
                    "RU"
                ],
                "forbidden_operation_systems": [
                    "IOS"
                ],
                "forbidden_ages": [
                ]
            }
        }
    }
}
(/code);
```
