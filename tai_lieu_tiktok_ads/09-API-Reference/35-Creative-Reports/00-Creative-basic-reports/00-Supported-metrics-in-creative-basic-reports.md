---
title: "Supported metrics in creative basic reports"
doc_id: 1861363088053250
path: "API Reference / Creative Reports / Creative basic reports / Supported metrics in creative basic reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported metrics in creative basic reports

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|spend|string|The estimated total amount of money you've spent on your campaign, ad group or ad during its schedule. The unit of the amount of money depends on your advertiser account.|
|cpc|string|The average amount of money you've spent on a click.|
|cpm|string|The average amount of money you've spent per 1,000 impressions.|
|impressions|string|The number of times your ads were on screen.|
|clicks|string|The number of clicks on your ads.|
|ctr|string|The percentage of times people saw your ad and performed a click.|
|conversion|string|The number of times your ad achieved an outcome, based on the secondary goal you selected. As one campaign may have a number of different secondary goals, this statistic is not supported for campaigns. Please go to ad groups or ads to view. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_conversion|string|The average amount of money you've spent on a conversion.(The total count is calculated based on the time each ad impression occurred.)|
|conversion_rate|string|The percentage of results you received out of all the clicks of your ads.(The total count is calculated based on the time each ad impression occurred.)|
|real_time_conversion|string|The number of times your ad achieved an outcome, based on the objective and settings you selected. (The total count is based on when the conversion actually happened.)|
|real_time_cost_per_conversion|string|The average amount of money you've spent on a conversion. (The total count is based on when the conversion actually happened.)|
|real_time_conversion_rate|string|The percentage of results you received out of all the clicks of your ads. (The total count is based on when the conversion actually happened.)|
|video_play_actions|string|The number of times your video starts to play. Replays will not be counted.|
|video_watched_2s|string|The number of times your video played for at least 2 seconds. Replays will not be counted.|
|video_watched_6s|string|The number of times your video played for at least 6 seconds, or completely played. Replays will not be counted.|
|average_video_play|string|The average time your video was played per single video view, including any time spent replaying the video.|
|video_views_p25|string|The number of times your video was played at 25% of its length. Replays will not be counted.|
|video_views_p50|string|The number of times your video was played at 50% of its length. Replays will not be counted.|
|video_views_p75|string|The number of times your video was played at 75% of its length. Replays will not be counted.|
|video_views_p100|string|The number of times your video was played at 100% of its length. Replays will not be counted.|
|real_time_app_install|string|The number of times your mobile app was installed that were recorded as app events and attributed to your ads. (The total count is based on when the conversion actually happened.)|
|real_time_app_install_cost|string|The average cost for each app install event. (The total count is based on when the conversion actually happened.)|
|app_install|string|The number of times your mobile app was installed that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_app_install|string|The average cost for each app install event. (The total count is calculated based on the time each ad impression occurred.)|
|registration|string|The number of unique registrations in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_registration|string|The average cost for each unique registration that is attributed to your ad. (The total count is calculated based on the time each ad impression occurred.)|
|registration_rate|string|The percentage of unique registration events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|purchase|string|The number of unique purchases event occurred in your app that were recorded by your measurement partner. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_purchase|string|The average cost of each unique purchase. (The total count is calculated based on the time each ad impression occurred.)|
|purchase_rate|string|The percentage of unique purchase event out of all the app install event. (The total count is calculated based on the time each ad impression occurred.)|
|app_event_add_to_cart|string|The number of unique times a user added items to a shopping cart in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_app_event_add_to_cart|string|The average cost for each unique add to cart event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|app_event_add_to_cart_rate|string|The percentage of unique add to cart events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|checkout|string|The number of unique times a user made a checkout in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_checkout|string|The average cost for each unique checkout event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|checkout_rate|string|The percentage of unique checkout events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|view_content|string|The number of unique times a user viewed content in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_view_content|string|The average cost for each unique view content event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|view_content_rate|string|The percentage of unique view content events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|next_day_open|string|The number of unique day 2 retentions in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_next_day_open|string|The average cost for each unique day 2 retention event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|next_day_open_rate|string|The percentage of unique day 2 retention events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|add_payment_info|string|The number of unique times a user added payment info in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_add_payment_info|string|The average cost for each unique add payment info event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|add_payment_info_rate|string|The percentage of unique add payment info events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|add_to_wishlist|string|The number of unique times users added items to a wishlist in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_add_to_wishlist|string|The average cost for each unique add to wishlist event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|add_to_wishlist_rate|string|The percentage of unique add to wishlist events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|launch_app|string|The number of unique times a user launched your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_launch_app|string|The average cost for each unique launch app event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|launch_app_rate|string|The percentage of unique launch app events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|complete_tutorial|string|The number of unique times a user completed a tutorial in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_complete_tutorial|string|The average cost for each unique complete tutorial event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|complete_tutorial_rate|string|The percentage of unique complete tutorial events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|create_group|string|The number of unique times a user created a group in your mobile game app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_create_group|string|The average cost for each unique create group event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|create_group_rate|string|The percentage of unique create group events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|join_group|string|The number of unique times a user joined a group in your mobile game app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_join_group|string|The average cost for each unique join group event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|join_group_rate|string|The percentage of unique join group events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|create_gamerole|string|The number of unique times a user created a character or role in your mobile game app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_create_gamerole|string|The average cost for each unique create role event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|create_gamerole_rate|string|The percentage of unique create role events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|spend_credits|string|The number of unique times a user spent credit in your mobile game app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_spend_credits|string|The average cost for each unique spend credits event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|spend_credits_rate|string|The percentage of unique spend credits events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|achieve_level|string|The number of unique times a user achieved a level in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_achieve_level|string|The average cost for each unique achieve level event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|achieve_level_rate|string|The percentage of unique achieve level events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|unlock_achievement|string|The number of unique times that a user unlocked an achievement in your mobile game app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_unlock_achievement|string|The average cost for each unique unlock achievement event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|unlock_achievement_rate|string|The percentage of unique unlock achievement events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|sales_lead|string|The number of unique leads generated in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_sales_lead|string|The average cost for each unique generate lead event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|sales_lead_rate|string|The percentage of unique generate lead events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|in_app_ad_click|string|The number of unique in-app ad click events that occurred in your mobile app and are attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_in_app_ad_click|string|The average cost for each unique in-app ad click event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|in_app_ad_click_rate|string|The percentage of unique in-app ad click events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|in_app_ad_impr|string|The number of unique in-app ad impression events that occurred in your mobile app and are attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_in_app_ad_impr|string|The average cost for each unique in-app ad impression event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|in_app_ad_impr_rate|string|The percentage of unique in-app ad impression events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|loan_apply|string|The number of unique times a user applied for a loan in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_loan_apply|string|The average cost for each unique loan apply event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|loan_apply_rate|string|The percentage of unique loan apply events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|loan_credit|string|The number of unique times a user was approved a loan in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_loan_credit|string|The average cost for each unique loan approval event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|loan_credit_rate|string|The percentage of unique loan approval events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|loan_disbursement|string|The number of unique loan disbursal in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_loan_disbursement|string|The average cost for each unique loan disbursal event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|loan_disbursement_rate|string|The number of unique loan disbursals in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|login|string|The number of unique logins in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_login|string|The average cost for each unique login event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|login_rate|string|The percentage of unique login events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|ratings|string|The number of unique ratings in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_ratings|string|The average cost for each unique rate event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|ratings_rate|string|The percentage of unique rate events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|search|string|The number of unique searches in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_search|string|The average cost for each unique search event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|search_rate|string|The percentage of unique search events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|start_trial|string|The number of unique times a user started a trial in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_start_trial|string|The average cost for each unique start trial event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|start_trial_rate|string|The percentage of unique start trial events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|subscribe|string|The number of unique subscriptions in your mobile app that were recorded as app events and attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|cost_per_subscribe|string|The average cost for each unique subscribe event that is attributed to your ads. (The total count is calculated based on the time each ad impression occurred.)|
|subscribe_rate|string|The percentage of unique subscribe events out of all app install events. (The total count is calculated based on the time each ad impression occurred.)|
|total_registration|string|Registration (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_registration|string|Cost per Registration (The total count is based on when you were billed.)|
|total_purchase|string|Purchase (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_purchase|string|Cost per Purchase (The total count is based on when you were billed.)|
|value_per_total_purchase|string|Value per Purchase (The total count is based on when you were billed.)|
|total_purchase_value|string|Total Purchase Value (The total count is based on when you were billed.)|
|total_app_event_add_to_cart|string|Add to Cart (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_app_event_add_to_cart|string|Cost per Add to Cart (The total count is based on when you were billed.)|
|value_per_total_app_event_add_to_cart|string|Value per Add to Cart (The total count is based on when you were billed.)|
|total_app_event_add_to_cart_value|string|Total Add to Cart Value (The total count is based on when you were billed.)|
|total_checkout|string|Checkout (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_checkout|string|Cost per Checkout (The total count is based on when you were billed.)|
|value_per_checkout|string|Value per Checkout (The total count is based on when you were billed.)|
|total_checkout_value|string|Total Checkout Value (The total count is based on when you were billed.)|
|total_view_content|string|View Content (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_view_content|string|Cost per View Content (The total count is based on when you were billed.)|
|value_per_total_view_content|string|Value per View Content (The total count is based on when you were billed.)|
|total_view_content_value|string|Total View Content Value (The total count is based on when you were billed.)|
|total_next_day_open|string|Day 2 Retention (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_next_day_open|string|Cost per Day 2 Retention (The total count is based on when you were billed.)|
|total_add_payment_info|string|Add Payment Info (Total No.) (The total count is based on when you were billed.)|
|cost_total_add_payment_info|string|Cost per Add Payment Info (The total count is based on when you were billed.)|
|total_add_to_wishlist|string|Add to Wishlist (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_add_to_wishlist|string|Cost per Add to Wishlist (The total count is based on when you were billed.)|
|value_per_total_add_to_wishlist|string|Value per Add to Wishlist (The total count is based on when you were billed.)|
|total_add_to_wishlist_value|string|Total Add to Wishlist Value (The total count is based on when you were billed.)|
|total_launch_app|string|Launch App (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_launch_app|string|Cost per Launch App (The total count is based on when you were billed.)|
|total_complete_tutorial|string|Complete Tutorial (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_complete_tutorial|string|Cost per Complete Tutorial (The total count is based on when you were billed.)|
|value_per_total_complete_tutorial|string|Value per Complete Tutorial (The total count is based on when you were billed.)|
|total_complete_tutorial_value|string|Total Complete Tutorial Value (The total count is based on when you were billed.)|
|total_create_group|string|Create Group (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_create_group|string|Cost per Create Group (The total count is based on when you were billed.)|
|value_per_total_create_group|string|Value per Create Group (The total count is based on when you were billed.)|
|total_create_group_value|string|Total Create Group Value (The total count is based on when you were billed.)|
|total_join_group|string|Join Group (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_join_group|string|Cost per Join Group (The total count is based on when you were billed.)|
|value_per_total_join_group|string|Value per Join Group (The total count is based on when you were billed.)|
|total_join_group_value|string|Total Join Group Value (The total count is based on when you were billed.)|
|total_create_gamerole|string|Create Role (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_create_gamerole|string|Cost per Create Role (The total count is based on when you were billed.)|
|value_per_total_create_gamerole|string|Value per Create Role (The total count is based on when you were billed.)|
|total_create_gamerole_value|string|Total Create Role Value (The total count is based on when you were billed.)|
|total_spend_credits|string|Spend Credit (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_spend_credits|string|Cost per Spend Credit (The total count is based on when you were billed.)|
|value_per_total_spend_credits|string|Value per Spend Credit (The total count is based on when you were billed.)|
|total_spend_credits_value|string|Total Spend Credit Value (The total count is based on when you were billed.)|
|total_achieve_level|string|Achieve Level (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_achieve_level|string|Cost per Achieve Level (The total count is based on when you were billed.)|
|value_per_total_achieve_level|string|Value per Achieve Level (The total count is based on when you were billed.)|
|total_achieve_level_value|string|Total Achieve Level Value (The total count is based on when you were billed.)|
|total_unlock_achievement|string|Unlock Achievement (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_unlock_achievement|string|Cost per Unlock Achievement (The total count is based on when you were billed.)|
|value_per_total_unlock_achievement|string|Value per Unlock Achievement (The total count is based on when you were billed.)|
|total_unlock_achievement_value|string|Total Unlock Achievement Value (The total count is based on when you were billed.)|
|total_sales_lead|string|Generate Lead (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_sales_lead|string|Cost per Generate Lead (The total count is based on when you were billed.)|
|value_per_total_sales_lead|string|Value per Generate Lead (The total count is based on when you were billed.)|
|total_sales_lead_value|string|Total Generate Lead Value (The total count is based on when you were billed.)|
|total_in_app_ad_click|string|In App Ad Click (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_in_app_ad_click|string|Cost per In App Ad Click (The total count is based on when you were billed.)|
|value_per_total_in_app_ad_click|string|Value per In App Ad Click (The total count is based on when you were billed.)|
|total_in_app_ad_click_value|string|Total In App Ad Click Value (The total count is based on when you were billed.)|
|total_in_app_ad_impr|string|In App Ad Impr (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_in_app_ad_impr|string|Cost per In App Ad Impr (The total count is based on when you were billed.)|
|value_per_total_in_app_ad_impr|string|Value per In App Ad Impr (The total count is based on when you were billed.)|
|total_in_app_ad_impr_value|string|Total In App Ad Impr Value (The total count is based on when you were billed.)|
|total_loan_apply|string|Loan Apply (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_loan_apply|string|Cost per Loan Apply (The total count is based on when you were billed.)|
|total_loan_credit|string|Loan Approval (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_loan_credit|string|Cost per Loan Approval (The total count is based on when you were billed.)|
|total_loan_disbursement|string|Loan Disbursement (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_loan_disbursement|string|Cost per Loan Disbursement (The total count is based on when you were billed.)|
|total_login|string|Login (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_login|string|Cost per Login (The total count is based on when you were billed.)|
|total_ratings|string|Rate (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_ratings|string|Cost per Rate (The total count is based on when you were billed.)|
|value_per_total_ratings|string|Value per Rate (The total count is based on when you were billed.)|
|total_ratings_value|string|Total Rate Value (The total count is based on when you were billed.)|
|total_search|string|Search (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_search|string|Cost per Search (The total count is based on when you were billed.)|
|total_start_trial|string|Start Trial (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_start_trial|string|Cost per Start Trial (The total count is based on when you were billed.)|
|total_subscribe|string|Subscribe (Total No.) (The total count is based on when you were billed.)|
|cost_per_total_subscribe|string|Cost per Subscribe (The total count is based on when you were billed.)|
|value_per_total_subscribe|string|Value per Subscribe (The total count is based on when you were billed.)|
|total_subscribe_value|string|Total Subscribe Value (The total count is based on when you were billed.)|
|vta_conversion|string|The percentage of app install events you received out of all the clicks of your ads.|
|cost_per_vta_conversion|string|The average cost for each app install event that attribute to impression.|
|vta_registration|string|The number of registration event that attribute to impression, based on information received from your measurement partner.|
|cost_per_vta_registration|string|The average cost for each registration event that attribute to impression.|
|vta_purchase|string|The number of in-app payment event that attribute to impression, based on information received from your measurement partner.|
|cost_per_vta_purchase|string|The average cost for each payment event that attribute to impression.|
|vta_complete_payment|string|VTA complete paymentComplete payment events attributed to someone only viewing your ad.|
|cost_per_vta_payments_completed|string|Cost per VTA complete paymentAverage cost of each complete payment event attributed to someone only viewing your ad.|
|vta_complete_payment_value|string|Total VTA complete payment valueTotal value of complete payment events attributed to someone only viewing your ad.|
|vta_complete_payment_roas|string|VTA complete payment ROASReturn on ad spend (ROAS) from complete payment events attributed to someone only viewing your ad.|
|cta_conversion|string|The number of in-app app install event that occurred on your mobile app and are attributed to the amount of clicks.|
|cost_per_cta_conversion|string|The average cost for each app install event that attribute to clicks.|
|cta_registration|string|The number of in-app registration event that attribute to click, based on information received from your measurement partner.|
|cost_per_cta_registration|string|The average cost for in-app registration event that attribute to clicks.|
|cta_purchase|string|The number of in-app payment event that attribute to click, based on information received from your measurement partner.|
|cost_per_cta_purchase|string|The average cost for each payment event that attribute to clicks.|
|complete_payment_roas|string|The total return on ad spend (ROAS) from complete payment events that are attributed to your ads.|
|complete_payment|string|The number of complete payment events.|
|cost_per_complete_payment|string|The average cost of each complete payment event.|
|complete_payment_rate|string|The percentage of complete payment events out of all click events.|
|value_per_complete_payment|string|The average value returned from complete payment events.|
|total_complete_payment_rate|string|The total value returned from complete payment events.|
|page_browse_view|string|The number of page browse events.|
|cost_per_page_browse_view|string|The average cost of each page browse event.|
|page_browse_view_rate|string|The percentage of page browse events out of all click events.|
|total_page_browse_view_value|string|The average value returned from page browse events.|
|value_per_page_browse_view|string|The total value returned from page browse events.|
|button_click|string|The number of button click events.|
|cost_per_button_click|string|The average cost of each button click event.|
|button_click_rate|string|The percentage of button click events out of all click events.|
|value_per_button_click|string|The average value returned from button click events.|
|total_button_click_value|string|The total value returned from button click events.|
|online_consult|string|The number of online consultation events.|
|cost_per_online_consult|string|The average cost of each online consultation event.|
|online_consult_rate|string|The percentage of online consultation events out of all click events.|
|value_per_online_consult|string|The average value returned from online consultation events.|
|total_online_consult_value|string|The total value returned from online consultation events.|
|user_registration|string|The number of user registration events.|
|cost_per_user_registration|string|The average cost of each user registration event.|
|user_registration_rate|string|The percentage of user registration events out of all click events.|
|value_per_user_registration|string|The average value returned from user registration events.|
|total_user_registration_value|string|The total value returned from user registration events.|
|product_details_page_browse|string|The number of product details page browse events.|
|cost_per_product_details_page_browse|string|The average cost of each product details page browse event.|
|product_details_page_browse_rate|string|The percentage of product details page browse events out of all click events.|
|value_per_product_details_page_browse|string|The average value returned from product details page browse events.|
|total_product_details_page_browse_value|string|The total value returned from product details page browse events.|
|web_event_add_to_cart|string|The number of add to cart events.|
|cost_per_web_event_add_to_cart|string|The average cost of each add to cart event.|
|web_event_add_to_cart_rate|string|The percentage of add to cart events out of all click events.|
|value_per_web_event_add_to_cart|string|The average value returned from add to cart events.|
|total_web_event_add_to_cart_value|string|The total value returned from add to cart events.|
|on_web_order|string|The number of place an order events.|
|cost_per_on_web_order|string|The average cost of each place an order event.|
|on_web_order_rate|string|The percentage of place an order events out of all click events.|
|value_per_on_web_order|string|The average value returned from place an order events.|
|total_on_web_order_value|string|The total value returned from place an order events.|
|form_detail|string|The number of details page browse (form) events.|
|cost_per_form_detail|string|The average cost of details page browse (form) event.|
|form_detail_rate|string|The percentage of details page browse (form) events out of all click events.|
|value_per_form_detail|string|The average value returned from details page browse (form) events.|
|total_form_detail_value|string|The total value returned from details page browse (form) events.|
|form_button|string|The number of button click (form) events.|
|cost_per_form_button|string|The average cost of each button click (form) event.|
|form_button_rate|string|The percentage of button click (form) events out of all click events.|
|value_per_form_button|string|The average value returned from button click (form) events.|
|total_form_button_value|string|The total value returned from button click (form) events.|
|form|string|The number of form submission events.|
|cost_per_form|string|The average cost of each form submission event.|
|form_rate|string|The percentage of form submission events out of all click events.|
|value_per_form|string|The average value returned from form submission events.|
|total_form_value|string|The total value returned from form submission events.|
|page_browse_consultation|string|The number of details page browse (consultation) events.|
|cost_per_page_browse_consultation|string|The average cost of each details page browse (consultation) event.|
|page_browse_consultation_rate|string|The number of button click (consultation) events.|
|value_per_page_browse_consultation|string|The average value returned from button click (consultation) events.|
|total_page_browse_consultation_value|string|The total value returned from button click (consultation) events.|
|button_click_consultation|string|The number of button click (consultation) events.|
|cost_per_button_click_consultation|string|The average cost of each button click (consultation) event.|
|button_click_consultation_rate|string|The percentage of button click (consultation) events out of all click events.|
|value_per_button_click_consultation|string|The average value returned from button click (consultation) events.|
|total_button_click_consultation_value|string|The total value returned from button click (consultation) events.|
|consultation|string|The number of consultation events.|
|cost_per_consultation|string|The average cost of each consultation event.|
|consultation_rate|string|The percentage of consultation events out of all click events.|
|value_per_consultation|string|The average value returned from consultation events.|
|total_consultation_value|string|The total value returned from consultation events.|
|download_detail|string|The number of details page browse (app download) events.|
|cost_per_download_detail|string|The average cost of each details page browse (app download) event.|
|download_detail_rate|string|The percentage of details page browse (app download) events out of all click events.|
|value_per_download_detail|string|The average value returned from details page browse (app download) events.|
|total_download_detail_value|string|The total value returned from details page browse (app download) events.|
|download_button|string|The number of button click (app download) events.|
|cost_per_download_button|string|The average cost of each button click (app download) event.|
|download_button_rate|string|The percentage of button click (app download) events out of all click events.|
|value_per_download_button|string|The average value returned from button click (app download) events.|
|total_download_button_value|string|The average value returned from button click (app download) events.|
|download_start|string|The number of download button click events.|
|cost_per_download_start|string|The average cost of each download button click events.|
|download_start_rate|string|The percentage of download button click events out of all click events.|
|value_per_download_start|string|The average value returned from download button click events.|
|total_download_start_value|string|The total value returned from download button click events.|
|initiate_checkout|string|The number of initiate checkout events.|
|cost_per_initiate_checkout|string|The average cost of each initiate checkout event.|
|initiate_checkout_rate|string|The percentage of initiate checkout events out of all click events.|
|value_per_initiate_checkout|string|The average value returned from initiate checkout events.|
|total_initiate_checkout_value|string|The total value returned from initiate checkout events.|
|add_billing|string|The number of add billing events.|
|cost_per_add_billing|string|The average cost of each add billing event.|
|add_billing_rate|string|The percentage of add billing events out of all click events.|
|value_per_add_billing|string|The average value returned from add billing events.|
|total_add_billing_value|string|The total value returned from add billing events.|
|page_event_search|string|The number of search events.|
|cost_per_page_event_search|string|The average cost of each search event.|
|page_event_search_rate|string|The percentage of search events out of all click events.|
|value_per_page_event_search|string|The average value returned from search events.|
|total_page_event_search_value|string|The total value returned from search events.|
|onsite_shopping_roas|string|The ROAS of complete payment (onsite)|
|onsite_shopping|string|The number of complete payment (onsite) events|
|cost_per_onsite_shopping|string|The average cost of complete payment (onsite) events|
|onsite_shopping_rate|string|The percentage of complete payment (onsite) events out of all click events|
|value_per_onsite_shopping|string|The average value returned from complete payment (onsite) events|
|total_onsite_shopping_value|string|The total value returned from complete payment (onsite) events|
|onsite_initiate_checkout_count|string|The number of onsite initiate checkout events|
|cost_per_onsite_initiate_checkout_count|string|The average cost of onsite initiate checkout events|
|onsite_initiate_checkout_count_rate|string|The percentage of onsite initiate checkout events out of all click events|
|value_per_onsite_initiate_checkout_count|string|The average value returned from onsite initiate checkout events|
|total_onsite_initiate_checkout_count_value|string|The total value returned from onsite initiate checkout events|
|onsite_on_web_detail|string|The number of onsite product details page view events|
|cost_per_onsite_on_web_detail|string|The average cost of onsite product details page view events|
|onsite_on_web_detail_rate|string|The percentage of onsite product details page view events out of all click events|
|value_per_onsite_on_web_detail|string|The average value returned from onsite product details page view events|
|total_onsite_on_web_detail_value|string|The total value returned from onsite product details page view events|
|onsite_add_to_wishlist|string|The number of onsite all to wishlist events|
|cost_per_onsite_add_to_wishlist|string|The average cost of onsite all to wishlist events|
|onsite_add_to_wishlist_rate|string|The percentage of onsite all to wishlist events out of all click events|
|value_per_onsite_add_to_wishlist|string|The average value returned from onsite all to wishlist events|
|total_onsite_add_to_wishlist_value|string|The total value returned from onsite all to wishlist events|
|onsite_add_billing|string|The number of onsite add billing events|
|cost_per_onsite_add_billing|string|The average cost of onsite add billing events|
|onsite_add_billing_rate|string|The percentage of onsite add billing events out of all click events|
|value_per_onsite_add_billing|string|The average value returned from onsite add billing events|
|total_onsite_add_billing_value|string|The total value returned from onsite add billing events|
|onsite_on_web_cart|string|The number of onsite add to cart events|
|cost_per_onsite_on_web_cart|string|The average cost of onsite add to cart events|
|onsite_on_web_cart_rate|string|The percentage of onsite add to cart events out of all click events|
|value_per_onsite_on_web_cart|string|The average value returned from onsite add to cart events|
|total_onsite_on_web_cart_value|string|The total value returned from onsite add to cart events|
|onsite_form|string|The number of onsite form submission events|
|cost_per_onsite_form|string|The average cost of onsite form submission events|
|onsite_form_rate|string|The percentage of onsite form submission events out of all click events|
|value_per_onsite_form|string|The average value returned from onsite form submission events|
|total_onsite_form_value|string|The total value returned from onsite form submission events|
|onsite_download_start|string|The number of onsite app store click events|
|cost_per_onsite_download_start|string|The average cost of onsite app store click events|
|onsite_download_start_rate|string|The percentage of onsite app store click events out of all click events|
|ix_page_view_count|string|The number of onsite page view events|
|cost_per_ix_page_view_count|string|The average cost of an onsite page view event|
|ix_page_view_count_rate|string|The percentage of onsite page view events out of all events|
|ix_button_click_count|string|The number of onsite call-to-action button click events|
|cost_per_ix_button_click_count|string|The average cost of an onsite call-to-action button click event|
|ix_button_click_count_rate|string|The percentage of onsite call-to-action button click events out of all events|
|ix_product_click_count|string|The number of onsite product click events|
|cost_per_ix_product_click_count|string|The average cost of an onsite product click event|
|ix_product_click_count_rate|string|The percentage of onsite product click events out of all events|
```
