---
title: "Manage comments on owned TikTok videos"
doc_id: 1776065099288577
path: "Organic API / Accounts API / Guides / Manage comments on owned TikTok videos"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Manage comments on owned TikTok videos

This article walks you through how to manage comments on owned TikTok videos.

# Introduction
Managing comments on TikTok accounts is vital for creating a positive experience for the community, fostering engagement, gathering feedback, and maintaining a reputable online presence.

**You can use Accounts API to effectively moderate, respond to, and analyze comments, enhancing your ability to manage your TikTok account and engage with your audience.**

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://business-api.tiktok.com/portal/docs?id=1735713609895937) for details. 
  - To manage comments on owned TikTok videos, you need relevant permissions. See [API Reference](https://business-api.tiktok.com/portal/docs?id=1735713875563521) to find out the required permissions for each endpoint and see [Update app permissions](https://business-api.tiktok.com/portal/docs?id=1738855280338946) to find out how to configure permissions.    
- You have an owned video that has been published to your TikTok account. If you don't have one and want to publish a public video to your TikTok account, use [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234).
- You have followed the steps in [Authorization](https://business-api.tiktok.com/portal/docs?id=1738083939371009) and [Authentication](https://business-api.tiktok.com/portal/docs?id=1738084387220481) to obtain a TikTok account access token and the Application specific unique ID for the TikTok account.

# Use cases and required parameters overview
The table below outlines the use cases, relevant endpoints, and the required parameters:
<table>
<thead>
  <tr>
    <th rowspan="2" style="width:20%">Use case</th>
    <th rowspan="2" style="width:20%">Endpoint to use</th>
    <th colspan="5" style="width:60%; text-align: center;">Required parameters</th>
  </tr>
  <tr>
    <th  style="width:12%"><code>business_id</code></th>
    <th  style="width:12%"><code>video_id</code></th>
    <th style="width:12%"><code>comment_id</code></th>
    <th style="width:12%"><code>text</code></th>
    <th style="width:12%"><code>action</code></th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Create a new comment</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1762228442072066"  target="_blank" >/business/comment/create/</a></td>
    <td>✅<br></td>
    <td>✅</td>
    <td></td>
    <td>✅</td>
    <td></td>
  </tr>
  <tr>
    <td>Reply to a comment</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1762228448779266"  target="_blank" >/business/comment/reply/create/</a></td>
    <td>✅<br></td>
    <td>✅<br></td>
    <td>✅<br></td>
    <td>✅</td>
    <td></td>
  </tr>
  <tr>
    <td>Like a comment</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1762228456608770"  target="_blank" >/business/comment/like/</a><br></td>
    <td>✅</td>
    <td></td>
    <td>✅</td>
    <td></td>
    <td>✅</td>
  </tr>
  <tr>
    <td>Unlike a comment</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1762228456608770"  target="_blank" >/business/comment/like/</a></td>
    <td>✅</td>
    <td></td>
    <td>✅</td>
    <td></td>
    <td>✅</td>
  </tr>
  <tr>
    <td>Hide a comment</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1762228474532865"  target="_blank" >/business/comment/hide/</a></td>
    <td>✅</td>
    <td>✅</td>
    <td>✅</td>
    <td></td>
    <td>✅</td>
  </tr>
  <tr>
    <td>Unhide a comment</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1762228474532865"  target="_blank" >/business/comment/hide/</a></td>
    <td>✅</td>
    <td>✅</td>
    <td>✅</td>
    <td></td>
    <td>✅</td>
  </tr>
  <tr>
    <td>Delete a comment</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1762228483080193" target="_blank" >/business/comment/delete/</a></td>
    <td>✅</td>
    <td></td>
    <td>✅</td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td>Retrieve all top-level comments<br></td>
    <td rowspan="4"><a href="https://business-api.tiktok.com/portal/docs?id=1760232109619202"  target="_blank" >/business/comment/list/</a><br></td>
    <td rowspan="2">✅<br></td>
    <td rowspan="2">✅</td>
    <td rowspan="2"></td>
    <td rowspan="2"></td>
    <td rowspan="2"></td>
  </tr>
  <tr>
    <td>Retrieve all top-level comments and the corresponding replies</td>
  </tr>
  <tr>
    <td>Retrieve specific comments or replies by ID</td>
    <td rowspan="2">✅</td>
    <td rowspan="2">✅</td>
    <td rowspan="2">✅</td>
    <td rowspan="2"></td>
    <td rowspan="2"></td>
  </tr>
  <tr>
    <td>Retrieve specific top-level comments and their replies by ID in a single request</td>
  </tr>
  <tr>
    <td>Retrieve all replies to a comment</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1762228430145538"  target="_blank" >/business/comment/reply/list/</a></td>
    <td>✅<br></td>
    <td>✅</td>
    <td>✅</td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td>Subscribe to comment updates through webhooks</td>
    <td>/</td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
  </tr>
</tbody>
</table>


# Steps

## 1. Obtain the ID of an owned TikTok video
Use [/business/video/list/](https://business-api.tiktok.com/portal/docs?id=1762228421622786) to obtain the ID of an owned TikTok video through the returned `item_id`.

## 2. Manage comments on an owned TikTok video
You can manage comments on an owned TikTok video based on your business needs. 

The table below lists common parameters in the Accounts Comment endpoints and how to set these parameters. To see an overview of the required parameters for Accounts Comment endpoints, refer to the section "<a href="#item-link-Use cases and required parameters overview">Use cases and required parameters overview</a>" above.

```xtable
| Common request parameter {30%} | How to configure the parameter {70%}|
|---|---|
| `business_id` | <ul><li> Pass the `open_id` value returned from [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Authentication%202.0) (recommended)</li><li> OR pass the `creator_id` value returned from [/oauth2/creator_token/?business=tt_user](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Authentication%201.0) (<span style="color:red">to be deprecated</span>)</li></ul> |
| `video_id` | Pass the `item_id` value returned from <a href="#item-link-1. Obtain the ID of an owned TikTok video "> Step 1. Obtain the ID of an owned TikTok video </a>. |
| `comment_id` | Pass the `comment_id` value returned from the [/business/comment/list/](https://business-api.tiktok.com/portal/docs?id=1760232109619202). |
```


The common use cases for managing comments are as follows:

### Create a new comment
Use [/business/comment/create/](https://business-api.tiktok.com/portal/docs?id=1762228442072066).

### Reply to a comment
Use [/business/comment/reply/create/](https://business-api.tiktok.com/portal/docs?id=1762228448779266).

### Like a comment
Use [/business/comment/like/](https://business-api.tiktok.com/portal/docs?id=1762228456608770). Set `action` to `LIKE`.

### Unlike a comment
Use [/business/comment/like/](https://business-api.tiktok.com/portal/docs?id=1762228456608770). Set `action` to `UNLIKE`.

### Hide a comment
Use [/business/comment/hide/](https://business-api.tiktok.com/portal/docs?id=1762228474532865). Set `action` to `HIDE`.

### Unhide a comment
Use [/business/comment/hide/](https://business-api.tiktok.com/portal/docs?id=1762228474532865). Set `action` to `UNHIDE`.

### Delete a comment
Use [/business/comment/delete/](https://business-api.tiktok.com/portal/docs?id=1762228483080193).

### Retrieve comments
Use [/business/comment/list/](https://business-api.tiktok.com/portal/docs?id=1760232109619202).

Depending on your business needs, you can choose one of the four combinations below:
```xtable
| Scenario {40%} | How to configure <code>comment_ids</code> {30%} | How to configure <code>include_replies</code> {30%}|
|---|---|---|
| Retrieve all top-level comments | Not passed | Not passed |
| Retrieve all top-level comments and the corresponding replies | Not passed | Passed |
| Retrieve specific comments or replies by ID | Passed | Not passed |
| Retrieve specific top-level comments and their replies by ID in a single request | Passed | Passed |
```


### Retrieve all replies to a comment
Use [/business/comment/reply/list/](https://business-api.tiktok.com/portal/docs?id=1762228430145538).

### Subscribe to comment updates through webhooks
The comment webhook event [comment.update](https://business-api.tiktok.com/portal/docs?id=1810515104773122) enables you to get notifications within five minutes of a comment or reply being created, deleted, or the comment or reply visibility settings being modified on any public video under an owned TikTok account.

To subscribe to the comment webhook, use the Webhook API endpoint [/business/webhook/update/](https://business-api.tiktok.com/portal/docs?id=1810521279479810) and set `event_type` to `COMMENT`. 
