---
title: "Reply to a comment"
doc_id: 1738957702411265
path: "API Reference / Ad Comments / Reply to a comment"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Reply to a comment

Use this endpoint to reply to a comment. 

For TikTok ads that are created by users linked to TikTok User or Custom User types of identities (`identity_type` = `TT_USER` or `CUSTOMIZED_USER`), users who are linked to these two types of identities and have comment management permissions can reply to first-level comments under the ads.


## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
| Endpoint path | /v1.2/comment/post/ | /v1.3/comment/post/ |
| Request parameter data type | `advertiser_id`: number <br>`comment_id`: number<br> `ad_id`: number | `advertiser_id`: string<br> `comment_id`: string<br> `ad_id`: string|
| Request parameter name and data type |`item_id`: number |`tiktok_item_id`: string |
| Response parameter data type | `comment_id`: number<br> `reply_to_comment_id`: number | `comment_id`: string<br> `reply_to_comment_id`: string |
| Response parameter name and data type |`item_id`: number |`tiktok_item_id`: string |
| Response parameter deprecated in v1.3 |/ |`identity_type`(in data) |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/comment/post/

**Method** POST

**Header**

``` xtable
|Field{30%}|Data Type{35%}|Description{35%}|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type{Required}|string|The content type of the request. <br>Allowed value: `"application/json"`.|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID |
| ad_id {Required} | string | Ad ID |
| tiktok_item_id {Required} | string | TikTok video ID |
| comment_id {Required} | string | ID of the comment that you want reply to |
| comment_type {Required} | string | Comment type. Currently, only `REPLY` is supported. |
| text {Required} | string | Comment text. |
| identity_type {Required} | string | Type of the identity that are linked to the user. <br><br>Enum values: `CUSTOMIZED_USER` (Custom User), `TT_USER` (TikTok User). <br><br>For details about identity types, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097). |
| identity_id {Required} | string | Identity ID |
```

### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID",
    "ad_id": "AD_ID",
    "tiktok_item_id": "TIKTOK_ITEM_ID",
    "comment_id": "COMMENT_ID",
    "comment_type": "COMMENT_TYPE",
    "text": "TEXT",
    "identity_type": "IDENTITY_TYPE",
    "identity_id": "IDENTITY_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/comment/post/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#| comment_id | string | ID of the comment or reply that has just been created. |
#|tiktok_item_id | string | ID of the TikTok video |
#| text | string | Comment or reply text that you just created |
#| create_time | string | Time when your comment or reply was created (UTC+0), in the format of `YYYY-MM-DD HH:MM:SS`. |
#| reply_to_comment_id |string| ID of the comment that you replied to |
|request_id|string|The log id of the request, which uniquely identifies a request. |
```


### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "comment_id": "7122941661025993518",
        "text": "Hello word",
        "reply_to_comment_id": "7122941661025993510",
        "item_id": 0,
        "create_time": "2022-06-07"
	}
}
(/code)
```
