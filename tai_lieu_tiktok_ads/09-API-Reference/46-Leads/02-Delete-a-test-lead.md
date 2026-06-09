---
title: "Delete a test lead"
doc_id: 1739049313198081
path: "API Reference / Leads / Delete a test lead"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete a test lead

Use this endpoint to delete an existing test lead.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/pages/leads/mock/delete/|/v1.3/page/lead/mock/delete/|
|Request parameter data type |`advertiser_id`:number<br>`library_id `:number<br>`page_id`:number|`advertiser_id`: string<br>`library_id `:string<br>`page_id`:string|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/page/lead/mock/delete/

**Method** POST

**Header**
```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string| The content type of the request. For this endpoint, use `application/json`.|
```


**Parameters**
```xtable
| Field | Type | Description |
|---|---|---|
|advertiser_id {+Conditional}|string| Required when the test lead is under an ad account.<br><br>Advertiser ID. |
|library_id {+Conditional}|string|Required when the test lead is under a form library of a Business Center.<br><br>ID of a form library.|
|lead_id {Required}|string| The ID of the test lead that you want to delete.<br><br>To obtain the ID of the existing test lead, use [/page/lead/mock/get/](https://business-api.tiktok.com/portal/docs?id=1739048460493826). |
```

### Example
```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID", 
    "lead_id": "LEAD_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/page/lead/mock/delete/
(/code)

```

## Response
```xtable
|Field|Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|The returned data. |
|request_id |string|The unique ID of the request, which uniquely identifies a request. |
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
