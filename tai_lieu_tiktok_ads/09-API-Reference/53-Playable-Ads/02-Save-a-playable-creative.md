---
title: "Save a playable creative"
doc_id: 1737732203851777
path: "API Reference / Playable Ads / Save a playable creative"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Save a playable creative


Use this endpoint to save a playable.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/playable/save/|/v1.3/playable/save/|
|Request parameter data type|`advertiser_id`: number<br>`playable_id`: number|`advertiser_id`: string<br>`playable_id`: string|
|Response parameter data type|`playable_id`: number|`playable_id`: string|
```

## Request
**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/playable/save/

**Method** POST

**Header**
``` xtable
| Field | Type | Description |
|---|---|---|
| Access-Token {Required} | string | Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
| Content-Type {Required} | string | Request Message Type <br> Allowable value: `application/json` |
```
**Parameters**
``` xtable
| Field | Type | Description |
|---|---|---|
| advertiser_id {required} | string | Advertiser ID |
| playable_id {required} | string | Playable creative ID. |
| playable_name {required} | string | Playable creative name. |
```

### Example
```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID", 
    "playable_id": "PLAYABLE_ID", 
    "playable_name": "PLAYABLE_NAME"
}' \
https://business-api.tiktok.com/open_api/v1.3/playable/save/
(/code)
```

## Response
``` xtable
| Field | Type | Description |
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return message. For details, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data.|
# | playable_id | string | Playable creative ID. |
# | playable_name | string | Playable creative name. |
# | playable_orientation | string | Playable creative direction. Enum values:`BOTH`, `LANDSCAPE`, `PORTRAIT`. |
# | playable_url | string | Playable creative URL. |
# | status | string | Playable creative state. Enum values:`AUDIT_FAIL`,`AUDIT_SUCCESS`, `VALIDATE_FAIL`, `VALIDATE_SUCCESS`, `VALIDATING`. |
| request_id | string | The log id of a request, which uniquely identifies the request. |
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
        "playable_id": "4009",
        "playable_url": "https://sf-tk-sg.ibytedtos.com/obj/union-fe-sg/playable/f0fbcba5acc86a0e22ec0d9fca194d09/index.html",
        "playable_name": "my_playable.zip",
        "playable_orientation": "BOTH"
    },
    "request_id": "202009241012350101160350397899622"
}
(/code);
```
