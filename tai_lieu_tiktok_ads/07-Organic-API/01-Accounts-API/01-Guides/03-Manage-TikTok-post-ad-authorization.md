---
title: "Manage TikTok post ad authorization"
doc_id: 1815124785075201
path: "Organic API / Accounts API / Guides / Manage TikTok post ad authorization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Manage TikTok post ad authorization

This article walks you through how to manage the ad authorization settings for TikTok posts.

# Introduction
The "Ad authorization" feature enables TikTok posts to be used as ad creatives for Spark Ads. With authorized posts, you can [create Spark Ads by pulling organic posts from authorization codes](https://business-api.tiktok.com/portal/docs?id=1739470744631298#item-link-Pull%20organic%20posts%20from%20authorization%20codes).

**You can use Accounts API to effectively generate, extend, and delete the authorization codes of TikTok posts, streamlining the management of authorization codes.**

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To manage the authorization codes of TikTok posts, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  
- You have an owned post that has been published to your TikTok account. If you don't have one and want to publish a post to your TikTok account, use [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234) or [/business/photo/publish/](https://business-api.tiktok.com/portal/docs?id=1803630424390658).
- You have obtained a TikTok account access token with `biz.spark.auth` permission and the Application specific unique ID for the TikTok account by following the steps in [Authorization](https://business-api.tiktok.com/portal/docs?id=1738083939371009) and [Authentication](https://business-api.tiktok.com/portal/docs?id=1738084387220481).
  - To confirm whether a TikTok account access token has the `biz.spark.auth` permission, use [/tt_user/token_info/get/](https://business-api.tiktok.com/portal/docs?id=1765927978092545) and check the returned `scope`. If the access token doesn't have the `biz.spark.auth` permission, request the TikTok account owner to approve Spark Ads authorization code management permission through the [Authorization](https://business-api.tiktok.com/portal/docs?id=1738083939371009) workflow.

  
# Steps

1. Decide on the TikTok post for which you want to generate an authorization code.
<br>To obtain the IDs of TikTok posts associated with a TikTok account, use [/business/video/list/](https://business-api.tiktok.com/portal/docs?id=1762228421622786).
2. Enable the ad authorization setting for the TikTok post using [/business/post/authorize/setting/](https://business-api.tiktok.com/portal/docs?id=1815051741912066). 
<br>Provide the `item_id` obtained from Step 1, set `is_ad_promotable` to `true`, and specify the desired validity period for the authorization code via `authorization_days`.
3. (Optional) [Create Spark Ads by pulling the authorized posts from authorization codes](https://business-api.tiktok.com/portal/docs?id=1739470744631298#item-link-Pull%20organic%20posts%20from%20authorization%20codes).
4. (Optional) Manage the existing authorization code of a TikTok post.
- To extend the validity period of an expiring authorization code, use [/business/post/authorize/](https://business-api.tiktok.com/portal/docs?id=1815051784582210).
- To delete the authorization code of a TikTok post, use [/business/post/authorize/delete/](https://business-api.tiktok.com/portal/docs?id=1815051817073665).
