---
title: "Get Authorized TTO Media Insights"
doc_id: 1834913554657282
path: "API Reference / TikTok One / Get Authorized TTO Media Insights"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Authorized TTO Media Insights

Use this endpoint to get the reporting insights of videos of a TikTok creator who has joined TikTok One (TTO).


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/creator/authorized/video/list/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators. <br>Use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Obtain%20a%20Creator%20access%20token) or [/tt_user/oauth2/refresh_token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Renew%20a%20Creator%20access%20token) endpoint to get or renew an access token.|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|creator_id {Required}|string|Application specific unique ID of the TikTok account.<br><br> Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Obtain%20a%20Creator%20access%20token) or [/tt_user/oauth2/refresh_token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Renew%20a%20Creator%20access%20token) to this field.|
|video_ids|string[]|The IDs of the videos to retrieve information for.<br><br> If `video_ids` is provided, `limit` and `cursor` will be ignored.|
|limit|integer|The number of videos that you want to retrieve per request. <br><br>Value range: 1-10.<br> Default value: 10.|
|cursor|integer|Timestamp cursor. <br>The videos before the timestamp value in milliseconds will be returned in reverse-chronological order. <br>By default, the value is 0 and the most recent videos will be returned.|
```

### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/creator/authorized/video/list/?creator_id={{creator_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|posts|object[]|List of posts within the creator's TikTok account.|
##|video_id|string|Unique identifier for the video.|
##|display_name|string|The display name (nickname) for the creator.|
##|thumbnail_url|string|Temporary URL for profile photo of the creator. <br> The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds.<br><br> Validity period: around 7 days.|
##|embed_url|string|An embeddable link for this TikTok video. This URL can be used to embed the TikTok video in external websites or applications.|
##|caption|string|Video description.|
##|likes|number|Likes. <br>Total number of likes on the video.|
##|comments|number|Comments. <br>Total number of comments on the video.|
##|shares|number|Shares. <br>Total number of times the video has been shared.|
##|video_views|number|Video views.<br> The number of times viewers watched your video.|
##|favorites|number|Favorites. <br>The total number of times the video has been added to favorites.|
##|create_time|string|The time when the video was created, in the format of an Epoch/Unix timestamp in milliseconds.  <br><br>Example: `1749206540000`.|
##|caption_hashtags|string[]|A list of hashtags that appear in the post caption.
##|mentioned_accounts|string[]|A list of the display names of TikTok accounts that have been @mentioned in the post caption.
##|collaboration_status|string|A status indicating if the post has been used as Branded Content.<br><br>Enum values:<ul><li>`PAID_PARTNERSHIP`: Posts linked to TTO Campaign Links.</li><li>`CREATOR_EARNS_COMMISSION`: TikTok Shop creator earns commission from affiliate links, learn more [here](https://ads.tiktok.com/help/article/faqs-affiliate-creatives-authorization?lang=en).</li><li>`NONE`</li>
#|has_more|boolean|Whether an additional page of data is available.<br><br> Supported values: `true`, `false`.|
#|cursor|integer|Timestamp cursor. The time value of the last item returned in milliseconds according to the current request. <br>You need to use this cursor value in your subsequent request to get the remaining videos.|
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
        "cursor": 1743739292000,
        "has_more": false,
        "posts": [
            {
                "caption": "",
                "comments": 0,
                "create_time": "1749206540000",
                "display_name": "{{display_name}}",
                "embed_url": "{{embed_url}}",
                "favorites": 0,
                "likes": 0,
                "shares": 0,
                "thumbnail_url": "{{thumbnail_url}}",
                "video_id": "{{video_id}}",
                "video_views": 0
            },
            {
                "caption": "",
                "comments": 0,
                "create_time": "1743739292000",
                "display_name": "{{display_name}}",
                "embed_url": "{{embed_url}}",
                "favorites": 0,
                "likes": 0,
                "shares": 0,
                "thumbnail_url": "{{thumbnail_url}}",
                "video_id": "{{video_id}}",
                "video_views": 1
            }
        ]
    }
}
(/code)
```
