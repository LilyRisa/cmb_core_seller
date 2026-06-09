---
title: "Create an ad group"
doc_id: 1738857874837506
path: "Marketing API / Campaign Management / Guides / Ad group / Create an ad group"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an ad group

This article introduces how to create an ad group. 

# Steps

Use [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114) to create an ad group. You can define the following settings at the ad group level. 

To understand how ad group-level features in TikTok Ads Manager correspond to API parameters, see [Mapping between campaign features in TikTok Ads Manager and API configurations](https://business-api.tiktok.com/portal/docs?id=1740673047618561).

- General info 
  - `advertiser_id`: Advertiser ID. 
  - `campaign_id`:  Campaign ID. To obtain a campaign ID, create a campaign using [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602) and obtain the `campaign_id` from the response.
  - `adgroup_name`: A descriptive name of your ad group.

- Optimization location
  - Use `promotion_type` to decide the place where the optimization goal you want happens. 

- Placements 
  - Use `placement_type` and `placements` to determine where your ads will be shown. See [Placements](https://ads.tiktok.com/marketing_api/docs?id=1745292441739265) for details. 

- Targeting 
  - See [Targeting](https://ads.tiktok.com/marketing_api/docs?id=1745292439329793) for different targeting options.  
  - **Recommendation**: You can use [/ad/audience_size/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1740302379236353) to estimate audience size. The parameters for this endpoint are similar to the ones for creating an ad group.

- Budget
  - Use `budget_mode`, `budget` and `budget_optimize_on` to define your budget. See [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114) for details. 
	  
- Schedule 
  - Use `schedule_type`, `schedule_start_time` and `schedule_end_time` to decide when you want to run your ads.  
  - Use `dayparting` to arrange your ad delivery. 

- Bidding 
  - Use `bid_type`, `billing_event`, `deep_bid_type` and other related parameters to decide how you want the system to spend your budget. See [Bidding](https://ads.tiktok.com/marketing_api/docs?id=1745292444424193) for details.  
	  
- Optimization 
  - Use `optimization_goal` to determine the measurable results you'd like to drive with your ads. See [Enumeration - Optimization Goal](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for the allowed values. 

To find out the details of other parameters, see the [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114) article.

## Example 
```xcodeblock
(code)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "WEBSITE",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "video_download_disabled": false,
    "location_ids": ["{{location_id}}"],
    "gender": "GENDER_UNLIMITED",
    "operating_systems": ["ANDROID"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_end_time": "{{schedule_end_time}}",
    "schedule_start_time": "{{schedule_start_time}}",
    "optimization_goal": "CLICK",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "CPC",
    "pacing": "PACING_MODE_SMOOTH",
      "operation_status": "ENABLE"
}'
(/code)
```

# Next step
[Create an ad](https://ads.tiktok.com/marketing_api/docs?id=1745292553358338)
