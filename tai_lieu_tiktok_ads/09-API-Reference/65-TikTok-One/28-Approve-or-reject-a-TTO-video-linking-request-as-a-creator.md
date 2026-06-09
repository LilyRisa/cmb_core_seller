---
title: "Approve or reject a TTO video linking request as a creator"
doc_id: 1837247744649345
path: "API Reference / TikTok One / Approve or reject a TTO video linking request as a creator"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Approve or reject a TTO video linking request as a creator

Use this endpoint to approve or reject a request for linking your public video to a TikTok One (TTO) Creator Marketplace campaign.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/creator/link/request/confirm/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators. <br>Use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Obtain%20a%20Creator%20access%20token) or [/tt_user/oauth2/refresh_token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Renew%20a%20Creator%20access%20token) endpoint to get or renew an access token.|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|creator_id {Required}|string|Application specific unique ID of the TikTok account.<br><br> Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Obtain%20a%20Creator%20access%20token) or [/tt_user/oauth2/refresh_token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Renew%20a%20Creator%20access%20token) to this field.|
| link_request_id {Required} | string | The ID of the linking request.<br><br>To obtain the ID, use [/tto/creator/link/request/get/](https://business-api.tiktok.com/portal/docs?id=1837247726007298) and check the returned `link_request_id`. |
| action {Required} | string | Action to be taken.<br><br>Enum values: <ul><li>`APPROVE`: To approve the linking request.</li><li>`REJECT`: To reject the linking request.</li></ul> |
```


### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tto/creator/link/request/confirm/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "creator_id": "{{creator_id}}",
    "link_request_id": "{{link_request_id}}",
    "action": "APPROVE"
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
|data |object|Returned data. <br> <br>For a successful request, this field will be an empty object (`{}`).|
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
