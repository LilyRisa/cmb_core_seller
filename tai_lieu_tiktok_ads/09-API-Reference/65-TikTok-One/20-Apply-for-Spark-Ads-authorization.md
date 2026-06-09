---
title: "Apply for Spark Ads authorization"
doc_id: 1751098904942594
path: "API Reference / TikTok One / Apply for Spark Ads authorization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Apply for Spark Ads authorization

Use this endpoint to send a request to apply for TikTok creator's authorization to boost their video for a TikTok Creator Marketplace (TTCM) order or a TikTok One (TTO) Creator Marketplace campaign.

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/tcm/tt_video/apply/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TikTok Creator Marketplace account or TTO Creator Marketplace account. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|video_id {Required}|string| ID of the video that is created under the creator account. <br><br>The video needs to meet any of the following requirements: <ul><li>The video has been uploaded and tagged as branded content, and the creator has linked the video to a TTCM order via the invite link (`invite_link`) or campaign code (`campaign_code`) created via [/tcm/order/create/v2/](https://business-api.tiktok.com/portal/docs?id=1747285331289089). <ul><li>You can get `video_id` via [/tcm/order/get/v2/](https://business-api.tiktok.com/portal/docs?id=1755415792685057) or [/tcm/report/get/v2/](https://business-api.tiktok.com/portal/docs?id=1752366000747522).</li></ul></li><li>The video has been uploaded and tagged as branded content, and the creator has linked the video to a TTO Creator Marketplace campaign via the invite link (`invite_link`) created via [/tto/tcm/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1815693549459458).<ul><li>You can get `video_id` via [/tto/tcm/campaign/](https://business-api.tiktok.com/portal/docs?id=1815693562720258) or [/tto/tcm/report/](https://business-api.tiktok.com/portal/docs?id=1815693573340162).</li></ul></li></ul>|
|tcm_account_id {Required}|string|TikTok Creator Marketplace account ID or the ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the TTO Creator Marketplace accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146).|
|authorization_days |number| Authorization validity period (in days).  <br> <br>Value range: 1-365.  <br>Default value: 30. <br> <br><p><span style="color:darkred"><b>Note</b></span>: You can use this field to specify the validity period of the authorization, or to request an update for the validity period of approved authorization. <br>The actual validity period can be different from the validity period you request, because the creator can choose any validity period (one to 365 days) when approving the request. |
|action|string|The action to take.<br><br>Enum value:<ul><li>`EXTEND`: To extend the validity period of the authorization by the specified days (`authorization_days`). For instance, when `authorization_days` is 30, you'll request the authorization to be extended by 30 days.</li></ul>|
```

### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/tcm/tt_video/apply/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "tcm_account_id":"{{tcm_account_id}}",
    "video_id":"{{video_id}}",
    "authorization_days": 30
}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| Unique ID of the request. |
|data|object|Returned data.|
```

### Example
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```
