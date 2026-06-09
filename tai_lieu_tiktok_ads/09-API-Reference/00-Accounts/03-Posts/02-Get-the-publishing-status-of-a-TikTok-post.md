---
title: "Get the publishing status of a TikTok post"
doc_id: 1816387106635778
path: "API Reference / Accounts / Posts / Get the publishing status of a TikTok post"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the publishing status of a TikTok post

Use this endpoint to obtain the publishing status of a TikTok video post or photo post.

## Before you start
Initiate a post publishing task using [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234) or [/business/photo/publish/](https://business-api.tiktok.com/portal/docs?id=1803630424390658) and obtain `share_id` from the response.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/publish/status/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators.<br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| business_id {Required} | string | Application specific unique identifier for the TikTok account. <br><br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) to this field.|
| publish_id {Required} | string | Unique identifier for a post publishing task.<br><br>Pass the value of the `share_id` field returned in the response of  [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234) or [/business/photo/publish/](https://business-api.tiktok.com/portal/docs?id=1803630424390658) to this field. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/publish/status/?business_id={{business_id}}&publish_id={{publish_id}}' \
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
#| status | string | The publishing status of the TikTok post. <br><br>Enum values: <ul><li>`PROCESSING_DOWNLOAD`: The download of content from the URL is in progress. This status is only available when you publish the post using a file URL.</li><li>`PUBLISH_COMPLETE`: The post has passed moderation and has been successfully published.</li><li>`FAILED`: The publishing process failed due to an error. </li><li>`SEND_TO_USER_INBOX`: The draft post has been successfully uploaded and the system has sent a corresponding notification to creator's inbox.</li></ul>|
#| post_ids | string[] | Returned only when `status` is `PUBLISH_COMPLETE` and the posts are publicly viewable.<br><br>The unique identifiers for the published TikTok posts.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Post IDs require up to three minutes of processing time. If post IDs are not immediately available in the initial response, retry after three minutes.</li><li>You can use the IDs as the filter field `video_ids` in [/business/video/list/](https://business-api.tiktok.com/portal/docs?id=1762228421622786) to obtain the detailed metrics for the posts. </li></ul>|
#| reason | string | Returned only when `status` is `FAILED`. <br><br>The reason for the publishing failure. <br><br>Example: `frame_rate_check_failed`.<br><br>To find out the detailed description for each reason, see [Failure reasons](https://business-api.tiktok.com/portal/docs?id=1810515090228226#item-link-Failure%20reasons). |
```

### Example

#### For a successfully published post
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "post_ids": [
            "{{post_id}}"
        ],
        "status": "PUBLISH_COMPLETE"
    }
}
(/code)
```
#### For a failed post
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "reason": "frame_rate_check_failed",
        "status": "FAILED"
    }
}
(/code)
```
