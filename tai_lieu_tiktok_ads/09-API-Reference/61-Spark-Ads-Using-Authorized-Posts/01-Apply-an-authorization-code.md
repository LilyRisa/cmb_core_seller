---
title: "Apply an authorization code"
doc_id: 1738376435339265
path: "API Reference / Spark Ads Using Authorized Posts / Apply an authorization code"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Apply an authorization code

Use this endpoint to apply an authorization code. After you get the authorization code from the post owner, the authorization is not automatically in effect. You need to apply the code to connect the ad account with the Spark Ads post.

If a post is a duet or stitch from another post or it mentions another video, you need to get authorization from the owners of both posts before you can promote the post. Use `auth_code` to specify the authorization code for the post, and use `original_post_auth_code` to specify the authorization code for the original post.

## Before you start
Ensure that you have obtained an authorization code from the creator (TikTok post owner). 
You can ask the creator to generate the authorization code by following these steps:
1. Open the TikTok app and tap on the three dots on the post you want to share.
2. In the share drawer, tap Ad settings.
3. Toggle on Ad authorization.
4. Select an authorization duration.
5. Generate and copy the authorization code.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/tt_video/authorize/| /v1.3/tt_video/authorize/|
|Request parameter name|`from_auth_code` |`original_post_auth_code`|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
```

## Request

**Endpoint**  
https://business-api.tiktok.com/open_api/v1.3/tt_video/authorize/

**Method** POST

**Header**

``` xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
|Content-Type {required}|string| The content type of the request.<br data-tomark-pass>Allowed value: `"application/json"`  |
```

**Parameters**

``` xtable
|Field|Type|Description|
|---|---|---|
|advertiser_id {required}|string| Advertiser id |
|auth_code {required}|string| The authorization code for the Spark Ads post. <br><br><p><span style="color:darkred"><b>Note</b></span>: Replace "+" in your authorization code with "%2B". Otherwise, you may get an invalid authorization code error. For example, you can replace the "+" in the code "#XtjblzbEuOGK/BvHj4V/K0i71JL3Wu2fgRtOJrZSSf4SrPAedegtn+IH+UXr3f4=" and specify "#XtjblzbEuOGK/BvHj4V/K0i71JL3Wu2fgRtOJrZSSf4SrPAedegtn%2BIH%2BUXr3f4=".|
|original_post_auth_code |string| If this post is a duet or stitch from another post or it mentions another video, you also need to get the authorization code for the original post. Use this field to specify the authorization code for the original post.<br> Note: Currently, the post can only mention at most one video.|

```

### Example

``` xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID", 
    "auth_code": "AUTH_CODE"
}' \
https://business-api.tiktok.com/open_api/v1.3/tt_video/authorize/
(/code)
```

## Response

``` xtable
|Field|Type|Description|
|-|-|-|
|message|string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|The returned data|
|request_id |string|The unique log ID of the request.  |
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code);
```
