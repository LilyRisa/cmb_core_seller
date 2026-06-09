---
title: "Reply to an existing comment on an owned video"
doc_id: 1762228448779266
path: "API Reference / Accounts / Comments / Reply to an existing comment on an owned video"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Reply to an existing comment on an owned video

Use this endpoint to create a reply to an existing comment on an organic video posted by an owned TikTok Account or others' TikTok Account.

> <span style="color:DodgerBlue">**Note**</span><br>
> - To prevent comments from being flagged as spam and subsequently hidden by the system, avoid posting a high volume of comments with largely similar content within a short timeframe. If a comment is flagged as spam, you will not receive the [comment.update](https://business-api.tiktok.com/portal/docs?id=1810515104773122) webhook event with `comment_action` set to `set_to_public`.
> - The comment webhook event [comment.update](https://business-api.tiktok.com/portal/docs?id=1810515104773122) enables you to get notifications within five minutes of a comment reply being created on any public video associated with an owned TikTok account. To subscribe to the comment webhook, see [Subscribe to the comment update event](https://business-api.tiktok.com/portal/docs?id=1810521730276354#item-link-Subscribe%20to%20the%20comment%20update%20event).

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/business/comments/replies/create/|/v1.3/business/comment/reply/create/|
|New request parameters| / |`reply_image_url`<br>`image_uri`<br>`image_width`<br>`image_height`|
|New response parameters| / |`unique_identifier`<br>`image_url`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/comment/reply/create/

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
| video_id {Required} | string | Body | Unique identifier for owned TikTok video to create the reply on. <br>This can be accessed via the [/business/video/list/](https://ads.tiktok.com/marketing_api/docs?id=1762228421622786) endpoint as the `item_id` for each video listed. |
| comment_id {Required} | string | Body |Unique identifier for comment on an owned TikTok video to create reply on.<br>This can be accessed via the [/business/comment/list/](https://ads.tiktok.com/marketing_api/docs?id=1760232109619202) endpoint as the `comment_id` for each comment listed. |
| reply_image_url | string | Body |URL for the reply image.<br><br>A publicly accessible HTTP(s) URL for a custom image to be used as the reply.<br><br>Photo constraints:<ul><li>Maximum resolution: 1080 x 1920 pixels or 1920 x 1080 pixels.</li><li>Minimum resolution: 360 x 360 pixels.</li><li>File size: within 20 MB.</li><li>File type: JPG, JPEG, WebP, or PNG.</li></ul><br><br><strong>Example</strong>:: https://tiktok.com/tiktok-images/image.jpg<br><br><p><span style="color:darkred"><b>Note</b></span>: Specify a URL that has been verified for ownership, either manually or automatically. To manually verify ownership of a URL property, follow the steps outlined in Manage URL properties.</p>|
| text {+Conditional} | string | Body | Either `text` or `image_uri` must be specified.<br><br>Text for the comment reply.<br><br>Length limit: 150 characters (UTF-8 encoding).<br><br><strong>Example</strong>: "This is a comment #comment 🙂".|
| image_uri {+Conditional} |string| Body | Either `text` or `image_uri` must be specified.<br><br>URI for the reply image.<br><br>To obtain the image URL, call the [/business/comment/image/upload/](https://business-api.tiktok.com/portal/docs?id=1856212334897154) endpoint first.<br><br><strong>Example</strong>: `54ccb875ae0c4368b94037d03a95b633`|
| image_width {+Conditional} | integer | Body | Required when `image_uri` is specified.<br><br>The width of the image.<br><br>To obtain the image width, call the [/business/comment/image/upload/](https://business-api.tiktok.com/portal/docs?id=1856212334897154) endpoint first.|
| image_height {+Conditional} | integer | Body | Required when `image_uri` is specified.<br><br>The height of the image.<br><br>To obtain the image height, call the [/business/comment/image/upload/](https://business-api.tiktok.com/portal/docs?id=1856212334897154) endpoint first.<br><br><p><span style="color:darkred"><b>Note</b></span>: This value is from [/business/comment/image/upload/](https://business-api.tiktok.com/portal/docs?id=1856212334897154) response.</p>|
```
### Example
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/comment/reply/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id" : "{{business_id}}",
    "video_id": "{{video_id}}",
    "comment_id" : "{{comment_id}}",
    "text": "{{text}}"
}'
(/code)
```
## Response
**Headers**

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
| data | object | Returned data. |
#|comment_id| string | Unique identifier for the newly created reply. <br><br><strong>Example</strong>: 6990565363377392901|
#|parent_comment_id| string | Unique identifier for the comment to which the reply was created.<br><br><strong>Example</strong>: 6990565363377392901|
#|video_id | string | Unique identifier for the owned video the reply was created on. <br><br><strong>Example</strong>: 6990565363377392901|
#|user_id {-To-be-deprecated}| string | Developer application and TikTok account scoped unique identifier for the user that made the reply.<br><br><strong>Example</strong>: ce9b7be629b0447af777a0ad945783f61b7f790982b49fbda04a23b4722eaffe<br><br><p><span style="color:darkred"><b>Note</b></span>: This field will be deprecated in the next API version. We recommend using `unique_identifier` to identify the TikTok user because the identifier remains consistent across different APIs. |
#| unique_identifier | string | A globally unique identifier assigned to each user commenting. This identifier remains consistent across different APIs, facilitating cross-referencing and integration.<br><br><b>Example</b>: +ABc1D2/E0fGhijklMNOrD34vDW/zDiZUNRH5Jyahue6OcA7b8ZpwnqXV9u1Uh+k |
#|create_time |string| Unix/Epoch date-time when the reply was created, in UTC timezone. <br><br><strong>Example</strong>: 1627617835 |
#|text|string|Comment text.<br><br>Returned if the user replies with text rather than with an image.<br><br><strong>Example</strong>: "This is a comment #comment 🙂"|
#| image_url |string | A URL to an image in the comment.<br><br>Returned if the comment is an image.<br><br>The URL won't expire.|
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
        "video_id": "{{video_id}}",
        "parent_comment_id": "{{parent_comment_id}}",
        "comment_id": "{{comment_id}}",
        "create_time": "1701334608",
        "text": "{{text}}",
        "user_id": "{{user_id}}",
        "unique_identifier": "{{unique_identifier}}"
    }
}
(/code)
```

