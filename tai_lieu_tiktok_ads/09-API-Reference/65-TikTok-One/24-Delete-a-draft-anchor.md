---
title: "Delete a draft anchor"
doc_id: 1826564985760770
path: "API Reference / TikTok One / Delete a draft anchor"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete a draft anchor

Use this endpoint to delete an anchor in draft mode that was created on the [TikTok One](https://ads.tiktok.com/creative) platform.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/anchor/delete/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TikTok One Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| tto_tcm_account_id {Required} | string | The ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
|anchor_id {Required}|string|Anchor ID.<br><br>To obtain a draft anchor within your TikTok One (TTO) Creator Marketplace account, use [/tto/tcm/anchor/get/](https://business-api.tiktok.com/portal/docs?id=1826564965840066) and confirm that the `status` of the anchor is `DRAFT`.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/anchor/delete/' \
--header 'Access-Token: {{Access_Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "tto_tcm_account_id" : "{{tto_tcm_account_id}}",
    "anchor_id" : "{{anchor_id}}"
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
