---
title: "Delete an Offline Event set"
doc_id: 1765596790860802
path: "API Reference / Events 1.0 / Offline Events / Delete an Offline Event set"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete an Offline Event set

Use this endpoint to delete an Offline Event set under an advertiser account.

> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.
## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/offline/delete/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID. |
| event_set_id {Required} | string | ID of the Offline Event set that you want to delete. <br> You can get Offline Event set IDs under an advertiser account via [/offline/get/](https://ads.tiktok.com/marketing_api/docs?id=1765596808589313). |
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/offline/delete/' 
--header 'Access-Token: {{Access-Token}}' 
--header 'Content-Type: application/json' 
--data-raw '{
    "advertiser_id":"{{advertiser_id}}",
    "event_set_id":"{{event_set_id}}"
}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.<br>Empty object (`{}`) for successful actions.|
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
