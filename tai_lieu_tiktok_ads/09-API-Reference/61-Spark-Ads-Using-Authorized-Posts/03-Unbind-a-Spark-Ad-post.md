---
title: "Unbind a Spark Ad post"
doc_id: 1738376509936641
path: "API Reference / Spark Ads Using Authorized Posts / Unbind a Spark Ad post"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Unbind a Spark Ad post

Use this endpoint to unbind a Spark Ad post after the authorization has expired or has been revoked. To unbind a Spark Ad post is to remove the post from the list of authorized Spark Ad posts for the ad account.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/tt_video/unbind/| /v1.3/tt_video/unbind/|
|Request parameter data type |`advertiser_id`: number <br> `item_id`:number |`advertiser_id`: string <br> `item_id`:string|
```

## Request

**Endpoint**  
https://business-api.tiktok.com/open_api/v1.3/tt_video/unbind/

**Method** POST

**Header**

``` xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {required}|string| The content type of the request.<br>Allowed value: `"application/json"`  |
```

**Parameters**

``` xtable
|Field|Type|Description|
|--- |--- |--- |
|advertiser_id {required}|string|The advertiser ID|
|item_id {required}|string| The ID of the Spark Ads post |
```

### Example

``` xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID", 
    "item_id": "ITEM_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/tt_video/unbind/
(/code)
```

## Response

``` xtable
|Field|Type|Description|
|---|---|---|
|message|string|The return message. For details, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
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

