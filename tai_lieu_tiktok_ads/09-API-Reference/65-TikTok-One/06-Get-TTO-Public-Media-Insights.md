---
title: "Get TTO Public Media Insights"
doc_id: 1828185411810305
path: "API Reference / TikTok One / Get TTO Public Media Insights"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get TTO Public Media Insights

Use this endpoint to get information about the videos of a creator on [TikTok One (TTO) Creator Marketplace](https://ads.tiktok.com/creative/creatormarketplace).

## Comparing TCM and TTO Creator Marketplace endpoints

The following table outlines the differences between TikTok Creator Marketplace (TCM) and TTO Creator Marketplace endpoints.


```xtable
| Changes {24%}| TCM Endpoint{36%} | TTO Creator Marketplace Endpoint{40%} |
|---|---|---|
| Endpoint path | /v1.3/tcm/creator/public/video/list/| /v1.3/tto/tcm/creator/public/video/list/ |
| Same request parameters | `video_ids`<br>`cursor`<br>`limit` | `video_ids`<br>`cursor`<br>`limit` |
| Deprecated request parameters | N/A | `creator_id` |
| New request parameters | N/A | `tto_tcm_account_id`<br>`handle_name` |
| Same response parameters | `posts`<br>`posts.video_id`<br>`posts.caption`<br>`posts.likes`<br>`posts.comments`<br>`posts.shares`<br>`posts.video_views`<br>`posts.embed_url`<br>`posts.thumbnail_url`<br>`posts.create_time`<br>`posts.display_name`<br>`page_info`<br>`page_info.has_more`<br>`page_info.cursor` | `posts`<br>`posts.video_id`<br>`posts.caption`<br>`posts.likes`<br>`posts.comments`<br>`posts.shares`<br>`posts.video_views`<br>`posts.embed_url`<br>`posts.thumbnail_url`<br>`posts.create_time`<br>`posts.display_name`<br>`page_info`<br>`page_info.has_more`<br>`page_info.cursor` |
| Deprecated response parameters | N/A | `posts.engagement_count`<br>`posts.engagement_rate` |
| New response parameters | N/A | `posts.favorites` |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/creator/public/video/list/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TTO Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
```


**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|tto_tcm_account_id {Required}|string|The ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146).|
|handle_name {Required}|string|The handle name of the TTO Creator Marketplace creator that you want to search for.<br><br>The exact match is supported.<br><br><span style="color:darkred"><b>Note</b></span>: If you specify the handle name of a creator that is not on TTO Creator Marketplace yet, an error will occur.|
|video_ids|string[]|The IDs of the videos to filter the results by.<br><br>Max size: 25.<br><br>If this field is not specified, the results won't be filtered by video IDs.|
|cursor|number|The timestamp cursor of the video from which you want to retrieve subsequent videos.<br><br>If the response parameter `has_more` is `true`, pass the returned `cursor` to this parameter in the next request to retrieve the subsequent videos.<br><br>Default value: 0. By default, the most recent videos will be returned.|
|limit|number|The number of posts to return for each request.<br><br>Value range: 1-25.<br><br>Default value: 10.|
```

### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/creator/public/video/list/?tto_tcm_account_id={{tto_tcm_account_id}}&handle_name={{handle_name}}' \
--header 'Access-Token: {{access_token}}' \
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|posts|object[]|The list of public posts of the creator.|
##|display_name|string|The display name of the creator.|
##|video_id|string|Unique identifier for the video.<br><br>Example: `1234567890`.|
##|thumbnail_url|string|Temporary URL for the video thumbnail.<br><br>Validity period: around seven days.<br><br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds.<br><br>Once the URL expires, you need to call [/tto/tcm/creator/public/video/list/](https://business-api.tiktok.com/portal/docs?id=1828185411810305) to obtain a new URL.|
##|embed_url|string|An embeddable link for this TikTok video. This URL can be used to embed the TikTok video in external websites or applications.<br><br>If the privacy setting of the post is "Friends" or "Only you", the video will not be viewable through this link.|
##|caption|string|Video description.|
##|likes|integer|Likes.<br>Total number of likes on the video.<br><br>Example: 100.|
##|comments|integer|Comments.<br>Total number of comments on the video.<br><br>Example: 100.|
##|shares|integer|Shares.<br>Total number of times the video has been shared.<br><br>Example: 100.|
##|favorites|integer|Favorites.<br>The total number of times the video has been added to favorites.<br><br>Example: 100.|
##|video_views|integer|Video views.<br>The number of times viewers who watched the video.<br><br>Example: 100.<br><br><span style="color:darkred"><b>Note</b></span>: Replays will not be counted.|
##|create_time|string|The time when the video was created, in Unix/epoch timestamp in milliseconds (UTC+0 time).<br><br>Example: `1743090990000`.|
##|caption_hashtags|string[]|A list of hashtags that appear in the post caption.
##|mentioned_accounts|string[]|A list of the display names of TikTok accounts that have been @mentioned in the post caption.
##|collaboration_status|string|A status indicating if the post has been used as Branded Content.<br><br>Enum values:<ul><li>`PAID_PARTNERSHIP`: Posts linked to TTO Campaign Links.</li><li>`CREATOR_EARNS_COMMISSION`: TikTok Shop creator earns commission from affiliate links, learn more [here](https://ads.tiktok.com/help/article/faqs-affiliate-creatives-authorization?lang=en).</li><li>`NONE`</li>
#|page_info|object|Pagination information.|
##|has_more|boolean|Whether more videos are available.<br><br>Supported values: `true`, `false`.|
##|cursor|integer|The timestamp cursor that marks the time of the last video returned in the current request.<br><br>If `has_more` in the response is `true`, you can pass the value to the `cursor` parameter in your subsequent request to retrieve subsequent posts.<br><br>Example: `1734031433000`.|
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
        "page_info": {
            "cursor": 1734031433000,
            "has_more": false
        },
        "posts": [
            {
                "caption": "{{caption}}",
                "comments": 0,
                "create_time": "1734031433000",
                "display_name": "{{display_name}}",
                "embed_url": "{{embed_url}}",
                "favorites": 0,
                "likes": 3,
                "shares": 1,
                "thumbnail_url": "{{thumbnail_url}}",
                "video_id": "{{video_id}}",
                "video_views": 236
            }
        ]
    }
}
(/code)
```
