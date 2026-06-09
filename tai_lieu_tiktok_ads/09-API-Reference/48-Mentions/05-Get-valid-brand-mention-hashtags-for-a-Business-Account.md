---
title: "Get valid brand mention hashtags for a Business Account"
doc_id: 1833862470219842
path: "API Reference / Mentions / Get valid brand mention hashtags for a Business Account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get valid brand mention hashtags for a Business Account

Use this endpoint to retrieve a list of valid hashtags that you can enable for a Business Account. The results will include up to 100 top hashtags related to the Business Account.  

To be valid for a Business Account Hashtag, the Hashtag must meet the following requirements:
- The hashtag is used in at least 3 post captions, published either by the Business Account or in a post that mentions the Business Account Handle.

You can enable valid hashtags for a Business Account using [/business/mention/hashtag/add/](https://business-api.tiktok.com/portal/docs?id=1833831427732481). Once enabled, the hashtags will be factored into the results of [/business/mention/hashtag/video/list/](https://business-api.tiktok.com/portal/docs?id=1825108001123522). 

> <span style="color:DodgerBlue">**Note**</span><br>
This endpoint is designed for accounts with substantial organic mention activity. Test accounts or newly created accounts may not return valid hashtags and should not be relied on for validation. For accurate testing results, you need to use a production account that receives real organic mentions.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/mention/hashtag/verify/list/

**Method** GET

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
| username {Required}  | string | The username (handle) of the Business Account. |
```

### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/mention/hashtag/verify/list/?business_id={{business_id}}&username={{username}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| hashtag_list | object[] | The list of valid hashtags that you can enable for the Business Account.|
##| hashtag | string | A valid hashtag that you can enable for the Business Account.|
##| post_count | number | The total number of posts containing the hashtag in the post caption in the last 90 days.|
##| like_count | number | The total number of likes on posts containing the hashtag in the post caption in the last 90 days.|
##| mention_post_rank | number | The rank of the hashtag among all the valid hashtags for the Business Account.|
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
        "hashtag_list": [
            {
                "create_date": "2025-05-21",
                "hashtag": "{{hashtag}}"
            },
            {
                "create_date": "2025-05-21",
                "hashtag": "{{hashtag}}"
            }
        ]
    }
}
(/code)
```






