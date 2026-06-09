---
title: "Extend the authorization validity period of a TikTok post"
doc_id: 1815051784582210
path: "API Reference / Accounts / Ad authorization / Extend the authorization validity period of a TikTok post"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Extend the authorization validity period of a TikTok post

Use this endpoint to extend the validity period of an existing authorization code of a TikTok post.

><span style="color:DodgerBlue">**Note**</span><br> 
> If the authorization code of a TikTok post is deleted, you can use this endpoint to generate a new authorization code for the post.

## Before you start
Ensure that the "Ad authorization" setting is enabled for the TikTok post. Otherwise, an error will occur.

To enable the "Ad authorization" setting for a TikTok post, use [/business/post/authorize/setting/](https://business-api.tiktok.com/portal/docs?id=1815051741912066) and set `is_ad_promotable` to `true`.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/post/authorize/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators.<br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| business_id {Required} | string | Application specific unique identifier for the TikTok account. <br><br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) to this field.|
| item_id {Required} | string | The ID of the TikTok post.<br><br> To obtain the public posts of a TikTok account, use [/business/video/list/](https://business-api.tiktok.com/portal/docs?id=1762228421622786). <br><br> Example: `7123456789123456789`. |
| authorization_days | number |<ul><li>If the post already has an authorization code, this field represents the authorization extension period, which is an additional amount of time you can add on to your existing Ad authorization period. <ul><li>For instance, if the existing authorization period of a TikTok post is 180 days and you set this field to `180`, the authorization period of the post will be extended to 360 days.</li></ul></li><li>If the authorization code of a TikTok post is deleted, this field represents the validity period of the new authorization code.</li></ul><br>Supported values: `7`, `30`, `60`, `180`, `365`. <br>Default value: `30`. |
```


### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/post/authorize/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "item_id": "{{item_id}}",
    "authorization_days": 180
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
#| item_id | string | The ID of the TikTok post. <br><br>Example: `7123456789123456789`. |
#| auth_code | string | The authorization code for the TikTok post. <br><br> Example: `#1Ea2bcde3fUM/ghijEk/L4mOceft5deFRq6za7JsgnAJ8PB9n1XIr1rC1b/5Rw4=`. |
#| auth_code_start_time | string | The time when the authorization code becomes valid, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0). <br><br>Example: `2024-01-01 00:00:00`. |
#| auth_code_end_time | string | The time when the authorization code expires, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0). <br><br>Example: `2024-01-01 00:00:00`. |
#| authorization_days | number | The authorization validity period in days. <br><br>Example: `30`.  |
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
        "auth_code": "{{auth_code}}",
        "auth_code_end_time": "2025-10-31 12:58:42",
        "auth_code_start_time": "2024-11-05 12:58:42",
        "authorization_days": 360,
        "item_id": "{{item_id}}"
    }
}
(/code)
```

