---
title: "Delete the authorization code of a TikTok post"
doc_id: 1815051817073665
path: "API Reference / Accounts / Ad authorization / Delete the authorization code of a TikTok post"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete the authorization code of a TikTok post

Use this endpoint to delete the authorization code of a TikTok post.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/post/authorize/delete/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators.<br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| business_id {Required} | string | Application specific unique identifier for the TikTok account. <br><br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) to this field.|
| item_id {Required} | string | The ID of the TikTok post.<br><br> To obtain the public posts of a TikTok account, use [/business/video/list/](https://business-api.tiktok.com/portal/docs?id=1762228421622786). <br><br> Example: `7123456789123456789`.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the TikTok post doesn't have an authorization code, either because the code is not generated or is deleted, an error will occur.  |
```

### Example

```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/post/authorize/delete/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "item_id": "{{item_id}}"
}'
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
#| item_id | string | The ID of the TikTok post. <br><br> Example: `7123456789123456789`. |
#| auth_code_status | string | The status of the authorization code. <br><br>Enum value: <ul><li>`DELETED`: The authorization code has been deleted.</li></ul>|
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
        "auth_code_status": "DELETED",
        "item_id": "{{item_id}}"
    }
}
(/code)
```
