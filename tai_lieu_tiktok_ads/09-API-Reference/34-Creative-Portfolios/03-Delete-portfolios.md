---
title: "Delete portfolios"
doc_id: 1834560049625089
path: "API Reference / Creative Portfolios / Delete portfolios"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete portfolios

Use this endpoint to bulk delete creative portfolios (interactive add-ons). 

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/portfolio/delete/

**Method** POST

**Header**

```xtable
| Field{30%} | Data Type{15%} | Description{55%} |
|------------|----------------|------------------|
| Access-Token {Required} | string | Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162). |
| Content-Type {Required} | string | Request message type.<br>Allowed value: `application/json`. |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|creative_portfolio_ids {Required}|string[]|List of creative portfolio IDs to delete.<br><br>Max size: 100. <br><br>To obtain creative portfolio IDs within an ad account, use [/creative/portfolio/list/](https://business-api.tiktok.com/portal/docs?id=1766324010279938).<br><br><p><span style="color:darkred"><b>Note</b></span>:  <ul><li>You cannot use this field to delete Product Card portfolios, Product Tile portfolios, or CTA (Call-to-action) portfolios.</li><li>  If any creative portfolio ID is invalid, the entire deletion process will fail. Invalid portfolio IDs include:  <ul><li>Portfolio IDs that do not exist.</li><li>Portfolio IDs that have already been deleted.</li></ul> |
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/creative/portfolio/delete/' \
--header 'Access-Token: {{Access_Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "creative_portfolio_ids": ["{{creative_portfolio_ids}}"]
}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
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
