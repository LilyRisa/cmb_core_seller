---
title: "Disable or enable creatives in an Upgraded Smart+ Ad"
doc_id: 1843317433577601
path: "API Reference / Upgraded Smart+ / Ads / Disable or enable creatives in an Upgraded Smart+ Ad"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Disable or enable creatives in an Upgraded Smart+ Ad

Use this endpoint to disable or enable one or more creatives in an Upgraded Smart+ Ad.

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/material_status/update/

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
|ad_material_ids {Required}|string[]|A list of ad material IDs for creatives to operate on. <br>An ad material ID is an ad-specific material ID generated when a particular creative is used in an ad. <br>This ID differs from the creative ID you receive when uploading the creative to your ad account’s Creative Library.<br><br>To obtain ad material IDs associated with an ad, use [/smart_plus/ad/get/](https://business-api.tiktok.com/portal/docs?id=1843317378982914).<br><br>Max size: 20.<br><br>You can operate on the following creative types:<ul><li>Carousels from Standard Carousel Spark Ads (via Spark Ads Push).</li><li>Carousels from non-Spark Ad Standard Carousels.</li><li>Videos from non-Spark Ads.</li><li>Videos from Spark Ads (via Spark Ads Push).</li><li>TikTok video posts used as Spark Ad Videos (via Spark Ads Pull).</li><li>TikTok photo posts used as Standard Carousel Spark Ads (via Spark Ads Pull).</li></ul><br><font color="darkred"><b>Note</b></font>: You cannot mix carousel and non-carousel creative types in the same request.|
|operation_status {Required}|string|The operation to perform on the creatives.<br><br>Enum values: `DISABLE` (pause), `ENABLE` (enable).|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/material_status/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "smart_plus_ad_id":"{{smart_plus_ad_id}}",
    "ad_material_ids": ["{{ad_material_ids}}"],
    "operation_status": "DISABLE"
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
|data|object|Returned data.|
#|smart_plus_ad_id|string[]|Ad ID.|
#|ad_material_ids|string[]|A list of ad material IDs for creatives operated on. <br>An ad material ID is an ad-specific material ID generated when a particular creative is used in an ad. <br>This ID differs from the creative ID you receive when uploading the creative to your ad account’s Creative Library.|
#|material_status|string|The statuses of the creatives.<br><br>Enum values: `DISABLE` (paused), `ENABLE` (enabled).|
```

### Example
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_material_ids": [
            "{{ad_material_id}}"
        ],
        "operation_status": "DISABLE",
        "smart_plus_ad_id": "{{smart_plus_ad_id}}"
    }
}
(/code)
```

