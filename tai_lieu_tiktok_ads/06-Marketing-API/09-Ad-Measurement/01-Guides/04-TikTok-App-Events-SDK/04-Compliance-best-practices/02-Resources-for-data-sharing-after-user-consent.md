---
title: "Resources for data sharing after user consent"
doc_id: 1824048384831554
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Compliance best practices / Resources for data sharing after user consent"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Resources for data sharing after user consent

When integrating the TikTok App Events SDK, you are required under our [Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) that you have a valid legal basis for collecting and processing user data. In jurisdictions governed by the ePrivacy Directive and similar data protection frameworks, this typically requires obtaining prior, informed consent from end users before initiating any data transmission via the SDK.

TikTok App Events SDK is designed to automatically log standard mobile app events, such as app installs, app launches, and in-app purchases. These events are transmitted to TikTok upon app open and are used to enable insight reporting, audience creation and delivery optimization.

To support your compliance with legal requirements (including EU data protection laws and our data terms), we’ve compiled resources to help you build or integrate consent mechanisms into your app experience.

## Pre-consent tracking delay
TikTok App Events SDK is designed to automatically transmit data to TikTok upon app open. TikTok provides tools that allow you to delay data transmission until the user has completed an in-app consent flow. This enables a delay prior to data transmission until consent is obtained.
### Android
By default, the SDK begins logging and sending events automatically upon initialisation. If you need to obtain user consent before sending data, call `disableAutoStart()` during configuration. This allows the SDK to queue events locally without sending them.
```xcodeblock
(code curl http)
TTConfig ttConfig = new TTConfig(getApplicationContext(), "YOUR_APP_SECRET")
    .setAppId("YOUR_APP_ID")
    .setTTAppId("YOUR_TIKTOK_APP_ID")
    .disableAutoStart(); // Add this line

TikTokBusinessSdk.initializeSdk(ttConfig);
(/code)
```

Once consent is granted, call `startTrack()` to begin sending all queued and future events to TikTok.
```xcodeblock
(code curl http)
// Call this after user provides consent
TikTokBusinessSdk.startTrack();
(/code)
```

### iOS
By default, the SDK begins logging and sending events automatically upon initialisation. If you need to obtain user consent before sending data, call `disableTracking()` during configuration. This allows the SDK to queue events locally without sending them.
```xcodeblock
(code curl http)
// Swift
guard let config = TikTokConfig(accessToken: "YOUR_APP_SECRET", appId: "YOUR_APP_ID", tiktokAppId: "YOUR_TIKTOK_APP_ID") else {
    return true
}
config.disableTracking() // Add this line

TikTokBusiness.initializeSdk(config)
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
TikTokConfig *config = [TikTokConfig configWithAccessToken:@"YOUR_APP_SECRET" appId:@"YOUR_APP_ID" tiktokAppId:@"YOUR_TIKTOK_APP_ID"];
[config disableTracking]; // Add this line

[TikTokBusiness initializeSdk:config];
(/code)
```

Once consent is granted, call `setTrackingEnabled(true)` to begin sending all queued and future events to TikTok.
```xcodeblock
(code curl http)
// Swift
// Call this after user provides consent
TikTokBusiness.setTrackingEnabled(true)
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
// Call this after user provides consent
[TikTokBusiness setTrackingEnabled:YES];
(/code)
```

### Unity
By default, the SDK begins logging and sending events automatically upon initialisation. If you need to obtain user consent first, call `DisableTrack()` during configuration. This allows the SDK to queue events locally without sending them.
```xcodeblock
(code curl http)
TikTokConfig config = new TikTokConfig(/*...*/);
config.DisableTrack(); // Add this line

TikTokBusinessSDK.InitializeSdk(config);
(/code)
```

Once consent is granted, call `StartTrack()` to send all queued and future events.
```xcodeblock
(code curl http)
// Call this after user provides consent
TikTokBusinessSDK.StartTrack();
(/code)
```


## Disabling automatic event collection
TikTok App Events SDK is designed to automatically log standard mobile app events, such as app installs, app launches, and in-app purchases. You have the option to disable automatic event logging. 
> <span style="color:darkred">**Important**</span><br>
> Disabling automatic event logging means that events such as app installs and app launches will no longer be tracked by default. If you require these events for measurement or analytics, you must implement manual logging in your app code to capture and send them manually.

### Android
```xcodeblock
(code curl http)
TTConfig ttConfig = new TTConfig(getApplicationContext(), "YOUR_APP_SECRET")
    // ... other configurations

    // Option 1: Disable ALL automatic events (Install, Launch, 2DRetention, Purchase)
    .disableAutoEvents()

    // Option 2: Disable specific automatic events
    .disableInstallLogging() // Disable install reporting only
    .disableLaunchLogging() // Disable automatic app launch reporting only
    .disableRetentionLogging() // Disable automatic 2DRetention reporting only
    .disableAutoIapTrack() // Disable automatic Purchase reporting only
    
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

// Option 1: Disable ALL automatic events
config.disableAutomaticTracking()

// Option 2: Disable specific automatic events
config.disableInstallTracking()
config.disableLaunchTracking()
config.disableRetentionTracking()
config.disablePaymentTracking()

TikTokBusiness.initializeSdk(config)
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
TikTokConfig *config = [TikTokConfig configWithAccessToken:@"YOUR_APP_SECRET" appId:@"YOUR_APP_ID" tiktokAppId:@"YOUR_TIKTOK_APP_ID"];

// Option 1: Disable ALL automatic events
[config disableAutomaticTracking];

// Option 2: Disable specific automatic events
[config disableInstallTracking];
[config disableLaunchTracking];
[config disableRetentionTracking];
[config disablePaymentTracking];

[TikTokBusiness initializeSdk:config];
(/code)
```

### Unity
```xcodeblock
(code curl http)
TikTokConfig config = new TikTokConfig(/*...*/);

// Option 1: Disable ALL automatic events
config.DisableAutoTrack();

// Option 2: Disable specific automatic events
config.DisableInstallTrack();
config.DisableLaunchTrack();
config.DisableRetentionTrack();
config.DisablePayTrack();

TikTokBusinessSDK.InitializeSdk(config);
(/code)
```


## Disabling Enhance data postback
Enhance data postback (available from SDK version 1.4.0 onwards) automatically collects in-app page information such as metadata and button clicks through the App Events SDK. You have the option to disable automatic metadata collection. There are three ways to control the collection:
```xtable
|Control type{15%}|Interface{20%}|Purpose{40%}|Scope{15%}|
|---|---|---|---|
|Front-end toggle|TikTok Events Manager|Enable or disable the feature on all devices. Use this to change the setting without having to release a new app version.<br><br>Overrides client-side global toggle, and respects client-side low-performance flags if present.|Global|
|Client-side global toggle|SDK configuration|Disable the feature on all devices. Changing this setting requires a new app version release.<br><br>Defers to front-end toggle (server-side priority).|Global|
|Client-side low performance flag|SDK configuration|Optimize feature performance on low-performance devices, if you have custom device grading logic.<br><br>Enforced when global toggles are enabled.|Device-specific|
```
### 1. Events manager front-end toggle
This toggle is accessed from your app's Settings tab in Events Manager. It allows dynamic enable/disable of the feature in TikTok Events Manager, without having to recompile and re-release your app. This toggle **overrides the general disable flag**, and respects the low performance device flag.

This toggle also enforces server-side priority. When implementing the SDK for the first time, the initial state of the toggle will reflect client-side settings. Any further changes made in events manager will override any client-side settings.
### 2. Client-side global toggle
Add this setting in your SDK configuration to disable the feature **globally across all devices**. Use this when you want to **turn off the feature entirely** regardless of device type or runtime conditions.
#### Android
```xcodeblock
(code curl http)
TTConfig ttConfig = new TTConfig(getApplicationContext(), "YOUR_APP_SECRET")
    .setAppId("YOUR_APP_ID")
    .setTTAppId("YOUR_TIKTOK_APP_ID")
    .disableAutoEnhancedDataPostbackEvent() // Disable Enhanced data postback

TikTokBusinessSdk.initializeSdk(ttConfig);
(/code)
```

#### iOS
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

#### Unity
```xcodeblock
(code curl http)
// C#
TikTokConfig config = new TikTokConfig("YOUR_IOS_APP_SECRET", "YOUR_IOS_APP_ID", "YOUR_IOS_TIKTOK_APP_ID", "YOUR_ANDROID_APP_SECRET", "YOUR_ANDROID_APP_ID", "YOUR_ANDROID_TIKTOK_APP_ID");
// Add this line to disable Enhanced data postback
config.DisableEnhancedDataPostbackTrack();

TikTokBusinessSDK.InitializeSdk(config);
(/code)
```

### 3. Client-side low performance device flag
Add this setting in your SDK configuration to optimise performance **only on devices identified as low performance**. Use this if you have custom logic to classify device capabilities, and want the feature active by default, but **excluded for performance-constrained devices**.
#### Android
```xcodeblock
(code curl http)
TTConfig ttConfig = new TTConfig(getApplicationContext(), "YOUR_APP_SECRET")
    .setAppId("YOUR_APP_ID")
    .setTTAppId("YOUR_TIKTOK_APP_ID")
    .setIsLowPerformanceDevice(true); // Optimise feature performance on this device only

TikTokBusinessSdk.initializeSdk(ttConfig);
(/code)
```

#### iOS
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

#### Unity
```xcodeblock
(code curl http)
// C#
TikTokConfig config = new TikTokConfig("YOUR_IOS_APP_SECRET", "YOUR_IOS_APP_ID", "YOUR_IOS_TIKTOK_APP_ID", "YOUR_ANDROID_APP_SECRET", "YOUR_ANDROID_APP_ID", "YOUR_ANDROID_TIKTOK_APP_ID");
// Add this line to optimise feature performance on this device only
config.SetIsLowPerformanceDevice(true);

TikTokBusinessSDK.InitializeSdk(config);
(/code)
```



