---
title: "Get languages"
doc_id: 1737188554152962
path: "API Reference / Tools / Get languages"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get languages

Use this endpoint to get the enum values of language codes.

After you obtain the language codes, you can pass them to `languages` in [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114) for language targeting.


## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/tools/language/|/v1.3/tool/language/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/tool/language/

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
|advertiser_id {Required}|string| Advertiser ID |
```

### Example
```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
https://business-api.tiktok.com/open_api/v1.3/tool/language/
(/code)
```

## Response
```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return message. For details, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data. |
#|languages|object[]| Language list. |
##|code|string| Language code. You can pass the value of this field to `languages` in [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114) for language targeting.<br><br>Example: `en`.|
##|name|string| Language name. <br><br>Example: `English`.|
|request_id |string|The log id of a request, which uniquely identifies the request. |
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "20220630225329010245246039165DE9B4",
    "data": {
        "languages": [
            {
                "name": "Arabic",
                "code": "ar"
            },
            {
                "name": "Assamese",
                "code": "as"
            },
            {
                "name": "Haryanvi",
                "code": "bgc"
            },
            {
                "name": "Bihari",
                "code": "bh"
            },
            ...
        ]
    }
}
(/code);
```
