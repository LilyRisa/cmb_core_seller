---
title: "Get trending videos related to hashtags"
doc_id: 1825119056527362
path: "API Reference / Discovery / Get trending videos related to hashtags"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get trending videos related to hashtags

Use this endpoint to retrieve the top 20 trending videos that use a popular hashtag in the selected top country of the hashtag and timeframe. These videos are ranked by a score that combines views, comments, likes, and shares.


## Before you start
Call [/discovery/trending_list/](https://business-api.tiktok.com/portal/docs?id=1825119032526849) to obtain a list of popular hashtags on TikTok.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/discovery/video_list/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.<br><br>Pass one of the advertiser IDs returned via `advertiser_ids` from [/oauth2/access_token/](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|discovery_type {Required}|string|The type of discovery item.<br><br>Enum values:<ul><li>`HASHTAG`: hashtag-related videos.</li></ul>|
|hashtag_ids {Required}|string[]|A list of hashtag IDs.<br><br>Max size: 10.<br><br>Pass one or more popular hashtag IDs obtained via `hashtag_id` from [/discovery/trending_list/](https://business-api.tiktok.com/portal/docs?id=1825119032526849). <br><br>Example: `["4268426520694"]`.|
|country_code|string|Location code.<br><br>Default value: `US`.<br><br>Pass one of the location codes returned via `top_country_list` from `/discovery/trending_list/`.|
|date_range|string|The timeframe in which the videos have been popular.<br><br>Enum values:<ul><li>`1DAY`: last 1 day.</li><li>`7DAY`: last 7 days.</li><li>`30DAY`: last 30 days.</li><li>`120DAY`: last 120 days.</li></ul><br>Default value: `7DAY`.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/discovery/video_list/?advertiser_id={{advertiser_id}}&discovery_type=HASHTAG&hashtag_ids=["{{hashtag_id}}","{{hashtag_id}}"]&country_code=GB&date_range=7DAY' \
--header 'Access-Token: {{Access-Token}}'
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
#|list|object[]|The list of trending videos using popular hashtags.<br><br>This object array returns the top 20 videos related to each popular hashtag in the selected region and timeframe.|
##|hashtag_id|string|Hashtag ID.<br><br>Example: `4268426520694`.|
##|hashtag_name|string|Hashtag name.<br><br>Example: `yummy`.|
##|top_video_list|object[]|The list of trending videos for the hashtag.|
###|video_id|string|Unique identifier for the TikTok video.|
###|embed_url|string|An embeddable link for the TikTok video.<br><br>This URL can be used to embed the TikTok video in external websites or applications.<br><br>If the privacy setting of the post is "Friends" or "Only you", the video will not be viewable through this link.|
###|share_url|string|A shareable URL for the TikTok video.|
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
        "list": [
            {
                "hashtag_id": "{{hashtag_id}}",
                "hashtag_name": "relatable",
                "top_video_list": [
                    {
                        "embed_url": "{{embed_url}}",
                        "video_id": "{{video_id}}",
                        "share_url": "{{share_url}}"
                    },
                    ...,
                    {
                        "embed_url": "{{embed_url}}",
                        "video_id": "{{video_id}}",
                        "share_url": "{{share_url}}"
                    }
                ]
            },
            {
                "hashtag_id": "{{hashtag_id}}",
                "hashtag_name": "funny",
                "top_video_list": [
                    {
                        "embed_url": "{{embed_url}}",
                        "video_id": "{{video_id}}",
                        "share_url": "{{share_url}}" 
                    },
                    ...,
                    {
                        "embed_url": "{{embed_url}}",
                        "video_id": "{{video_id}}",
                        "share_url": "{{share_url}}" 
                    }
                ]
            }
        ]
    }
}
(/code)
```
