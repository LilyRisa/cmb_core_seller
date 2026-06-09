---
title: "Like/unlike an existing comment on an owned video"
doc_id: 1762228456608770
path: "API Reference / Accounts / Comments / Like/unlike an existing comment on an owned video"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Like/unlike an existing comment on an owned video

Use this endpoint to like or unlike an existing comment on an organic video posted by an owned TikTok Account.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/business/comments/like/|/v1.3/business/comment/like/|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/comment/like/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators. <br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{20%}|Data Type{10%}| Param Location{15%}|Description{55%}|
|--- |--- |--- |--- |
| business_id {Required} | string | Body | Application specific unique identifier for the TikTok account. <br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) to this field.|
| comment_id {Required} | string | Body |Unique identifier for comment on an owned TikTok video to like/unlike. This can be accessed via the [/business/comment/list/](https://ads.tiktok.com/marketing_api/docs?id=1760232109619202) endpoint as the `comment_id` for each comment listed. |
|action {Required} | string | Body | Specific operation to be performed on the comment - like or unlike. <br><br><strong>Enum values</strong>: `LIKE`, `UNLIKE`. <br><br><strong>Example</strong>: `LIKE`. |
```
### Example
```xcodeblock
(code curl http)
curl -L -X POST 'https://business-api.tiktok.com/open_api/v1.3/business/comment/like/' \
-H 'Content-Type: application/json' \
-H 'Access-Token: 1234a16d2e08c3f17d1984a1be07d00406p3LIAF7vEpxliox8GRpCINv70x' \
--data-raw '{
  "business_id" : "b3d2f73d-4b8c-47fb-85c3-cd571287754b",
  "comment_id" : "b3d2f73d-4b8c-47fb-85c3-cd571287754b",
  "action": "like"
}'
(/code)
```
## Response
**Header**

These are important headers to record for issue reports and troubleshooting. This is not an exhaustive list of response headers.

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| Date | string | Date and time (GMT) when the response was received. <br><strong>Example</strong>: Fri, 13 Aug 2021 08:04:42 GMT |
| X-Tt-Logid | string | Unique identifier for the API request. |
```
**Body**
``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
| request_id | string | Unique identifier for the API request. <br><br>Please record this field for all API requests. Important for issue reports and troubleshooting. |
| code | Integer | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message| string | Response message. For details, see [Appendix - Return Information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
| data | object | Returned data. <br>Empty object (`{}`) for successful actions. |
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
  "code": 0,
  "message": "Ok",
  "request_id": "20210817034316010245031056097316BA",
  "data": {}
}
(/code)
```


