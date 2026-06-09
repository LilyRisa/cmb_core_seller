---
title: "Get comments"
doc_id: 1738086301876225
path: "API Reference / Ad Comments / Get comments"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get comments

Use this endpoint to get the comments under the video ads of an ad account. 

You need to specify a search value and the time range for the comments you want to get. This endpoint returns comments for both paid impression ads and Spark Ads.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/comment/list/|/v1.3/comment/list/|
|Request parameter data type|`advertiser_id`: number|`advertiser_id`: string|
|Response parameter name |`is_top`<br>`list`| `is_pinned` <br>`comments`|
|Response parameter data type |`comment_id`: number<br>`original_comment_id`: number<br>`campaign_id`: number<br>`adgroup_id`: number<br>`ad_id`: number|`comment_id`: string<br>`original_comment_id`: string<br>`campaign_id`: string<br>`adgroup_id`: string<br>`ad_id`: string|
|Response parameter name and data type|`app_id`: number<br>`item_id`: number| `app`: string<br>`tiktok_item_id`: string|
|New response parameter|/|`video_play_url`<br>`video_cover_url`<br>`user_avatar_url`<br>`user_name`<br>`user_id`|
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/comment/list/

**Method** GET

**Header**

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|--- |--- |--- |
|advertiser_id {required}|string| Advertiser ID.|
|comment_type |string[]|  Comment type. <br><br>Enum values: `ALL`, `COMMENT`, `REPLY`.<br><br>Default value: `["ALL"]`|
|search_field {required} |string| Field to search by. <br><br>Enum value: <ul><li>`ADGROUP_ID`: To filter by an ad group ID.</li></ul>|
|search_value {required}|string| Value to search. <br><br>When `search_field` is `ADGROUP_ID`, specify an ad group ID via this field.<ul><li>To obtain the list of ad groups within your ad account, use [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922).</li></ul><br>Example: `1234567891234567`.|
|sort_field |string| Field to sort by. <br><br>Enum values: `CREATE_TIME`, `LIKES`, `REPLIES`. <br><br>Default value: `CREATE_TIME`.|
|sort_type |string| Sorting order. <br><br>Enum values: `ASC`, `DESC`. <br><br>Default value: `DESC`.|
|start_time {required} |string| Start date of the time range that you want to get comments, in the format of `YYYY-MM-DD`. |
|end_time {required} |string| End date of the time range that you want to get comments, in the format of `YYYY-MM-DD`. |
|page_size |number| Page size.<br><br> Value range: 1-100. <br><br>Default value: `10`.|
|page |number| Current page number. <br><br>Value range: ≥ 1. <br><br>Default value: 1.|
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "start_time=START_TIME" \
--data-urlencode "end_time=END_TIME" \
--data-urlencode "comment_type=[\"comment_type\"]" \
--data-urlencode "page_size=PAGE_SIZE" \
--data-urlencode "page=PAGE" \
--data-urlencode "search_value=SEARCH_VALUE" \
--data-urlencode "search_field=SEARCH_FIELD" \
--data-urlencode "sort_field=SORT_FIELD" \
--data-urlencode "sort_by_asc=SORT_BY_ASC" \
--data-urlencode "comment_status=[\"COMMENT_STATUS\"]" \
https://business-api.tiktok.com/open_api/v1.3/comment/list/
(/code)
```

## Response

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|message|string|The return message. For Details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For details, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|The returned data.|
#|comments |object[]| List of comments. |
##|comment_id |string| Comment ID. |
##|app |string| App ID. |
##|content |string| Comment content. |
##|likes |number| Number of likes. |
##|replies |number| Number of replies. |
##|comment_type |string| Comment type. It can be `COMMENT`, which is an original comment, or `REPLY`, which is a reply to an original comment. |
##|original_comment_id |string| ID of the original comment. A value will be returned only when `comment_type` is `REPLY`.|
##|comment_status |string| Comment status. Enum values: `HIDDEN`, `PUBLIC`. |
##|hit_blockedword |boolean| Whether the comment contains blocked words. |
##|ad_text |string|Ad title. |
##|create_time |string| The time when the comment is created.|
##|campaign_id |string| ID of the campaign that contains the ad that the comment is added to. |
##|campaign_name |string| Name of the campaign that contains the ad that the comment is added to.|
##|adgroup_id |string| ID of the ad group that contains the ad that the comment is added to. |
##|adgroup_name |string| Name of the ad group that contains the ad that the comment is added to.|
##|ad_id |string| ID of the ad that the comment is added to. |
##|ad_name |string| Name of the ad that the comment is added to.|
##|tiktok_item_id|string|TikTok video ID.|
##|identity_id|string|Identity ID.|
##|identity_type|string| Identity type. `CUSTOMIZED_USER` (Customized User), `TT_USER` (TikTok Business Account User). For details about identities, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097).|
##|is_pinned|boolean| Whether the comment is pinned.|
##|can_delete|boolean|Whether the comment can be deleted.|
##|is_auth_ttba|boolean|Whether the user is linked to a TikTok Business Account.|
##|is_auth_comment_manage_scope|boolean|Whether the user has the comment management permission.|
##|video_play_url|string|Video play URL.|
##|video_cover_url|string|Video cover URL.|
##|user_avatar_url|string|URL to the users' avatar.|
##|user_name|string|Unique user name.|
##| user_id | string | The ID of the TikTok user who made the comment. <br> <br>Example: `"6123456789123456789"`. |
#|page_info |object| Page information. |
##|page |number| Current page number. |
##|page_size |number| Page size. |
##|total_number |number| Total number of results. |
##|total_page |number| Total number of pages. |
|request_id |string|Unique ID of the request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "202105210639490102360430824C1AE673",
    "data": {
        "comments": [
            {
                "likes": 0,
                "create_time": "2021-01-14 07:04:16 +0000 UTC",
                "comment_type": "COMMENT",
                "hit_blockedword": false,
                "ad_text": "",
                "campaign_name": "Bytedance_3006_JP_JA_IOS/AND_1123_dogfood",
                "adgroup_name": "Ad Group20201123093958",
                "ad_id": "1684147917059121",
                "replies": 0,
                "comment_status": "PUBLIC",
                "original_comment_id": null,
                "campaign_id": "1684147923311617",
                "adgroup_id": "1684147923311633",
                "ad_name": "Ad Name20201123094902",
                "comment_id": "6917508027378253825",
                "content": "Another creative",
                "app": null
            },
            {
                "likes": 0,
                "create_time": "2021-01-14 05:54:24 +0000 UTC",
                "comment_type": "REPLY",
                "hit_blockedword": false,
                "ad_text": "",
                "campaign_name": "Bytedance_3006_JP_JA_IOS/AND_1123_dogfood",
                "adgroup_name": "Ad Group20201123093958",
                "ad_id": "1684147917057073",
                "replies": 0,
                "comment_status": "PUBLIC",
                "original_comment_id": "6917441294860550145",
                "campaign_id": "1684147923311617",
                "adgroup_id": "1684147923311633",
                "ad_name": "Creative award~~~",
                "comment_id": "6917490050552823810",
                "content": "hsbbdbd@user285",
                "app": null
            },
        ],
        "page_info": {
            "total_number": 2,
            "page_size": 10,
            "page": 1,
            "total_page": 1
        }
    }
}
(/code);
```
