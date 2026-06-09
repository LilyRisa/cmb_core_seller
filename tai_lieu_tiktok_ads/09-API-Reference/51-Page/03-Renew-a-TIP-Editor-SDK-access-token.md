---
title: "Renew a TIP Editor SDK access token"
doc_id: 1820826847199234
path: "API Reference / Page / Renew a TIP Editor SDK access token"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Renew a TIP Editor SDK access token

Use this endpoint to renew a TikTok Instant Page (TIP) Editor SDK access token. 

Once renewed, the same token will remain valid for another 24 hours.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/oauth2/access_token/tip_sdk/renew/

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
| tip_sdk_access_token {Required} | string | A TIP Editor SDK access token.<br><br>If you want to create a new TIP Editor SDK access token, use [/oauth2/access_token/tip_sdk/create/](https://business-api.tiktok.com/portal/docs?id=1820826755283969).|
```

### Example
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/oauth2/access_token/tip_sdk/renew/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "tip_sdk_access_token": "{{tip_sdk_access_token}}"
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
