---
title: "Get OS versions"
doc_id: 1738308662898689
path: "API Reference / Tools / Get OS versions"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get OS versions

Use this endpoint to get the enumeration values of operating system version.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/tools/os_version/|/v1.3/tool/os_version/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Response parameter name and data type|`id`: number|`os_id`: string|
```
## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/tool/os_version/

**Method** GET

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|os_type {Required}|string| OS type. Enum values: `ANDROID`,`IOS`. |
```

### Example
```xcodeblock
(code curl http)
curl --location request GET 'https://business-api.tiktok.com/open_api/v1.3/tool/os_version/?advertiser_id={{advertiser_id}}&os_type=IOS' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response
```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return message. For details, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data. |
#|os_versions|object[]| OS versions list. |
##|os_id|string| OS versions ID. |
##|os_type |string| OS type. |
##|version|string| OS version. |
##|name|string| OS version name. |
|request_id |string|The log ID of a request, which uniquely identifies the request.|
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
        "os_versions": [
            {
                "name": "{{name}}",
                "version": "{{version}}",
                "os_id": "{{os_id}}"
            },
            {
                "name": "{{name}}",
                "version": "{{version}}",
                "os_id": "{{os_id}}"
            }
            }
        ]
    }
}
(/code);
```
