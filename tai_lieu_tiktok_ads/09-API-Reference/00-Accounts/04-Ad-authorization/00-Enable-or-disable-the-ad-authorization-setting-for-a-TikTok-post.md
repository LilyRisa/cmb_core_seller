---
title: "Enable or disable the ad authorization setting for a TikTok post"
doc_id: 1815051741912066
path: "API Reference / Accounts / Ad authorization / Enable or disable the ad authorization setting for a TikTok post"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Enable or disable the ad authorization setting for a TikTok post

Use this to enable or disable the "Ad authorization" setting for a TikTok post.

To learn about how to manage the "Ad authorization" settings for TikTok posts, see [Manage TikTok post ad authorization](https://business-api.tiktok.com/portal/docs?id=1815124785075201).

><span style="color:DodgerBlue">**Note**</span><br> 
>  <ul><li>By turning on "Ad authorization", you acknowledge that you have read and agreed to [the Advertising Content Terms of Service](https://ads.tiktok.com/i18n/official/policy/advertising-terms), and that you allow this post to be promoted on TikTok. Once Ad authorization is turned on, the privacy status of the post will become "Available for Ads" so that the Ad authorization team can review this post. In addition to TikTok, your post may also appear as an ad on third party platforms. </li><li>To delete an authorization code (a video code) or turn off an ad authorization, you must pause all active campaigns or ads associated with this video.   </li><li>To extend the authorization period, use [/business/post/authorize/](https://business-api.tiktok.com/portal/docs?id=1815051784582210).</li></ul>

## Before you start
Obtain a TikTok account access token with `biz.spark.auth` permission and the Application specific unique ID for the TikTok account by following the steps in [Authorization](https://business-api.tiktok.com/portal/docs?id=1738083939371009) and [Authentication](https://business-api.tiktok.com/portal/docs?id=1738084387220481).
  - To confirm whether a TikTok account access token has the `biz.spark.auth` permission, use [/tt_user/token_info/get/](https://business-api.tiktok.com/portal/docs?id=1765927978092545) and check the returned `scope`. If the access token doesn't have the `biz.spark.auth` permission, request the TikTok account owner to approve Spark Ads authorization code management permission through the [Authorization](https://business-api.tiktok.com/portal/docs?id=1738083939371009) workflow.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/post/authorize/setting/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators.<br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| business_id {Required} | string | Application specific unique identifier for the TikTok account. <br><br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) to this field.|
| item_id {Required} | string | The ID of the TikTok post.<br><br> To obtain the public posts of a TikTok account, use [/business/video/list/](https://business-api.tiktok.com/portal/docs?id=1762228421622786). <br><br> Example: `7123456789123456789`. |
| is_ad_promotable {Required} | boolean | Whether to enable the "Ad authorization" setting to allow the post to be promoted. <br><br>Supported values: <ul><li>`true`: To enable the "Ad authorization" setting.</li><li>`false`: To disable the "Ad authorization" setting.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If the TikTok post is being used in Spark Ads, you cannot disable the "Ad authorization" setting for the post.     
| authorization_days | number | The authorization validity period in days.<br><br>Supported values: `7`, `30`, `60`, `180`, `365`. <br>Default value: `30`. |
```

### Example

#### Enable the ad authorization setting
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/post/authorize/setting/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "item_id": "{{item_id}}",
    "is_ad_promotable": true,
    "authorization_days": 365
}'
(/code)
```

#### Disable the ad authorization setting
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/post/authorize/setting/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "item_id": "{{item_id}}",
    "is_ad_promotable": false
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
#| item_id | string | Returned only when `is_ad_promotable` is set to `true` in the request.<br><br> The ID of the TikTok post. <br><br>Example: `7123456789123456789`. |
#| auth_code | string | Returned only when `is_ad_promotable` is set to `true` in the request. <br><br> The authorization code for the TikTok post. <br><br> Example: `#1Ea2bcde3fUM/ghijEk/L4mOceft5deFRq6za7JsgnAJ8PB9n1XIr1rC1b/5Rw4=`. |
#| auth_code_start_time | string | Returned only when `is_ad_promotable` is set to `true` in the request. <br><br>The time when the authorization code becomes valid, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0). <br><br>Example: `2024-01-01 00:00:00`. |
#| auth_code_end_time | string | Returned only when `is_ad_promotable` is set to `true` in the request. <br><br> The time when the authorization code expires, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0). <br><br>Example: `2024-01-01 00:00:00`. |
#| authorization_days | number | Returned only when `is_ad_promotable` is set to `true` in the request. <br><br>The authorization validity period in days. <br><br> Supported values:  `7`, `30`, `60`, `180`, `365`.  |
```

### Example
#### Enable the ad authorization setting
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "auth_code": "{{auth_code}}",
        "auth_code_end_time": "2025-01-01 12:39:35",
        "auth_code_start_time": "2024-01-01 12:39:35",
        "authorization_days": 365,
        "item_id": "{{item_id}}"
    }
}
(/code)
```

#### Disable the ad authorization setting
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
