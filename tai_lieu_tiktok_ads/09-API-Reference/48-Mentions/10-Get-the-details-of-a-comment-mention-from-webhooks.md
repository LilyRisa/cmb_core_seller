---
title: "Get the details of a comment mention from webhooks"
doc_id: 1831358502098946
path: "API Reference / Mentions / Get the details of a comment mention from webhooks"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the details of a comment mention from webhooks

Use this endpoint to retrieve the details of a mention in a comment obtained from [the mentions webhook](https://business-api.tiktok.com/portal/docs?id=1825113658431489), enabling real-time monitoring of business mentions in comment threads. The mention can originate from a post comment or reply that mentions a business's handle.

> <span style="color:DodgerBlue">**Note**</span><br>
> Retrieve the details within 48 hours of receiving the webhook to ensure data availability.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/mention/comment/get/

**Method** GET

**Required permission scopes**: `biz.brand.insights`, `comment.list`

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok Business Accounts. <br><br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1825105003772930#item-link-Obtain%20an%20access%20token).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|business_id {Required}|string|Application specific unique identifier for the TikTok Business Account.<br><br>You can get the ID through the `open_id` field returned by [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1825105003772930#item-link-Obtain%20an%20access%20token).|
|comment_id{Required}|string|The ID of the comment or reply that mentions a business in the comment text.<br><br>Pass the `comment_id` as obtained from `comment_id` within the [`content` of the mentions webhook event](https://business-api.tiktok.com/portal/docs?id=1825113658431489#item-link-Event%20content).|
|item_id{Required}|string|The ID of the post on which the comment was published.<br><br>Pass the post ID as obtained from `video_id` within the [`content` of the mentions webhook event](https://business-api.tiktok.com/portal/docs?id=1825113658431489#item-link-Event%20content).|
|fields|string[]|Requested fields.<br> If `fields` is not set, only the <b>default</b> fields will be returned.<br><br>Default value: `["comment_id","comment_text"]`.<br><br><b>Supported fields</b>:<ul><li>`item_id`: Unique identifier for the post.</li><li>`video_link`: A generic URL for the post.</li><li>`video_likes`: Total number of likes on the post.</li><li>`thumbnail_url`: The URL of the post thumbnail.</li><li>`commenter_handle_name`: The handle name of the user who made the comment.</li><li>`comment_id`: Unique identifier for the comment.</li><li>`comment_type`: The type of comment.</li><li>`comment_text`: The comment text that mentions the business's handle.</li><li>`comment_create_time`: The time when the comment was posted.</li><li>`comment_likes`: Total number of likes for the comment.</li></ul>|
```


### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/mention/comment/get/?business_id={{business_id}}&comment_id={{comment_id}}&item_id={{item_id}}&fields=["item_id","video_link","caption","video_likes","thumbnail_url","commenter_handle_name","comment_id","comment_type","comment_text","comment_create_time","comment_likes"]' \
--header 'Access-Token: {{Access-Token}}' \
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
#|comment|object|Information about the mention in the comment.|
##|item_id|string|Unique identifier for the post.<br><br>Example: `6995316737801538821`.|
##|video_link|string|The generic URL of the post, in the format of https://www.tiktok.com/@/video/&#123;item_id&#125;.<br><br>Example: `https://www.tiktok.com/@/video/7231234123412341234`.|
##|video_likes|number|Total number of likes on the post.<br><br>Example: 243.|
##|thumbnail_url|string|The URL of the post thumbnail.<br><br>Validity period: 48 hours.<br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: `https://p16-sign-sg.tiktokcdn.com/tos-maliva-p-0068/1f9a445423844b82bd79317b42b9953f~c5_300x400.webp?x-expires=1614034800&x-signature=qChosOGafzBcTctPm9zYiF8Y2MA%3D"`.<br><br><span style="color:darkred"><b>Note</b></span>: For TikTok photo posts, the value of this field will be an empty string (`""`).|
##|commenter_handle_name|string|The handle name of the user who made the comment.<br><br>Example: `angely`.|
##|comment_id|string|Unique identifier for the comment.<br><br>Example: `6990565363377392901`.|
##|comment_type|string|The type of comment.<br><br>Enum values:<ul><li>`COMMENT`: Top-level comment.</li><li>`REPLY`: Comment reply.</li></ul>|
##|comment_text|string|The comment text that mentions the brand's handle.<br><br>Example: `This is a comment #comment 🙂`.|
##|comment_create_time|string|Unix/epoch date-time when the comment was posted, in UTC timezone.<br><br>Example: `2025-04-01 06:49:10 +0000 UTC`.|
##|comment_likes|number|Total number of likes for the comment.<br><br>Example: 243.|
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
        "comment":
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "video_likes": 101,
                "thumbnail_url": "{{thumbnail_url}}",
                "commenter_handle_name": "{{commenter_handle_name}}",
                "comment_id": "{{comment_id}}",
                "comment_likes": 0,
                "comment_text": "{{comment_text}}",
                "comment_create_time": "2025-04-29 06:11:59 +0000 UTC",
                "comment_type": "COMMENT"
            }
    }
}

(/code)
```

