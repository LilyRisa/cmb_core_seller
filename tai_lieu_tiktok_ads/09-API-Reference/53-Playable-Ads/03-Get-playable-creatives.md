---
title: "Get playable creatives"
doc_id: 1737732758495234
path: "API Reference / Playable Ads / Get playable creatives"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get playable creatives

Use this endpoint to get a list of playables.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/playable/get/|/v1.3/playable/get/|
|Request parameter data type |`advertiser_id`: number<br>`playable_id`: number|`advertiser_id`: string<br>`playable_id`: string|
|Response parameter data type|`advertiser_id`: number<br>`playable_id`: number|`advertiser_id`: string<br>`playable_id`: string|
|New response parameters|/|`operation_status`|
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/playable/get/

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
| advertiser_id {required} | string | Advertiser ID. |
| playable_id | string | Playable creative ID, if passed only return ID matching Playable clip. |
| playable_name | string | Playable creative name (filter the Playable creative material with the name 'playable_name'). |
| page | number | Current number of pages. Default value: 1. Range: ≥ 1. |
| page_size | number | Pagination size. Default value: 10. Range: 1-100. |
| playable_url | string | Playable creative URL. |
| status | string | Playable creative state. Enum values:`AUDIT_FAIL`,`AUDIT_SUCCESS`,`VALIDATE_FAIL`,`VALIDATE_SUCCESS`,`VALIDATING`. |
| operation_status | string | Playable creative operation state. Enum values:`UPLOADED`,`SAVED`,`DELETED`. Default value: `SAVED`.|
```

### Example
```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "playable_url=PLAYABLE_URL" \
--data-urlencode "status=STATUS" \
--data-urlencode "page=PAGE" \
--data-urlencode "page_size=PAGE_SIZE" \
https://business-api.tiktok.com/open_api/v1.3/playable/get/
(/code)
```

## Response
``` xtable
| Field | Type | Description |
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return message. For details, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data.|
# | list | object [] | Playable creative list. |
# # | advertiser_id | string | Advertiser ID. |
# # | playable_id | string | Playable creative ID. |
# # | playable_name | string | Playable creative name. |
# # | playable_orientation | string | Playable creative orientation direction. Enum values:`BOTH`,`LANDSCAPE`,`PORTRAIT`. |
# # | status | string | Playable creative state. Enum values:`AUDIT_FAIL`,`AUDIT_SUCCESS`, `VALIDATE_FAIL`, `VALIDATE_SUCCESS`,`VALIDATING`. |
# # | playable_url | string | Playable creative URL. |
# # | operation_status | string | Playable Creative operation state. Enum values:`UPLOADED`,`SAVED`,`DELETED`. |
# | page_info | object | Pagination information. |
# # | page | number | Current page number.|
# # | page_size | number | Page size. |
# # | total_number | number | Total number of results. |
# # | total_page | number | Total number of pages. |
| request_id | string |The log id of a request, which uniquely identifies the request.|
```
### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
  "code": 0,
  "data": {
    "list": [
      {
        "playable_id": "1",
		"operation_status": "SAVED",
		"advertiser_id": "1",
        "playable_name": "playable_name1",
        "playable_orientation": "BOTH",
        "playable_url": "https://playable.ad.url",
        "status": "AUDIT_SUCCESS"
      },
      {
        "playable_id": "2",
		"operation_status": "SAVED",
		"advertiser_id": "1",
        "playable_name": "playable_name2",
        "playable_orientation": "PORTRAIT",
        "playable_url": "https://playable.ad.url",
        "status": "AUDIT_SUCCESS"
      }
    ],
    "page_info": {
      "page": 1,
      "page_size": 10,
      "total": 2,
      "total_pages": 1
    }
  },
  "message": "OK",
  "request_id": "20200310084902010189072215505ECDB3"
}
(/code);
```

