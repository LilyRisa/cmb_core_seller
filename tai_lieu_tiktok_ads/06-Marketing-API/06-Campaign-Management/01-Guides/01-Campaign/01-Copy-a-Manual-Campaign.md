---
title: "Copy a Manual Campaign"
doc_id: 1788434533212162
path: "Marketing API / Campaign Management / Guides / Campaign / Copy a Manual Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Copy a Manual Campaign

This article introduces how to copy a Manual Campaign.

To copy an Upgraded Smart+ Campaign, see [Copy an Upgraded Smart+ Campaign](https://business-api.tiktok.com/portal/docs/copy-an-upgraded-smart-campaign/v1.3) instead.

# Introduction
With the campaign copy feature, you can efficiently duplicate an entire Manual Campaign, including multiple ad groups and ads. 

**You can use Asynchronous Campaign Copy API to duplicate Manual Campaigns while adjusting campaign, ad group, and ad settings as needed. This helps you save time and focus on optimizing your overall advertising strategy.**

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To copy a campaign, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the **"Steps"** section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  
- Asynchronous Campaign Copy API is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.
- Ensure that you have existing ads within your ad account that can be selected for duplication.

# Steps
1. **Decide on the Manual campaigns, ad groups, and ads that you want to copy.**

You can filter the eligible campaigns, ad groups, and ads using [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986), [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922), and [/ad/get/](https://business-api.tiktok.com/portal/docs?id=1735735588640770). Note that the following requirements must be met.

> <span style="color:DodgerBlue">**Note**</span><br>
> If you copy a campaign that contains ad groups bound to a split test, only the ad groups will be copied. Split test groups associated with those ad groups will not be created.

```xtable
| Level {10%}| Setting {18%}| Requirement {25%}| Parameters {22%}| Eligible configuration{25%} |
|---|---|---|---|---|
| Campaign | Advertising objective | Any of the following types: <ul><li> Reach </li><li> Traffic </li><li> Video views </li><li> Community interaction </li><li> App promotion </li><li> Lead generation </li><li> Website conversions </li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: Community interaction livestreaming ad groups cannot be copied.  | `objective_type` | Any of the following values: <ul><li>`REACH`</li><li>`TRAFFIC`</li><li>`VIDEO_VIEWS`</li><li>`ENGAGEMENT`</li><li> `APP_PROMOTION` </li><li>`LEAD_GENERATION` </li><li>`WEB_CONVERSIONS`</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: Ad groups belonging to an `ENGAGEMENT` campaign and with a `promotion_type` of `LIVE_SHOPPING` cannot be copied. |
| Campaign | Smart+ Campaign | Disabled | `is_smart_performance_campaign` |  `false`  |
| Ad group | The number of undeleted ad groups in each campaign to be copied.  | 1-20 | / | / |
| Ad group | Automated Creative Optimization (ACO) | Disabled <br><br><p><span style="color:darkred"><b>Note</b></span>: Smart Creative ad groups can be copied. | `creative_material_mode` | Any of the following values: <ul><li>`CUSTOM`</li><li>`SMART_CREATIVE` (Smart Creative ad groups) </li></ul>  |
| Ad | The number of undeleted ads in each ad group to be copied. |<ul><li> For non-ACO non-Smart Creative ad groups: 1-50.</li><li> For Smart Creative ad groups: no specific requirements, as each group already has at least one auto-generated ad.</li></ul>  | / | / |
```

**Example**

Filter undeleted non-SPC App promotion campaigns

```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/campaign/get/?advertiser_id={{advertiser_id}}&filtering={"primary_status": "STATUS_NOT_DELETE","objective_type":"APP_PROMOTION","is_smart_performance_campaign": false}&page=1&page_size=1000' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Filter undeleted non-ACO, non-Smart Creative ad groups within a campaign
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/adgroup/get/?advertiser_id={{advertiser_id}}filtering={"campaign_ids":["{{campaign_id}}"],"primary_status":"STATUS_NOT_DELETE", "creative_material_mode":"CUSTOM"}&page=1&page_size=1000' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Filter undeleted Smart Creative ad groups within a campaign
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/adgroup/get/?advertiser_id={{advertiser_id}}&filtering={"campaign_ids":["{{campaign_id}}"],"primary_status":"STATUS_NOT_DELETE", "creative_material_mode":"SMART_CREATIVE"}&page=1&page_size=1000' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Filter undeleted ads within an ad group
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/ad/get/?advertiser_id={{advertiser_id}}&filtering={"adgroup_ids":["{{adgroup_id}}"],"primary_status":"STATUS_NOT_DELETE"}&page=1&page_size=1000' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
2. **Create an asynchronous campaign copy task** using [/campaign/copy/task/create/](https://business-api.tiktok.com/portal/docs?id=1788434394151938).
- Provide `campaign_id` (the original campaign ID obtained from Step 1) , `advertiser_id` , `request_id` (to prevent duplicate requests), and optionally, customize the campaign settings (`operation_status`, `campaign_name`, and `budget`).
- (Optional) Define the same schedule for all ad groups in the new campaign using` schedule_type`, `schedule_start_time`, and optionally `schedule_end_time`. 
  - These parameters are supported for both default and custom copy mode. However, if you use default copy mode and attempt to copy ad groups with a delivery start time more than 12 hours earlier than the current time, we recommend defining a new schedule for all the new ad groups. Otherwise, you may see an error indicating that the schedule, with the start time already passed, cannot be copied into the new ad groups.
- Specify the copy mode through `deep_copy_mode`. You can choose between two copy modes:
  - **Default copy mode**. This mode copies all undeleted ad groups and ads from the original campaign to the new campaign. 
    - To select the default copy mode, set `deep_copy_mode` to `DEFAULT` or omit `deep_copy_mode`.
    - Do not specify the fields `adgroup_list`, `ad_list`, and `smart_creative_info` as they will be ignored.
  - **Custom copy mode**. This mode only copies the specified undeleted ad groups and ads from the original campaign to the new campaign. 
    - To select the custom copy mode, set `deep_copy_mode` to `CUSTOM`.
    - For non-ACO non-Smart Creative ad groups, provide `adgroup_list` with `adgroup_id` and `ad_list` simultaneously to specify the ad groups and ads to copy. Optionally, customize certain settings of the ad groups and ads in the new campaign. Do not pass `smart_creative_info`. 
		- The customizable ad group and ad settings are listed in the following table.

```xtable
| Level {15%}| Customizable settings {40%}|
|---|---|
| Ad group | <ul><li>`operation_status` </li><li>`adgroup_name` </li><li>`location_ids` </li><li>`zipcode_ids` </li><li>`audience_ids` </li><li>`excluded_audience_ids` </li><li>`budget`</li><li>`schedule_type` </li><li>`schedule_start_time` </li><li>`schedule_end_time` </li></ul>|
| Ad |<ul><li> `operation_status` </li><li>`ad_name` </li><li>`identity_type` </li><li>`identity_id` </li><li>`identity_authorized_bc_id` </li><li>`video_id` </li><li>`image_ids` </li><li>`music_id` </li><li>`tiktok_item_id` </li><li>`ad_text` </li><li>`aigc_disclosure_type`</li><li>`tracking_pixel_id` </li><li>`tracking_app_id` </li></ul>|
```

   - For Smart Creative ad groups, provide `adgroup_list` with `adgroup_id` to specify the ad group to copy. Optionally, customize the ad creatives used to generate Smart Creative ads in the new ad group through `smart_creative_info`. Do not pass `ad_list`.   
        - All settings that you can specify through [/ad/aco/create/](https://business-api.tiktok.com/portal/docs?id=1739473063234626), except for `is_smart_creative`, can be customized.

You can create one copy task for one campaign each time. After you create the task, you'll get the task ID (`task_id`).

3. **Check the results of the asynchronous campaign copy task** by passing the `task_id` obtained from Step 2 to [/campaign/copy/task/check/](https://business-api.tiktok.com/portal/docs?id=1788434463507458). 

We recommend waiting approximately one minute after the task is created before checking the task result.

