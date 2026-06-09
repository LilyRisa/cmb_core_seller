---
title: "Revoke a short-term access token"
doc_id: 1833997665030145
path: "API Reference / Authentication / Revoke a short-term access token"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Revoke a short-term access token

Use this endpoint to revoke a short-term access token.

A short-term access token is a type of token that you can obtain after receiving authorization from a TikTok account. The token expires in one day.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tt_user/oauth2/revoke/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| client_id {Required} | string | ID of your developer application. <br><br>To find the <strong>App ID</strong>, navigate to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
| client_secret {Required} | string | Secret of your developer application.<br><br> To find the <strong>Secret</strong>, navigate to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
| access_token {Required} | string | The TikTok account access token that you want to revoke.<br><br> You can either create a new access token via [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) or obtain a renewed access token via [/tt_user/oauth2/refresh_token/](https://business-api.tiktok.com/portal/docs?id=1833997679342594). |
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tt_user/oauth2/revoke/' \
--header 'Content-Type: application/json' \
--data '{
    "client_id":"{{client_id}}",
    "client_secret":"{{client_secret}}",
    "access_token":"{{access_token}}"
}'
(/code)
```

## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
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
