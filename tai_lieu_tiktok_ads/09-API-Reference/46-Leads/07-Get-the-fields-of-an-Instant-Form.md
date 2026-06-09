---
title: "Get the fields of an Instant Form"
doc_id: 1739054080455681
path: "API Reference / Leads / Get the fields of an Instant Form"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the fields of an Instant Form

Use this endpoint to get the fields of an Instant Form (or lead form).

To retrieve the Instant Form fields, you can also choose to create a mock lead using [/page/lead/mock/create/](https://ads.tiktok.com/marketing_api/docs?id=1739047132614722) and then extract the field names. However, we recommend you use this endpoint to get Instant Form fields directly. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/pages/fields/get/|/v1.3/page/field/get/|
|Request parameter data type |`advertiser_id`: number<br>`page_id`: number|`advertiser_id`: string<br>`page_id`:string|
|Response parameter data type|`page_id`: number<br>`create_time`: number|`page_id`: string<br>`create_time`: datetime|
```


## Request
**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/page/field/get/

**Method** GET

**Header**
```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|The content type of the request. <br>Allowed value: <code>"application/json"</code>.
```

**Parameters**
```xtable
| Field | Type | Description |
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|page_id {Required}|string| The ID of the Instant Form. |
```

### Example
```xcodeblock
(code curl http)
curl -L -X GET 'https://business-api.tiktok.com/open_api/v1.3/page/field/get/?advertiser_id=ADVERTISER_ID&page_id=PAGE_ID' \
-H 'Access-Token: xxx' \
-H 'Content-Type: application/json'
(/code)
```

## Response
```xtable
|Field|Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|The returned data. |
#|fields|string| The Instant Form fields. |
#|meta_data|object| Metadata about the Instant Form. |
##|page_id|string| The Instant Form ID. |
##|create_time|datetime| The creation time of the Instant Form. |
##|page_name|string| Page title. |
##| page_url|string| Instant Form URL.|
|request_id |string|The unique ID of the request, which uniquely identifies a request. |
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "data": {
        "fields": [],
        "meta_data": {}
    }
}
(/code);
```




