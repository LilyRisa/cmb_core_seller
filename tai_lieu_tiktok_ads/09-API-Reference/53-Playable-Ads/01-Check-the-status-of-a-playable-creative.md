---
title: "Check the status of a playable creative"
doc_id: 1737732054892545
path: "API Reference / Playable Ads / Check the status of a playable creative"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Check the status of a playable creative

Use this endpoint to check the validation and audit status of the uploaded playable creative.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/playable/validate/|/v1.3/playable/validate/|
|Request parameter data type |`advertiser_id`: number<br>`playable_id`: number|`advertiser_id`: string<br>`playable_id`: string|
|Response parameter data type|`playable_id`: number|`playable_id`: string|
```

## Request
**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/playable/validate/

**Method** GET

**Header**
``` xtable
| Field | Type | Description |
|---|---|---|
| Access-Token {Required} | string | Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```
**Parameters**
``` xtable
| Field | Type | Description |
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID. |
| playable_id {Required} | string | Playable creative ID. |
```

### Example
```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "playable_id=PLAYABLE_ID" \
https://business-api.tiktok.com/open_api/v1.3/playable/validate/
(/code)

```

## Response
``` xtable
| Field | Type | Description |
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return message. For details, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data|
# | playable_id | string | Playable creative ID. |
# | playable_url | string | Playable creative URL. |
# | playable_orientation | string | Playable creative direction. Enum values:`BOTH`,`LANDSCAPE`,`PORTRAIT`. |
# | status | string | Playable creative status. Enum values:`AUDIT_FAIL`,`AUDIT_SUCCESS`, `VALIDATE_FAIL`, `VALIDATE_SUCCESS`,`VALIDATING`. |
# | fail_message | string | Verify the cause of failure. |
| request_id | string | The log id of a request, which uniquely identifies the request.|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "status": "AUDIT_SUCCESS",
        "playable_url": "https://sf-tk-sg.ibytedtos.com/obj/union-fe-sg/playable/23a333c9966d94979783857f2105ec70/index.html",
        "playable_id": "4154",
        "playable_orientation": "BOTH"
    },
    "request_id": "20200924101137010116035039245F569"
}
(/code);
```

