---
title: "TTO Creator Marketplace Content Linking"
doc_id: 1830337139853313
path: "Organic API / TikTok One API / Guides / TTO Creator Marketplace Content Linking"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# TTO Creator Marketplace Content Linking

Content Linking is a feature of the TikTok One (TTO) API designed to enhance performance tracking for organic creator videos on TikTok. This robust API functionality simplifies communication between advertisers and creators via TikTok's official creative platform. By leveraging Content Linking, advertisers can access advanced analytics, obtain Spark Ads authorization, and unlock other benefits to maximize the impact of organic content.

# Types of Content Links
The two Content Link types are **invite links** and **linking requests**. Each serves distinct purposes and offers unique benefits for advertisers and creators.

## Invite links
Invite links are URLs that creators use to connect new content, upload drafts, or link existing content within the TikTok app. They can be shared directly by brands or partners via email, direct message, etc. and are ideal for pre-publishing or retroactive linking.

TTO Creator Marketplace campaigns support configuring invite links at either the campaign-level or the brand-level. Each invite link configuration is designed for specific use cases, offering different capabilities and limitations. Understanding these differences will help you choose the right configuration for your needs.

### Campaign-level invite link
Configure invite links at the campaign-level to associate videos with a specific campaign. This is ideal for time-sensitive or event-driven campaigns that require clear monitoring and reporting.

- Limitations:
  - You can invite up to 500 Creators to a single campaign using a campaign-level invite link.
  - A single Creator can upload videos to the same campaign-level invite link 500 times, including uploaded and deleted videos.
  - A campaign-level invite link can host up to 250,000 videos across all invited Creators.
- Use case examples:
  - Seasonal promotions, such as Black Friday sales.
  - Product launches with a defined timeframe.
  
To create a campaign with a campaign-level invite link, use [/tto/tcm/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1815693549459458) and set `campaign_type` to `CAMPAIGN` or leave `campaign_type` unspecified.

### Brand-level invite link
Configure invite links at the brand-level to associate videos with your brand account on an ongoing basis. This is ideal for long-term or evergreen content aggregation.

- Limitations:
  - You can invite up to an unlimited number of Creators to a single campaign using a brand-level invite link.
  - A single Creator can upload an unlimited number of videos to the same brand-level invite link.
  - No restrictions on the total number of videos hosted.
- Use case examples:
  - Building a library of user-generated content for your brand.
  - Long-term influencer partnerships.

To create a campaign with a campaign-level invite link, use [/tto/tcm/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1815693549459458) and set `campaign_type` to `BRAND_LINK`.

### When to use each invite link type

The following table compares campaign-level invite links and brand-level invite links to help you determine the best option for your needs.

```xtable
| Feature {20%}| Campaign-level invite link  {30%}| Brand-level invite link {50%} |
|---|---|---|
| Creator limit | Up to 500 creators | Unlimited |
| Video limit per Creator | 500 videos (including deleted ones) | Unlimited |
| Total video limit | 250,000 videos across all creators | Unlimited |
| Best for | Time-sensitive campaigns | Evergreen branding efforts |
```

## Linking requests
Linking requests are in-app notifications sent directly to creators' inboxes, prompting them to tag their videos. These notifications are provided via in-app push and are used only for retroactive linking.

To send a linking request, use the [/tto/tcm/campaign/link/](https://business-api.tiktok.com/portal/docs?id=1824449956282370) endpoint with the appropriate parameters based on your use case:
- For tagging a video with a brand:
  - Set the `action` parameter to `LINK`.
  - Specify a campaign with a brand-level invite link through the `campaign_id` parameter.
- For tagging a video with a campaign:
  - Set the `action` parameter to `LINK`.
  - Specify a campaign with a campaign-level invite link through the `campaign_id` parameter.

By using these configurations, you can ensure creators receive timely notifications to connect their unlinked content, enabling seamless content association with your brand or campaign.
