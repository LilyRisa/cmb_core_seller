---
title: "Get the post privacy settings of a TikTok account"
doc_id: 1816387951979521
path: "API Reference / Accounts / Insights / Get the post privacy settings of a TikTok account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the post privacy settings of a TikTok account

Use this endpoint to obtain the post privacy settings of a TikTok account.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/video/settings/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators.<br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| business_id {Required} | string | Application specific unique identifier for the TikTok account. <br><br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) to this field.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/video/settings/?business_id={{business_id}}' \
--header 'Access-Token: {{Access-Token}}'
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
#| privacy_level_options | string[] | The available privacy level options for the TikTok account's posts. <br><br><ul><li>If the TikTok account is a public account, the enum values are:<ul><li>`PUBLIC_TO_EVERYONE`: Everyone (people on or off TikTok).</li><li>`MUTUAL_FOLLOW_FRIENDS`: Friends (followers you follow back).</li><li>`SELF_ONLY`: Only you.</li></ul></li><li>If the TikTok account is a private account, the enum values are:<ul><li>`FOLLOWER_OF_CREATOR`: Followers (Visible only to followers on your private account).</li><li>`MUTUAL_FOLLOW_FRIENDS`: Friends (followers you follow back).</li><li>`SELF_ONLY`: Only you.</li></ul></li></ul> |
#| comment_disabled | boolean | Whether the "Allow comments" setting is available for the TikTok account's posts. <br><br>Supported values: <ul><li> `true`: unavailable. You cannot enable the setting for the TikTok account's posts. <ul><li>If you have set the comment privacy settings for your TikTok account as allowing comments from <b>No one</b>, this field will be `true`.</li></ul></li><li> `false`: available. You can either enable or disable the setting for the TikTok account's posts.</li></ul>|
#| duet_disabled | boolean | Whether the "Allow Duets" setting is available for the TikTok account's posts. <br><br> Supported values: <ul><li>`true`: unavailable. You cannot enable the setting for the TikTok account's posts.<ul><li>If you have set the Duet privacy settings for your TikTok account as allowing Duet from <b>Only you</b> or if your TikTok account is a private account, this field will be `true`.</li></ul></li><li> `false`: available. You can either enable or disable the setting for the TikTok account's posts. </li></ul> |
#| stitch_disabled | boolean | Whether the "Allow Stitch" setting is available for the TikTok account's posts. <br><br> Supported values: <ul><li>`true`: unavailable. You cannot enable the setting for the TikTok account's posts.<ul><li>If you have set the Stitch privacy settings for your TikTok account as allowing Stitch from <b>Only you</b> or if your TikTok account is a private account, this field will be `true`.</li></ul></li><li>`false`: available. You can either enable or disable the setting for the TikTok account's posts.</li></ul>|
#| max_video_post_duration_sec | number | The maximum duration for video posts that the TikTok account can publish in seconds. <br><br>If you attempt to publish a video post longer than the maximum duration via [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234), the publishing attempt will fail. |
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
        "comment_disabled": false,
        "duet_disabled": false,
        "max_video_post_duration_sec": 600,
        "privacy_level_options": [
            "PUBLIC_TO_EVERYONE",
            "MUTUAL_FOLLOW_FRIENDS",
            "SELF_ONLY"
        ],
        "stitch_disabled": false
    }
}
(/code)
```
