---
title: "Create an export task (blocked words)"
doc_id: 1739029296119809
path: "API Reference / Ad Comments - Blocked Words / Create an export task (blocked words)"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an export task (blocked words)

Use this endpoint to  create a blocked word export task. 

After the task is created, you can use [/blockedword/task/check/](https://ads.tiktok.com/marketing_api/docs?id=1739029311687681) to check the status of the task. When the task completes, you can call [/blockedword/task/download/](https://ads.tiktok.com/marketing_api/docs?id=1739029325203457) to download the exported data.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/blockedword/task/create/| /v1.3/blockedword/task/create/|
|Request parameter data type|`advertiser_id`: number |`advertiser_id`: string |
|Request parameter name|`lang` |`language`|
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/blockedword/task/create/

**Method** POST

**Header**

```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
|Content-Type {required}|string| Content type of the request. Allowed value: `application/json`|
```

**Parameters**

```xtable
|Field|Type|Description|
|--- |--- |--- |
|advertiser_id {required}|string| Advertiser ID.|
|blocked_words | string[]| List of blocked words that you want to export. If not specified, all blocked words will be exported. <br><p><span style="color:darkred"><b>Note</b></span>: Unblocked words specified via this field will be ignored. Therefore, if you specify both blocked words and unblocked words at the same time via this field, only the blocked words will be exported.|
|language|string| Language of the headers of the exported data. Enum values: `EN`, `JA`, `ZH`. Default value: `EN`.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/blockedword/task/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "advertiser_id": "{{advertiser_id}}"
}'
(/code)
```

## Response

```xtable
|Field|Type|Description|
|---|---|---|
|message|string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|The returned data.|
#|task_id|string|Task ID.|
|request_id |string|Unique ID of the request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code);
```
