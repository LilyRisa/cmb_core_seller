---
title: "Update the operation statuses of Upgraded Smart+ Ad Groups"
doc_id: 1843314908153858
path: "API Reference / Upgraded Smart+ / Ad groups / Update the operation statuses of Upgraded Smart+ Ad Groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update the operation statuses of Upgraded Smart+ Ad Groups

Use this endpoint to enable, pause, or delete Upgraded Smart+ Ad Groups.

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/status/update/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed value: `application/json`.|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|adgroup_ids {Required}|string[]|The IDs of the ad groups to operate on.<br><br>Size range: 1-20.<br><br>To obtain ad group IDs, use [/smart_plus/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1843314879617026).|
|operation_status {Required}|string|The operation to perform on the ad groups.<br><br>Enum values: `DISABLE` (pause), `ENABLE` (enable), `DELETE` (delete).<br><br><p><span style="color:darkred"><b>Note</b></span>: The status of deleted ad groups or ad groups in deleted campaigns cannot be modified.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/status/update/' \
--header 'Access-Token: "{{Access-Token}}"' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_ids": ["{{adgroup_id}}","{{adgroup_id}}"],
    "operation_status":"DISABLE"
}'
(/code)
```

### Response
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code|number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data.|
#|adgroup_list|object[]|A list of ad groups for which the operation was successful.|
##|adgroup_id|string|The ID of the ad group for which the operation was successful.|
##|status|string|The current status of the ad group for which the operation was successful.<br><br>Enum values: `DISABLE` (paused), `ENABLE` (enabled), `DELETE` (deleted).|
#|error_list|object[]|A list of errors and the corresponding failed ad groups.|
##|adgroup_id|string|The ID of the ad group for which the operation failed.|
##|error_message|string|The error message associated with the failed ad group.|
```

### Example
#### For successful updates
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "adgroup_list": [
            {
                "adgroup_id": "{{adgroup_id}}",
                "status": "DISABLE"
            },
            {
                "adgroup_id": "{{adgroup_id}}",
                "status": "DISABLE"
            }
        ],
        "error_list": []
    }
}
(/code)
```

#### For failed updates
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "adgroup_list": [],
        "error_list": [
            {
                "adgroup_id": "{{adgroup_id}}",
                "error_message": "Adgroup does not exist. adgroup_id {{adgroup_id}}."
            }
        ]
    }
}
(/code)
```
