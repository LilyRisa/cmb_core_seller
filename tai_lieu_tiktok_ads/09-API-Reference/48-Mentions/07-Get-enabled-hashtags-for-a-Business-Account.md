---
title: "Get enabled hashtags for a Business Account"
doc_id: 1833832557996034
path: "API Reference / Mentions / Get enabled hashtags for a Business Account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get enabled hashtags for a Business Account

Use this endpoint to retrieve the hashtags that have been enabled for a Business Account.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/mention/hashtag/manage/list/

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
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/mention/hashtag/manage/list/?business_id={{business_id}}&username={{username}}' \
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
#| hashtag_list | object[] | The list of hashtags enabled for the Business Account (brand). |
##| hashtag | string | A hashtag enabled for the Business Account. |
##| create_date | string | The date when the hashtag was enabled for the Business Account, in the format of `YYYY-MM-DD` (UTC+0 time).<br><br>Example: `2025-01-01`. |
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

