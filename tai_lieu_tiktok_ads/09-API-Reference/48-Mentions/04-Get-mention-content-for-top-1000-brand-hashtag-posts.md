---
title: "Get mention content for top 1000 brand hashtag posts"
doc_id: 1825108001123522
path: "API Reference / Mentions / Get mention content for top 1000 brand hashtag posts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get mention content for top 1000 brand hashtag posts

Use this endpoint to retrieve up to 1,000 posts containing one or more brand hashtags, hashtags you’ve enabled for your business (up to 50, managed via [/business/mention/hashtag/verify/list/](https://business-api.tiktok.com/portal/docs?id=1833862470219842) and [/business/mention/hashtag/add/](https://business-api.tiktok.com/portal/docs?id=1833831427732481)). If more than 1,000 posts contain brand hashtags, only the 1,000 posts with the most likes are returned. You can use this endpoint either to get the top 1,000 posts across all your enabled hashtags combined, or to get the top 1,000 posts for a specific enabled hashtag.

To appear in the search results, the post must meet the following requirements:
- Post visibility: The post is public.
- Brand hashtag: The post caption contains one or more brand hashtags. 
- Lookback period: The post has been posted within the past 90 days.
- User age: The post is not posted by a user who is underage or of an unknown age. For users in Europe, an age of 17 or under is considered underage, while for users in the US and other countries or regions, an age of 12 or under is considered underage.


## Request

**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/business/mention/hashtag/video/list/

**Method**  GET

**Required permission scopes**: `biz.brand.insights`

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok Business Accounts. <br><br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1825105003772930#item-link-Obtain%20an%20access%20token).|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|business_id {Required}|string|Application specific unique identifier for the TikTok Business Account.<br><br>You can get the ID through the `open_id` field returned by [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1815693478150146).|
|hashtag|string|The hashtag for which to retrieve the top 1,000 posts over the last 90 days.<br>Specify one hashtag from the Business Account's enabled hashtags.<br><br>If this field is omitted, the response will include the top posts across all of the Business Account's enabled hashtags.<br><br>To retrieve the list of hashtags enabled for the Business Account, use [/business/mention/hashtag/manage/list/](https://business-api.tiktok.com/portal/docs?id=1833832557996034).<br><br><p><span style="color:darkred"><b>Note</b></span>: The hashtag is case-sensitive and requires an exact match with an enabled hashtag for the Business Account.|
|fields|string[]|Requested fields. If `fields` is not set, only the <b>default</b> fields will be returned.<br><br>Default value: `["item_id","caption"]`.<br><br><b>Supported fields</b>:<ul><li>`item_id`: Unique identifier for the post.</li><li>`create_time`: Unix/epoch date-time when the post was posted.</li><li>`video_link`: A generic URL for the post.</li><li>`caption`: The description for the post.</li><li>`likes`: Total number of likes on the post.</li><li>`comments`: Total number of comments on the post.</li><li>`shares`: Total number of times the post has been shared.</li><li>`thumbnail_url`: The URL of the post thumbnail.</li><li>`matched_hashtags`: A list of brand hashtags in the post caption that match the hashtags configured for the business.</li></ul>|
|sort_field|string|The field to sort the result by.<br><br>Enum values:<ul><li>`CREATE_TIME`: The field `create_time`.</li><li>`LIKES`: The field `likes`.</li><li>`COMMENTS`: The field `comments`.</li><li>`SHARES`: The field `shares`.</li></ul>Default value: `CREATE_TIME`.|
|sort_order|string|Sorting order.<br><br>Enum values:<ul><li>`ASC`: Ascending.</li><li>`DESC`: Descending.</li></ul>Default value: `DESC`.|
|number_of_days|number|The lookback period for the results, measured in days.<br><br>Value range: 1-90.<br>Default value: 90.<br><br>For instance, if you set this field to 90, the results for the last 90 days will be retrieved.|
|regions|string[]|The regions of posts that you want to filter the results by.<br><br><ul><li>If you specify this field, only posts posted by TikTok users registered in the specified regions will be returned.</li><li>If this field is not specified, all relevant posts that contain brand hashtags will be returned.</li></ul><br>To learn about the allowed values for this field, refer to [List of values for `regions`](https://business-api.tiktok.com/portal/docs?id=1825107519114241#item-link-List%20of%20values%20for%20regions).|
|cursor|number|Cursor for pagination. The position of the results that you want to retrieve from.<br><br>For instance, setting `cursor` to 10 means you want to retrieve the next page of results after the 10th position.<br><br>If the response parameter `has_more` is `true`, pass the returned cursor in this parameter in the subsequent request to receive the next page of results.<br><br>Default value: 0.|
|max_count|number|The maximum number of hashtags that will be returned for each page of data.<br><br>Default value: 10.<br><br><span style="color:darkred"><b>Note</b></span>: Due to our trust and safety policies, it is possible that the endpoint returns less than the `max_count` number of hashtags even if the response parameter `has_more` is `true`.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/mention/hashtag/video/list/?business_id={{business_id}}&fields=["item_id","video_link","caption","likes","comments","shares","create_time","thumbnail_url","matched_hashtags"]&sort_field=LIKES&sort_order=ASC' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|videos|object[]|Information about the posts.|
##|item_id|string|Unique identifier for the post.<br><br>Example: `6995316737801538821`.|
##|create_time|string|Unix/epoch date-time when the post was posted, in UTC timezone.<br><br>Example: `1628461703`.|
##|video_link|string|The generic URL of the post, in the format of `https://www.tiktok.com/@/video/&#123;item_id&#125;`.<br><br>Example: `https://www.tiktok.com/@/video/7231234123412341234`.|
##|caption|string|Post description.<br><br>Example: `Nuki: me and me casa 🏠 #cat #kitten #britishshorthair`.|
##|likes|number|Total number of likes on the post.<br><br>Example: 243.|
##|comments|number|Total number of comments on the post.<br><br>Example: 10.|
##|shares|number|Total number of times the post has been shared.<br><br>Example: 10.|
##|thumbnail_url|string|The URL of the post thumbnail.<br><br>Validity period: 48 hours.<br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: `https://p16-sign-sg.tiktokcdn.com/tos-maliva-p-0068/1f9a445423844b82bd79317b42b9953f~c5_300x400.webp?x-expires=1614034800&x-signature=qChosOGafzBcTctPm9zYiF8Y2MA%3D`.|
##|matched_hashtags|string[]|A list of brand hashtags in the post caption that match the hashtags configured for the business.<br><br>Example: `[\"tiktok\", \"tiktokrunning\"]`.<br><br><span style="color:darkred"><b>Note</b></span>: If a specific hashtag is passed in the request, this field will return an empty array `[]`, as the response is filtered to only include posts for the requested hashtag.|
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
        "cursor": 10,
        "has_more": true,
        "videos": [
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "comments": 0,
                "caption": "{{caption}}",
                "create_time": "1699537820",
                "shares": 1,
                "thumbnail_url": "{{thumbnail_url}}",
                "matched_hashtags": [
                    "{{matched_hashtag}}",
                    "{{matched_hashtag}}"
                ],
                "likes": 132
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "comments": 3,
                "caption": "{{caption}}",
                "create_time": "1699617820",
                "shares": 0,
                "thumbnail_url": "{{thumbnail_url}}",
                "matched_hashtags": [
                    "{{matched_hashtag}}",
                    "{{matched_hashtag}}"
                ],
                "likes": 132
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "comments": 180,
                "caption": "{{caption}}",
                "create_time": "1694736000",
                "shares": 200,
                "thumbnail_url": "{{thumbnail_url}}",
                "matched_hashtags": [
                    "{{matched_hashtag}}",
                    "{{matched_hashtag}}"
                ],
                "likes": 170
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "comments": 10,
                "caption": "{{caption}}",
                "create_time": "1699627820",
                "shares": 4,
                "thumbnail_url": "{{thumbnail_url}}",
                "matched_hashtags": [
                    "{{matched_hashtag}}",
                    "{{matched_hashtag}}"
                ],
                "likes": 460
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "comments": 10,
                "caption": "{{caption}}",
                "create_time": "1699241347",
                "shares": 5,
                "thumbnail_url": "{{thumbnail_url}}",
                "matched_hashtags": [
                    "{{matched_hashtag}}",
                    "{{matched_hashtag}}"
                ],
                "likes": 1161
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "comments": 14,
                "caption": "{{caption}}",
                "create_time": "1695495795",
                "shares": 1380,
                "thumbnail_url": "{{thumbnail_url}}",
                "matched_hashtags": [
                    "{{matched_hashtag}}",
                    "{{matched_hashtag}}"
                ],
                "likes": 1396
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "comments": 20,
                "caption": "{{caption}}",
                "create_time": "1696097179",
                "shares": 5,
                "thumbnail_url": "{{thumbnail_url}}",
                "matched_hashtags": [
                    "{{matched_hashtag}}",
                    "{{matched_hashtag}}"
                ],
                "likes": 1422
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "comments": 261,
                "caption": "{{caption}}",
                "create_time": "1698616575",
                "shares": 134,
                "thumbnail_url": "{{thumbnail_url}}",
                "matched_hashtags": [
                    "{{matched_hashtag}}",
                    "{{matched_hashtag}}"
                ],
                "likes": 1702
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "comments": 10,
                "caption": "{{caption}}",
                "create_time": "1699637820",
                "shares": 83,
                "thumbnail_url": "{{thumbnail_url}}",
                "matched_hashtags": [
                    "{{matched_hashtag}}",
                    "{{matched_hashtag}}"
                ],
                "likes": 2049
            },
            {
                "video_link": "{{video_link}}",
                "item_id": "{{item_id}}",
                "comments": 37,
                "caption": "{{caption}}",
                "create_time": "1697493375",
                "shares": 440,
                "thumbnail_url": "{{thumbnail_url}}",
                "matched_hashtags": [
                    "{{matched_hashtag}}",
                    "{{matched_hashtag}}"
                ],
                "likes": 3492
            }
        ]
    }
}
(/code)
```

