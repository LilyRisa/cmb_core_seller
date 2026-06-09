---
title: "Enable brand hashtags for a Business Account"
doc_id: 1833831427732481
path: "API Reference / Mentions / Enable brand hashtags for a Business Account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Enable brand hashtags for a Business Account

Use this endpoint to enable hashtags for a Business Account. 

Once enabled, the hashtags will be factored into the results of [/business/mention/hashtag/video/list/](https://business-api.tiktok.com/portal/docs?id=1825108001123522).  

Business Accounts can either use hashtags returned from [/business/mention/hashtag/verify/list/](https://business-api.tiktok.com/portal/docs?id=1833862470219842) or use hashtags that include the full handle name. You can enable a maximum of 50 hashtags for a brand, with a limit of 10 hashtags per request.

> <span style="color:DodgerBlue">**Note**</span><br>
> - Once you enable  a hashtag  for a brand, you cannot remove it in the next seven days.
> - The brand mentions data for the newly enabled  hashtags will be included in [/business/mention/hashtag/video/list/](https://business-api.tiktok.com/portal/docs?id=1825108001123522) 24 hours after you enable these hashtags for a brand.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/mention/hashtag/add/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok Business Accounts. <br><br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1825105003772930#item-link-Obtain%20an%20access%20token).|
| Content-Type {Required} | string | Request message type.<br>Allowed value: `application/json`. |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|business_id {Required}|string|Application specific unique identifier for the TikTok Business Account.<br><br>You can get the ID through the `open_id` field returned by [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1825105003772930#item-link-Obtain%20an%20access%20token).|
| username {Required}  | string | The username (handle) of the Business Account. |
| hashtags{Required} | string[] | A list of hashtags to enable for the brand.<br><br>You can include hashtags from [/business/mention/hashtag/verify/list/](https://business-api.tiktok.com/portal/docs?id=1833862470219842) or include the full Business Account handle as a substring of the hashtag.<br><br>For example, if the Business Account handle is `@TikTok`, the hashtag `#drinktiktok` would be valid.<br><br>Max size: 10. |
```

### Example
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/mention/hashtag/add/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id":"{{business_id}}",
    "username":"{{username}}",
    "hashtags":["{{hashtag}}","{{hashtag}}"]
}'
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
#|hashtag_list | object[] | The list of hashtags enabled for the Business Account (brand). |
##|hashtag | string | A hashtag that has been enabled for the Business Account. |
##|create_date | string | The date when the hashtag was enabled for the brand, in the format of `YYYY-MM-DD` (UTC+0 time).<br><br>Example: `2025-01-01`. |
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

