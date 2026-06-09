---
title: "Cancel the sharing of an audience"
doc_id: 1740245829978113
path: "API Reference / Audience / Cancel the sharing of an audience"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Cancel the sharing of an audience

Use this endpoint to stop the sharing of an audience with advertisers.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
  ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/dmp/custom_audience/share/cancel/| /v1.3/dmp/custom_audience/share/cancel/
|Request parameter data type|`shared_advertiser_id`: number<br>`advertiser_id`: number<br>`custom_audience_id`: number|`shared_advertiser_id`: string<br>`advertiser_id`: string<br>`custom_audience_id`: string|
```

## Request 
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/share/cancel/

**Method** POST

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
| Content-Type {Required} | string | The content type of the request. <br>Allowed value: `"application/json"`.|
```
**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|custom_audience_id {Required}|string|Custom audiences that you want to stop sharing.|
|shared_advertiser_id{Optional}|string|Advertisers that you want to stop sharing audiences with. Only set it when you want to cancel the audiences you shared with others before.|
|advertiser_id{Required}|string| Operator advertiser ID.|
```
### Examples
```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "custom_audience_id": "CUSTOM_AUDIENCE_ID",
    "shared_advertiser_id": "SHARED_ADVERTISER_ID",
    "advertiser_id": "ADVERTISER_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/share/cancel/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
|request_id |string|The log id of the request, which uniquely identifies a request. |
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code);
```




