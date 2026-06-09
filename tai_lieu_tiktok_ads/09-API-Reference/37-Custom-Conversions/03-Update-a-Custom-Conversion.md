---
title: "Update a Custom Conversion"
doc_id: 1842225247176705
path: "API Reference / Custom Conversions / Update a Custom Conversion"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update a Custom Conversion

Use this endpoint to update a Custom Conversion for a Pixel or an App.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/custom_conversion/update/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|advertiser_id {Required}|string|Advertiser ID.|
|custom_conversion_id {Required}|string|The ID of the Custom Conversion.<br><br>To obtain the list of Custom Conversions associated with a Pixel or an App, use [/custom_conversion/list/](https://business-api.tiktok.com/portal/docs?id=1842225174460673).|
|name|string|The new name of the Custom Conversion.<br>The name must start with a letter and can include letters, numbers, spaces, underscores, or hyphens.<br><br>Length limit: 50 characters and cannot contain emoji.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Do not specify the name of a standard event.</li><li>Duplicate names for Custom Conversion within an ad account are not allowed.</li></ul> |
|description|string|The new description of the Custom Conversion.<br><br>Length limit: 500 characters and cannot contain emoji.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/custom_conversion/update/' \
--header 'Access-token: {{Access-token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "custom_conversion_id": "{{custom_conversion_id}}",
    "name": "{{name}}"
}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```
