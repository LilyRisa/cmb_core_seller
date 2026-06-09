---
title: "Create a Manual Campaign"
doc_id: 1737719523561474
path: "Marketing API / Campaign Management / Guides / Campaign / Create a Manual Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Manual Campaign

This article introduces how to create a campaign. 
# Steps
Use [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602) to create a regular campaign.  You can define the following settings at the campaign level. 

To understand how campaign-level features in TikTok Ads Manager correspond to API parameters, see [Mapping between campaign features in TikTok Ads Manager and API configurations](https://business-api.tiktok.com/portal/docs?id=1740673047618561).

- `advertiser_id`: ID of your ad account. You can get the ID from your TikTok For Business account.
- `campaign_name`: A descriptive name of your campaign. 
- `objective_type`: The advertising objective of your campaign. See [Advertising objectives](https://ads.tiktok.com/marketing_api/docs?id=1737585562434561) for details. 
- `budget_mode`: The budget mode can be total (`BUDGET_MODE_TOTAL`), daily (`BUDGET_MODE_DAY`), dynamic daily (`BUDGET_MODE_DYNAMIC_DAILY_BUDGET`), or infinite (`BUDGET_MODE_INFINITE`). The budget mode can be set at the campaign level or the ad group level. See [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114) for details. 
- `budget`: If `budget_mode` is total, daily, or dynamic daily, you need to set a budget.
- `budget_optimize_on`: Whether you want to enable Campaign Budget Optimization (CBO). Once CBO is enabled (`budget_optimize_on` = `TRUE`), you need to set the campaign-level `budget_mode` and `budget`. Other CBO-related fields such as `bid_type`, `deep_bid_type`, `optimization_goal` are set at the ad group level. See [Campaign Budget Optimization](https://ads.tiktok.com/marketing_api/docs?id=1739381757818881) for details. 


 To find out the details of other parameters, see the [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602) article. 
 
 ## Example
 ```xcodeblock
 (code)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "TRAFFIC",
    "campaign_name": "{{campaign_name}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}}
}'
 (/code)
```

# Next step
[Create an ad group](https://ads.tiktok.com/marketing_api/docs?id=1738857874837506)

