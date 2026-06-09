---
title: "(To be deprecated) Legacy Smart+ Campaign"
doc_id: 1767959274087425
path: "Marketing API / Campaign Management / Guides / Campaign / (To be deprecated) Legacy Smart+ Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To be deprecated) Legacy Smart+ Campaign

Smart+ Campaign is TikTok's AI-powered automation solution to maximize your delivery outcomes with less effort from your side. We provide you with multiple creatives and bids for auctions which lead to stronger performance of your campaigns when you choose to create a Smart+ Campaign.

Smart+ Campaign supports  `APP_PROMOTION`, `WEB_CONVERSIONS`, and `LEAD_GENERATION` objectives. 

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

# Create a Smart+ Campaign
See [Create a Smart+ Campaign](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1768006268820546)  for detailed steps. 

# Access reporting data for Smart+ Campaigns and Smart Performance Web Campaigns
> <span style="color:DodgerBlue">**Note**</span><br>
> Smart Performance Web Campaigns will be deprecated in the next API version. To ensure a smooth API integration, we recommend that you upgrade to [Smart+ Web Campaigns](https://business-api.tiktok.com/portal/docs?id=1814122471133186) as soon as possible. Smart+ Web Campaigns are a new AI-powered automation solution that leverages machine learning to drive business outcomes for advertisers.

You can access reporting data for Smart+ Campaigns and Smart Performance Web Campaigns at the campaign, ad group, or creative level.
- To access the campaign or ad group level data, use [/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353) or [/report/task/create/](https://business-api.tiktok.com/portal/docs?id=1740302766489602). 
- To access the creative level data, use [/campaign/spc/report/get/](https://business-api.tiktok.com/portal/docs?id=1776454619412481).

