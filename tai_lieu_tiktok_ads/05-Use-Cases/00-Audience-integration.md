---
title: "Audience integration"
doc_id: 1758526010304514
path: "Use Cases / Audience integration"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Audience integration

This article walks you through the steps of audience integration. 
# Introduction
Audience is an ad targeting option that enables TikTok For Business advertisers to target or exclude specific users when setting up ads. TikTok API for Business supports different [audience types](https://ads.tiktok.com/marketing_api/docs?id=1746998648212482). Custom Audience is an ad targeting option that lets you find people who already know or have engaged with your business, while Lookalike Audience is based on your existing Custom Audiences. <strong>You can use Audience API to automate CSV file upload without manual effort or build Lookalike Audience to find similar users on TikTok.</strong>

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937&rid=7llhcla7zmh) for details. 
  - To integrate the audience, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946&rid=7llhcla7zmh) to find out how to configure permissions.  
- By using the Audience API endpoints, you agree to the [TikTok Custom Audiences (Customer File) Terms](https://ads.tiktok.com/i18n/official/policy/custom-audience-terms).

# Steps
1. Create an audience using one of the following methods. The ID of the audience that you just created will be returned in the response.
- If you have a pre-defined audience list, you can use Customer File API to upload your audience. See [Create Customer File Audiences - Customer File API](https://ads.tiktok.com/marketing_api/docs?id=1747012327159809) for detailed steps. 
- If you want to send a stream of data to create audiences for multiple advertisers simultaneously, you can use Streaming API. See [Create Customer File Audiences - Streaming API](https://ads.tiktok.com/marketing_api/docs?id=1747012327159809) for detailed steps. 


> <span style="color: DodgerBlue">**Note**</span><br>To apply for access to the Streaming API (formerly known as Segment API), use Allowlist Management feature on the Developer Portal by selecting "Streaming API" from the drop down menu. We review applications on an ongoing basis. 

- If you want to use rules to create audiences, for example, audiences who have clicked the ad in the past seven days and audiences who have added products to the cart but haven't purchased them in the last 14 days, see [Create audiences by rules](https://ads.tiktok.com/marketing_api/docs?id=1739566532187138) for detailed steps. Engagement Audiences, App Activity Audiences, Website Traffic Audiences, or Lead Generation Audiences are created in this way. 
- If you want to create an audience who is similar to your existing audience, you can create a Lookalike Audience. See [Create Lookalike Audiences](https://ads.tiktok.com/marketing_api/docs?id=1747012174934018) for detailed steps. 
2. After audience creation, you can include or exclude audiences in a **single** ad group, or apply audiences to or disconnect audiences from **multiple** ad groups.
- In [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114) or [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745), include/exclude the audience in a **single** ad group. 
  - To include audiences in an ad group, pass in audience IDs to the `audience_ids` field.
  - To exclude audiences from an ad group, pass in audience IDs to the `excluded_audience_ids` field.
- In [/dmp/custom_audience/apply/](https://ads.tiktok.com/marketing_api/docs?id=1758615097524225), apply/disconnect the audience in **multiple** ad groups. 
  - To apply audience to ad groups, set `action_mode` as `Apply`. You can choose whether this audience is included in or excluded from your ad groups by specifying `usage_mode` as `Include` or `Exclude`. 
  - To disconnect audience from  ad groups, set `action_mode` as `Disconnect`. You can disconnect the audience that is included in or excluded from your ad groups.

# Related docs
[Audience API](https://ads.tiktok.com/marketing_api/docs?id=1739940398505986)

[Audience Management Guide](https://ads.tiktok.com/marketing_api/docs?id=1739566496956417)



