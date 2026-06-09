---
title: "Delete an enabled brand hashtag for a Business Account"
doc_id: 1833833401871362
path: "API Reference / Mentions / Delete an enabled brand hashtag for a Business Account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete an enabled brand hashtag for a Business Account

Use this endpoint to delete enabled hashtags for a Business Account.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/mention/hashtag/remove/

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
| hashtag {Required}  | string | A hashtag to remove.<br><br>To obtain a list of existing enabled hashtags for the brand, use [/business/mention/hashtag/manage/list/](https://business-api.tiktok.com/portal/docs?id=1833832557996034).<br><br><p><span style="color:darkred"><b>Note</b></span>: You can only delete hashtags that were enabled at least seven days ago. To ensure this, check the returned `create_date` for the hashtags from [/business/mention/hashtag/manage/list/](https://business-api.tiktok.com/portal/docs?id=1833832557996034). |
```

### Example
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/mention/hashtag/remove/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id":"{{business_id}}",
    "username":"{{username}}",
    "hashtag":"{{hashtag}}"
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
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```




