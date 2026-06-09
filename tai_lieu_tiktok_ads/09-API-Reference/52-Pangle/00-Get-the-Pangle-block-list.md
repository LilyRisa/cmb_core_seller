---
title: "Get the Pangle block list"
doc_id: 1740039957181441
path: "API Reference / Pangle / Get the Pangle block list"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the Pangle block list

Use this endpoint to get the Pangle block list of an ad account.

## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/pangle_block_list/get/|/v1.3/pangle_block_list/get/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Response parameter data type |`app_package_id`: number|`app_package_id`: string|
```
## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/pangle_block_list/get/

**Method** GET

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
```
### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
https://ads.tiktok.com/open_api/v1.3/pangle_block_list/get/
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Return messages. For details, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|Returned data.  |
#|app_list|string[]| App block list. iTunes ID for iOS app and package name for Android apps. |
#|app_package_id|string| App block list ID. |
#|modify_time|string| The time when the App block list was last updated, in [ISO-8601](https://en.wikipedia.org/wiki/ISO_8601) format.<br><br>Example: "2024-01-01T00:00:01+00:00".|
|request_id |string|The log id of a request, which uniquely identifies the request.|
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
        "modify_time": "2024-01-30T01:57:34+00:00",
        "app_package_id": "{{app_package_id}}",
        "app_list": [
            "android.package.name"
        ]
    }
}
(/code);
```



