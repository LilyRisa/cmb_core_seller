---
title: "Delete a max delivery or creative boost session"
doc_id: 1835246983475217
path: "API Reference / GMV Max / Delete a max delivery or creative boost session"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete a max delivery or creative boost session

Use this endpoint to delete a max delivery session for a specific product or a creative boost session for a specific video in an active Product GMV Max Campaign.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/session/delete/

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
| advertiser_id {Required} | string | Advertiser ID. |
|session_id {Required}|string|The ID of the max delivery session for a product or creative boost session for a video in the Product GMV Max Campaign.<br><br><ul><li>To obtain the ID list of active max delivery sessions within a Product GMV Max Campaign, use [/campaign/gmv_max/session/list/](https://business-api.tiktok.com/portal/docs?id=1835246996436162) and select sessions with `bid_type` as `NO_BID`.</li><li>To obtain the ID list of active creative boost sessions within a Product GMV Max Campaign, use [/campaign/gmv_max/session/list/](https://business-api.tiktok.com/portal/docs?id=1835246996436162) and select sessions with `bid_type` as `CREATIVE_NO_BID`.</li></ul>|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/session/delete/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "session_id": "{{session_id}}"
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
