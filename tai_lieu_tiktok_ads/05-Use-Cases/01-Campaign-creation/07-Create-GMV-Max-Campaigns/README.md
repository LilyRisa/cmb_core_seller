---
title: "Create GMV Max Campaigns"
doc_id: 1822009058467842
path: "Use Cases / Campaign creation / Create GMV Max Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create GMV Max Campaigns

This article walks you through the steps to create GMV Max Campaigns.

# Introduction
GMV Max is an automated ad campaign type that optimizes total channel ROI for your TikTok Shop. 

GMV Max Campaigns consist of two types:
- **Product GMV Max Campaign**: Automatically chooses the products, creative assets and placement options that are most likely to encourage people to buy your products. 
- **LIVE GMV Max Campaign**: Optimizes liveroom traffic to get the highest gross revenue for your LIVE event. 

**You can use GMV Max API to create GMV Max Campaigns, and this helps streamline your ad creation experience, and elevate operational efficiency and scalability.**

For a comprehensive introduction to and unique values for GMV Max API, consult [API for Business Playbook - GMV Max](https://bytedance.sg.larkoffice.com/file/WxJ5bQMnhoz6l3xEbdVl8TyPgwg).

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://business-api.tiktok.com/portal/docs?id=1735713609895937) for details. 
  - To create GMV Max Campaigns, you need relevant permissions. See [API Reference](https://business-api.tiktok.com/portal/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://business-api.tiktok.com/portal/docs?id=1738855280338946) to find out how to configure permissions.  

# Mutual exclusion rules for GMV Max Campaigns
GMV Max Campaigns operate under specific exclusion rules to ensure optimal performance and accurate attribution. Familiarize yourself with these rules to prevent campaign creation errors.

## Ad account exclusive authorization
TikTok Shop implements a single ad account authorization model for GMV Max Campaigns:

- Each TikTok Shop can authorize only one ad account for GMV Max Campaign creation.
- When you grant GMV Max exclusive authorization to a new ad account: 
  - The previous ad account loses access to GMV Max Campaigns that use the same TikTok Shop.
  - All existing GMV Max Campaigns that use the same TikTok Shop in the previous ad account are automatically paused.
  
## Product usage in GMV Max Campaigns
Products must adhere to single-campaign usage rules:
- Each product within a TikTok Shop can only be used in one enabled Product GMV Max Campaign at a time.
- To use a product in a new Product GMV Max Campaign, first disable or delete its existing Product GMV Max Campaign.

Campaign configuration guidelines:
- For Product GMV Max Campaigns that promote all products within a TikTok Shop, ensure that the TikTok Shop is only used in one enabled Product GMV Max Campaign.
- For Product GMV Max Campaigns that promote specific products within a TikTok Shop, ensure that each product is only used in one enabled Product GMV Max Campaign. You can create more than one enabled Product GMV Max Campaign for the same TikTok Shop, as long as these campaigns promote different products.

## Campaign type compatibility
The following exclusion rules apply across campaign types:

### Shop level
Product GMV Max Campaigns promoting all products within a TikTok Shop are incompatible with enabled [Video Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1750361719059457) and [Product Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1785886237030401) that promote all products within **the same TikTok Shop**.
  - If you create an enabled Product GMV Max Campaign by promoting all products from a TikTok Shop, any existing enabled Video Shopping Ads or Product Shopping Ads using the same shop will be automatically paused or impacted, regardless of which ad account the Video Shopping Ad Groups or Product Shopping Ad Groups were created in.
  - If all products from a TikTok Shop have been occupied by an enabled Product GMV Max Campaign, you can no longer create new enabled Video Shopping Ads or Product Shopping Ads that promote all products from the TikTok Shop. 

### Product level
Product GMV Max Campaigns promoting specific products within a TikTok Shop are incompatible with enabled Video Shopping Ads and Product Shopping Ads that promote **the same products** within the same TikTok Shop.
  - If you create an enabled Product GMV Max Campaign by promoting specific products from a TikTok Shop, any existing enabled Video Shopping Ads or Product Shopping Ads using the same products will be automatically paused or impacted, regardless of which ad account the Video Shopping Ads or Product Shopping Ads were created in.
  - If specific products from a TikTok Shop have been occupied by enabled Product GMV Max Campaigns, you can no longer create new enabled Video Shopping Ads or Product Shopping Ads that promote these products from the TikTok Shop. 
  
### Identity level
Live GMV Max Campaigns are incompatible with enabled [Live Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1754162402455554) that use **the same identity** (TikTok account).
  - If you create an enabled Live GMV Max Campaign by using an identity associated with the official TikTok account of a TikTok Shop, any existing enabled Live Shopping Ad Groups using the same identity will be paused or impacted, regardless of which ad account the Live Shopping Ad Groups were created in.
  - If an enabled Live Shopping Ad Group has occupied an identity associated with a TikTok account that is not an official TikTok account of a TikTok Shop, you cannot use the identity in an enabled LIVE GMV Max Campaign. You will need to manually pause the existing enabled Live Shopping Ad Group using the same identity first. 
  - If an identity has been occupied by an enabled Live GMV Max Campaign, you can no longer create new enabled Live Shopping Ad Groups that use the same identity. 
  
# Steps
- See [Create Product GMV Max Campaigns](https://business-api.tiktok.com/portal/docs?id=1822009220448257) to find out how to create a Product GMV Max Campaign.
- See [Create LIVE GMV Max Campaigns](https://business-api.tiktok.com/portal/docs?id=1822009242546258) to find out how to create a LIVE GMV Max Campaign.
