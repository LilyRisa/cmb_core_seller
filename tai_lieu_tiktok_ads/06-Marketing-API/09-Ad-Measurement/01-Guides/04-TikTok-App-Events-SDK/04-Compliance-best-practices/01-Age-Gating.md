---
title: "Age Gating"
doc_id: 1824048170680450
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Compliance best practices / Age Gating"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Age Gating

Data from or about Children (as defined in Clause 2.8 of the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms))) must not be shared with TikTok for Business. The TikTok App Events SDK must be implemented in a compliant manner:
- If your app **is** primarily marketed to Children, an age gate is required. The TikTok App Events SDK must not be initialized and data must not be transferred to TikTok for Business until after the age gate is passed.
- If your app **is not** primarily marketed to Children, the TikTok App Events SDK may be used without an age gate. However in all cases, data from or about Children must not be transferred to TikTok for Business.

# Delaying events collection until age gate is passed
## Android
When TikTok App Events SDK is initialized, events will be collected and stored locally until `startTrack()` is called, after which stored events will be sent to TikTok. If your app is primarily marketed to Children, event collection and storage must be delayed entirely until an age gate is passed.

Before age gate is passed:
```xcodeblock
(code curl http)
// Disable auto start and do not fully initialize the SDK at this point
TTConfig ttConfig = new TTConfig(context)
                    .disableAutoStart(); // disable sending data here
...
(/code)
```

After age gate is passed:
```xcodeblock
(code curl http)
// After the age gate is passed, fully initialize and start the sdk.
TikTokBusinessSdk.initializeSdk(ttConfig); // initialize the SDK
TiktokBusiness.startTrack(); // start sending data
(/code)
```

## iOS
When TikTok App Events SDK is initialized, events will be collected and stored locally until `setTrackingEnabled()` is called, after which stored events will be sent to TikTok. If your app is primarily marketed to Children, event collection and storage must be delayed entirely until an age gate is passed.

Before age gate is passed:
```xcodeblock
(code curl http)
// Disable auto start and do not fully initialize the SDK at this point
let config = TikTokConfig.init(appId: "APP_ID", tiktokAppId: TIKTOK_APP_ID)
config?.disableTracking(); // disable measurement here
...
(/code)
```
After age gate is passed:
```xcodeblock
(code curl http)
// After the age gate is passed, fully initialize and start the sdk.
TikTokBusiness.initializeSdk(config); // initialize the SDK
TikTokBusiness.setTrackingEnabled(true); // start sending data
(/code)
```


## Unity
When TikTok App Events SDK is initialized, events will be collected and stored locally until `StartTrack()` is called, after which stored events will be sent to TikTok. If your app is primarily marketed to Children, event collection and storage must be delayed entirely until an age gate is passed.

Before age gate is passed:
```xcodeblock
(code curl http)
// Disable auto start and do not fully initialize the SDK at this point
TikTokConfig config = new TikTokConfig("iOS_appId", "iOS_tiktokAppId", "Android_appId", "Android_tiktokAppId");
config.DisableTrack(); // disable measurement here
...
(/code)
```
After age gate is passed:
```xcodeblock
(code curl http)
// After the age gate is passed, fully initialize and start the sdk.
TikTokBusinessSDK.InitializeSdk(config); // initialize the SDK
TikTokBusinessSDK.StartTrack(); // start sending data
(/code)
```


