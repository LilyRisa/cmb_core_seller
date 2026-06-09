---
title: "(Deprecated) Supported metrics"
doc_id: 1751631257050114
path: "Marketing API / Reporting / Guides / Report types / (Deprecated) Reservation ad reports / (Deprecated) Supported metrics"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Supported metrics

You can use the following metrics in reservation ad reports. Metrics that have no consumption will be returned as `-`. Please note that currently, we don't support all metrics available on Reservation Ad Platform.

# Comparing v1.2 and v1.3 
 The following table outlines the differences **between v1.2 and v1.3 report metrics**. 
 
```xtable
|Changes {30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
| Attribute metrics field data type | `campaign_id`: number<br> `adgroup_id`: number| `campaign_id`: string <br> `adgroup_id`: string|
```


# Attribute metrics
 Attribute metrics, such as ad group names and promotion types, are basic attributes of your campaigns, ad groups or ads. Attribute metrics are valid only when an ID type of dimension is used. 
```xtable
| Field {30%}  | Data Type{10%}  | Description{15%}    | Detail{45%}|
| ---- | ---- | ---- |
| campaign_name   | string | Campaign name | Supported at the Campaign, Ad Group and Ad levels. |
| campaign_id   | string| Campaign ID | Supported at the Ad Group and Ad levels only. |
| objective_type | string| Advertising objective| Supported at the Campaign, Ad Group and Ad levels.|
| adgroup_name   | string | Ad Group name | Supported at the Ad Group and Ad levels only. |
| adgroup_id   | string | Ad Group ID | Supported at the Ad level only. |
| ad_name   | string | Ad name | Supported at the Ad level only. |
```
#  Basic data metrics
Basic data metrics are data about spend and performance of your ads.
```xtable
| Field {25%} | Data Type{10%} | Description {25%} | Detail {40%}|
|---|---|---|---|
| spend | string | Cost | Sum of your total ad spend.<br><br> Not supported at the Ad level. |
| impressions | string | Impressions | Number of times your ads were shown. |
| clicks | string | Clicks (destination) | Number of clicks from your ads to a specified destination. |
| ctr | string | CTR (destination) | Percentage of impressions that resulted in a destination click out of all impressions. |
| reach | string | Reach | Number of unique users who saw your ads at least once. |
| cost_per_1000_reached | string | Cost per 1,000 people reached | Average cost to reach 1,000 unique users. |
| frequency | string | Frequency | The average number of times each user saw your ad over a given time period. |
| currency  | string |  currency | The currency code, e.g. USD. Note that if you want to use currency as metrics,  then the 'dimensions' field in your request must include adgroup_id/ ad_id/campaign_id/advertiser_id.  |
```
# Video play metrics
Video play metrics, such as 2-Second Video Views and Video Views at 50%, are data about video view performance.
```xtable
| Field {25%} | Data Type{10%} | Description {25%} | Detail {40%}|
|---|---|---|---|
| video_play_actions | string | Video views | Number of times your video started to play. For each video impression, plays are counted separately and replays are excluded. |
| video_watched_2s | string | 2-second video views | Number of times your video was played for at least 2 seconds. For each video impression, plays are counted separately and replays are excluded. |
| video_watched_6s | string | 6-second video views | Number of times your video was played for at least 6 seconds. For each video impression, plays are counted separately and replays are excluded. |
| video_views_p25 | string | Video views at 25% | Number of times your video was played at least 25% of its length. For each impression, views are counted separately and replays are excluded. |
| video_views_p50 | string | Video views at 50% | Number of times your video was played at least 50% of its length. For each impression, views are counted separately and replays are excluded. |
| video_views_p75 | string | Video views at 75% | Number of times your video was played at least 75% of its length. For each impression, views are counted separately and replays are excluded. |
| video_views_p100 | string | Video views at 100% | Number of times your video was played 100% of its length. For each impression, views are counted separately and replays are excluded. |
| average_video_play | string | Average play time per video view | Average time your video was played per single video view, including any time spent replaying the video. |
| average_video_play_per_user | string | Average play time per user | Average time your video was played per user, including any time spent replaying the video. |
```

# Engagement metrics
```xtable
| Field {25%} | Data Type{10%} | Description {25%} | Detail {40%}|
|---|---|---|---|
| engagements | string | Clicks (all) | Number of clicks from your ads. This includes clicks that lead to destination as well as those for social and interaction purposes. <br><br> Supported at the Ad group level only. |
| engagement_rate | string | CTR (all) | Percentage of impressions that resulted in a click out of all impressions.<br><br>Supported at the Ad group level only. |
| follows | string | Paid follows | Number of follows to the associated profile during the ad impression.<br><br>Supported at the Ad group level only. |
| likes | string | Paid likes | Number of times your ad was liked during the ad impression.<br><br>Supported at the Ad group level only. |
| comments | string | Paid comments | Number of comments sent on your ad during the ad impression. <br><br> Supported at the Ad group level only. |
| shares | string | Paid shares | Number of shares of your ad during the ad impression.<br><br>Supported at the Ad group level only. |
| profile_visits | string | Paid profile visits | Number of visits to the associated profile during the ad impression. |
| clicks_on_music_disc | string | Sound clicks | Number of clicks on the sound associated with your ad during the ad impression. |
| clicks_on_hashtag_challenge | string | Hashtag clicks | Number of clicks on the hashtag challenge associated with your ad during the ad impression. |
| skip_ad | string | Ad skips | Number of clicks or swipes to skip your ad. |
```
# Additional format metrics
```xtable
| Field {25%} | Data Type{10%} | Description {25%} | Detail {40%}|
|---|---|---|---|
| interact_card_show_cnt | string | Interactive Card Impression   | Interactive Card Impression    |
| interact_card_a_click_cnt   | string | Interactive Card Button A Click    | Interactive Card Button A Click|
| interact_card_b_click_cnt   | string | Interactive Card Button B Click    | Interactive Card Button B Click|
| vote_card_show_cnt| string | Voting Card Impression   | Voting Card Impression    |
| vote_card_a_click_cnt  | string | Voting Card Button A Click    | Voting Card Button A Click|
| vote_card_b_click_cnt  | string | Voting Card Button B Click    | Voting Card Button B Click|
| vote_card_convert_click_cnt | string | Voting Card CTA Button Click  | Voting Card CTA Button Click   |
| redpacket_show_cnt| string | Premium Badge Impression | Premium Badge Impression  |
| redpacket_click_cnt    | string | Premium Badge Click | Premium Badge Click  |
| image_card_show_cnt    | string | Display Card Impression  | Display Card Impression   |
| image_card_click_cnt   | string | Display Card Click  | Display Card Click   |
```

