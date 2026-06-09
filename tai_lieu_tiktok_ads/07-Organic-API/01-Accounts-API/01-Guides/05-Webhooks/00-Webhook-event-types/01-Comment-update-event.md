---
title: "Comment update event"
doc_id: 1810515104773122
path: "Organic API / Accounts API / Guides / Webhooks / Webhook event types / Comment update event"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Comment update event

The comment update category includes the event `comment.update`. 

> <span style="color:DodgerBlue">**Note**</span><br>
> To configure a Webhook callback URL for your developer app, use the Webhook API endpoint [/business/webhook/update/](https://business-api.tiktok.com/portal/docs?id=1810521279479810). 

## comment.update

Fired within five minutes of a comment or reply being created, deleted, or the comment visibility settings being modified on any photo post or public video post under an owned TikTok account. This applies to both posts published through [/business/video/publish/](https://ads.tiktok.com/marketing_api/docs?id=1762228496095234) or [/business/photo/publish/](https://business-api.tiktok.com/portal/docs?id=1803630424390658) and posts manually published through the TikTok App.

### Prerequisite
To ensure that you can receive `comment.update` events, the TikTok account owner needs to authorize the developer app with the `comment.list` scope (Read the comments and replies of your in-app content). 

The authorization must remain valid and active. If the authorization expires or is revoked, you will no longer receive `comment.update` events for the TikTok account. 

### Event content
``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| comment_id | number | Unique identifier for the comment or comment reply. |
| video_id | number | Unique identifier for the owned post that the comment was created on. |
| parent_comment_id | number | Returned only for comment replies.<br> The ID of the comment to which the reply is directed. |
| comment_type | string | The type of the comment. <br>Enum values: <ul><li> `comment`: comment.</li><li> `reply`: comment reply. </li></ul>|
| comment_action | string | The action taken on a comment or comment reply. <br>Enum values: <ul><li>`insert`: The comment or comment reply was created.</li><li>`delete`: The comment or comment reply was deleted. </li><li>`set_to_hidden`: The comment or comment reply was set to hidden either by the user or through moderation.</li><li>`set_to_friends_only`: The comment or comment reply was set to visible only to the user's friends.</li><li>`set_to_public`: The comment or comment reply was changed from being hidden or visible only to friends to being publicly visible to all users.</li></ul>|
| timestamp | number | Unix/Epoch date-time when the comment action was performed. |
| unique_identifier | string | A globally unique identifier assigned to each user commenting. This identifier remains consistent across different APIs, facilitating cross-referencing and integration.<br><br><b>Example</b>: +ABc1D2/E0fGhijklMNOrD34vDW/zDiZUNRH5Jyahue6OcA7b8ZpwnqXV9u1Uh+k. |
|text|string|Text content of the comment or comment reply.|
```

### Example payload
```xcodeblock
(code curl http)
{
    "client_key":  "bwo1m23456a7k89",
    "event": "comment.update",
    "create_time": 1615338610,
    "user_openid": "_000abPL1cJIdVefgDhGijkl2mFn3AB0abY4",
    "content": "{\"comment_id\":7247303576418566913,\"video_id\":7203946942097902849,\"parent_comment_id\":7235861947622916866,\"comment_type\":\"reply\",\"comment_action\":\"delete\",\"unique_identifier\":\"+ABc1D2/E0fGhijklMNOrD34vDW/zDiZUNRH5Jyahue6OcA7b8ZpwnqXV9u1Uh+k\",\"timestamp\":1687394416109,\"text\":\"text\"}"
} 
(/code)
```

