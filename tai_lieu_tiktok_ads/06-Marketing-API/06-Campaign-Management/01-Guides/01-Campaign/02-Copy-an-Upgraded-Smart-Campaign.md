---
title: "Copy an Upgraded Smart+ Campaign"
doc_id: 1866529015923713
path: "Marketing API / Campaign Management / Guides / Campaign / Copy an Upgraded Smart+ Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Copy an Upgraded Smart+ Campaign

This article introduces how to copy an Upgraded Smart+ Campaign.
## Introduction
With the campaign copy feature, you can efficiently duplicate an entire Upgraded Smart+ Campaign, including multiple ad groups and ads.

**You can use Asynchronous Campaign Copy API to duplicate Upgraded Smart+ Campaigns while adjusting campaign, ad group, and ad (asset group) settings as needed. This helps you save time and focus on optimizing your overall advertising strategy.**


## Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details.
	- To copy a campaign, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the **"Steps"** section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.
- Asynchronous Campaign Copy API is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.
- Ensure that you have existing ads within your ad account that can be selected for duplication.

## Steps

1. **Decide on the Upgraded Smart+ campaigns, ad groups, and ads that you want to copy.**

You can filter the eligible Upgraded Smart+ campaigns, ad groups, and ads using [/smart_plus/campaign/get/](https://business-api.tiktok.com/portal/docs/get-upgraded-smart-campaigns/v1.3), [/smart_plus/adgroup/get/](https://business-api.tiktok.com/portal/docs/get-upgraded-smart-ad-groups/v1.3), and [/smart_plus/ad/get/](https://business-api.tiktok.com/portal/docs/get-upgraded-smart-ads/v1.3). The following requirements must be met.

``` xtable
|Level{15%}|Setting{17%}|Requirement{25%}|Parameters{18%}|Eligible configuration{25%}|
|---|---|---|---|---|
| Campaign | Advertising objective | Any of the following types:<ul><li>App promotion</li><li>Lead generation</li><li>Website conversions</li></ul> | `objective_type` | Any of the following values:<ul><li>`APP_PROMOTION`</li><li>`LEAD_GENERATION`</li><li>`WEB_CONVERSIONS`</li></ul>|
| Ad group | The number of undeleted ad groups in each source campaign. | 1-10 | / | / |
| Ad | The number of undeleted ads per ad group. | 1-30<br><br><p><span style="color:darkred"><b>Note</b></span>: In addition to the per-ad-group limit above, a global campaign limit of maximum 200 total ads that can be copied into the new target campaign applies.</p> | / | / |
| Creative | The number of creatives per ad. | 1-50<br><br><p><span style="color:darkred"><b>Note</b></span>: In addition to the per-ad limit above, a global campaign limit of maximum 1,000 total creatives that can be copied into the new target campaign applies.</p> | / | / |
```

2. **Create an asynchronous campaign copy task** using [/smart_plus/campaign/copy/task/create/](https://business-api.tiktok.com/portal/docs/create-an-asynchronous-copy-task-for-an-upgraded-smart-campaign/v1.3).

- Provide `campaign_id` (the source campaign ID obtained from Step 1), `advertiser_id`, `request_id` (to prevent duplicate requests), and optionally customize the campaign settings (`operation_status`, `campaign_name`, and `budget`).
- (Optional) Define the same schedule for all ad groups in the new campaign using `schedule_type`, `schedule_start_time`, and optionally `schedule_end_time`.
    - These parameters are supported for both default and custom copy modes. However, if you use default copy mode and attempt to copy ad groups with a delivery start time more than 12 hours earlier than the current time, we recommend defining a new schedule for all the new ad groups. Otherwise, you may see an error indicating that the schedule, with the start time already passed, cannot be copied into the new ad groups.
- (Optional) Define the same ad delivery arrangement for all ad groups in the new campaign using `dayparting`.
- Specify the copy mode through `deep_copy_mode`. You can choose between two copy modes:
    - <b>Default copy mode</b>. This mode copies all undeleted ad groups and ads from the source campaign to the new campaign.
      - To select the default copy mode, set `deep_copy_mode` to `DEFAULT` or omit `deep_copy_mode`.
      - Do not specify the fields `adgroup_list` and `ad_list` as they are ignored.
    - <b>Custom copy mode</b>. This mode only copies the specified undeleted ad groups and ads from the source campaign to the new campaign.
      - To select the custom copy mode, set `deep_copy_mode` to `CUSTOM`.
      - Provide `adgroup_list` with `adgroup_id` and `ad_list` simultaneously to specify the ad groups and ads to copy. Optionally, customize certain settings of the ad groups and ads in the new campaign.

The customizable ad group and ad settings are listed in the following table.

``` xtable
|Level{20%}|Customizable settings{80%}|
|---|---|
| Ad group | <ul><li>`operation_status`</li><li>`adgroup_name`</li><li>`budget`</li><li>`min_budget`</li><li>`location_ids`</li><li>`zipcode_ids`</li><li>`audience_ids`</li><li>`excluded_audience_ids`</li><li>`saved_audience_id`</li></ul>|
| Ad | <ul><li>`operation_status`</li><li>`ad_name`</li><li>`ad_format`</li><li>`video_info`</li><li>`image_info`</li><li>`music_info`</li><li>`aigc_disclosure_type`</li><li>`tiktok_item_id`</li><li>`identity_type`</li><li>`identity_id`</li><li>`identity_authorized_bc_id`</li><li>`ad_text_list`</li><li>`call_to_action_list`</li><li>`landing_page_url_list`</li><li>`utm_params`</li><li>`call_to_action_id`</li></ul>|
```

You can create one copy task for one campaign each time. After you create the task, you receive the task ID (`task_id`).

3. **Check the results of the asynchronous campaign copy task** by passing the `task_id` obtained from Step 2 to [/smart_plus/campaign/copy/task/check/](https://business-api.tiktok.com/portal/docs/get-the-results-of-an-asynchronous-copy-task-for-an-upgraded-smart-campaign/v1.3).

We recommend waiting approximately five minutes after the task is created before checking the task result.


