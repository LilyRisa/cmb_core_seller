---
title: "Appeal rejection of an Upgraded Smart+ Ad"
doc_id: 1843317522553106
path: "API Reference / Upgraded Smart+ / Ad Review / Appeal rejection of an Upgraded Smart+ Ad"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Appeal rejection of an Upgraded Smart+ Ad

Use this endpoint to appeal the rejection of an Upgraded Smart+ Ad, which allows you to request for the ad to be re-evaluated when it has been rejected during the review process.

After submitting your appeal, you need to wait for some time and then call the [/smart_plus/ad/review_info/](https://business-api.tiktok.com/portal/docs?id=1843317465695233) endpoint to check the appeal status and see if the review status has been updated.

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/appeal/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed value: `application/json`.|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|smart_plus_ad_id {Required}|string|Ad ID.<br><br>To obtain ad IDs, use [/smart_plus/ad/get/](https://business-api.tiktok.com/portal/docs?id=1843317378982914).|
|appeal_reason|string|The reason for the appeal.|
|attachment_list|string[]|List of attachment links.<br><br>Max size: 100.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/appeal/' \
--header 'Access-Token: &#123;&#123;Access-Token&#125;&#125;' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "&#123;&#123;advertiser_id&#125;&#125;",
    "smart_plus_ad_id": "&#123;&#123;smart_plus_ad_id&#125;&#125;"
}'
(/code)
```

## Response
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code|number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data.<br><br>For a successful request, this field will be an empty object (`&#123;&#125;`).|
```

### Example
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```

