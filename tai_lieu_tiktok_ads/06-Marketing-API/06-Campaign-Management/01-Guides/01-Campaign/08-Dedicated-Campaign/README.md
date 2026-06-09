---
title: "Dedicated Campaign"
doc_id: 1740029173531649
path: "Marketing API / Campaign Management / Guides / Campaign / Dedicated Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Dedicated Campaign

Dedicated Campaigns are a special type of campaign that targets devices running iOS 14.5 and later versions. 

Devices using iOS 14.5 or later iOS versions make up a large part of traffic. Create Dedicated Campaigns to ensure you're able to reach these users. If you create regular campaigns, your ads will not reach devices using iOS 14.5 or later iOS versions.

Depending on whether a SKAdNetwork (SKAN) 4.0 is enabled for the App used to create a Dedicated Campaign, the campaign can be either an iOS 14 Dedicated Campaign or a SKAN 4.0 Dedicated Campaign. Dedicated Campaigns can only be created using the advertising objective `PRODUCT_SALES` (when `campaign_product_source` is `CATALOG`) or `APP_PROMOTION` (when `app_promotion_type` is `APP_INSTALL`).


# SKAN 4.0 Attribution Window configuration

With the launch of SKAN 4.0 by Apple, you can configure a SKAN 4.0 attribution window for a disabled campaign to ensure more granular reporting. Based on your business needs, you can configure one of three attribution windows that correspond to the three postbacks in SKAN 4.0. A longer attribution window of up to 35 days is now available, allowing you to collect feedback for longer time ranges. Once you configure a SKAN 4.0 attribution window for a campaign, you cannot update the setting. 

To configure a SKAN 4.0 attribution window for a campaign, you can use the `postback_window_mode` parameter at the [campaign level](https://business-api.tiktok.com/portal/docs?id=1739318962329602). 

Make sure that all the following conditions are met:
- You have enabled SKAN 4.0 for your App.
- The campaign is a Dedicated Campaign (`campaign_type` is `IOS14_CAMPAIGN` ).
- The current status of the campaign is disabled (`operation_status` is `DISABLE` ).
- `postback_window_mode` has not been configured for the campaign.

> <span style="color:DodgerBlue">**Note**</span><br>
>  <ul><li>If you have set up Mobile Measurement Partner (MMP) Tracking with <b>Adjust</b>, <b>Airbridge</b>, <b>Appsflyer</b>,  <b>Branch</b>, <b>Kochava</b>, or <b>Singular</b> for your App and your MMP SDK version is updated to a SKAN 4.0 supported SDK, you can transition your App to SKAN 4.0 on Events Manager. To learn about how to set up MMP tracking for your App, see [How to Set Up App Attribution in TikTok Ads Manager](https://ads.tiktok.com/help/article/set-up-app-attribution-tiktok-ads-manager?lang=en). To learn about how to transition your App to SKAN 4.0, see [About SKAN 4.0 and TikTok](https://ads.tiktok.com/help/article/about-skan-4-0-and-tiktok?lang=en) and [How to transition to SKAN 4.0](https://ads.tiktok.com/help/article/how-to-transition-to-skan-4-0). </li><li>If you have enabled SKAN 4.0 for your App, ensure that you target devices running iOS 16.1 and later so that you can receive SKAN 4.0 postbacks. To only target iOS 16.1+ devices, set <code>min_ios_version</code> to <code>16.1</code> at the ad group level.</li></ul>

To learn about how the attribution window impacts campaign quota release and how to configure the setting, refer to the table below.
```xtable
| Attribution window {20%} | Corresponding postback {20%}| Impact on DC campaign quota release {30%}| Value for <code>postback_window_mode</code>{30%} |
|---|---|---|---|
| 0-2 days | First postback | Up to 4 days to release campaign quota | `POSTBACK_WINDOW_MODE1` |
| 3-7 days | First two postbacks | Up to 13 days to release campaign quota | `POSTBACK_WINDOW_MODE2` |
| 8-35 days | All three postbacks | Up to 41 days to release campaign quota | `POSTBACK_WINDOW_MODE3` |
```


# Create a Dedicated Campaign
## Prerequisites 
- Check whether the app is eligible for Dedicated Campaigns. 
Use [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) or [/app/info/](https://ads.tiktok.com/marketing_api/docs?id=1740859272887297) to verify the value for `skan_allowed` in the response. If not allowed, you need to verify the ownership of the app in the Event Manager within TikTok Ads Manager. For instructions on how to verify App ownership, please see [here](https://ads.tiktok.com/help/article?aid=10001060).
- Check the quota status using [/campaign/quota/info/](https://ads.tiktok.com/marketing_api/docs?id=1752256376677378) to ensure that the campaign quota is not used up.  

## Steps
1. Create a new campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). 

- Set `objective_type` to `PRODUCT_SALES` (when `campaign_product_source` is `CATALOG`),  or `APP_PROMOTION`(with  `app_promotion_type` set to `APP_INSTALL`). 
- Specify an iOS app that is eligible for Dedicated Campaigns via `app_id`.

If you want to configure a SKAN 4.0 attribution window for your campaign, you need to meet the following requirements:
- You have enabled SKAN 4.0 for your App.
	- If you have set up Mobile Measurement Partner (MMP) Tracking with <b>Adjust</b>, <b>Airbridge</b>, <b>Appsflyer</b>,  <b>Branch</b>, <b>Kochava</b>, or <b>Singular</b> for your App and your MMP SDK version is updated to a SKAN 4.0 supported SDK, you can transition your App to SKAN 4.0 on Events Manager. To learn about how to set up MMP tracking for your App, see [How to Set Up App Attribution in TikTok Ads Manager](https://ads.tiktok.com/help/article/set-up-app-attribution-tiktok-ads-manager?lang=en). To learn about how to transition your App to SKAN 4.0, see [About SKAN 4.0 and TikTok](https://ads.tiktok.com/help/article/about-skan-4-0-and-tiktok?lang=en) and [How to transition to SKAN 4.0](https://ads.tiktok.com/help/article/how-to-transition-to-skan-4-0).
- `campaign_type` is set to `IOS14_CAMPAIGN`.
- `operation_status` is set to `DISABLE`. 
- `postback_window_mode` is passed.


Note that: <ul><li>If `objective_type` is `PRODUCT_SALES` or `APP_PROMOTION` (when `app_promotion_type`= `APP_INSTALL`), you can directly set `campaign_type` to `IOS14_CAMPAIGN` without specifying the `ios14_targeting` field at the ad group level,  because then by default at the ad group level, `ios14_targeting` will be automatically set as `IOS14_PLUS`, and `ios14_quota_type` will be automatically set as `OCCUPIED`. <br>Therefore, at the ad group level, you can choose not to pass in `ios14_targeting` and `ios14_quota_type`, or you need to pass in both fields and specify `ios14_targeting` as `IOS14_PLUS` and specify `ios14_quota_type`  as `OCCUPIED`.</li></ul>

2. Create an ad group. 

  - Create a standard ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). <ul><li>Set the `ios14_targeting` to `IOS14_PLUS`, which stands for devices where the privacy update has been enforced. After being set to `IOS14_PLUS`, this field cannot be edited, and the system will verify if related fields meet the requirements for a Dedicated Campaign. For example, `optimization_goal` can only be set to `CLICK`, `INSTALL`, `IN_APP_EVENT` or `VALUE`. See [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114) to find out the requirements for detailed fields. </li><li>When `ios14_targeting` is `IOS14_PLUS`, `ios14_quota_type` is automatically set to `OCCUPIED`. </li></ul>
> <span style="color:DodgerBlue">**Note**</span><br>
> If you have enabled SKAN 4.0 for your App, ensure that you target devices running iOS 16.1 and later so that you can receive SKAN 4.0 postbacks. To only target iOS 16.1+ devices, set `min_ios_version` to `16.1` at the ad group level.
  - Create a R&F ad group using [/adgroup/rf/create/](https://ads.tiktok.com/marketing_api/docs?id=1738235338194945). Set the `ios14_quota_type` to `OCCUPIED`. 
  
3. Create ads using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354).

For ads to be included in a Dedicated Campaign, the `deeplink_type` cannot be set to `DEFERRED_DEEPLINK`. For ads in a R&F ad group, fallback and deep links are not supported.

# Get a Dedicated Campaign
To get the list of Dedicated Campaigns for your account, make a GET request to the [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986) endpoint and set the `campaign_type` filter to `IOS14_CAMPAIGN`.

# Dedicated Campaign quota
By default, Dedicated Campaigns are all SKAN Dedicated Campaigns and the campaign is bound by Dedicated Campaign quota rules. To learn more about the quota limits and rules for a Dedicated Campaign, see [Dedicated Campaign quota limits](https://business-api.tiktok.com/portal/docs?id=1752256248260610).

However, if your app qualifies for Advanced Dedicated Campaigns, you have the option to create a Dedicated non-SKAN Campaign by setting disable_skan_campaign to true at the campaign level. With this setting, the SKAN attribution for the campaign will be disabled and the campaign will not be bound by [Dedicated Campaign quota limits](https://business-api.tiktok.com/portal/docs?id=1752256248260610). You will have access to Self Attribution Network (SAN) metrics for the campaign, but you will not be able to access SKAN reporting metrics for the campaign.


# Reporting metrics
## Reporting metrics for Dedicated Campaigns
[SKAN metrics in basic reports](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics) are available for SKAN Dedicated Campaigns. For non-SKAN Dedicated Campaigns, you can only access [Self Attributing Network (SAN) metrics in basic reports](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SAN%20metrics). To determine whether a Dedicated Campaign is a SKAN Dedicated Campaign, use /campaign/get/ and check whether the returned disable_skan_campaign is false.

## Reporting metrics that are not supported or have data loss
The following dimensions and metrics are not supported or have data loss for Dedicated Campaigns.
- The Audience Report is not supported.
- Inaccuracy or loss of by-day data. There might be 1-3 days of delay.
- Day 2 Retention metrics are not supported.
- For In-App Event metrics that are in the Total or Each category, there will be data inaccuracies.

# SKAN Dedicated Campaign Structure
The following table outlines the structure for iOS 14 SKAN Dedicated Campaigns and SKAN 4.0 Dedicated Campaigns.

```xtable
| Campaign Type{30%} | iOS 14 SKAN Dedicated Campaigns <br>using a non-SKAN 4.0 App {30%} | SKAN 4.0 Dedicated Campaigns <br>using a SKAN 4.0 App {40%} |
|---|---|---|
| Maximum number of Dedicated Campaigns for each ad network in an iOS app | 15 or 25 <br><br><p><span style="color:darkred"><b>Note</b></span>: Expanding the quota of active Dedicated Campaigns to 25 per ad network for an iOS app is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.| 15 or 25 <br><br><p><span style="color:darkred"><b>Note</b></span>: Expanding the quota of active Dedicated Campaigns to 25 per ad network for an iOS app is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. |
| Maximum number of active ad groups within a Dedicated Campaign | 5 | 5 |
| Maximum number of undeleted ads within an ad group | 50 | <ul><li> If Smart+ Campaign, Automated Creative Optimization, and Smart Creative are not enabled: 9 </li><li> If Smart+ Campaign, Automated Creative Optimization, or Smart Creative is enabled: no limit </li></ul>|
```
# Advanced Dedicated Campaign
The introduction of the Advanced Dedicated Campaign on iOS brings a new level of sophistication to campaign delivery and performance. This campaign type leverages advanced delivery models benefiting from real-time signals to achieve improved results for advertisers on iOS.

To learn about how to create such campaigns, see [Create Advanced Dedicated Campaigns](https://business-api.tiktok.com/portal/docs?id=1797011827608577).


