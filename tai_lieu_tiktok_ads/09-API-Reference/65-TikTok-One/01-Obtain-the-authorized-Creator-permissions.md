---
title: "Obtain the authorized Creator permissions"
doc_id: 1765926674648065
path: "API Reference / TikTok One / Obtain the authorized Creator permissions"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Obtain the authorized Creator permissions

Use this endpoint to get the permission scopes of the TikTok account of a TTCM (TikTok Creator Marketplace) Creator, which is a TikTok Personal Account, or a TikTok Business Account that are authorized by the TikTok account user. 

If you want to get the permission scopes of a TikTok account authorized by the TikTok account user, see [here](https://business-api.tiktok.com/portal/docs?id=1765927978092545).

## Request


**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tt_user/token_info/get/

**Method** POST

**Header**
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| app_id {Required} | string | ID of your developer application. You can find the <strong>App ID</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
|access_token {Required}|string|Authorized access token for permission verification.<br>To get an access token that is generated after a TTCM creator grants you the permission scopes of a TikTok creator account, see [Get or renew an access token](https://ads.tiktok.com/marketing_api/docs?id=1737586583649281).|
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tt_user/token_info/get/'
--header 'Content-Type: application/json'
--data-raw '{
    "app_id":"{{app_id}}",
    "access_token":"{{access_token}}"
}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The return code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The log id of a request, which uniquely identifies the request.|
|data |object|Returned data.|
#|app_id |string |ID of your developer application. |
#|scope|string|Scope of permissions for the specified access token.<br>For instance, `"user.info.basic,video.list"`.|
#|creator_id|string|Application specific unique ID of the TikTok creator account.<br><p><span style="color:darkred"><b>Note</b></span>: You can pass the value of this field to the parameter `creator_id` in the request of three TikTok Creator Marketplace endpoints:  [/tcm/creator/authorized/](https://ads.tiktok.com/marketing_api/docs?id=1735827374196738), [/tcm/order/update/creator/](https://ads.tiktok.com/marketing_api/docs?id=1739934833185793), and [/tcm/creator/authorized/video/list/](https://ads.tiktok.com/marketing_api/docs?id=1735827480228865).|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "app_id": "{{app_id}}",
        "creator_id": "{{creator_id}}",
        "scope": "video.publish,user.info.basic,video.list,user.insights,user.insights.creator,tcm.order.update,comment.list.manage,video.insights,comment.list"
    }
}
(/code)
```

