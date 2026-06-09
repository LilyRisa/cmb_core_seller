---
title: "Delete a playable creative"
doc_id: 1737733368940546
path: "API Reference / Playable Ads / Delete a playable creative"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete a playable creative

Use this endpoint to delete a playable creative. 

Only saved playable creatives can be deleted. You can use [/playable/save/](https://ads.tiktok.com/marketing_api/docs?id=1737732203851777) to save a playable creative.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/playable/delete/|/v1.3/playable/delete/|
|Request parameter data type |`advertiser_id`: number<br>`playable_id`: number|`advertiser_id`: string<br>`playable_id`: string|
|Response parameter data type|`playable_id`: number|`playable_id`: string|
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/playable/delete/

**Method** POST

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
| playable_id {Required} | string | The ID of the playable ad. |
```

### Example
```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID", 
    "playable_id": "PLAYABLE_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/playable/delete/
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
| request_id | string |The log id of a request, which uniquely identifies the request. |
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "playable_id": "4154",
    },
    "request_id": "20200924101137010116035039245F569"
}
(/code);
```
