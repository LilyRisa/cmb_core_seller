---
title: "Overview"
doc_id: 1825127469285442
path: "Organic API / Discovery API / Overview"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Overview

Discovery API is a tool that provides top hashtags and music tracks on TikTok within a specific time window, helping advertisers understand what is popular on TikTok so they can create content around those topics. Along with the list of top hashtags and tracks, users can get insights and metrics related to each hashtag or track that can be used for campaign planning, product design, and ad optimization.

With developers in mind, we make accessing and using our API possible through only a few steps. **Below are the steps needed to take before making your first API call and some useful resources to help you quickly integrate with Discovery API.**

## Step-by-step workflow
1. [Create a TikTok For Business account](https://business-api.tiktok.com/portal/docs?id=1738855099573250).
2. [Register as a developer](https://business-api.tiktok.com/portal/docs?id=1738855176671234).
3. [Create a developer app](https://business-api.tiktok.com/portal/docs?id=1738855242728450).

4 & 5. Authorization & Authentication.
<br>The specific steps for authorization and authentication depend on which endpoint you want to use:
- For the endpoints related to hashtags ([/discovery/trending_list/](https://business-api.tiktok.com/portal/docs?id=1825119032526849), [/discovery/detail/](https://business-api.tiktok.com/portal/docs?id=1825119047070721), and [/discovery/video_list/](https://business-api.tiktok.com/portal/docs?id=1825119056527362)), follow these guides: 
  - [Obtain authorization from an ad account](https://business-api.tiktok.com/portal/docs?id=1738373141733378)
  - [Generate an advertiser access token](https://business-api.tiktok.com/portal/docs?id=1738373164380162)
- For the endpoints related to tracks ([/discovery/cml/trending_list/](https://business-api.tiktok.com/portal/docs?id=1825119063013505) and [/discovery/cml/video_list/](https://business-api.tiktok.com/portal/docs?id=1825119068941314)), follow these guides: 
  - [Obtain authorization from a TikTok account](https://business-api.tiktok.com/portal/docs?id=1738083939371009)
  - [Generate a TikTok account access token](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token)
6. Make your first API call.
