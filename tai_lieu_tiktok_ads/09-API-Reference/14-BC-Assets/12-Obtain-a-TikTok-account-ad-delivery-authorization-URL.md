---
title: "Obtain a TikTok account ad delivery authorization URL"
doc_id: 1846868897541122
path: "API Reference / BC Assets / Obtain a TikTok account ad delivery authorization URL"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Obtain a TikTok account ad delivery authorization URL

Use this endpoint to generate an authorization URL for requesting ad delivery permissions from a TikTok account. 

Once you obtain the authorization URL, generate a QR code from it using a QR code generator and share the QR code with a TikTok account owner. When the TikTok account owner scans the QR code, they can grant the <b>"Existing posts"</b> permissions for Spark Ads Pull and the <b>"TikTok posts"</b> permissions for Spark Ads Push to the Business Center.

## Before you start
Ensure you are an Admin of the Business Center. To confirm your permission to a Business Center, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826) and confirm whether the returned `user_role` is `ADMIN`.

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/asset/account/authorization/

**Method** POST

**Header**

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token.<br><br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br><br>Allowed value: `application/json`.|
```

**Parameters**

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|bc_id {Required}|string|Business Center ID.<br><br>To obtain the list of Business Centers that you can access, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826).|
|is_ads_only_mode|boolean|Whether to enable "Show through ads only" permission.<br><br>You can enable this permission to prevent members from publishing posts that appear on the TikTok account profile. <br>Once you enable this permission and receive approval for ad delivery permission from the TikTok account owner, videos published through Spark Ads PUSH by members who are assigned the TikTok account will get paid traffic (formerly "ads-only mode") and are hidden from organic traffic. <br><br>Supported values: `true`, `false`.<br>Default value: `false`.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/bc/asset/account/authorization/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "is_ads_only_mode": true
}'
(/code)
```

## Response
```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code|number|Response code.<br><br>For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|message|string|Response message.<br><br>For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data.|
#|bc_auth_qr_code|string|The TikTok account ad delivery authorization URL. <br><br><b>Once you obtain the authorization URL, generate a QR code from it using a QR code generator</b> and share the QR code with a TikTok account owner. When the TikTok account owner scans the QR code, they can grant the <b>"Existing posts"</b> permissions for Spark Ads Pull and the <b>"TikTok posts"</b> permissions for Spark Ads Push to the Business Center.|
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
        "bc_auth_qr_code": "{{bc_auth_qr_code}}"
    }
}
(/code)
```
