---
title: "Get a TikTok in-app link"
doc_id: 1738457335681026
path: "API Reference / Tools / Get a TikTok in-app link"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get a TikTok in-app link

Use this endpoint to get the corresponding TikTok in-app link for an open TikTok URL. The in-app link you get can be passed to the `deeplink` field when creating or updating a Reach & Frequency ad.

> <span style="color:DodgerBlue">**Note**</span><br>TikTok In-app links is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/tools/open_url/|/v1.3/tool/open_url/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/open_url/

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {required}|string| Advertiser ID |
|url {required}|string| Open URL that you want to get the internal link for. |
|url_type {required}| string| Type of the open TikTok URL. Enum: <ul><li>`USER_PROFILE`</li><li>`VIDEO`: Video detail page</li><li>`HASHTAG_CHALLENGE`: Hashtag challenge page</li><li>`MUSIC`: Music page</li><li>`STICKER`: Sticker (special effect) page</li><li>`STICKER_SHOOTER`: Shoot a video with a sticker (special effect).</li></ul>|
```


### Example

```xcodeblock
(code CURL CURL)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/tool/open_url/?advertiser_id={{advertiser_id}}&url={{url}}&url_type=USER_PROFILE' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The return code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.|
|data |object|Returned data|
#|open_url|string| TikTok internal link.|
#|supported_regions|string[]| Countries and/or regions that the sticker (special effect) can be used. Returned when `url_type` is `STICKER` or `STICKER_SHOOTER`. |
```

### Example
```xcodeblock
(code JSON JSON)
{
    "code": 0,
    "message": "OK",
    "request_id": "2021090212163301024502410742C587F8",
    "data": {
        "open_url": "sslocal://user/profile/60320267633123456?sec_uid=MS4wLjABAAAAzUKwYoagO3vLvQVSeMxAFUc5INu7rqzoCFmlOYtAtbd9Rah9FpoMg9wpr8QdkdIf"
    }
}
(/code)
```


