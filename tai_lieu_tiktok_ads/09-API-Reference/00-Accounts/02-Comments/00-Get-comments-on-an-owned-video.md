---
title: "Get comments on an owned video"
doc_id: 1760232109619202
path: "API Reference / Accounts / Comments / Get comments on an owned video"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get comments on an owned video

Use this endpoint to access all the comments or only the specified comments (along with related information) - both public and hidden - that have been created against a specific organic video posted by an owned TikTok Account.

You can determine whether the returned `comment_id` is for a comment or a reply by checking whether the corresponding `parent_comment_id` is returned. The parent comment (`parent_comment_id`) will only be returned for comment replies.

> <span style="color: dodgerblue">**Note**</span><br> 
> - Hidden comments returned by the endpoint can include those hidden by the video owner, and comments hidden due to moderation actions, user privacy settings, or other system-level filters. 
> - The comment webhook event [comment.update](https://business-api.tiktok.com/portal/docs?id=1810515104773122) enables you to get notifications within five minutes of a comment being created, deleted, or the comment visibility settings being modified on any public video associated with an owned TikTok account. To subscribe to the comment webhook, see [Subscribe to the comment update event](https://business-api.tiktok.com/portal/docs?id=1810521730276354#item-link-Subscribe%20to%20the%20comment%20update%20event).</li></ul>

## Comparing v1.2 and v1.3
The following table outlines the differences between v1.2 and v1.3 endpoints.
 ```xtable
|Changes{30%}|v1.2{30%}|v1.3{40%}|
|---|---|---|
|Endpoint path|/v1.2/business/comments/list/|/v1.3/business/comment/list/|
|Method|POST |GET|
|New request parameters|/|`comment_ids`<br>`include_replies`|
|New response parameters|/|`parent_comment_id`<br>`reply_list` (including the parameters within the object array) <br>`unique_identifier` (within the `comments` object array)<br>`unique_identifier` (within the `reply_list` object array)<br>`display_name`(within the `comments` object array)<br>`display_name` (within the `reply_list` object array)<br>`image_url`|
```
## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/comment/list/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators. <br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041).|
```

**Parameters**

Pass params as url parameters.
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|business_id {Required}|string|Application specific unique identifier for the TikTok account. <br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) to this field.|
|video_id {Required}|string|Unique identifier for owned TikTok video to list comments (and comment replies) on. This can be accessed via the [/business/video/list/](https://ads.tiktok.com/marketing_api/docs?id=1762228421622786) endpoint.|
|comment_ids|string[] |A list of IDs for comments or comment replies that you want to filter the results by. <br>Max size: 30.|
|include_replies| boolean|Whether to include replies to the top-level comments in the results. <br>If you set this field to `true`, each top-level comment will be returned with their corresponding replies. The maximum number of replies for each comment is three. The returned comments and replies will be sorted by smart sorting algorithm by default.<br>Default value: `false`.<br>To get all the replies to a comment, use [/business/comment/reply/list/](https://ads.tiktok.com/marketing_api/docs?id=1762228430145538).|
|status|string|The visibility status of comments (and comment replies) that you want to filter the results by.<br><br><strong>Enum values</strong>:<ul><li><code>PUBLIC</code>: Comments (and comment replies) that are publicly viewable by all TikTok users.</li><li><code>ALL</code>: Video owner's comments (and comment replies) that are both hidden from view as well as publicly viewable by all TikTok users.</li></ul><strong>Default value</strong>:  <code>ALL</code>|
|sort_field |string|Specific field to sort comments (and comment replies) by.<br><br><strong>Enum values</strong>:<ul><li><code>likes</code>: Sort comments (and comment replies) by the number of likes in the specified <code>sort_order</code>.</li><li><code>replies</code>: Sort comments (and comment replies) by the number of replies in the specified <code>sort_order</code>.</li><li><code>create_time</code>: Sort comments (and comment replies) chronologically in the specified <code>sort_order</code>.</li></ul>By default, the comments will be returned in a random order.<br> <br><p><span style="color:darkred"><b>Note</b></span>: If the number of comments on the owned video exceeds 500, the first 500 comments will be returned according to the specified sorting, and the remaining comments will be returned in reverse order based on the number of likes. Also, the comments beyond the first 500 and the first 500 comments themselves are not deduplicated and may contain duplicates.|
|sort_order|string|Specific field to sort comments (and comment replies) by.<br><br><strong>Enum values</strong>:<ul><li><code>asc</code>: Sort comments (and comment replies) in ascending order by the specified <code>sort_field</code>.</li><li><code>desc</code>: Sort comments (and comment replies) in descending order by the specified <code>sort_field</code>.</li><li><code>smart</code>: Sort comments (and comment replies) using algorithms.</li></ul><strong>Default value:</strong> <code>smart</code>|
|cursor |integer|Cursor for pagination.<br> If the response parameter <code>has_more</code> is <code>true</code>, pass the returned cursor in this parameter in the subsequent request to receive the next page of results.<br><br><strong>Default value</strong>: 0|
|max_count |integer|The maximum number of comments that will be returned for each page of data.<br><br><strong>Default value</strong>: 20<br><br><strong>Minimum value</strong>: 1<br><br><strong>Maximum value</strong>: 30<br><span style="color:darkred"><b>Note</b></span>: Due to our trust and safety policies, it is possible that the endpoint returns less than the <code>max_count</code> number of comments even if the response parameter <code>has_more</code> is <code>true</code>.|
```
### Example

#### Get all comments on an owned video
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/comment/list/?business_id={{business_id}}&video_id={{video_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

#### Get specified comments on an owned video
```xcodeblock
(code curl http)
curl --location --request GET "https://business-api.tiktok.com/open_api/v1.3/business/comment/list/?business_id={{business_id}}&video_id={{video_id}}&comment_ids=["{{comment_id}}","{{comment_id}}"]" \
--header "Access-Token: {{Access-Token}}"
(/code)
```

## Response
**Header**

These are important headers to record for issue reports and troubleshooting. This is not an exhaustive list of response headers.

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Date|string|Date and time (GMT) when the response was received.<br><br><strong>Example:</strong> Fri, 13 Aug 2021 08:04:42 GMT.|
|X-Tt-Logid|string|Unique identifier for the API request.|
```
**Body**
```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
| request_id| string|Unique identifier for the API request. <br>Please record this field for all API requests. Important for issue reports and troubleshooting.|
| code|integer| Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
| message| string|Response message. For details, see [Appendix - Return Information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
| data|object|Returned data.|
#| comments|object[]|List of current page of comments (or comment replies or both) created on the specified video.|
##|comment_id|string| Unique identifier for the comment or comment reply. <br><br><strong>Example</strong>: 6990565363377392901|
##|video_id|string| Unique identifier for the owned video that the comment or comment reply was created on. <br><br><strong>Example</strong>: 6990565363377392901|
##|user_id {-To-be-deprecated}| string|Developer application and TikTok account scoped unique identifier for the user that made the comment or comment reply. <br><br><strong>Example</strong>: ce9b7be629b0447af777a0ad945783f61b7f790982b49fbda04a23b4722eaffe<br><br><p><span style="color:darkred"><b>Note</b></span>: This field will be deprecated in the next API version. We recommend using `unique_identifier` to identify the TikTok user because the identifier remains consistent across different APIs.|
##| unique_identifier | string | A globally unique identifier assigned to each user commenting. This identifier remains consistent across different APIs, facilitating cross-referencing and integration.<br><br><b>Example</b>: +ABc1D2/E0fGhijklMNOrD34vDW/zDiZUNRH5Jyahue6OcA7b8ZpwnqXV9u1Uh+k |
##|create_time| string| Unix/Epoch date-time when the comment or comment reply was created. <br><br><strong>Example</strong>: 1627617835|
##|text| string|Text content of the comment or comment reply. <br><br><strong>Example</strong>: "This is a comment #comment 🙂"|
##|likes| integer|Number of likes the comment or comment reply has received. <br><br><strong>Example</strong>: 123|
##|replies|integer| Number of replies the comment has received. <br><br><strong>Example</strong>: 123|
##|owner| boolean| Whether the user who posted the video made the comment or comment reply. <br><br><strong>Example</strong>: <code>true</code>|
##|liked|boolean| Whether the user who posted the video has liked the comment or comment reply. <br><br><strong>Example</strong>: <code>true</code>|
##|pinned| boolean|Whether the user who posted the video has pinned the comment or comment reply. <br><br><strong>Example</strong>: <code>true</code>|
##|status| string|The visibility status of the comment or comment reply. <br><br><strong>Enum values</strong>:<ul><li><code>HIDDEN</code>: The comment or comment reply is hidden from public viewership.</li> <li><code>PUBLIC</code>: The comment or comment reply can be viewed by all users.</li></ul><strong>Example</strong>: <code>PUBLIC</code>|
##|username| string| The username of the TikTok user who made the comment or comment reply. <br><br><strong>Example</strong>: feather_in_the_w1nd|
##| display_name | string | The display name of the TikTok user who made the comment or comment reply.<br><br> <strong>Example</strong>: Feather in the wind |
##|profile_image | string|Temporary URL for profile image of the TikTok user who posted the comment or comment reply. Expiration date-time is included in the `x-expires` parameter as Epoch/Unix time in seconds.<br><br><strong>Example</strong>: https://p16-sign-va.tiktokcdn.com/tos-maliva-avt-0068/188d468fc1de3f328250ea0c96e98f8b~c5_168x168.jpeg?<strong>x-expires</strong>=1651687200&x-signature=AAiHg70%2Bnj9iUipTuQWodRmk0qA%3D |
##| parent_comment_id | string | Returned only for comment replies. <br>The ID of the comment to which the reply is directed. |
##| reply_list | object[] | Returned only for top-level comments when `include_replies` is set to `true`. <br>Information about the replies to the comment. <br>Max size: 3. |
###| video_id | number | Unique identifier for the owned video that the comment reply was created on. <br><br><strong>Example</strong>: 6990565363377392901 |
###| comment_id | number | Unique identifier for the comment reply. <br><br><strong>Example</strong>: 6990565363377392901 |
###| create_time | string | Unix/Epoch date-time when the comment reply was created. <br><br><strong>Example</strong>: 1627617835 |
###| text | string | Text content of the comment reply. <br><br><strong>Example</strong>: "This is a comment reply#comment reply 🙂" |
###| status | string | The visibility status of the comment reply. Enum values: <ul><li>`HIDDEN`: The comment reply is hidden from public viewership.</li><li>`PUBLIC`: The comment reply can be viewed by all users.</li></ul><br><strong>Example</strong>: `PUBLIC`. |
###| liked | boolean | Whether this comment reply is liked by the user who posted the video. <br><br><strong>Example</strong>: `true` |
###| likes | number | Number of likes the comment reply has received. <br><br><strong>Example</strong>: 123 |
###| owner | boolean | Whether the comment reply is made by the user who posted the video. <br><br><strong>Example</strong>: `true` |
###| user_id {-To-be-deprecated} | string | Developer application and TikTok account scoped unique identifier for the user who made the comment reply. <br><br><strong>Example</strong>: ce9b7be629b0447af777a0ad945783f61b7f790982b49fbda04a23b4722eaffe <br><br><p><span style="color:darkred"><b>Note</b></span>: This field will be deprecated in the next API version. We recommend using `unique_identifier` to identify the TikTok user because the identifier remains consistent across different APIs.|
###| unique_identifier | string | A globally unique identifier assigned to each user commenting. This identifier remains consistent across different APIs, facilitating cross-referencing and integration.<br><br><b>Example</b>: +ABc1D2/E0fGhijklMNOrD34vDW/zDiZUNRH5Jyahue6OcA7b8ZpwnqXV9u1Uh+k |
###| username | string | The username of the TikTok user who posted the comment reply. <br><br><strong>Example</strong>: feather_in_the_w1nd |
###| display_name | string | The display name of the TikTok user who posted the comment reply.<br><br> <strong>Example</strong>: Feather in the wind |
###| profile_image | string | Temporary URL for profile image of the TikTok user who posted the comment reply. Expiration date-time is included in the `x-expires` parameter as Epoch/Unix time in seconds. <br><br><strong>Example</strong>: https://p16-sign-va.tiktokcdn.com/tos-maliva-avt-0068/188d468fc1de3f328250ea0c96e98f8b~c5_168x168.jpeg?<strong>x-expires</strong>=1651687200&x-signature=AAiHg70%2Bnj9iUipTuQWodRmk0qA%3D |
###| parent_comment_id | string | The ID of the parent comment to which the comment reply is directed. |
##| image_url | string | A URL to an image in the comment.<br><br>Returned if the comment is an image.<br><br>The URL won't expire.|
#| cursor| integer| Cursor for the next page of results, to be passed in the cursor query parameter of the subsequent API request.  |
#| has_more | boolean | Whether an additional page of data is available. |
```
### Example
#### Get all comments on an owned video
```xcodeblock
(code)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "comments": [
            {
                "user_id": "{{user_id}}",
                "unique_identifier": "{{unique_identifier}}",
                "profile_image": "{{profile_image}}",
                "replies": 0,
                "username": "{{username}}",
                "display_name": "{{display_name}}",
                "liked": false,
                "comment_id": "{{comment_id}}",
                "status": "HIDDEN",
                "create_time": "1681790814",
                "pinned": false,
                "likes": 0,
                "owner": true,
                "video_id": "{{video_id}}",
                "text": "{{text}}"
            },
            {
                "user_id": "{{user_id}}",
                "unique_identifier": "{{unique_identifier}}",
                "profile_image": "{{profile_image}}",
                "replies": 0,
                "username": "{{username}}",
                "display_name": "{{display_name}}",
                "liked": false,
                "comment_id": "{{comment_id}}",
                "status": "HIDDEN",
                "create_time": "1681790814",
                "pinned": false,
                "likes": 0,
                "owner": true,
                "video_id": "{{video_id}}",
                "text": "{{text}}"
            },
            {
                "user_id": "{{user_id}}",
                "unique_identifier": "{{unique_identifier}}",
                "profile_image": "{{profile_image}}",
                "replies": 1,
                "username": "{{username}}",
                "display_name": "{{display_name}}",
                "liked": true,
                "comment_id": "{{comment_id}}",
                "status": "PUBLIC",
                "create_time": "{{create_time}}",
                "pinned": false,
                "likes": 1,
                "owner": true,
                "video_id": "{{video_id}}",
                "text": "{{text}}"
            }
        ],
        "has_more": false,
        "cursor": 3
    }
}
(/code)
```
#### Get specified comments on an owned video
```xcodeblock
(code)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "comments": [
            {
                "user_id": "{{user_id}}",
                "unique_identifier": "{{unique_identifier}}",
                "profile_image": "{{profile_image}}",
                "replies": 0,
                "username": "{{username}}",
				"display_name": "{{display_name}}",
                "liked": false,
                "comment_id": "{{comment_id}}",
                "status": "HIDDEN",
                "create_time": "1681790814",
                "pinned": false,
                "likes": 0,
                "owner": true,
                "video_id": "{{video_id}}",
                "text": "{{text}}"
            },
            {
                "user_id": "{{user_id}}",
                "unique_identifier": "{{unique_identifier}}",
                "profile_image": "{{profile_image}}",
                "replies": 1,
                "username": "{{username}}",
				"display_name": "{{display_name}}",
                "liked": true,
                "comment_id": "{{comment_id}}",
                "status": "PUBLIC",
                "create_time": "{{create_time}}",
                "pinned": false,
                "likes": 1,
                "owner": true,
                "video_id": "{{video_id}}",
                "text": "{{text}}"
            }
        ]
    }
}
(/code)
```
