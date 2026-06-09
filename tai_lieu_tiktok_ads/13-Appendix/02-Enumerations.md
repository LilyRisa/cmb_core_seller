---
title: "Enumerations"
doc_id: 1737174886619138
path: "Appendix / Enumerations"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Enumerations

# Advertisers
## Advertiser Billing Type
```xtable
|Value {50%}|Description  {50%}|
|---|---|
|TRANS_TYPE_TRANSFER|Transfer|
|TRANS_TYPE_TAX|Tax|
|TRANS_TYPE_COST|Consumption|
```

## Advertiser Display Status (`advertiser_status`)
```xtable
|Value {50%}|Description {50%}|
|---|---|
|SHOW_ACCOUNT_STATUS_NOT_APPROVED|Failed|
|SHOW_ACCOUNT_STATUS_APPROVED|Passed|
|SHOW_ACCOUNT_STATUS_IN_REVIEW|Under review|
|SHOW_ACCOUNT_STATUS_PUNISHED|Punishment|
```

## Advertiser Roles
```xtable
|Value {50%}|Description {50%}|
|---|---|
|ROLE_ADVERTISER|Standard advertiser (direct customer)|
|ROLE_CHILD_ADVERTISER|Standard advertiser (agency sub-account)|
|ROLE_CHILD_AGENT|Secondary agency|
|ROLE_AGENT|Primary agency|
```

## Advertiser Status
```xtable
|Value {50%}|Description {50%}|
|---|---|
|STATUS_DISABLE|The ad account has been closed|
|STATUS_PENDING_CONFIRM|Application pending review|
|STATUS_PENDING_VERIFIED|Pending verification|
|STATUS_CONFIRM_FAIL|Review failed|
|STATUS_ENABLE|Approved|
|STATUS_CONFIRM_FAIL_END|CRM system review failed|
|STATUS_PENDING_CONFIRM_MODIFY|Modifications pending review|
|STATUS_CONFIRM_MODIFY_FAIL|Review of modifications failed|
|STATUS_LIMIT|Ad account under punishment|
|STATUS_WAIT_FOR_BPM_AUDIT|Pending CRM system review|
|STATUS_WAIT_FOR_PUBLIC_AUTH|Pending corporate bank account authentication|
|STATUS_SELF_SERVICE_UNAUDITED|Pending verification of qualifications for self-service account|
|STATUS_CONTRACT_PENDING|Contract has not taken effect|
```

## Business Center Status
```xtable
|Value{50%}|Description{50%}|
|---|---|
|REVIEWING|Under review|
|DENY|Rejected|
|ENABLE|Passed|
|PUNISH|Disabled|
```

## Business Center Type
```xtable
|Value {50%}|Description {50%}|
|---|---|
|NORMAL|Regular Business Center.|
|DIRECT|Non-self-serve Direct Business Center.|
|AGENCY|Non-self-serve Agency Business Center.|
|SELF_SERVICE|Self-serve Direct Business Center.|
|SELF_SERVICE_AGENCY|Self-serve Agency Business Center.|
```

# Ad Management
## Ad Delivery Speed (`pacing`)
```xtable
|Value {50%}|Description {50%}|
|---|---|
|PACING_MODE_SMOOTH|Standard pace, budgets are allocated within the scheduled ad time.|
|PACING_MODE_FAST|Fast paced, consumes budget and produces results as fast as possible.|
```

## Ad group status - secondary status
>  <span style="color:DodgerBlue">**Note**</span><br>
>  See [Supported secondary statuses for a primary status](https://ads.tiktok.com/marketing_api/docs?id=1757239620352002) to learn about the valid secondary statuses you can pass via [/adgroup/get/](https://ads.tiktok.com/marketing_api/docs?id=1739314558673922) for the primary status you specify.
```xtable
| Value {40%} | Description {60%} |
| ---| ---|
|ADGROUP_STATUS_ALL |All statuses (including "Deleted")|
|ADGROUP_STATUS_NOT_DELETE |All statuses (excluding "Deleted")|
|Auction|-|
#|ADGROUP_STATUS_DELETE|Deleted|
#|ADGROUP_STATUS_CAMPAIGN_DELETE|Campaign deleted|
#|ADGROUP_STATUS_ADVERTISER_AUDIT_DENY|Advertiser review failed|
#|ADGROUP_STATUS_ADVERTISER_AUDIT|Advertiser review in progress|
#|ADVERTISER_CONTRACT_PENDING|Advertiser contract has not taken effect|
#|ADVERTISER_ACCOUNT_PUNISH|Advertiser account is punished |
#|ADGROUP_STATUS_CAMPAIGN_EXCEED|Campaign over budget|
#|ADGROUP_STATUS_BUDGET_EXCEED|Ad group over budget|
#|ADGROUP_STATUS_BALANCE_EXCEED|Insufficient account balance|
#| ADGROUP_STATUS_ADGROUP_PRE_ONLINE | The ad group is in a pre-online state and has not been delivered.   |
#|ADGROUP_STATUS_AUDIT_DENY|Ad group review failed|
#|ADGROUP_STATUS_REAUDIT|Review of modifications in progress|
#|ADGROUP_STATUS_AUDIT|New review created|
#|ADGROUP_STATUS_CREATE|New ad group created|
#|ADGROUP_STATUS_FROZEN|Ad group is frozen|
#|ADGROUP_STATUS_NOT_START|The scheduled delivery time has not started|
#|ADGROUP_STATUS_LIVE_NOT_START|The scheduled delivery time has started but Live has not yet started. The status is for auction ad groups only.|
#|ADGROUP_STATUS_CAMPAIGN_DISABLE|Campaign suspended|
#|ADGROUP_STATUS_DISABLE|Suspended|
#|ADGROUP_STATUS_SHADOW_ADGROUP_REAUDIT|Ad group in re-review|
#|ADGROUP_STATUS_PIXEL_UNBIND|The associated pixel is unbound |
#|ADGROUP_STATUS_PARTIAL_AUDIT_NO_DELIVERY|Ad group in partial review and not in delivery |
#|ADGROUP_STATUS_PARTIAL_AUDIT_DELIVERY_OK|Ad group in partial review and in delivery|
#|ADGROUP_STATUS_INDUSTRY_QUALIFICATION_MISSING|Industry qualification is missing, please submit|
#|ADGROUP_STATUS_INDUSTRY_QUALIFICATION_EXPIRED|Industry qualification is expired, please re-submit|
#|ADGROUP_STATUS_INDUSTRY_QUALIFICATION_DENY|Industry qualification is rejected|
#|ADGROUP_STATUS_ASSET_AUTHORIZATION_LOST|Authorization for the asset or assets in the ad group is missing. <br><br><p><span style="color:darkred"><b>Note</b></span>: This status cannot be used as filter.|
#|ADGROUP_STATUS_TRANSCODING_FAIL|Transcoding of the video or videos in the ad group failed. <br><br><p><span style="color:darkred"><b>Note</b></span>: This status cannot be used as filter.|
#|ADGROUP_STATUS_ADGROUP_QUOTA_LIMIT|The ad account has reached the ad group quota limit. <br>To create an active ad group, you need to pause or delete an ad group with the "active "or "in review" status.<br><br><p><span style="color:darkred"><b>Note</b></span>: This status cannot be used as filter.|
#| ADGROUP_STATUS_IDENTITY_USED_BY_GMV_MAX_AD | The identity that you try to use in an ad within the ad group is unavailable because it is already used by an ad group with the optimization goal GMV (`optimization_goal` =`GMV`). |
#| ADGROUP_STATUS_SEARCH_KEYWORDS_IN_REVIEW | All keywords for the ad group within a Search Campaign are under review. |
#| ADGROUP_STATUS_SEARCH_KEYWORDS_NOT_AVAILABLE | All keywords for the ad group within a Search Campaign are rejected, or no keywords are set. |
#| ADGROUP_STATUS_SEARCH_KEYWORDS_PARTIAL_APPROVED | Some keywords for the ad group within a Search Campaign are approved while other keywords are in review or disapproved. |
|Auction or R&F|-|
#|ADGROUP_STATUS_DELIVERY_OK|Advertising in progress|
#|ADGROUP_STATUS_REVIEW_PARTIALLY_APPROVED | One or more ads have been rejected |
#|ADGROUP_STATUS_TIME_DONE|Completed <br><br><p><span style="color:darkred"><b>Note</b></span>: This status indicates the delivery of the ad group ended at the scheduled time.|
|R&F|-|
#|ADGROUP_STATUS_RF_DEDUCTION_FAILED|Deduction failed for the R&F ad group|
#|ADGROUP_STATUS_RF_NO_VALID_CREATIVE|No valid creatives in the R&F ad group|
#|ADGROUP_STATUS_RF_CLOSED_OTHERS|The R&F ad group is closed|
#|ADGROUP_STATUS_RF_SHORT_BALANCE|Not enough balance in the R&F ad group|
#|ADGROUP_STATUS_RF_BOOKING|Budget / inventory has been booked for this R&F ad group|
#|ADGROUP_STATUS_RF_NO_DELIVERY_CREATIVE|No creatives in this R&F ad group|
#|ADGROUP_STATUS_RF_SCHEDULE|A schedule has been created for the R&F ad group|
#|ADGROUP_STATUS_RF_TERMINATE|The R&F ad group is terminated|
#|ADGROUP_STATUS_RF_AD_AUDIT_DENY|The R&F ad is rejected|
#|ADVERTISER_ACCOUNT_INVALID|The advertiser account is invalid|
#|ADGROUP_STATUS_RF_ADGROUP_INVALID|The R&F ad group doesn't exist |
#|ADGROUP_STATUS_RF_WITHDRAW_ORDER|The R&F order is withdrawn|
#|ADGROUP_STATUS_RF_TIME_DONE|The R&F ad group is completed <br><br><p><span style="color:darkred"><b>Note</b></span>:   This status indicates the delivery of the R&F ad group ended before the scheduled time due to insufficient budget or ad account balance.|
| Promote | |
#| ADGROUP_STATUS_PROMOTE_AD_NOT_APPROVED | One or more creatives in the Promote ad group are not approved. |
#| ADGROUP_STATUS_PROMOTE_WITHDRAW_ORDER | The Promote order is withdrawn. |
```
## Ad Group Review Appeal Status (`appeal_status`)
```xtable
|Value {50%}|Description {50%}|
|---|---|
|NOT_APPEALED|No appeal is started|
|APPEALING|Appeal in progress|
|APPEAL_SUCCESSFUL|Appeal is successful|
|APPEAL_FAILED|Appeal failed|
|APPEAL_DONE|After the appeal was successful, the ad group was reviewed again and the review status was updated accordingly.|
```



## Ad status - secondary status
>  <span style="color:DodgerBlue">**Note**</span><br>
>  See [Supported secondary statuses for a primary status](https://ads.tiktok.com/marketing_api/docs?id=1757239620352002) to learn about the valid secondary statuses you can pass via [/ad/get/](https://ads.tiktok.com/marketing_api/docs?id=1735735588640770) for the primary status you specify.
```xtable
| Value {40%} | Description {60%} |
| --- | --- |
|AD_STATUS_ALL |All statuses (including "Deleted") |
|AD_STATUS_NOT_DELETE |All statuses (excluding "Deleted") |
|AD_STATUS_CAMPAIGN_DELETE|Campaign deleted|
|AD_STATUS_ADGROUP_DELETE|Ad group deleted|
|AD_STATUS_DELETE|Deleted|
|AD_STATUS_ADVERTISER_AUDIT_DENY|Account review failed|
|AD_STATUS_ADVERTISER_AUDIT|Account review summary released|
|ADVERTISER_CONTRACT_PENDING|Advertiser contract has not taken effect|
|ADVERTISER_ACCOUNT_PUNISH|Advertiser account is punished|
|AD_STATUS_BALANCE_EXCEED|Insufficient account balance|
|AD_STATUS_CAMPAIGN_EXCEED|Campaign over budget|
|AD_STATUS_BUDGET_EXCEED|Ad group over budget|
| AD_STATUS_AD_PRE_ONLINE| The ad is in a pre-online state and has not been delivered      |
|AD_STATUS_AUDIT|Under review|
|AD_STATUS_REAUDIT|Modifications under review|
|AD_STATUS_AUDIT_DENY|Review failed|
|AD_STATUS_ADGROUP_AUDIT_DENY|Ad group review failed|
|AD_STATUS_ADGROUP_PARTIAL_AUDIT_NO_DELIVERY|Ad in partial review  and not in delivery  |
|AD_STATUS_REVIEW_PARTIALLY_APPROVED|One or more ad creatives have been rejected |
|AD_STATUS_PARTIAL_AUDIT_DELIVERY_OK|Ad in partial review and in delivery|
|AD_STATUS_PARTIAL_AUDIT_NO_DELIVERY|Ad creative in partial review and not in delivery  |
|AD_STATUS_ADGROUP_INDUSTRY_QUALIFICATION_MISSING|Industry qualification is missing, please submit |
|AD_STATUS_ADGROUP_INDUSTRY_QUALIFICATION_EXPIRED|Industry qualification is expired, please re-submit |
|AD_STATUS_ADGROUP_INDUSTRY_QUALIFICATION_DENY|Industry qualification is rejected |
|AD_STATUS_MUSIC_AUTHORIZATION_MISSING|Music authorization is missing |
|AD_STAUS_PIXEL_UNBIND|The associated pixel is unbound|
|AD_STATUS_LIVE_OFFLINE|Live is not yet started|
|AD_STATUS_NOT_START|The scheduled delivery time has not started|
|AD_STATUS_DONE|Completed|
|AD_STATUS_CAMPAIGN_DISABLE|Campaign suspended|
|AD_STATUS_ADGROUP_DISABLE|Ad group suspended|
|ADGROUP_STATUS_FROZEN|Ad group is frozen |
|AD_STATUS_DISABLE|Ad suspended|
|AD_STATUS_DELIVERY_OK|Advertising in progress|
|AD_STATUS_PROCESS_AUDIO|Audio processing in progress<br><p><span style="color:darkred"><b>Note</b></span>: If the status `AD_STATUS_PROCESS_AUDIO` lasts (message "Processing audio, if this lasts long check if your creative has audio, or upload another video file to fix" displayed in interface) for a long time, retry by uploading a new video with audio to fix the issue.|
|AD_STATUS_TRANSCODING_FAIL |Transcoding of the video or videos in the ad failed.<br><p><span style="color:darkred"><b>Note</b></span>: This status cannot be used as filter. |
|AD_STATUS_ADGROUP_ASSET_AUTHORIZATION_LOST|Authorization for the asset or assets in the ad group is missing.<br><p><span style="color:darkred"><b>Note</b></span>: This status cannot be used as filter. |
|AD_STATUS_ASSET_AUTHORIZATION_LOST|Authorization for the asset or assets in the ad is missing.<br><p><span style="color:darkred"><b>Note</b></span>: This status cannot be used as filter. |
|AD_STATUS_AD_QUOTA_LIMIT|The ad group where you want to create the ad has been deactivated because the ad account has reached the ad group quota limit. <br>To create an active ad, you need to pause or delete an ad group with the "active "or "in review" status and then create the ad in a new ad group.<br><p><span style="color:darkred"><b>Note</b></span>: This status cannot be used as filter.|
| AD_STATUS_PRODUCT_UNAVAILABLE | The product that you try to use in the ad is unavailable. |
| AD_STATUS_ANCHOR_UNAVAILABLE | The product link that you try to use in the ad is unavailable. |
| AD_STATUS_NO_AUTHORIZATION_OF_SHOWCASE | The identity of the Showcase that you try to use in the ad is unavailable. |
|AD_STATUS_RF_ADGROUP_CLOSED|The R&F ad group is closed  |
| AD_STATUS_PROMOTE_AD_OFFLINE_AUDIT| One or more creatives in the Promote ad are not approved. |
| AD_STATUS_PROMOTE_ADGROUP_CLOSED | The Promote ad group is closed. |
```
## Advertising Objective (`objective_type`)

```xtable
| Value {40%} | Description {60%} |
| --- | ---|
| APP_PROMOTION | Get people to install your mobile app and re-engage your app's current users.  |
|WEB_CONVERSIONS | Send people to your website, or TikTok Instant Page, to perform a specific action like making a purchase or adding an item to their cart. |
| APP_INSTALL {-Deprecated}| Get more people to install your app. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>New features will be added under the new App promotion objective, but not for the App Installs objective. We recommend that you use the new objective [App promotion](https://ads.tiktok.com/marketing_api/docs?id=1747553433902082) instead. </li><li>Ongoing App Install objective campaigns created before October 27th 2023 will not be impacted, and can run until the budget exhausts. You can pause or delete these campaigns, ad groups, ads, and ACO ads via API anytime.</li></ul>|
| CONVERSIONS{-Deprecated}| Drive valuable actions on your website or app. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>New features will be added under the new Website conversions objective, but not for the Conversions objective. We recommend that you use the new objective [Website conversions](https://ads.tiktok.com/marketing_api/docs?id=1747553507237889) instead. </li><li>Ongoing Conversions objective campaigns created before October 27th 2023 will not be impacted, and can run until the budget exhausts. You can pause or delete these campaigns, ad groups, ads, and ACO ads via API anytime.</li></ul>|
| REACH | Auction reach. <br>Show your Auction ads to the maximum number of people.|
|BRAND_CONSIDERATION|Brand consideration. Acquire a consideration audience who have demonstrated interest in your brand.<br><br><p><span style="color:darkred"><b>Note</b></span>: Currently, creating Brand Consideration campaigns via API is not supported. This value is only available in [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986), both as an `objective_type` filter in the request and an identifier of Brand Consideration campaigns in the response.|
| TRAFFIC | Send more people to a destination on your website or app. |
| VIDEO_VIEWS | Get more people to view your video content. |
| CATALOG_SALES (allowlisted){-Deprecated} | Dynamic Showcase Ads that attract users to buy your products in the catalog. <br>Note: Starting from Feb.16, 2023, you can no longer create Dynamic Showcase Ads under Catalog Sales objective. To ensure a smooth API integration, we recommend you migrate to [Video Shopping Ads](https://ads.tiktok.com/marketing_api/docs?id=1741942291730434&rid=tv5sdeafqp)  under Product Sales objective.  |
| PRODUCT_SALES | Offer a more straightforward way for Commerce advertisers to deliver ads and sell products from TikTok Shop, websites, and apps. <br><br><p><span style="color:darkred"><b>Note</b></span>: [Video Shopping Ads (for Catalog)](https://business-api.tiktok.com/portal/docs?id=1750361698613249) is currently an allowlist-only feature for Commerce App advertisers. If you are a Commerce App advertiser or a non-Commerce advertiser and would like to access it, please contact your TikTok representative. If you are a Web Commerce advertiser, you automatically get access to the feature.|
| ENGAGEMENT | Get more follows or profile visits. |
| LEAD_GENERATION| Collect leads for your business or brand. |
|SHOP_PURCHASES {-Deprecated}| Drive users to purchase products in your shop on TikTok. |
|RF_APP_INSTALL (allowlisted) {-Deprecated}| Get more people to install your app via R&F ads|
|RF_ENGAGEMENT (allowlisted){-Deprecated}| Get more follows or profile visits via R&F ads|
|RF_REACH (allowlisted) | Reservation Reach & Frequency or Reservation TikTok Pulse. <br><br> Show your ads reserved in advance with a guaranteed reach and frequency or ads placed after the Top 4% trending content on the For You page at a fixed CPM to as many people as possible. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>To use the Reach & Frequency objective, set `objective_type` to `RF_REACH` and `rf_campaign_type` to `STANDARD` using [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602). </li><li>To use the TikTok Pulse objective, set `objective_type` to `RF_REACH` and `rf_campaign_type` to `PULSE` using [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602).</li></ul>|
| TOPVIEW_REACH | Reservation TopView.   <br><br>Show your ads designed to be shown when users open the app to as many people as possible.   <br><br><p><span style="color:darkred"><b>Note</b></span>:   <ul><li>This enum value cannot be used as a filter for the `objective_type` parameter in [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986), [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922), and [/ad/get/](https://business-api.tiktok.com/portal/docs?id=1735735588640770) to retrieve TopView data. Instead, set the filter field `buying_types` to `&#91;"RESERVATION_TOP_VIEW"&#93;` to filter TopView campaigns, ad groups, and ads.</li><li>Creating, updating, and copying TopView ads is not supported through the API. You can only retrieve information about existing TopView ads via the API. To learn more about the availability of TopView ads in the API, see [TopView](https://business-api.tiktok.com/portal/docs?id=1804258360285186).  </li></ul> |
|RF_TRAFFIC (allowlisted)<br>{-Deprecated}| Get more people to visit your website or app via R&F ads.<br><p><span style="color:darkred"><b>Note</b></span>: Video View and Traffic objectives of Reach and Frequency is no longer be available after June 1st, 2023. Existing orders reserved before this date will continue to be delivered, edited or terminated, but the scheduled end date of the reserved campaigns cannot be later than January 12th, 2024.|
|RF_VIDEO_VIEW (allowlisted) <br>{-Deprecated}| Get more people to view your video content via R&F ads. <br><p><span style="color:darkred"><b>Note</b></span>: Video View and Traffic objectives of Reach and Frequency is no longer be available after June 1st, 2023. Existing orders reserved before this date will continue to be delivered, edited or terminated, but the scheduled end date of the reserved campaigns cannot be later than January 12th, 2024.|
```

## Age Group (`age_groups`)

```xtable
|Value {50%}|Description {50%}|
|---|---|
|AGE_13_17|13-17 years old|
|AGE_18_24|18-24 years old|
|AGE_25_34|25-34 years old|
|AGE_35_44|35-44 years old|
|AGE_45_54|45-54 years old|
|AGE_55_100|55+|
```

## App Retargeting Audience Type (`audience_type` for `/adgroup/create/`)
```xtable
|Value {50%}|Description {50%}|
|---|---|
|CUSTOM_AUDIENCE|Customize your audience. Not supported for `TRAFFIC` objective.|
|EXCLUDE_INTERACT_USERS|Exclude audience who have interacted with your App.|
|INCLUDE_INTERACT_USERS|Include audience who have interacted with your App.|
|INCLUDE_SPECIFIC_EVENTS|Include audience who satisfy specific `audience_rule`.|
|EXCLUDE_SPECIFIC_EVENTS|Exclude audience who satisfy specific `audience_rule`.|
|NEW_CUSTOM_AUDIENCE|New custom audience type. It is the only supported app retargeting audience type for new ad groups.|
```

## Bidding Strategy (`bid_type`)
You can specify your bidding strategy when setting up an ad. Each bidding strategy has unique advantage to best fit your business objectives. `BID_TYPE_CUSTOM` is recommended if you value cost stability, although you may not be able to effectively utilize your budget. For details about different bidding strategies, please visit [Biding Stategies](https://ads.tiktok.com/help/article?aid=6731899932019523590) for more.
```xtable
|Value {50%}|Description {50%}|
|---|---|
|BID_TYPE_CUSTOM|Manual bidding. This bidding strategy is called "Cost Cap" in TikTok Ads Manager.|
|BID_TYPE_NO_BID|Disable bid control, spending your budget fully and getting the maximum possible results. This bidding strategy is called "Maximum Delivery" in TikTok Ads Manager.|
```

## Billing Event (`billing_event` or `billing_events`)
```xtable
|Value {50%}|Description {50%}|
|---|---|
|CPC|CPC|
|CPM|CPM|
|CPV|CPV|
|OCPC   deprecated|oCPC (CPA is equal to oCPC)|
|GD|Guaranteed Delivery|
|OCPM|oCPM|
```

## Budget Mode (`budget_mode`)

For details, see [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114).

```xtable
| Value {40%} | Description {60%} |
| --- | ---|
| BUDGET_MODE_DAY | Daily budget |
| BUDGET_MODE_DYNAMIC_DAILY_BUDGET (allowlisted)| Dynamic daily budget <br><p><span style="color:darkred"><b>Note</b></span>: `BUDGET_MODE_DYNAMIC_DAILY_BUDGET` for campaign objectives `REACH`, `VIDEO_VIEWS`, and `ENGAGEMENT` is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. On the other hand, `BUDGET_MODE_DYNAMIC_DAILY_BUDGET` for campaign objectives `TRAFFIC`, `APP_PROMOTION`, `WEB_CONVERSIONS`, `LEAD_GENERATION`, and `PRODUCT_SALES` (Video Shopping Ads only) is generally available. |
| BUDGET_MODE_TOTAL | Lifetime budget |
| BUDGET_MODE_INFINITE | Unlimited budget |
```

## Call-to-action (`call_to_action`)
```xtable
|Value {50%}|Description {50%}|
|---|---|
|APPLY_NOW|Apply now|
|BOOK_NOW|Book now|
|CALL_NOW|Call now|
|CHECK_AVAILABLILITY|Check availability|
|CONTACT_US|Contact us|
|DOWNLOAD_NOW|Download|
|EXPERIENCE_NOW|Experience now|
|GET_QUOTE|Get quote|
|GET_SHOWTIMES|Get showtimes|
|GET_TICKETS_NOW|Get tickets now|
|INSTALL_NOW|Install now|
|INTERESTED|Interested|
|LEARN_MORE|Learn more|
|LISTEN_NOW|Listen now|
|ORDER_NOW|Order now|
|PLAY_GAME|Play game|
|PREORDER_NOW|Pre-order now|
|READ_MORE|Read more|
|SEND_MESSAGE|Send message|
|SHOP_NOW|Shop now|
|SIGN_UP|Sign up|
|SUBSCRIBE|Subscribe|
|VIEW_NOW|View now|
|VIEW_PROFILE|View profile (Only supported in ads with the advertising objective `ENGAGEMENT`)|
|VISIT_STORE|Visit store|
|WATCH_LIVE|Watch LIVE (Only supported in LIVE ads)|
|WATCH_NOW|Watch now|
|JOIN_THIS_HASHTAG|Join this hashtag (Only supported in R&F ads)|
|SHOOT_WITH_THIS_EFFECT|Shoot with this effect (Only supported in R&F ads)|
|VIEW_VIDEO_WITH_THIS_EFFECT|View video with this effect (Only supported in R&F ads)|
```

## Campaign Status - Secondary Status
>  <span style="color:DodgerBlue">**Note**</span><br>
>  See [Supported secondary statuses for a primary status](https://ads.tiktok.com/marketing_api/docs?id=1757239620352002) to learn about the valid secondary statuses you can pass via [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986) for the primary status you specify.

```xtable
| Value {40%} | Description {60%} |
| ---| ---|
|CAMPAIGN_STATUS_DELETE |Deleted|
|CAMPAIGN_STATUS_ADVERT ISER_AUDIT_DENY |Advertiser review failed|
|CAMPAIGN_STATUS_ADVERT ISER_AUDIT |Advertiser review in progress|
|ADVERTISER_CONTRACT_PENDING| Advertiser contract has not taken effect|
|ADVERTISER_ACCOUNT_PUNISH| Advertiser account is punished |
|CAMPAIGN_STATUS_BUDGET_EXCEED| Over budget|
|CAMPAIGN_STATUS_DISABLE| Suspended|
|CAMPAIGN_STATUS_AWAITING_RELEASE |Awaiting release. <br>The SKAN 4.0 Dedicated Campaign is under postback. We recommend that you wait a few days to release this campaign for reporting accuracy. |
| CAMPAIGN_STATUS_IDENTITY_USED_BY_GMV_MAX_AD | The identity that you try to use in an ad within the campaign is unavailable because it is already used by an ad group with the optimization goal GMV (`optimization_goal` =`GMV`). |
|CAMPAIGN_STATUS_ENABLE| Advertising in progress|
|CAMPAIGN_STATUS_ALL| All statuses (including "Deleted")|
|CAMPAIGN_STATUS_NOT_DELETE| All statuses (excluding "Deleted")|
| CAMPAIGN_STATUS_TTS_TT_ASSET_UNAVAILABLE| The asset used in your campaign is not available. Check campaign settings. |
| CAMPAIGN_STATUS_TTS_TT_IDENTITY_UNAVAILABLE| TikTok account not available. Try to reconnect or choose another account to create ads. |
|CAMPAIGN_STATUS_TIKTOK_SHOP_UNAVAILABLE | Shop is unconnected from a Business Center account. Reconnect to a shop. |
| CAMPAIGN_STATUS_REVIEW_DISAPPROVED | Disapproved. |
| CAMPAIGN_STATUS_AD_UNAVAILABLE | The whole campaign was paused because one or more of the ad groups was rejected or some assets were unavailable. |
| CAMPAIGN_STATUS_PRODUCT_UNAVAILABLE| The selected products are not available after the campaign is created. Go to Seller Center to manage or add products. |
| CAMPAIGN_STATUS_LIVE_GMV_MAX_AUTHORIZATION_CANCEL | This campaign is paused because the selected official TikTok account has granted GMV Max authorization to another ad account. |
| CAMPAIGN_STATUS_PRODUCT_GMV_MAX_AUTHORIZATION_CANCEL | This campaign is paused because the TikTok Shop has granted GMV Max authorization to another ad account. |
| CAMPAIGN_STATUS_IDENTITY_USED_BY_LIVE_GMV_MAX | This campaign is paused due to an active LIVE GMV max campaign which uses this TikTok account. |
| CAMPAIGN_STATUS_PRODUCT_USED_BY_PRODUCT_GMV_MAX | This campaign is paused due to an active Product GMV max campaign created for this shop. |
```

## Connection Type (`network_types`)
```xtable
|Value {50%}|Description {50%}|
|---|---|
|WIFI|wifi|
|2G|2G|
|3G|3G|
|4G|4G|
|5G|5G|
```

## Conversion Window
```xtable
|Value {50%}|Description {50%}|
|---|---|
|ONE_DAY_CLICK|1 day after clicking|
|SEVEN_DAY_CLICK|7 day after clicking|
|ONE_DAY_CLICK_OR_ONE_DAY_VIEW|1 day after clicking or viewing|
|SEVEN_DAY_CLICK_OR_ONE_DAY_VIEW|7 days after clicking or 1 day after viewing|
```

## Creative Type (`ad_format`)
```xtable
|Value {50%}|Description {50%}|
|---|---|
|SINGLE_IMAGE|A single image.|
|SINGLE_VIDEO|A single video.|
|CAROUSEL   Deprecated|Carousel of images for TopBuzz placement.|
|LIVE_CONTENT|Live events. Valid only for live shopping scenarios (`promotion_type` = `LIVE_SHOPPING`).|
|CAROUSEL_ADS|Carousel of images for TikTok placement.|
```

## Creative Type (Deprecated)
`image_mode` has been deprecated. The enum values below are just for reference.
```xtable
|Value{20%}|Description {20%}|Aspect ratio {15%}|Minimal Size{15%}|Maximal Size{15%}|Recommend Size{15%}|
|---|---|---|---|---|---|
|IMAGE_MODE_GIF|GIF (cannot be used to create/update ads)|-|-|-|-|
|IMAGE_MODE_GROUP|Group of images (cannot be used to create/update ads)|-|-|-|-|
|IMAGE_MODE_LARGE|Horizontal large image|1.91|600 * 314|2400 * 1256|1200 * 628|
|IMAGE_MODE_LARGE_VERTICAL|Vertical large image|0.62|187 * 302|750 * 1208|375 * 604|
|IMAGE_MODE_SMALL|Small image (cannot be used to create/update ads)|-|-|-|-|
|IMAGE_MODE_BIG_ONE|Square image|1|320 * 320|1280 * 1280|640 * 640|
|IMAGE_MODE_VIDEO|Horizontal video|1.78|1280 * 720|2560 * 1400|1280 * 720|
|IMAGE_MODE_VIDEO_SQUARE|Square video|1|640 * 640|1280 * 1280|640 * 640|
|IMAGE_MODE_VIDEO_VERTICAL|Vertical video|0.56|720 * 1280|1440 * 2560|720 * 1280|
|MULTI_SQUARE_PICTURES|Multiple Square Pictures|1|640 * 640|640 * 640|640 * 640|
|MULTI_RECTANGLE_PICTURES|Multiple Rectangular Pictures|1.91|1200 * 628|1200 * 628|1200 * 628|
```

## Creative Types for Shop Purchases (`creative_type`)
Creative type for shop purchases scenarios. Required when `objective_type` is `SHOP_PURCHASES`.
```xtable
|Value {50%}|Description {50%}|
|---|---|
|SHOP_PDP|Shop ads with a single product link. `ad_format` must be `SINGLE_VIDEO`.|
|SHOP_PLP|Shop ads with 6-50 product links. `ad_format` must be `SINGLE_VIDEO`.|
|SHORT_VIDEO_LIVE|Short videos that direct traffic to live shopping room. `ad_format` must be `SINGLE_VIDEO`.|
|DIRECT_LIVE|Live shopping room. Each campaign can have only one live shopping room. `ad_format` must be `LIVE_CONTENT`.|
```

## Deep Event Bidding Strategy (`deep_bid_type`)

You may optimize your in-app events with different bidding methods. The table below explains the optional values.

```xtable
| Value {30%} | Description {70%} |
| --- | ---|
| DEFAULT | No deep event bid |
| MIN | Double bid. Operation Method: TikTok Ads platform will bid to keep your average cost around your target, including installation and target costs. Recommended for: Maintaining a stable cost on your bid for both installation costs and your target costs. <br>`Note` This might cause a lower install volume. |
| PACING | Automatic optimization. Operation method: TikTok Ads platform will bid to keep your average installation cost around your target goal, while aiming to get you the maximum number of target events. <br>Recommended for: Maintaining a stable cost around your installation bid and getting an optimized cost for your target goal.<br>`Note` The cost for your target goal might be higher than that from Multi Bid mode. |
|VO_MIN{-Deprecated}| Bid with a cost cap. Valid only for Value Optimization (`optimization_goal` = `VALUE`). For more details, see [Value Optimization](https://ads.tiktok.com/marketing_api/docs?id=1739381743067137).|
|VO_MIN_ROAS| Bid with a ROAS goal. Valid only for Value Optimization (`optimization_goal` = `VALUE`). For more details, see [Value Optimization](https://ads.tiktok.com/marketing_api/docs?id=1739381743067137).|
|VO_HIGHEST_VALUE| Bid without cost cap or ROAS goal. Valid only for Value Optimization (`optimization_goal` = `VALUE`). For more details, see [Value Optimization](https://ads.tiktok.com/marketing_api/docs?id=1739381743067137).|
|AEO|Bid for App Event Optimization. |
```

## Gender (`gender`)
```xtable
|Value {50%}|Description {50%}|
|---|---|
|GENDER_UNLIMITED|No limitation|
|GENDER_MALE|Male|
|GENDER_FEMALE|Female|
```

## Language Code (`languages`)
Use [/tool/language/](https://ads.tiktok.com/marketing_api/docs?id=1737188554152962) endpoint to get the latest list of language codes.
```xtable
|Value {50%}|Description {50%}|
|---|---|
|ar|Arabic|
|as|Assamese|
|bgc|Haryanavi|
|bh|Bihari|
|bn|Bengali|
|cs|Czech|
|de|German|
|el|Greek|
|en|English|
|es|Spanish|
|fi|Finnish|
|fr|French|
|gu|Gujarati|
|he|Hebrew|
|hi|Hindi|
|hu|Hungarian|
|id|Indonesian|
|it|Italian|
|ja|Japanese|
|kn|Kannada|
|ko|Korean|
|ml|Malayalam|
|mr|Marathi|
|ms|Malay|
|nl|Dutch|
|or|Oriya|
|pa|Punjabi|
|pl|Polish|
|pt|Portuguese|
|raj|Rajasthani|
|ro|Romanian|
|ru|Russian|
|sv|Swedish|
|ta|Tamil|
|te|Telugu|
|th|Thai|
|tr|Turkish|
|uk|Ukrainian|
|vi|Vietnamese|
|zh|Simplified Chinese|
|zh-Hant|Traditional Chinese|
```

## Minimum Android Version (`min_android_version`)
Use [/tool/os_version/](https://ads.tiktok.com/marketing_api/docs?id=1738308662898689) to get the latest list of Android or iOS versions.
```xtable
|Value {50%}|Description {50%}|
|---|---|
|NONE|No limitation|
|4.0|Android 4.0|
|4.1|Android 4.1|
|4.2|Android 4.2|
|4.3|Android 4.3|
|4.4|Android 4.4|
|5.0|Android 5.0|
|5.1|Android 5.1|
|6.0|Android 6.0|
|7.0|Android 7.0|
|7.1|Android 7.1|
|8.0|Android 8.0|
|8.1|Android 8.1|
|9.0|Android 9.0|
|10.0|Android 10.0|
|10.1|Android 10.1|
|10.2|Android 10.2|
|10.3|Android 10.3|
|10.5|Android 10.5|
|10.6|Android 10.6|
|11.0|Android 11.0|
|12.0|Android 12.0|
|13.0|Android 13.0|
```

## Minimum iOS Version (`min_ios_version`)
Use [/tool/os_version/](https://ads.tiktok.com/marketing_api/docs?id=1738308662898689) to get the latest list of Android or iOS versions.
```xtable
|Value {50%}|Description {50%}|
|---|---|
|NONE|No limitation|
|8.0|iOS 8.0|
|8.1|iOS 8.1|
|8.2|iOS 8.2|
|8.3|iOS 8.3|
|8.4|iOS 8.4|
|9.0|iOS 9.0|
|9.1|iOS 9.1|
|9.2|iOS 9.2|
|9.3|iOS 9.3|
|10.1|iOS 10.1|
|10.2|iOS 10.2|
|10.3|iOS 10.3|
|11.0|iOS 11.0|
|11.1|iOS 11.1|
|11.2|iOS 11.2|
|11.3|iOS 11.3|
|11.4|iOS 11.4|
|12.0|iOS 12.0|
|12.1|iOS 12.1|
|12.2|iOS 12.2|
|12.3|iOS 12.3|
|12.4|iOS 12.4|
|13.0|iOS 13.0|
|13.1|iOS 13.1|
|13.2|iOS 13.2|
|13.3|iOS 13.3|
|13.4|iOS 13.4|
|13.5|iOS 13.5|
|13.6|iOS 13.6|
|13.7|iOS 13.7|
|14.0|iOS 14.0|
|14.1|iOS 14.1|
|14.2|iOS 14.2|
|14.3|iOS 14.3|
|14.4|iOS 14.4|
|14.5|iOS 14.5|
|14.6|iOS 14.6|
|14.7|iOS 14.7|
|14.8|iOS 14.8|
|15.0|iOS 15.0|
|15.1|iOS 15.1|
|15.2|iOS 15.2|
|15.3|iOS 15.3|
|15.4|iOS 15.4|
|15.5|iOS 15.5|
|15.6|iOS 15.6|
|16.0|iOS 16.0|
|16.1|iOS 16.1|
|16.2|iOS 16.2|
|16.3|iOS 16.3|
|16.4|iOS 16.4|
```

## Operating System (`operating_systems`)
```xtable
|Value{50%}|Description{50%}|
|---|---|
|ANDROID|android|
|IOS|iOS|
|PCDeprecated|PC|
```

## Optimization Goal (`optimization_goal`)
```xtable
| Value {40%} | Description {60%} |
| --- | --- |
| CLICK | Click/Clicks. <br><br>`CLICK` corresponds to the optimization goal Clicks in any of the following scenarios: <ul><li>`shopping_ads_type` is `VIDEO` and `product_source` is `STORE` or `SHOWCASE`. </li><li> `shopping_ads_type` is `LIVE`.</li><li>`shopping_ads_type` is `PRODUCT_SHOPPING_ADS` and `product_source` is `STORE`. </li></ul> |
| CONVERT | Conversion/Purchases/Initiate checkouts. <br><br>`CONVERT` (its `optimization_event` is `SHOPPING`) corresponds to the optimization goal Purchases in any of the following scenarios: <ul><li>`shopping_ads_type` is `VIDEO` and `product_source` is `STORE` or `SHOWCASE`.</li><li>`shopping_ads_type` is `LIVE`.</li><li>`shopping_ads_type` is `PRODUCT_SHOPPING_ADS` and `product_source` is `STORE`.</li></ul><br>`CONVERT` (its `optimization_event` is `INITIATE_ORDER`) corresponds to the optimization goal Initiate checkouts in any of the following scenarios: <ul><li> `shopping_ads_type` is `VIDEO` and `product_source` is `STORE` or `SHOWCASE`. </li><li>`shopping_ads_type` is `LIVE`.</li></ul>|
| INSTALL | Install. <br><br>  To use the optimization goal "Install with in-app event" on Pangle placement or Global App Bundle placement or both in an ad group, set `optimization_goal` to `INSTALL`, specify a valid `secondary_optimization_event` value, and only include `PLACEMENT_PANGLE` or `PLACEMENT_GLOBAL_APP_BUNDLE` or both in the `placements` value.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Starting November 30th, 2024, you will no longer be able to create or copy ad groups with the optimization goal "Install with in-app event" on TikTok placement or Automatic Placement. This change affects ad groups with the following confiftguration: <ul><li>At the campaign level: `objective_type` is `APP_PROMOTION`</li><li>At the ad group level:<ul><li>`optimization_goal` is `INSTALL` and a valid `secondary_optimization_event` value is specified </li><li>`placement_type` is `PLACEMENT_TYPE_NORMAL` with `placements` as `["PLACEMENT_TIKTOK"]`, or `placement_type` is `PLACEMENT_TYPE_AUTOMATIC` </li></ul> </li><li>Existing "Install with in-app event" ad groups on TikTok placement will not be affected. Additionally, Pangle placement and Global App Bundle placement (where `placement_type` is `PLACEMENT_TYPE_NORMAL` and `placements` includes only `PLACEMENT_PANGLE` or `PLACEMENT_GLOBAL_APP_BUNDLE` or both) will not be impacted. Please be aware of this change and make appropriate adjustments to your integration if necessary.</li></ul></li></ul> |
| IN_APP_EVENT | In-app event |
| SHOW | Impression |
| VIDEO_VIEW{-Deprecated} |Play video. |
| REACH | Reach |
| LEAD_GENERATION | Leads |
| CONVERSATION | Conversations. Show your ads to people that are most likely to start a TikTok direct message conversation or conversation in an Instant Messaging App.<br>This optimization goal has a greater chance to achieve better performance than `CLICK` as it optimizes for deeper conversions. <br><br><ul><li>To learn about how to use Conversations in TikTok Direct Messaging Ads, see [Create a Lead Generation ad with optimization location as TikTok direct messages](https://business-api.tiktok.com/portal/docs?id=1774482992153601).</li><li>To learn about how to use Conversations in Instant Messaging Ads, see [Create a Lead Generation ad with optimization location as instant messaging apps](https://business-api.tiktok.com/portal/docs?id=1774482999036930). </li></ul> <br><p><span style="color:darkred"><b>Note</b></span>:<ul><li> The optimization goal Conversations (`CONVERSATION`) is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li><li> Once you set `optimization_goal` to `CONVERSATION`, `optimization_event` can be omitted and will default to `MESSAGE`. If you set a different value in the `optimization_event` field, an error will occur. </li></ul> |
| FOLLOWERS | Follows |
| PROFILE_VIEWS {-To be deprecated} | TikTok Profile page visits<br><p><span style="color:darkred"><b>Note</b></span>: We are planning to deprecate `PROFILE_VIEWS` in the next API version. To ensure a smooth integration journey, we recommend you use `PAGE_VISIT` as the optimization goal.  |
|PAGE_VISIT|TikTok page visits. Drive traffic to a variety of TikTok pages, including your TikTok account profile page, other TikTok pages (Playlist Page, Hashtag Page, Music Page, or Branded Effect Page), and Custom TikTok Instant Page.<br><br>Only when `objective_type` is `ENGAGEMENT`(Community Interaction) at the campaign level can you use this optimization goal.<br><br>To learn about how to use TikTok page visits optimization goal for the Community Interaction objective, see [Create Community Interaction ads](https://business-api.tiktok.com/portal/docs?id=1808164002093058).|
| VALUE | Value/Gross revenue. <br><br>`VALUE` corresponds to the optimization goal Gross revenue in any of the following scenarios: <ul><li> `shopping_ads_type` is `VIDEO` and `product_source` is `STORE` or `SHOWCASE`.</li><li>`shopping_ads_type` is `LIVE`.</li><li>`shopping_ads_type` is `PRODUCT_SHOPPING_ADS` and `product_source` is `STORE`.</li></ul> <br>To learn about how to use the optimization goal Value, see [Enable Value-Based Optimization](https://business-api.tiktok.com/portal/docs?id=1770019181843458).|
| AUTOMATIC_VALUE_OPTIMIZATION |Automatic Value Optimization. Dynamically optimize for the highest value or maximum delivery via machine learning. <br><br>To learn about how to use the optimization goal Automatic Value Optimization for the Web Conversions objective, see [Enable VBO for Web](https://business-api.tiktok.com/portal/docs?id=1770019181843458#item-link-Enable%20VBO%20for%20Web).<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Automatic Value Optimization is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>Enabling Automatic Value Optimization and Campaign Budget Optimization at the same time is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul>|
|GMV|A combined filter that selects campaigns, ad groups, and ads with these parameters: `objective_type` = `PRODUCT_SALES`, `optimization_goal` = `VALUE`, `campaign_product_source` = `STORE`, and `shopping_ads_type` = `VIDEO`/`LIVE`/`PRODUCT_SHOPPING_ADS`. <br><p><span style="color:darkred"><b>Note</b></span>: This value cannot be used to create ad groups. Instead, it can only be applied as a filter in the campaign/adgroup/ad GET endpoints and Reporting endpoints.  |
|PURCHASES|A combined filter that selects campaigns, ad groups, and ads with these parameters: `objective_type` = `PRODUCT_SALES`, `optimization_goal` = `CONVERT`, `campaign_product_source` = `STORE`, and `optimization_event` = `SHOPPING`. <br><p><span style="color:darkred"><b>Note</b></span>: This value cannot be used to create ad groups. Instead, it can only be applied as a filter in the campaign/adgroup/ad GET endpoints and Reporting endpoints.  |
|INITIATE_CHECKOUTS|A combined filter that selects campaigns, ad groups, and ads with these parameters: `objective_type` = `PRODUCT_SALES`, `optimization_goal` = `CONVERT`, `campaign_product_source` = `STORE`, and `optimization_event` =  `INITIATE_ORDER`. <br><p><span style="color:darkred"><b>Note</b></span>:This value cannot be used to create ad groups. Instead, it can only be applied as a filter in the campaign/adgroup/ad GET endpoints and Reporting endpoints.|
|MT_LIVE_ROOM| Live shopping room viewer retention. This goal is applicable to live shopping scenario (`promotion_type` = `LIVE_SHOPPING`). The corresponding `optimization_event` is `LIVE_STAY_TIME`|
|PRODUCT_CLICK_IN_LIVE| Product link clicks during a live session. This goal is applicable to live shopping scenario (`promotion_type` = `LIVE_SHOPPING`). The corresponding `optimization_event` is `LIVE_CLICK_PRODUCT_ACTION`|
|ENGAGED_VIEW| 6-second views (Focused view).<br>The number of times your video has been played at least 6 seconds, or received at least 1 engagement within 1 day of the user seeing a paid ad. Engagements to be measured: Likes, shares, follows, profile visits, clicks, hashtag clicks, music clicks, anchor clicks, and interactive add-ons activity clicks. <br><p><span style="color:DarkRed"><b>Note</b></span>:<ul><li>You cannot change `ENGAGED_VIEW` to a different value in `optimization_goal` after ad group creation.</li><li>Once you set `ENGAGED_VIEW` in `optimization_goal`, the system will automatically pass `ENGAGED_VIEW` into `optimization_event`. If you set a different value in the `optimization_event` field, it will be ignored. </li></ul>|
|ENGAGED_VIEW_FIFTEEN| 15-second views (Focused view).<br>The number of times your video has been played at least 15 seconds, or received at least 1 engagement within 1 day of the user seeing a paid ad. Engagements to be measured: Likes, shares, follows, profile visits, clicks, hashtag clicks, music clicks, anchor clicks, and interactive add-ons activity clicks. <br><p><span style="color:DarkRed"><b>Note</b></span>:<ul><li>Once you set `ENGAGED_VIEW_FIFTEEN` in `optimization_goal`, the system will automatically pass `ENGAGED_VIEW_FIFTEEN` into `optimization_event`. If you set a different value in the `optimization_event` field, it will be ignored.</li></ul>|
| TRAFFIC_LANDING_PAGE_VIEW|Landing Page View. The user clicks and loads the landing page successfully. The corresponding `optimization_event` is `LANDING_PAGE_VIEW`. <br>  <br>To use `TRAFFIC_LANDING_PAGE_VIEW` for the Traffic objective, the following conditions must be met:<ul><li>At the campaign level：`objective_type` is `TRAFFIC`</li><li>At the ad group level：<ul><li>`promotion_type` is `WEBSITE`</li><li>Placement: <ul><li>`placement_type` is `PLACEMENT_TYPE_AUTOMATIC` or</li><li>`placement_type` is `PLACEMENT_TYPE_NORMAL`, and `placements` is not `&#91;"PLACEMENT_GLOBAL_APP_BUNDLE"&#93;`(Global App Bundle only).</li></ul></li><li>`optimization_goal` is `TRAFFIC_LANDING_PAGE_VIEW`</li></ul></li><li>At the ad level：<ul><li>The ID of a TikTok Instant Page is passed via `page_id`.</li><li>Deeplink settings are not configured. </li></ul></li></ul></li></ul><br>To learn about how to use Landing page view for the Product Sales objective, refer to [Optimize Landing page view for Video Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1775099621140482).<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Using the optimization goal Landing page view in Shopping Ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li><li>Once you set `optimization_goal` to `TRAFFIC_LANDING_PAGE_VIEW`, `optimization_event` can be omitted and will default to `LANDING_PAGE_VIEW`. If you set a different value in the `optimization_event` field, an error will occur.</li></ul>|
| CONVERSION_LEADS {-Deprecated}| Conversion leads.<br>Show your ads to people that are most likely to convert after sharing their contact information. |
| DESTINATION_VISIT| Destination Visit. Ads with this optimization goal will introduce users to your in-app page or fallback to the website. <br><br>To learn about how to use Destination Visit for the Traffic objective, see [Optimize Destination Visit in Traffic ads](https://business-api.tiktok.com/portal/docs?id=1782086241778690). <br>To learn about how to use Destination Visit for the Product Sales objective, see [Optimize Destination Visit in Video Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1782087855154177). <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> The optimization goal Destination Visit (`DESTINATION_VISIT`) is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li> Once you set `optimization_goal` to `DESTINATION_VISIT`, `optimization_event` can be omitted and will default to `DESTINATION_VISIT`. If you set a different value in the `optimization_event` field, the value will be ignored. </li></ul>|
| PREFERRED_LEAD | Preferred Leads. Show your ads to people most likely to meet your preferred lead qualifications.|
```

## Placement (`placements`)

```xtable
| Value {40%} | Description {60%} |
| --- | --- |
| PLACEMENT_TIKTOK | TikTok |
| PLACEMENT_PANGLE | Pangle（Used to be TikTok_Audience_Network） |
| PLACEMENT_GLOBAL_APP_BUNDLE| Global App Bundle, including Capcut and Fizzo for now.<br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The Global App Bundle placement does not support the optimization goal Landing Page View (`optimization_goal` = `TRAFFIC_LANDING_PAGE_VIEW`).</li></ul> |
| PLACEMENT_TOPBUZZ {-Deprecated} | BuzzVideo/Babe |
| PLACEMENT_HELO {-Deprecated} | Helo |
```

## Primary Status
>  <span style="color:DodgerBlue">**Note**</span><br>
>  See [Supported secondary statuses for a primary status](https://ads.tiktok.com/marketing_api/docs?id=1757239620352002) to learn about the valid secondary statuses you can pass via [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986), [/adgroup/get/](https://ads.tiktok.com/marketing_api/docs?id=1739314558673922), or [/ad/get/](https://ads.tiktok.com/marketing_api/docs?id=1735735588640770) for the primary status you specify.

```xtable
| Value {40%} | Description {60%} |
| ---| --- |
|STATUS_DELETE |Deleted|
|STATUS_DELIVERY_OK |Advertising in progress|
|STATUS_DISABLE |Suspended|
|STATUS_NOT_DELIVERY |Advertising not in delivery|
|STATUS_TIME_DONE |Completed (this status does not exist for campaigns) |
|STATUS_RF_CLOSED|R&F ad group closed (this status is available for R&F ad groups and ads) |
|STATUS_FROZEN|R&F ad group frozen (this status is available for R&F ads)|
|STATUS_ALL |All statuses (including "Deleted")|
|STATUS_NOT_DELETE |All statuses (excluding "Deleted"). This is a default filter in sync reports for BASIC, AUDIENCE or CATALOG types. |
```

## Promotion Type (Optimization location) (`promotion_type`)

```xtable
| Value {40%} | Description {60%} |
| --- | --- |
| APP_ANDROID | Android application |
| APP_IOS | iOS application |
|MINI_APP|TikTok Minis of the mini series type.<br><br>You can only promote a TikTok Minis in Upgraded Smart+ Minis Ads (with `app_promotion_type` as `MINIS`).<br><br>To learn more about the campaign type, see [Create an Upgraded Smart+ Minis Campaign](https://business-api.tiktok.com/portal/docs?id=1853377811982657).|
|MINI_GAME|TikTok Minis of the mini game type.<br><br>You can only promote a TikTok Minis in Upgraded Smart+ Minis Ads (with `app_promotion_type` as `MINIS`).<br><br>To learn more about the campaign type, see [Create an Upgraded Smart+ Minis Campaign](https://business-api.tiktok.com/portal/docs?id=1853377811982657).| 
| GAME |Game. <br>You can only promote a Game in App posts promotion campaigns (with `app_promotion_type` as `APP_POSTS_PROMOTION`).<br><br><p><span style="color:darkred"><b>Note</b></span>: Currently, creating App posts promotion campaigns via API is not supported. You can only update or retrieve such campaigns via API. |
| WEBSITE | Landing page |
| LEAD_GENERATION| Instant Form or Website for Lead generation ads. <br><br>When `promotion_type` is set to `LEAD_GENERATION`, you can set `promotion_target_type` to: <ul><li>`INSTANT_PAGE`: Instant Form.</li><li> OR `EXTERNAL_WEBSITE`: Website.</li></ul><br>To learn about how to create a Lead Generation ad with promotion type as Instant Form, refer to [here](https://business-api.tiktok.com/portal/docs?id=1774482920012801). <br> To learn about how to create a Lead Generation ad with promotion type as Website, refer to [here](https://business-api.tiktok.com/portal/docs?id=1774482976097281). |
| LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE | Collect leads by TikTok direct messages. <br><br> To learn about how to create a Lead Generation ad with promotion type as TikTok direct messages, refer to [here](https://business-api.tiktok.com/portal/docs?id=1774482992153601). <br> <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Creating a Lead Generation ad with TikTok direct messages as the optimization location is generally available for advertisers registered in the following regions: <ul><li>APAC: Australia, Bangladesh, Cambodia, Indonesia, Japan, Malaysia, New Zealand, Pakistan, the Philippines, Singapore, South Korea, Sri Lanka, Taiwan, Thailand, and Vietnam.</li><li>LATAM: Argentina, Bolivia, Brazil, Chile, Colombia, Costa Rica, the Dominican Republic, Ecuador, Guatemala, Mexico, Panama, Paraguay, Peru, Puerto Rico, and Uruguay.</li></ul></li><li>For advertisers registered in other regions, this feature is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul> |
| LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE | Collect leads by instant messaging apps. <br><br>To learn about how to create a Lead Generation ad with promotion type as instant messaging apps, refer to [here](https://business-api.tiktok.com/portal/docs?id=1774482999036930). <br> <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Creating a Lead Generation ad with optimization location as instant messaging apps is generally available for advertisers registered in the following APAC regions: Australia, Bangladesh, Cambodia, Indonesia, Japan, Malaysia, New Zealand, Pakistan, the Philippines, Singapore, South Korea, Sri Lanka, Taiwan, Thailand, and Vietnam.</li><li>For advertisers registered in other regions, this feature is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul>  |
| LEAD_GEN_CLICK_TO_CALL | Collect leads by phone call.  <br><br>To learn about how to create a Lead Generation ad with promotion type as phone call, refer to [here](https://business-api.tiktok.com/portal/docs?id=1774482936048641). <br> <br><p><span style="color:darkred"><b>Note</b></span>: Creating a Lead Generation ad with promotion type as phone call is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.  |
| WEBSITE_OR_DISPLAY | Landing page or pure display page. `WEBSITE_OR_DISPLAY` is only for campaign objectives (Reach / Video Views / Engagement) where the `landing_page_url` is optional. If you want to promote a website for other objectives (Traffic / Conversion), the external type should be `WEBSITE` . |
|TIKTOK_SHOP| TikTok shop / store|
|VIDEO_SHOPPING| <ul><li> Valid only when `shopping_ads_type` is `VIDEO` and `product_source` is `STORE` or `SHOWCASE`.</li><li> When `shopping_ads_type` is `VIDEO` and `product_source` is `CATALOG`, set `promotion_type` to `WEBSITE`, `APP_ANDRIOD`, or `APP_IOS`. </li></ul><br>Add a product to videos that users can click on to buy the product. |
|LIVE_SHOPPING| Valid only when `shopping_ads_type` is `LIVE`.<br><br>Promote products in a live video. Users can purchase your products throughout the stream. |
| PSA_PRODUCT | Valid only when `shopping_ads_type` is `PRODUCT_SHOPPING_ADS`. <br><br>Use [Shopping Center and Search](https://ads.tiktok.com/help/article/about-onsite-shopping-placements?lang=en) traffic to promote products. |
```

## Reach & Frequency Buy Type (`rf_purchased_type`)
```xtable
|Value{50%}|Description{50%}|
|---|---|
|FIXED_SHOW|Purchase to achieve a fixed number of impressions.|
|FIXED_REACH|Purchase to reach a fixed number of users.|
```

## Schedule Type (`schedule_type`)
```xtable
|Value{50%}|Description{50%}|
|---|---|
|SCHEDULE_FROM_NOW|From now on (continuous deliver)|
|SCHEDULE_START_END|Set Start and End time|
```

# Reporting
## Audience Report Grouping Criteria
```xtable
|Value{50%}|Description{50%}|
|---|---|
|GENDER|Group by gender|
|AGE|Group by age|
|COUNTRY|Group by country|
|AC|Group by network|
|LANGUAGE|Group by language|
|PLATFORM|Group by operating system|
|INTEREST_CATEGORY|Group by interest|
|PLACEMENT|Group by placement|
```

## Audience Report Sort Order
```xtable
|Value{50%}|Description{50%}|
|---|---|
|click_cnt|Sort by clicks|
|convert_cnt|Sort by conversions|
|convert_cost|Sort by CPA|
|convert_rate|Sort by CVR|
|ctr|Sort by CTR|
|show_cnt|Sort by impressions|
|stat_cost|Sort by cost|
```

# Mobile App Management
## Device Model
Use [Get device models](https://ads.tiktok.com/marketing_api/docs?id=1737172880570369) endpoint to get the latest list of device models.
```xtable
|Value|
|---|
|iPhone 13/13 Mini/13 Pro/13 Pro Max|
|iPhone 11 Pro Max/XS Max|
|iPhone 11 Pro/X/XS|
|iPhone 11/XR|
|iPhone 6/6s/7/8|
|iPhone 6/7/8 Plus|
|iPhone 5/5s/5c/SE|
|iPhone 4/4s|
|OPPO A3s|
|OPPO A5s|
|OPPO A71|
|OPPO F7|
|OPPO F9|
|OPPO R15|
|VIVO Y91i|
|VIVO X20A|
|RedMi 5A|
|RedMi 6A|
|RedMi Y2|
|Redmi Note 4|
|RedMi Note 5 Pro|
|RedMi Note 6 Pro|
|SAMSUNG Galaxy J2|
|SAMSUNG Galaxy J2 Prime|
|SAMSUNG Galaxy J7 Nxt|
|SAMSUNG Galaxy J7 Prime|
|SAMSUNG Galaxy A10|
|SAMSUNG Galaxy S8|
|HUAWEI P30|
|Google Pixel 3XL|
```

## Frame Template Type
```xtable
|Value{30%}|Layout{20%}|Description{50%}|
|---|---|---|
|END_FRAME_HORIZONTAL_1|Horizontal|The horizonal end frame template in which the logo is square in shape, and the slogan and brand name are in small font.|
|END_FRAME_HORIZONTAL_2|Horizontal|The horizonal end frame template in which the logo is square in shape, and the slogan and brand name are in large font.|
|END_FRAME_HORIZONTAL_3|Horizontal|The horizonal end frame template in which the logo is rectangular in shape, and the slogan is in small font. Brand name is not supported.|
|END_FRAME_HORIZONTAL_4|Horizontal|The horizonal end frame template in which the logo is square in shape, and the slogan and brand name are in medium font.|
|END_FRAME_HORIZONTAL_5|Horizontal|The horizonal end frame template in which the logo is rectangular in shape, and the slogan is in medium font. Brand name is not supported.|
|END_FRAME_VERTICAL_1|Vertical|The vertical end frame template in which the logo is square in shape, and the slogan and brand name are in small font.|
|END_FRAME_VERTICAL_2|Vertical|The vertical end frame template in which the logo is square in shape, and the slogan and brand name are in large font.|
|END_FRAME_VERTICAL_3|Vertical|The vertical end frame template in which the logo is rectangular in shape, and the slogan is in small font. Brand name is not supported.|
|END_FRAME_VERTICAL_4|Vertical|The vertical end frame template in which the logo is square in shape, and the slogan and brand name are in medium font.|
|END_FRAME_VERTICAL_5|Vertical|The vertical end frame template in which the logo is rectangular in shape, and the slogan is in medium font. Brand name is not supported.|
```

## Material Tag
```xtable
|Name{30%}|Description{30%}|Applicable Type{40%}|
|---|---|---|
|title|Product name|Text|
|price|Price|Text|
|description|Description (default for text)|Text|
|logo|Brand logo|Image|
|image|Product image（default for image）|Image|
|video|Product video（default for video）|Video|
```

## Task Status
```xtable
|Value{50%}|Description{50%}|
|---|---|
|CREATED|Task has been created|
|PROCESSING|Task is under process|
|SUCCESS|Task succeed|
|FAILED|Task failed|
```

## Third-party Monitoring Partners
```xtable
|Value{50%}|Description{50%}|
|---|---|
|AdBrix|Adbrix|
|Adjust|Adjust|
|Adzcore  Deprecated|Party|
|AppAdforce  Deprecated|F.O.X|
|AppLink|Branch|
|AppMetrica  Deprecated|AppMetrica|
|AppsFlyer|Appsflyer|
|DoubleClick|DCM (Double Click)|
|FlashTalking|FlashTalking|
|Huangbaoche  Deprecated|Sensors Data|
|Kochava|Kochava|
|Lazada  Deprecated|Alibaba Group|
|LeadBolt  Deprecated|Leadbolt|
|Metaps  Deprecated|Metaps|
|ServingsSys|Sizmek|
|Singular|Singular|
|Tenjin|Tenjin|
|TlnkIo|Tune|
|Vidoadsplus  Deprecated|Vidoadsplus|
|myTracker  Deprecated|myTracker|
```

# Audience Management
## Audience Type
```xtable
|Value{50%}|Description{50%}|
|---|---|
|ENGAGEMENT|Engagement Audience|
|APP|App Activity Audience|
|PIXEL|Website Traffic Audience|
|LEAD_GENERATION|Lead Generation Audience|
|TIKTOK_SHOP|Shop Activity Audience|
|ENGAGEMENT_ORGANIC_VIDEO|Organic Engagement Audience (Audiences who engage with organic videos)|
|ENGAGEMENT_LIVE_VIDEO|Live Engagement Audience(Audiences who engage with live videos)|
|OFFLINE|Offline Activity Audience (Audiences who interact with your offline events activity)|
```

## Encryption Type (`calculate_type`)
```xtable
|Value{50%}|Description{50%}|
|---|---|
|FIRST_SHA256|IDFA or GAID original value encrypted with SHA256.|
|FIRST_MD5|IDFA or GAID original value encrypted with MD5.|
|EMAIL_SHA256|Emails encrypted with SHA256.|
|PHONE_SHA256|Phone numbers encrypted with SHA256.|
|IDFA_SHA256|Apple IDFA (Identifier for Advertisers) encrypted with SHA256.|
|IDFA_MD5|IDFA encrypted with MD5.|
|GAID_SHA256|GAID (Google Advertising ID) encrypted with SHA256.|
|GAID_MD5|GAID encrypted with MD5.|
|MULTIPLE_TYPES|Multiple types of identifiers. This encrytion type only applies to Customer File Audience created using File API.|
|FIRST_NORMAL(deprecated)|IDFA or GAID raw data|
```

## Filter Field
```xtable
|Value{50%}|Description{50%}|
|---|---|
|EVENT|Filter field is event|
```

## Filter Operator
```xtable
|Value{50%}|Description{50%}|
|---|---|
|EQ|Use `or` to connect filter field and filter value, ie: for EQ, FIELD = VALUE|
```

## Filter Value
```xtable
| Value {30%} | Description {70%} |
| --- | --- |
| Engagement Audience values | |
#| CLICK | Click event for video ads |
#| IMPRESSION | Impression event for video ads|
#| PLAY 2S | View 2 seconds for video ads|
#| PLAY 6S | View 6 seconds for video ads|
#| PLAY 25 | View 25 percent for video ads|
#| PLAY 50 | View 50 percent for video ads|
#| PLAY 75 | View 75 percent for video ads|
#| PLAY OVER | View 100 percent for video ads|
#|ENGAGEMENT APP PROFILE CLICK|Click event for App Profile Page ads|
#|ENGAGEMENT APP PROFILE IMPRESSION|Impression event for App Profile Page ads|
#|ENGAGEMENT TIKTOK INSTANT CLICK|Click event for TikTok Instant Page ads|
#|ENGAGEMENT TIKTOK INSTANT IMPRESSION|Impression event for TikTok Instant Page ads|
#|ENGAGEMENT COLLECTION ADS CLICK|Click event for Collection ads|
#|ENGAGEMENT COLLECTION ADS IMPRESSION |Impression event for Collection ads|
|Live Engagement Audience values| |
#|LIVE VIDEO VIEW|Views|
#|LIVE VIDEO ENGAGEMENT|Engagement |
|Organic Engagement Audience| |
|ORGANIC VIDEO PLAY 2S|2-Second Video View|
|ORGANIC VIDEO PLAY 6S|6-Second Video View|
|ORGANIC VIDEO PLAY OVER|Video View at 100%|
|ORGANIC VIDEO ENGAGEMENT|Engagement |
|App Audience values | |
#| INSTALL | Install event |
#| DAY 2 RETENTION | Day 2 retention event |
#| APP ADD PAYMENT INFO | Add payment info event |
#| APP ADD TO CART | App add to cart event, to distinguish with pixel add to cart event |
#| ADD TO WiSHLIST | Add to wishlist event |
#| LAUNCH APP | Launch app event |
#| CHECKOUT | Checkout event |
#| COMPLETE TUTORIAL | Complete tutorial event |
#| VIEW CONTENT | View content event |
#| CREATE GROUP | Create group event |
#| CREATE ROLE | Create role event |
#| GENERATE LEAD | Generate lead event |
#| IN-APP AD CLICK | In-app ad click event |
#| IN-APP AD IMPRESSION | In-app ad impression event |
#| JOIN GROUP | Join group event |
#| ACHIEVE LEVEL | Achieve level event |
#| LOAN APPLY | Loan apply event |
#| LOAN APPROVAL | Loan approval event |
#| LOAN DISBURSAL | Loan disbursal event |
#| LOGIN | Login event |
#| PURCHASE | Purchase event |
#| RATE | Rate event |
#| REGISTRATION | Registration event |
#| APP SEARCH | Search event |
#| SPEND CREDITS | Spend credits event |
#| START TRIAL | Start trial event |
#| SUBSCRIBE | Subscribe event |
#| UNLOCK ACHIEVEMENT | Unlock achievement event |
| Pixel Audience values | |
#| PAGE BROWSE | It corresponds with Page View event. It tells you when a visitor lands on your website. |
#| CLICK BUTTON | Button click event |
#| PIXEL SUBMIT FORM | Form submission event |
#| CONTACT | Consultation event |
#| DOWNLOAD 	 | Download button click event |
#|PIXEL ADD PAYMENT INFO | Add payment info event |
#| COMPLETE PAYMENT | Complete payment event |
#| INITIATE CHECKOUT | Initiate checkout event |
#| COMPLETE REGISTRATION | User registration event |
#| PRODUCT DETAIL PAGE BROWSE | It corresponds with View Content event. It tells you when a detailed product page is viewed. |
#| PIXEL SEARCH | Search event |
#| PIXEL ADD TO CART | Pixel add to cart event, to distinguish with app add to cart |
#| PLACE AN ORDER | Place an order event |
#| PIXEL ADD TO WISHLIST | Add to wishlist event |
#| PIXEL SUBSCRIBE | Subscribe event |
| Lead Generation Audience values | |
#| LEAD GENERATION FORM SUBMISSION | Form submission event |
#| FORM VIEW | form view event |
|Shop Activity Audience values | |
#| TIKTOK SHOP PAGE VIEW | Product Details Page View |
#| TIKTOK SHOP ADD TO CART | Add to Cart |
#| TIKTOK SHOP INITIATE CHECKOUT | Initiate Checkout |
#| TIKTOK SHOP COMPLETE PAYMENT | Complete Payment |
|Business Account Audience values | |
#| BUSINESS ACCOUNT PROFILE FOLLOW | Follow |
#| BUSINESS ACCOUNT PROFILE VISIT | Visit profile |
#| BUSINESS ACCOUNT ENGAGEMENT | Video engagement (Like/Share/Comment) |
#| BUSINESS ACCOUNT PLAY 2S | 2-second video view |
#| BUSINESS ACCOUNT PLAY 6S | 6-second video view |
#| BUSINESS ACCOUNT PLAY OVER | Video view at 100% |
| Offline Audience values |  |
#| OFFLINE COMPLETE PAYMENT  | Complete payment |
#| OFFLINE CONTACT  | Contact |
#| OFFLINE SUBMIT FORM  | Submit form |
#| OFFLINE SUBSCRIBE | Subscribe |
#| OFFLINE ADD PAYMENT INFO | Add payment info |
#| OFFLINE ADD TO CART  | Add to cart |
#| OFFLINE ADD TO WISHLIST  | Add to wishlist |
#| OFFLINE CLICK BUTTON  | Click button |
#| OFFLINE COMPLETE REGISTRATION | Complete registration |
#| OFFLINE DOWNLOAD  | Download |
#| OFFLINE INITIATE CHECKOUT  | Initiate checkout |
#| OFFLINE PLACE AN ORDER | Place an order |
```

## Lookalike Locations
```xtable
|Value{50%}|Description{50%}|
|---|---|
|AE|United Arab Emirates|
|AU|Australia|
|AZ|Azerbaijan|
|BG|Bulgaria|
|BO|Bolivia|
|CA|Canada|
|CY|Cyprus|
|DE|Germany|
|DZ|Algeria|
|EE|Estonia|
|EG|Egypt|
|ES|Spain|
|FR|France|
|GB|United Kingdom|
|HR|Croatia|
|ID|Indonesia|
|IL|Israel|
|IN|India|
|IT|Italy|
|JP|Japan|
|KE|Kenya|
|KR|Korea|
|LK|Sri Lanka|
|LT|Lithuania|
|LV|Latvia|
|MY|Malaysia|
|PL|Poland|
|PR|Puerto Rico|
|PY|Paraguay|
|RS|Serbia|
|RU|Russia|
|SA|Saudi Arabia|
|SI|Slovenia|
|SK|Slovakia|
|TH|Thailand|
|TR|Turkey|
|TW|Taiwan, China|
|US|United States|
|VN|Vietnam|
```

## Lookalike Size
```xtable
|Value{50%}|Description{50%}|
|---|---|
|NARROW|Narrow size of the lookalike audience|
|BALANCED|Balanced size of the lookalike audience|
|BROAD|Broad size of the lookalike audience|
```




## Lookalike System (`system`)
```xtable
|Value{50%}|Description{50%}|
|---|---|
|ALL|Both iOS and Android|
|IOS|iOS|
|ANDROID|Android|
```

## Lookback window (`retention_days`)
```xtable
|Value{50%}|Description{50%}|
|---|---|
|7|Look back the past 7 days.|
|14|Look back the past 14 days.|
|30|Look back the past 30 days.|
|60|Look back the past 60 days.|
|90|Look back the past 90 days.|
|180|Look back the past 180 days.|
```

## Rule Set Operator (`operator`)
```xtable
|Value{50%}|Description{50%}|
|---|---|
|OR|Use `or` to connect rules, i.e.: rule1 or rule2 or ...|
```

# Creative Management
## Category Labels
```xtable
|Value|
|---|
|`Action`|
|`Adventure`|
|`Arcade`|
|`Art&Design`|
|`Auto&Vehicles`|
|`Beauty`|
|`Board`|
|`Book`|
|`Books`|
|`Books&Reference`|
|`Business`|
|`Card`|
|`Casino`|
|`Casual`|
|`Catalogs`|
|`Comics`|
|`Communication`|
|`Dating`|
|`DeveloperTools`|
|`Education`|
|`Educational`|
|`Entertainment`|
|`Events`|
|`Finance`|
|`Food&Drink`|
|`FoodandDrink`|
|`Games`|
|`Graphics&Design`|
|`Health&Fitness`|
|`HealthandFitness`|
|`House&Home`|
|`Libraries&Demo`|
|`Lifestyle`|
|`Magazines&Newspaper`|
|`MagazinesandNewspapers`|
|`Maps&Navigation`|
|`Medical`|
|`Music`|
|`Music&Audio`|
|`Navigation`|
|`News`|
|`News&Magazines`|
|`Parenting`|
|`Personalization`|
|`Photo&Video`|
|`PhotoandVideo`|
|`Photography`|
|`Productivity`|
|`Puzzle`|
|`Racing`|
|`Reference`|
|`RolePlaying`|
|`Shopping`|
|`Simulation`|
|`Social`|
|`SocialNetworking`|
|`Sports`|
|`Stickers`|
|`Strategy`|
|`Tools`|
|`Travel`|
|`Travel&Local`|
|`Trivia`|
|`Utilities`|
|`VideoPlayers&Editors`|
|`Weather`|
|`Word`|
```

## Device Type
Use [Get device models](https://ads.tiktok.com/marketing_api/docs?id=1737172880570369) endpoint to get the latest list of device models.


## Frame Template Type
```xtable
|Value{30%}|Layout{20%}|Description{50%}|
|---|---|---|
|END_FRAME_HORIZONTAL_1|Horizontal|The horizonal end frame template in which the logo is square in shape, and the slogan and brand name are in small font.|
|END_FRAME_HORIZONTAL_2|Horizontal|The horizonal end frame template in which the logo is square in shape, and the slogan and brand name are in large font.|
|END_FRAME_HORIZONTAL_3|Horizontal|The horizonal end frame template in which the logo is rectangular in shape, and the slogan is in small font. Brand name is not supported.|
|END_FRAME_HORIZONTAL_4|Horizontal|The horizonal end frame template in which the logo is square in shape, and the slogan and brand name are in medium font.|
|END_FRAME_HORIZONTAL_5|Horizontal|The horizonal end frame template in which the logo is rectangular in shape, and the slogan is in medium font. Brand name is not supported.|
|END_FRAME_VERTICAL_1|Vertical|The vertical end frame template in which the logo is square in shape, and the slogan and brand name are in small font.|
|END_FRAME_VERTICAL_2|Vertical|The vertical end frame template in which the logo is square in shape, and the slogan and brand name are in large font.|
|END_FRAME_VERTICAL_3|Vertical|The vertical end frame template in which the logo is rectangular in shape, and the slogan is in small font. Brand name is not supported.|
|END_FRAME_VERTICAL_4|Vertical|The vertical end frame template in which the logo is square in shape, and the slogan and brand name are in medium font.|
|END_FRAME_VERTICAL_5|Vertical|The vertical end frame template in which the logo is rectangular in shape, and the slogan is in medium font. Brand name is not supported.|
```

## Material Source (`source`)
```xtable
|Value{50%}|Description{50%}|
|---|---|
|UPLOADED|Uploaded by user|
|CREATIVE_TOOL_OTHERS|Other creative tools|
|CREATIVE_TOOL_SMART_VIDEO|Uploaded via Smart Video|
|OTHERS|Other sources|
```

## Task Status
```xtable
|Value{50%}|Description{50%}|
|---|---|
|CREATED|Task has been created|
|PROCESSING|Task is under process|
|SUCCESS|Task succeed|
|FAILED|Task failed|
```

## Spark Ads Post Authorization Status
```xtable
|Value{50%}|Description{50%}|
|---|---|
|EXPIRED|Authorization has expired.|
|APPLYING|In the process of applying for authorization.|
|AUTHORIZED|The authorization has been granted.|
|NORESPONSE|There was no response from the post owner.|
|REJECTED|The request for authorization has been rejected.|
|CANCEL|The authorization has been cancelled.|
```

# For Upgraded Smart+ Reports
## Material primary status (`material_primary_status`)
```xtable
|Value{50%}|Description{50%}|
|---|---|
|`STATUS_DELETE`|Deleted|
|`STATUS_DISABLE`|Paused|
|`STATUS_FROZEN`|Frozen|
|`STATUS_TIME_DONE`|Completed|
|`STATUS_NOT_DELIVERY`|Not delivering|
|`STATUS_PENDING`|Pending|
|`STATUS_DELIVERY_OK`|Active|
|`STATUS_DELIVERY_PARTIAL`|Limited delivery|
|`STATUS_DELIVERY_CATALOG`|The creative is a creative within a catalog. Currently, the statuses of catalog creatives are unavailable.|
```

## Material secondary status (`material_second_status_list`)
```xtable
|Value{50%}|Description{50%}|
|---|---|
|`AD_STATUS_CAMPAIGN_DELETE`|Campaign deleted|
|`AD_STATUS_ADGROUP_DELETE`|Ad group deleted|
|`AD_STATUS_DELETE`|Ad deleted|
|`AD_STATUS_CAMPAIGN_DISABLE`|Campaign inactive|
|`AD_STATUS_ADGROUP_DISABLE`|Ad group inactive|
|`AD_STATUS_DISABLE`|Ad inactive|
|`ADGROUP_STATUS_FROZEN`|Ad group frozen|
|`CREATIVE_PAUSED_USED_IN_GMV_MAX`|Creative unavailable as it's used by a GMV Max ad|
|`AD_STATUS_DONE`|Completed|
|`AD_STATUS_CAMPAIGN_EXCEED`|Campaign out of budget|
|`AD_STATUS_BUDGET_EXCEED`|Ad group out of budget|
|`AD_STATUS_ADVERTISER_AUDIT_DENY`|Account not approved|
|`AD_STATUS_ADVERTISER_AUDIT`|Account in review|
|`ADVERTISER_CONTRACT_PENDING`|Contract has not taken effect|
|`ADVERTISER_ACCOUNT_PUNISH`|Account penalized|
|`AD_STATUS_AD_QUOTA_LIMIT`|Ad group paused because your account is out of quota. Pause an active ad group to continue.|
|`PRODUCT_ANCHOR_UNAVAILABLE`|The product link that you try to use in the ad is unavailable.|
|`AD_STATUS_CREATOR_AUTHORIZATION_REVOKED`|Creator hasn't granted authorization|
|`AD_STATUS_CREATIVE_ANCHOR_UNAVAILABLE`|The product link that you try to use in the ad is unavailable.|
|`AD_STATUS_CREATIVE_PRODUCT_ALL_REJECTED`|The products that you try to use in the ad are all rejected|
|`AD_STATUS_ANCHOR_UNAVAILABLE`|The product link that you try to use in the ad is unavailable.|
|`AD_STATUS_PRODUCT_UNAVAILABLE`|The product that you try to use in the ad is unavailable.|
|`NO_AVAILABLE_PRODUCTS`|No available products|
|`AD_STATUS_SHOWCASE_AUTHORIZATION_LOST`|The identity of the Showcase that you try to use in the ad is unavailable.|
|`AD_STATUS_ASSET_AUTHORIZATION_LOST`|Asset unavailable or authorization revoked|
|`AD_STAUS_PIXEL_UNBIND`|Pixel unauthorized|
|`AD_STATUS_AUDIT`|In review|
|`AD_STATUS_NOT_START`|Outside of schedule|
|`AD_STATUS_AD_PRE_ONLINE`|The ad is in a pre-online state and has not been delivered.|
|`ADVERTISER_REAUDIT`|Ad account modifications under review|
|`MATERIAL_DELETE`|Creative deleted|
|`MATERIAL_DISABLE`|Creative paused|
|`MATERIAL_AUDIT_DENY`|Creative disapproved|
|`MATERIAL_AUDIT`|Creative in review|
|`MATERIAL_AUDIT_PARTIAL_APPROVED`|Creative partially approved|
|`MATERIAL_DELIVERY_OK`|Creative active|
|`ADVERTISER_ACCOUNT_BALANCE_EXCEED`|Insufficient account balance|
```


