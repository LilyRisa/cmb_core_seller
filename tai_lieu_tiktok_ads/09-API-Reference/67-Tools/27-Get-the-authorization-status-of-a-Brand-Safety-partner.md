---
title: "Get the authorization status of a Brand Safety partner"
doc_id: 1738456743621634
path: "API Reference / Tools / Get the authorization status of a Brand Safety partner"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the authorization status of a Brand Safety partner

Use this endpoint to get the  authorization status of your Brand Safety partner. 
* For Zefr, please go to [https://measurement.zefr.com/tiktok/connect](https://measurement.zefr.com/tiktok/connect) to complete setup.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{20%}|v1.2{40%}|v1.3{40%}|
|---|---|---|
|Endpoint path|/v1.2/tools/brand_safety/partner/authentication/|/v1.3/tool/brand_safety/partner/authorize/status/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/tool/brand_safety/partner/authorize/status/

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
|partner {Required}|string| Brand Safety post bid measurement partner. Enum values: `Zefr`.  |
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "partner=PARTNER" \
https://business-api.tiktok.com/open_api/v1.3/tool/brand_safety/partner/authorize/status/
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|Returned data. |
#|status |string | Authorization status. Enum values: `OK`, `NOT_FOUND`.  |
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "202105120919540102360412202805EB06",
    "data": {
        "status": "OK"
    }
}
(/code);
```
