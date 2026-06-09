---
title: "Renew a short-term access token"
doc_id: 1833997679342594
path: "API Reference / Authentication / Renew a short-term access token"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Renew a short-term access token

Use this endpoint to renew a short-term access token by `refresh_token`.

A short-term access token is a type of token that you can obtain after receiving authorization from a TikTok account. The token expires in one day.

## Before you start
Obtain a refresh token for generating a new short-term token using [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041).

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tt_user/oauth2/refresh_token/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| client_id {Required}  | string | ID of your developer app.<br><br> To find the <b>App ID</b>, navigate to [My Apps](https://ads.tiktok.com/marketing_api/apps/) > <b>App Detail</b> > <b>Basic Information</b>.|
| client_secret {Required}  | string | Secret of your developer app. <br><br>To find the <b>Secret</b>, navigate to [My Apps](https://ads.tiktok.com/marketing_api/apps/) > <b>App Detail</b> > <b>Basic Information</b>. |
| grant_type {Required} | string | The way of generating the access token.<br><br>The allowed enum value: <ul><li>`refresh_token`: To generate the access token by refresh token.</li></ul>|
| refresh_token {Required} | string | Refresh token to renew an existing access token.<br><br>You can obtain the refresh token in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) endpoint when you generate an access token. |
```


### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tt_user/oauth2/refresh_token/' \
--header 'Content-Type: application/json' \
--data '{
    "client_id":"{{client_id}}",
    "client_secret":"{{client_secret}}",
    "grant_type":"refresh_token",
    "refresh_token":"{{refresh_token}}"
}'
(/code)
```

## Response

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data|object|Returned data.|
|request_id|string| The log ID of the request, which uniquely identifies a request.|
#|access_token | string | The new short-term token for permission verification. <br><br>The token expires in one day. |
#|token_type | string | The type of token.<br><br>Enum value: <ul><li>`Bearer`: Bearer token. Anyone in possession of the token can use it to access the associated resources.</li></ul> |
#|scope | string | The scope of permissions granted to the access token. |
#|expires_in | number | The time remaining until the access token expires, measured in seconds. |
#|refresh_token | string | The refresh token, which can be used to renew the access token.<br><br> The refresh token is valid for one year.<br><br><p><span style="color:darkred"><b>Note</b></span>: Retain this field for `access_token` renewal. |
#|refresh_token_expires_in | number | The time remaining until the refresh token expires, measured in seconds. |
#|open_id | string | Application specific unique ID of the TikTok account. |
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
        "access_token": "{{access_token}}",
        "expires_in": 86400,
        "open_id": "{{open_id}}",
        "refresh_token": "{{refresh_token}}",
        "refresh_token_expires_in": 28484364,
        "scope": "message.list.manage,video.list,video.insights,user.insights,user.info.basic,comment.list,biz.brand.insights,biz.spark.auth,tcm.order.update,comment.list.manage,video.upload,video.publish,biz.ads.recommend,user.account.type,biz.creator.info,user.info.username,message.list.send,user.info.profile,user.info.stats,message.list.read,biz.creator.insights",
        "token_type": "Bearer"
    }
}
(/code)
```
