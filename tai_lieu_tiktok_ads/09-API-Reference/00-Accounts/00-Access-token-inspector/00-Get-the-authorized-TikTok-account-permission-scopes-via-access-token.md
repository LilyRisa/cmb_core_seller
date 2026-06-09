---
title: "Get the authorized TikTok account permission scopes via access token"
doc_id: 1765927978092545
path: "API Reference / Accounts / Access token inspector / Get the authorized TikTok account permission scopes via access token"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the authorized TikTok account permission scopes via access token

Use this endpoint to get the permission scopes of a TikTok Business Account or a TikTok Personal Account that are authorized by the TikTok account user. 

If you want to get the permission scopes of a TikTok creator account (which is a TikTok Personal Account) authorized by a creator on TikTok Creator Marketplace, see [here](https://ads.tiktok.com/marketing_api/docs?id=1765926674648065).

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
| app_id {Required} | string | ID of your developer application. <br><br>You can find the <strong>App ID</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
|access_token {Required}|string|Access token authorized by TikTok accounts. <br><br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Obtain%20a%20TikTok%20account%20access%20token).|
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
#|creator_id|string|Application specific unique ID of the TikTok Account.<br><p><span style="color:darkred"><b>Note</b></span>: You can pass the value of this field to the parameter `business_id` in [Accounts API](https://ads.tiktok.com/marketing_api/docs?id=1737944775511041) requests.|
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

