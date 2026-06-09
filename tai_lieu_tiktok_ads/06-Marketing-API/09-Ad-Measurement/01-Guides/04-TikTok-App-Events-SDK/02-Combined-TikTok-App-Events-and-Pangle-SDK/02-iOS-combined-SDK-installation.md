---
title: "iOS combined SDK installation"
doc_id: 1862345579934722
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Combined TikTok App Events and Pangle SDK / iOS combined SDK installation"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# iOS combined SDK installation

> <span style="color:darkred">**Important**</span><br>
> Use of the TikTok App Events SDK is governed by the [TikTok For Business Commercial Terms of Service](https://ads.tiktok.com/i18n/official/policy/commercial-terms-of-service). By using the TikTok App Events SDK, you also agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.

The combined SDK is maintained in a separate Maven repository, and is developed on TikTok App Events SDK version 1.6.1.


## Prerequisites
Before you begin, ensure you have the following from your TikTok for Business account:
- Your Apple App's **App ID** (e.g., `123456789`). This is your application's numeric Apple App Store ID.
- A **TikTok App ID** from your app's settings page in Events Manager.
- An **App Secret** for verification.
If you do not have these, refer to [Getting started](https://business-api.tiktok.com/portal/docs/tiktok-app-events-sdk-getting-started/v1.3) before proceeding further.


## Add the SDK as a build dependency
### CocoaPods integration
1. In your `Podfile`, add the combined SDK pod:
```xcodeblock
(code curl http)
# Podfile
pod 'Ads-Global'
(/code)
```

2. (Optional) If you want to use the combined SDK but exclude the Pangle SDK module, you can declare only the TikTok App Event SDK. This will include only the TikTok App Events SDK-specific components in your build.
```xcodeblock
(code curl http)
# Podfile to exclude the Pangle module
pod 'Ads-Global/TikTokBusinessSDK'
(/code)
```

3. Install the pod by running the following command in your project directory:
```xcodeblock
(code curl http)
pod install
(/code)
```

### Swift Package Manager integration
If you prefer to integrate using Swift Package Manager, follow the instructions [here](https://github.com/bytedance/AdsGlobalPackage).

## Configuring and setting up the SDK
- To continue setting up TikTok App Events SDK, refer to the rest of the steps from `Configure build settings` onwards in [iOS installation](https://business-api.tiktok.com/portal/docs/ios-integration-steps/v1.3).
- To continue setting up the Pangle Ad Monetization SDK, refer to [Pangle Integration Documentation](https://www.pangleglobal.com/integration/integrate-pangle-sdk-for-android).


