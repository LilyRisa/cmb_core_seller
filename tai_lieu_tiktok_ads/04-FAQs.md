---
title: "FAQs"
doc_id: 1740029175352322
path: "FAQs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# FAQs

This article provides a summary of FAQs related to API for Business.
# Get started
**When creating an ad group, I need to specify the App ID. Is this the App ID for my developer application?**

The App ID (`app_id`) that is required when creating an ad group is the ID of the app that you want to promote. You can make a GET request to the [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) endpoint. All promoted apps under your ad account will be returned. Locate the one that you want to create an ad group for, and note down the value of `app_id`.
<br>
<br>

 **How do I know the review status after I create an ad account using my Agency Business Center account?**

After you create an account using the [/bc/advertiser/create/](https://ads.tiktok.com/marketing_api/docs?id=1739939020318721) endpoint, you can use the [/advertiser/info/](https://ads.tiktok.com/marketing_api/docs?id=1739593083610113) endpoint to check the review status and additional review information (such as rejection reasons).
# Allowlisting
**How can I request an allowlist-only feature**?

You can use the Allowlist Management feature on the TikTok For Business Developers website to request allowlist features for developer apps. Check out [here](https://ads.tiktok.com/marketing_api/blog/detail/7102266224578396162) to learn more.

Note that Allowlist Management only works for developer app related allowlisting, and all advertiser or ad account level allowlisting will still be done through your TikTok representative.
# Authorization
Please see [Authorization-FAQs](https://ads.tiktok.com/marketing_api/docs?id=1766037914914818) to learn about Authorization FAQs .
# Authentication
Please see [Authentication-FAQs](https://ads.tiktok.com/marketing_api/docs?id=1766041812130817) to learn about Authorization FAQs .
# Campaign Management
Please see [here](https://ads.tiktok.com/marketing_api/docs?id=1767309272869890&rid=68wfzvwt9ov) to learn about Campaign Management FAQs.
# Creatives
<strong>How can I get the preview URL of my video used in the ad? The [/ad/get/](https://ads.tiktok.com/marketing_api/docs?id=1735735588640770) endpoint doesn't return the URL. </strong>

To obtain the preview URL of your video used in the ad, follow these steps based on the identity used for your ad:
1. Call [/ad/get/](https://ads.tiktok.com/marketing_api/docs?id=1735735588640770) to retrieve the identity type (`identity_type`) and either the video ID (`video_id`) or TikTok post ID (`tiktok_item_id`) associated with your ad.
2. Depending on the `identity_type` obtained from Step 1, select the appropriate endpoint to retrieve the video preview URL:
- For non-Spark ads (`identity_type`=`CUSTOMIZED_USER`), pass the `video_id` value returned from `/ad/get/` to [/file/video/ad/search/](https://ads.tiktok.com/marketing_api/docs?id=1740050472224769). Then obtain the video preview URL from the `preview_url` field in the response.
- For Spark Ads (`identity_type` = `AUTH_CODE`, `TT_USER`, or `BC_AUTH_TT`), pass the `tiktok_item_id` value returned from `/ad/get/` and pass it as the `item_id` parameter in the request to [/identity/video/info/](https://ads.tiktok.com/marketing_api/docs?id=1740218522178562). Then obtain the video preview URL from the `url` field in the response.
> <span style="color:DodgerBlue">**Note**</span><br>
> If you want to get the preview URL of your **ad**, use [/creative/ads_preview/create/](https://ads.tiktok.com/marketing_api/docs?id=1739403070695426).
# Audience Management
Please see [here](https://ads.tiktok.com/marketing_api/docs?id=1746998681953281&rid=av9yvcqekf) to learn about Audience Management FAQs.
# Business Center
Please see [here](https://ads.tiktok.com/marketing_api/docs?id=1739562436183041&rid=zb9ri5lxtmi) to learn about Business Center FAQs.
# Accounts
Please see [here](https://business-api.tiktok.com/portal/docs?id=1776983576127490) to learn about Accounts FAQs.
# Reporting
Please see [here](https://ads.tiktok.com/marketing_api/docs?id=1738864850353154) to learn about Reporting FAQs .

