---
title: "Post publishing events"
doc_id: 1810515090228226
path: "Organic API / Accounts API / Guides / Webhooks / Webhook event types / Post publishing events"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Post publishing events

Post publishing events provide you with real-time updates on the status of content published via specific TikTok API endpoints. These events are triggered only for posts that are published through [/business/video/publish/](https://ads.tiktok.com/marketing_api/docs?id=1762228496095234) or [/business/photo/publish/](https://business-api.tiktok.com/portal/docs?id=1803630424390658). The event types you'll receive include:
- `post.publish.failed`
- `post.publish.complete`
- `post.publish.publicly_available`
- `post.publish.no_longer_publicly_available`

> <span style="color:DodgerBlue">**Note**</span><br>
> To configure a Webhook callback URL for your developer app, use the Webhook API endpoint [/business/webhook/update/](https://business-api.tiktok.com/portal/docs?id=1810521279479810). 

# Post publishing life cycle
The following table describes the life cycle that a post goes through **after an API request has been made to publish the post**  and the expected time between receiving notifications for each stage:



<table>
<thead>
  <tr>
    <th style="width:15%">Stage </th>
    <th style="width:30%">Event</th>
    <th style="width:28%">Description</th>
    <th style="width:27%">Expected Time Delay</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="2">1*one of the two</td>
    <td><code>post.publish.failed</code></td>
    <td>Post publishing failed due to format constraint violations or post URL being blocked before download completion.</td>
    <td>Not deterministic - depends on file size and network conditions. In general, 30 minutes should be sufficient to download a 1GB post file.</td>
  </tr>
  <tr>
    <td><code>post.publish.complete</code></td>
    <td>Post publishing completed - file format constraints were met and no issues in downloading file from provided URL.</td>
    <td>Not deterministic - depends on file size and network conditions. In general, 30 minutes should be sufficient to download a 1GB post file.</td>
  </tr>
  <tr>
    <td>2</td>
    <td><code>post.publish.publicly_available</code></td>
    <td>Post published has gone through moderation and is distributed for public viewership on TikTok.</td>
    <td>In normal cases will take at most 2 minutes, but may take longer if manual moderation is required.</td>
  </tr>
  <tr>
    <td>3*conditional</td>
    <td><code>post.publish.no_longer_publicly_available</code></td>
    <td>Post published is no longer available for public viewership on TikTok. This may be because of subsequent moderation or because the user has turned the post to friends-only/private viewership.</td>
    <td>N/A - may occur at any time after a post has become publicly available</td>
  </tr>
</tbody>
</table>


# Post publishing event types
## post.publish.failed

Fired when the post uploaded fails to upload to TikTok.

### Event content
``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|publish_id | string | An identifier for the requested publish to TikTok action |
| reason | string | A machine-parsable string representing the reason for the failure, listed in the <strong>"Failure reasons"</strong> section below.|
|publish_type| string | Publish type. The allowed enum value: `DIRECT_PUBLISH` (To directly publish the post to your TikTok Account). |
```
### Failure reasons
``` xtable
| Reason{30%} | Description {70%}|
|---|---|
| file_format_check_failed | TikTok server is able to download the post material but identifies that the post file format did not meet the file format specifications. <br>This is not a retryable error. |
| duration_check_failed | TikTok server is able to download the video material but identifies that the video file format did not meet the duration constraint - min 3 seconds long, max 60 seconds long. <br>This is not a retryable error. |
| frame_rate_check_failed | TikTok server is able to download the video material but identifies that the video file format did not meet the frame rate specifications - min 23 FPS, max 60 FPS. <br>This is not a retryable error. |
| picture_size_check_failed | TikTok server is able to download the post material but identifies that the post file format did not meet the picture resolution specifications - min height and width being 360p. <br>This is not a retryable error. |
| internal | Some parts of TikTok server may be currently unavailable. <br>This is a retryable error. |
| video_pull_failed |TikTok server encountered a connection error while downloading the specified video resource, or the download is terminated since it cannot be completed within the one hour timeout. <br> Check if the supplied URL is publicly accessible or bandwidth-limited. If partner is certain that the URL is valid, a retry is recommended.  |
| photo_pull_failed |TikTok server encountered a connection error while downloading the specified photo resource, or the download is terminated since it can not be completed within the one hour timeout.<br>Check if the supplied URL is publicly accessible or bandwidth-limited. If developers are certain that the URL is valid, a retry is recommended.  |
| auth_removed | This TikTok creator has removed the developer's access while the download/uploading is being processed, so the publishing effort must be terminated. <br>Retry should not be done. |
| spam_risk_too_many_posts | This TikTok creator has created too many posts within the last 24 hours via API. <br>Try to publish the posts from the TikTok Mobile App. |
| spam_risk_user_banned_from_posting | This TikTok creator is banned from making new posts. |
| spam_risk_text | The description text has risky or spammy contents, so the publishing attempt is terminated. .|
| spam_risk | TikTok determines that the post has a high spamming risk and rejects the publishing attempt. This may be a result of: <ul><li>Making publishing attempts too frequently</li><li> Other risky in-app user behaviors </li></ul> Retry is not recommended. |
| publish_cancelled | The ongoing upload or download has been successfully cancelled.  |
```
### Example payload
```xcodeblock
(code curl http)
{
    "client_key": "bwo1m23456a7k89",
    "event": "post.publish.failed",
    "create_time": 1615338610,
    "user_openid": "_000abPL1cJIdVefgDhGijkl2mFn3AB0abY4",
    "content":"{\"publish_id\":\"v_pub_url~v1.2345123456789123456\", \"reason\":\"file_format_check_failed\",\"publish_type\":\"DIRECT_PUBLISH\"}"
} (/code)
```
## post.publish.complete

Fired when the post uploaded has been successfully published to TikTok.

### Event content
``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| publish_id | string | An identifier for the requested publish to TikTok action |
|publish_type| string | Publish type. The allowed enum value: `DIRECT_PUBLISH` (To directly publish the post to your TikTok Account). |
```

### Example payload
```xcodeblock
(code curl http)
{
    "client_key": "bwo1m23456a7k89",
    "event": "post.publish.complete",
    "create_time": 1615338610,
    "user_openid": "_000abPL1cJIdVefgDhGijkl2mFn3AB0abY4",
    "content":"{\"publish_id\":\"v_pub_url~v1.2345123456789123456\",\"publish_type\":\"DIRECT_PUBLISH\"}"
} (/code)
```

## post.publish.publicly_available

Fired when the post uploaded has gone through moderation and is distributed for public viewership on TikTok.

### Event content
``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| publish_id | string | An identifier for the requested publish to TikTok action |
|post_id | string | The unique identifier for the post upon successful public availability on TikTok. This ID can be used to start querying insights for the specific post.|
|publish_type| string | Publish type. The allowed enum value: `DIRECT_PUBLISH` (To directly publish the post to your TikTok Account). |
```

### Example payload
```xcodeblock
(code curl http)
{
    "client_key": "bwo1m23456a7k89",
    "event": "post.publish.publicly_available",
    "create_time": 1615338610,
    "user_openid": "_000abPL1cJIdVefgDhGijkl2mFn3AB0abY4",
    "content":"{\"publish_id\":\"v_pub_url~v1.2345123456789123456\",\"post_id\":\"1234567891234567891\",\"publish_type\":\"DIRECT_PUBLISH\"}"
} (/code)
```

## post.publish.no_longer_publicly_available

Fired when the post uploaded is no longer available for public viewership on TikTok. This may be because of subsequent moderation or because the user has turned the post to friends-only/private viewership.

### Event content
``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| publish_id | string | An identifier for the requested publish to TikTok action |
|post_id | string | The unique identifier for the post on TikTok.|
|publish_type| string | Publish type. The allowed enum value: `DIRECT_PUBLISH` (To directly publish the post to your TikTok Account). |
```

### Example payload
```xcodeblock
(code curl http)
{
    "client_key":  "bwo1m23456a7k89",
    "event": "post.publish.no_longer_publicly_available",
    "create_time": 1615338610,
    "user_openid": "_000abPL1cJIdVefgDhGijkl2mFn3AB0abY4",
    "content":"{\"publish_id\":\"v_pub_url~v1.2345123456789123456\",\"post_id\":\"1234567891234567891\",\"publish_type\":\"DIRECT_PUBLISH\"}"
} 
(/code)
```
