---
title: "Get the authorization status of a TikTok post"
doc_id: 1815051801257986
path: "API Reference / Accounts / Ad authorization / Get the authorization status of a TikTok post"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the authorization status of a TikTok post

Use this endpoint to get the authorization status of a TikTok post.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/post/authorize/status/

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
| item_id {Required} | string | The ID of the TikTok post.<br><br> To obtain the public posts of a TikTok account, use [/business/video/list/](https://business-api.tiktok.com/portal/docs?id=1762228421622786). <br><br> Example: `7123456789123456789`.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the TikTok post doesn't have an authorization code, either because the code is not generated or is deleted, an error will occur.  |
```


### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/post/authorize/status/?business_id={{business_id}}&item_id={{item_id}}' \
--header 'Access-Token: {{Access-Token}}' \
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
#| auth_code_status | string | The status of the authorization code. <br><br>Enum values:<ul><li>`NOT_USED`: The authorization code has been generated but has not been used yet.</li><li>`IN_USE`: The authorization code has been generated and is in use now.</li><li> `EXPIRED`: The authorization code has been generated and has expired. </li></ul>|
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
        "auth_code_end_time": "2024-12-07 00:21:37",
        "auth_code_start_time": "2024-10-31 00:21:37",
        "auth_code_status": "NOT_USED",
        "authorization_days": 37,
        "item_id": "{{item_id}}"
    }
}
(/code)
```
