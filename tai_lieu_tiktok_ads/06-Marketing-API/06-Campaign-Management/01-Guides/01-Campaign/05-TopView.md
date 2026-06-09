---
title: "TopView"
doc_id: 1804258360285186
path: "Marketing API / Campaign Management / Guides / Campaign / TopView"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# TopView

TopView is a video ad format that greets users immediately as they open TikTok. It's a powerful, full-screen video with audio enabled, positioned to give your brand maximum visibility before any other TikTok content. The TopView journey starts with a 3-second video that takes over the screen, before seamlessly transitioning to the native in-feed video environment.
To learn more about the core capabilities of TopView and a demo of a TopView ad, see [About TopView](https://ads.tiktok.com/help/article/about-topview) and [TikTok Reservation TopView](https://ads.tiktok.com/help/article/tiktok-reservation-topview).


## Availability
The following table outlines whether a feature is supported for TopView ads in API.


```xtable
| Feature {35%}| Available in API {18%}| Related endpoints{47%} |
|---|---|---|
| Creating TopView campaigns, ad groups, or ads | ❌ | / |
| Updating TopView campaigns, ad groups, or ads | ❌ | / |
| Retrieving TopView campaigns, ad groups, or ads | ✅ | <ul><li>[/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986)</li><li> [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922)</li><li>[/ad/get/](https://business-api.tiktok.com/portal/docs?id=1735735588640770)</li></ul> To learn more about the specific data you can retrieve for a TopView ad group, see <a href="#item-link-Retrievable data for TopView ad groups">Retrievable data for TopView ad groups</a>.|
| Copying TopView campaigns, ad groups, or ads | ❌ | <ul><li>/campaign/copy/task/create/</li><li>/campaign/copy/task/check/</li></ul> |
| Running synchronous basic reports and audience reports | ✅ |<ul><li>[/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353)</li></ul>  |
| Previewing TopView ads | ❌ | <ul><li>/creative/ads_preview/create/</li></ul> |
```

### Retrievable data for TopView ad groups
When retrieving data for a TopView ad group using [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922), you can access the following fields:
-  `advertiser_id`
-  `campaign_id`
-  `campaign_name`
-  `adgroup_id`
-  `adgroup_name`
-  `placements`
-  `location_ids`
-  `gender`
-  `age_groups`
-  `languages`
-  `interest_category_ids` 
-  `predict_impression`    
-  `topview_reach_range`   
-  `pre_discount_cpm`      
-  `cpm`
-  `discount_type`
-  `discount_amount`       
-  `discount_percentage`   
-  `pre_discount_budget`   
-  `budget`
-  `schedule_type`
-  `schedule_start_time`
-  `schedule_end_time`
> <span style="color:DodgerBlue">**Note**</span><br>
> Newly created TopView ad groups automatically adjust their scheduled start and end times according to Daylight Saving Time (DST). For example, in 2024, when DST begins in the US (UTC-4) on March 10th, a TopView ad group scheduled for March 20th will run from 4:00 a.m. on March 20th until 4:00 a.m. on March 21st. 
> <br><br>During DST transitions, ad group delivery times adjust accordingly. On the day when Standard Time transitions to DST, TopView ad groups will run for 23 hours. Conversely, when DST reverts to Standard Time, TopView ad groups will run for 25 hours. For instance, on March 10th, 2024, when the US transitions from Standard Time (UTC-5) to DST (UTC-4), a TopView ad group scheduled for the date runs from 5:00 a.m. on March 10th until 4:00 a.m. on March 11th, totaling 23 hours. When the US reverts to Standard Time (UTC-5) on November 3rd, a TopView ad group scheduled for the date runs from 4:00 a.m. on November 3rd until 5:00 a.m. on November 4th, totaling 25 hours.
-  `billing_event`
-  `create_time`
-  `modify_time`
-  `operation_status`
-  `secondary_status`


