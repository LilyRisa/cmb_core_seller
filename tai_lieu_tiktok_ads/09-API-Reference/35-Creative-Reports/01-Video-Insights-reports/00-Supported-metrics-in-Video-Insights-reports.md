---
title: "Supported metrics in Video Insights reports"
doc_id: 1789703614085122
path: "API Reference / Creative Reports / Video Insights reports / Supported metrics in Video Insights reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported metrics in Video Insights reports

You can use the following metrics in Video Insights reports. Note that currently, we don't support all Video Insights metrics available on TikTok Ads Manager.

## Basic Data Metrics
Basic data metrics are data about spend and performance of the ads where the videos are used.

```xtable
| Metric Name{25%} | Data Type{10%} | Description{20%} | Detail{45%} |
|---|---|---|---|
| spend | string | Total cost | The estimated total amount of money you've spent on your campaign, ad group, or ad during its schedule. |
| cpc | string | CPC | The average amount of money you've spent on a paid click. |
| cpm | string | CPM | The average amount of money you've spent per 1,000 paid impressions. |
| impressions | string | Impressions | The number of times your ads were on screen. |
| clicks | string | Clicks | The number of clicks on your ads. |
| ctr | string | CTR | The percentage of times people saw your ad and performed a click. |
| conversion | string | Conversions | The number of times your ad achieved an outcome based on the objective and settings you selected. The total count is calculated based on the time each ad impression occurred. |
|  cost_per_conversion| string | CPA | The average amount of money you've spent on a paid conversion. The total count is calculated based on the time each ad impression occurred. |
| conversion_rate_v2 | string | CVR (Impressions) | The percentage of conversions from all impressions on your ads. The total count is calculated based on the time each ad impression occurred. |
| real_time_conversion | string | Real-time Conversions | The number of times your paid ad achieved an outcome based on the objective and settings you selected. |
| real_time_cost_per_conversion | string | Real-time CPA | The average amount of money you've spent on a paid conversion. The total count is based on when the conversion actually happened. |
| real_time_conversion_rate_v2 | string | Real-time CVR (Impressions) | The percentage of conversions from all impressions on your ads. The total count is based on when the conversion actually happened. |
| result | string | Result | The number of times your paid ad achieved an outcome, based on the optimization goal you selected. As one campaign may have a number of different optimization goals, this statistic is not supported for campaigns. Please go to ad groups or ads to view the results. |
| cost_per_result | string | Cost per result | The number of times your paid ad achieved an outcome, based on the optimization goal you selected. As one campaign may have a number of different optimization goals, this statistic is not supported for campaigns. Please go to ad groups or ads to view the results. |
| result_rate | string | Result rate | The percentage of results you achieved out of all of the views/clicks on your ads. As one campaign may have a number of different optimization goals, this statistic is not supported for campaigns. Please go to ad groups or ads to view the result rate. |
| real_time_result | string | Real-time Result | The number of times your paid ad achieved an outcome, based on the optimization goal you selected. As one campaign may have a number of different optimization goals, this statistic is not supported for campaigns. Please go to ad groups or ads to view the results. |
| real_time_cost_per_result | string | Real-time Cost Per Result | The average cost for each result from your ads. As one campaign may have a number of different optimization goals, this statistic is not supported for campaigns. Please go to ad groups or ads to view the cost per result. |
| real_time_result_rate | string | Real-time Result Rate | The percentage of results you achieved out of all of the views/clicks on your ads. As one campaign may have a number of different optimization goals, this statistic is not supported for campaigns. Please go to ad groups or ads to view the result rate. |
| secondary_goal_result | string | Secondary goal result | Number of times your ad resulted in an intended outcome based on the secondary goal you selected. |
| secondary_goal_result_rate | string | Secondary goal result rate | Percentage of secondary goal results that happened out of all impressions on your ads. |
| cost_per_secondary_goal_result | string | Cost per secondary goal result | Average cost per each secondary goal result from your ads. |
| skan_result| string | Result (SKAN) | The number of times your ad achieved an outcome, based on the optimization goal you selected. As one campaign may have a number of different optimization goals, this statistic is not supported for campaigns. Please go to your ad groups or ads to view the results. Data may be incomplete due to SKAdNetwork(SKAN) limitations. |
| skan_cost_per_result | string | Cost Per Result (SKAN) | The average cost for each result from your ads. As one campaign may have a number of different optimization goals, this statistic is not supported for campaigns. Please go to ad groups or ads to view the cost per result. Data may be partial due to SKAdNetwork(SKAN) limitations. |
| skan_result_rate | string | Result Rate (SKAN) | The percentage of results you achieved out of all of the views/clicks on your ads. As one campaign may have a number of different optimization goals, this statistic is not supported for campaigns. Please go to your ad groups or ads to view the result rate. Data may be incomplete due to SKAdNetwork(SKAN) limitations. |
| skan_conversion | string | Conversions (SKAN) | The number of times your ad achieved an outcome based on the objective and settings you selected. Data may be partial due to SKAdNetwork(SKAN) limitations. |
| skan_cost_per_conversion | string | CPA (SKAN) | The average amount of money you've spent on a conversion. Data may be partial due to SKAdNetwork(SKAN) limitations. |
| skan_conversion_rate_v2 | string | CVR (SKAN, impressions) | The percentage of conversions from all impressions on your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. |
```

## Video Play Metrics
Video play metrics are data about video view performance.

```xtable
| Metric Name{25%} | Data Type{10%} | Description{20%} | Detail{45%} |
|---|---|---|---|
| video_play_actions | string | Paid video views | The number of times your video was played via paid traffic. Replays will not be counted. |
| organic_video_views | string | Organic video views | The number of times your video was played from organic traffic, excluding replays. <br>This data becomes available once paid ads run, but stops when the ads remain inactive for more than 7 days. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Only data after Jan 21, 2020 is available.</li><li> Filtering by location (`country_code`) isn't supported when this metric is requested.</li></ul>  |
| video_watched_2s | string | 2-Second Video Views | The number of times your video was played for at least 2 seconds. Replays will not be counted. |
| video_watched_6s | string | 6-Second Video Views | The number of times your video was played for at least 6 seconds, or played in full. Replays will not be counted. |
| video_views_p100 | string | Video Views at 100% | The number of times your video was played at 100% of its length. Replays will not be counted. |
| video_views_p75 | string | Video Views at 75% | The number of times your video was played at 75% of its duration. Replays will not be counted. |
| video_views_p50 | string | Video Views at 50% | The number of times your video was played at 50% of its duration. Replays will not be counted. Data from Pangle is currently not included. |
| video_views_p25 | string | Video Views at 25% | The number of times your video was played at 25% of its duration. Replays will not be counted. |
| average_video_play | string | Average Watch Time per Video View | The average amount of time your video was played per single video view, including any time spent replaying the video. |
```

## Engagement Metrics
Engagement metrics, such as the number of likes and comments, are data about how people interact with your videos.

```xtable
| Metric Name{25%} | Data Type{10%} | Description{20%} | Detail{45%} |
|---|---|---|---|
| follows | string | Paid followers | The number of followers gained from paid traffic within 1 day of being shown this paid ad. Unfollowing may lower this number. This metric is only for Spark Ads. |
| likes | string | Paid likes | The number of likes your video received from people within 1 day of being shown this paid ad. Unliking may lower this number. |
| comments | string | Paid Comments | The number of comments your video creative received within 1 day of a user seeing a paid ad. |
| shares | string | Paid shares | The number of times your video was shared by people within 1 day of being shown this paid ad. |
| profile_visits| string | Paid Profile Visits | The number of profile visits the paid ad drove during the campaign. This metric is only for Spark Ads. |
| profile_visits_rate | string | Paid Profile Visit Rate | The rate of profile visits per impression the paid ad drove during the campaign. This metric is only for Spark Ads. |
| earned_page_video_views | string | Earned page video views | Number of times someone started to play videos in the TikTok page you set after viewing your ad within a single TikTok app session. For each video impression, plays are counted separately and replays are excluded. |
| earned_page_video_views_p100 | string | Earned page video views at 100% | Number of times videos in the TikTok page you set was played 100% of its length after viewing your ad within a single TikTok app session. For each video impression, plays are counted separately and replays are excluded. |
| earned_page_clicks | string | Earned page clicks (all) | Number of clicks on the TikTok page you set after viewing your ad within a single TikTok app session. This includes clicks for social and interaction purposes. |
```
