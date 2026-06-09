---
title: "Supported metrics"
doc_id: 1775747476019266
path: "Marketing API / Reporting / Guides / Report types / Business Center reports / Supported metrics"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported metrics

You can use the following metrics in Business Center reports. 

## Attribute metrics
Attribute metrics provide basic attributes of the ad accounts within the Business Center, including ad account names and time zones. 

```xtable
| Field {25%}| Type{10%}| Description{20%}| Detail{45%}|
|---|---|---|---|
| advertiser_name | string | Ad Account name | The name of each ad account. |
| timezone | string | Time zone | The time zone of each ad account. |
```

## Basic data metrics
Basic data metrics provide data about spend and performance of your ads.

```xtable
| Field {25%}| Type{10%}| Description{20%}| Detail{45%}|
|---|---|---|---|
| currency | string | Currency | The currency of each ad account. |
| spend | string | Cost | The estimated amount of money spent on your ads during the selected time period. Refer to your invoice for the accurate final data. |
| billed_cost | string | Net cost | The amount spent, excluding ad credit/coupons used. |
| cash_spend | string | Cost (Cash) | Each day's cash costs will be updated within 48 hours of the end of that day (UTC-12). |
| voucher_spend | string | Cost (Voucher) | Each day's voucher costs will be updated within 48 hours of the end of that day (UTC-12). |
| cashback_coupon_spend | string | Cost (Cashback) | A cashback coupon returns a set amount of cash balance to you after you spend it. The value of the coupon is added to your account the day after the balance is spent. |
| tax_spend | string | Cost (Tax) | Each day's tax costs will be updated within 48 hours of the end of that day (UTC-12). |
| cpc | string | CPC | Cost per click. The average amount of money you've spent on each click. |
| cpm | string | CPM | Cost per mille. The average amount of money you've spent on each 1,000 impressions. |
| impressions | string | Impression | The number of times your ads were shown to a user. |
| clicks | string | Clicks | The number of clicks on your ads. |
| ctr | string | CTR (Destination) | Click-through rate. The percentage of times a user saw one of your ads and clicked on it. |
| conversion | string | Conversions | The number of times your ad achieved an outcome, based on the objective and settings you selected. |
| cost_per_conversion | string | CPA | The average amount of money you've spent on a conversion. |
| conversion_rate | string | CVR (Conversion rate)| The percentage of results you received out of all the clicks of your ads. |
| reach | string | Reach | The number of unique users who saw your ads at least once. This metric is estimated. |
| real_time_conversion | string | Real-time conversions | The number of times your ad achieved an outcome, based on the objective and settings you selected. The total count is based on when the conversion actually happened. |
| real_time_cost_per_conversion | string | Real-time CPA | The average amount of money you spent on a conversion. The total count is based on when the conversion actually happened. |
| real_time_conversion_rate | string | Real-time CVR | The percentage of results you achieved out of all the clicks on your ads. |
| account_type | string | Account type | The type of your ad account. <br><br>Supported values: <ul><li>`ADS_MANAGER`: Ad account on TikTok Ads Manager.</li><li> `RAP`: Ad account on RAP (Reservation Ads Platform). </li></ul>|
| skan_conversion | string | Conversions (SKAN) | The number of times your ad achieved an outcome based on the objective and settings you selected. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
| skan_cost_per_conversion | string | CPA (SKAN) | The average amount of money you've spent on a conversion. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
| skan_conversion_rate | string | CVR (SKAN) | The percentage of results you received out of all the clicks of your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
```

## In-app events metrics
In-App Event metrics provide data about in-app conversion events for non-Dedicated Campaigns.

```xtable
| Field {25%}| Type{10%}| Description{20%}| Detail{45%}|
|---|---|---|---|
| registration | string | Unique registration | The number of unique registration events that are attributed to your ads. The total count is calculated based on the time each ad impression occurred. |
| cost_per_registration | string | Cost per unique registration | The average cost for each registration that is attributed to your ad. The total count is calculated based on the time each ad impression occurred. |
| registration_rate | string | Unique registration rate | The percentage of in-app registration events out of all app install events. The total count is calculated based on the time each ad impression occurred. |
| purchase | string | Purchase | The number of in-app purchase events that are attributed to your ads. The total count is calculated based on the time each ad impression occurred. |
| cost_per_purchase | string | Cost per purchase | The average cost of each purchase. The total count is calculated based on the time each ad impression occurred. |
| purchase_rate | string | Unique purchase rate | The percentage of in-app purchase events out of all app install events. The total count is calculated based on the time each ad impression occurred. |
| value_per_total_purchase | string | Value per purchase | The average value returned from each in-app purchase event. The total count is calculated based on the time each ad impression occurred. |
| total_purchase_value | string | Total purchase value | The total value returned from all in-app purchase events. The total count is calculated based on the time each ad impression occurred. |
| app_install | string | App install | The number of app install events that are attributed to your ads. The total count is calculated based on the time each ad impression occurred. |
| cost_per_app_install | string | Cost per app install | The average cost for each app install event. The total count is calculated based on the time each ad impression occurred. |
| app_install_rate | string | App install rate | The percentage of app install events out of all clicks on your ads. The total count is calculated based on the time each ad impression occurred. |
| real_time_app_install | string | Real-time app install | The number of app install events that are attributed to your ads. The total count is based on when the conversion actually happened. |
| real_time_app_install_cost | string | Cost per real-time app install | The average cost for each app install event. The total count is based on when the conversion actually happened. |
| real_time_app_install_rate | string | Real-time app install rate | The percentage of app install events out of all clicks on your ads. The total count is based on when the conversion actually happened. |
```


## In-app events (SKAN) metrics
In-App Event metrics provide data about in-app conversion events for Dedicated Campaigns.

```xtable
| Field {25%}| Type{10%}| Description{20%}| Detail{45%}|
|---|---|---|---|
| skan_app_install | string | App install (SKAN) | The number of unique app install events that are attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
| skan_cost_per_app_install | string | Cost per app install (SKAN) | The average cost of each unique app install event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
| skan_registration | string | Unique registration (SKAN) | The number of unique registration events that are attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
| skan_cost_per_registration | string | Cost per unique registration (SKAN) | The average cost of each unique registration event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
| skan_registration_rate | string | Unique registration rate (SKAN) | The percentage of unique registration events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
| skan_total_registration | string | Total registration (SKAN) | The number of registration events that are attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
| skan_cost_per_total_registration | string | Cost per registration (SKAN) | The average cost for each registration event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
| skan_purchase | string | Unique purchase (SKAN) | The number of unique purchase events that are attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
| skan_cost_per_purchase | string | Cost per unique purchase (SKAN) | The average cost of each unique purchase event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
| skan_purchase_rate | string | Unique purchase rate (SKAN) | The percentage of unique purchase events out of all app install events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
| skan_total_purchase | string | Total purchase (SKAN) | The number of purchase events that are attributed to your ads. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
| skan_cost_per_total_purchase | string | Cost per purchase (SKAN) | The average cost for each purchase event. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
| skan_total_purchase_value | string | Total purchase value (SKAN) | The total value returned from all purchase events. Data may be incomplete due to SKAdNetwork (SKAN) limitations. The total count is based on when the events are returned through the SKADNetwork API. There may be a delay between the actual conversion time and when it's reported. |
```

## Video plays metrics
Video play metrics provide key indicators of video view performance, including 2-second video views and video views at 50%.

```xtable
| Field {25%}| Type{10%}| Description{20%}| Detail{45%}|
|---|---|---|---|
| video_watched_2s | string | 2-second video views | The number of times your video was played for at least 2 seconds. Replays aren't counted. |
| video_watched_6s | string | 6-second video views | The number of times your video was played for at least 6 seconds. Replays aren't counted. |
| video_views_p100 | string | Video views at 100% | The number of times your video was played at 100% of its duration. Replays aren't counted. |
| video_views_p75 | string | Video views at 75% | The number of times your video was played at 75% of its duration. Replays aren't counted. |
| video_views_p50 | string | Video views at 50% | The number of times your video was played at 50% of its duration. Replays aren't counted. |
| video_views_p25 | string | Video views at 25% | The number of times your video was played at 25% of its duration. Replays aren't counted. |
```

## Settings metrics
Setting metrics provide basic contact information for the ad accounts in the Business Center.

```xtable
| Field {25%}| Type{10%}| Description{20%}| Detail{45%}|
|---|---|---|---|
| contact_name | string | Contact name | The contact name for the ad account. |
```

