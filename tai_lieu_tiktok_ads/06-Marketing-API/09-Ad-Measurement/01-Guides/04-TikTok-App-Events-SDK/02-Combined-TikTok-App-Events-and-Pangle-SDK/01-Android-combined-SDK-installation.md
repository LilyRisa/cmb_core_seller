---
title: "Android combined SDK installation"
doc_id: 1862345576136194
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Combined TikTok App Events and Pangle SDK / Android combined SDK installation"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Android combined SDK installation

> <span style="color:darkred">**Important**</span><br>
> Use of the TikTok App Events SDK is governed by the [TikTok For Business Commercial Terms of Service](https://ads.tiktok.com/i18n/official/policy/commercial-terms-of-service). By using the TikTok App Events SDK, you also agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.

The combined SDK is maintained in a separate Maven repository, and is developed on TikTok App Events SDK version 1.6.1.


## Prerequisites
Before you begin, ensure you have the following from your TikTok for Business account:
- Your Android App's **App ID** (e.g., `com.example.app`). This is your application's package name.
- A **TikTok App ID** from your app's settings page in Events Manager.
- An **App Secret** for verification.
If you do not have these, refer to [Getting started](https://business-api.tiktok.com/portal/docs/tiktok-app-events-sdk-getting-started/v1.3) before proceeding further.


## Add the SDK as a build dependency
### Integrating through Maven
1. In your project-level `build.gradle` file, add the corresponding repo declaration in `build.gradle` in the root directory of your app. **This repo declaration is different from the ****`jitpack`**** repo used in the standalone version.**
```xcodeblock
(code curl http)
// project/build.gradle
allprojects {
    repositories {
        google()
        mavenCentral()
        maven { url 'https://artifact.bytedance.com/repository/pangle' }
    }
}
(/code)
```

2. In your app-level `build.gradle` file, add the implementation dependencies for the combined SDK and its required libraries.
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
    // TikTok App Events SDK
    implementation 'com.github.tiktok:tiktok-business-android-sdk:7.8.5.9'

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

3. If you want to add the combined SDK now, but plan to onboard to Pangle at a later date, you can exclude the Pangle module from your dependency declaration. This will include only the TikTok App Events SDK-specific components in your build.
```xcodeblock
(code curl http)
implementation('com.github.tiktok:tiktok-business-android-sdk:7.8.5.9', {
    // Exclude the Pangle module
    exclude group: 'com.pangle.global', module: 'pag-sdk-ad'
})
(/code)
```


## Configuring and setting up the SDK
- To continue setting up TikTok App Events SDK, refer to the rest of the steps from `Add permissions` onwards in [Android installation](https://business-api.tiktok.com/portal/docs/android-integration-steps/v1.3).
- To continue setting up the Pangle Ad Monetization SDK, refer to [Pangle Integration Documentation](https://www.pangleglobal.com/integration/integrate-pangle-sdk-for-android).


