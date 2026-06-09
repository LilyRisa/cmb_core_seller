---
title: "Revoke a long-term access token"
doc_id: 1739965949088770
path: "API Reference / Authentication / Revoke a long-term access token"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Revoke a long-term access token

Use this endpoint to revoke a long-term access token. This is a new endpoint in TikTok API for Business v1.3. 

A long-term access token is a type of token that you can obtain after receiving authorization from an ad account, a Business Center account, or a TikTok One Creator Marketplace account. The token does not expire. 


## Request 
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/oauth2/revoke_token/ 

**Method** POST

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
|app_id{Required}|string|ID of your developer app.<br><br> To find the <b>App ID</b>, navigate to [My Apps](https://ads.tiktok.com/marketing_api/apps/) > <b>App Detail</b> > <b>Basic Information</b>.|
|secret{Required}|string|Secret of your developer app. <br><br>To find the <b>Secret</b>, navigate to [My Apps](https://ads.tiktok.com/marketing_api/apps/) > <b>App Detail</b> > <b>Basic Information</b>. |
|access_token{Required}|string|The long-term access token that you want to revoke.<br><br>You can create a new access token via [/oauth2/access_token/](https://ads.tiktok.com/marketing_api/docs?id=1739965703387137#item-link-/oauth2/access_token/) or [/oauth/token/](https://ads.tiktok.com/marketing_api/docs?id=1739965703387137#item-link-/oauth/token/).|
```

### Example
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/oauth2/revoke_token/' \
--header 'Access-Token: {{access_token}}' \
--header 'Content-Type: application/json' \
--data '{
    "app_id": "{{app_id}}",
    "secret": "{{secret}}",
    "access_token": "{{access_token}}"
}'
(/code)
```

## Response
```xtable
|Field|Type |Description|
|---|---|---|
|code |number|Return code, see [Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097) |
|message |string|Returned messages, see [Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097) |
|request_id |string| The log ID of a request, which uniquely identifies the request.|
|data |object|Return data.  |
#|app_id |string|ID of your developer app.|
#|advertiser_ids |string[]| The ID list of ad accounts that the token can no longer access.|
```

### Example
```xcodeblock
(code)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "advertiser_ids": [
            "{{advertiser_ids}}",
            "{{advertiser_ids}}"
        ],
        "app_id": "{{app_id}}"
    }
}
(/code);
```

