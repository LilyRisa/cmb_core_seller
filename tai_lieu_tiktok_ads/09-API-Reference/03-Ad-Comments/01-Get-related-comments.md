---
title: "Get related comments"
doc_id: 1738086824859650
path: "API Reference / Ad Comments / Get related comments"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get related comments

Use this endpoint to get related comments. 

For an original comment, you can get the comment itself and all of its replies. For a reply, you can get the reply and its original comment.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/comment/reference/|/v1.3/comment/reference/|
|Request parameter data type|`advertiser_id`: number<br>`comment_id`: number<br>`original_comment_id`: number|`advertiser_id`: string<br>`comment_id`: string<br>`original_comment_id`: string|
|Response parameter name|`list`<br>`is_top`|`comments`<br>`is_pinned`|
|Response parameter data type|`comment_id`: number|`comment_id`: string|
|Response parameter name and data type|`item_id`: number |`tiktok_item_id`: string|
|New response parameter|/|`user_id`|

```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/comment/reference/

**Method** GET

**Header**

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|--- |--- |--- |
|advertiser_id {Required}|string| Advertiser ID.|
|comment_id {Required}|string| Comment ID. |
|comment_type {Required}  |string| Comment type. It can be `COMMENT`, which is an original comment, or `REPLY`, which is a reply to an original comment. |
|original_comment_id |string| ID of the original comment. A value will be returned only when `comment_type` is REPLY`.|
|page_size |number| Page size. <br><br>Value range: 1-1000. <br><br>Default value: `10`.|
|page |number| Current page number. <br><br>Value range: ≥ 1. <br><br>Default value: 1.|
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "comment_id=COMMENT_ID" \
--data-urlencode "page_size=PAGE_SIZE" \
--data-urlencode "original_comment_id=COMMENTAL_COMMENT_ID" \
--data-urlencode "comment_type=comment_type" \
--data-urlencode "page=PAGE" \
https://business-api.tiktok.com/open_api/v1.3/comment/reference/
(/code)
```

## Response

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|message|string|The return message. For Details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For details, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|The returned data.|
#|comments |object[]| List of comments. |
##|comment_id |string| Comment ID. |
##|content |string| Comment content. |
##|comment_type |string| Comment type. It can be `COMMENT`, which is an original comment, or `REPLY`, which is a reply to an original comment. |
##|comment_status |string| Comment status. Enum values: `HIDDEN`, `PUBLIC`. |
##|hit_blockedword |boolean| Whether the comment contains blocked words. |
##|create_time |string| The time when the comment is created.|
##|user_name|string| User who made the comment.|
##| user_id | string | The ID of the TikTok user who made the comment. <br> <br>Example: `"6123456789123456789"`. |
##|user_avatar_url|string|URL to the users' avatar.|
##|tiktok_item_id|string|TikTok video ID.|
##|identity_id|string|Identity ID.|
##|identity_type|string|Identity type. `CUSTOMIZED_USER` (Customized User), `TT_USER` (TikTok Business Account User). For details about identities, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097).|
##|is_pinned|boolean| Whether the comment is pinned.|
##|can_delete|boolean|Whether the comment can be deleted.|
##|is_auth_ttba|boolean|Whether the user is linked to a TikTok Business Account.|
##|is_auth_comment_manage_scope|boolean|Whether the user has the comment management permission.|
##|reply_user_info|object| Information about the user who posted the original comment. Valid only for replies (second-level or lower comments). Returns null for first-level comments.|
#|page_info |object| Page information. |
##|page |number| Current page number. |
##|page_size |number| Page size. |
##|total_number |number| Total number of results. |
##|total_page |number| Total number of pages. |
|request_id |string|Unique ID of the request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code);
```

