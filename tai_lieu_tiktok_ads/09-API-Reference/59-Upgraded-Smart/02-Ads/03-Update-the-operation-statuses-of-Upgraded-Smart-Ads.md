---
title: "Update the operation statuses of Upgraded Smart+ Ads"
doc_id: 1843317423164482
path: "API Reference / Upgraded Smart+ / Ads / Update the operation statuses of Upgraded Smart+ Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update the operation statuses of Upgraded Smart+ Ads

Use this endpoint to enable, pause, or delete Upgraded Smart+ Ads.

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/status/update/

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
|smart_plus_ad_ids {Required}|string[]|A list of ad IDs. <br><br>Max size: 20.<br><br>To obtain ad IDs, use [/smart_plus/ad/get/](https://business-api.tiktok.com/portal/docs?id=1843317378982914).|
|operation_status {Required}|string|The operation to perform on the ads.<br><br>Enum values: `DISABLE` (pause), `ENABLE` (enable), `DELETE` (delete).<br><br><p><span style="color:darkred"><b>Note</b></span>: The status of deleted ads or ads in deleted campaigns or ad groups cannot be modified.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/status/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "smart_plus_ad_ids":["{{smart_plus_ad_ids}}"],
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
#|smart_plus_ad_ids|string[]|The ID list of updated ads.|
#|status|string|The current statuses of the ads.<br><br>Enum values: `DISABLE` (paused), `ENABLE` (enabled), `DELETE` (deleted).|
```

### Example
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "smart_plus_ad_ids": [
            "{{smart_plus_ad_id}}"
        ],
        "status": "DISABLE"
    }
}
(/code)
```
