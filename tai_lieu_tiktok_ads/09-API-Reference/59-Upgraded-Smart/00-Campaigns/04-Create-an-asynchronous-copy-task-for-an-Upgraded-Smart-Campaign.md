---
title: "Create an asynchronous copy task for an Upgraded Smart+ Campaign"
doc_id: 1866528879472641
path: "API Reference / Upgraded Smart+ / Campaigns / Create an asynchronous copy task for an Upgraded Smart+ Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an asynchronous copy task for an Upgraded Smart+ Campaign

Use this endpoint to create an asynchronous copy task for an Upgraded Smart+ Campaign.

You can only copy one campaign at a time. To learn about the detailed steps, see [Copy an Upgraded Smart+ Campaign](https://business-api.tiktok.com/portal/docs/copy-an-upgraded-smart-campaign/v1.3). After you create the task, use [/smart_plus/campaign/copy/task/check/](https://business-api.tiktok.com/portal/docs/get-the-results-of-an-asynchronous-copy-task-for-an-upgraded-smart-campaign/v1.3) to check the results.

> <span style="color:DodgerBlue">**Note**</span><br>
> - Asynchronous Campaign Copy API is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.
> - The rate limits for this endpoint are 1 query per second (QPS) and 30 queries per minute (QPM) per developer app. [Global rate limits](https://business-api.tiktok.com/portal/docs?id=1740029171730433#item-link-Global%20rate%20limits) are not applicable to this endpoint.

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/copy/task/create/


**Method** POST

**Header**

```xtable
|Field{35%}|Type{15%}|Description{50%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs/marketing-api-authentication/v1.3).|
|Content-Type {Required}|string|Request message type.<br>Allowed value: `application/json`.|
```

**Parameters**

``` xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|request_id {Required}|string|Request ID that supports idempotency to prevent you from sending the same request twice. If you retry requests with the same request ID multiple times within the 10-second cache time, then only one request will succeed. If a duplicate request with the expired request ID is received after the cache time, the server will treat it as a new request and process it accordingly.<br><br>It is different from the `request_id` returned in the response parameters, which is used to uniquely identify an HTTP request.<br><br>The value should be a string representation of a 64-bit integer.<br><br>Example: `"123456789"`.|
|campaign_id {Required}|string|The ID of an Upgraded Smart+ Campaign that you want to copy. <br><br>To retrieve Upgraded Smart+ Campaigns within your ad account, use [/smart_plus/campaign/get/](https://business-api.tiktok.com/portal/docs/get-upgraded-smart-campaigns/v1.3).<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The source campaign must use one of these advertising objectives: `APP_PROMOTION`, `LEAD_GENERATION`, `WEB_CONVERSIONS`</li><li>The source campaign must not be deleted.</li><li>The source campaign must contain at least 1 undeleted ad group, which must contain at least 1 undeleted ad.</li><li>The source campaign must satisfy all per-level limits, and the total copied assets must also not exceed the global new campaign limits:<ul><li>On the source campaign: you may have a maximum of 10 undeleted ad groups, each ad group may contain a maximum of 30 undeleted ads, and each individual ad may use a maximum of 50 creatives.</li><li>Separately, there are hard global caps that apply to the resulting new campaign: no more than 200 total ads may be copied over, and no more than 1,000 total creatives may be copied into the new campaign.</li></ul></li></ul>|
|operation_status|string|The status of the new campaign when created.<br><br>Enum values:<ul><li>`ENABLE`: The campaign is enabled when created.</li><li>`DISABLE`: The campaign is disabled when created.</li></ul><br>Default value: `DISABLE`.<br><br>If you want to update the status of the campaign after creation, use [/smart_plus/campaign/status/update/](https://business-api.tiktok.com/portal/docs/update-the-operation-statuses-of-upgraded-smart-campaigns/v1.3).|
|campaign_name|string|The name for the new campaign.<br><br>Length limit: 512 characters. Emojis are not supported. Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character.<br><br>If not specified, this field will default to `"COPIED_&#123;&#123;name_of_the_source_campaign&#125;&#125;"`. For instance, if the source campaign is named `"FIRST_CAMPAIGN"` and this field is not specified, the name of the new campaign will be `"COPIED_FIRST_CAMPAIGN"`.<br><br>|
|budget|number|The budget for the new campaign or the budget limit for all ad groups under the new campaign.<ul><li>When `budget_optimize_on` of the source campaign is `true`, this field represents the fixed budget or initial budget for the new campaign.<ul><li>When `budget_auto_adjust_strategy` is `UNSET`, this field represents the fixed budget for the new campaign.</li><li>When `budget_auto_adjust_strategy` is `AUTO_BUDGET_INCREASE`, this field represents the initial budget for the new campaign. To retrieve the current campaign budget, use [/smart_plus/campaign/get/](https://business-api.tiktok.com/portal/docs/get-upgraded-smart-campaigns/v1.3) after the copy task is completed and check the returned `current_budget`.</li></ul></li><li>When `budget_optimize_on` of the source campaign is `false`, this field represents the budget limit for all ad groups under the new campaign.<ul><li>When `budget_mode` is `BUDGET_MODE_DAY`, this field represents the daily limit for all ad groups under the new campaign.</li><li>When `budget_mode` is `BUDGET_MODE_TOTAL`, this field represents the total limit for all ad groups under the new campaign.</li></ul></li></ul><br>If not specified, this field will default to the budget of the source campaign or the budget limit for all ad groups under the source campaign.|
|schedule_type|string|<ul><li>Specify this field only when you want to set the same schedule for all ad groups in the new campaign.</li><li>If you want to use schedules of the source ad groups in the new campaign, leave this field unspecified.</li></ul><br>Schedule type for all new ad groups.<br><br>Enum values:<ul><li>`SCHEDULE_START_END`: Set start time and end time to run ad groups. You need to pass both `schedule_start_time` and `schedule_end_time` at the same time.</li><li>`SCHEDULE_FROM_NOW`: Set start time to run ad groups continuously. You need to pass `schedule_start_time` and the end time will be automatically set to 10 years later than the start time.</li></ul>|
|schedule_start_time {+Conditional}|string|Required when `schedule_type` is passed.<br><br>Schedule start time (UTC+0) for all new ad groups, in the format of `YYYY-MM-DD HH:MM:SS`.<br><br>The start time can be up to 12 hours earlier than the current time, but cannot be later than `2028-01-01 00:00:00`.|
|schedule_end_time {+Conditional}|string|<ul><li>Required when `schedule_type` is `SCHEDULE_START_END`.</li><li>Not supported when `schedule_type` is `SCHEDULE_FROM_NOW`.</li></ul><br>Schedule end time (UTC+0) for all new ad groups, in the format of `YYYY-MM-DD HH:MM:SS`.<br><br>The end time cannot be later than `2038-01-01 00:00:00`.|
|dayparting|string|<ul><li>Specify this field only when you want to set the same ad delivery arrangement for all ad groups in the new campaign.</li><li>If you want to use the same ad delivery arrangements of the source ad groups in the new campaign, leave this field unspecified.</li></ul><br>Ad delivery arrangement, in the format of a string that consists of 48 x 7 characters. Each character is mapped to a 30-minute timeframe from Monday to Sunday. Each character can be set to either 0 or 1. 1 represents delivery in the 30-minute timeframe, and 0 stands for non-delivery in the 30-minute timeframe. The first character is mapped to 0:01-0:30 of Monday; The second character is mapped to 0:31-1:00 of Monday, and the last character represents 23:31-0:00 Sunday.<br><br><p><span style="color:darkred"><b>Note</b></span>: An all-1 value and when this field is not specified, are considered full-time delivery.|
|deep_copy_mode|string|The copying mode determining how you create ad groups and ads (asset groups) in the new campaign. <br><br>Enum values:<ul><li>`DEFAULT`: The default copy mode. You copy all undeleted ad groups and ads from the source campaign to the new one. You can omit the `adgroup_list` field, as the API ignores it in this mode.</li><li>`CUSTOM`: The custom copy mode. You copy only the specified undeleted ad groups and ads from the source campaign to the new one. You also have options to customize specific ad group or ad settings to override the source configurations.<ul><li>When using this mode, you need to provide the `adgroup_list` field simultaneously. You can copy a maximum of 20 ads per ad group, across a maximum of 30 ad groups.</li></ul></li></ul> Default value: `DEFAULT`.</li></ul>|
|adgroup_list|object[]|<ul><li>When `deep_copy_mode` is set to `DEFAULT` or not passed, this field is ignored.</li><li>When `deep_copy_mode` is set to `CUSTOM`, this field is required.</li></ul><br>The customized settings for ad groups in the new campaign.<br><br>Max size: 10.|
#|adgroup_id {+Conditional}|string|Required when `adgroup_list` is passed.<br><br>The ID of the ad group that you want to copy. The ad group should belong to the source campaign (`campaign_id`).|
#|operation_status|string|The status of the new ad group when created.<br><br>Enum values:<ul><li>`ENABLE`: The ad group is enabled when created.</li><li>`DISABLE`: The ad group is disabled when created.</li></ul><br>Default value: `ENABLE`.<br><br>If you want to update the status of the ad group after creation, use [/smart_plus/adgroup/status/update/](https://business-api.tiktok.com/portal/docs/update-the-operation-statuses-of-upgraded-smart-ad-groups/v1.3).|
#|adgroup_name|string|The name for the new ad group.<br><br>Length limit: 512 characters. Emojis are not supported.<br> Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character.<br><br>If not specified, this field will default to the name of the source ad group.|
#|budget|number|<ul><li>Valid when Campaign Budget Optimization (CBO) is disabled (`budget_optimize_on` is `false`) at the campaign level.</li><li>Ignored when CBO is enabled (`budget_optimize_on` is `true`) at the campaign level.</li></ul><br>Fixed budget or initial budget for the new ad group.<ul><li>When `budget_auto_adjust_strategy` of the source ad group is `UNSET`, this field represents the fixed budget for the new ad group.</li><li>When `budget_auto_adjust_strategy` is `AUTO_BUDGET_INCREASE`, this field represents the initial budget for the new ad group. To retrieve the current ad group budget, use [/smart_plus/adgroup/get/](https://business-api.tiktok.com/portal/docs/get-upgraded-smart-ad-groups/v1.3) after the copy task is completed and check the returned `current_budget`.</li></ul>|
#|min_budget|number|Valid only when the following conditions are all met:<ul><li>At the campaign level:<ul><li>`budget_optimize_on` is `true`</li><li>`budget_mode` is `BUDGET_MODE_DYNAMIC_DAILY_BUDGET`</li></ul></li><li>At the ad group level:<ul><li>`bid_type` is `BID_TYPE_NO_BID`</li></ul></li></ul><br>Ad group minimum budget.<br><br>The system will aim to spend at least this amount, but it is not guaranteed.|
#|targeting_spec|object|<ul><li>Specify this field only when you want to customize the targeting settings for the new ad group in the new campaign.</li><li>If you want to use the same targeting settings of the source ad group in the new campaign, leave this field unspecified.</li></ul><br>Targeting settings.|
##|location_ids|string[]|IDs of the locations that you want to target in the new ad group.<br><br>To get the available locations and corresponding IDs based on your placement and objective, use the [/tool/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1761236883355649) or [/tool/region/](https://business-api.tiktok.com/portal/docs?id=1737189539571713) endpoint.<br> To get the list of location IDs, see [Location IDs](https://business-api.tiktok.com/portal/docs?id=1739311040498689). <br><br><p><span style="color:darkred"><b>Note</b></span>: If you add the US as your target location, then you can not remove the US after ad group creation.|
##|zipcode_ids|string[]|Zip code IDs or postal code IDs that you want to use to target locations in the new ad group.<br><br>Max size: 3,000. If you provide both `location_ids` and `zipcode_ids`, the combined total of location IDs, zip code IDs, and postal code IDs cannot exceed 3,000 per ad group.<br><br>You can get the available zip code IDs or postal code IDs based on your placement, objective and keyword via `geo_id` (when `geo_type` = `ZIP_CODE`) returned from the [/tool/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1761236883355649) endpoint. <br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Zip code targeting is currently only supported for the US and postal code targeting is currently only supported for Canada, Brazil, Indonesia, Thailand, and Vietnam.</li><li>Targeting postal code areas in Brazil, Indonesia, Thailand, and Vietnam is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>You cannot use zip code targeting or postal code targeting in campaigns that have enabled special ad categories (`special_industries`).</li><li>Overlapping targeted locations are not supported. For instance, you cannot target the US and the state of California at the same time.</li><li>If you target locations in the US via `location_ids` or `zipcode_ids` during ad group creation, you can subsequently update those IDs to other US locations but you cannot remove all US locations to target only non-US countries.</li><li>To get information about the zip code IDs or postal code IDs, you can only use [/tool/targeting/info/](https://business-api.tiktok.com/portal/docs?id=1761237001980929).</li></ul>|
##|excluded_audience_ids|string[]|A list of audience IDs to be excluded.<br><br>To get a list of audience IDs, use [/dmp/custom_audience/list/](https://business-api.tiktok.com/portal/docs?id=1739940506015746). <br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level `rta_id` is specified, this field is not supported.|
##|audience_ids|string[]|A list of audience IDs.<br><br>To get audience IDs, use [/dmp/custom_audience/list/](https://business-api.tiktok.com/portal/docs?id=1739940506015746). <br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level `rta_id` is specified, this field is not supported.|
##|saved_audience_id|string|Valid when the following conditions are both met:<ul><li>The `targeting_optimization_mode` of the source ad group is `MANUAL`.</li><li>The category of Housing, Employment, or Credit (`specical_industries`) is NOT specified in your campaign.</li><li>TikTok placement is selected in your ad group (i.e., `placement_type` is set as `PLACEMENT_TYPE_AUTOMATIC` <b>or</b> `placement_type` is set as `PLACEMENT_TYPE_NORMAL` and `PLACEMENT_TIKTOK` is included in `placements`).</li></ul><br>Saved Audience ID.<br><br>To obtain the list of Saved Audiences within your ad account, use [/dmp/saved_audience/list/](https://business-api.tiktok.com/portal/docs?id=1780154619404290).<br><br>Before using this field, call [/dmp/saved_audience/create/](https://business-api.tiktok.com/portal/docs?id=1780154541898754) to create a Saved Audience and get the Saved Audience ID in response. The advertiser ID associated with your Saved Audience should be the same as the advertiser ID in your ad group. Otherwise, an error will occur.<br><br>If you use `saved_audience_id` to create an ad group, we will return both the Saved Audience ID and the targeting options that are included within your Saved Audience in response.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>When creating a Saved Audience via [/dmp/saved_audience/create/](https://business-api.tiktok.com/portal/docs?id=1780154541898754), you can specify various targeting options, including `gender`. However, be aware that if you are creating an ad group based on a Saved Audience, it’s essential to avoid setting both the `saved_audience_id` and targeting options (such as `gender`) defined within your Saved Audience at the same time.</li><li>In cases where the targeting settings in your Saved Audience conflict with those in your ad group, the settings from your Saved Audience will take precedence. As a result, the conflicting targeting options in your ad group will be ignored.<ul><li>For example, if you create a Saved Audience where `gender` is set to `GENDER_FEMALE` and then use that Saved Audience to create an ad group while specifying `gender` as `GENDER_MALE`, the resulting ad group will adopt `GENDER_FEMALE` as the gender targeting configuration, reflecting what is set in the Saved Audience.</li></ul></li><li>If the `saved_audience_id` was created with `age_groups` specified, the age restriction rules outlined in [New age restrictions for ads on TikTok](https://business-api.tiktok.com/portal/docs?id=1788755983247362) for different advertising objectives also apply. Make sure that the age targeting setting is allowed before you use the Saved Audience (`saved_audience_id` ) in the ad group.</li></ul>|
#|ad_list {+Conditional}|object[]|<ul><li>When `deep_copy_mode` is set to `DEFAULT` or not passed, this field is ignored.</li><li>When `deep_copy_mode` is set to `CUSTOM`, this field is required.<ul><li>If you don't want to customize the settings of the ads to be generated in the new ad group, only pass `ad_id` in this object array.</li><li>If you want to customize the settings of the ads to be generated in the new ad group, pass `ad_id` and other parameters simultaneously in this object array.</li></ul></li></ul><br>The settings for ads in the new ad group.<br><br>Max size: 30.<br><br><p><span style="color:darkred"><b>Note</b></span>: The maximum number of ads that you can specify in the new campaign is 200.</p>|
##|smart_plus_ad_id {+Conditional}|string|Required when `ad_list` is passed.<br><br>The ID of the ad that you want to copy. The ad should belong to the source ad group (`adgroup_id`).|
##|operation_status|string|The status of the new ad when created.<br><br>Enum values:<ul><li>`ENABLE`: The ad is enabled when created.</li><li>`DISABLE`: The ad is disabled when created.</li></ul><br>Default value: `ENABLE`.<br><br>If you want to update the status of the ad after creation, use [/smart_plus/ad/status/update/](https://business-api.tiktok.com/portal/docs/update-the-operation-statuses-of-upgraded-smart-ads/v1.3).|
##|ad_name|string|The name for the new ad.<br><br>Length limit: 512 characters. Emojis are not supported. Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character.<br><br>If not specified, this field will default to `"COPIED_&#123;&#123;name_of_the_source_ad&#125;&#125;_&#123;&#123;ID_of_the_source_ad&#125;&#125;"`. For instance, if the source ad is named `"FIRST_AD"` with the ad ID 1234567891234567, and this field is not specified, the name of the new ad will be `"COPIED_FIRST_AD_1234567891234567"`.<br><br>|
##|creative_list|object[]|A list of creatives. Size range: 1-50.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When this field is provided, it will replace all the existing creatives in the new ad.</li><li>Automatically added creatives will be filtered out. These creatives include:<ul><li>TikTok creator content, creator content from TikTok One, Content Suite, and authorized TikTok post</li><li>Your own content, content you've previously used from your linked TikTok accounts and Creative Library</li><li>Content generated for you, such as remixing images from your app store page</li></ul></li><li>The maximum number of creatives that you can specify in the new campaign is 1,000.</li></ul>|
###|creative_info {+Conditional}|object|Required when `creative_list` is specified.<br><br> Creative information.|
####|ad_format {+Conditional}|string|Required when `creative_info` is specified.<br><br> The ad format.<ul><li>`SINGLE_VIDEO`: Single Video.<ul><li>To use this format, specify any of the following:<ul><li>a video through `video_id` and a video cover through `web_uri`</li><li>a TikTok video post through `tiktok_item_id`.</li></ul></li></ul></li><li>`CAROUSEL_ADS`: Standard Carousel.<ul><li>To use this format, specify any of the following:<ul><li>carousel images through `web_uri` and a piece of music through `music_id`.</li><li>TikTok photo posts images through `tiktok_item_id`.</li></ul></li></ul> </li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can copy a Standard Carousel ad into a Single Video ad and vice versa.</p>|
####|video_info {+Conditional}|object|Required for Spark Ads Single Video ads through Spark Ads Push.<br><br>Video information.|
######|video_id {+Conditional}|string|Required when `video_info` is specified.<br><br>Video ID.<br><br>To upload a video and obtain the video ID, use [/file/video/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1737587322856449). <br>To search for videos within your ad account, use [/file/video/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740050472224769).|
######|file_name|string|Video name.|
####|image_info {+Conditional}|object[]|<ul><li>Required for the following types of ads: <ul><li>Spark Ads Single Video ads through Spark Ads Push. You need to specify a video cover. </li><li>Spark Ads Standard Carousel ads through Spark Ads Push. You need to specify one to 35 carousel images.</li><li>Catalog Carousel ads in [Upgraded Smart+ Automotive Ads](https://business-api.tiktok.com/portal/docs?id=1843324618421314). You need to specify one image as the end card image.</li></ul></li><li>Not supported for Catalog Carousel ads in Upgraded Smart+ Web Ads.</li></ul><br>Image information.|
######|web_uri {+Conditional}|string|Required when `image_info` is specified.<br><br>Image ID.<br><br>To upload an image and obtain the image ID, use [/file/image/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1739067433456642).<br> To search for images within your ad account, use [/file/image/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740052016789506).|
####|music_info {+Conditional}|object|Required for the following scenarios:<ul><li>When you create Standard Carousel Ads, including:<ul><li>Spark Ads Standard Carousel ads through Spark Ads Push</li><li>Spark Ads Standard Carousel ads through Spark Ads Pull</li></ul></li><li>When `objective_type` is `WEB_CONVERSIONS` or `LEAD_GENERATION` and `catalog_creative_toggle` is `true`. The system will automatically generate catalog carousel ads.</li></ul><br>Music information.|
######|music_id {+Conditional}|string|Required when `music_info` is specified.<br><br>The ID of the piece of music to use in the carousel ads.|
####|aigc_disclosure_type|string|Whether to turn on the AIGC (Artificial Intelligence Generated Content) self-disclosure toggle to indicate the ad contains AI-generated content. After the toggle is turned on, your ad will carry an "Advertiser labeled as Al-generated" label when viewed in full.<br><br>Enum values:<ul><li>`SELF_DISCLOSURE`: To turn on the toggle to declare that the ad contains AI-generated content.</li><li>`NOT_DECLARED`: To not declare that the ad contains AI-generated content. Default value: `NOT_DECLARED`.</li></ul>|
####|tiktok_item_id {+Conditional}|string|<ul><li>Required when you create Spark Ads through Spark Ads Pull, including:<ul><li>Spark Ads Single Video ads through Spark Ads Pull. You need to specify a TikTok video post.</li><li>Spark Ads Standard Carousel ads through Spark Ads Pull. You need to specify a TikTok photo post.</li></ul></li><li>Not supported when `catalog_creative_toggle` is `true`.</li></ul><br>The ID of the TikTok post to be used as an ad (Spark Ads).<br><br>Pass in the `item_id` you get from the response of the [/tt_video/info/](https://business-api.tiktok.com/portal/docs?id=1738376324021250) and [/identity/video/get/](https://business-api.tiktok.com/portal/docs?id=1740218475032577) endpoints.<br><br>When you pass in `tiktok_item_id`, you don't need to pass in the objects `image_info`, `video_info`, and `ad_text_list`. <br><br><p><span style="color:darkred"><b>Note</b></span>: By using Spark Ads, you confirm that you have the rights to use the music in the videos for commercial purposes.|
####|identity_type {+Conditional}|string|Required when you create Spark Ads.<br><br>Identity type for Spark Ads.<br><br>Enum values: `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT`.<br><br>For details about identities, see [Identities](https://business-api.tiktok.com/portal/docs?id=1738958351620097).|
####|identity_id {+Conditional}|string|Required when you create Spark Ads.<br><br>Identity ID for Spark Ads.|
####|identity_authorized_bc_id {+Conditional}|string|Required when `identity_type` is `BC_AUTH_TT`.<br><br>ID of the Business Center that a TikTok Account User in Business Center identity is associated with.|
##|ad_text_list {+Conditional}|object[]|Required when `tiktok_item_id` is not specified.<br><br>List of ad texts. <br>Ad texts are shown to your audience as part of your ad creatives, to deliver the message you intend to communicate to them.<br><br>Max size: 5.|
###|ad_text {+Conditional}|string|Required when `ad_text_list` is specified.<br><br>Ad text.|
##|call_to_action_list {+Conditional}|object[]|Call-to-action list.<br><br>Max size: 3.<br><br><p><span style="color:darkred"><b>Note</b></span>: This field is not supported in any of the following scenarios and you need to use `call_to_action_id` instead.<ul><li>Scenario 1:<ul><li>At the campaign level, `objective_type` is `LEAD_GENERATION`.</li><li>At the ad group level, `placement_type` is `PLACEMENT_TYPE_NORMAL` and `placements` includes `PLACEMENT_TIKTOK`, or `placement_type` is `PLACEMENT_TYPE_AUTOMATIC`.</li></ul></li><li>Scenario 2:<ul><li>At the campaign level, `objective_type` is `APP_PROMOTION` or `WEB_CONVERSIONS`.</li><li>At the ad group level, `placement_type` is `PLACEMENT_TYPE_NORMAL` and `placements` includes `PLACEMENT_TIKTOK`, or `placement_type` is `PLACEMENT_TYPE_AUTOMATIC`.</li><li>At the ad level, `identity_type` within the `creative_info` object to `TT_USER`, `BC_AUTH_TT`, or `AUTH_CODE`.</li></ul></li></ul>|
###|call_to_action {+Conditional}|string|Required when `call_to_action_list` is specified.<br><br>Call-to-action text.<br><br>For enum values, see [Enumeration - Call-to-action](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Call-to-action).|
##|landing_page_url_list|object[]|Landing page URL list.<br><br>Size range: 0-1.|
###|landing_page_url {+Conditional}|string|Required when `landing_page_url_list` is specified.<br><br>Landing page URL.|
##|ad_configuration|object|Additional configurations.|
###|utm_params|object[]|Valid when `objective_type` is `WEB_CONVERSIONS` at the campaign level.<br><br>A list of URL parameters. URL parameters are snippets of code that can be added to the end of the URLs to help you track clicks across different channels and understand how visitors interact with a website through third-party analytics platforms. They consist of key-value pairs that are specified through `key` and `value`.<br><br>Max size : 14.<br><br>If you set `landing_page_url` to a URL that already includes URL parameters, you can optionally pass `utm_params` at the same time to store the URL parameters used in the URL. In such cases, you need to ensure that `utm_params` exactly matches the used URL parameters. The URL parameters will not be automatically appended to the `landing_page_url` upon ad delivery.|
####|key|string|The supported UTM parameters are:<ul><li>`utm_source`: The app, site, etc., that brings traffic to your website. For example: TikTok.</li><li>`utm_medium`: The advertising or marketing medium. For example: cpm, cpc, banner, video.</li><li>`utm_content`: The creative content used for promotion. For example: ad name, CTA text, asset, color, etc.</li><li>`utm_campaign`: The individual campaign name, slogan, or promo code. For example: BlackFridayProm. Note that UTM parameters are case-sensitive.</li></ul><br>Length limit when you specify a custom parameter: 100 characters.|
####|value|string|The value of the URL parameter.<br><br>You can specify a custom value or the name of a macro.<br><br>The supported macros are:<ul><li>`CAMPAIGN_NAME`: This will be replaced by your campaign name.</li><li>`CAMPAIGN_ID`: This will be replaced by your campaign ID.</li><li>`AID_NAME`: This will be replaced by your ad group name.</li><li>`AID`: This will be replaced by your ad group ID.</li><li>`CID_NAME`: This will be replaced by your ad name.</li><li>`CID`: This will be replaced by your ad ID.</li><li>`PLACEMENT`: This will be replaced by your placement.</li></ul><br>Length limit when you specify a custom value: 600 characters.|
###|call_to_action_id|string|Valid in any of the following scenarios:<ul><li>Scenario 1:<ul><li>At the campaign level `objective_type` is `LEAD_GENERATION`.</li><li>At the ad group level, `placement_type` is `PLACEMENT_TYPE_NORMAL` and `placements` includes `PLACEMENT_TIKTOK`, or `placement_type` is `PLACEMENT_TYPE_AUTOMATIC`.</li></ul></li><li>Scenario 2:<ul><li>At the campaign level, `objective_type` is `APP_PROMOTION` or `WEB_CONVERSIONS`.</li><li>At the ad group level, `placement_type` is `PLACEMENT_TYPE_NORMAL` and `placements` includes `PLACEMENT_TIKTOK`, or `placement_type` is `PLACEMENT_TYPE_AUTOMATIC`.</li><li>At the ad level, `identity_type` within the `creative_info` object to `TT_USER`, `BC_AUTH_TT`, or `AUTH_CODE`.</li></ul></li></ul><br>The ID of the call-to-action (CTA) portfolio (also known as dynamic CTA) that you want to use in your ads. A CTA portfolio is a group of auto-optimized CTAs.<br><br>For details about auto-optimized CTAs, see [CTA recommendations > Dynamic CTAs](https://business-api.tiktok.com/portal/docs?id=1740307296329730#item-link-Dynamic%20CTAs%20).|
```

### **Example**

Depending on whether you want to use default settings or apply custom settings at different levels, ad group or ad settings, you can copy a campaign using any of the following code examples:


#### Default copy mode: all settings duplicated exactly from source campaign
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/copy/task/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": {{advertiser_id}},
    "request_id": "{{request_id}}",
    "campaign_id": "{{campaign_id}}"
}'
(/code)
```

#### Default copy mode: campaign-level overrides and shared schedule for new ad groups
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/copy/task/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "request_id": "{{request_id}}",
    "campaign_id": "{{campaign_id}}",
    "operation_status": "ENABLE",
    "campaign_name": "{{campaign_name}}",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "deep_copy_mode": "DEFAULT"
}'
(/code)
```

#### Custom copy mode: campaign-level overrides, shared schedule for new ad groups, and ad group-level overrides
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/copy/task/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "request_id": "{{request_id}}",
    "campaign_id": "{{campaign_id}}",
    "operation_status": "ENABLE",
    "campaign_name": "{{campaign_name}}",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "deep_copy_mode": "CUSTOM",
    "adgroup_list": [
        {
            "adgroup_id": "{{adgroup_id}}",
            "operation_status": "ENABLE",
            "adgroup_name": "{{adgroup_name}}",
            "budget": {{budget}},
            "targeting_spec": {
                "location_ids": [
                    "{{location_ids}}"
                ]
            },
            "ad_list": [
                {
                    "smart_plus_ad_id": "{{smart_plus_ad_id}}"
                }
            ]
        }
    ]
}'
(/code)
```

#### Custom copy mode: campaign-level overrides, shared schedule for new ad groups, ad group-level overrides, and ad-level overrides
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/copy/task/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "request_id": "{{request_id}}",
    "campaign_id": "{{campaign_id}}",
    "operation_status": "ENABLE",
    "campaign_name": "{{campaign_name}}",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "deep_copy_mode": "CUSTOM",
    "adgroup_list": [
        {
            "adgroup_id": "{{adgroup_id}}",
            "operation_status": "ENABLE",
            "adgroup_name": "{{adgroup_name}}",
            "budget": {{budget}},
            "targeting_spec": {
                "location_ids": [
                    "{{location_ids}}"
                ]
            },
            "ad_list": [
                {
                    "smart_plus_ad_id": "{{smart_plus_ad_id}}",
                    "operation_status": "ENABLE",
                    "ad_name": "{{ad_name}}",
                    "creative_info": {
                        "ad_format": "SINGLE_VIDEO",
                        "identity_type": "BC_AUTH_TT",
                        "identity_id": "{{identity_id}}",
                        "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                        "image_info": [
                            {
                                "web_uri": "{{web_uri}}"
                            }
                        ],
                        "video_info": {
                            "file_name": "{{file_name}}",
                            "video_id": "{{video_id}}"
                        }
                    },
                    "ad_text_list": [
                        {
                            "ad_text": "{{ad_text}}"
                        }
                    ],
                    "ad_configuration": {
                        "call_to_action_id": "{{call_to_action_id}}"
                    }
                }
            ]
        }
    ]
}'
(/code)
```

## Response

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs/return-codes-appendix/v1.3).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs/return-codes-appendix/v1.3).|
|request_id|string| The log ID of the request, which uniquely identifies a request.|
|data|object|Returned data.|
#|task_id|string|ID of the asynchronous campaign copy task.<br><br>To check the results of the task, use [/smart_plus/campaign/copy/task/check/](https://business-api.tiktok.com/portal/docs/get-the-results-of-an-asynchronous-copy-task-for-an-upgraded-smart-campaign/v1.3).|
#|adgroup_error_list|object[]|The errors encountered during the process of copying specific ad groups.|
##|adgroup_id|string|The ID of the ad group that fails to be copied.|
##|error_message|string|The error encountered during the process of copying the ad group (`adgroup_id`).|
```
### Example
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "task_id": "{{task_id}}"
    }
}
(/code)
```


