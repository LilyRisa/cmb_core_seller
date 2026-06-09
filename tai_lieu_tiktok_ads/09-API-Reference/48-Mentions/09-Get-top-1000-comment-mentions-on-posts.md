---
title: "Get top 1000 comment mentions on posts"
doc_id: 1825107985360898
path: "API Reference / Mentions / Get top 1000 comment mentions on posts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get top 1000 comment mentions on posts

Use this endpoint to retrieve a list of up to 1,000 post comments or replies that mention a business's handle.

If more than 1,000 post comments or replies mention the business's handle, the top 1,000 with the highest number of comment likes will be returned. Note that comments or replies that solely consist of the mention (@) without additional text will be deprioritized.

To appear in the search results, the comment or reply must meet the following requirements:
- Account type: The TikTok account is a Business Account.
- Post visibility: The comment or reply is posted on a public post.
- Mention: The comment or reply explicitly mentions (@) the business's handle in the comment text.
- Lookback period: The post has been posted within the past 90 days.
- User age: The post is not posted by a user who is underage or of an unknown age. For users in Europe, an age of 17 or under is considered underage, while for users in the US and other countries or regions, an age of 12 or under is considered underage.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/mention/comment/list/

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
|fields|string[]|Requested fields. If `fields` is not set, only the <b>default</b> fields will be returned.<br><br>Default value: `["comment_id","comment_text"]`.<br><br><b>Supported fields</b>:<ul><li>`item_id`: Unique identifier for the post.</li><li>`video_link`: A generic URL for the post.</li><li>`caption`: The description of the post.</li><li>`video_likes`: Total number of likes on the post.</li><li>`thumbnail_url`: The URL of the post thumbnail.</li><li>`commenter_display_name`: The handle name of the user who made the comment.</li><li>`comment_id`: Unique identifier for the comment.</li><li>`comment_type`: The type of comment.</li><li>`comment_text`: The comment text that mentions the business's handle.</li><li>`comment_create_time`: The time when the comment was posted.</li><li>`comment_likes`: Total number of likes for the comment.</li></ul>|
|sort_field|string|The field to sort the result by.<br><br>Enum values:<ul><li>`VIDEO_LIKES`: The field `video_likes`.</li><li>`COMMENT_CREATE_TIME`: The field `comment_create_time`.</li><li>`COMMENT_LIKES`: The field `comment_likes`.</li></ul>Default value: `COMMENT_CREATE_TIME`.|
|sort_type|string|Sorting order.<br><br>Enum values:<ul><li>`ASC`: Ascending.</li><li>`DESC`: Descending.</li></ul>Default value: `DESC`.|
|number_of_days|number|The lookback period for the results, measured in days.<br><br>Value range: 1-90.<br>Default value: 90.<br><br>For instance, if you set this field to 90, the results for the last 90 days will be retrieved.|
|regions|string[]|The regions of posts that you want to filter the results by.<br><br><ul><li>If you specify this field, only comments on posts posted by TikTok users registered in the specified regions will be returned.</li><li>If this field is not specified, comments on all relevant posts will be returned.</li></ul><br>To learn about the allowed values for this field, refer to [List of values for `regions`](https://business-api.tiktok.com/portal/docs?id=1825107519114241#item-link-List%20of%20values%20for%20regions).|
|cursor|integer|Cursor for pagination. The position of the results that you want to retrieve from.<br><br>For instance, setting `cursor` to 10 means you want to retrieve the next page of results after the 10th position.<br><br>If the response parameter `has_more` is true, pass the returned cursor in this parameter in the subsequent request to receive the next page of results.<br><br>Default value: 0.|
|max_count|integer|The maximum number of comments to be returned for each page of data.<br><br>Default value: 10.<br>Value range: 1-100.<br><br><span style="color:darkred"><b>Note</b></span>: Due to our trust and safety policies, it is possible that the endpoint returns less than the `max_count` number of comments even if the response parameter `has_more` is `true`.|
```


### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/mention/comment/list/?business_id={{business_id}}&fields=["item_id","video_link","caption","video_likes","thumbnail_url","commenter_display_name","comment_id","comment_type","comment_text","comment_create_time","comment_likes"]&sort_field=COMMENT_LIKES&sort_type=ASC' \
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
#|comments|object[]|Information about the mentions in comments.|
##|item_id|string|Unique identifier for the post.<br><br>Example: `6995316737801538821`.|
##|video_link|string|The generic URL of the post, in the format of https://www.tiktok.com/@/video/&#123;item_id&#125;.<br><br>Example: `https://www.tiktok.com/@/video/7231234123412341234`.|
##|caption|string|Post description.<br><br>Example: `Nuki: me and me casa 🏠 #cat #kitten #britishshorthair`.|
##|video_likes|number|Total number of likes on the post.<br><br>Example: 243.|
##|thumbnail_url|string|The URL of the post thumbnail.<br><br>Validity period: 48 hours.<br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: `https://p16-sign-sg.tiktokcdn.com/tos-maliva-p-0068/1f9a445423844b82bd79317b42b9953f~c5_300x400.webp?x-expires=1614034800&x-signature=qChosOGafzBcTctPm9zYiF8Y2MA%3D"`.<br><br><span style="color:darkred"><b>Note</b></span>: For TikTok photo posts, the value of this field will be an empty string (`""`).|
##|commenter_display_name|string|The handle name of the user who made the comment.<br><br>Example: `angely`.|
##|comment_id|string|Unique identifier for the comment.<br><br>Example: `6990565363377392901`.|
##|comment_type|string|The type of comment.<br><br>Enum values:<ul><li>`COMMENT`: Top-level comment.</li><li>`REPLY`: Comment reply.</li></ul>|
##|comment_text|string|The comment text that mentions the brand's handle.<br><br>Example: `This is a comment #comment 🙂`.|
##|comment_create_time|string|Unix/epoch date-time when the comment was posted, in UTC timezone.<br><br>Example: `1628461703`.|
##|comment_likes|number|Total number of likes for the comment.<br><br>Example: 243.|
#|cursor|number|Cursor for the next page of results, to be passed in the cursor query parameter of the subsequent API request.|
#|has_more|boolean|Whether an additional page of data is available.|
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
        "cursor": 10,
        "has_more": true,
        "comments": [
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "video_likes": 101,
                "caption": "{{caption}}",
                "thumbnail_url": "{{thumbnail_url}}",
                "commenter_display_name": "{{commenter_display_name}}",
                "comment_id": "{{comment_id}}",
                "comment_likes": 0,
                "comment_text": "{{comment_text}}",
                "comment_create_time": "1698616575",
                "comment_type": "COMMENT"
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "video_likes": 104,
                "caption": "{{caption}}",
                "thumbnail_url": "{{thumbnail_url}}",
                "commenter_display_name": "{{commenter_display_name}}",
                "comment_id": "{{comment_id}}",
                "comment_likes": 0,
                "comment_text": "{{comment_text}}",
                "comment_create_time": "1695495795",
                "comment_type": "COMMENT"
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "video_likes": 105,
                "caption": "{{caption}}",
                "thumbnail_url": "{{thumbnail_url}}",
                "commenter_display_name": "{{commenter_display_name}}",
                "comment_id": "{{comment_id}}",
                "comment_likes": 0,
                "comment_text": "{{comment_text}}",
                "comment_create_time": "1696097179",
                "comment_type": "COMMENT"
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "video_likes": 110,
                "caption": "{{caption}}",
                "thumbnail_url": "{{thumbnail_url}}",
                "commenter_display_name": "{{commenter_display_name}}",
                "comment_id": "{{comment_id}}",
                "comment_likes": 0,
                "comment_text": "{{comment_text}}",
                "comment_create_time": "1699537820",
                "comment_type": "COMMENT"
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "video_likes": 103,
                "caption": "{{caption}}",
                "thumbnail_url": "{{thumbnail_url}}",
                "commenter_display_name": "{{commenter_display_name}}",
                "comment_id": "{{comment_id}}",
                "comment_likes": 0,
                "comment_text": "{{comment_text}}",
                "comment_create_time": "1697493375",
                "comment_type": "REPLY"
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "video_likes": 107,
                "caption": "{{caption}}",
                "thumbnail_url": "{{thumbnail_url}}",
                "commenter_display_name": "{{commenter_display_name}}",
                "comment_id": "{{comment_id}}",
                "comment_likes": 0,
                "comment_text": "{{comment_text}}",
                "comment_create_time": "1699637820",
                "comment_type": "REPLY"
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "video_likes": 106,
                "caption": "{{caption}}",
                "thumbnail_url": "{{thumbnail_url}}",
                "commenter_display_name": "{{commenter_display_name}}",
                "comment_id": "{{comment_id}}",
                "comment_likes": 0,
                "comment_text": "{{comment_text}}",
                "comment_create_time": "1699241347",
                "comment_type": "COMMENT"
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "video_likes": 102,
                "caption": "{{caption}}",
                "thumbnail_url": "{{thumbnail_url}}",
                "commenter_display_name": "{{commenter_display_name}}",
                "comment_id": "{{comment_id}}",
                "comment_likes": 0,
                "comment_text": "{{comment_text}}",
                "comment_create_time": "1698011775",
                "comment_type": "COMMENT"
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "video_likes": 109,
                "caption": "{{caption}}",
                "thumbnail_url": "{{thumbnail_url}}",
                "commenter_display_name": "{{commenter_display_name}}",
                "comment_id": "{{comment_id}}",
                "comment_likes": 1,
                "comment_text": "{{comment_text}}",
                "comment_create_time": "1699617820",
                "comment_type": "COMMENT"
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "video_likes": 108,
                "caption": "{{caption}}",
                "thumbnail_url": "{{thumbnail_url}}",
                "commenter_display_name": "{{commenter_display_name}}",
                "comment_id": "{{comment_id}}",
                "comment_likes": 16,
                "comment_text": "{{comment_text}}",
                "comment_create_time": "1699627820",
                "comment_type": "COMMENT"
            }
        ]
    }
}
(/code)
```

