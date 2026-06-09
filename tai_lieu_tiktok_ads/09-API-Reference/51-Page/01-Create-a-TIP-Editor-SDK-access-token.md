---
title: "Create a TIP Editor SDK access token"
doc_id: 1820826755283969
path: "API Reference / Page / Create a TIP Editor SDK access token"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a TIP Editor SDK access token

Use this endpoint to create a TikTok Instant Page (TIP) Editor SDK access token. 

The TIP Editor SDK access token is required for loading the TIP Editor SDK. Once the token is generated, you can learn about how to [create Instant Pages using the TIP Editor SDK](https://business-api.tiktok.com/portal/docs?id=1740307202170881).

Each TIP Editor SDK access token is valid for 24 hours. You can verify whether an SDK token expires using [/oauth2/access_token/tip_sdk/validate/](https://business-api.tiktok.com/portal/docs?id=1820826826000385) and renew the token using [/oauth2/access_token/tip_sdk/renew/](https://business-api.tiktok.com/portal/docs?id=1820826847199234) after the token expires.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/oauth2/access_token/tip_sdk/create/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
```

### Example
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/oauth2/access_token/tip_sdk/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}"
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
#| tip_sdk_access_token | string | The temporary TIP Editor SDK access token. <br><br>You can pass the token to the `ixAccessToken` parameter in the SDK function `InstantPage.start()` for invoking the TIP Editor. To learn about how to load the TIP SDK to create instant pages, see [Instant Page Editor SDK](https://business-api.tiktok.com/portal/docs?id=1740307202170881).<br><br>The token is valid for 24 hours. After the token expires, you can either renew the token using [/oauth2/access_token/tip_sdk/renew/](https://business-api.tiktok.com/portal/docs?id=1820826847199234) or create a new token using [/oauth2/access_token/tip_sdk/create/](https://business-api.tiktok.com/portal/docs?id=1820826755283969).|
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
        "tip_sdk_access_token": "{{tip_sdk_access_token}}"
    }
}
(/code)
```
