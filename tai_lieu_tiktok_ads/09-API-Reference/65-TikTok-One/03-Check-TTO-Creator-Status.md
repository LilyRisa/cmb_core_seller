---
title: "Check TTO Creator Status"
doc_id: 1837799887531010
path: "API Reference / TikTok One / Check TTO Creator Status"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Check TTO Creator Status

Use this endpoint to check if TikTok users have already become TikTok One (TTO) Creators. 

You can check the statuses of up to 20 users at the same time, using `tto_tcm_account_id` and `handle_names`.

## Request


**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/creator/status/get/

**Method** GET

**Header**
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token  {Required}|string|Access token authorized by TTO Creator Marketplace accounts.<br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793). |
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| tto_tcm_account_id {Required} | string | The ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
| handle_names {Required} | string[] | The handle names of TikTok Creators.<br><br>Max size: 20. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/creator/status/get/?tto_tcm_account_id={{tto_tcm_account_id}}&handle_names=["{{handle_name}}","{{handle_name}}","{{handle_name}}"]' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data |object|Returned data.|
#|onboarding_status |object[]|The onboarding status data for Creators.|
##|handle_name |string|The handle name that was checked.|
##|status |string|The onboarding status of the Creator. <br><br>Enum values:<ul><li>`IN`: The Creator has joined TTO Creator Marketplace.</li><li>`NOT_IN`: The Creator has not joined TTO Creator Marketplace.</li><li>`INVALID`: The specified handle name (`handle_name`) is invalid.</li></ul>|
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
        "onboarding_status": [
            {
                "handle_name": "{{handle_name}}",
                "status": "NOT_IN"
            },
            {
                "handle_name": "{{handle_name}}",
                "status": "IN"
            },
            {
                "handle_name": "{{handle_name}}",
                "status": "INVALID"
            }
        ]
    }
}
(/code)
```

