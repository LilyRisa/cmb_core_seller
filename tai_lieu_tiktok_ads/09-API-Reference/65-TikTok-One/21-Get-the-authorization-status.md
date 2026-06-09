---
title: "Get the authorization status"
doc_id: 1751098935842817
path: "API Reference / TikTok One / Get the authorization status"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the authorization status

Use this endpoint to get the Spark Ads authorization status of a TikTok Creator Marketplace (TTCM) order or a TikTok One (TTO) Creator Marketplace campaign.
 

## Request
 
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tcm/tt_video/status/
 
**Method** GET
 
**Header**
 
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TikTok Creator Marketplace account or TTO Creator Marketplace account. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```
 
**Parameters**

 Place these parameters in the query section.
 
```xtable
|Field|Data Type|Description|
|---|---|---|
|video_id {Required}|string| ID of the video that is created under the creator account. <br><br>The video needs to meet any of the following requirements: <ul><li>The video has been uploaded and tagged as branded content, and the creator has linked the video to a TTCM order via the invite link (`invite_link`) or campaign code (`campaign_code`) created via [/tcm/order/create/v2/](https://business-api.tiktok.com/portal/docs?id=1747285331289089). <ul><li>You can get `video_id` via [/tcm/order/get/v2/](https://business-api.tiktok.com/portal/docs?id=1755415792685057) or [/tcm/report/get/v2/](https://business-api.tiktok.com/portal/docs?id=1752366000747522).</li></ul></li><li>The video has been uploaded and tagged as branded content, and the creator has linked the video to a TTO Creator Marketplace campaign via the invite link (`invite_link`) created via [/tto/tcm/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1815693549459458).<ul><li>You can get `video_id` via [/tto/tcm/campaign/](https://business-api.tiktok.com/portal/docs?id=1815693562720258) or [/tto/tcm/report/](https://business-api.tiktok.com/portal/docs?id=1815693573340162).</li></ul></li></ul>|
|tcm_account_id {Required}|string|TikTok Creator Marketplace account ID or the ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the TTO Creator Marketplace accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146).|
```
 ### Example

```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tcm/tt_video/status/?tcm_account_id={{tcm_account_id}}&video_id={{video_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
 
## Response
 
```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The return code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| Unique ID of the request.|
|data |object|Returned data.|
#| video_id | string | Video ID. |
#| auth_status | string | The status of the authorization. <br><br>Enum values: <ul><li>`WAITING`: The creator has not rejected or approved your request for authorization.<br>  The creator will receive a notification on their TikTok app regarding your request and can approve or reject it through the System Notification page within the app. Even if the creator has enabled "Ad authorization" in the "Branded content and ad" settings, the `auth_status` will remain `WAITING` until the creator approves your request. However, in this case, you can receive an `auth_code` that can be used to grant video authorization to an advertiser account.</li><li>`REJECTED`: The creator has rejected your request for authorization.</li><li>`ACCEPTED`: The creator has approved your request for authorization. </li></ul>|
#| auth_status_updated_time | string | The time when `auth_status` was last updated. <br><br>For example, when `auth_status` changed from `WAITING` to `ACCEPTED`. |
#| num_remaining_request | number | Returned only for videos linked to TTCM orders.<br><br>The number of times left for you to request Spark Ads authorization. You have 30 times left at most to request authorization before the request is approved or rejected. |
#| requested_authorization_days | number | The validity period (in days) that you request for spark ads authorization or the new validity period you want to update for the approved authorization. <br><br>Enum values:  `7`, `30`, `60`, `365`.  <br><br>Default value: `30`. <br><br><p><span style="color:darkred"><b>Note</b></span>: The actual validity period (`auth_code_end_time` - `auth_code_start_time`) can be different from the validity period you request, because the creator can choose any validity period (`7`，`30`，`60`，`365`) when approving the request. |
#| auth_code | string |  Returned only when `auth_status` is `ACCEPTED`.<br><br>Authorization code to boost the video in the order. |
#| auth_code_start_time | string |  Returned only when `auth_status` is `ACCEPTED`.<br><br>The UTC+0 date and time when the authorization code becomes valid. |
#| auth_code_end_time | string |  Returned only when `auth_status` is `ACCEPTED`.<br><br>UTC+0 date and time when the authorization code expires. |
#| auth_code_status | string | Returned only when `auth_status` is `ACCEPTED`.<br><br>The status of the authorization code. <br><br>Enum values: <ul><li>`NOT_USED`: The authorization code has been generated but has not been used yet.</li><li>`IN_USE`: The authorization code has been generated and is in use now. </li><li>`EXPIRED`: The authorization code has been generated and has expired.</li><li>`DELETED`: The authorization code has been deleted.</li></ul>|
```

### Example
#### When authorization request is pending
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "auth_status": "WAITING",
        "auth_status_updated_time": "{{auth_status_updated_time}}",
        "requested_authorization_days": 30,
        "video_id": "{{video_id}}"
    }
}
(/code)
```
#### When authorization request is accepted
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "auth_code": "{{auth_code}}",
        "auth_code_end_time": "{{auth_code_end_time}}",
        "auth_code_start_time": "{{auth_code_start_time}}",
        "auth_code_status": "NOT_USED",
        "auth_status": "ACCEPTED",
        "auth_status_updated_time": "{{auth_status_updated_time}}",
        "requested_authorization_days": 30,
        "video_id": "{{video_id}}"
    }
}
(/code)
```
#### When authorization request is rejected
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "auth_status": "REJECTED",
        "auth_status_updated_time": "{{auth_status_updated_time}}",
        "requested_authorization_days": 30,
        "video_id": "{{video_id}}"
    }
}
(/code)
```
