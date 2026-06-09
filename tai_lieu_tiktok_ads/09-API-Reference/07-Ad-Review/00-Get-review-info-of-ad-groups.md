---
title: "Get review info of ad groups"
doc_id: 1740554277061634
path: "API Reference / Ad Review / Get review info of ad groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get review info of ad groups

Use this endpoint to get information about the review of ad groups. 

An ad group needs to be approved before it can be deployed, and it may get rejected due to various reasons, such as incorrect placement or targeting selections. Based on the rejection reasons and suggestions you get, you can adjust your ad group accordingly. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{20%}|v1.2{32%}|v1.3{48%}|
|---|---|---|
|Endpoint path| /v1.2/adgroup/review_info/| /v1.3/adgroup/review_info/|
|Request parameter data type |`advertiser_id`: number <br> `adgroup_ids`: number[]|`advertiser_id`: string<br> `adgroup_ids`: string[]|
|New request parameter |/|`lang`: string|
|Response parameter name| `is_pass`(in `ad_review_map`)<br>`is_pass`(in `ad_group_review_map`) <br>`has_rejected_ad`|`is_approved`(in `ad_review_map`)<br>`is_approved`(in `ad_group_review_map`)<br>`contains_rejected_ads`|
|Response parameter data type | `reject_info`: object|`reject_info`: object[] |
|Response parameter name and data type |`id`(in `ad_review_map`): number <br> `id`(in `ad_group_review_map`  object): number<br>`reasons` (in `ad_group_review_map`  object): 	string[]|`ad_id`(in `ad_review_map`  object): string <br> `adgroup_id`(in `ad_group_review_map`  object): string<br>`reject_info` (in `ad_group_review_map`  object): object[]|
|New response parameter |/|`suggestion`(in `reject_info`of `ad_group_review_map`  object)<br>`reasons`(in `reject_info` of `ad_group_review_map`  object)<br>`forbidden_ages`  (in `reject_info` of `ad_group_review_map` object)<br>`forbidden_locations`  (in `reject_info` of `ad_group_review_map` object)<br>`forbidden_placements`  (in `reject_info` of `ad_group_review_map` object)<br>`content_info`(in `reject_info`of `ad_group_review_map` object)<br>`image_content`(in `content_info` of `ad_group_review_map` object)<br>`image_id`(in `image_content` of `ad_group_review_map` object)<br>`video_content`(in `content_info` of `ad_group_review_map` object)<br>`video_id`(in `video_content` of `ad_group_review_map` object)<br>`text_content`(in `content_info` of `ad_group_review_map` object)<br>`content_type`(in `content_info` of `ad_group_review_map` object)<br>`carousel_music_content` (in `content_info` of `ad_review_map` object) <br>`music_id` (in `content_info` of `ad_review_map` object)<br>`carousel_music_content` (in `content_info` of `ad_group_review_map` object)<br>`music_id` (in `content_info` of `ad_group_review_map` object)|
```
## Request

**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/adgroup/review_info/

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
|adgroup_ids {Required}|string[]|The list of ad group IDs. Currently, we support a maximum of 20 ad group IDs in the request.|
|lang |string|Language.|
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id={{ADVERTISER_ID}}" \
--data-urlencode "adgroup_ids=[\"ADGROUP_IDS\"]" \
https://business-api.tiktok.com/open_api/v1.3/adgroup/review_info/
(/code)
```

## Response

```xtable
|Field{30%}|Type{12%}|Description{58%}|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|The returned data.  |
#|ad_review_map |object|The bi-dimensional structure for the review data at the ad level. The key for the first dimension is `adgroup_id`. The key for the second dimension is `ad_id`.|
##|ad_id |string|The ad ID.<br><br><p><span style="color:darkred"><b>Note</b></span>: For Smart+ ad groups with a partial or complete review failure (`review_status` within `ad_group_review_map` is `PART_AVAILABLE` or `UNAVAILABLE`), the `ad_id` within `ad_review_map` does not represent a real ad ID and cannot be used with [/ad/review_info/](https://business-api.tiktok.com/portal/docs?id=1740559669226498) to fetch ad-level review details. Instead, treat each `ad_id` as a material within the Smart+ Campaign. To understand review failures, examine the `reject_info` within `ad_review_map` returned by [/adgroup/review_info/](https://business-api.tiktok.com/portal/docs?id=1740554277061634). Afterwards, update the Smart+ Campaign accordingly via [/campaign/spc/update/](https://business-api.tiktok.com/portal/docs?id=1767334250066945). |
##|is_approved |boolean|Whether the ad has been approved or not.|
##|review_status |string|The ad review status. <br>Enum values：<ul><li>`ALL_AVAILABLE`: The ad has been reviewed and can be delivered.</li><li>`PART_AVAILABLE`: The ad has been reviewed and can only be delivered to some of the specified targeting countries.</li><li>`UNAVAILABLE`: The ad has been reviewed and cannot be delivered.</li></ul>|
##|appeal_status {-To-be-deprecated}|string| Ad appeal status. <br><br><p><span style="color:darkred"><b>Note</b></span>: The value of this field will always be null.|
##|forbidden_placements|string[]|The placements that failed the review. For enum values, see [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|forbidden_ages |string[]|Age groups that failed the review. For enum values, see [Enumeration - Age Group](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|forbidden_locations |string[]|The targeted regions that failed the review. For enum values, see [Appendix - Location codes](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010). |
##|forbidden_operation_systems |string[]|The audience operating systems that failed the review. For enum values, see [Enumeration - Operating Systems](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|last_audit_time | string|<p><span style="color:darkred"><b>Note</b></span>: The value of this field will always be null.<br>The last time when the ad was reviewed.|
##|reject_info |object[]|Details about the rejection.|
###|suggestion|string|The review suggestion.|
###|reasons|string[]|List of rejection reasons.|
###|forbidden_ages |string[]|Age groups that failed the review. For enum values, see [Enumeration - Age Group](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
###|forbidden_locations|string[]|The targeted regions that failed the review. For enum values, see [Appendix - Location codes](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010). |
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
#|ad_group_review_map |object|The review data at the ad group level, organized by `adgroup_id`. The key for the first dimension is `adgroup_id`.|
##|adgroup_id |string|Ad group ID.|
##|is_approved |boolean|Whether the ad group has been approved.|
##|review_status |string|The ad group review status. <br>Enum values: <ul><li>`ALL_AVAILABLE`: The ad group has been reviewed and all ads in the ad group can be delivered.</li><li>`PART_AVAILABLE`: The ad group has been reviewed and only some of the ads in the ad group can be delivered.</li><li>`UNAVAILABLE`: The ad group has been reviewed and all ads in the ad group cannot be delivered.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: For Smart+ ad groups with a partial or complete review failure (`review_status` within `ad_group_review_map` is `PART_AVAILABLE` or `UNAVAILABLE`), the `ad_id` within `ad_review_map` does not represent a real ad ID and cannot be used with [/ad/review_info/](https://business-api.tiktok.com/portal/docs?id=1740559669226498) to fetch ad-level review details. Instead, treat each `ad_id` as a material within the Smart+ Campaign. To understand review failures, examine the `reject_info` within `ad_review_map` returned by [/adgroup/review_info/](https://business-api.tiktok.com/portal/docs?id=1740554277061634). Afterwards, update the Smart+ Campaign accordingly via [/campaign/spc/update/](https://business-api.tiktok.com/portal/docs?id=1767334250066945). |
##|appeal_status|string|Ad group appeal status. <br><br>For enum values, see [Enumeration - Ad Group Review Appeal Status](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Ad%20Group%20Review%20Appeal%20Status). <br><br>To appeal the rejection of an ad group, call [/adgroup/appeal/](https://ads.tiktok.com/marketing_api/docs?id=1740571821728770). Then this field will be set to `APPEALING`(Appeal in progress).|
##|forbidden_placements |string[]|The placements that failed the review. For enum values, see [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|forbidden_ages |string[]|The audience age ranges that failed the review. For enum values, see [Enumeration - Age Groups](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|forbidden_locations |string[]|The targeted locations that failed the review. For enum values, see [Appendix - Location codes](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010).|
##|forbidden_operation_systems|string[]|The audience operating systems that failed the review. For enum values, see [Enumeration - Operating System](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|last_audit_time | string|<p><span style="color:darkred"><b>Note</b></span>: The value of this field will be the actual review time only when the ad group has been rejected.<br><br>The last time when the ad group was reviewed (UTC+0), in the format of `YYYY-MM-DD HH:MM:SS`. <br>Example: `2023-01-01 00:00:00`.|
##|contain_rejected_ads |boolean|Whether ads in this ad group have been rejected.|
##|reject_info |object[]|Details about the rejection.|
###|suggestion|string|The review suggestion.|
###|reasons|string[]|List of rejection reasons|
###|forbidden_ages|string[]|Age groups that failed the review. For enum values, see [Enumeration - Age Group](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
###|forbidden_locations |string[]|The targeted regions that failed the review. For enum values, see [Appendix - Location codes](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010). |
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
            "adgroup_id": {
                "ad_id": {
                    "reject_info": [
                        {
                            "content_info": {
                                "text_content": null,
                                "content_type": "MODE_VIDEO",
                                "image_content": {
                                    "image_id": "tos-maliva-p-0000/0ba31ad4244c48d3a61e42ea3f91649e"
                                },
                                "video_content": {
                                    "video_id": "video_id",

                                }
                            },
                            "forbidden_locations": [
                                "RU"
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
                    "ad_id": "ad_id",
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
        },
        "ad_group_review_map": {
            "adgroup_id": {
                "reasons": [
                    "some reason"
                ],
                "contains_rejected_ads": true,
                "is_approved": true,
                "forbidden_placements": [
                    "PLACEMENT_TOPBUZZ",
                    "PLACEMENT_HELO",
                    "PLACEMENT_TIKTOK"
                ],
                "adgroup_id": "adgroup_id",
                "review_status": "PART_AVAILABLE",
                "appeal_status": "NOT_APPEALED",
                "forbidden_locations": [
                    "RU"
                ],
                "forbidden_operation_systems": [
                    "IPHONE"
                ],
                "forbidden_ages": [
                ]
            }
        }
    }
}
(/code);
```
