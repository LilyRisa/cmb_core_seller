---
title: "Download exported comments"
doc_id: 1738088341958658
path: "API Reference / Ad Comments / Download exported comments"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Download exported comments

Use this endpoint to download the exported data after an export task completes successfully. 

The exported data is in CSV, and has the following headers: Content, Likes, Replies, Comment Type, Create Time, Campaign Name, Ad Group Name, Ad Name. If a comment contains mentions of user names, the mentions will be processed as "@user_name" in the exported table.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/comment/task/download/|/v1.3/comment/task/download/|
|Request parameter data type|`advertiser_id`: number|`advertiser_id`: string|
|New response parameter|/|`csv`: string|
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/comment/task/download/

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
https://business-api.tiktok.com/open_api/v1.3/comment/task/download
(/code)
```

## Response

```xtable
|Field|Type|Description|
|-|-|-|
|message|string|The return message. For Details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For details, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object| Data  |
#|csv |string| CSV comments content. |
|request_id |string|Unique ID of the request  |
```

