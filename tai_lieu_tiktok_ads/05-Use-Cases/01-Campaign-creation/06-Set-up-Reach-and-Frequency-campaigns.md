---
title: "Set up Reach & Frequency campaigns"
doc_id: 1758528372513794
path: "Use Cases / Campaign creation / Set up Reach & Frequency campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Set up Reach & Frequency campaigns

This article walks you through the steps to set up Reach & Frequency (R&F) campaigns.

# Introduction
R&F is a campaign buying type that enables predictable reach, controlled frequency and guaranteed delivery with a more flexible and transparent ad experience. If you want to control the audiences you reach and the number of times they are exposed to your ad messaging before delivery, you can create a R&F campaign. 

**You can use Campaign Management API to create R&F ads, and this helps streamline your ad creation experience, and elevate operational efficiency and scalability.**

# Prerequisites
- You've gained access to Tiktok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937&rid=7llhcla7zmh) for details. 
  - To use R&F endpoints, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946&rid=7llhcla7zmh) to find out how to configure permissions.  
- The Reach and Frequency feature is in GA. Developers can build integrations with this feature. However, the integations would only work if the ad accounts are allowlisted for this feature. To have your ad accounts allowlisted, please reach out to your TikTok representative, and sign a Commercial Contract for Branding.
- Currently, only `RF_REACH` advertising objective is available for R&F type ads.

# Steps
1. [Create a Reach & Frequency campaign](https://ads.tiktok.com/marketing_api/docs?id=1744571205201922). The creation workflow includes: 
 	 1. Create a R&F campaign.
	  2. Obtain estimated available ad inventory.
	  3. [Optional] Create a budget & impression or budget & reach chart.
	  4. Create an ad group.
 	 5. Create ads.
2. If you want to manage your R&F campaign, for example, update the campaign or update the ad status, see [Manage a Reach & Frequency campaign](https://ads.tiktok.com/marketing_api/docs?id=1744571207714818). 

# Related docs
- [Reach & Frequency API](https://ads.tiktok.com/marketing_api/docs?id=1738235311704065)
- [Reach & Frequency Guide](https://ads.tiktok.com/marketing_api/docs?id=1739470723315713)
