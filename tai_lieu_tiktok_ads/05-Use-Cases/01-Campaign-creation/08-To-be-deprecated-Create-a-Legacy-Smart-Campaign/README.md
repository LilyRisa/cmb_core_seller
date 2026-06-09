---
title: "(To be deprecated) Create a Legacy Smart+ Campaign"
doc_id: 1768006268820546
path: "Use Cases / Campaign creation / (To be deprecated) Create a Legacy Smart+ Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To be deprecated) Create a Legacy Smart+ Campaign

This article walks you through the steps to create a Smart+ Campaign. 

> <span style="color:DarkRed">**Important**</span><br>
> - **Upgraded Smart+ API**:
>     - We are actively upgrading the Smart+ API to deliver enhanced capabilities and performance. The new Upgraded Smart+ API is already launched. Learn more about [Upgraded Smart+ Campaign](https://business-api.tiktok.com/portal/docs?id=1853452461203458) and [available Upgraded Smart+ API endpoints](https://business-api.tiktok.com/portal/docs?id=1843301794874370).
>     - **Get started**:
>         - **Reporting**: No additional allowlist required.
>         - **Campaign Management**: To access the upgraded Smart+ experience, contact your TikTok sales representative to have your ad account allowlisted. If your ad account is already in the allowlist, you can directly use the Upgraded Smart+ API creation and modification endpoints.  
> - **Legacy API Deprecation**: 
>     - After **March 31, 2026**, you will no longer be able to create legacy Smart+ Campaigns using the [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362) endpoint.
>     - Note that legacy Smart+ Campaigns that have not been migrated will not be transitioned to the new API automatically. Attempts to use the legacy Smart+ API endpoint afterward will result in errors, rendering new campaign creation via this endpoint unavailable.
> - **Legacy API deprecation extension request process for existing legacy Smart+ creation developers**:
>     - 1. **Submit a request**: If you require an extension, please submit an application via the [External Extension Request Form for Legacy Smart+ Creation API](https://bytedance.sg.larkoffice.com/share/base/form/shrlgsm2yMsQaSoAsMy77tcAzng).
>     - 2. **Allowlist processing**: After receiving your request, we will process an allowlist entry for the associated developer app ID. After the process, developers will be able to continue calling the `/campaign/spc/create/` endpoint until **June 30, 2026**.
>     - 3. **Extended timeline for strong business needs**: If you have a strong business case requiring an extension beyond June 30, 2026, please specify your use case in the application form, or contact your sales representative directly.

# Introduction
[Smart+ Campaign](https://ads.tiktok.com/help/article/about-smart-plus-campaign?lang=en) is TikTok's AI-powered automation solution to maximize your delivery outcomes with less effort from your side. We provide you with multiple creatives and bids for auctions which lead to stronger performance of your campaigns when you choose to create a Smart+ Campaign. Smart+ Campaign supports both `APP_PROMOTION`, `WEB_CONVERSIONS`, and `LEAD_GENERATION` objectives. 
<br>**You can use Smart+ API to create a Smart+ Campaign, and this helps streamline your ad creation experience, and elevate operational efficiency and scalability.**

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937&rid=7llhcla7zmh) for details.
  - To create a Smart+ Campaign, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946&rid=7llhcla7zmh) to find out how to configure permissions.

# Steps
> <span style="color: DodgerBlue">**Note**</span><br>
>  <ul><li>You only need to call this campaign level endpoint ([/campaign/spc/create/](https://ads.tiktok.com/marketing_api/docs?id=1767334204047362)) to create a Smart+ Campaign. There is no need to call other ad group or ad level endpoints. </li><li>If you want to update a Smart+ Campaign, use [/campaign/spc/update/](https://ads.tiktok.com/marketing_api/docs?id=1767334250066945). </li></ul>

Based on your preference for the advertising objective, you can create Smart+ Web Campaigns, Smart+ App Campaigns, or Smart+ Lead Generation Campaigns.
- To learn about how to create Smart+ Web Campaigns, see [Create Legacy Smart+ Web Campaigns](https://business-api.tiktok.com/portal/docs?id=1814122471133186).
- To learn about how to create Smart+ App Campaigns, see [Create Legacy Smart+ App Campaigns](https://business-api.tiktok.com/portal/docs?id=1814122492598274).
- To learn about how to create Smart+ Lead Generation Campaigns, see [Create Legacy Smart+ Lead Generation Campaigns](https://business-api.tiktok.com/portal/docs?id=1827645480080385).

# API reference
Use these endpoints to manage Smart+ Campaigns:
- [/campaign/spc/create/](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1767334204047362)
- [/campaign/spc/update/](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1767334250066945)
- [/campaign/spc/get/](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1767334299811842)
- [/campaign/spc/report/get/](https://business-api.tiktok.com/portal/docs?id=1776454619412481)

