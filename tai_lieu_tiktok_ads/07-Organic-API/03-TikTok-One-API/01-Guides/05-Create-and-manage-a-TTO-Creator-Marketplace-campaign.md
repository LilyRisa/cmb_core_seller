---
title: "Create and manage a TTO Creator Marketplace campaign"
doc_id: 1828929329184770
path: "Organic API / TikTok One API / Guides / Create and manage a TTO Creator Marketplace campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create and manage a TTO Creator Marketplace campaign

This article outlines how to create and manage TikTok One (TTO) Creator Marketplace campaigns via API.

To generate campaigns via API, all API Partners must use their own TTO accounts. If you have any questions, reach out to your TikTok Partner Manager.


# Steps
## 1. Create TTO Creator Marketplace campaigns
After confirming the list of Creators you would like to invite to the Campaign, call [/tto/tcm/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1815693549459458) to create campaigns for the Creators. 

You can create a campaign along with any of the following invite link types:
- A [campaign-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Campaign-level%20invite%20link) to associate videos with a specific campaign.
- A [brand-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Brand-level%20invite%20link) to associate videos with your brand account.

> <span style="color:DodgerBlue">**Note**</span><br>
> - If `default_spark_ads_requested_authorization_days` is passed, this will be applied to all videos uploaded to the invite link.



## 2. Share the campaign-level or brand-level invite link with Creators
Creators must use the campaign-level or brand-level invite link when uploading their video to TikTok. Once the video has been linked, performance reporting becomes available.

## 3. Retrieve the campaign
After generating a TTO Creator Marketplace campaign, developers can use [/tto/tcm/campaign/](https://business-api.tiktok.com/portal/docs?id=1815693562720258) to retrieve information on the campaign. After a video has been linked to the campaign, `/tto/tcm/campaign/` will return the corresponding video ID.

## 4. Access performance reporting
After a video has been uploaded and linked to a TTO Creator Marketplace campaign, use [/tto/tcm/report/](https://business-api.tiktok.com/portal/docs?id=1815693573340162) to obtain performance data on the video.

