---
title: "Get the Pangle audience packages"
doc_id: 1740040177229826
path: "API Reference / Pangle / Get the Pangle audience packages"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the Pangle audience packages

Use this endpoint to get the Pangle audience packages that are available to an advertiser. 

This audience package is only available for Pangle ad placement, and will not affect traffic for other placements. The audience package will be applied alongside any other targeting settings you select. Audience packages can help you more effectively reach your target audience, while narrowing down the scope for delivery.

## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/pangle_flow_package/get/|/v1.3/pangle_audience_package/get/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Response parameter data type |`package_id`: number|`package_id`: string|
```

## Request
**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/pangle_audience_package/get/

**Method** GET

**Header**
```xtable
|Field|Data Type|Desciption|
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
https://ads.tiktok.com/open_api/v1.3/pangle_audience_package/get/
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return message. For details, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data.|
#|packages |object[]| Pangle audience package list. |
##|bind_type |string| Pangle audience package type. Enum values: `EXCLUDE`, `INCLUDE`. |
##|package_id |string| Pangle audience package ID. |
##|package_name |string| Pangle audience package name. |
|request_id |string| The log id of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "packages": [
            {
                "bind_type": "INCLUDE",
                "package_name": "AudiencePackage0",
                "package_id": 3
            },
            {
                "bind_type": "EXCLUDE",
                "package_name": "AudiencePackage1",
                "package_id": 14
            },
            {
                "bind_type": "INCLUDE",
                "package_name": "AudiencePackage2",
                "package_id": 5
            }
        ]
    },
    "request_id": "2020101209435901023125009012611"
}
(/code);
```
