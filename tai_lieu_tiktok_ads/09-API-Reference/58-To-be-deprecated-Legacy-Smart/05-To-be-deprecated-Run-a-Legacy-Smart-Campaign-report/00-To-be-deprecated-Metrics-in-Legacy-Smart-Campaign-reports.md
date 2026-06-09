---
title: "(To be deprecated) Metrics in Legacy Smart+ Campaign reports"
doc_id: 1776461365424129
path: "API Reference / (To be deprecated) Legacy Smart+ / (To be deprecated) Run a Legacy Smart+ Campaign report / (To be deprecated) Metrics in Legacy Smart+ Campaign reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To be deprecated) Metrics in Legacy Smart+ Campaign reports

Below are the supported metrics for Smart+ Campaign reports.

## Basic data
```xtable
| Metric {20%} |  Type{15%}  |  Description {15%} |Detail  {50%} |
|----------|------|-------------|--------------|
| spend | string | Total cost | The estimated total amount of money you've spent on your campaign, ad group or ad during its schedule.  |
| cpc | string | CPC (Destination) | The average amount of money to a specified destination costs you. |
| cpm | string | CPM | The average amount of money you've spent per 1,000 impressions. |
| impressions | string | Impressions | The number of times your ads were on screen. |
| clicks | string | Clicks (Destination) | The number of clicks from your ads that lead to specified destinations. |
| ctr | string | CTR (Destination) | The percentage of times people click to a specified destination after being shown your ad. |
| conversion | string | Conversions | The number of times your ad achieved an outcome, based on the objective and settings you selected. <br> The total count is calculated based on the time each ad impression occurred. |
| cost_per_conversion | string | CPA | The average amount of money you've spent on a conversion.  <br>The total count is calculated based on the time each ad impression occurred. |
| conversion_rate | string | CVR (Clicks) | The percentage of conversions from all clicks on your ads. The metric will be removed soon. <br>The total count is calculated based on the time each ad impression occurred. |
| conversion_rate_v2 | string | CVR (Impressions) | The percentage of conversions from all impressions on your ads.  <br>The total count is calculated based on the time each ad impression occurred. |
| real_time_conversion | string | Real-time Conversions | The number of times your ad achieved an outcome based on the objective and settings you selected. <br> The total count is based on when the conversion actually happened. |
| real_time_cost_per_conversion | string | Real-time CPA | The average amount of money you've spent on a conversion.  <br>The total count is based on when the conversion actually happened. |
| real_time_conversion_rate | string | Real-time CVR (Clicks) | The percentage of conversions from all clicks on your ads. This metric will be removed soon . <br>The total count is based on when the conversion actually happened. |
| real_time_conversion_rate_v2 | string | Real-time CVR (Impressions) | The percentage of conversions from all impressions on your ads. <br>The total count is based on when the conversion actually happened. |
| secondary_goal_result | string | Secondary goal result | The number of times your ad achieved an outcome, based on the secondary goal you selected. As one campaign may have a number of different secondary goals, this statistic is not supported for campaigns. Please go to ad groups or ads to view.  <br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_secondary_goal_result | string | Cost per secondary goal result | The average cost for each secondary goal result from your adverts. As one campaign may have a number of different secondary goals, this statistic is not supported for campaigns. Please go to ad groups or ads to view.  <br>The total count is calculated based on the time each ad impression occurred. |
| secondary_goal_result_rate | string | Secondary goal result rate | The percentage of secondary goal results you achieved out of all of the installs of your adverts. As one campaign may have a number of different secondary goals, this statistic is not supported for campaigns. Please go to ad groups or ads to view.  <br>The total count is calculated based on the time each ad impression occurred. |
| skan_conversion | string | Conversions (SKAN) | The number of times your ad achieved an outcome based on the objective and settings you selected. <br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_conversion | string | CPA (SKAN) | The average amount of money you've spent on a conversion. <br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_conversion_rate | string | CVR (SKAN, clicks) | The percentage of conversions from all clicks of your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. This metric will be removed soon. <br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. <br><span style="color:darkred"><b>Note</b></span>:<br> Starting late October, 2023, the calculation logic for this metric will be updated to be impression-based (the same as `skan_conversion_rate_v2`). To ensure a smooth API integration and avoid disruptions caused by the change in calculation logic, we recommend you switch to using the impression-based metric `skan_conversion_rate_v2` as soon as possible. |
| skan_conversion_rate_v2 | string | CVR (SKAN, impressions) | The percentage of conversions from all impressions on your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. <br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
```
## Video play
```xtable
| Metric {20%} |  Type{15%}  |  Description {15%} |Detail  {50%} |
|----------|------|-------------|--------------|
| video_play_actions | string | Video Views | The number of times someone started playing your video. Replays will not be counted. |
| video_watched_2s |  string | 2-second video Views | The number of times your video played for at least 2 seconds. Replays will not be counted. |
| video_watched_6s | string | 6-second video Views | The number of times your video played for at least 6 seconds, or played in full. Replays will not be counted. |
| video_views_p100 | string | Video Views at 100% | The number of times your video was played at 100% of its length. Replays will not be counted. |
| video_views_p75 | string | Video Views at 75% | The number of times your video was played at 75% of its duration. Replays will not be counted. |
| video_views_p50 | string | Video Views at 50% | The number of times your video was played at 50% of its duration. Replays will not be counted. Data from Pangle is currently not supported.  |
| video_views_p25 | string | Video Views at 25% | The number of times your video was played at 25% of its duration. Replays will not be counted. Data from Pangle is currently not supported.  |
| average_video_play | string | Average Watch Time per Video View | The average time your video was played per single video view, including any time spent replaying the video. |
```

## Interaction

```xtable
| Metric {20%} |  Type{15%}  |  Description {15%} |Detail {50%} |
|----------|------|-------------|--------------|
| follows | string | Paid followers | The number of new followers that were gained within 1 day of a user seeing a paid ad. <br>This metric is only for Spark Ads. |
| likes | string | Paid likes | The number of likes your video creative received within 1 day of a user seeing a paid ad. |
| comments | string | Paid comments | The number of comments your video creative received within 1 day of a user seeing a paid ad. |
| shares | string | Paid shares | The number of times your video creative was shared within 1 day of a user seeing a paid ad. |
| profile_visits | string | Paid profile visits | The number of profile visits the paid ad drove during the campaign. <br>This metric is only for Spark Ads. |
| profile_visits_rate | string | Paid profile visit rate | The rate of profile visits per impression the paid ad drove during the campaign. <br>This metric is only for Spark Ads. |
```

## In-app event - Statistic Type: Every
```xtable
| Metric {20%} |  Type{15%}  |  Description {15%} |Detail {50%} |
|----------|------|-------------|--------------|
| total_registration | string | Total registration | The number of registration events that are attributed to your ads. <br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_total_registration | string | Cost per registration | The average cost of each registration event. <br>The total count is calculated based on the time each ad impression occurred. |
| total_purchase | string | Total purchase | The number of purchase events that are attributed to your ads. <br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_total_purchase | string | Cost per purchase | The average cost for each purchase event. <br>The total count is calculated based on the time each ad impression occurred. |
| value_per_total_purchase | string | Value per purchase | The avarage value returned from each purchase event. <br>The total count is calculated based on the time each ad impression occurred. |
| total_purchase_value | string | Total purchase value | The total value returned from all purchase events. <br>The total count is calculated based on the time each ad impression occurred. |
| total_active_pay_roas | string | Purchase ROAS | The total return on ad spend (ROAS) from purchase events. <br>The total count is calculated based on the time each ad impression occurred. |
| total_app_event_add_to_cart | string | Total add-to-cart | The number of add to cart events that are attributed to your ads. <br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_total_app_event_add_to_cart | string | Cost per add-to-cart | The average cost for each ad to cart event. <br>The total count is calculated based on the time each ad impression occurred. |
| value_per_total_app_event_add_to_cart | string | Value per add-to-cart | The average value returned from each add-to-cart event. <br>The total count is calculated based on the time each ad impression occurred. |
| total_app_event_add_to_cart_value | string | Total add-to-cart value | The total value returned from all add to cart events. <br>The total count is calculated based on the time each ad impression occurred. |
| total_checkout | string | Total checkout | The number of checkout events that are attributed to your ads. <br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_total_checkout | string | Cost per checkout | The average cost for each checkout event. <br>The total count is calculated based on the time each ad impression occurred. |
| value_per_checkout | string | Value per checkout | The average value returned from each checkout event. <br>The total count is calculated based on the time each ad impression occurred. |
| total_checkout_value | string | Total checkout value | The total value returned from all checkout events. <br>The total count is calculated based on the time each ad impression occurred. |
| total_view_content | string | Total view content | The number of view content events that are attributed to your ads. <br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_total_view_content | string | Cost per view content | The average cost for each view content. <br>The total count is calculated based on the time each ad impression occurred. |
| value_per_total_view_content | string | Value per view content | The average value returned from each view content. <br>The total count is calculated based on the time each ad impression occurred. |
| total_view_content_value | string | Total view view content  | The total value returned from all view content events. <br>The total count is calculated based on the time each ad impression occurred. |
| total_next_day_open | string | Total day 1 retention | The number of day 2 retention events that are attributed to your ads. <br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_total_next_day_open | string | Cost per day 1 retention | The average cost for each day 2 retention events. <br>The total count is calculated based on the time each ad impression occurred. |
| total_launch_app | string | Total launch app | The total of launch app events that are attributed to your ads. <br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_total_launch_app | string | Cost per launch app | The average cost for each launch app event. <br>The total count is calculated based on the time each ad impression occurred. |
| total_complete_tutorial | string | Total complete tutorial | The number of complete tutorial events that are attributed to your ads. <br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_total_complete_tutorial | string | Cost per complete tutorial | The average cost for each complete tutorial event. <br>The total count is calculated based on the time each ad impression occurred. |
| value_per_total_complete_tutorial | string | Value per complete tutorial | The average value returned from each complete tutorial event. <br>The total count is calculated based on the time each ad impression occurred. |
| total_complete_tutorial_value | string | Total complete tutorial value | The total value returned from all complete tutorial events. <br>The total count is calculated based on the time each ad impression occurred. |
| total_achieve_level | string | Total achieve level | The number of achieve level events that are attributed to your ads. <br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_total_achieve_level | string | Cost per achieve level | The average cost for each achieve level event. <br>The total count is calculated based on the time each ad impression occurred. |
| value_per_total_achieve_level | string | Value per achieve level | The average value returned from each achieve level event. <br>The total count is calculated based on the time each ad impression occurred. |
| total_achieve_level_value | string | Total achieve level value | The total value returned from all achieve level events. <br>The total count is calculated based on the time each ad impression occurred. |
```

## In-app event - Statistic Type: Once
```xtable
| Metric {20%} |  Type{15%}  |  Description {15%} |Detail {50%} |
|----------|------|-------------|--------------|
| real_time_app_install | string | Real-time app installs | The number of app install events that are attributed to your ads. <br>The total count is based on when the conversion actually happened. |
| real_time_app_install_cost | string | Cost per real-time app install | The average cost for each app install event. <br>The total count is based on when the conversion actually happened. |
| app_install | string | App install | The number of app install events that are attributed to your ads. <br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_app_install | string | Cost per app install | The average cost for each app install event. <br>The total count is calculated based on the time each ad impression occurred. |
| registration | string | Unique registration | The number of unique registration events that are attributed to your ads.<br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_registration | string | Cost per unique registration | The average cost for each registration that is attributed to your ad.<br>The total count is calculated based on the time each ad impression occurred. |
| registration_rate | string | Unique registration rate (%) | The percentage of registration events out of all app install events.<br>The total count is calculated based on the time each ad impression occurred. |
| purchase | string | Unique purchase | The number of unique purchase events that are attributed to your ads.<br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_purchase | string | Cost per unique purchase | The average cost for each unique purchase that is attributed to your ad.<br>The total count is calculated based on the time each ad impression occurred. |
| purchase_rate | string | Unique purchase rate (%) | The percentage of purchase events out of all app install events.<br>The total count is calculated based on the time each ad impression occurred. |
| app_event_add_to_cart | string | Unique add-to-cart | The number of unique add to cart events that are attributed to your ads.<br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_app_event_add_to_cart | string | Cost per unique add-to-cart | The average cost of each unique add to cart event.<br>The total count is calculated based on the time each ad impression occurred. |
| app_event_add_to_cart_rate | string | Unique add-to-cart rate (%) | The percentage of unique add to cart events out of all app install events.<br>The total count is calculated based on the time each ad impression occurred. |
| checkout | string | Unique checkout | The number of unique checkout events that are attributed to your ads.<br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_checkout | string | Cost per unique checkout | The average cost of each unique checkout event.<br>The total count is calculated based on the time each ad impression occurred. |
| checkout_rate | string | Unique checkout rate (%) | The percentage of unique checkout events out of all app install events.<br>The total count is calculated based on the time each ad impression occurred. |
| view_content | string | Unique view content | The number of unique view content events that are attributed to your ads.<br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_view_content | string | Cost per unique view content | The average cost of each unique view content event.<br>The total count is calculated based on the time each ad impression occurred. |
| view_content_rate | string | Unique view content rate (%) | The percentage of unique checkout events out of all app install events.<br>The total count is calculated based on the time each ad impression occurred. |
| next_day_open | string | Day 1 retention | The number of unique day 2 retention events that are attributed to your ads.<br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_next_day_open | string | Cost per unique day 1 retention | The average cost of each unique day 2 retention event.<br>The total count is calculated based on the time each ad impression occurred. |
| next_day_open_rate | string | Unique day 1 retention rate (%) | The percentage of unique day 2 retention events out of all app install events.<br>The total count is calculated based on the time each ad impression occurred. |
| launch_app | string | Unique launch app | The number of unique launch app events that are attributed to your ads.<br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_launch_app | string | Cost per unique launch app | The average cost of each unique launch app event.<br>The total count is calculated based on the time each ad impression occurred. |
| launch_app_rate | string | Unique launch app rate (%) | The percentage of unique launch app events out of all app install events.<br>The total count is calculated based on the time each ad impression occurred. |
| complete_tutorial | string | Unique complete tutorial | The number of unique complete tutorial events that are attributed to your ads.<br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_complete_tutorial | string | Cost per unique complete tutorial | The average cost of each unique complete tutorial event.<br>The total count is calculated based on the time each ad impression occurred. |
| complete_tutorial_rate | string | Unique complete tutorial rate (%) | The percentage of unique complete tutorial events out of all app install events.<br>The total count is calculated based on the time each ad impression occurred. |
| achieve_level | string | Unique achieve level | The number of unique achieve level events that are attributed to your ads.<br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_achieve_level | string | Cost per unique achieve level | The average cost of each unique achieve level event.<br>The total count is calculated based on the time each ad impression occurred. |
| achieve_level_rate | string | Unique achieve level rate (%) | The percentage of unique achieve level events out of all app install events.<br>The total count is calculated based on the time each ad impression occurred. |
```

## In-app event (SKAN)
```xtable
| Metric {20%} |  Type{15%}  |  Description {15%} |Detail {50%} |
|----------|------|-------------|--------------|
| skan_app_install | string | App Install (SKAN) | The number of unique app install events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_app_install | string | Cost per App Install (SKAN) | The average cost of each unique app install event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_app_install_withheld | string | App installs (SKAN Privacy Withheld) | The number of unique app install events that are attributed to your ads, with withheld values due to Apple's privacy threshold. Data may be incomplete due to SKAdNetwork (SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_registration | string | Unique registration (SKAN) | The number of unique registration events that are attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_registration | string | Cost per unique registration (SKAN) | The average cost of each unique registration event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_registration_rate | string | Unique registration rate (SKAN) | The percentage of unique registration events out of all app install events. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_total_registration | string | Total registration (SKAN) | The number of registration events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_total_registration | string | Cost per registration (SKAN) | The average cost for each registration event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_purchase | string | Unique Purchase (SKAN) | The number of unique purchase events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_purchase | string | Cost per unique purchase (SKAN) | The average cost of each unique purchase event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_purchase_rate | string | Unique purchase rate (SKAN) | The percentage of unique purchase events out of all app install events. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_total_purchase | string | Total purchase (SKAN) | The number of purchase events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_total_purchase | string | Cost per purchase (SKAN) | The average cost for each purchase event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_total_purchase_value | string | Total Purchase Value (SKAN) | The total value returned from all purchase events. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_total_repetitive_active_pay_roas | string | Purchase ROAS (SKAN) | The total return on ad spend (ROAS) from purchase events. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_add_to_cart | string | Unique add-to-cart (SKAN) | The number of unique add to cart events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_add_to_cart | string | Cost per unique add-to-cart (SKAN) | The average cost of each unique add to cart event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_add_to_cart_rate | string | Unique add-to-cart rate (SKAN) | The percentage of unique checkout events out of all app install events. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_total_add_to_cart | string | Total add-to-cart (SKAN) | The number of add to cart events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_total_add_to_cart | string | Cost per add-to-cart (SKAN) | The average cost for each add to cart event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_total_add_to_cart_value | string | Total add-to-cart value (SKAN) | The total value returned from all add to cart events. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_checkout | string | Unique checkout (SKAN) | The number of unique checkout events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_checkout | string | Cost per unique checkout (SKAN) | The average cost of each unique checkout event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_checkout_rate | string | Unique checkout rate (SKAN) | The percentage of unique checkout events out of all app install events. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_total_checkout | string | Total checkout (SKAN) | The number of checkout events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_total_checkout | string | Cost per checkout (SKAN) | The average cost for each checkout event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_total_checkout_value | string | Total checkout value (SKAN) | The total value returned from all checkout events. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_view_content | string | Unique view content (SKAN) | The number of unique view content events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_view_content | string | Cost per unique view content (SKAN) | The average cost of each unique view content event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_view_content_rate | string | Unique view content rate (SKAN) | The percentage of unique view content events out of all app install events. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_total_view_content | string | Total view content (SKAN) | The number of view content events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_total_view_content | string | Cost per view content (SKAN) | The average cost for each view content event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_launch_app | string | Unique launch app (SKAN) | The number of unique launch app events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_launch_app | string | Cost per unique launch app (SKAN) | The average cost of each unique launch app event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_launch_app_rate | string | Unique launch app rate (SKAN) | The percentage of unique launch app events out of all app install events. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_total_launch_app | string | Total launch app (SKAN) | The number of launch app events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_total_launch_app | string | Cost per launch app (SKAN) | The average cost for each launch app event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_complete_tutorial | string | Unique complete tutorial (SKAN) | The number of unique complete tutorial events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_complete_tutorial | string | Cost per unique complete tutorial (SKAN) | The average cost of each unique complete tutorial event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_complete_tutorial_rate | string | Unique complete tutorial rate (SKAN) | The percentage of unique complete tutorial events out of all app install events. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_total_complete_tutorial | string | Total complete tutorial (SKAN) | The number of complete tutorial events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_total_complete_tutorial | string | Cost per complete tutorial (SKAN) | The average cost for each complete tutorial event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_total_complete_tutorial_value | string | Total complete tutorial value (SKAN) | The total value returned from all complete tutorial events. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_achieve_level | string | Unique achieve level (SKAN) | The number of unique achieve level events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_achieve_level | string | Cost per unique achieve level (SKAN) | The average cost of each unique achieve level event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_achieve_level_rate | string | Unique achieve level rate (SKAN) | The percentage of unique achieve level events out of all app install events. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_total_achieve_level | string | Total achieve level (SKAN) | The number of achieve level events that are attributed to your ads. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_cost_per_total_achieve_level | string | Cost per achieve level (SKAN) | The average cost for each achieve level event. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
| skan_total_achieve_level_value | string | Total achieve level value (SKAN) | The total value returned from all achieve level events. Data may be incomplete due to SKAdNetwork(SKAN) limitations.<br>The total count is based on when the events are returned through the SKAdNetwork API. There may be a delay between the actual conversion time and when the conversion is reported. |
```

## Page event
```xtable
| Metric {20%} | Type{15%} | Description {15%} |Detail {50%} |
|----------|------|-------------|--------------|
| total_landing_page_view | string | Total landing page view | The number of landing page view events from your ads. <br>The total count is calculated based on the time each ad impression occurred. |
| cost_per_landing_page_view | string | Cost per landing page view | The average cost of each landing page view event.<br>The total count is calculated based on the time each ad impression occurred. |
| landing_page_view_rate | string | Landing page view rate | The percentage of click events that are landing page view events.<br>The total count is calculated based on the time each ad impression occurred. |
```

## Date

```xtable
| Metric {20%} | Type{15%} | Description {15%} |Detail {50%} |
|----------|------|-------------|--------------|
| create_time | string | Created on | Time at which the ad was created. Example: `2022-09-06T01:05:12Z` |
| modify_time | string | Modified on | Time at which the ad was modified. Example: `2022-09-06T01:05:12Z` |
```

## Video Material Details

```xtable
| Metric | Type | Description |
|:---:|:---:|:---:|
| video_duration | string | Video Duration |
| video_resolution | object | Video Resolution |
#| height | integer | Height |
#| width | integer  | Width |
| video_format | string | Video Format  |
```


