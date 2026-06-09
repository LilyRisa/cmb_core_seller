---
title: "Get top 1000 mentioned posts"
doc_id: 1825107955294402
path: "API Reference / Mentions / Get top 1000 mentioned posts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get top 1000 mentioned posts

Use this endpoint to retrieve a list of up to 1,000 posts that mention a Business Account in the post caption. 

By default, the returned list of up to the top 1,000 posts will be sorted by the highest number of likes among all the posts that mention the business's handle. For each request, you can retrieve a maximum of 100 posts. If more results are needed, multiple requests can be made with cursor for pagination. Additionally, you have the flexibility to manually set the sorting among these returned posts using the `sort_field` and `sort_type` parameters. 

Note that the list of returned posts will only be limited to the top 1,000, even if more than 1,000 posts may exist where the business's handle is mentioned in the post caption.

To appear in the search results, a post must meet the following requirements:
- Post visibility: The post is public.
- Mention: The post explicitly mentions (@) the business's handle in the caption.
- Lookback period: The post has been posted within the past 90 days.
- User age: The post is not posted by a user who is underage or of an unknown age. For users in Europe, an age of 17 or under is considered underage, while for users in the US and other countries or regions, an age of 12 or under is considered underage.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/mention/video/list/

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
|fields|string[]|Requested fields. <br>If `fields` is not set, only the <b>default</b> fields will be returned.<br><br>Default value: `["item_id","caption"]`. <br><br><b>Supported fields</b>:<ul><li>`item_id`: Unique identifier for the post.</li><li>`create_time`: Unix/epoch date-time when the post was posted.</li><li>`video_link`: A generic URL for the post.</li><li>`caption`: The description for the post.</li><li>`likes`: Total number of likes on the post.</li><li>`comments`: Total number of comments on the post.</li><li>`shares`: Total number of times the post has been shared.</li><li>`thumbnail_url`: The URL of the post thumbnail.</li><li>`views`: Total number of views the post has received.</li><li>`reach`: Total number of unique viewers who viewed the post.</li><li>`creator_handle_name`: The handle name of the creator who published the post.</li></ul>|
|sort_field|string|The field to sort the result by.<br><br>Enum values:<ul><li>`CREATE_TIME`: The field `create_time`.</li><li>`LIKES`: The field `likes`.</li><li>`COMMENTS`: The field `comments`.</li><li>`SHARES`: The field `shares`.</li></ul>Default value: `CREATE_TIME`.|
|sort_type|string|Sorting order.<br><br>Enum values:<ul><li>`ASC`: Ascending.</li><li>`DESC`: Descending.</li></ul>Default value: `DESC`.|
|number_of_days|number|The lookback period for the results, measured in days.<br><br>Value range: 1-90.<br>Default value: 90.<br><br>For instance, if you set this field to 90, the results for the last 90 days will be retrieved.|
|regions|string[]|The regions of mentioned posts that you want to filter the results by.<br><br><ul><li>If you specify this field, only the mentioned posts published by TikTok users registered in the specified regions will be returned.</li><li>If this field is not specified, all mentioned posts will be returned.</li></ul><br>To learn about the allowed values for this field, refer to [List of values for `regions`](https://business-api.tiktok.com/portal/docs?id=1825107519114241#item-link-List%20of%20values%20for%20regions).|
|cursor|integer|Cursor for pagination. The position of the results that you want to retrieve from.<br><br>For instance, setting `cursor` to 10 means you want to retrieve the next page of results after the 10th position.<br><br>If the response parameter `has_more` is `true`, pass the returned cursor in this parameter in the subsequent request to receive the next page of results.<br><br>Default value: 0.|
|max_count|integer|The maximum number of comments to be returned for each page of data.<br><br>Default value: 100.<br>Value range: 1-100.<br><br><p><span style="color:darkred"><b>Note</b></span>: Due to our trust and safety policies, it is possible that the endpoint returns less than the `max_count` number of comments even if the response parameter `has_more` is `true`.|
```


### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/mention/video/list/?business_id={{business_id}}&fields=["item_id", "video_link", "caption", "likes", "comments", "shares", "create_time","thumbnail_url","views","reach","creator_handle_name"]&sort_field=SHARES&sort_type=DESC&number_of_days=90&regions=["US"]' \
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
#|videos|object[]|Information about the mentioned posts.|
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
#|cursor|number|Cursor for the next page of results, to be passed in the `cursor` query parameter of the subsequent API request.|
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
        "has_more": false,
        "cursor": 100,
        "videos": [
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
        ]
    }
}
(/code)
```

