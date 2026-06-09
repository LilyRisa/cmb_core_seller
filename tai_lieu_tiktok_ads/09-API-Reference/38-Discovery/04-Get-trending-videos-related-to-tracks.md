---
title: "Get trending videos related to tracks"
doc_id: 1825119068941314
path: "API Reference / Discovery / Get trending videos related to tracks"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get trending videos related to tracks

Use this endpoint to retrieve the top trending videos that use a popular track in a selected region. 

## Before you start
Call [/discovery/cml/trending_list/](https://business-api.tiktok.com/portal/docs?id=1825119063013505) to obtain a list of popular tracks from the Commercial Music Library.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/discovery/cml/video_list/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators.<br><br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token).|
```

**Parameters**


```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|business_id {Required}|string|Application specific unique identifier for the TikTok account.<br><br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token) to this field.|
|commercial_music_id {Required}|string|The ID of the track.|
|country_code|string|The code of the location to filter the results by.<br><br>Default value: `US`.<br><br>See [Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010) for the supported values.|
```

### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/discovery/cml/video_list/?business_id={{business_id}}&commercial_music_id={{commercial_music_id}}&country_code=US' \
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
#|commercial_music_id|string|The ID of the track.|
#|commercial_music_name|string|The name of the track.|
#|top_video_list|object[]|The list of the top 20 trending videos for the track.|
##|video_id|string|Unique identifier for the TikTok video.|
##|embed_url|string|An embeddable link for the TikTok video.<br><br>This URL can be used to embed the TikTok video in external websites or applications.<br><br>If the privacy setting of the post is "Friends" or "Only you", the video will not be viewable through this link.|
##|share_url|string|A shareable URL for the TikTok video.|
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
        "commercial_music_id": "{{commercial_music_id}}",
        "commercial_music_name": "{{commercial_music_name}}",
        "top_video_list": [
            {
                "embed_url": "{{embed_url}}",
                "share_url": "{{share_url}}",
                "video_id": "{{video_id}}"
            },
            ...,
            {
                "embed_url": "{{embed_url}}",
                "share_url": "{{share_url}}",
                "video_id": "{{video_id}}"
            }
        ]
    }
}
(/code)
```
