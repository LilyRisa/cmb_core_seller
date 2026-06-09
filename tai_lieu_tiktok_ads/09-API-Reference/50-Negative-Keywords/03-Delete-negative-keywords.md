---
title: "Delete negative keywords"
doc_id: 1775104910010369
path: "API Reference / Negative Keywords / Delete negative keywords"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete negative keywords

Use this endpoint to get the list of negative keywords for Search Ads.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/search_ad/negative_keyword/delete/

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
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| object_type {Required} | string | Object type. Use this field to specify whether you want to delete negative keywords for a campaign or ad group. <br><br> Enum values: <ul><li>`CAMPAIGN`: campaign.</li><li>`ADGROUP`: ad group.</li></ul> |
| object_id {Required} | string | Object ID. Use this field to pass in a campaign ID or ad group ID that you want to delete negative keywords for. <br><br> Specify this field according to `object_type`: <ul><li> If `object_type` is `CAMPAIGN`, assign to this field a campaign ID.</li><li> If `object_type` is `ADGROUP`, assign to this field an ad group ID.</li></ul>|
| keyword_ids {Required} | string[] | ID of the negative keywords that you want to delete.<br> <br>Max size: 1,000.<br> <br> To obtain negative keyword IDs, use [/search_ad/negative_word/get/](https://business-api.tiktok.com/portal/docs?id=1775104887052289).  <br><br><p><span style="color:darkred"><b>Note</b></span>: If any of the negative keyword IDs that you specify does not exist, an error will occur. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/search_ad/negative_keyword/delete/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id":"{{advertiser_id}}",
    "object_type":"CAMPAIGN",
    "object_id":"{{object_id}}",
    "keyword_ids":["{{keyword_id}}"]
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
