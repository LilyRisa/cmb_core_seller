---
title: "SKAdNetwork reporting and schema mapping"
doc_id: 1862352021568001
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Additional features / SKAdNetwork reporting and schema mapping"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# SKAdNetwork reporting and schema mapping

The App Events SDK supports Apple's SKAdNetwork (SKAN) framework for privacy-preserving attribution. When SKAN is enabled, the SDK maps tracked events to your SKAN schema and calls the relevant Apple APIs to update the conversion value (CV). A SKAN schema is required so TikTok can correctly interpret the conversion values reported by Apple for your app.If you use the App Events SDK, you can provide this schema in two ways:
1. If you already have an MMP configured for your app in Events Manager, TikTok automatically imports your SKAN schema from the MMP integration.
2. If you do not have an MMP configured in Events Manager, you can upload a SKAN schema directly in Events Manager.
Regardless of how the schema is provided, **only one SDK** in your app should update SKAN conversion values. Having multiple SDKs write CVs can cause conflicts and inaccurate reporting.
## Decide whether the App Events SDK should update the conversion value in your app
Use the table below to decide how to configure SKAN measurement for your app.
```xtable
|Scenario {25%}|Who updates SKAN CV{15%}|TikTok SDK SKAdNetwork support{15%}|SKAN schema in Events Manager{15%}|Notes{30%}|
|---|---|---|---|---|
|You use an MMP SDK and want to use the MMP's SKAN schema.|MMP SDK|Disable|Do not configure a SKAN schema.|MMP is the single source of truth for CV updates and schema. See "Avoid SKAN Update Conflicts" below.|
|You do not have an MMP, but another non-MMP SDK already updates SKAN CVs.|Other SDK (non-MMP)|Disable|Upload a SKAN schema.|TikTok uses your schema for reporting only and does not send CV updates. See "SKAN3 Schema Basics" and "SKAN4 Schema Basics" below.|
|You want the TikTok SDK to update SKAN CVs.|TikTok App Events SDK|Enable|Upload a SKAN schema.|The SDK becomes the single source of truth for SKAN CV updates.|
```
## SKAN3 Schema Basics
You can configure your SKAN3 schema in Events Manager, either by manually adding rules or by uploading a CSV file.A SKAN3 CSV file includes the following columns:
```xtable
|conversion_value{20%}|event_name{25%}|min_revenue_usd{25%}|max_revenue_usd{30%}|
|---|---|---|---|
|Integer from 1-63.|The standard or custom event name to match.|The minimum revenue (inclusive) to trigger this value.|The maximum revenue (inclusive) to trigger this value.|
```
**Notes:**
- The SDK automatically calls `updatePostbackConversionValue` (on iOS 15.4+) or `updateConversionValue` (on iOS 14.0+) when a tracked event matches a rule in your schema.
- Revenue values are always specified in USD in the schema. The SDK will automatically convert event currencies to USD before matching, if currency information is available.
## SKAN4 Schema Basics
Configuring a SKAN4 schema may require allowlisting from your TikTok representative. The schema is defined via a CSV template with the following key columns:
```xtable
|postback_index{16%}|fine_conversion_value{17%}|coarse_conversion_value{19%}|event_name{15%}|min_revenue_usd{17%}|max_revenue_usd{16%}|
|---|---|---|---|---|---|
|The conversion window (0, 1, or 2).|Integer from 0-63. Only applicable for `postback_index` 0.|`low`, `medium`, or `high`.|The event name to match.|Minimum revenue (inclusive).|Maximum revenue (inclusive).|
```
**Notes:**
- The SDK evaluates rules for both `fine` and `coarse` values. If an event matches rules for both, the SDK prioritizes updating the `fine` value.
- The SDK calls `updatePostbackConversionValue:coarseValue:lockWindow:completionHandler:` on iOS 16.1+ to update SKAN4 values. It may pass `lockWindow: true` when appropriate to lock the conversion window and receive the postback sooner.
## Where to Configure
You can manage your SKAN schemas in **Events Manager** under your app's **Tracking Settings** → **Configure SKAN**.
## Avoid SKAN Update Conflicts
Ensure that only one SDK in your app is responsible for updating SKAN conversion values. If you use another SDK (like an MMP) to manage SKAN, you must disable the TikTok SDK's SKAdNetwork support to prevent conflicts.

To disable, call `disableSKAdNetworkSupport()` on your `TikTokConfig` object before initializing the SDK.
```xcodeblock
(code curl http)
// Swift
guard let config = TikTokConfig(accessToken: "YOUR_APP_SECRET", appId: "YOUR_APP_ID", tiktokAppId: "YOUR_TIKTOK_APP_ID") else {
    return true
}
// Add this line to disable SKAN updates
config.disableSKAdNetworkSupport()

TikTokBusiness.initializeSdk(config)
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
TikTokConfig *config = [TikTokConfig configWithAccessToken:@"YOUR_APP_SECRET" appId:@"YOUR_APP_ID" tiktokAppId:@"YOUR_TIKTOK_APP_ID"];
// Add this line to disable SKAN updates
[config disableSKAdNetworkSupport];

[TikTokBusiness initializeSdk:config];
(/code)
```

## Implementation Note
The SDK handles all the complexity of matching events to your SKAN schema and calling the correct Apple APIs based on the iOS version. Your primary responsibility is to track events accurately and configure your desired schema in Events Manager.



