---
title: "Supported ad-level metrics for Upgraded Smart+ Creative Reports"
doc_id: 1843337909199938
path: "API Reference / Upgraded Smart+ / Reporting / Run an Upgraded Smart+ Creative Overview Report / Supported ad-level metrics for Upgraded Smart+ Creative Reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported ad-level metrics for Upgraded Smart+ Creative Reports

The following tables list the metrics you can query at the ad level in Upgraded Smart+ Creative Overview Reports and Upgraded Smart+ Creative Breakdown Reports. 


# Attribute metrics

Attribute metrics are basic attributes of creatives within Upgraded Smart+ Ads.

```xtable
|Field{25%}|Description{25%}|Detail{50%}|
|---|---|---|
|ad_material_id|N/A|An ad-specific material ID generated when a particular creative is used in an ad.<br><br>This ID differs from the creative ID you receive when uploading the creative to your ad account’s Creative Library.<br><br><p><span style="color:darkred"><b>Note</b></span>: This metric is only available for [Upgraded Smart+ Creative Overview Reports](https://business-api.tiktok.com/portal/docs/run-an-upgraded-smart-creative-overview-report/v1.3).|
```

# Core metrics
Core metrics provide fundamental insights into your advertising performance, covering essential aspects such as cost and impressions.
```xtable
|Field{25%}|Description{25%}|Detail{50%}|
|---|---|---|
|spend|Cost|Sum of your total ad spend.|
|cpc|CPC (destination)|Average cost of each click to a specified destination.|
|cpm|CPM|Average amount you spent per 1,000 impressions.|
|impressions|Impressions|Number of times your ads were shown.|
|gross_impressions|Gross Impressions (Includes Invalid Impressions)|Number of times your ads were shown, including invalid impressions.|
|clicks|Clicks (destination)|Number of clicks from your ads to a specified destination.|
|ctr|CTR (destination)|Percentage of impressions that resulted in a destination click out of all impressions.|
|conversion|Conversions|Number of times your ad resulted in the optimization event you selected.|
|cost_per_conversion|Cost per conversion|Average amount spent on a conversion.|
|conversion_rate_v2|Conversion rate (CVR)|Percentage of results you received out of all impressions on your ads.|
|real_time_conversion|Conversions by conversion time|Number of times your ad resulted in the optimization event you selected.|
|real_time_cost_per_conversion|Cost per conversion by conversion time|Average amount spent on a conversion.|
|real_time_conversion_rate_v2|Conversion rate (CVR) by conversion time|Percentage of conversions you received out of all impressions on your ads.|
|skan_result|Results (SKAN)|Number of times your ad resulted in an intended outcome based on your campaign objective and optimization goal.|
|skan_cost_per_result|Cost per result (SKAN)|Average cost per each result from your ads.|
|skan_result_rate|Result rate (SKAN)|Percentage of results that happened out of all impressions on your ads.|
|skan_conversion|Conversions (SKAN)|Number of times your ad resulted in an intended outcome based on your optimization event.|
|skan_cost_per_conversion|Cost per conversion (SKAN)|Average amount you spent on a conversion.|
|skan_conversion_rate_v2|Conversion rate (SKAN)|Percentage of conversions you received out of all impressions on your ads.|
```



# Interaction metrics
Interaction metrics measure how people interact with your ads.


## Videos metrics
Videos metrics measure performance related to video content views on TikTok.

````xtable
| Field {34%}| Description {22%}| Detail {44%}|
|---|---|---|
| Video views | | |
#| video_play_actions | Video views | Number of times your video started to play. For each video impression, plays are counted separately and replays are excluded. |
#| video_watched_2s | 2-second video views | Number of times your video was played for at least 2 seconds. For each video impression, plays are counted separately and replays are excluded. |
#| video_watched_6s | 6-second video views | Number of times your video was played for at least 6 seconds. For each video impression, plays are counted separately and replays are excluded. |
#| video_views_p25 | Video views at 25% | Number of times your video was played at least 25% of its length. For each impression, views are counted separately and replays are excluded. |
#| video_views_p50 | Video views at 50% | Number of times your video was played at least 50% of its length. For each impression, views are counted separately and replays are excluded. |
#| video_views_p75 | Video views at 75% | Number of times your video was played at least 75% of its length. For each impression, views are counted separately and replays are excluded. |
#| video_views_p100 | Video views at 100% | Number of times your video was played 100% of its length. For each impression, views are counted separately and replays are excluded. |
#| average_video_play | Average play time per video view | Average time your video was played per single video view, including any time spent replaying the video. |
| Focus views | | |
#| engaged_view | 6-second focused views | Number of times your video was played for at least 6 seconds, played in full if it is less than 6 seconds, or received at least 1 engagement within the first 6 seconds. |
#| paid_engaged_view | 6-second focused views (paid views) | Number of times your video was played for at least 6 seconds, or played in full if it is less than 6 seconds. For each video impression, plays are counted separately and replays are excluded.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you want to use this metric, you can specify any of the following dimension groupings in the `dimensions` field of the request:<ul><li>one ID dimension (`advertiser_id`, `campaign_id`, `adgroup_id`, or `ad_id`)</li><li>one ID dimension + `stat_time_day`</li></ul> |
#| paid_engagement_engaged_view | 6-second focused views (paid interactions) | The number of times your video received at least one positive interaction (likes, shares, follows, or clicks) without being viewed for 6 seconds. This metric is only available for advertisers using focused view products. For each video impression, paid interactions are counted separately and replays are excluded.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you want to use this metric, you can specify any of the following dimension groupings in the `dimensions` field of the request:<ul><li>one ID dimension (`advertiser_id`, `campaign_id`, `adgroup_id`, or `ad_id`)</li><li>one ID dimension + `stat_time_day`</li></ul> |
#| engaged_view_15s | 15-second focused views | Number of times your video was played for at least 15 seconds, played in full if it is less than 15 seconds, or received at least 1 engagement within the first 15 seconds. |
#| paid_engaged_view_15s | 15-second focused views (paid views) | The number of times your video was viewed for at least 15 seconds, or viewed in full if it is less than 15 seconds long. For each video impression, plays are counted separately and replays are excluded. This metric is only available for advertisers using focused view products.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you want to use this metric, you can specify any of the following dimension groupings in the `dimensions` field of the request:<ul><li>one ID dimension (`advertiser_id`, `campaign_id`, `adgroup_id`, or `ad_id`)</li><li>one ID dimension + `stat_time_day`</li></ul> |
#| paid_engagement_engaged_view_15s | 15-second focused views (paid interactions) | The number of times your video received at least one positive interaction (likes, shares, follows, or clicks) without being viewed for 15 seconds. This metric is only available for advertisers using focused view products. For each video impression, paid interactions are counted separately and replays are excluded.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you want to use this metric, you can specify any of the following dimension groupings in the `dimensions` field of the request:<ul><li>one ID dimension (`advertiser_id`, `campaign_id`, `adgroup_id`, or `ad_id`)</li><li>one ID dimension + `stat_time_day`</li></ul> |
````

## Clicks metrics
Clicks metrics measure the number of clicks generated by ads, including interactions with various components like buttons and anchors.
```xtable
|Field{25%}|Description{25%}|Detail{50%}|
|---|---|---|
|engagements|Clicks (all)|Number of clicks from your ads. This includes clicks that lead to destination as well as those for social and interaction purposes.|
|engagement_rate|CTR (all)|Percentage of impressions that resulted in a click out of all impressions.|
|clicks_on_music_disc|Sound clicks|Number of clicks on the sound associated with your ad during the ad impression.|
|duet_clicks|Duet button clicks|Number of clicks on the "Duet" button.|
|stitch_clicks|Stitch button clicks|Number of clicks on the "Stitch" button.|
|sound_usage_clicks|Use sound button clicks|Number of clicks on the "Use sound" button.|
|anchor_clicks|Anchor clicks|Number of clicks on ad anchors.|
|anchor_click_rate|Anchor click rate|Percentage of anchor clicks out of all impressions on your ads.|
|clicks_on_hashtag_challenge|Hashtag clicks|Number of clicks on the hashtag challenge associated with your ad during the ad impressions.|
```

## Social metrics 
Social metrics measure social interactions with ads, such as follows, likes, comments, and shares.

```xtable
| Field {25%}| Type{10%}| Description{25%}| Detail{40%}|
|---|---|---|---|
| follows | string | Paid follows | Number of follows to the associated profile during the ad impression. |
| likes | string | Paid likes | Number of times your ad was liked during the ad impression. |
| comments | string | Paid comments | Number of comments sent on your ad during the ad impression. |
| shares | string | Paid shares | Number of shares of your ad during the ad impression. |
| profile_visits | string | Paid profile visits | Number of visits to the associated profile during the ad impression. |
| profile_visits_rate | string | Paid profile visit rate | Percentage of profile visits from ads out of all impressions on your ads. |
| tt_playlist_visit |string|Playlist page visit|The number of playlist page visits the paid ad drove during the campaign.|
| tt_playlist_visit_rate |string|Playlist page visit rate|The rate of playlist page visits per impression the paid ad drove during the campaign.|
| cost_per_consideration | string | Cost per consideration | Average cost per consideration audience from your ad. <br><br><p><span style="color:darkred"><b>Note</b></span>:  <ul><li>When using Upgraded Smart+ Creative Reports, you can only use this metric in [/smart_plus/material_report/overview/](https://business-api.tiktok.com/portal/docs/run-an-upgraded-smart-creative-overview-report/v1.3) for Upgraded Smart+ Creative Overview Reports.</li><li> If you want to use this metric, you can specify any of the following dimension groupings in the `dimensions` field of the request: <ul><li>one ID dimension ( `advertiser_id`, `campaign_id`, `adgroup_id`, `ad_id` , or `ad_id_v2` )</li><li>one ID dimension + `stat_time_day`</li></ul></li></ul></p> |
| new_consideration_rate | string | New consideration rate | Percentage of consideration audience acquired from all ad impressions. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When using Upgraded Smart+ Creative Reports, you can only use this metric in [/smart_plus/material_report/overview/](https://business-api.tiktok.com/portal/docs/run-an-upgraded-smart-creative-overview-report/v1.3) for Upgraded Smart+ Creative Overview Reports.</li><li> If you want to use this metric, you can specify any of the following dimension groupings in the `dimensions` field of the request: <ul><li>one ID dimension ( `advertiser_id`, `campaign_id`, `adgroup_id`, `ad_id` , or `ad_id_v2` )</li><li>one ID dimension + `stat_time_day`</li></ul></li></ul></p> |
| new_consideration_size | string | New consideration size | Number of acquired consideration audience your ad resulted in. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When using Upgraded Smart+ Creative Reports, you can only use this metric in [/smart_plus/material_report/overview/](https://business-api.tiktok.com/portal/docs/run-an-upgraded-smart-creative-overview-report/v1.3) for Upgraded Smart+ Creative Overview Reports.</li><li> If you want to use this metric, you can specify any of the following dimension groupings in the `dimensions` field of the request: <ul><li>one ID dimension ( `advertiser_id`, `campaign_id`, `adgroup_id`, `ad_id` , or `ad_id_v2` )</li><li>one ID dimension + `stat_time_day`</li></ul></li></ul></p> |
```

## LIVE metrics
LIVE metrics measure viewer engagement during live sessions, capturing metrics such as views and clicks.
```xtable
|Field{25%}|Description{25%}|Detail{50%}|
|---|---|---|
|live_views|LIVE views|Number of times your LIVE was viewed from an ad. Revisits are counted.|
|live_effective_views|10-second LIVE views|Number of times your LIVE was viewed for at least 10 seconds from an ad. Revisits are counted.|
|live_product_clicks|LIVE Product Clicks|Number of time viewers clicked a product and viewed its details page during your LIVE.|
```

## Playable metrics
Playable metrics are data about how people interact with your playale ads.
```xtable
|Field{25%}|Description{25%}|Detail{50%}|
|---|---|---|
|playable_page_view_count|Playable page views|Number of times your playable page was shown.|
|playable_first_scene_view_count|Playable views of first scene|Number of views of your playable page that reach at least the first scene of your ad.|
|playable_second_scene_view_count|Playable views of second scene|Number of views of your playable page that reach at least the second scene of your ad.|
|playable_third_scene_view_count|Playable views of third scene|Number of views of your playable page that reach the third scene of your ad.|
|playable_download_button_click_count|Playable download button clicks|Number of clicks on the download button on your playable page.|
```

## Interactive add-ons metrics
Interactive add-ons metrics measure the effectiveness of Interactive Add-ons used in ads.
```xtable
|Field{25%}|Description{25%}|Detail{50%}|
|---|---|---|
|interactive_add_on_impressions|Interactive Add-on impressions|Number of times your Interactive Add-on was shown.|
|interactive_add_on_destination_clicks|Interactive Add-on destination clicks|Number of clicks from an Interactive Add-on to a specified destination.|
|interactive_add_on_activity_clicks|Interactive Add-on activity clicks|Number of interactive activity clicks in an Interactive Add-on.|
|interactive_add_on_option_a_clicks|Interactive Add-on option A clicks|Number of clicks on option A in an Interactive Add-on.|
|interactive_add_on_option_b_clicks|Interactive Add-on option B clicks|Number of clicks on option B in an Interactive Add-on.|
|countdown_sticker_recall_clicks|Countdown sticker recall clicks|Number of clicks on a reminder from the countdown sticker to a specified destination.|
```

## Instant experience metrics
Instant experience metrics measure performance related to viewing or playing Instant Experiences.
```xtable
|Field {20%}|Description {20%} |Detail {60%}|
|---|---|---|
|ix_page_duration_avg|Instant experience average view time|Average time your Instant Experience was shown.|
|ix_page_viewrate_avg|Instant experience average view percentage|Average percentage of content on your Instant Experience that was viewed.|
|ix_video_views|Instant experience video views|Number of times your Instant Experience video was played. Replays are excluded.|
|ix_video_views_p25|Instant experience video views at 25%|Number of times your Instant Experience video was played at least 25% of its length. Replays are excluded.|
|ix_video_views_p50|Instant experience video views at 50%|Number of times your Instant Experience video was played at least 50% of its length. Replays are excluded.|
|ix_video_views_p75|Instant experience video views at 75%|Number of times your Instant Experience video was played at least 75% of its length. Replays are excluded.|
|ix_video_views_p100|Instant experience video views at 100%|Number of times your Instant Experience video was played 100% of its length. Replays are excluded.|
|ix_average_video_play|Average Instant experience video view time|Average time your instant experience video was played in a single video view, including any time spent replaying the video.|
```

## Product metrics
Product metrics are data about the performance of your products.
```xtable
|Field {25%}|Description {25%}|Detail {50%}|
|---|---|---|
|product_impressions|Product impressions|Number of times your products were shown.|
|product_clicks|Product clicks (destination)|Number of clicks from your products to a specified destination.|
|product_cta|Product click-through rate (destination)|Percentage of product impressions that resulted in a destination click out of all product impressions.|
```

# Conversions metrics
Conversions metrics measure the conversion events in different channels, such as App, Website, and Offline.


## App metrics
App metrics measure actions taken in relation to mobile apps, such as installations and registrations attributed to ads.

````xtable
| Field {34%}| Description {22%}| Detail {44%}|
|---|---|---|
| App install by conversion time | | When a user installs your app. |
#| real_time_app_install | App installs by conversion time | The number of times your mobile app was installed that were recorded as app events and attributed to your ads. (The total count is based on when the conversion actually happened.) |
#| real_time_app_install_cost | Cost per app install by conversion time | The average cost for each app install event. (The total count is based on when the conversion actually happened.) |
| App install | | |
#| app_install | App install | The number of times your mobile app was installed that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_app_install | Cost per app install | The average cost for each app install event. (The total count is calculated based on the time each ad impression occurred.) |
| Registration | | |
#| registration | Unique registrations (app) | The number of unique app registration actions attributed to your ads. |
#| cost_per_registration | Unique cost per registration (app) | The average cost per unique app registration action attributed to your ads. |
#| registration_rate | Unique registration rate (app) | The percentage of app registration actions from all app installs attributed to your ads. |
#| total_registration | Registrations (app) | The number of app registration actions attributed to your ads. |
#| cost_per_total_registration | Cost per registration (app) | The average cost per app registration action attributed to your ads. |
| Purchase | | <p><span style="color:darkred"><b>Note</b></span>: If you want to use Purchase metrics, you can specify any of the following dimension groupings in the dimensions field of the request:<ul><li>one ID dimension (`advertiser_id`, `campaign_id`, `adgroup_id`, or `ad_id`)</li><li>one ID dimension + `stat_time_day`</li></ul> |
#| purchase | Unique purchases (app) | The number of unique app purchase actions attributed to your ads. |
#| cost_per_purchase | Unique cost per purchase (app) | The average cost per unique app purchase action attributed to your ads. |
#| purchase_rate | Unique purchase rate (app) | The percentage of app purchase actions from all app installs attributed to your ads. |
#| total_purchase | Purchases (app) | The number of app purchase actions attributed to your ads. |
#| cost_per_total_purchase | Cost per purchase (app) | The average cost per app purchase action attributed to your ads. |
#| value_per_total_purchase | Value per purchase (app) | The average value per app purchase action attributed to your ads. |
#| total_purchase_value | Purchase value (app) | The total value of app purchase actions attributed to your ads. |
#| total_active_pay_roas | Purchase ROAS (app) | The return on ad spend (ROAS) from app purchase actions. |
#| total_purchase_roas_day0 | Day 0 purchase ROAS | Total return on ad spend (ROAS) from purchase actions within 24 hours of app install. |
#| total_purchase_roas_day2 | Day 2 purchase ROAS | Total return on ad spend (ROAS) from purchase actions within 72 hours of app install. |
#| total_purchase_roas_day6 | Day 6 purchase ROAS | Total return on ad spend (ROAS) from purchase actions within 168 hours of app install. |
#| total_purchase_value_day0 | Day 0 purchase value | Total value of all purchase actions within 24 hours of app install. |
#| total_purchase_value_day2 | Day 2 purchase value | Total value of all purchase actions within 72 hours of app install. |
#| total_purchase_value_day6 | Day 6 purchase value | Total value of all purchase actions within 168 hours of app install. |
| Add to Cart | | |
#| app_event_add_to_cart | Unique adds to cart (app) | The number of unique app add to cart actions attributed to your ads. |
#| cost_per_app_event_add_to_cart | Unique cost per add to cart (app) | The average cost per unique app add to cart action attributed to your ads. |
#| app_event_add_to_cart_rate | Unique add to cart rate (app) | The percentage of app add to cart actions from all app installs attributed to your ads. |
#| total_app_event_add_to_cart | Adds to cart (app) | The number of app add to cart actions attributed to your ads. |
#| cost_per_total_app_event_add_to_cart | Cost per add to cart (app) | The average cost per app add to cart action attributed to your ads. |
#| value_per_total_app_event_add_to_cart | Value per add to cart (app) | The average value per app add to cart action attributed to your ads. |
#| total_app_event_add_to_cart_value | Add to cart value (app) | The total value of app add to cart actions attributed to your ads. |
| Checkout | | |
#| checkout | Unique checkouts (app) | The number of unique app checkout actions attributed to your ads. |
#| cost_per_checkout | Unique cost per checkout (app) | The average cost per unique app checkout action attributed to your ads. |
#| checkout_rate | Unique checkout rate (app) | The percentage of app checkout actions from all app installs attributed to your ads. |
#| total_checkout | Checkouts (app) | The number of app checkout actions attributed to your ads. |
#| cost_per_total_checkout | Cost per checkout (app) | The average cost per app checkout action attributed to your ads. |
#| value_per_checkout | Value per checkout (app) | The average value per app checkout action attributed to your ads. |
#| total_checkout_value | Checkout value (app) | The total value of app checkout actions attributed to your ads. |
| View Content | | |
#| view_content | Unique content views (app) | The number of unique app view content actions attributed to your ads. |
#| cost_per_view_content | Unique cost per content view (app) | The average cost per unique app view content action attributed to your ads. |
#| view_content_rate | Unique content view rate (app) | The percentage of app view content actions from all app installs attributed to your ads. |
#| total_view_content | Content views (app) | The number of app view content actions attributed to your ads. |
#| cost_per_total_view_content | Cost per content view (app) | The average cost per app view content action attributed to your ads. |
#| value_per_total_view_content | Value per content view (app) | The average value per app view content action attributed to your ads. |
#| total_view_content_value | Content view value (app) | The total value of app view content actions attributed to your ads. |
| Day 1 retention | | |
#| next_day_open | Unique Day 1 retentions | Number of unique Day 1 retentions attributed to your ads. |
#| cost_per_next_day_open | Cost per unique Day 1 retention | Average cost per unique Day 1 retention. |
#| next_day_open_rate | Day 1 retention rate (%) | Percentage of unique Day 1 retentions out of all app install events. |
#| total_next_day_open | Day 1 retentions | Number of Day 1 retentions attributed to your ads. |
#| cost_per_total_next_day_open | Cost per Day 1 retention | Average cost per Day 1 retention. |
| Day 7 retention | | When a user launches your app the 7th day after installing it.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you want to use Day 7 retention metrics, you can specify any of the following dimension groupings in the `dimensions` field of the request:<ul><li>one ID dimension (`advertiser_id`, `campaign_id`, `adgroup_id`, or `ad_id`)</li><li>one ID dimension + `stat_time_day`</li><li>one ID dimension + `stat_time_day` + `ad_type`</li><li>one ID dimension + `ad_type`</li><li>`stat_time_day` + `ad_type`</li><li>`ad_type` only</li></ul> |
#| day7_retention | Unique Day 7 retentions | Number of unique Day 7 retentions from your mobile app counted as app events and attributed to your ads. |
#| cost_per_day7_retention | Cost per unique Day 7 retention | Average cost per unique Day 7 retention attributed to your ad. |
#| day7_retention_rate | Day 7 retention rate (%) | Percentage of Day 7 retentions out of all app install events. |
#| total_day7_retention | Day 7 retentions | Total number of Day 7 retentions from your mobile app counted as app events and attributed to your ads. |
#| cost_per_total_day7_retention | Cost per Day 7 retention | Average cost per Day 7 retention attributed to your ad. |
#| value_per_total_day7_retention | Value per Day 7 retention | Average value per Day 7 retention attributed to your ad. |
#| total_day7_retention_value | Day 7 retention value | Total value returned from all Day 7 retentions. |
| Add payment info | | |
#| add_payment_info | Unique payment info adds (app) | The number of unique app add payment info actions attributed to your ads. |
#| cost_per_add_payment_info | Unique cost per payment info add (app) | The average cost per unique app add payment info action attributed to your ads. |
#| add_payment_info_rate | Unique payment info add rate (app) | The percentage of app add payment info actions from all app installs attributed to your ads. |
#| total_add_payment_info | Payment info adds (app) | The number of app add payment info actions attributed to your ads. |
#| cost_total_add_payment_info | Cost per payment info add (app) | The average cost per app add payment info action attributed to your ads. |
| Add to wishlist | | |
#| add_to_wishlist | Unique adds to wishlist (app) | The number of unique app add to wishlist actions attributed to your ads. |
#| cost_per_add_to_wishlist | Unique cost per add to wishlist (app) | The average cost per unique app add to wishlist action attributed to your ads. |
#| add_to_wishlist_rate | Unique add to wishlist rate (app) | The percentage of app add to wishlist actions from all app installs attributed to your ads. |
#| total_add_to_wishlist | Adds to wishlist (app) | The number of app add to wishlist actions attributed to your ads. |
#| cost_per_total_add_to_wishlist | Cost per add to wishlist (app) | The average cost per app add to wishlist action attributed to your ads. |
#| value_per_total_add_to_wishlist | Value per add to wishlist (app) | The average value per app add to wishlist action attributed to your ads. |
#| total_add_to_wishlist_value | Add to wishlist value (app) | The total value of app add to wishlist actions attributed to your ads. |
| Launch app | | |
#| launch_app | Unique app launches (app) | The number of unique launch app actions attributed to your ads. |
#| cost_per_launch_app | Unique cost per app launch (app) | The average cost per unique launch app action attributed to your ads. |
#| launch_app_rate | Unique app launch rate (app) | The percentage of launch app actions from all app installs attributed to your ads. |
#| total_launch_app | App launches (app) | The number of launch app actions attributed to your ads. |
#| cost_per_total_launch_app | Cost per app launch (app) | The average cost per launch app action attributed to your ads. |
| Complete tutorial | | |
#| complete_tutorial | Unique tutorials completed (app) | The number of unique app complete tutorial actions attributed to your ads. |
#| cost_per_complete_tutorial | Unique cost per complete tutorial (app) | The average cost per unique app complete tutorial action attributed to your ads. |
#| complete_tutorial_rate | Unique tutorial completion rate (app) | The percentage of app complete tutorial actions from all app installs attributed to your ads. |
#| total_complete_tutorial | Tutorials completed (app) | The number of app complete tutorial actions attributed to your ads. |
#| cost_per_total_complete_tutorial | Cost per complete tutorial (app) | The average cost per app complete tutorial action attributed to your ads. |
#| value_per_total_complete_tutorial | Value per complete tutorial (app) | The average value per app complete tutorial action attributed to your ads. |
#| total_complete_tutorial_value | Tutorial completion value (app) | The total value of app complete tutorial actions attributed to your ads. |
| Create group | | |
#| create_group | Unique groups created (app) | The number of unique app create group actions attributed to your ads. |
#| cost_per_create_group | Unique cost per group created (app) | The average cost per unique app create group action attributed to your ads. |
#| create_group_rate | Unique group creation rate (app) | The percentage of app create group actions from all app installs attributed to your ads. |
#| total_create_group | Groups created (app) | The number of app create group actions attributed to your ads. |
#| cost_per_total_create_group | Cost per group created (app) | The average cost per app create group action attributed to your ads. |
#| value_per_total_create_group | Value per group created (app) | The average value per app create group action attributed to your ads. |
#| total_create_group_value | Group creation value (app) | The total value of app create group actions attributed to your ads. |
| Join group | | |
#| join_group | Unique group joins (app) | The number of unique app join group actions attributed to your ads. |
#| cost_per_join_group | Unique cost per group join (app) | The average cost per unique app join group action attributed to your ads. |
#| join_group_rate | Unique group joining rate (app) | The percentage of app join group actions from all app installs attributed to your ads. |
#| total_join_group | Group joins (app) | The number of app join group actions attributed to your ads. |
#| cost_per_total_join_group | Cost per group join (app) | The average cost per app join group action attributed to your ads. |
#| value_per_total_join_group | Value per group join (app) | The average value per app join group action attributed to your ads. |
#| total_join_group_value | Group joining value (app) | The total value of app join group actions attributed to your ads. |
| Create role | | |
#| create_gamerole | Unique roles created (app) | The number of unique app create role actions attributed to your ads. |
#| cost_per_create_gamerole | Unique cost per role created (app) | The average cost per unique app create role action attributed to your ads. |
#| create_gamerole_rate | Unique role creation rate (app) | The percentage of app create role actions from all app installs attributed to your ads. |
#| total_create_gamerole | Roles created (app) | The number of app create role actions attributed to your ads. |
#| cost_per_total_create_gamerole | Cost per role created (app) | The average cost per app create role action attributed to your ads. |
#| value_per_total_create_gamerole | Value per role created (app) | The average value per app create role action attributed to your ads. |
#| total_create_gamerole_value | Role creation value (app) | The total value of app create role actions attributed to your ads. |
| Spend credit | | |
#| spend_credits | Unique credit spends (app) | The number of unique app spend credit actions attributed to your ads. |
#| cost_per_spend_credits | Unique cost per credit spend (app) | The average cost per unique app spend credit action attributed to your ads. |
#| spend_credits_rate | Unique credit spend rate (app) | The percentage of app spend credit actions from all app installs attributed to your ads. |
#| total_spend_credits | Credit spends (app) | The number of app spend credit actions attributed to your ads. |
#| cost_per_total_spend_credits | Cost per credit spend (app) | The average cost per app spend credit action attributed to your ads. |
#| value_per_total_spend_credits | Value per credit spend (app) | The average value per app spend credit action attributed to your ads. |
#| total_spend_credits_value | Credit spend value (app) | The total value of app spend credit actions attributed to your ads. |
| Achieve level | | |
#| achieve_level | Unique levels achieved (app) | The number of unique app achieve level actions attributed to your ads. |
#| cost_per_achieve_level | Unique cost per unique level achieved (app) | The average cost per unique app achieve level action attributed to your ads. |
#| achieve_level_rate | Unique level achievement rate (app) | The percentage of app achieve level actions from all app installs attributed to your ads. |
#| total_achieve_level | Levels achieved (app) | The number of app achieve level actions attributed to your ads. |
#| cost_per_total_achieve_level | Cost per level achieved (app) | The average cost per app achieve level action attributed to your ads. |
#| value_per_total_achieve_level | Value per level achieved (app) | The average value per app achieve level action attributed to your ads. |
#| total_achieve_level_value | Level achievement value (app) | The total value of app achieve level actions attributed to your ads. |
| Unlock achievement | | |
#| unlock_achievement | Unique achievements unlocked (app) | The number of unique app unlock achievement actions attributed to your ads. |
#| cost_per_unlock_achievement | Unique cost per achievement unlocked (app) | The average cost per unique app unlock achievement action attributed to your ads. |
#| unlock_achievement_rate | Unique unlocked achievement rate (app) | The percentage of app unlock achievement actions from all app installs attributed to your ads. |
#| total_unlock_achievement | Achievements unlocked (app) | The number of app unlock achievement actions attributed to your ads. |
#| cost_per_total_unlock_achievement | Cost per achievement unlocked (app) | The average cost per app unlock achievement action attributed to your ads. |
#| value_per_total_unlock_achievement | Value per achievement unlocked (app) | The average value per app unlock achievement action attributed to your ads. |
#| total_unlock_achievement_value | Unlocked achievement value (app) | The total value of app unlock achievement actions attributed to your ads. |
| Generate lead | | |
#| sales_lead | Unique leads generated (app) | The number of unique app generate lead actions attributed to your ads. |
#| cost_per_sales_lead | Unique cost per lead generated (app) | The average cost per unique app generate lead action attributed to your ads. |
#| sales_lead_rate | Unique lead generation rate (app) | The percentage of app generate lead actions from all app installs attributed to your ads. |
#| total_sales_lead | Leads generated (app) | The number of app generate lead actions attributed to your ads. |
#| cost_per_total_sales_lead | Cost per lead generated (app) | The average cost per app generate lead action attributed to your ads. |
#| value_per_total_sales_lead | Value per lead generated (app) | The average value per app generate lead action attributed to your ads. |
#| total_sales_lead_value | Lead generation value (app) | The total value of app generate lead actions attributed to your ads. |
| In-app ad click | | |
#| in_app_ad_click | Unique in-app ad clicks (app) | The number of unique in-app ad clicks attributed to your ads. |
#| cost_per_in_app_ad_click | Unique cost per in-app ad click (app) | The average cost per unique in-app ad click attributed to your ads. |
#| in_app_ad_click_rate | In-app ad click rate (app) | The percentage of in-app ad clicks from all app installs attributed to your ads. |
#| total_in_app_ad_click | In-app ad clicks (app) | The number of in-app ad clicks attributed to your ads. |
#| cost_per_total_in_app_ad_click | Cost per in-app ad click (app) | The average cost per in-app ad click attributed to your ads. |
#| value_per_total_in_app_ad_click | Value per in-app ad click (app) | The average value per in-app ad click attributed to your ads. |
#| total_in_app_ad_click_value | In-app ad click value (app) | The total value of in-app ad clicks attributed to your ads. |
| In-app ad impression | | |
#| in_app_ad_impr | Unique in-app ad impressions (app) | The number of unique in-app ad impressions attributed to your ads. |
#| cost_per_in_app_ad_impr | Unique cost per in-app ad impression (app) | The average cost per unique in-app ad impression attributed to your ads. |
#| in_app_ad_impr_rate | In-app ad impression rate (app) | The percentage of in-app ad impressions from all app installs attributed to your ads. |
#| total_in_app_ad_impr | In-app ad impressions (app) | The number of in-app ad impressions attributed to your ads. |
#| cost_per_total_in_app_ad_impr | Cost per in-app ad impression (app) | The average cost per in-app ad impression attributed to your ads. |
#| value_per_total_in_app_ad_impr | Value per in-app ad impression (app) | The average value per in-app ad impression attributed to your ads. |
#| total_in_app_ad_impr_value | In-app ad impression value (app) | The total value of in-app ad impressions attributed to your ads. |
| Loan apply | | |
#| loan_apply | Unique loan applications (app) | The number of unique app loan application actions attributed to your ads. |
#| cost_per_loan_apply | Unique cost per loan application (app) | The average cost per unique app loan application action attributed to your ads. |
#| loan_apply_rate | Unique loan application rate (app) | The percentage of app loan application actions from all app installs attributed to your ads. |
#| total_loan_apply | Loan applications (app) | The number of app loan application actions attributed to your ads. |
#| cost_per_total_loan_apply | Cost per loan application (app) | The average cost per app loan application action attributed to your ads. |
| Loan approval | | |
#| loan_credit | Unique loan approvals (app) | The number of unique app loan approval actions attributed to your ads. |
#| cost_per_loan_credit | Unique cost per loan approval (app) | The average cost per unique app loan approval action attributed to your ads. |
#| loan_credit_rate | Unique loan approval rate (app) | The percentage of app loan approval actions from all app installs attributed to your ads. |
#| total_loan_credit | Loan approvals (app) | The number of app loan approval actions attributed to your ads. |
#| cost_per_total_loan_credit | Cost per loan approval (app) | The average cost per app loan approval action attributed to your ads. |
| Loan disbursal | | |
#| loan_disbursement | Unique loan disbursals (app) | The number of unique app loan disbursal actions attributed to your ads. |
#| cost_per_loan_disbursement | Unique cost per loan disbursal (app) | The average cost per unique app loan disbursal action attributed to your ads. |
#| loan_disbursement_rate | Unique loan disbursal rate (app) | The percentage of app loan disbursal actions from all app installs attributed to your ads. |
#| total_loan_disbursement | Loan disbursals (app) | The number of app loan disbursal actions attributed to your ads. |
#| cost_per_total_loan_disbursement | Cost per loan disbursal (app) | The average cost per app loan disbursal action attributed to your ads. |
| Login | | |
#| login | Unique logins (app) | The number of unique app login actions attributed to your ads. |
#| cost_per_login | Unique cost per login (app) | The average cost per unique app login action attributed to your ads. |
#| login_rate | Unique login rate (app) | The percentage of app login actions from all app installs attributed to your ads. |
#| total_login | Logins (app) | The number of app login actions attributed to your ads. |
#| cost_per_total_login | Cost per login (app) | The average cost per app login action attributed to your ads. |
| Rate | | |
#| ratings | Unique ratings (app) | The number of unique app rate actions attributed to your ads. |
#| cost_per_ratings | Unique cost per rating (app) | The average cost per unique app rate action attributed to your ads. |
#| ratings_rate | Unique rate rate (%) | The percentage of unique rate events out of all app install events. |
#| total_ratings | Ratings (app) | The number of app rate actions attributed to your ads. |
#| cost_per_total_ratings | Cost per rating (app) | The average cost per app rate action attributed to your ads. |
#| value_per_total_ratings | Value per rating (app) | The average value per app rate action attributed to your ads. |
#| total_ratings_value | Rating value (app) | The total value of app rate actions attributed to your ads. |
| Search | | |
#| search | Unique searches (app) | The number of unique app search actions attributed to your ads. |
#| cost_per_search | Unique cost per search (app) | The average cost per unique app search action attributed to your ads. |
#| search_rate | Unique search rate (app) | The percentage of app search actions from all app installs attributed to your ads. |
#| total_search | Searches (app) | The number of app search actions attributed to your ads. |
#| cost_per_total_search | Cost per search (app) | The average cost per app search action attributed to your ads. |
| Start trial | | |
#| start_trial | Unique trial started (app) | The number of unique app start trial actions attributed to your ads. |
#| cost_per_start_trial | Unique cost per trial started (app) | The average cost per unique app start trial action attributed to your ads. |
#| start_trial_rate | Unique trial start rate (app) | The percentage of app start trial actions from all app installs attributed to your ads. |
#| total_start_trial | Trials started (app) | The number of app start trial actions attributed to your ads. |
#| cost_per_total_start_trial | Cost per trial started (app) | The average cost per app start trial action attributed to your ads. |
| Subscribe | | |
#| subscribe | Unique subscriptions (app) | The number of unique app subscribe actions attributed to your ads. |
#| cost_per_subscribe | Unique cost per subscription (app) | The average cost per unique app subscribe action attributed to your ads. |
#| subscribe_rate | Unique subscription rate (app) | The percentage of app subscribe actions from all app installs attributed to your ads. |
#| total_subscribe | Subscriptions (app) | The number of app subscribe actions attributed to your ads. |
#| cost_per_total_subscribe | Cost per subscription (app) | The average cost per app subscribe action attributed to your ads. |
#| value_per_total_subscribe | Value per subscription (app) | The average value per app subscribe action attributed to your ads. |
#| total_subscribe_value | Subscription value (app) | The total value of app subscribe actions attributed to your ads. |
| Ad impression | | <p><span style="color:darkred"><b>Note</b></span>: If you want to use Ad impression metrics, you can specify any of the following dimension groupings in the dimensions field of the request:<ul><li>one ID dimension (`advertiser_id`, `campaign_id`, `adgroup_id`, or `ad_id`)</li><li>one ID dimension + `stat_time_day`</li></ul> |
#| unique_ad_impression_events | Unique ad impressions | The number of unique ad impression events from your mobile app counted as app events and attributed to your ads. |
#| cost_per_unique_ad_impression_event | Cost per unique ads impression | The average cost of each unique ad impression event attributed to your ad. |
#| customized_ad_impression_event_rate | Unique ad impression rate (%) | The percentage of unique ad impression events out of all app install events. |
#| ads_impression_events | Ads impressions | The total number of ad impression events from your mobile app counted as app events and attributed to your ads. |
#| cost_pre_ad_impression_event | Cost per ads impression | The average cost of each ad impression event attributed to your ad. |
#| value_per_ad_impression_event | Value per ads impression | The average value of each ad impression event attributed to your ad. |
#| total_ad_impression_events_value | Ads impression value | The total value returned from all ad impression events. |
#| total_ad_impression_roas | Ads impression ROAS | The total return on ad spend (ROAS) from ad impression events. |
#| ad_impression_ad_revenue_roas_day0 | Day 0 ad revenue ROAS | Total return on ad spend (ROAS) from ad revenue within 24 hours of app install. |
#| ad_impression_ad_revenue_roas_day2 | Day 2 ad revenue ROAS | Total return on ad spend (ROAS) from ad revenue within 72 hours of app install. |
#| ad_impression_ad_revenue_roas_day6 | Day 6 ad revenue ROAS | Total return on ad spend (ROAS) from ad revenue within 168 hours of app install. |
#| ad_impression_ad_revenue_day0 | Day 0 ad revenue | Total ad revenue within 24 hours of app install. |
#| ad_impression_ad_revenue_day2 | Day 2 ad revenue | Total ad revenue within 72 hours of app install. |
#| ad_impression_ad_revenue_day6 | Day 6 ad revenue | Total ad revenue within 168 hours of app install. |
````

## App metrics (SKAN)
**SKAN metrics are data for SKAN Dedicated Campaigns**.

App metrics measure actions taken in relation to mobile apps, such as installations and registrations attributed to ads.

````xtable
| Field {34%}| Description {22%}| Detail {44%}|
|---|---|---|
| App install | | |
#| skan_app_install | App install (SKAN) | Number of unique app install events attributed to your ads. |
#| skan_cost_per_app_install | Cost per app install (SKAN) | Average cost per unique app install event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| App install (SKAN Privacy Withheld) | | |
#| skan_app_install_withheld | App Installs (SKAN Privacy Withheld) | Number of unique app install events attributed to your ads, with withheld values due to Apple's privacy threshold. |
| Registration | | |
#| skan_registration | Unique registrations (SKAN) | Number of unique registration events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_registration | Cost per unique registration (SKAN) | Average cost per unique registration event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_registration_rate | Unique registration rate (SKAN) | Percentage of unique registration events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_registration | Registrations (SKAN) | Number of in-app registration events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_registration | Cost per registration (SKAN) | Average cost per registration event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Purchase | | |
#| skan_purchase | Unique purchases (SKAN) | Number of unique purchase events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_purchase | Cost per unique purchase (SKAN) | Average cost per unique purchase event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_purchase_rate | Unique purchase rate (%) (SKAN) | Percentage of unique purchase events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_purchase | Purchases (SKAN) | Number of purchase events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_purchase | Cost per purchase (SKAN) | Average cost per purchase event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_purchase_value | Purchase value (SKAN) | Total value returned from all purchase events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_repetitive_active_pay_roas | Purchase ROAS (SKAN) | Total return on ad spend (ROAS) from purchase events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Add to cart | | |
#| skan_add_to_cart | Unique adds to cart (SKAN) | Number of unique add to cart events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_add_to_cart | Cost per unique add to cart (SKAN) | Average cost per unique add to cart event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_add_to_cart_rate | Unique add to cart rate (SKAN) | Percentage of unique add to cart events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_add_to_cart | Adds to cart (SKAN) | Number of add to cart events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_add_to_cart | Cost per add to cart (SKAN) | Average cost per add to cart event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_add_to_cart_value | Add to cart value (SKAN) | Total value returned from all add to cart events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Checkout | | |
#| skan_checkout | Unique checkouts (SKAN) | Number of unique checkout events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_checkout | Cost per unique checkout (SKAN) | Average cost per unique checkout event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_checkout_rate | Unique checkout rate (SKAN) | Percentage of unique checkout events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_checkout | Checkouts (SKAN) | Number of checkout events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_checkout | Cost per checkout (SKAN) | Average cost per checkout event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_checkout_value | Checkout value (SKAN) | Total value returned from all checkout events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| View content | | |
#| skan_view_content | Unique content views (SKAN) | Number of unique view content events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_view_content | Cost per unique content view (SKAN) | Average cost per unique view content event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_view_content_rate | Unique content view rate (SKAN) | Percentage of unique view content events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_view_content | Content views (SKAN) | Number of view content events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_view_content | Cost per content view (SKAN) | Average cost per view content event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_view_content_value | Content view value (SKAN) | Total value returned from all view content events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Add payment info | | |
#| skan_add_payment_info | Unique payment info adds (SKAN) | Number of unique add payment info events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_add_payment_info | Cost per unique payment info add (SKAN) | Average cost per unique add payment info event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_add_payment_info_rate | Unique payment info add rate (SKAN) | Percentage of unique add payment info events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_add_payment_info | Payment info adds (SKAN) | Number of add payment info events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_total_add_payment_info | Cost per payment info add (SKAN) | Average cost per add payment info event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Add to wishlist | | |
#| skan_add_to_wishlist | Unique adds to wishlist (SKAN) | Number of unique add to wishlist events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_add_to_wishlist | Cost per unique add to wishlist (SKAN) | Average cost per unique add to wishlist event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_add_to_wishlist_rate | Unique add to wishlist rate (SKAN) | Percentage of unique add to wishlist events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_add_to_wishlist | Adds to wishlist (SKAN) | Number of add to wishlist events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_add_to_wishlist | Cost per add to wishlist (SKAN) | Average cost per add to wishlist event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_add_to_wishlist_value | Add to wishlist value (SKAN) | Total value returned from all add to wishlist events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Launch app | | |
#| skan_launch_app | Unique app launches (SKAN) | Number of unique launch app events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_launch_app | Cost per unique app launch (SKAN) | Average cost per unique launch app event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_launch_app_rate | Unique app launch rate (SKAN) | Percentage of unique launch app events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_launch_app | App launches (SKAN) | Number of launch app events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_launch_app | Cost per app launch (SKAN) | Average cost per launch app event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Complete tutorial | | |
#| skan_complete_tutorial | Unique tutorials completed (SKAN) | Number of unique complete tutorial events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_complete_tutorial | Cost per unique tutorial completed (SKAN) | Average cost per unique complete tutorial event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_complete_tutorial_rate | Unique tutorial completion rate (SKAN) | Percentage of unique complete tutorial events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_complete_tutorial | Tutorials completed (SKAN) | Number of complete tutorial events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_complete_tutorial | Cost per tutorial completed (SKAN) | Average cost per complete tutorial event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_complete_tutorial_value | Tutorial completion value (SKAN) | Total value returned from all complete tutorial events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Create group | | |
#| skan_create_group | Unique groups created (SKAN) | Number of unique create group events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_create_group | Cost per unique group created (SKAN) | Average cost per unique create group event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_create_group_rate | Unique group creation rate (SKAN) | Percentage of unique create group events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_create_group | Groups created (SKAN) | Number of create group events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_create_group | Cost per group created (SKAN) | Average cost per create group event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_create_group_value | Group creation value (SKAN) | Total value returned from all create group events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Join group | | |
#| skan_join_group | Unique group joins (SKAN) | Number of unique join group events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_join_group | Cost per unique group join (SKAN) | Average cost per unique join group event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_join_group_rate | Unique group joining rate (SKAN) | Percentage of unique join group events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_join_group | Group joins (SKAN) | Number of join group events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_join_group | Cost per group join (SKAN) | Average cost per join group event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_join_group_value | Group joining value (SKAN) | Total value returned from all join group events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Create role | | |
#| skan_create_gamerole | Unique roles created (SKAN) | Number of unique create role events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_create_gamerole | Cost per unique role created (SKAN) | Average cost per unique create role event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_create_gamerole_rate | Unique role creation rate (SKAN) | Percentage of unique create role events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_create_gamerole | Roles created (SKAN) | Number of create role events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_create_gamerole | Cost per role created (SKAN) | Average cost per create role event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_create_gamerole_value | Role creation value (SKAN) | Total value returned from all create role events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Spend credit | | |
#| skan_spend_credits | Unique credit spends (SKAN) | Number of unique spend credit events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_spend_credits | Cost per unique credit spend (SKAN) | Average cost per unique spend credit event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_spend_credits_rate | Unique credit spend rate (SKAN) | Percentage of unique spend credit events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_spend_credits | Credit spends (SKAN) | Number of spend credit events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_spend_credits | Cost per credit spend (SKAN) | Average cost per spend credit event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_spend_credits_value | Credit spend value (SKAN) | Total value returned from all spend credit events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Achieve level | | |
#| skan_achieve_level | Unique levels achieved (SKAN) | Number of unique achieve level events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_achieve_level | Cost per unique level achieved (SKAN) | Average cost per unique achieve level event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_achieve_level_rate | Unique level achievement rate (SKAN) | Percentage of unique achieve level events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_achieve_level | Levels achieved (SKAN) | Number of achieve level events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_achieve_level | Cost per level achieved (SKAN) | Average cost per achieve level event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_achieve_level_value | Level achievement value (SKAN) | Total value returned from all achieve level events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Unlock achievement | | |
#| skan_unlock_achievement | Unique achievements unlocked (SKAN) | Number of unique unlock achievement events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_unlock_achievement | Cost per unique achievement unlocked (SKAN) | Average cost per unique unlock achievement event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_unlock_achievement_rate | Unique achievement unlocked rate (SKAN) | Percentage of unique unlock achievement events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_unlock_achievement | Achievements unlocked (SKAN) | Number of unlock achievement events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_unlock_achievement | Cost per achievement unlocked (SKAN) | Average cost per unlock achievement event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_unlock_achievement_value | Unlocked achievement value (SKAN) | Total value returned from all unlock achievement events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Generate lead | | |
#| skan_sales_lead | Unique leads generated (SKAN) | Number of unique generate lead events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_sales_lead | Cost per unique lead generated (SKAN) | Average cost per unique generate lead event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_sales_lead_rate | Unique lead generation rate (SKAN) | Percentage of unique generate lead events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_sales_lead | Leads generated (SKAN) | Number of generate lead events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_sales_lead | Cost per lead generated (SKAN) | Average cost per generate lead event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_sales_lead_value | Lead generation value (SKAN) | Total value returned from all generate lead events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| In-app ad click | | |
#| skan_in_app_ad_click | Unique in-app ad clicks (SKAN) | Number of unique in-app ad click events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_in_app_ad_click | Cost per unique in-app ad click (SKAN) | Average cost per unique in-app ad click event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_in_app_ad_click_rate | Unique in-app ad click rate (SKAN) | Percentage of unique in-app ad click events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_in_app_ad_click | In-app ad clicks (SKAN) | Number of in-app ad click events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_in_app_ad_click | Cost per in-app ad click (SKAN) | Average cost per in-app ad click event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_in_app_ad_click_value | In-app ad click value (SKAN) | Total value returned from all in-app ad click events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| In-app ad impression | | |
#| skan_in_app_ad_impr | Unique in-app ad impressions (SKAN) | Number of unique in-app ad impression events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_in_app_ad_impr | Cost per unique in-app ad impression (SKAN) | Average cost per unique in-app ad impression event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_in_app_ad_impr_rate | Unique in-app ad impression rate (SKAN) | Percentage of unique in-app ad impression events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_in_app_ad_impr | In-app ad impressions (SKAN) | Number of in-app ad impression events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_in_app_ad_impr | Cost per in-app ad impression (SKAN) | Average cost per in-app ad impression event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_in_app_ad_impr_value | In-app ad impression value (SKAN) | Total value returned from all in-app ad impression events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Loan application | | |
#| skan_loan_apply | Unique loan approvals (SKAN) | Number of unique loan approval events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_loan_apply | Cost per unique loan approval (SKAN) | Average cost per unique loan approval event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_loan_apply_rate | Unique loan approval rate (SKAN) | Percentage of unique loan approval events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_loan_apply | Loan approvals (SKAN) | Number of loan approval events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_loan_apply | Cost per loan approval (SKAN) | Average cost per loan approval event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Loan approval | | |
#| skan_loan_credit | Unique loan approvals (SKAN) | Number of unique loan approval events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_loan_credit | Cost per unique loan approval (SKAN) | Average cost per unique loan approval event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_loan_credit_rate | Unique loan approval rate (SKAN) | Percentage of unique loan approval events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_loan_credit | Loan approvals (SKAN) | Number of loan approval events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_loan_credit | Cost per loan approval (SKAN) | Average cost per loan approval event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Loan disbursal | | |
#| skan_loan_disbursement | Unique loan disbursals (SKAN) | Number of unique loan disbursal events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_loan_disbursement | Cost per unique loan disbursal (SKAN) | Average cost per unique loan disbursal event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_loan_disbursement_rate | Unique loan disbursal rate (SKAN) | Percentage of unique loan disbursal events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_loan_disbursement | Loan disbursals (SKAN) | Number of loan disbursal events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_loan_disbursement | Cost per loan disbursal (SKAN) | Average cost per loan disbursal event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Login | | |
#| skan_login | Unique logins (SKAN) | Number of unique login events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_login | Cost per unique login (SKAN) | Average cost per unique login event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_login_rate | Unique login rate (SKAN) | Percentage of unique login events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_login | Logins (SKAN) | Number of login events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_login | Cost per login (SKAN) | Average cost per login event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Rate | | |
#| skan_ratings | Unique ratings (SKAN) | Number of unique rate events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_ratings | Cost per unique rating (SKAN) | Average cost per unique rate event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_ratings_rate | Unique rate rate (SKAN) | Percentage of unique rate events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_ratings | Ratings (SKAN) | Number of rate events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_ratings | Cost per rating (SKAN) | Average cost per rate event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_ratings_value | Rating value (SKAN) | Total value returned from all rate events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Search | | |
#| skan_search | Unique searches (SKAN) | Number of unique search events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_search | Cost per unique search (SKAN) | Average cost per unique search event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_search_rate | Unique search rate (SKAN) | Percentage of unique search events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_search | Searches (SKAN) | Number of search events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_search | Cost per search (SKAN) | Average cost per search event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Start trial | | |
#| skan_start_trial | Unique trial started (SKAN) | Number of unique start trial events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_start_trial | Cost per unique trial started (SKAN) | Average cost per unique start trial event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_start_trial_rate | Unique trial start rate (SKAN) | Percentage of unique start trial events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_start_trial | Trials started (SKAN) | Number of start trial events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_start_trial | Cost per trial started (SKAN) | Average cost per start trial event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Subscribe | | |
#| skan_subscribe | Unique subscriptions (SKAN) | Number of unique subscribe events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_subscribe | Cost per unique subscription (SKAN) | Average cost per unique subscribe event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_subscribe_rate | Unique subscription rate (SKAN) | Percentage of unique subscribe events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_subscribe | Subscriptions (SKAN) | Number of subscribe events attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_total_subscribe | Cost per subscription (SKAN) | Average cost per subscribe event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_total_subscribe_value | Subscription value (SKAN) | Total value returned from all subscribe events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
````

## Website metrics
Website metrics measure actions taken on websites as a result of ads, such as completed purchases.

````xtable
| Field {34%}| Description {22%}| Detail {44%}|
|---|---|---|
| Purchase ROAS | | |
#| complete_payment_roas | Purchase ROAS (website) | Return on ad spend (ROAS) from website purchase actions. |
| Purchase | | |
#| complete_payment | Purchases (website) | Number of website purchase actions attributed to your ads. |
#| cost_per_complete_payment | Cost per purchase (website) | Average cost per website purchase action attributed to your ads. |
#| complete_payment_rate | Unique purchase rate (website) (%) | Percentage of website purchase actions from all clicks attributed to your ads. |
#| value_per_complete_payment | Value per purchase (website) | Average value per website purchase action attributed to your ads. |
#| total_complete_payment_rate | Purchase value (website) | Total value of website purchase actions attributed to your ads. |
| Landing page view | | |
#| total_landing_page_view | Landing page views (website) | Number of landing page view events from your ads. |
#| cost_per_landing_page_view | Cost per landing page view (website) | Average cost per landing page view event on your website. |
#| landing_page_view_rate | Landing page view rate (website) | Percentage of click events that are landing page view events. |
| Engaged session | | |
#| website_total_engaged_session | Total engaged session (web) | The number of web engaged session actions attributed to your ads. Engaged session is defined as session duration time lasts longer than 10 seconds or jump to a new page view. |
#| website_engaged_session_rate | Engaged session rate (web) | The percentage of web engaged session actions from all clicks attributed to your ads. |
#| website_cost_per_engaged_session | Cost per engaged session (web) | The average cost per web engaged session action attributed to your ads. |
| Page view | | |
#| total_pageview | Page views (website) | Number of page view events. |
#| cost_per_pageview | Cost per page view (website) | Average cost per page view event. |
#| pageview_rate | Page view rate (website) (%) | Percentage of page view events out of all click events. |
#| avg_value_per_pageview | Value per page view (website) | Average value returned from each page view event. |
#| total_value_per_pageview | Total page view value (website) | Total value returned from all page view events. |
| Click button | | |
#| button_click | Button clicks (website) | The number of website click button actions attributed to your ads. |
#| cost_per_button_click | Cost per button click (website) | The average cost per website click button action attributed to your ads. |
#| button_click_rate | Unique button click rate (website) | The percentage of website click button actions from all clicks attributed to your ads. |
#| value_per_button_click | Value per button click (website) | The average value per website click button action attributed to your ads. |
#| total_button_click_value | Button click value (website) | The total value of website click button actions attributed to your ads. |
| Contact | | |
#| online_consult | Contacts (website) | The number of website contact actions attributed to your ads. |
#| cost_per_online_consult | Cost per contact (website) | The average cost per website contact action attributed to your ads. |
#| online_consult_rate | Unique contact rate (website) | The percentage of website contact actions from all clicks attributed to your ads. |
#| value_per_online_consult | Value per contact (website) | The average value per website contact action attributed to your ads. |
#| total_online_consult_value | Contact value (website) | The total value of website contact actions attributed to your ads. |
| Registration | | |
#| user_registration | Registrations (website) | The number of website complete registration actions attributed to your ads. |
#| cost_per_user_registration | Cost per registration (website) | The average cost per website complete registration action attributed to your ads. |
#| user_registration_rate | Unique registration rate (website) (%) | The percentage of website complete registration actions from all clicks attributed to your ads. |
#| value_per_user_registration | Value per registration (website) | The average value per website complete registration action attributed to your ads. |
#| total_user_registration_value | Registration value (website) | The total value of website complete registration actions attributed to your ads. |
| View content | | |
#| page_content_view_events | Content views (website) | The number of website view content actions attributed to your ads. |
#| cost_per_page_content_view_event | Cost per content view (website) | The average cost per website view content action attributed to your ads. |
#| page_content_view_event_rate | Unique content view rate (website) (%) | The percentage of website view content actions from all clicks attributed to your ads. |
#| value_per_page_content_view_event | Value per content view (website) | The average value per website view content action attributed to your ads. |
#| total_page_view_content_events_value | Content view value (website) | The total value of website view content actions attributed to your ads. |
| Add to cart | | |
#| web_event_add_to_cart | Adds to cart (website) | The number of website add to cart actions attributed to your ads. |
#| cost_per_web_event_add_to_cart | Cost per add to cart (website) | The average cost per website add to cart action attributed to your ads. |
#| web_event_add_to_cart_rate | Unique add to cart rate (website) (%) | The percentage of website add to cart actions from all clicks attributed to your ads. |
#| value_per_web_event_add_to_cart | Value per add to cart (website) | The average value per website add to cart action attributed to your ads. |
#| total_web_event_add_to_cart_value | Add to cart value (website) | The total value of website add to cart actions attributed to your ads. |
| Place order | | |
#| on_web_order | Orders placed (website) | The number of website place order actions attributed to your ads. |
#| cost_per_on_web_order | Cost per order placed (website) | The average cost per website place order action attributed to your ads. |
#| on_web_order_rate | Unique orders placed rate (website) (%) | The percentage of website place order actions from all clicks attributed to your ads. |
#| value_per_on_web_order | Value per order placed (website) | The average value per website place order action attributed to your ads. |
#| total_on_web_order_value | Orders placed value (website) | The total value of website place order actions attributed to your ads. |
| Initiate checkout | | |
#| initiate_checkout | Checkouts initiated (website) | The number of website initiate checkout actions attributed to your ads. |
#| cost_per_initiate_checkout | Cost per checkout initiated (website) | The average cost per website initiate checkout action attributed to your ads. |
#| initiate_checkout_rate | Unique checkout initiation rate (website) (%) | The percentage of website initiate checkout actions from all clicks attributed to your ads. |
#| value_per_initiate_checkout | Value per checkout initiated (website) | The average value per website initiate checkout action attributed to your ads. |
#| total_initiate_checkout_value | Checkout initiation value (website) | The total value of website initiate checkout actions attributed to your ads. |
| Add payment info | | |
#| add_billing | Payment info added (website) | Number of add payment info actions on your website. |
#| cost_per_add_billing | Cost per payment info added (website) | Average cost per add payment info action on your website attributed to your ads. |
#| add_billing_rate | Unique payment info added rate (%) (website) | Percentage of add payment info actions on your website from all clicks attributed to your ads. |
#| value_per_add_billing | Value per payment info added (website) | Average value per add payment info action on your website attributed to your ads. |
#| total_add_billing_value | Add payment info value (website) | Total value of add payment info actions on your website attributed to your ads. |
| Search | | |
#| page_event_search | Searches (website) | The number of website search actions attributed to your ads. |
#| cost_per_page_event_search | Cost per search (website) | The average cost per website search action attributed to your ads. |
#| page_event_search_rate | Unique search rate (website) (%) | The percentage of website search actions from all clicks attributed to your ads. |
#| value_per_page_event_search | Value per search (website) | The average value per website search action attributed to your ads. |
#| total_page_event_search_value | Search value (website) | The total value of website search actions attributed to your ads. |
| Lead | | |
#| form | Form submissions (website) | The number of website lead actions attributed to your ads. |
#| cost_per_form | Cost per form submitted (website) | The average cost per website lead action attributed to your ads. |
#| form_rate | Unique form submission rate (website) (%) | The percentage of website lead actions from all clicks attributed to your ads. |
#| value_per_form | Value per form submitted (website) | The average value per website lead attributed to your ads. |
#| total_form_value | Form submission value (website) | The total value of website lead actions attributed to your ads. |
| Download | | |
#| download_start | Downloads (website) | The number of website download actions attributed to your ads. |
#| cost_per_download_start | Cost per download (website) | The average cost per website download action attributed to your ads. |
#| download_start_rate | Unique download rate (website) (%) | The percentage of website download actions from all clicks attributed to your ads. |
#| value_per_download_start | Value per download (website) | The average value per website download action attributed to your ads. |
#| total_download_start_value | Download value (website) | The total value of website download actions attributed to your ads. |
| Add to wishlist | | |
#| on_web_add_to_wishlist | Adds to wishlist (website) | The number of website add to wishlist actions attributed to your ads. |
#| cost_per_on_web_add_to_wishlist | Cost per add to wishlist (website) | The average cost per website add to wishlist action attributed to your ads. |
#| on_web_add_to_wishlist_per_click | Unique add to wishlist rate (website) (%) | The percentage of website add to wishlist actions from all clicks attributed to your ads. |
#| value_per_on_web_add_to_wishlist | Value per add to wishlist (website) | The average value per website add to wishlist action attributed to your ads. |
#| total_on_web_add_to_wishlist_value | Add to wishlist value (website) | The total value of website add to wishlist actions attributed to your ads. |
| Subscribe | | |
#| on_web_subscribe | Subscriptions (website) | The number of website subscribe actions attributed to your ads. |
#| cost_per_on_web_subscribe | Cost per subscription (website) | The average cost per website subscribe action attributed to your ads. |
#| on_web_subscribe_per_click | Unique subscription rate (website) (%) | The percentage of website subscribe actions from all clicks attributed to your ads. |
#| value_per_on_web_subscribe | Value per subscription (website) | The average value per website subscribe action attributed to your ads. |
#| total_on_web_subscribe_value | Subscription value (website) | The total value of website subscribe actions attributed to your ads. |
| Find location | | |
#| website_total_find_location | Total find location | The number of find location events. |
#| website_cost_per_find_location | Cost per find location | The average cost of each find location event. |
#| website_find_location_rate | Find location rate | The percentage of find location events out of all click events. |
#| website_value_per_find_location | Value per find location | The average value returned from each find location event. |
#| website_total_find_location_value | Total find location value | The total value returned from all find location events. |
| Schedule | | |
#| website_total_schedule | Total schedule | The number of schedule events. |
#| website_cost_per_schedule | Cost per schedule | The average cost of each schedule event. |
#| website_schedule_rate | Schedule rate | The percentage of schedule events out of all click events. |
#| website_value_per_schedule | Value per schedule | The average value returned from each schedule event. |
#| website_total_schedule_value | Total schedule value | The total value returned from all schedule events. |
| Start trial | | |
#| website_total_start_trial | Total start trial (website) | Number of website start trial actions attributed to your ads. |
#| website_cost_per_start_trial | Cost per start trial (website) | Average cost per website start trial action attributed to your ads. |
#| website_start_trial_rate | Start trial rate (website) | Percentage of website start trial actions from all clicks attributed to your ads. |
#| website_value_per_start_trial | Value per start trial (website) | Average value per website start trial action attributed to your ads. |
#| website_total_start_trial_value | Total start trial value (website | Total value of website start trial actions attributed to your ads. |
| Submit application | | |
#| website_total_submit_application | Total submit application (website) | Number of website submit application actions attributed to your ads. |
#| website_cost_per_submit_application | Cost per submit application (website) | Average cost per website submit application action attributed to your ads. |
#| website_submit_application_rate | Submit application rate (website) | Percentage of website submit application actions from all clicks attributed to your ads. |
#| website_value_per_submit_application | Value per submit application (website) | Average value per website submit application action attributed to your ads. |
#| website_total_submit_application_value | Total submit application value (website) | Total value of website submit application actions attributed to your ads. |
| Application approval | | |
#| website_application_approval | Application approvals (website) | Number of website application approval actions attributed to your ads. |
#| website_cost_per_application_approval | Cost per application approval (website) | Average cost per website application approval action attributed to your ads. |
#| website_application_approval_rate | Application approval rate (website) | Percentage of website application approval actions from all clicks attributed to your ads. |
#| website_value_per_application_approval | Value per application approval (website) | Average value per website application approval action attributed to your ads. |
#| website_total_application_approval_value | Total application approval value (website) | Total value of website application approval actions attributed to your ads. |
| Customize product | | |
#| website_total_customize_product | Total customize product | The number of customize product events. |
#| website_cost_per_customize_product | Cost per customize product | The average cost of each customize product event. |
#| website_customize_product_rate | Customize product rate | The percentage of customize product events out of all click events. |
#| website_value_per_customize_product | Value per customize product | The average value returned from each customize product event. |
#| website_total_customize_product_value | Total customize product value | The total value returned from all customize product events. |
````

## TikTok metrics
TikTok metrics measure conversions directly within TikTok, including purchase actions and checkouts initiated.

````xtable
| Field {34%}| Description {22%}| Detail {44%}|
|---|---|---|
| Purchases ROAS | | |
#| onsite_purchases_roas | Purchases ROAS (TikTok) | The return on ad spend (ROAS) from TikTok purchase actions. |
| Purchases | | |
#| onsite_total_purchase | Purchases (TikTok) | The number of TikTok purchase actions attributed to your ads. |
#| onsite_cost_per_purchase | Cost per purchase (TikTok) | The average cost per TikTok purchase action attributed to your ads. |
#| onsite_purchase_rate | Purchase rate (TikTok) (%) | The percentage of TikTok purchase actions from all clicks attributed to your ads. |
#| onsite_value_per_purchase | Value per purchase (TikTok) | The average value per TikTok purchase action attributed to your ads. |
#| onsite_total_purchase_value | Purchase value (TikTok) | The total value of TikTok purchase actions attributed to your ads. |
#| onsite_unique_purchase | Unique purchase | Number of unique purchase actions attributed to your ad. |
#| onsite_cost_per_unique_purchase | Cost per unique purchase | Average cost per unique purchase action attributed to your ad. |
#| onsite_purchase_value_day0 | Day 0 purchase value | Total value of all purchase actions within 24 hours of click. |
#| onsite_purchase_value_day6 | Day 6 purchase value | Total value of all purchase actions within 168 hours of click. |
#| onsite_purchase_value_day13 | Day 13 purchase value | Total value of all purchase actions within 336 hours of click. |
#| onsite_purchase_value_calendar_day0 | Calendar day 0 purchase value | Total value of all purchase actions from the time of click until the end of that day. |
#| onsite_purchase_value_calendar_day6 | Calendar day 6 purchase value | Total value of all purchase actions from the time of click until the end of the 6th day that follows. |
#| onsite_purchase_value_calendar_day13 | Calendar day 13 purchase value | Total value of all purchase actions from the time of click until the end of the 13th day that follows. |
#| onsite_purchase_roas_day0 | Day 0 purchase (ROAS) | Total return on Mini spend (ROAS) from purchase actions within 24 hours of click. |
#| onsite_purchase_roas_day6 | Day 6 purchase (ROAS) | Total value of all purchase actions from the time of click until the end of the 6th day that follows. |
#| onsite_purchase_roas_day13 | Day 13 purchase (ROAS) | Total value of all purchase actions from the time of click until the end of the 13th day that follows. |
#| onsite_purchase_roas_calendar_day0 | Calender Day 0 purchase (ROAS) | Total value of all purchase actions from the time of click until the end of that day. |
#| onsite_purchase_roas_calendar_day6 | Calender day 6 purchase (ROAS) | Total value of all purchase actions from the time of click until the end of the 6th day that follows. |
#| onsite_purchase_roas_calendar_day13 | Calender day 13 purchase (ROAS) | Total value of all purchase actions from the time of click until the end of the 13th day that follows. |
| Checkouts initiated | | |
#| onsite_total_checkout_initiation | Checkouts initiated (TikTok) | The number of TikTok checkouts initiated actions attributed to your ads. |
#| onsite_cost_per_checkout_initiation | Cost per checkout initiation (TikTok) | The average cost per TikTok checkout initiation action attributed to your ads. |
#| onsite_checkout_initiation_rate | Checkout initiation rate (TikTok) (%) | The percentage of TikTok checkout initiation actions from all clicks attributed to your ads. |
#| onsite_value_per_checkout_initiation | Value per checkout initiation (TikTok) | The average value per TikTok checkout initiation action attributed to your ads. |
#| onsite_total_checkout_initiation_value | Checkout initiation value (TikTok) | The total value of TikTok checkout initiation actions attributed to your ads. |
| Add to cart | | |
#| onsite_total_add_to_cart | Add to cart (TikTok) | The number of TikTok add to cart actions attributed to your ads. |
#| onsite_cost_per_add_to_cart | Cost per add to cart (TikTok) | The average cost per TikTok add to cart action attributed to your ads. |
#| onsite_add_to_cart_rate | Add to cart rate (TikTok) (%) | The percentage of TikTok add to cart actions from all clicks attributed to your ads. |
#| onsite_value_per_add_to_cart | Value per add to cart (TikTok) | The average value per TikTok add to cart action attributed to your ads. |
#| onsite_total_add_to_cart_value | Add to cart value (TikTok) | The total value of TikTok add to cart actions attributed to your ads. |
| Product details page view | | |
#| onsite_total_product_details_page_view | Product details page view (TikTok) | The number of TikTok product details page view actions attributed to your ads. |
#| onsite_cost_per_product_details_page_view | Cost per product details page view (TikTok) | The average cost per TikTok product details page view action attributed to your ads. |
#| onsite_product_details_page_view_rate | Product details page view rate (TikTok) (%) | The percentage of TikTok product details page view actions from all clicks attributed to your ads. |
#| onsite_value_per_product_details_page_view | Value per product details page view (TikTok) | The average value per TikTok product details page view action attributed to your ads. |
#| onsite_total_product_details_page_view_value | Product details page view value (TikTok) | The total value of TikTok product details page view actions attributed to your ads. |
| Active by conversion time | | |
#| onsite_active_by_conversion_time | Active by conversion time | First launches attributed to your ad. |
#| onsite_cost_per_active_by_conversion_time | Cost per active by conversion time | Average cost per launch. |
| Active | | |
#| onsite_total_active | Active | First launches attributed to your ad. |
#| onsite_cost_per_active | Cost per active | Average cost per first launch. |
| Launch | | |
#| onsite_unique_launch | Unique launch | Number of unique non-first launches attributed to your ad. |
#| onsite_cost_per_unique_launch | Cost per unique launch | Average cost for each unique non-first launch that is attributed to your ad. |
#| onsite_launch_rate | Launch rate (%) | Percentage of non-first launch actions from all clicks attributed to your ad. |
#| onsite_total_launch | Total launch | Non-first launch actions attributed to your ad. |
#| onsite_cost_per_total_launch | Cost per launch | Average cost for each non-first launch attributed to your ad. |
| Registration | | |
#| onsite_unique_registration | Unique registration | Number of unique registrations attributed to your ad. |
#| onsite_cost_per_unique_registration | Cost per unique registration | Average cost for each unique registration attributed to your ad. |
#| onsite_registration_rate | Registration rate (%) | Percentage of registrations from all clicks attributed to your ad. |
#| onsite_total_registration | Total registration | Number of registrations attributed to your ad. |
#| onsite_cost_per_total_registration | Cost per registration | Average cost for each registration attributed to your ad. |
| Ad impressions | | |
#| onsite_unique_ad_impression | Unique ad impression event | Number of unique ad impression events attributed to your ad. |
#| onsite_cost_per_unique_ad_impression | Cost per unique ad impression event | Average cost for each unique ad impression event attributed to your ad. |
#| onsite_ad_impression_rate | Ad impression event rate | Percentage of ad impression events from all clicks attributed to your ad. |
#| onsite_total_ad_impression | Total ad impression events | Number of ad impression events attributed to your ad. |
#| onsite_cost_per_total_ad_impression | Cost per ad impression event | Average cost for each ad impression event attributed to your ad. |
#| onsite_total_ad_impression_value | Total ad impression value | Total value for each ad impression event attributed to your ad. |
#| onsite_value_per_ad_impression_value | Value per ad impression event | Value for each ad impression event attributed to your ad. |
#| onsite_ad_impression_ad_revenue_day0 | Day 0 ad revenue | Total ad revenue within 24 hours of click. |
#| onsite_ad_impression_ad_revenue_day6 | Day 6 ad revenue | Total ad revenue within 168 hours of click. |
#| onsite_ad_impression_ad_revenue_day13 | Day 13 ad revenue | Total ad revenue within 336 hours of click. |
#| onsite_ad_impression_ad_revenue_calendar_day0 | Calendar day 0 ad revenue | Total ad revenue collected from time of click until end of that day. |
#| onsite_ad_impression_ad_revenue_calendar_day6 | Calendar day 6 ad revenue | Total ad revenue collected from the time of click until the end of the 6th day that follows. |
#| onsite_ad_impression_ad_revenue_calendar_day13 | Calendar day 13 ad revenue | Total ad revenue collected from the time of click until the end of the 13th day that follows. |
#| onsite_ad_impression_ad_revenue_roas_day0 | Day 0 ad revenue (ROAS) | Total return on ad spend (ROAS) from ad revenue within 24 hours of click. |
#| onsite_ad_impression_ad_revenue_roas_day6 | Day 6 ad revenue (ROAS) | Total return on ad spend (ROAS) from ad revenue within 168 hours of click. |
#| onsite_ad_impression_ad_revenue_roas_day13 | Day 13 ad revenue (ROAS) | Total return on ad spend (ROAS) from ad revenue within 336 hours of click. |
#| onsite_ad_impression_ad_revenue_roas_calendar_day0 | Calender Day 0 ad revenue (ROAS) | Total return on ad spend (ROAS) from ad revenue from the time of click until the end of that day. |
#| onsite_ad_impression_ad_revenue_roas_calendar_day6 | Calender Day 6 ad revenue (ROAS) | Total return on ad spend (ROAS) from ad revenue from the time of click until the end of the 6th day that follows. |
#| onsite_ad_impression_ad_revenue_roas_calendar_day13 | Calender Day 13 ad revenue (ROAS) | Total return on ad spend (ROAS) from ad revenue from the time of click until the end of the 13th day that follows. |
| Add to wishlist | | |
#| onsite_add_to_wishlist | Adds to wishlist (TikTok) | The number of add to wishlist actions attributed to your ads. |
#| cost_per_onsite_add_to_wishlist | Cost per add to wishlist (TikTok) | The average cost per add to wishlist action attributed to your ads. |
#| onsite_add_to_wishlist_rate | Unique add to wishlist rate (TikTok) | The percentage of add to wishlist actions from all clicks attributed to your ads. |
#| value_per_onsite_add_to_wishlist | Value per add to wishlist (TikTok) | The average value per add to wishlist action attributed to your ads. |
#| total_onsite_add_to_wishlist_value | Add to wishlist value (TikTok) | The total value of add to wishlist actions attributed to your ads. |
| Add billing | | |
#| onsite_add_billing | Billing adds (TikTok) | Number of add billing events attributed to your ads within a TikTok-owned property. |
#| cost_per_onsite_add_billing | Cost per billing add (TikTok) | Average cost per add billing event within a TikTok-owned property. |
#| onsite_add_billing_rate | Billing add rate (TikTok) (%) | Percentage of add billing events out of all click events within a TikTok-owned property. |
#| value_per_onsite_add_billing | Value per billing add (TikTok) | Average value returned from each add billing event within a TikTok-owned property. |
#| total_onsite_add_billing_value | Billing add value (TikTok) | Total value returned from all add billing events within a TikTok-owned property. |
| Lead | | |
#| onsite_form | Form submissions (TikTok) | The number of lead actions attributed to your ads. |
#| cost_per_onsite_form | Cost per form submitted (TikTok) | The average cost per lead action attributed to your ads. |
#| onsite_form_rate | Unique form submission rate (TikTok) | The percentage of lead actions from all clicks attributed to your ads. |
#| value_per_onsite_form | Value per form submitted (TikTok) | The average value per lead attributed to your ads. |
#| total_onsite_form_value | Form submission value (TikTok) | The total value of lead actions attributed to your ads. |
| Filtered out form submissions | | |
#| onsite_total_filtered_out_form_submission | Filtered out form submissions (TikTok) | This is the number of users that were disqualified and not counted as submissions due to the logic you set. |
| App store click | | |
#| onsite_download_start | App store clicks (TikTok) | Number of app store click events attributed to your ads within a TikTok-owned property. For the ads placed on Pangle, this metric may be incomplete due to the automatic traffic optimization strategy. |
#| cost_per_onsite_download_start | Cost per app store click (TikTok) | Average cost per app store click event within a TikTok-owned property. For the ads placed on Pangle, this metric may be incomplete due to the automatic traffic optimization strategy. |
#| onsite_download_start_rate | App store click rate (TikTok) (%) | Percentage of app store click events out of all click events within a TikTok-owned property. For the ads placed on Pangle, this metric may be incomplete due to the automatic traffic optimization strategy. |
| Destination visit | | <p><span style="color:darkred"><b>Note</b></span>:<ul><li>Destination visit (TikTok) metrics can only be used in synchronous basic reports.</li><li>Destination visit (TikTok) metrics are supported for the following dimension groupings:<ul><li><b>one</b> ID dimension (`advertiser_id`, `campaign_id`, `adgroup_id`, or `ad_id`).</li><li><b>one</b> ID dimension (`advertiser_id`, `campaign_id`, `adgroup_id`, or `ad_id`) and `stat_time_day`.</li></ul></li></ul> |
#| onsite_destination_visits | Destination visits (TikTok) | The number of destination visit events that are attributed to your ads within a TikTok-owned property. |
#| cost_per_onsite_destination_visit | Cost per destination visit (TikTok) | The average cost of each destination visit event within a TikTok-owned property. |
#| onsite_destination_visit_rate | Destination visit rate (TikTok) (%) | The percentage of destination visit events out of all click events within a TikTok-owned property. |
| Page view | | |
#| ix_page_view_count | Page views (TikTok) | Number of page views on the instant page. For the ads placed on Pangle, this metric may be incomplete due to the automatic traffic optimization strategy. |
#| cost_per_ix_page_view_count | Cost per page view (TikTok) | Average cost per page view event within a TikTok-owned property. For the ads placed on Pangle, this metric may be incomplete due to the automatic traffic optimization strategy. |
#| ix_page_view_count_rate | Page view rate (TikTok) (%) | Percentage of page view events out of all click events within a TikTok-owned property. For the ads placed on Pangle, this metric may be incomplete due to the automatic traffic optimization strategy. |
| Outbound clicks | | |
#| ix_button_click_count | Outbound clicks (TikTok) | Number of call-to-action button click events within a TikTok-owned property. For the ads placed on Pangle, this metric may be incomplete due to the automatic traffic optimization strategy. |
#| cost_per_ix_button_click_count | Cost per outbound click (TikTok) | Average cost per call-to-action button click event within a TikTok-owned property. For the ads placed on Pangle, this metric may be incomplete due to the automatic traffic optimization strategy. |
#| ix_button_click_count_rate | Outbound click rate (TikTok) (%) | Percentage of call-to-action button click events out of all click events within a TikTok-owned property. For the ads placed on Pangle, this metric may be incomplete due to the automatic traffic optimization strategy. |
| Product clicks | | |
#| ix_product_click_count | Product clicks (TikTok) | Number of product clicks on the instant page that leads to detail page views. |
#| cost_per_ix_product_click_count | Cost per product clicks (TikTok) | Average cost per product click event within a TikTok-owned property. |
#| ix_product_click_count_rate | Product clicks rate (TikTok) (%) | Percentage of product click events out of all click events within a TikTok-owned property. |
````

## Offline metrics
Offline metrics measure offline actions attributed to ads, such as completed purchases in physical stores.

````xtable
| Field {34%}| Description {22%}| Detail {44%}|
|---|---|---|
| CRM Event | | |
#| offline_total_crm_events | CRM events | Number of CRM events attributed to your ads. |
#| offline_cost_per_crm_event | Cost per CRM Event | Average cost per CRM event. |
#| offline_crm_event_rate | CRM Event Rate (%) | Average value returned from each CRM event. |
#| offline_value_per_crm_event | Value per CRM Event | Percentage of CRM events out of all click events. |
#| offline_crm_event_value | CRM event value | Total value returned from all CRM events. |
| Purchase | | |
#| offline_shopping_events | Purchases (offline) | The number of offline purchase actions attributed to your ads. |
#| cost_per_offline_shopping_event | Cost per purchase (offline) | The average cost per offline purchase action attributed to your ads. |
#| offline_shopping_event_rate | Unique purchase rate (offline) (%) | The percentage of offline purchase actions from all clicks attributed to your ads. |
#| value_per_offline_shopping_event | Value per purchase (offline) | The average value per offline purchase action attributed to your ads. |
#| offline_shopping_events_value | Purchase value (offline) | The total value of offline purchaseactions attributed to your ads. |
| Purchase ROAS | | |
#| offline_shopping_events_roas | Purchase ROAS (offline) | The return on ad spend (ROAS) from offline purchase actions. |
| Contact | | |
#| offline_contact_events | Contacts (offline) | The number of offline contact actions attributed to your ads. |
#| cost_per_offline_contact_event | Cost per contact (offline) | The average cost per offline contact action attributed to your ads. |
#| offline_contact_event_rate | Unique contact rate (offline) (%) | The percentage of offline contact actions from all clicks attributed to your ads. |
#| value_per_offline_contact_event | Value per contact (offline) | The average value per offline contact action attributed to your ads. |
#| offline_contact_events_value | Contact value (offline) | The total value of offline contact actions attributed to your ads. |
| Subscribe | | |
#| offline_subscribe_events | Subscriptions (offline) | The number of offline subscribe actions attributed to your ads. |
#| cost_per_offline_subscribe_event | Cost per subscription (offline) | The average cost per offline subscribe action attributed to your ads. |
#| offline_subscribe_event_rate | Unique subscription rate (offline) (%) | The percentage of offline subscribe actions from all clicks attributed to your ads. |
#| value_per_offline_subscribe_event | Value per subscription (offline) | The average value per offline subscribe action attributed to your ads. |
#| offline_subscribe_events_value | Subscription value (offline) | The total value of offline subscribe actions attributed to your ads. |
| Lead | | |
#| offline_form_events | Form submissions (offline) | The number of offline lead actions attributed to your ads. |
#| cost_per_offline_form_event | Cost per form submitted (offline) | The average cost per offline lead action attributed to your ads. |
#| offline_form_event_rate | Unique form submission rate (offline) (%) | The percentage of offline lead actions from all clicks attributed to your ads. |
#| value_per_offline_form_event | Value per form submitted (offline) | The average value per offline lead attributed to your ads. |
#| offline_form_events_value | Form submission value (offline) | The total value of offline lead actions attributed to your ads. |
| Add payment info | | |
#| offline_add_payment_info_events | Payment info adds (offline) | The number of offline add payment info actions attributed to your ads. |
#| cost_per_offline_add_payment_info_event | Cost per payment info add (offline) | The average cost per offline add payment info action attributed to your ads. |
#| offline_add_payment_info_event_rate | Unique payment info add rate (offline) (%) | The percentage of offline add payment info actions from all clicks attributed to your ads. |
#| value_per_offline_add_payment_info_event | Value per payment info add (offline) | The average value per offline add payment info action attributed to your ads. |
#| offline_add_payment_info_events_value | Payment info add value (offline) | The total value of offline add payment info actions attributed to your ads. |
| Add to cart | | |
#| offline_add_to_cart_events | Adds to cart (offline) | The number of offline add to cart actions attributed to your ads. |
#| cost_per_offline_add_to_cart_event | Cost per add to cart (offline) | The average cost per offline add to cart action attributed to your ads. |
#| offline_add_to_cart_event_rate | Unique add to cart rate (offline) (%) | The percentage of offline add to cart actions from all clicks attributed to your ads. |
#| value_per_offline_add_to_cart_event | Value per add to cart (offline) | The average value per offline add to cart action attributed to your ads. |
#| offline_add_to_cart_events_value | Add to cart value (offline) | The total value of offline add to cart actions attributed to your ads. |
| Add to wishlist | | |
#| offline_add_to_wishlist_events | Adds to wishlist (offline) | The number of offline add to wishlist actions attributed to your ads. |
#| cost_per_offline_add_to_wishlist_event | Cost per add to wishlist (offline) | The average cost per offline add to wishlist action attributed to your ads. |
#| offline_add_to_wishlist_event_rate | Unique add to wishlist rate (offline) (%) | The percentage of offline add to wishlist actions from all clicks attributed to your ads. |
#| value_per_offline_add_to_wishlist_event | Value per add to wishlist (offline) | The average value per offline add to wishlist action attributed to your ads. |
#| offline_add_to_wishlist_events_value | Add to wishlist value (offline) | The total value of offline add to wishlist actions attributed to your ads. |
| Click button | | |
#| offline_click_button_events | Button clicks (offline) | The number of offline click button actions attributed to your ads. |
#| cost_per_offline_click_button_event | Cost per button click (offline) | The average cost per offline click button action attributed to your ads. |
#| offline_click_button_event_rate | Unique button click rate (offline) (%) | The percentage of offline click button actions from all clicks attributed to your ads. |
#| value_per_offline_click_button_event | Value per button click (offline) | The average value per offline click button action attributed to your ads. |
#| offline_click_button_events_value | Button click value (offline) | The total value of offline click button actions attributed to your ads. |
| Registration | | |
#| offline_complete_registration_events | Registrations (offline) | The number of offline complete registration actions attributed to your ads. |
#| cost_per_offline_complete_registration_event | Cost per registration (offline) | The average cost per offline complete registration action attributed to your ads. |
#| offline_complete_registration_event_rate | Unique registration rate (offline) (%) | The percentage of offline complete registration actions from all clicks attributed to your ads. |
#| value_per_offline_complete_registration_event | Value per registration (offline) | The average value per offline complete registration action attributed to your ads. |
#| offline_complete_registration_events_value | Registration value (offline) | The total value of offline complete registration actions attributed to your ads. |
| Download | | |
#| offline_download_events | Downloads (offline) | The number of offline download actions attributed to your ads. |
#| cost_per_offline_download_event | Cost per download (offline) | The average cost per offline download action attributed to your ads. |
#| offline_download_event_rate | Unique download rate (offline) (%) | The percentage of offline download actions from all clicks attributed to your ads. |
#| value_per_offline_download_event | Value per download (offline) | The average value per offline download action attributed to your ads. |
#| offline_download_events_value | Download value (offline) | The total value of offline download actions attributed to your ads. |
| Initiate checkout | | |
#| offline_initiate_checkout_events | Checkouts initiated (offline) | The number of offline initiate checkout actions attributed to your ads. |
#| cost_per_offline_initiate_checkout_event | Cost per checkout initiated (offline) | The average cost per offline initiate checkout action attributed to your ads. |
#| offline_initiate_checkout_event_rate | Unique checkout initiation rate (offline) (%) | The percentage of offline initiate checkout actions from all clicks attributed to your ads. |
#| value_per_offline_initiate_checkout_event | Value per checkout initiated (offline) | The average value per offline initiate checkout action attributed to your ads. |
#| offline_initiate_checkout_events_value | Checkout initiation value (offline) | The total value of offline initiate checkout actions attributed to your ads. |
| Place order | | |
#| offline_place_order_events | Orders placed (offline) | The number of offline place order actions attributed to your ads. |
#| cost_per_offline_place_order_event | Cost per order placed (offline) | The average cost per offline place order action attributed to your ads. |
#| offline_place_order_event_rate | Unique orders placed rate (offline) (%) | The percentage of offline place order actions from all clicks attributed to your ads. |
#| value_per_offline_place_order_event | Value per order placed (offline) | The average value per offline place order action attributed to your ads. |
#| offline_place_order_events_value | Orders placed value (offline) | The total value of offline place order actions attributed to your ads. |
| Search | | |
#| offline_search_events | Searches (offline) | The number of offline search actions attributed to your ads. |
#| cost_per_offline_search_event | Cost per search (offline) | The average cost per offline search action attributed to your ads. |
#| offline_search_event_rate | Unique search rate (offline) (%) | The percentage of offline search actions from all clicks attributed to your ads. |
#| value_per_offline_search_event | Value per search (offline) | The average value per offline search action attributed to your ads. |
#| offline_search_events_value | Search value (offline) | The total value of offline search actions attributed to your ads. |
| View content | | |
#| offline_view_content_events | Content views (offline) | The number of offline view content actions attributed to your ads. |
#| cost_per_offline_view_content_event | Cost per content view (offline) | The average cost per offline view content action attributed to your ads. |
#| offline_view_content_event_rate | Unique content view rate (offline) (%) | The percentage of offline view content actions from all clicks attributed to your ads. |
#| value_per_offline_view_content_event | Value per content view (offline) | The average value per offline view content action attributed to your ads. |
#| offline_view_content_events_value | Content view value (offline) | The total value of offline view content actions attributed to your ads. |
| Preferred leads | | |
#| offline_preferred_leads | Preferred leads (offline) | Number of preferred leads events attributed to your TikTok ads. |
#| offline_cost_per_preferred_lead | Cost per preferred leads (offline) | Average cost per preferred lead event attributed to your TikTok ads. |
#| offline_preferred_lead_rate | Preferred leads rate (offline) (%) | Percentage of preferred leads events out of all click events attributed to your TikTok ads. |
| Find location | | |
#| offline_total_find_location | Total find location (offline) | The number of offline find location events attributed to your TikTok ads. |
#| offline_cost_per_find_location | Cost per find location (offline) | The average cost of each offline find location event attributed to your TikTok ads. |
#| offline_find_location_rate | Find location rate (offline) | The percentage of offline find location events out of all click events attributed to your TikTok ads. |
#| offline_value_per_find_location | Value per find location (offline) | The average value returned offline from each find location event attributed to your TikTok ads. |
#| offline_total_find_location_value | Total find location value (offline) | The total value returned from all offline find location events attributed to your TikTok ads. |
| Schedule | | |
#| offline_total_schedule | Total schedule (offline) | The number of offline schedule events attributed to your TikTok ads. |
#| offline_cost_per_schedule | Cost per schedule (offline) | The average cost of each offline schedule event attributed to your TikTok ads. |
#| offline_schedule_rate | Schedule rate (offline) | The percentage of offline schedule events out of all click events attributed to your TikTok ads. |
#| offline_value_per_schedule | Value per schedule (offline) | The average value returned from each offline schedule event attributed to your TikTok ads. |
#| offline_total_schedule_value | Total schedule value (offline) | The total value returned from all offline schedule events attributed to your TikTok ads. |
| Start trial | | |
#| offline_total_start_trial | Start trial count (offline) | Number of offline start trial actions attributed to your ads. |
#| offline_cost_per_start_trial | Start trial rate (offline) | Percentage of offline start trial actions from all clicks attributed to your ads. |
#| offline_start_trial_rate | Cost per start trial (offline) | Average cost per offline start trial action attributed to your ads. |
#| offline_value_per_start_trial | Value per start trial (offline) | Average value per offline start trial action attributed to your ads. |
#| offline_total_start_trial_value | Total start trial value (offline) | Total value of offline start trial actions attributed to your ads. |
| Submit application | | |
#| offline_total_submit_application | Submit application count (offline) | Number of offline submit application actions attributed to your ads. |
#| offline_cost_per_submit_application | Cost per submit application (offline) | Average cost per offline submit application action attributed to your ads. |
#| offline_submit_application_rate | Submit application rate (offline) | Percentage of offline submit application actions from all clicks attributed to your ads. |
#| offline_value_per_submit_application | Value per submit application (offline) | Average value per offline submit application action attributed to your ads. |
#| offline_total_submit_application_value | Total submit application value (offline) | Total value of offline submit application actions attributed to your ads. |
| Application approval | | |
#| offline_application_approval | Application approvals (offline) | Number of offline application approval actions attributed to your ads. |
#| offline_cost_per_application_approval | Cost per application approval (offline) | Average cost per offline application approval action attributed to your ads. |
#| offline_application_approval_rate | Application approval rate (offline) | Percentage of offline application approval actions from all clicks attributed to your ads. |
#| offline_value_per_application_approval | Value per application approval (offline) | Average value per offline application approval action attributed to your ads. |
#| offline_total_application_approval_value | Total application approval value (offline) | Total value of offline application approval actions attributed to your ads. |
| Customize product | | |
#| offline_total_customize_product | Total customize product (offline) | The number of offline customize product events attributed to your TikTok ads. |
#| offline_cost_per_customize_product | Cost per customize product (offline) | The average cost of each offline customize product event attributed to your TikTok ads. |
#| offline_customize_product_rate | Customize product rate (offline) | The percentage of offline customize product events out of all click events attributed to your TikTok ads. |
#| offline_value_per_customize_product | Value per customize product (offline) | The average value returned from each offline customize product event attributed to your TikTok ads. |
#| offline_total_customize_product_value | Total customize product value (offline) | The total value returned from all offline customize product events attributed to your TikTok ads. |
| Custom event | | |
#| offline_total_custom_events | Custom events (offline) | Number of custom offline events attributed to your ads. |
#| offline_cost_per_custom_event | Cost per custom event (offline) | Average cost per custom offline event attributed to your ads. |
#| offline_custom_event_rate | Custom event rate (offline) (%) | Percentage of custom offline events from all clicks attributed to your ads. |
#| offline_value_per_custom_event | Value per custom event (offline) | Average value per custom offline event attributed to your ads. |
#| offline_total_custom_event_value | Custom event value (offline) | Total value of custom offline events attributed to your ads. |
````

## Messaging metrics
Messaging metrics measure conversations initiated through TikTok or instant messaging apps as a result of ads.

````xtable
| Field {41%}| Description {24%}| Detail {35%}|
|---|---|---|
| Conversations (TikTok direct message) | | |
#| messaging_total_conversation_tiktok_direct_message | Conversations (TikTok direct message) | Number of TikTok direct message conversations attributed to ads running on a TikTok-owned property. |
#| messaging_cost_per_conversation_tiktok_direct_message | Cost per conversation (TikTok direct message) | Average cost of each TikTok direct message conversation event attributed to ads running on a TikTok-owned property. |
#| messaging_conversation_rate_tiktok_direct_message | Conversation rate (TikTok direct message) (%) | Percentage of TikTok direct message conversations that occurred within a TikTok-owned property (out of all click events). |
| Conversations (Instant messaging app) | | |
#| messaging_total_conversation_instant_messaging_app | Conversations (Instant messaging app) | Number of instant messaging app conversation started events attributed to your TikTok ads. |
#| messaging_cost_per_conversation_instant_messaging_app | Cost per conversation (Instant messaging app) | Average cost per instant messaging app conversation started event attributed to your TikTok ads. |
#| messaging_conversation_rate_instant_messaging_app | Conversation rate (Instant messaging app) (%) | Percentage of instant messaging app conversation started out of all click events attributed to your TikTok ads. |
````

## Shop metrics
Shop metrics measure conversion actions made through TikTok Shop.

````xtable
| Field {34%}| Description {22%}| Detail {44%}|
|---|---|---|
| ROAS | | |
#| onsite_shopping_roas | ROAS (Shop) | The return on ad spend (ROAS) from TikTok Shop gross revenue attributed to your ads. |
| Purchase | | |
#| onsite_shopping | Purchases (Shop) | Number of Shop order submission actions attributed to your ads. |
#| cost_per_onsite_shopping | Cost per purchase (Shop) | Average cost per Shop purchase attributed to your ads. |
#| onsite_shopping_rate | Purchase rate (Shop) | Percentage of Shop purchases from clicks on your ads. |
#| value_per_onsite_shopping | Average order value (Shop) | Average order value per Shop purchase attributed to your ads. |
#| total_onsite_shopping_value | Gross revenue (Shop) | Gross revenue of Shop orders attributed to your ads. It's the amount people pay, less any sales taxes, plus any product discounts provided to them by Shop platform. |
| Purchases by order submission time | | |
#| shop_total_purchase_by_order_submission | Purchases (Shop) by order submission time | The number of Shop order submission actions attributed to your ads. |
#| shop_gross_revenue_by_order_submission | Gross revenue (Shop) by order submission time | The gross revenue of Shop orders attributed to your ads. It's the amount people pay, less any sales taxes, plus any product discounts provided to them by Shop platform. |
| Items purchased | | |
#| shop_total_items_purchased | Items purchased (Shop) | The number of Shop items sold attributed to your ads. |
| Checkouts initiated | | |
#| onsite_initiate_checkout_count | Checkouts initiated (Shop) | Number of initiated checkout actions in Shop attributed to your ads, such as clicks on Buy now and Buy with coupon buttons on your product pages. |
#| cost_per_onsite_initiate_checkout_count | Cost per checkout initiated (Shop) | Average cost per Shop initiated checkout action attributed to your ads. |
#| onsite_initiate_checkout_count_rate | Checkout initiation rate (Shop) | Percentage of Shop initiated checkout actions from clicks on your ads. |
#| value_per_onsite_initiate_checkout_count | Value per checkout initiated (Shop) | Average value per Shop initiated checkout action attributed to your ads. |
#| total_onsite_initiate_checkout_count_value | Checkout initiation value (Shop) | Total value of Shop initiated checkout actions attributed to your ads. |
| Product page views | | |
#| onsite_on_web_detail | Product page views (Shop) | Number of Shop product detail page views attributed to your ads. |
#| cost_per_onsite_on_web_detail | Cost per product page view (Shop) | Average cost per Shop product detail page view attributed to your ads. |
#| onsite_on_web_detail_rate | Product page view rate (Shop) (%) | Percentage of Shop product detail page views from clicks on your ads. |
#| value_per_onsite_on_web_detail | Value per product page view (Shop) | Average value per Shop product detail page view attributed to your ads. |
#| total_onsite_on_web_detail_value | Product page view value (Shop) | Total value of Shop product detail page views attributed to your ads. |
| Add to cart | | |
#| onsite_on_web_cart | Add to cart (Shop) | Number of Shop add to cart actions attributed to your ads. |
#| cost_per_onsite_on_web_cart | Cost per add to cart (Shop) | Average cost per Shop add to cart action attributed to your ads. |
#| onsite_on_web_cart_rate | Add to cart rate (Shop) (%) | Percentage of Shop add to cart actions from clicks on your ads. |
#| value_per_onsite_on_web_cart | Value per add to cart (Shop) | Average value per Shop add to cart action attributed to your ads. |
#| total_onsite_on_web_cart_value | Add to cart value (Shop) | Total value of Shop add to cart actions attributed to your ads. |
````

## Attribution metrics
Attribution metrics measure events attributed to ad impressions, clicks, or focused views (engaged views).

````xtable
| Field {34%}| Description {22%}| Detail {44%}|
|---|---|---|
| Conversion | | |
#| vta_conversion | VTA conversions | The number of conversion events attributed to an ad impression. |
#| cost_per_vta_conversion | Cost per VTA conversion | The average cost per each conversion event attributed to an ad impression. |
#| cta_conversion | CTA conversions | The number of conversion events attributed to a click on your ad. |
#| cost_per_cta_conversion | Cost per CTA conversion | The average cost per each conversion event attributed to a click on your ad. |
#| engaged_view_through_conversions | EVTA conversions | Number of conversions attributed to engaged views. |
#| cost_per_engaged_view_through_conversion | Cost per EVTA conversion | Average cost per conversion attributed to an engaged view.The average cost of each conversion that is attributed to an engaged view. |
| App install | | |
#| vta_app_install | VTA app installs | The number of app install events attributed to an ad impression. |
#| cost_per_vta_app_install | Cost per VTA app install | The average cost per each app install event attributed to an ad impression. |
#| cta_app_install | CTA app installs | The number of app install events attributed to a click on your ad. |
#| cost_per_cta_app_install | Cost per CTA app install | The average cost per each app install event attributed to a click on your ad. |
#| evta_app_install | EVTA app installs | The number of app install actions attributed to engaged views of your ad.<br><br><p><span style="color:darkred"><b>Note</b></span>: The EVTA metrics can only be used with the following dimensions:<ul><li>`advertiser_id`</li><li>`campaign_id`</li><li>`adgroup_id`</li><li>`ad_id`</li><li>`stat_time_day`</li></ul> |
#| cost_per_evta_app_install | Cost per EVTA app install | The average cost per app install action attributed to engaged views of your ad. |
| Registration | | |
#| vta_registration | VTA registrations | Number of registration events attributed to an ad impression based on information received from your measurement partner. |
#| cost_per_vta_registration | Cost per VTA registration | Average cost per registration event attributed to an ad impression. |
#| cta_registration | CTA registrations | Number of in-app registration events attributed to a click on your ad based on information received from your measurement partner. |
#| cost_per_cta_registration | Cost per CTA registration | Average cost for an in-app registration event attributed to a click on your ad. |
#| evta_registration | EVTA registrations | The number of registration actions attributed to engaged views of your ad. |
#| cost_per_evta_registration | Cost per EVTA registration | The average cost per registration action attributed to engaged views of your ad. |
| Purchase (app) | | |
#| vta_purchase | VTA purchases (app) | Number of in-app purchase events attributed to an ad impression based on information received from your measurement partner. |
#| cost_per_vta_purchase | Cost per VTA purchase (app) | Average cost per in-app purchase event attributed to an ad impression. |
#| cta_purchase | CTA purchases (app) | Number of in-app purchase events attributed to clicks on your ad based on information received from your measurement partner. |
#| cost_per_cta_purchase | Cost per CTA purchase (app) | Average cost per in-app purchase event attributed to a click on your ad. |
#| evta_purchase | EVTA purchases (app) | The number of in-app purchase actions attributed to engaged views of your ad. |
#| cost_per_evta_purchase | Cost per EVTA purchase (app) | The average cost per in-app purchase action attributed to engaged views of your ad. |
| Purchase (website) | | |
#| vta_complete_payment | VTA purchases (website) | Website purchase events attributed to someone only viewing your ad. |
#| cost_per_vta_payments_completed | Cost per VTA purchase (website) | Average cost of each website purchase event attributed to someone only viewing your ad. |
#| evta_payments_completed | EVTA purchases (website) | The number of website purchase actions attributed to engaged views of your ad. |
#| cost_per_evta_payments_completed | Cost per EVTA purchase (website) | The average cost per website purchase action attributed to engaged views of your ad. |
#| website_cta_purchase | CTA purchases (website) | Purchase events attributed to someone clicking on your ad. |
#| website_cost_per_cta_purchase | Cost per CTA purchase (website) | Average cost of each purchase event attributed to someone clicking on your ad. |
| Purchase value (website) | | |
#| vta_complete_payment_value | VTA purchase value (website) | Total value of purchase events attributed to someone only viewing your ad. |
#| website_cta_purchase_value | CTA purchase value (website) | Total value of purchase events attributed to someone clicking on your ad. |
| Purchase ROAS (website) | | |
#| vta_complete_payment_roas | VTA purchase ROAS (website) | Return on ad spend (ROAS) from purchase events attributed to someone only viewing your ad. |
#| website_cta_purchase_roas | CTA purchase ROAS (website) | Return on ad spend (ROAS) from purchase events attributed to someone clicking on your ad. |
| Conversion (SKAN) | | |
#| skan_vta_conversion | VTA conversions (SKAN) | Number of conversion events attributed to ad impressions. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_vta_conversion | Cost per VTA conversion (SKAN) | Average cost per conversion event attributed to an ad impression. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cta_conversion | CTA conversions (SKAN) | Number of in-app conversion events attributed to ad impressions. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_cta_conversion | Cost per CTA conversion (SKAN) | Average cost per in-app conversion event attributed to a click on your ad. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| App install (SKAN) | | |
#| skan_vta_app_install | VTA app installs (SKAN) | Number of app install events attributed to ad impressions. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_vta_app_install | Cost per VTA app install (SKAN) | Average cost per app install event attributed to an ad impression. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cta_app_install | CTA app installs (SKAN) | Number of app install events attributed to clicks on your ad. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_cta_app_install | Cost per CTA app install (SKAN) | Average cost per app install event attributed to a click on your ad. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Registration (SKAN) | | |
#| skan_vta_registration | VTA registrations (SKAN) | Number of in-app registration events attributed to an ad impression. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_vta_registration | Cost per VTA registration (SKAN) | Average cost per in-app registration event attributed to an ad impression. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cta_registration | CTA registrations (SKAN) | Number of in-app registration events attributed to a click on your ad. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_cta_registration | Cost per CTA registration (SKAN) | Average cost per in-app registration event attributed to a click on your ad. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
| Purchase (SKAN) | | |
#| skan_vta_purchase | VTA purchases (SKAN) | Number of in-app purchase events attributed to ad impressions. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_vta_purchase | Cost per VTA purchase (SKAN) | Average cost per in-app purchase event attributed to an ad impression. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cta_purchase | CTA purchases (SKAN) | Number of in-app purchase events attributed to clicks on your ad. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
#| skan_cost_per_cta_purchase | Cost per CTA purchase (SKAN) | Average cost per in-app purchase event attributed to a click on your ad. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
````

