---
title: "Enhance data postback"
doc_id: 1862351781303361
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Additional features / Enhance data postback"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Enhance data postback

Enhance data postback (available from SDK version 1.4.0 onwards) automatically collects in-app page information such as metadata and button clicks through the App Events SDK. This feature is currently in limited closed testing with the objective of eventually improving ads delivery and providing signal automation improvements. Refer to the [Business Help Center](https://ads.tiktok.com/help/article/about-enhanced-data-postback-for-app-sdk) article for more details.

Three methods are provided to enable or disable this feature, depending on your requirements:
```xtable
|Control type{20%}|Interface{20%}|Purpose{40%}|Scope{20%}|
|---|---|---|---|
|Front-end toggle|TikTok Events Manager|Enable or disable the feature on all devices. Use this to change the setting without having to release a new app version.<br><br>Overrides client-side global toggle, and respects client-side low-performance flags if present.|Global|
|Client-side global toggle|SDK configuration|Disable the feature on all devices. Changing this setting requires a new app version release.<br><br>Defers to front-end toggle (server-side priority).|Global|
|Client-side low performance flag|SDK configuration|Optimize feature performance on low-performance devices, if you have custom device grading logic.<br><br>Enforced when global toggles are enabled.|Device-specific|
```
## 1. Events manager front-end toggle
This toggle is accessed from your app's Settings tab in Events Manager. It allows dynamic enable/disable of the feature in TikTok Events Manager, without having to recompile and re-release your app. This toggle **overrides the general disable flag**, and respects the low performance device flag.This toggle also enforces server-side priority. When implementing the SDK for the first time, the initial state of the toggle will reflect client-side settings. Any further changes made in events manager will override any client-side settings.
## 2. Client-side global toggle
Add this setting in your SDK configuration to disable the feature **globally across all devices**. Use this when you want to **turn off the feature entirely** regardless of device type or runtime conditions.
### Android
```xcodeblock
(code curl http)
TTConfig ttConfig = new TTConfig(getApplicationContext(), "YOUR_APP_SECRET")
    .setAppId("YOUR_APP_ID")
    .setTTAppId("YOUR_TIKTOK_APP_ID")
    .disableAutoEnhancedDataPostbackEvent() // Disable Enhanced data postback

TikTokBusinessSdk.initializeSdk(ttConfig);
(/code)
```

### iOS
```xcodeblock
(code curl http)
// Swift
guard let config = TikTokConfig(accessToken: "YOUR_APP_SECRET", appId: "YOUR_APP_ID", tiktokAppId: "YOUR_TIKTOK_APP_ID") else {
    return true
}
// Add this line to disable Enhanced data postback
config.disableAutoEnhancedDataPostbackEvent()

TikTokBusiness.initializeSdk(config)
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
TikTokConfig *config = [TikTokConfig configWithAccessToken:@"YOUR_APP_SECRET" appId:@"YOUR_APP_ID" tiktokAppId:@"YOUR_TIKTOK_APP_ID"];
// Add this line to disable Enhanced data postback
[config disableAutoEnhancedDataPostbackEvent];

[TikTokBusiness initializeSdk:config];
(/code)
```

### Unity
```xcodeblock
(code curl http)
// C#
TikTokConfig config = new TikTokConfig("YOUR_IOS_APP_SECRET", "YOUR_IOS_APP_ID", "YOUR_IOS_TIKTOK_APP_ID", "YOUR_ANDROID_APP_SECRET", "YOUR_ANDROID_APP_ID", "YOUR_ANDROID_TIKTOK_APP_ID");
// Add this line to disable Enhanced data postback
config.DisableEnhancedDataPostbackTrack();

TikTokBusinessSDK.InitializeSdk(config);
(/code)
```

## 3. Client-side low performance device flag
Add this setting in your SDK configuration to optimise performance **only on devices identified as low performance. **Use this if you have custom logic to classify device capabilities, and want the feature active by default, but **excluded for performance-constrained devices**.
### Android
```xcodeblock
(code curl http)
TTConfig ttConfig = new TTConfig(getApplicationContext(), "YOUR_APP_SECRET")
    .setAppId("YOUR_APP_ID")
    .setTTAppId("YOUR_TIKTOK_APP_ID")
    .setIsLowPerformanceDevice(true); // Optimise feature performance on this device only

TikTokBusinessSdk.initializeSdk(ttConfig);
(/code)
```

### iOS
```xcodeblock
(code curl http)
// Swift
guard let config = TikTokConfig(accessToken: "YOUR_APP_SECRET", appId: "YOUR_APP_ID", tiktokAppId: "YOUR_TIKTOK_APP_ID") else {
    return true
}
// Add this line to optimise feature performance on this device only
config.setIsLowPerformanceDevice(true)

TikTokBusiness.initializeSdk(config)
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
TikTokConfig *config = [TikTokConfig configWithAccessToken:@"YOUR_APP_SECRET" appId:@"YOUR_APP_ID" tiktokAppId:@"YOUR_TIKTOK_APP_ID"];
// Add this line to optimise feature performance on this device only
[config setIsLowPerformanceDevice:YES];

[TikTokBusiness initializeSdk:config];
(/code)
```

### Unity
```xcodeblock
(code curl http)
// C#
TikTokConfig config = new TikTokConfig("YOUR_IOS_APP_SECRET", "YOUR_IOS_APP_ID", "YOUR_IOS_TIKTOK_APP_ID", "YOUR_ANDROID_APP_SECRET", "YOUR_ANDROID_APP_ID", "YOUR_ANDROID_TIKTOK_APP_ID");
// Add this line to optimise feature performance on this device only
config.SetIsLowPerformanceDevice(true);

TikTokBusinessSDK.InitializeSdk(config);
(/code)
```



