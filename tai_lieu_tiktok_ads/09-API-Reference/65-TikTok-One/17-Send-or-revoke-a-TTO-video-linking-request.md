---
title: "Send or revoke a TTO video linking request"
doc_id: 1824449956282370
path: "API Reference / TikTok One / Send or revoke a TTO video linking request"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Send or revoke a TTO video linking request

Use this endpoint to send a request for linking a creator's public video to a TikTok One (TTO) Creator Marketplace campaign, or revoke such a request.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/campaign/link/

**Method** POST

**Header**

```xtable
|Field{32%}|Type{15%}|Description{53%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TikTok One Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{32%}|Type{15%}|Description{53%}|
|--- |--- |--- |
| tto_tcm_account_id {Required} | string | The ID of the TikTok One Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
| campaign_id {Required} | string | The ID of an existing TTO Creator Marketplace campaign. <br><br>To obtain a list of TTO Creator Marketplace campaigns, use [/tto/tcm/campaign/](https://business-api.tiktok.com/portal/docs?id=1815693562720258). |
| video_id {Required} | string | The ID of a creator's public video to be linked to the campaign. <br><br>To obtain a list of creator videos, use [/tcm/creator/authorized/video/list/](https://business-api.tiktok.com/portal/docs?id=1735827480228865). <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Videos that have been linked to any other TikTok Creator Marketplace campaigns or TTO Creator Marketplace campaigns are not supported.</li><li>The creator of the video needs to have been invited to the campaign.</li></ul>|
| action {Required} | string | Action to be taken.<br><br>Enum values:<ul><li>`LINK`: Send a request to the creator for linking the video to the campaign. If such a request has been sent, the system will send a reminder notification to the creator. <ul><li>After you send a linking request, you need to wait for 24 hours to send a reminder notification. Otherwise, an error will occur. </li><li> You can send up to two reminder notifications for a linking request.</li></ul></li><li> `REVOKE`: Revoke a linking request. <ul><li> You can only use this value when the creator has not accepted or rejected the request. </li><li>You can only revoke each linking request once.</li></ul></li></ul>|
```
### Example
#### Send a linking request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/campaign/link/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "tto_tcm_account_id": "{{tto_tcm_account_id}}",
    "video_id": "{{video_id}}",
    "campaign_id": "{{campaign_id}}",
    "action": "LINK"
}'
(/code)
```

#### Revoke a linking request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/campaign/link/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "tto_tcm_account_id": "{{tto_tcm_account_id}}",
    "video_id": "{{video_id}}",
    "campaign_id": "{{campaign_id}}",
    "action": "REVOKE"
}'
(/code)
```

## Response

``` xtable
|Field{32%}|Type{15%}|Description{53%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| video_id | string | The ID of the creator video to be linked to the campaign. |
#| status | string | The status of the request. <br><br>Enum values:<ul><li>`IN_REVIEW`: Sent to the creator and pending the creator's acceptance or rejection.</li><li> `REVOKE`: Revoked. </li></ul>|
#| last_reminded_timestamp | string | The last time when the linking request or the reminder notification was sent to the creator, in [ISO-8601](https://en.wikipedia.org/wiki/ISO_8601) format.<br><br>Example: `2025-01-01T00:00:01Z`. |
#| number_of_requests | integer | The number of linking requests that you have sent for the video. <br><br>Example: 1. |
#| campaign_type | string | The type of the TTO Creator Marketplace campaign.<br><br>Enum values:<ul><li>`CAMPAIGN`: A campaign with a [campaign-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Campaign-level%20invite%20link).</li><li>`BRAND_LINK`: A campaign with a [brand-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Brand-level%20invite%20link).</li></ul> |
```

### Example
#### Send a linking request
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "last_reminded_timestamp": "2025-02-07T21:44:56Z",
        "number_of_requests": 1,
        "status": "IN_REVIEW",
        "campaign_type": "CAMPAIGN",
		"video_id": "{{video_id}}"
    }
}
(/code)
```

#### Revoke a linking request
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "last_reminded_timestamp": "2025-02-07T21:12:44Z",
        "number_of_requests": 1,
        "status": "REVOKE",
        "campaign_type": "CAMPAIGN",
		"video_id": "{{video_id}}"
    }
}
(/code)
```
