---
title: "Download exported data"
doc_id: 1739029325203457
path: "API Reference / Ad Comments - Blocked Words / Download exported data"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Download exported data

Use this endpoint to download the exported blocked words when an export task completes. The downloaded file is in CSV format.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/blockedword/task/download/| /v1.3/blockedword/task/download/|
|Request parameter data type|`advertiser_id`: number |`advertiser_id`: string |
```


## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/blockedword/task/download/

**Method** GET

**Header**

```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

```xtable
|Field|Type|Description|
|--- |--- |--- |
|advertiser_id {required}|string| Advertiser ID|
|task_id {Required}|string| Task ID |
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "task_id=TASK_ID" \
https://business-api.tiktok.com/open_api/v1.3/blockedword/task/download
(/code)
```

## Response

A CSV file will be returned in the response.
