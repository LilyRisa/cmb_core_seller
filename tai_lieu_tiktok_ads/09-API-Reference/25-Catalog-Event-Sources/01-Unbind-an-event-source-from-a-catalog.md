---
title: "Unbind an event source from a catalog"
doc_id: 1740492512449538
path: "API Reference / Catalog Event Sources / Unbind an event source from a catalog"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Unbind an event source from a catalog

Use this endpoint to unbind an app event or a website event from a catalog in a Business Center.

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/catalog/eventsource_bind/delete/|/v1.3/catalog/eventsource/unbind/|
|Request parameter data type |`advertiser_id`: number<br>`bc_id`: number<br>`catalog_id`: number<br>`app_id`: number|`advertiser_id`: string <br>`bc_id`: string <br>`catalog_id`: string<br>`app_id`: string|
```

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/catalog/eventsource/unbind/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type<br>Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|bc_id {Required}|string|Business Center ID.|
|catalog_id{Required}|string|Catalog ID.|
|app_id|string| Mobile application ID. You can find the mobile app ID by navigating to <strong>Assets</strong> > <strong>Events</strong> > <strong>App Events</strong> in TikTok Ads Manager. |
|pixel_code|string| Website Pixel code. You can find the pixel code by navigating to <strong>Assets</strong>> <strong>Events</strong> > <strong>Website Events</strong> in TikTok Ads Manager. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST https://business-api.tiktok.com/open_api/v1.3/catalog/eventsource/unbind/
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "bc_id": "{{bc_id}}",
    "catalog_id": "{{catalog_id}}",
    "advertiser_id": "{{advertiser_id}}",
    "pixel_code": "{{pixel_code}}"
}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
```

