---
title: "Get all replies to a comment"
doc_id: 1762228430145538
path: "API Reference / Accounts / Comments / Get all replies to a comment"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get all replies to a comment

Use this endpoint to access all replies to a specific comment (along with related information) - both public and hidden - that have been created against a comment on an organic video posted by an owned TikTok Account.

><span style="color:DodgerBlue">**Note**</span><br>
>  <ul><li>`/business/comment/reply/list/`currently does not support getting replies to a hidden comment. </li><li>The comment webhook event [comment.update](https://business-api.tiktok.com/portal/docs?id=1810515104773122) enables you to get notifications within five minutes of a comment reply being created, deleted, or the reply visibility settings being modified on any public video associated with an owned TikTok account. To subscribe to the comment webhook, see [Subscribe to the comment update event](https://business-api.tiktok.com/portal/docs?id=1810521730276354#item-link-Subscribe%20to%20the%20comment%20update%20event).</li></ul>

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/business/comments/replies/list/|/v1.3/business/comment/reply/list/|
|Method|POST |GET|
|New response parameters| / |`unique_identifier`<br>`parent_comment_id`<br>`display_name`<br>`image_url` |
```


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/comment/reply/list/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators.<br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041).|
```
**Parameters**
Pass params as url parameters.
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| business_id {Required} | string | Application specific unique identifier for the TikTok account. <br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) to this field.|
| video_id {Required} | string | Unique identifier for owned TikTok video to list comments on. This can be accessed via the [/business/video/list/](https://ads.tiktok.com/marketing_api/docs?id=1762228421622786) endpoint as the `item_id` for each video listed. |
| comment_id {Required} | string | Unique identifier for comment on an owned TikTok video to list replies on. <br>This can be accessed via the [/business/comment/list/](https://ads.tiktok.com/marketing_api/docs?id=1760232109619202) endpoint as the `comment_id` for each comment listed. |
| status | string | Enumerated status of comment reply visibility. <br><br><strong>Enum values</strong>: <ul><li>`PUBLIC`: Comment replies are publicly viewable by all TikTok users.</li><li>`ALL`: Comment replies are both hidden from view as well as publicly viewable by all TikTok users. </li></ul><br><strong>Default value</strong>: `ALL`. |
| sort_field | string | Specific field to sort comment replies by. <br><br><strong>Enum values</strong>: <ul><li>`likes`: Sort comment replies by the number of likes in the specified `sort_order`. </li><li>`replies`: Sort comment replies by the number of replies in the specified `sort_order`.</li><li>`create_time`: Sort comment replies chronologically in the specified `sort_order`.</li></ul>By default, the comment replies will be returned in a random order. |
| sort_order | string | Specific field to sort comment replies by. <br><br> <strong>Enum values</strong>: <ul><li>`asc`: Sort comment replies in ascending order by the specified `sort_field`.</li><li> `desc`: Sort comment replies in descending order by the specified `sort_field`.</li></ul><br>By default, the comment replies will be sorted according to semantics. |
| cursor | integer | Cursor for pagination. If the response parameter `has_more` is true, pass the returned cursor in this parameter in the subsequent request to receive the next page of results. <br><br><strong>Default value</strong>: 0. |
| max_count | integer | The maximum number of comment replies that will be returned for each page of data. <br><br> <strong>Default value</strong>: 20. <br><br><strong>Minimum value</strong>: 0. <br><br><strong>Maximum value</strong>: 30. <br><br><p><span style="color:darkred"><b>Note</b></span>: Due to our trust and safety policies, it is possible that the endpoint returns less than the `max_count` number of comments even if the response parameter `has_more` is true. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/comment/reply/list/?business_id={{business_id}}&video_id={{video_id}}&comment_id={{comment_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response
**Header**

These are important headers to record for issue reports and troubleshooting. This is not an exhaustive list of response headers.

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---	|
| Date 	| string 	| Date and time (GMT) when the response was received. <br><strong>Example</strong>: Fri, 13 Aug 2021 08:04:42 GMT 	|
| X-Tt-Logid 	| string 	| Unique identifier for the API request. 	|
```
**Response Body**
``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
| request_id | string | Unique identifier for the API request. <br><br>Please record this field for all API requests. Important for issue reports and troubleshooting. |
| code | Integer | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
| message | string | Response message. For details, see [Appendix - Return Information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
| data | object | Returned data. |
#|comments | object[] | List of current page of replies to the specified comment created on the specified video. |
##|comment_id | string | Unique identifier for the reply. <br><br><strong>Example</strong>: 6990565363377392901. |
##|parent_comment_id| string | The ID of the comment to which the reply (`comment_id`) is directed.<br><br><strong>Example</strong>: 6990565363377392901.|
##|video_id| string | Unique identifier for the owned video the reply was created on. <br><br><strong>Example</strong>: 6990565363377392901. |
##|user_id{-To-be-deprecated}| string | Developer application and TikTok account scoped unique identifier for the user that made the reply. <br><br><strong>Example</strong>: ce9b7be629b0447af777a0ad945783f61b7f790982b49fbda04a23b4722eaffe.<br><br><p><span style="color:darkred"><b>Note</b></span>: This field will be deprecated in the next API version. We recommend using `unique_identifier` to identify the TikTok user because the identifier remains consistent across different APIs. |
##| unique_identifier | string | A globally unique identifier assigned to each user commenting. This identifier remains consistent across different APIs, facilitating cross-referencing and integration.<br><br><b>Example</b>: +ABc1D2/E0fGhijklMNOrD34vDW/zDiZUNRH5Jyahue6OcA7b8ZpwnqXV9u1Uh+k. |
##|create_time | string | Unix/Epoch date-time when the reply was created. <br><br><strong>Example</strong>: 1627617835. |
##|text | string | Text content of the reply. <br><br> <strong>Example</strong>: "This is a reply #reply 🙂". 
##|likes | integer | Number of likes the reply has received. <br><br><strong>Example</strong>: 123. |
##|replies | integer | Number of replies the reply has received.  <br><br><strong>Example</strong>: 123. |
##|owner | boolean | Whether the current application user owns the reply.<br><br><strong>Example</strong>: `true`. |
##|liked | boolean | Whether the current application user likes the reply. <br><br><strong>Example</strong>: `true`. |
##|pinned | boolean | Whether the current application user pinned the reply. <br><br><strong>Example</strong>: `true`. |
##|status | string | The visibility status of the reply. <br><br><strong>Possible values</strong>: <ul><li>`HIDDEN`: The reply is hidden from public viewership.</li><li> `PUBLIC`: The reply can be viewed by all users.</li></ul><br><strong>Example</strong>: `PUBLIC`.|
##|username | string | The username of the TikTok user who made the reply.. <br><br><strong>Example</strong>: feather_in_the_w1nd. |
##| display_name | string | The display name of the TikTok user who made the reply. <br><br> <strong>Example</strong>: Feather in the wind |
##|profile_image| string | The avatar link for the account. It will expire at the x-expires Unix/Epoch date-time in the link. <br><br> <strong>Example</strong>: https://p16-sign-va.tiktokcdn.com/tos-maliva-avt-0068/188d468fc1de3f328250ea0c96e98f8b~c5_168x168.jpeg?x-expires=1651687200&x-signature=AAiHg70%2Bnj9iUipTuQWodRmk0qA%3D. |
##| image_url | string | A URL to an image in the comment.<br><br>Returned if the comment is an image.<br><br>The URL won't expire.|
#|cursor | integer | Cursor for the next page of results, to be passed in the cursor query parameter of the subsequent API request. |
#|has_more | boolean | Whether an additional page of data is available. |
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
        "cursor": 2,
        "has_more": false,
        "comments": [
            {
                "profile_image": "{{profile_image}}",
                "user_id": "{{user_id}}",
                "text": "{{text}}",
                "likes": 0,
                "username": "{{username}}",
				"display_name": "{{display_name}}",
                "parent_comment_id": "{{parent_comment_id}}",
                "owner": true,
                "status": "PUBLIC",
                "liked": false,
                "video_id": "{{video_id}}",
                "unique_identifier": "{{unique_identifier}}",
                "create_time": "{{create_time}}",
                "comment_id": "{{comment_id}}"
            },
            {
                "profile_image": "{{profile_image}}",
                "user_id": "{{user_id}}",
                "text": "{{text}}",
                "likes": 0,
                "username": "{{username}}",
				"display_name": "{{display_name}}",
                "parent_comment_id": "{{parent_comment_id}}",
                "owner": true,
                "status": "PUBLIC",
                "liked": false,
                "video_id": "{{video_id}}",
                "unique_identifier": "{{unique_identifier}}",
                "create_time": "{{create_time}}",
                "comment_id": "{{comment_id}}"
            }
        ]
    }
}
(/code)
```

