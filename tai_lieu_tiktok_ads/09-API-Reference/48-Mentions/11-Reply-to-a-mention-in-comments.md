---
title: "Reply to a mention in comments"
doc_id: 1825107991945218
path: "API Reference / Mentions / Reply to a mention in comments"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Reply to a mention in comments

Use this endpoint to reply to a video comment or reply that mentions a business's handle.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/comment/reply/create/

**Method** POST

**Required permission scope**: `comment.list.manage`

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok Business Accounts. <br><br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1825105003772930#item-link-Obtain%20an%20access%20token).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|business_id {Required}|string|Application specific unique identifier for the TikTok Business Account.<br><br>You can get the ID through the `open_id` field returned by [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1825105003772930#item-link-Obtain%20an%20access%20token).|
|video_id {Required}|string|Unique identifier for the video on which you want to create a reply.<br><br>You can get the `video_id` from the `item_id` field returned by [/business/mention/comment/list/](https://business-api.tiktok.com/portal/docs?id=1825107985360898).|
|comment_id {Required}|string|Unique identifier for the comment to which you want to create a reply.<br><br>You can get the`comment_id` from the `comment_id` field returned by [/business/mention/comment/list/](https://business-api.tiktok.com/portal/docs?id=1825107985360898).|
|text {Required}|string|Text content of the reply you want to create.<br><br>Length limit: 150 characters (UTF-8 encoding).<br><br>Example: `This is a comment #comment 🙂`.|
```

### Example
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/comment/reply/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id" : "{{business_id}}",
    "video_id": "{{video_id}}",
    "comment_id" : "{{comment_id}}",
    "text": "{{text}}"
}'
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
#|comment_id|string|Unique identifier for the newly created reply.<br><br>Example: `6990565363377392901`.|
#|parent_comment_id|string|Unique identifier for the comment to which the reply was created.<br><br>Example: `6990565363377392901`.|
#|video_id|string|Unique identifier for the video where the reply was created.<br><br>Example: `6990565363377392901`.|
#|user_id|string|Developer application and TikTok account scoped unique identifier for the user that made the reply.<br><br>Example: `ce9b7be629b0447af777a0ad9455783f61b7f790982b49fbda04a23b4722eaffe`.|
#|create_time|integer|Unix/Epoch date-time when the reply was created, in UTC timezone.<br><br>Example: 1627617835.|
#|text|string|Text content of the reply.<br><br>Example: `This is a comment #comment 🙂`.|
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
        "video_id": "{{video_id}}",
        "parent_comment_id": "{{parent_comment_id}}",
        "comment_id": "{{comment_id}}",
        "create_time": "1701334608",
        "text": "{{text}}",
        "user_id": "{{user_id}}"
    }
}
(/code)
```

