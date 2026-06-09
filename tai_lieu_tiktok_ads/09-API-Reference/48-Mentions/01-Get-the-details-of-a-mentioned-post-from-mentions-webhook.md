---
title: "Get the details of a mentioned post from mentions webhook"
doc_id: 1831359360477185
path: "API Reference / Mentions / Get the details of a mentioned post from mentions webhook"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the details of a mentioned post from mentions webhook

Use this endpoint to retrieve the details of a mentioned post obtained from [the mentions webhook](https://business-api.tiktok.com/portal/docs?id=1825113658431489), enabling real-time monitoring of business mentions within post captions. A mentioned post refers to any post that mentions a business in the post caption.  

> <span style="color:DodgerBlue">**Note**</span><br>
> Retrieve the details within 48 hours of receiving the webhook to ensure data availability.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/mention/video/get/

**Method** GET

**Required permission scope**: `biz.brand.insights`

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
|item_id{Required}|string|The ID of the post on which the comment was published.<br><br>Pass the post ID as obtained from `video_id` within the [`content` of the mentions webhook event](https://business-api.tiktok.com/portal/docs?id=1825113658431489#item-link-Event%20content).|
|fields|string[]|Requested fields. <br>If `fields` is not set, only the <b>default</b> fields will be returned.<br><br>Default value: `["item_id","caption"]`. <br><br><b>Supported fields</b>:<ul><li>`item_id`: Unique identifier for the post.</li><li>`create_time`: Unix/epoch date-time when the post was posted.</li><li>`video_link`: A generic URL for the post.</li><li>`caption`: The description for the post.</li><li>`likes`: Total number of likes on the post.</li><li>`comments`: Total number of comments on the post.</li><li>`shares`: Total number of times the post has been shared.</li><li>`thumbnail_url`: The URL of the post thumbnail.</li><li>`views`: Total number of views the post has received.</li><li>`reach`: Total number of unique viewers who viewed the post.</li><li>`creator_handle_name`: The handle name of the creator who published the post.</li></ul>|
```


### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/mention/video/get/?business_id={{business_id}}&fields=["item_id", "video_link", "caption", "likes", "comments", "shares", "create_time","thumbnail_url","views","reach","creator_handle_name"]' \
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
|data|object|Returned data.|
#|video|object[]|Information about the mentioned post.|
##|item_id|string|Unique identifier for the post.<br><br>Example: `6995316737801538821`.|
##|create_time|string|Unix/epoch date-time when the post was posted, in UTC timezone.<br><br>Example: `1628461703`.|
##|video_link|string|The generic URL of the post, in the format of `https://www.tiktok.com/@/video/&#123;item_id&#125;`.<br><br>Example: `https://www.tiktok.com/@/video/7231234123412341234`.|
##|caption|string|Post description.<br><br>Example: `Nuki: me and me casa 🏠 #cat #kitten #britishshorthair`.|
##|likes|number|Total number of likes on the post.<br><br>Example: 243.|
##|comments|number|Total number of comments on the post.<br><br>Example: 10.|
##|shares|number|Total number of times the post has been shared.<br><br>Example: 10.|
##|thumbnail_url|string|The URL of the post thumbnail.<br><br>Validity period: 48 hours.<br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: `https://p16-sign-sg.tiktokcdn.com/tos-maliva-p-0068/1f9a445423844b82bd79317b42b9953f~c5_300x400.webp?x-expires=1614034800&x-signature=qChosOGafzBcTctPm9zYiF8Y2MA%3D`.<br><br><p><span style="color:darkred"><b>Note</b></span>: For TikTok photo posts, the value of this field will be an empty string (`""`).|
##|creator_handle_name|string|The handle name of the creator who published the post.|
##|views|number|Total number of views the post has received.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the number of views the post has received is less than 1,000, the value of this field will be null.|
##|reach|number|Total number of unique viewers who viewed the post.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the number of unique viewers who viewed the post is less than 1,000, the value of this field will be null.|
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
        "video": 
            {
                "video_link": "{{video_link}}",
                "likes": 50,
                "item_id": "{{item_id}}",
                "create_time": "1689481495",
                "shares": 253,
                "comments": 101,
                "caption": "{{caption}}",
                "thumbnail_url": "{{thumbnail_url}}",
                "views": 2000,
                "reach": 2000,
                "creator_handle_name": "{{creator_handle_name}}"
            },
            {
                "video_link": "{{video_link}}",
                "likes": 100,
                "item_id": "{{item_id}}",
                "create_time": "1689481495",
                "shares": 53,
                "comments": 151,
                "caption": "{{caption}}",
                "thumbnail_url": "{{thumbnail_url}}",
                "views": null,
                "reach": null,
                "creator_handle_name": "{{creator_handle_name}}"
            }
    }
}
(/code)
```

