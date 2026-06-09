---
title: "Update a Pixel Event"
doc_id: 1740858823774210
path: "API Reference / Events 1.0 / Pixel Events / Update a Pixel Event"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update a Pixel Event

Use this endpoint to update the name of a Pixel event and the conversion value.

> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.
## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/pixel/event/update/| /v1.3/pixel/event/update/|
|Request parameter data type|`advertiser_id`: number <br>`event_id`: number |`advertiser_id`: string <br>`event_id`: string |
```

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/pixel/event/update/

**Method** POST

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
|Content-Type {Required}|string|Request message type<br>Allowed format: `"application/json"`|
```

**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID |
|currency_value |string| Event conversion value |
|currency |string| Event conversion value representing the currency, optional values include: `INR` (Indian Rupee),` JPY` (Japanese Yen), `USD` (US Dollar). Default <br>value is ` USD` |
|event_id {Required}|string|  Event ID. You can get the event ID from the `event_id ` field in the response of the [/pixel/list/](https://ads.tiktok.com/marketing_api/docs?id=1709497028894721) endpoint.|
|event_name {Required}|string| Event name. The name cannot exceed 40 characters (for all languages) and cannot contain emoji. |
```

### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID", 
    "currency": "CURRENCY", 
    "event_id": "EVENT_ID", 
    "event_name": "EVENT_NAME", 
    "currency_value": "CURRENCY_VALUE"
}' \
https://business-api.tiktok.com/open_api/v1.3/pixel/event/update/
(/code)

```

## Response
```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see[Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)  |
|message |string|Return message, see[Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)  |
|data |object|Return data.|
|request_id |string|The log id of a request, which uniquely identifies the request.|
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

