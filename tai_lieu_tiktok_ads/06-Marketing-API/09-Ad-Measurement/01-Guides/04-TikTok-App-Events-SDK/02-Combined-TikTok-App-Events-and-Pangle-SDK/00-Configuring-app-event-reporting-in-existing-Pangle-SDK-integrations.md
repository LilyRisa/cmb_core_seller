---
title: "Configuring app event reporting in existing Pangle SDK integrations"
doc_id: 1862344912338497
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Combined TikTok App Events and Pangle SDK / Configuring app event reporting in existing Pangle SDK integrations"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Configuring app event reporting in existing Pangle SDK integrations

> <span style="color:darkred">**Important**</span><br>
> Use of the TikTok App Events SDK is governed by the [TikTok For Business Commercial Terms of Service](https://ads.tiktok.com/i18n/official/policy/commercial-terms-of-service). By using the TikTok App Events SDK, you also agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.

If the Pangle SDK is already installed in your app, follow these instructions to enable the TikTok App Events module. The Pangle SDK (version 7.7.0.0 and above) is a combined SDK that includes both the Pangle Ad Monetization SDK as a module and the TikTok App Events SDK as a module, that operate independently of each other.


## Android instructions
### Update existing build dependencies and add required libraries
1. Update your existing Pangle Ad Monetization SDK dependency to the latest version.

2. In your project-level `build.gradle` file, add the following required repo declaration:
```xcodeblock
(code curl http)
// project/build.gradle
allprojects {
    repositories {
        google()
    }
}
(/code)
```

3. In your app-level `build.gradle` file, add the implementation dependencies for these required libraries.
```xcodeblock
(code curl http)
// app/build.gradle
android {
  compileOptions {
    sourceCompatibility JavaVersion.VERSION_1_8
    targetCompatibility JavaVersion.VERSION_1_8
  }
}

dependencies {
    // Required for automatic event reporting and lifecycle tracking
    implementation 'androidx.lifecycle:lifecycle-process:2.3.1'
    implementation 'androidx.lifecycle:lifecycle-common-java8:2.3.1'

    // Required for automatic in-app purchase reporting
    implementation 'com.android.billingclient:billing:6.1.0'

    // Required for Play Store install referrer attribution
    implementation 'com.android.installreferrer:installreferrer:2.2'
}
(/code)
```

### Configure and initialize the TikTok App Events SDK module
1. Generate and note down your TikTok App ID and App Secret by following the instructions in [Getting started](https://business-api.tiktok.com/portal/docs/tiktok-app-events-sdk-getting-started/v1.3). Separate values are required for Android and iOS.
2. Follow the rest of the steps from `Add permissions` onwards in [Android installation](https://business-api.tiktok.com/portal/docs/android-integration-steps/v1.3).
### Configure ad revenue reporting
1. If you are not an allowlisted client already sending ad revenue events via the Pangle SDK, follow the remaining instructions in [In-app ad revenue reporting](https://business-api.tiktok.com/portal/docs/in-app-ad-revenue-reporting/v1.3).
2. If you are already sending ad revenue events via the Pangle SDK for Android, return your existing `adRevenueJson` object through the TikTok App Events SDK. Test this new reporting method first to avoid reporting discrepancies before removing any existing Pangle Ad Monetization SDK interface calls.
```xcodeblock
(code curl http)
// Make sure the App Events SDK has been initialized before calling this
TTBaseEvent adRevenueInfo = TTAdRevenueEvent.newBuilder(adRevenueJson).build();
TikTokBusinessSdk.trackTTEvent(adRevenueInfo);

// After testing the new TikTokBusinessSdk interface, remove the existing PAGSdk interface
// REMOVEME
PAGSdk.setAdRevenue(adRevenueJson);
(/code)
```


## iOS instructions
### Update existing build dependencies
Update your existing Pangle Ad Monetization SDK dependency to the latest version.

### Configure and initialize the TikTok App Events SDK module
1. Generate and note down your TikTok App ID and App Secret by following the instructions in [Getting started](https://business-api.tiktok.com/portal/docs/tiktok-app-events-sdk-getting-started/v1.3). Separate values are required for Android and iOS.
2. Follow the rest of the steps from `Configure build settings` onwards in [iOS installation](https://business-api.tiktok.com/portal/docs/ios-integration-steps/v1.3).
### Configure ad revenue reporting
Follow the instructions in [In-app ad revenue reporting](https://business-api.tiktok.com/portal/docs/in-app-ad-revenue-reporting/v1.3).


## Unity instructions
### Update existing build dependencies
Update your existing Unity mediation plugin for Pangle to the latest version.

### Import the TikTok App Events SDK Unity bridge
1. Download the latest `TikTokBusinessSDK.unitypackage` from the [GitHub repository](https://github.com/tiktok/tiktok-business-unity-sdk/).
2. In Unity, go to `Assets > Import Package > Custom Package`
3. Select your downloaded `TikTokBusinessSDK.unitypackage`
4. Import all `unitypackage` files **except** `tiktok-business-android-sdk.aar` and `TikTokBusinessSDK.xcframework`. This ensures the existing TikTok App Events modules in the mediation plugin are used correctly, and reduces your final build size.
### Configure and initialize the TikTok App Events SDK module
1. Generate and note down your TikTok App ID and App Secret by following the instructions in [Getting started](https://business-api.tiktok.com/portal/docs/tiktok-app-events-sdk-getting-started/v1.3). Separate values are required for Android and iOS.
2. Follow the rest of the steps from `Configure and initialize the SDK` onwards in [Unity installation](https://business-api.tiktok.com/portal/docs/unity-integration-steps/v1.3).
### Configure ad revenue reporting
1. Follow the instructions in [In-app ad revenue reporting](https://business-api.tiktok.com/portal/docs/in-app-ad-revenue-reporting/v1.3).
2. If you are already sending ad revenue events via a Unity mediation plugin for Pangle, return your existing `revenueInfo` object through the TikTok App Events SDK. Test this new reporting method first to avoid reporting discrepancies before removing any existing Unity plugin interface calls.
```xcodeblock
(code curl http)
// Make sure the App Events SDK has been initialized before calling this
TikTokAdRevenueEvent adRevenueEvent = new TikTokAdRevenueEvent(revenueInfo);
TikTokBusinessSDK.TrackTTEvent(adRevenueEvent);

// After testing the new TikTokBusinessSDK interface, remove the existing PAGSDKHelper interface
// REMOVEME
PAGSDKHelper.SetAdRevenue(revenueInfo);
(/code)
```



