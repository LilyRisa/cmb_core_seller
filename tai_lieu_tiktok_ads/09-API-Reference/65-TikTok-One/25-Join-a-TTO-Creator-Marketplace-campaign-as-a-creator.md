---
title: "Join a TTO Creator Marketplace campaign as a creator"
doc_id: 1836584318657537
path: "API Reference / TikTok One / Join a TTO Creator Marketplace campaign as a creator"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Join a TTO Creator Marketplace campaign as a creator

Use this endpoint to join a TikTok One (TTO) Creator Marketplace campaign as a TikTok creator.

After you join the campaign, you can use publish video posts using [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234) and link the videos to the campaign using [/tto/creator/campaign/video/link/](https://business-api.tiktok.com/portal/docs?id=1836584551347201).

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/creator/campaign/join/

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
| tto_invite_link {Required} | string | An invite link that is associated with a TTO Creator Marketplace campaign.<br><br>To create a TTO Creator Marketplace campaign and obtain the associated invite link, use [/tto/tcm/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1815693549459458). <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> If the invite link is a campaign level invite link (the `campaign_type` of the associated campaign is `CAMPAIGN`), the creator should be one of the creators invited to the campaign. To confirm this, use [/tto/tcm/campaign/](https://business-api.tiktok.com/portal/docs?id=1815693562720258) and check whether the returned `handle_names` includes the handle name of the creator (`creator_id`).</li><li> If the invite link is a brand level invite link (the `campaign_type` of the associated campaign is `BRAND`), the creator can be any TTO creator.</li></ul> |
```


### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tto/creator/campaign/join/' \
--header 'Access-Token: "{{Access-Token}}"' \
--header 'Content-Type: application/json' \
--data '{
    "creator_id": "{{creator_id}}",
    "tto_invite_link": "{{tto_invite_link}}"
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
