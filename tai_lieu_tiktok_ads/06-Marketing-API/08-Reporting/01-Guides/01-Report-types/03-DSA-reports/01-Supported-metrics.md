---
title: "Supported metrics"
doc_id: 1751625293044737
path: "Marketing API / Reporting / Guides / Report types / DSA reports / Supported metrics"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported metrics

You can use the following metrics in DSA reports. Metrics that have no consumption will be returned as `-`. Please note that currently, we don't support all metrics available on TikTok Ads Manager.

## Comparing v1.2 and v1.3 
 The following table outlines the differences **between v1.2 and v1.3 report metrics**. 
 
```xtable
|Changes {30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|New metric type | / |<strong>In-App Event Metrics</strong><br><strong>Attribution Metrics</strong><br><strong>Page Event Metrics</strong><br><strong>Product metrics</strong>|
| Attribute metrics field name | `placement` |  `placement_type`|
| Attribute metrics field data type | `campaign_id`: number<br> `adgroup_id`: number| `campaign_id`: string <br> `adgroup_id`: string|
|New Attribute metrics | / |`ad_id_v2` |
```

## For Catalog Ads
> <span style="color:DodgerBlue">**Note**</span><br>
> When you run DSA reports for Catalog Ads (formerly known as Video Shopping Ads with product source as catalog), only the asynchronous mode is supported.

### Attribute metrics
Attribute metrics are basic attributes of your campaigns, ad groups or ads. Attribute metrics are valid only when an ID type of dimension is used.

``` xtable
| Field{35%}| Data Type{10%}| Description{55%}|
| ---- | ---- | ---- |
| product_name_v2 | string | Catalog product name |
| product_image_v2 | string | Catalog product image |
| catalog_name_v2 | string | Catalog name |
| campaign_name | string | Campaign name. Supported at Campaign, Ad Group and Ad levels. |
| campaign_id | string | Campaign ID. Supported at Ad Group and Ad levels. |
| adgroup_name | string | Ad group name. Supported at Ad Group and Ad levels. |
| adgroup_id | string | Ad group ID. Supported at Ad level. |
| ad_id_v2 | string | The types of data you can get include:<ul><li>the ad IDs of Manual Ads.</li><li>the ad IDs of Smart+ Campaigns.</li><li>the ad IDs of Upgraded Smart+ Ads.</li></ul><br><ul><li>You cannot use it together with the dimension `ad_id`, the filter `ad_ids`, or the attribute metric `ad_id`.</li></ul><br>Supported at Ad level. |
| ad_name | string | Ad name. Supported at Ad level. |
```


### Core metrics
Core metrics provide fundamental insights into your advertising performance, covering essential aspects.

``` xtable
| Field{30%}| Type{10%}| Description{25%}| Detail{35%}|
|---|---|---|---|
| conversion | string | Conversions | Number of times your ad resulted in the optimization event you selected.|
```




### Product metrics
Product metrics are data about the performance of your products.
```xtable
| Field{30%}| Type{10%}| Description{25%}| Detail{35%}|
|---|---|---|---|
| product_impressions | string | Product impressions | Number of times your products were shown. |
| product_clicks | string | Product clicks (destination) | Number of clicks from your products to a specified destination. |
| product_cta| string | Product click-through rate (destination) | Percentage of product impressions that resulted in a destination click out of all product impressions. |
```


### App metrics
App metrics measure actions taken in relation to mobile apps.
```xtable
| Field{30%}| Type{10%}| Description{25%}| Detail{35%}|
|---|---|---|---|
| Purchase | | | |
#| purchase | string | Unique purchases (app) | The number of unique app purchase actions attributed to your ads. |
#| total_purchase | string | Purchases (app) | The number of app purchase actions attributed to your ads. |
#| value_per_total_purchase | string | Value per purchase (app) | The average value per app purchase action attributed to your ads. |
#| total_purchase_value | string | Purchase value (app) | The total value of app purchase actions attributed to your ads. |
| Add to Cart | | | |
#| app_event_add_to_cart | string | Unique adds to cart (app) | The number of unique app add to cart actions attributed to your ads. |
#| total_app_event_add_to_cart | string | Adds to cart (app) | The number of app add to cart actions attributed to your ads. |
#| value_per_total_app_event_add_to_cart | string | Value per add to cart (app) | The average value per app add to cart action attributed to your ads. |
#| total_app_event_add_to_cart_value | string | Add to cart value (app) | The total value of app add to cart actions attributed to your ads. |
| Checkout   | | | |
#| checkout | string | Unique checkouts (app) | The number of unique app checkout actions attributed to your ads. |
#| total_checkout | string | Checkouts (app) | The number of app checkout actions attributed to your ads. |
#| value_per_checkout | string | Value per checkout (app) | The average value per app checkout action attributed to your ads. |
#| total_checkout_value | string | Checkout value (app) | The total value of app checkout actions attributed to your ads. |
| View Content  | | | |
#| view_content | string | Unique content views (app) | The number of unique app view content actions attributed to your ads. |
#| total_view_content | string | Content views (app) | The number of app view content actions attributed to your ads. |
#| value_per_total_view_content | string | Value per content view (app) | The average value per app view content action attributed to your ads. |
#| total_view_content_value | string | Content view value (app) | The total value of app view content actions attributed to your ads. |
```

### Website metrics
Website metrics measure actions taken on websites as a result of ads, such as completed purchases. 

```xtable
| Field{30%}| Type{10%}| Description{25%}| Detail{35%}|
|---|---|---|---|
| Purchase | | | |
#| complete_payment | string | Purchases (website)| The number of website purchase actions attributed to your ads. |
#| value_per_complete_payment | string | Value per purchase (website) | The average value per website purchase action attributed to your ads. |
#| total_complete_payment_rate | string | Purchase value (website) | The total value of website purchase actions attributed to your ads. |
| Page View  | | | |
#| total_pageview | string | Total Page View | The total number of page view events.The total count is calculated based on the time each ad impression occurred. |
#| avg_value_per_pageview | string | Value per Page View | The average value returned from page view events. |
#| total_value_per_pageview | string | Total Page View Value | The total value returned from page view events. |
| View Content  | | | |
#| page_content_view_events | string | Content views (page) | The number of page view content actions attributed to your ads. |
#| value_per_page_content_view_event | string | Value per content view (page) | The average value per page view content action attributed to your ads. |
#| total_page_view_content_events_value | string | Content view value (page) | The total value of page view content actions attributed to your ads. |
| Add to Cart   | | | |
#| web_event_add_to_cart | string | Adds to cart (page) | The number of page add to cart actions attributed to your ads. |
#| value_per_web_event_add_to_cart | string | Value per add to cart (page) | The average value per page add to cart action attributed to your ads. |
#| total_web_event_add_to_cart_value | string | Add to cart value (page) | The total value of page add to cart actions attributed to your ads. |
| Initiate Checkout | | | |
#| initiate_checkout | string | Checkouts initiated (page) | The number of page initiate checkout actions attributed to your ads. |
#| value_per_initiate_checkout | string | Value per checkout initiated (page) | The average value per page initiate checkout action attributed to your ads. |
#| total_initiate_checkout_value | string | Checkout initiation value (page) | The total value of page initiate checkout actions attributed to your ads. |
```


### Offline metrics
Offline metrics measure offline actions attributed to ads, such as completed purchases in physical stores.

 ```xtable
| Field {34%}| Type{8%}| Description{20%}| Detail{38%}|
|---|---|---|---|
| Purchase | | | |
#| offline_shopping_events | string | Purchases (offline) | The number of offline purchase actions attributed to your ads. |
#| value_per_offline_shopping_event | string | Value per purchase (offline) | The average value per offline purchase action attributed to your ads. |
#| offline_shopping_events_value | string | Purchase value (offline) | The total value of offline purchase actions attributed to your ads. |
```

### Attribution metrics
Attribution metrics are data about how events are attributed.
```xtable
| Field{30%}| Type{10%}| Description{25%}| Detail{35%}|
|---|---|---|---|
| vta_conversion | string | VTA Conversion | The number of conversion events that are attributed to an ad impression. |
| vta_complete_payment | string | VTA Complete Payment | Complete payment events attributed to someone only viewing your ad. |
| vta_complete_payment_value | string | Total VTA Complete Payment Value | Total value of complete payment events attributed to someone only viewing your ad. |
| cta_conversion | string | CTA Conversion | The number of conversion events that are attributed to a click on your ad. |
| cta_complete_payment | string | CTA Complete Payment | Complete payment events attributed to someone clicking on your ad. |
| cta_complete_payment_value | string | Total CTA Complete Payment Value | Total value of complete payment events attributed to someone clicking on your ad. |
```


## For Dynamic Showcase Ads
### Attribute metrics
 Attribute metrics, such as ad group names and promotion types, are basic attributes of your campaigns, ad groups or ads. Attribute metrics are valid only when an ID type of dimension is used. 
``` xtable
| Field{35%}| Data Type{10%}| Description{55%}|
| ---- | ---- | ---- |
| catalog_name | string |Catalog name |
| product_set_name | string | Product set name|
| product_name | string | Product name |
| campaign_name | string | Campaign name. Supported at Campaign, Ad Group and Ad level. |
| campaign_id | string| Campaign ID. Supported at Ad Group and Ad level. |
| adgroup_name | string | Ad group name. Supported at Ad Group and Ad level. |
| placement_type| string | Placement type. Supported at Ad Group and Ad level. |
| adgroup_id | string| Ad group ID. Supported at Ad level. |
| ad_name | string | Ad name. Supported at Ad level. |
| ad_text | string | Ad text. Supported at Ad level. |
|product_image|string| URL of the product image. Supported at ad level.|
| tt_app_id | string | TikTok App ID, the App ID you used when creating an Ad Group. Supported at Ad Group and Ad level. Returned if the promotion type of one Ad Group is App. |
| tt_app_name | string | TikTok App Name. The name of your TikTok App. Supported at Ad Group and Ad level. Returned if the promotion type of one Ad Group is App. |
| mobile_app_id | string | Mobile App ID. Examples: <ul><li>App Store: https://apps.apple.com/us/app/angry-birds/id<b>343200656.</li><li>Google Play: https://play.google.com/store/apps/details?id=<b>com.rovio.angrybirds</b></li>Supported at Ad Group and Ad level. Returned if the promotion type of one Ad Group is App. |
|promotion_type|string| Promotion type. It can be app, website, or others. Supported at Adgroup and Ad levels in both synchronous and asynchronous reports. |
|dpa_target_audience_type| string | Target audience type for DPA. The Audience that DPA products target. Supported at Adgroup or Ad levels in both synchronous and asynchronous reports.|
```
### Basic data metrics
 Basic data metrics are data about spend and performance of your ads. 
``` xtable
| Field{35%}| Data Type{10%}| Description{55%}|
| ---- | ---- | ---- |
| impressions | string | Impressions. The number of times your ads were on screen. |
| clicks | string | Clicks (Destination). The number of clicks on your ads that led to advertiser-specified destinations.<br><p><span style="color:darkred"><b>Note</b></span>: Starting from July 6, the metric name in async reports has been updated from Clicks to Clicks (Destination).  |
| spend| string | Total Cost. The estimated total amount of money you've spent on your campaign, ad group or ad during its schedule. |
| conversion| string |Conversions. <br>The number of times your ad achieved an outcome based on the objective and settings you selected. <br>(The total count is calculated based on the time each ad impression occurred.)|
| cost_per_conversion | string | CPA. The average amount of money you've spent on a conversion. (The total count is calculated based on the time each ad impression occurred.) |
| conversion_rate | string | CVR (Clicks) .The percentage of conversions from all clicks on your ads. <br>(The total count is calculated based on the time each ad impression occurred.) <br><p><span style="color:darkred"><b>Note</b></span>: Starting late October, 2023, the calculation logic for this metric will be updated to be impression-based (the same as `conversion_rate_v2`). To ensure a smooth API integration and avoid disruptions caused by the change in calculation logic, we recommend you switch to using the impression-based metric `conversion_rate_v2` as soon as possible. |
| conversion_rate_v2 | string | CVR (Impressions). The percentage of conversions from all impressions on your ads. <br>  (The total count is calculated based on the time each ad impression occurred.) |
| ctr | string | CTR (Destination). The percentage of times people saw your ad and performed a click (destination).<br><p><span style="color:darkred"><b>Note</b></span>: Starting from July 6, the metric name in async reports has been updated from CTR to CTR (Destination). |
| cpc | string | CPC (Destination). The average amount of money you've spent on a click (destination). <br><p><span style="color:darkred"><b>Note</b></span>: Starting from July 6, the metric name in async reports has been updated from CPC to CPC (Destination). |
| cpm | string | CPM. The average amount of money you've spent per 1,000 impressions. |
| real_time_result | string | Real-time Result. The number of times your ad achieved an outcome, based on the optimization goal you selected. As a campaign may have different optimization goals, the total number of result is not supported in campaign section now. Please go to the ad group section to view the result. (The total count is based on when the conversion actually happened.) |
| real_time_cost_per_result| string | Real-time Cost Per Result. As a campaign may have different optimization goals, the total number of result is not supported in campaign section now, please go to the ad group section to view the cost per Result. (The total count is based on when the conversion actually happened.) |
| real_time_result_rate | string | Real-time Result Rate (%). As a campaign may have different optimization goals, the total number of result is not supported in campaign section now. Please go to the ad group section to view the Result Rate. (The total count is based on when the conversion actually happened.) |
| result | string | Result. The number of times your ad achieved an outcome, based on the optimization goal you selected. As one campaign may have a number of different optimization goals, this statistic is not supported for campaigns. Please go to ad groups or ads to view the results. (The total count is calculated based on the time each ad impression occurred.) |
| cost_per_result| string | Cost Per Result. The average cost for each result from your ads. As one campaign may have a number of different optimization goals, this statistic is not supported for campaigns. Please go to ad groups or ads to view the cost per result. (The total count is calculated based on the time each ad impression occurred.) |
| result_rate | string | Result Rate (%). The percentage of results you achieved out of all of the views/clicks on your ads. As one campaign may have a number of different optimization goals, this statistic is not supported for campaigns. Please go to ad groups or ads to view the result rate. (The total count is calculated based on the time each ad impression occurred.) |
| currency | string | The currency code, e. g. USD. Note that if you want to use `currency` as metrics, then the `dimensions` field in your request must include `adgroup_id`/`ad_id`/`campaign_id`/`advertiser_id`. |
```
### In-App Event metrics
 In-App event metrics are data about in-app conversion events. 
```xtable
| Field{30%}| Type{10%}| Description{25%}| Detail{35%}|
| --------- | ------ |-------- | --------- |
|Real-time App Install| | | |
#|real_time_app_install | string | Real-time App Install | The number of times your mobile app was installed that were recorded as app events and attributed to your ads. (The total count is based on when the conversion actually happened.) |
#|real_time_app_install_cost | string | Cost per Real-time App Install | The average cost for each app install event. (The total count is based on when the conversion actually happened.) |
|App Install| | | |
#| app_install | string | App Install | The number of times your mobile app was installed that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_app_install | string | Cost per App Install | The average cost for each app install event. (The total count is calculated based on the time each ad impression occurred.) |
|Registration||||
#| registration | string | Unique Registration | The number of unique registrations in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_registration | string | Cost per Unique Registration | The average cost for each unique registration that is attributed to your ad. (The total count is calculated based on the time each ad impression occurred.) |
#| registration_rate | string | Registration Rate (%) | The percentage of unique registration events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_registration | string | Total Registration | Total Registration. The total count is based on when you were billed. |
#| cost_per_total_registration | string | Cost per Registration | Cost per Registration (The total count is based on when you were billed.) |
|Purchase| | | |
#| purchase | string | Unique Purchase | The number of unique purchases event occurred in your app that were recorded by your measurement partner. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_purchase | string | Cost per Unique Purchase | The average cost of each unique purchase. (The total count is calculated based on the time each ad impression occurred.) |
#| purchase_rate | string | Purchase Rate (%) | The percentage of unique purchase event out of all the app install event. (The total count is calculated based on the time each ad impression occurred.) |
#| total_purchase | string | Purchase (Total No.) | Purchase (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_purchase | string | Cost per Purchase | Cost per Purchase (The total count is based on when you were billed.) |
#| value_per_total_purchase | string | Value per Purchase | Value per Purchase (The total count is based on when you were billed.) |
#| total_purchase_value | string | Total Purchase Value | Total Purchase Value (The total count is based on when you were billed.) |
#| total_active_pay_roas | string | Purchase ROAS | Return on ad spend (ROAS) from purchase events. (The total count is based on when you were billed.) |
|Add to Cart| | | |
#| app_event_add_to_cart | string | Unique Add to Cart | The number of unique times a user added items to a shopping cart in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_app_event_add_to_cart | string | Cost per Unique Add to Cart | The average cost for each unique add to cart event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| app_event_add_to_cart_rate | string | Unique Add to Cart Rate (%) | The percentage of unique add to cart events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_app_event_add_to_cart | string | Total Add to Cart | Add to Cart (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_app_event_add_to_cart | string | Cost per Add to Cart | Cost per Add to Cart (The total count is based on when you were billed.) |
#| value_per_total_app_event_add_to_cart | string | Value per Add to Cart | Value per Add to Cart (The total count is based on when you were billed.) |
#| total_app_event_add_to_cart_value | string | Total Add to Cart Value | Total Add to Cart Value (The total count is based on when you were billed.) |
|Checkout| | | |
#| checkout | string | Unique Checkout | The number of unique times a user made a checkout in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_checkout | string | Cost per Unique Checkout | The average cost for each unique checkout event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| checkout_rate | string | Unique Checkout Rate (%) | The percentage of unique checkout events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_checkout | string | Total Checkout | Checkout (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_checkout | string | Cost per Checkout | Cost per Checkout (The total count is based on when you were billed.) |
#| value_per_checkout | string | Value per Checkout | Value per Checkout (The total count is based on when you were billed.) |
#| total_checkout_value | string | Total Checkout Value | Total Checkout Value (The total count is based on when you were billed.) |
|View Content| | | |
#| view_content | string | Unique View Content | The number of unique times a user viewed content in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_view_content | string | Cost per Unique View Content | The average cost for each unique view content event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| view_content_rate | string | View Content Rate (%) | The percentage of unique view content events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_view_content | string | Total View Content | Total View Content. The total count is based on when you were billed. |
#| cost_per_total_view_content | string | Cost per View Content | Cost per View Content (The total count is based on when you were billed.) |
#| value_per_total_view_content | string | Value per View Content | Value per View Content (The total count is based on when you were billed.) |
#| total_view_content_value | string | Total View Content Value | Total View Content Value (The total count is based on when you were billed.) |
|Day 1 Retention| | | |
#| next_day_open | string | Unique Day 1 Retention | The number of unique day 1 retentions in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_next_day_open | string | Cost per Unique Day 1 Retention | The average cost for each unique day 1 retention event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| next_day_open_rate | string | Day 1 Retention Rate (%) | The percentage of unique day 1 retention events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_next_day_open | string | Day 1 Retention (Total No.) | Day 1 Retention (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_next_day_open | string | Cost per Day 1 Retention | Cost per Day 1 Retention (The total count is based on when you were billed.) |
|Add Payment Info| | | |
#| add_payment_info | string | Unique Add Payment Info | The number of unique times a user added payment info in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_add_payment_info | string | Cost per Unique Add Payment Info | The average cost for each unique add payment info event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| add_payment_info_rate | string | Add Payment Info Rate (%) | The percentage of unique add payment info events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_add_payment_info | string | Total Add Payment Info | Total Add Payment Info. The total count is based on when you were billed. |
#| cost_total_add_payment_info | string | Cost per Add Payment Info | Cost per Add Payment Info (The total count is based on when you were billed.) |
|Add to Wishlist| | | |
#| add_to_wishlist | string | Unique Add to Wishlist | The number of unique times users added items to a wishlist in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_add_to_wishlist | string | Cost per Unique Add to Wishlist | The average cost for each unique add to wishlist event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| add_to_wishlist_rate | string | Add to Wishlist Rate (%) | The percentage of unique add to wishlist events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_add_to_wishlist | string | Total Add to Wishlist | Add to Wishlist (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_add_to_wishlist | string | Cost per Add to Wishlist | Cost per Add to Wishlist (The total count is based on when you were billed.) |
#| value_per_total_add_to_wishlist | string | Value per Add to Wishlist | Value per Add to Wishlist (The total count is based on when you were billed.) |
#| total_add_to_wishlist_value | string | Total Add to Wishlist Value | Total Add to Wishlist Value (The total count is based on when you were billed.) |
|Launch App| | | |
#| launch_app | string | Unique Launch App | The number of unique times a user launched your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_launch_app | string | Cost per Unique Launch App | The average cost for each unique launch app event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| launch_app_rate | string | Launch App Rate (%) | The percentage of unique launch app events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_launch_app | string | Launch App (Total No.) | Launch App (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_launch_app | string | Cost per Launch App | Cost per Launch App (The total count is based on when you were billed.) |
|Complete Tutorial| | | |
#| complete_tutorial | string | Unique Complete Tutorial | The number of unique times a user completed a tutorial in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_complete_tutorial | string | Cost per Unique Complete Tutorial | The average cost for each unique complete tutorial event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| complete_tutorial_rate | string | Complete Tutorial Rate (%) | The percentage of unique complete tutorial events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_complete_tutorial | string | Complete Tutorial (Total No.) | Complete Tutorial (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_complete_tutorial | string | Cost per Complete Tutorial | Cost per Complete Tutorial (The total count is based on when you were billed.) |
#| value_per_total_complete_tutorial | string | Value per Complete Tutorial | Value per Complete Tutorial (The total count is based on when you were billed.) |
#| total_complete_tutorial_value | string | Total Complete Tutorial Value | Total Complete Tutorial Value (The total count is based on when you were billed.) |
|Create Group| | | |
#| create_group | string | Unique Create Group | The number of unique times a user created a group in your mobile game app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_create_group | string | Cost per Unique Create Group | The average cost for each unique create group event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| create_group_rate | string | Create Group Rate (%) | The percentage of unique create group events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_create_group | string | Create Group (Total No.) | Create Group (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_create_group | string | Cost per Create Group | Cost per Create Group (The total count is based on when you were billed.) |
#| value_per_total_create_group | string | Value per Create Group | Value per Create Group (The total count is based on when you were billed.) |
#| total_create_group_value | string | Total Create Group Value | Total Create Group Value (The total count is based on when you were billed.) |
|Join Group| | | |
#| join_group | string | Unique Join Group | The number of unique times a user joined a group in your mobile game app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_join_group | string | Cost per Unique Join Group | The average cost for each unique join group event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| join_group_rate | string | Join Group Rate (%) | The percentage of unique join group events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_join_group | string | Join Group (Total No.) | Join Group (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_join_group | string | Cost per Join Group | Cost per Join Group (The total count is based on when you were billed.) |
#| value_per_total_join_group | string | Value per Join Group | Value per Join Group (The total count is based on when you were billed.) |
#| total_join_group_value | string | Total Join Group Value | Total Join Group Value (The total count is based on when you were billed.) |
|Create Role| | | |
#| create_gamerole | string | Unique Create Role | The number of unique times a user created a character or role in your mobile game app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_create_gamerole | string | Cost per Unique Create Role | The average cost for each unique create role event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| create_gamerole_rate | string | Unique Create Role Rate (%) | The percentage of unique create role events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_create_gamerole | string | Create Role (Total No.) | Create Role (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_create_gamerole | string | Cost per Create Role | Cost per Create Role (The total count is based on when you were billed.) |
#| value_per_total_create_gamerole | string | Value per Create Role | Value per Create Role (The total count is based on when you were billed.) |
#| total_create_gamerole_value | string | Total Create Role Value | Total Create Role Value (The total count is based on when you were billed.) |
|Spend Credit| | | |
#| spend_credits | string | Unique Spend Credit | The number of unique times a user spent credit in your mobile game app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_spend_credits | string | Cost per Unique Spend Credit | The average cost for each unique spend credits event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| spend_credits_rate | string | Spend Credit Rate (%) | The percentage of unique spend credits events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_spend_credits | string | Spend Credit (Total No.) | Spend Credit (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_spend_credits | string | Cost per Spend Credit | Cost per Spend Credit (The total count is based on when you were billed.) |
#| value_per_total_spend_credits | string | Value per Spend Credit | Value per Spend Credit (The total count is based on when you were billed.) |
#| total_spend_credits_value | string | Total Spend Credit Value | Total Spend Credit Value (The total count is based on when you were billed.) |
|Achieve Level| | | |
#| achieve_level | string | Unique Achieve Level | The number of unique times a user achieved a level in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_achieve_level | string | Cost per Unique Achieve Level | The average cost for each unique achieve level event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| achieve_level_rate | string | Achieve Level Rate (%) | The percentage of unique achieve level events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_achieve_level | string | Achieve Level (Total No.) | Achieve Level (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_achieve_level | string | Cost per Achieve Level | Cost per Achieve Level (The total count is based on when you were billed.) |
#| value_per_total_achieve_level | string | Value per Achieve Level | Value per Achieve Level (The total count is based on when you were billed.) |
#| total_achieve_level_value | string | Total Achieve Level Value | Total Achieve Level Value (The total count is based on when you were billed.) |
|Unlock Achievement| | | |
#| unlock_achievement | string | Unique Unlock Achievement | The number of unique times that a user unlocked an achievement in your mobile game app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_unlock_achievement | string | Cost per Unique Unlock Achievement | The average cost for each unique unlock achievement event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| unlock_achievement_rate | string | Unlock Achievement Rate (%) | The percentage of unique unlock achievement events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_unlock_achievement | string | Unlock Achievement (Total No.) | Unlock Achievement (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_unlock_achievement | string | Cost per Unlock Achievement | Cost per Unlock Achievement (The total count is based on when you were billed.) |
#| value_per_total_unlock_achievement | string | Value per Unlock Achievement | Value per Unlock Achievement (The total count is based on when you were billed.) |
#| total_unlock_achievement_value | string | Total Unlock Achievement Value | Total Unlock Achievement Value (The total count is based on when you were billed.) |
|Generate Lead| | | |
#| sales_lead | string | Unique Generate Lead | The number of unique leads generated in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_sales_lead | string | Cost per Unique Generate Lead | The average cost for each unique generate lead event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| sales_lead_rate | string | Generate Lead Rate (%) | The percentage of unique generate lead events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_sales_lead | string | Generate Lead (Total No.) | Generate Lead (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_sales_lead | string | Cost per Generate Lead | Cost per Generate Lead (The total count is based on when you were billed.) |
#| value_per_total_sales_lead | string | Value per Generate Lead | Value per Generate Lead (The total count is based on when you were billed.) |
#| total_sales_lead_value | string | Total Generate Lead Value | Total Generate Lead Value (The total count is based on when you were billed.) |
|In-App Ad Click| | | |
#| in_app_ad_click | string | Unique In-App Ad Click | The number of unique in-app ad click events that occurred in your mobile app and are attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_in_app_ad_click | string | Cost per Unique In-App Ad Click | The average cost for each unique in-app ad click event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| in_app_ad_click_rate | string | In-App Ad Click Rate (%) | The percentage of unique in-app ad click events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_in_app_ad_click | string | In-App Ad Click (Total No.) | In-App Ad Click (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_in_app_ad_click | string | Cost per In-App Ad Click | Cost per In-App Ad Click (The total count is based on when you were billed.) |
#| value_per_total_in_app_ad_click | string | Value per In-App Ad Click | Value per In-App Ad Click (The total count is based on when you were billed.) |
#| total_in_app_ad_click_value | string | Total In-App Ad Click Value | Total In-App Ad Click Value (The total count is based on when you were billed.) |
|In-App Ad Impression| | | |
#| in_app_ad_impr | string | Unique In-App Ad Impr | The number of unique in-app ad impression events that occurred in your mobile app and are attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_in_app_ad_impr | string | Cost per Unique In-App Ad Impr | The average cost for each unique in-app ad impression event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| in_app_ad_impr_rate | string | In-App Ad Impr Rate (%) | The percentage of unique in-app ad impression events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_in_app_ad_impr | string | In-App Ad Impr (Total No.) | In-App Ad Impr (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_in_app_ad_impr | string | Cost per In-App Ad Impr | Cost per In-App Ad Impr (The total count is based on when you were billed.) |
#| value_per_total_in_app_ad_impr | string | Value per In-App Ad Impr | Value per In-App Ad Impr (The total count is based on when you were billed.) |
#| total_in_app_ad_impr_value | string | Total In-App Ad Impr Value | Total In-App Ad Impr Value (The total count is based on when you were billed.) |
|Loan Apply| | | |
#| loan_apply | string | Unique Loan Apply | The number of unique times a user applied for a loan in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_loan_apply | string | Cost per Unique Loan Apply | The average cost for each unique loan apply event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| loan_apply_rate | string | Loan Apply Rate (%) | The percentage of unique loan apply events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_loan_apply | string | Loan Apply (Total No.) | Loan Apply (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_loan_apply | string | Cost per Loan Apply | Cost per Loan Apply (The total count is based on when you were billed.) |
|Loan Approval| | | |
#| loan_credit | string | Unique Loan Approval | The number of unique times a user was approved a loan in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_loan_credit | string | Cost per Unique Loan Approval | The average cost for each unique loan approval event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| loan_credit_rate | string | Loan Approval Rate (%) | The percentage of unique loan approval events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_loan_credit | string | Loan Approval (Total No.) | Loan Approval (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_loan_credit | string | Cost per Loan Approval | Cost per Loan Approval (The total count is based on when you were billed.) |
|Loan Disbursal| | | |
#| loan_disbursement | string | Unique Loan Disbursement | The number of unique loan disbursal in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_loan_disbursement | string | Cost per Unique Loan Disbursement | The average cost for each unique loan disbursal event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| loan_disbursement_rate | string | Loan Disbursement Rate (%) | The number of unique loan disbursals in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| total_loan_disbursement | string | Loan Disbursement (Total No.) | Loan Disbursement (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_loan_disbursement | string | Cost per Loan Disbursement | Cost per Loan Disbursement (The total count is based on when you were billed.) |
|Login| | | |
#| login | string | Unique Login | The number of unique logins in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_login | string | Cost per Unique Login | The average cost for each unique login event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| login_rate | string | Login Rate (%) | The percentage of unique login events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_login | string | Login (Total No.) | Login (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_login | string | Cost per Login | Cost per Login (The total count is based on when you were billed.) |
|Rate| | | |
#| ratings | string | Unique Rate | The number of unique ratings in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_ratings | string | Cost per Unique Rate | The average cost for each unique rate event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| ratings_rate | string | Rate Rate (%) | The percentage of unique rate events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_ratings | string | Rate (Total No.) | Rate (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_ratings | string | Cost per Rate | Cost per Rate (The total count is based on when you were billed.) |
#| value_per_total_ratings | string | Value per Rate | Value per Rate (The total count is based on when you were billed.) |
#| total_ratings_value | string | Total Rate Value | Total Rate Value (The total count is based on when you were billed.) |
|Search| | | |
#|search | string | Unique Search | The number of unique searches in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_search | string | Cost per Unique Search | The average cost for each unique search event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| search_rate | string | Search Rate (%) | The percentage of unique search events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_search | string | Total Search | Total search (The total count is based on when you were billed.) |
#| cost_per_total_search | string | Cost per Search | Cost per Search (The total count is based on when you were billed.) |
|Start Trial| | | |
#| start_trial | string | Unique Start Trial | The number of unique times a user started a trial in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_start_trial | string | Cost per Unique Start Trial | The average cost for each unique start trial event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| start_trial_rate | string | Start Trial Rate (%) | The percentage of unique start trial events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_start_trial | string | Start Trial (Total No.) | Start Trial (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_start_trial | string | Cost per Start Trial | Cost per Start Trial (The total count is based on when you were billed.) |
|Subscribe| | | |
#| subscribe | string | Unique Subscribe | The number of unique subscriptions in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| cost_per_subscribe | string | Cost per Unique Subscribe | The average cost for each unique subscribe event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.) |
#| subscribe_rate | string | Subscribe Rate (%) | The percentage of unique subscribe events out of all app install events. (The total count is calculated based on the time each ad impression occurred.) |
#| total_subscribe | string | Subscribe (Total No.) | Subscribe (Total No.) (The total count is based on when you were billed.) |
#| cost_per_total_subscribe | string | Cost per Subscribe | Cost per Subscribe (The total count is based on when you were billed.) |
#| value_per_total_subscribe | string | Value per Subscribe | Value per Subscribe (The total count is based on when you were billed.) |
#| total_subscribe_value | string | Total Subscribe Value | Total Subscribe Value (The total count is based on when you were billed.) |
```
### Page event metrics
 Page event metrics are data about website conversion events. 
```xtable
| Field{30%}| Type{10%}| Description{25%}| Detail{35%}|
| --------- | ------ |-------- | --------- |
|Complete Payment ROAS| | | |
#| complete_payment_roas | string | Complete Payment ROAS | The total return on ad spend (ROAS) from complete payment events that are attributed to your ads. |
|Complete Payment| | | |
#| complete_payment | string | Total Complete Payment | The number of complete payment events. |
#| cost_per_complete_payment | string | Cost per Complete Payment | The average cost of each complete payment event. |
#| complete_payment_rate | string | Complete Payment Rate (%) | The percentage of complete payment events out of all click events. |
#| value_per_complete_payment | string | Value per Complete Payment | The average value returned from complete payment events. |
#| total_complete_payment_rate | string | Total Complete Payment Value | The total value returned from complete payment events. |
|Click Button| | | |
#| value_per_button_click | string | Value per Button Click | The average value returned from button click events. |
#| total_button_click_value | string | Total Button Click Value | The total value returned from button click events. |
|Complete Registration| | | |
#| user_registration | string | Total Complete Registration | The number of user registration events. |
#| cost_per_user_registration | string | Cost per Complete Registration | The average cost of each user registration event. |
#| user_registration_rate | string | Complete Registration Rate (%) | The percentage of user registration events out of all click events. |
#| value_per_user_registration | string | Value per Complete Registration | The average value returned from user registration events. |
#| total_user_registration_value | string | Total Complete Registration Value | The total value returned from user registration events. |
|Add to Cart| | | |
#| web_event_add_to_cart | string | Add to Cart | The number of add to cart events. |
#| cost_per_web_event_add_to_cart | string | Cost per Add to Cart | The average cost of each add to cart event. |
#| web_event_add_to_cart_rate | string | Add to Cart Rate (%) | The percentage of add to cart events out of all click events. |
#| value_per_web_event_add_to_cart | string | Value per Add to Cart | The average value returned from add to cart events. |
#| total_web_event_add_to_cart_value | string | Total Add to Cart Value | The total value returned from add to cart events. |
|Place an Order| | | |
#| on_web_order | string | Total Place an Order | The number of place an order events. |
#| cost_per_on_web_order | string | Cost per Place an Order | The average cost of each place an order event. |
#| on_web_order_rate | string | Place an Order Rate (%) | The percentage of place an order events out of all click events. |
#| value_per_on_web_order | string | Value per Place an Order | The average value returned from place an order events. |
#| total_on_web_order_value | string | Total Place an Order Value | The total value returned from place an order events. |
|Initiate Checkout| | | |
#| initiate_checkout | string | Total Initiate Checkout | The number of initiate checkout events. |
#| cost_per_initiate_checkout | string | Cost per Initiate Checkout | The average cost of each initiate checkout event. |
#| initiate_checkout_rate | string | Initiate Checkout Rate (%) | The percentage of initiate checkout events out of all click events. |
#| value_per_initiate_checkout | string | Value per Initiate Checkout | The average value returned from initiate checkout events. |
#| total_initiate_checkout_value | string | Total Initiate Checkout Value | The total value returned from initiate checkout events. |
|Add Payment Info| | | |
#| add_billing | string | Add Billing | The number of add billing events. |
#| cost_per_add_billing | string | Add Billing Cost | The average cost of each add billing event. |
#| add_billing_rate | string | Add Billing Rate (%) | The percentage of add billing events out of all click events. |
#| value_per_add_billing | string | Value per Add Billing | The average value returned from add billing events. |
#| total_add_billing_value | string | Total Add Billing Value | The total value returned from add billing events. |
|Search| | | |
#| page_event_search | string | Search | The number of search events. |
#| cost_per_page_event_search | string | Search Cost | The average cost of each search event. |
#| page_event_search_rate | string | Search Rate (%) | The percentage of search events out of all click events. |
#| value_per_page_event_search | string | Value per Search | The average value returned from search events. |
#| total_page_event_search_value | string | Total Search Value | The total value returned from search events. |
|Submit Form| | | |
#| form | string | Total Submit Form | The number of form submission events. |
#| cost_per_form | string | Cost per Submit Form | The average cost of each form submission event. |
#| form_rate | string | Submit Form Rate (%) | The percentage of form submission events out of all click events. |
#| value_per_form | string | Value per Submit Form | The average value returned from form submission events. |
#| total_form_value | string | Total Submit Form Value | The total value returned from form submission events. |
|Download| | | |
#| download_start | string | Total Download | The number of download button click events. |
#| cost_per_download_start | string | Cost per Download | The average cost of each download button click events. |
#| download_start_rate | string | Download Rate (%) | The percentage of download button click events out of all click events. |
#| value_per_download_start | string | Value per Download | The average value returned from download button click events. |
#| total_download_start_value | string | Total Download Value | The total value returned from download button click events. |
|Add to Wishlist| | | |
#|on_web_add_to_wishlist|string| Total Add to Wishlist | The number of add to wishlist events that are attributed to your ads. |
#|cost_per_on_web_add_to_wishlist|string| Cost per Add To Wishlist | The average cost of each add to wishlist event.|
#|on_web_add_to_wishlist_per_click|string|Add To Wishlist Rate| The percentage of add to wishlist events out of all click events. |
#|value_per_on_web_add_to_wishlist|string|Value per Add To Wishlist| The average value returned from each add to wishlist event.|
#|total_on_web_add_to_wishlist_value|string|Total Add To Wishlist Value| The total value returned from all add to wishlist events. |
|Subscribe| | | |
#|on_web_subscribe|string |Total Subscribe| The number of subscribe events that are attributed to your ads. |
#|cost_per_on_web_subscribe|string|Subscribe Cost| The average cost of each subscribe event. |
#|on_web_subscribe_per_click|string|Subscribe Rate (%)|The percentage of subscribe events out of all click events. |
#|value_per_on_web_subscribe|string|Value per Subscribe| The average value returned from each subscribe event. |
#|total_on_web_subscribe_value|string|Total Subscribe Value| The total value returned from all subscribe events. |
```
