---
title: "Supported metrics for a dimension in audience reports"
doc_id: 1762404685488130
path: "Marketing API / Reporting / Guides / Report types / Audience reports / Supported dimensions / Supported metrics for a dimension in audience reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported metrics for a dimension in audience reports

See the tables below to learn about the supported metrics when you use a dimension in audience reports. You can find out the detailed introductions of the metrics in [Audience reports-Supported metrics](https://ads.tiktok.com/marketing_api/docs?id=1751454162042882).

## Supported metrics for `country_code`

``` xtable
| Field {35%}	| Description {35%}	| Supported in synchronous audience reports {15%}	| Supported in asynchronous audience reports {15%}	|
|---|---|---|---|
| Basic data metrics 	| 	| 	| |
#|`spend`	| Total Cost 	| ✅ 	| ✅ |
#|`cpc`	| CPC 	| ✅ 	| ✅ |
#|`cpm`	| CPM 	| ✅ 	| ✅ |
#|`impressions`	| Impressions 	| ✅ 	| ✅ |
#|`gross_impressions`	| Gross Impressions (Includes Invalid Impressions) 	| ✅ 	| ✅ |
#|`clicks`	| Clicks 	| ✅ 	| ✅ |
#|`ctr`	| CTR (%) 	| ✅ 	| ✅ |
#|`conversion`	| Conversion 	| ✅ 	| ✅ |
#|`cost_per_conversion`	| CPA 	| ✅ 	| ✅ |
#|`conversion_rate`	| CVR (%) 	| ✅ 	| ✅ |
#|`real_time_conversion`	| Real-time Conversions 	| ✅ 	| ✅ |
#|`real_time_cost_per_conversion`	| Real-time CPA 	| ✅ 	| ✅ |
#|`real_time_conversion_rate`	| Real-time CVR (%) 	| ✅ 	| ✅ |
#|`result`	| Result 	| ✅ 	| ❌ |
#|`cost_per_result`	| Cost Per Result 	| ✅ 	| ❌ |
#|`result_rate`	| Result Rate (%) 	| ✅ 	| ❌ |
#|`real_time_result`	| Real-time Result 	| ✅ 	| ❌ |
#|`real_time_cost_per_result`	| Real-time Cost Per Result 	| ✅ 	| ❌ |
#|`real_time_result_rate`	| Real-time Result Rate (%) 	| ✅ 	| ❌ |
|Video play metrics	| 	| 	| |
#|`video_play_actions`	| Video Views 	| ✅ 	| ✅ |
#|`video_watched_2s`	| 2-Second Video Views 	| ✅ 	| ✅ |
#|`video_watched_6s`	| 6-Second Video Views 	| ✅ 	| ✅ |
#|`average_video_play`	| Video Average Watch Time Per Video View 	| ✅ 	| ✅ |
#|`video_views_p25`	| Video Views at 25% 	| ✅ 	| ✅ |
#|`video_views_p50`	| Video Views at 50% 	| ✅ 	| ✅ |
#|`video_views_p75`	| Video Views at 75% 	| ✅ 	| ✅ |
#|`video_views_p100`	| Video Views at 100% 	| ✅ 	| ✅ |
|Engagement metrics	| 	| 	| |
#|`profile_visits`	| Paid Profile Visit 	| ✅ 	| ✅ |
#|`profile_visits_rate`	| Paid Profile Visit Rate 	| ✅ 	| ✅ |
#|`likes`	| Paid Likes 	| ✅ 	| ✅ |
#|`comments`	| Paid Comments 	| ✅ 	| ✅ |
#|`shares`	| Paid Shares 	| ✅ 	| ✅ |
#|`follows`	| Paid Followers 	| ✅ 	| ✅ |
#|`clicks_on_music_disc`	| Music Clicks 	| ✅ 	| ✅ |
|Page event metrics	|  	| |
#|Landing Page View	| | | |
##|`total_landing_page_view`	| Total Landing Page View 	| ✅ 	| ✅ |
##|`cost_per_landing_page_view`	| Cost per Landing Page View 	| ✅ 	| ✅ |
##|`landing_page_view_rate`	| Landing Page View Rate 	| ✅ 	| ✅ |
```

## Supported metrics for `ad_type`/`hashtag`/`audience_tags`/`purchase_intention`
``` xtable
| Field {50%}	| Description {50%}	| 
|---|---|
|Basic data metrics|  |
#|    `spend`    |     Total cost     |
#|     `cpc`    |     CPC(Destination)    |
#|     `cpm`    |     CPM    |
#|     `impressions`    |     Impressions    |
#|     `clicks`    |     Clicks(Destination)    |
#|     `ctr `   |     CTR(Destination)    |
#|     `conversion`    |     Conversions    |
#|     `cost_per_conversion`    |     CPA    |
#|     `conversion_rate`    |     CVR(Clicks)    |
#|     `conversion_rate_v2`    |     CVR(Impressions)    |
#|     `real_time_conversion`    |     Real-time conversions    |
#|     `real_time_cost_per_conversion`    |     Real-time CPA    |
#|     `real_time_conversion_rate`    |     Real-time CVR(Clicks)    |
#|     `real_time_conversion_rate_v2`    |     Real-time CVR(Impressions)    |
|Video play|  |
#|     `video_play_actions`    |     Video views    |
#|     `video_watched_2s `   |     2-second video views    |
#|    `video_watched_6s`    |     6-second video views    |
#|     `video_views_p100`    |     Video views at 100%    |
#|     `video_views_p75`    |     Video views at 75%    |
#|     `video_views_p50`    |     Video views at 50%    |
#|     `video_views_p25`    |     Video views at 25%    |
#|    `average_video_play`    |     Average watch time per video view    |
#|    `engaged_view`    |     6-second views (Focused view)    |
#|    `engaged_view_15s`    |     15-second views (Focused view)    |
|Engagement metrics|  |
#|     `follows`    |     Paid followers    |
#|    ` likes `   |     Paid likes    |
#|     `comments`    |     Paid comments    |
#|     `shares`    |     Paid share    |
#|     `profile_visits_rate`    |     Paid profile visits    |
#|     `profile_visits`    |     Paid Profile visit rate    |
#|     `clicks_on_music_disc`    |     Music clicks    |
| Page event metrics |  |
#|     `total_landing_page_view`    |     Total Landing Page View    |
#|    ` cost_per_landing_page_view `   |     Cost per Landing Page View    |
#|    ` landing_page_view_rate`    |     Landing Page View Rate    |
```
