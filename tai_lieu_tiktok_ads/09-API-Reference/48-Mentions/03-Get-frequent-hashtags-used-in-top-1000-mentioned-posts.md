---
title: "Get frequent hashtags used in top 1000 mentioned posts"
doc_id: 1825107978583042
path: "API Reference / Mentions / Get frequent hashtags used in top 1000 mentioned posts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get frequent hashtags used in top 1000 mentioned posts

Use this endpoint to retrieve the top 20 hashtags in the captions of mentioned posts. 

To return in the search results, the hashtag must meet the following requirements:
- Account type: The TikTok account is a Business Account.
- Post: The hashtag appears in a post that is public, explicitly mentions (@) the business in the caption, and has been posted within the past 90 days. Additionally, the user who posted the post is not underage or of an unknown age. For users in Europe, an age of 17 or under is considered underage, while for users in the US and other countries or regions, an age of 12 or under is considered underage.
- Frequency: The hashtag is one of the top 20 most frequently used hashtags in the captions of mentioned posts. 


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/mention/top_hashtag/list/

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
|regions|string[]|The regions of mentioned posts that you want to filter the results by.<br><br><ul><li>If you specify this field, only the top hashtags in mentioned posts posted by TikTok users registered in the specified regions will be returned.</li><li>If this field is not specified, the top hashtags in all mentioned posts will be returned.</li></ul><br>To learn about the allowed values for this field, refer to [List of values for `regions`](https://business-api.tiktok.com/portal/docs?id=1825107519114241#item-link-List%20of%20values%20for%20regions).|
```


### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/mention/top_hashtag/list/?business_id={{business_id}}&regions=["US"]' \
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
#|results| object[] |The results of the top 20 hashtags in the mentioned posts.|
##|hashtag| string |A hashtag that appears frequently in the captions of mentioned posts.<br><br>Example: `fashion`.|
##|count| number |The frequency of the hashtag.|
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
        "results": [
            {
                "count": 72,
                "hashtag": "{{hashtag}}"
            },
            {
                "count": 31,
                "hashtag": "{{hashtag}}"
            },
            {
                "count": 24,
                "hashtag": "{{hashtag}}"
            },
            {
                "count": 17,
                "hashtag": "{{hashtag}}"
            },
            {
                "count": 13,
                "hashtag": "{{hashtag}}"
            }
        ]
    }
}
(/code)
```

