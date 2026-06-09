---
title: "Authentication"
doc_id: 1825105003772930
path: "Organic API / Mentions API / Guides / Get started / Authentication"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Authentication

With the authorization code you receive after authorization by the TikTok Business Account user (the business), you can make a request to the following endpoint to get an `access_token` for subsequent API requests and a `refresh_token` for access token renewal.

To get the TikTok Business Account permission scopes that are authorized by the business, you can pass the generated access token to [/tt_user/token_info/get/](https://business-api.tiktok.com/portal/docs?id=1765927978092545).

**Access token expiry**

The `access_token` is valid for one day. Once an access token has expired, you need to make a request to this endpoint to renew the access token. Remember to pass in the `refresh_token` for a token renewal request.

**Refresh token expiry**

The `refresh_token` is valid for one year. If the refresh token expires, the developer needs to request the user to re-authorize their application via the user authorization workflow.

To get or renew the TikTok account access token, you can use <a href="#item-link-Obtain an access token">/tt_user/oauth2/token/</a> and <a href="#item-link-Renew an access token">/tt_user/oauth2/refresh_token/</a> respectively. Note that these endpoints require `redirect_uri` for better security. 

# Obtain an access token
Use this endpoint to obtain an `access_token`.


## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/tt_user/oauth2/token/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Content-Type {Required}|string|Request message type<br>Allowed format: `"application/json"`  |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|client_id{Required}|string| ID of your developer application (App ID). <br><br>To obtain the <strong>App ID</strong>, navigate to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
|client_secret{Required}|string|Secret of your developer application. <br><br> To obtain the <strong>Secret</strong>, navigate to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
|grant_type{Required}|string|The way of generating the access token. <br><br> The allowed enum value: <ul><li>`authorization_code`: To generate the access token by authorization code.</li></ul>|
|auth_code{Required}|string|The authorization code in the callback URL. An authorization code is appened to the redirect URL after the business has logged into TikTok and approves your request for permissions.<br><br>To find out how to get the authorization code, see [Authorization](https://business-api.tiktok.com/portal/docs?id=1825103730490434).|
|redirect_uri{Required}|string|The redirect URL which the business will be directed to. Its value must be the same as the <strong>TikTok account holder redirect URL</strong> specified in your developer App.<br><br> To obtain the <strong>TikTok account holder redirect URL</strong>, navigate to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tt_user/oauth2/token/' \
--header 'Content-Type: application/json' \
--data '{
    "client_id":"{{client_id}}",
    "client_secret":"{{client_secret}}",
    "grant_type":"authorization_code",
    "auth_code":"{{auth_code}}",
    "redirect_uri":"{{redirect_uri}}"
}'
(/code)
```


## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| The log id of the request, which uniquely identifies a request.|
|data|object|Returned data.|
#|access_token|string|Business access token to call [Mention endpoints](https://business-api.tiktok.com/portal/docs?id=1825107837430850).  |
#|token_type|string|Token type. <br><br>Enum value: <ul><li>`Bearer`: Bearer token. Anyone in possession of the token can use it to access the associated resources.</li></ul>|
#|scope|string|Scope of permissions that the application has access to.|
#|expires_in|number|How long until access token expires, in seconds.|
#|refresh_token|string|Refresh token to renew access token.<br><br><p><span style="color:darkred"><b>Note</b></span>: Maintain this field for `access_token` renewal.|
#|refresh_token_expires_in|number|How long until refresh token expires, in seconds.|
#|open_id|string|Application specific unique ID of the TikTok account.|
```


### Example
```xcodeblock
(code curl http)
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
        "refresh_token_expires_in": 31536000,
        "scope": "message.list.manage,video.list,video.insights,user.insights,user.info.basic,comment.list,biz.brand.insights,biz.spark.auth,tcm.order.update,comment.list.manage,video.upload,video.publish,biz.ads.recommend,user.account.type,biz.creator.info,user.info.username,message.list.send,user.info.profile,user.info.stats,message.list.read,biz.creator.insights",
        "token_type": "Bearer"
    }
}
(/code)
```

# Renew an access token
Use this endpoint to renew an access token by `refresh_token`. 

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/tt_user/oauth2/refresh_token/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Content-Type {Required}|string|Request message type<br>Allowed format: `"application/json"`  |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|client_id{Required}|string| ID of your developer application (App ID). <br><br>To obtain the <strong>App ID</strong>, navigate to <strong>App ID</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
|client_secret{Required}|string|Secret of your developer application. <br><br> To obtain the <strong>Secret</strong>, navigate to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
|grant_type{Required}|string|The way of generating the access token. <br><br> The allowed enum value: <ul><li>`refresh_token`:  To generate the access token by refresh token.</li></ul>|
|refresh_token{Required}|string|Refresh token to renew an existing access token. <br><br>You can find the token in the response of <a href="#item-link-Obtain an access token">/tt_user/oauth2/token/</a> when you generate an access token.|
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
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data|object|Returned data.|
#|access_token|string|Business access token to call [Mention endpoints](https://business-api.tiktok.com/portal/docs?id=1825107837430850).  |
#|token_type|string|Token type. <br><br>Enum value: <ul><li>`Bearer`: Bearer token. Anyone in possession of the token can use it to access the associated resources.</li></ul>|
#|scope|string|Scope of permissions that the application has access to.|
#|expires_in|number|How long until access token expires, in seconds.|
#|refresh_token|string|Refresh token to renew access token.<br><br><p><span style="color:darkred"><b>Note</b></span>: Maintain this field for `access_token` renewal.|
#|refresh_token_expires_in|number|How long until refresh token expires, in seconds.|
#|open_id|string|Application specific unique ID of the TikTok account.|
```


### Example
```xcodeblock
(code curl http)
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


