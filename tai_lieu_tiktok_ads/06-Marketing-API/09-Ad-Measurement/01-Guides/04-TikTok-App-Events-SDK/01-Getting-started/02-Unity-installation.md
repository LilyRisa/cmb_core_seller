---
title: "Unity installation"
doc_id: 1824036608950273
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Getting started / Unity installation"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Unity installation

> <span style="color:DarkRed">**Important**</span><br>
> Use of the TikTok App Events SDK is governed by the [TikTok For Business Commercial Terms of Service](https://ads.tiktok.com/i18n/official/policy/commercial-terms-of-service). By using the TikTok App Events SDK, you also agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.

The TikTok App Events SDK for Unity is maintained in a [public GitHub repository](https://github.com/tiktok/tiktok-business-unity-sdk).

## Prerequisites
Before you begin, ensure you have the following from your TikTok for Business account:
- Your Android App's **App ID** (e.g., `com.example.app`). This is your application's package name.
- Your Apple App's **App ID** (e.g., `123456789`). This is your application's numeric Apple App Store ID.
- A **TikTok App ID** from your app's settings page in Events Manager, for each platform.
- An **App Secret** for verification, for each platform.
If you do not have these, refer to [Getting started](https://business-api.tiktok.com/portal/docs/tiktok-app-events-sdk-getting-started/v1.3) before proceeding further.


## Add the SDK as a build dependency
> <span style="color: DodgerBlue">**Note**</span><br>
> Newtonsoft Json is a prerequisite for integrating the TikTokBusinessSDK.unitypackage into Unity. Refer to [NuGet documentation](https://www.nuget.org/packages/Newtonsoft.Json) for details on how to add this dependency.


<table>
  <thead>
  <tr>
    <th style="width:10%">Step</th>
    <th style="width:45%">Description</th>
    <th style="width:45%">Screenshot</th>
  </tr>
  </thead>
<tbody>
  <tr>
    <td>0</td>
    <td>Download the latest TikTokBusinessSDK.unitypackage from the <a href="https://github.com/tiktok/tiktok-business-unity-sdk/"  target="_blank">GitHub repository</a>.</td>
    <td>N/A</td>
  </tr>
  <tr>
    <td>1</td>
    <td>Go to <code>Assets > Import Package > Custom Package</code></td>
    <td><img src="//images.ctfassets.net/5jqet0ab9qci/5fPC1OqjcMJ7l6hykwnQ3i/8f0528bb10f27df30092e292dc1b9266/6f909daa27de688a3f02c3366f87cafe_Go_to_Assets___Import_Package___Custom_Package.png" alt="Assets > Import Package > Custom Package" width="60%" style="color:#808080" /></td>
  </tr>
  <tr>
    <td>2</td>
    <td>Select <code>TikTokBusinessSDK.unitypackage</code></td>
    <td><img src="//images.ctfassets.net/5jqet0ab9qci/RIzTpe44soNmQmovjkotC/51ac751e08acb431cbe02edc9f14f1aa/3be37c0c84b412a43b68d915f120339d_Select_TikTokBusinessSDK_unitypackage.png" alt="Select TikTokBusinessSDK.unitypackage" width="60%" style="color:#808080" /></td>
  </tr>
  <tr>
    <td>3</td>
    <td>Click <code>All</code><br>Then click <code>Import</code></td>
    <td><img src="//images.ctfassets.net/5jqet0ab9qci/51oJSc5awmcGtgbdScMhOf/32d9772f676709c22b7f0ba35cb6c168/81064b6170750807d0d80b6457f6b033_Click_All__Then_click_Import_.png" alt="Click All Then click Import" width="60%" style="color:#808080" /></td>
  </tr>
</tbody>
</table>

In some versions of Unity, the `.xcframework` file may be recognized as a folder (the error in Figure 1). To resolve this when exporting for iOS, select your desired platform for different framework architectures within the folder. In Figures 2 and 3, the ARM64 architecture folder (`ios-arm64`) is selected for iOS export in the inspector, and x86 architecture (`ios-arm64_x86_64`) is not selected.

![.xcframework file error](https://images.ctfassets.net/5jqet0ab9qci/5ZizDlPSBKky5N881NxXKb/e1fce559316d990d1f892e71aa957548/.xcframework_file_error.png)


![ARM64 architecture folder (ios-arm64) is selected for iOS export](https://images.ctfassets.net/5jqet0ab9qci/39FJBkVUPeuFO0HYyRHZjc/7e85cd7f02f131df90efcefb8a111bb8/ARM64_architecture_folder__ios-arm64__is_selected_for_iOS_export.png)


![x86 architecture (ios-arm64 x86 64) is not selected](https://images.ctfassets.net/5jqet0ab9qci/3FIwFS1Tfm1Te0AhHBjGH/354edd5176de7b9b984609dd62f10a40/x86_architecture__ios-arm64_x86_64__is_not_selected.png)

### Migrating to Unity from existing native Android or iOS SDKs
Developers who have already integrated the native TikTok App Events SDKs for Android (Java, Kotlin) or iOS (Swift, Objective-C) may migrate to the Unity SDK by:
- Replacing the integration methods of `TikTokBusinessSDK.xcframework` (iOS) and `tiktok-business-android-sdk` (Android) with `TikTokBusinessSDK.unitypackage` (Unity), and
- Replacing any existing native configurations and bridge interfaces with our provided C# interfaces. Mixing interfaces may lead to code conflicts and is not recommended or supported.

## Configure and initialize the SDK
Before the first scene display of the game, initialize the SDK as early as possible. It is recommended to call it at `Awake` time.
> <span style="color: DodgerBlue">**Note**</span><br>
> The SDK only supports initialization and use in the main process and main thread.
> On the first initialization, the Android SDK will obtain the device user agent using WebView. To prevent lock conflicts, please use WebView only after initialization is successful.
> If you work with multiple Ad Accounts, you can provide a comma-separated string of TikTok App IDs. Ensure the string contains no spaces and does not end with a comma. The SDK will not initialize if there is a mismatch between the App ID and TikTok App ID(s).

```xcodeblock
(code curl http)
using TikTok.Business.SDK;

private void Awake()
{
    // Initialize with your iOS and Android credentials
    TikTokConfig config = new TikTokConfig(
        "YOUR_IOS_APP_SECRET", 
        "YOUR_IOS_APP_ID", 
        "YOUR_IOS_TIKTOK_APP_ID",
        "YOUR_ANDROID_APP_SECRET",
        "YOUR_ANDROID_APP_ID",
        "YOUR_ANDROID_TIKTOK_APP_ID"
    );

    // For multiple TikTok App IDs:
    // TikTokConfig config = new TikTokConfig(
    //     "YOUR_IOS_APP_SECRET", 
    //     "YOUR_IOS_APP_ID", 
    //     "IOS_TIKTOK_APP_ID_1,IOS_TIKTOK_APP_ID_2",
    //     "YOUR_ANDROID_APP_SECRET",
    //     "YOUR_ANDROID_APP_ID",
    //     "ANDROID_TIKTOK_APP_ID_1,ANDROID_TIKTOK_APP_ID_2"
    // );

    // Continue to initialisation...
    TikTokBusinessSDK.InitializeSdk(
        config,
        (success, code, message) =>
        {
            if (success)
            {
                Debug.Log("TikTok App Events SDK initialized successfully");
            }
            else
            {
                Debug.LogError($"TikTok App Events SDK initialisation failed: code={code}, message={message}");
            }
        }
    );
}
(/code)
```

### Delaying event transmission
When integrating TikTok App Events SDK, clients may need to configure the SDK to delay data transmission and event collection pursuant to jurisdictional-specific legal requirements. TikTok offers resources to support clients' compliance with these laws and can be integrated by following [Resources for data sharing after user consent](https://business-api.tiktok.com/portal/docs/resources-for-data-sharing-after-user-consent/v1.3).


## Platform setup notes
### Android: Permissions, Proguard, and Gradle options
- **Permissions**: In your `AndroidManifest.xml` file, declare the `INTERNET` and `AD_ID` permissions.
```xcodeblock
(code curl http)
<!-- AndroidManifest.xml -->
<manifest ...>
    <uses-permission android:name="android.permission.INTERNET" />
    <!-- Required for advertising and analytics purposes on Android 13+ -->
    <uses-permission android:name="com.google.android.gms.permission.AD_ID" />

    <application ...>
        ...
    </application>
</manifest>
(/code)
```

- **Proguard**: To prevent the SDK from being removed in release builds, add these rules to your `proguard-rules.pro` file:
```xcodeblock
(code curl http)
# proguard-rules.pro

# TikTok SDK
-keep class com.tiktok.** { *; }

# Google Play Billing Library
-keep class com.android.billingclient.api.** { *; }

# Google Install Referrer
-keep class com.android.installreferrer.api.** { *; }

# Android Lifecycle
-keep class androidx.lifecycle.** { *; }
(/code)
```

- **Gradle Options**: Ensure your app-level `build.gradle` or `mainTemplate.gradle` targets Java 8.
```xcodeblock
(code curl http)
// app/build.gradle
android {
  compileOptions {
    sourceCompatibility JavaVersion.VERSION_1_8
    targetCompatibility JavaVersion.VERSION_1_8
  }
}
(/code)
```

### iOS: AppTrackingTransparency (ATT) and SKAdNetwork
**App Tracking Transparency** **(ATT)**: To access the device's advertising identifier (IDFA) on iOS 14.5+, you must request user consent. Add the `NSUserTrackingUsageDescription` key to your `Info.plist` with a message explaining why you need tracking permission.

**SKAdNetwork** **(SKAN)**:
- The SDK supports SKAN for privacy-preserving attribution. To avoid conflicts, if you use another SDK (like an MMP) to manage SKAN updates in your app, you must disable the App Events SDK's SKAdNetwork support.
```xcodeblock
(code curl http)
// In your TikTokConfig initialisation
config.IOS_DisableSKAdNetworkSupport();
(/code)
```

- A schema map is required to accurately report the conversion values received from Apple. To configure this map, refer to [SKAdNetwork reporting and schema mapping](https://business-api.tiktok.com/portal/docs/skadnetwork-reporting-and-schema-mapping/v1.3).

## App event reporting
### Automatically logged events
The SDK automatically logs the following events if they are not disabled during configuration.
```xtable
|Event{20%}|Description{80%}|
|---|---|
|`InstallApp`|First-time app launch after installation.<br><br>You may notice an increase in reported install events when the SDK is integrated for the first time. This is expected, as installs are measured based on the first detectable app launch by the SDK. If this is a concern, allow a few days for your existing user base to update their apps before running paid campaigns.|
|`LaunchAPP`|On every app launch.|
|`2DRetention`|App is opened the day after the first app launch.|
|`Purchase`|A successful transaction is completed via Apple's StoreKit or the Google Play Billing Library. This includes consumable, non-consumable, and subscription purchases.|
```
You have the option to disable automatic event logging. Disabling automatic event logging means that events such as app installs and app launches will no longer be tracked by default.

If you require these events for measurement or analytics, you must implement manual logging in your app code to capture and send them.
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

### Manually logged events
#### Standard events
For simple events without properties, create a `TikTokBaseEvent` with an event constant from the `TTEventName` enum. For a full list of `TTEventName` enums, refer to [Standard event constants and classes](https://business-api.tiktok.com/portal/docs/standard-event-constants-and-classes/v1.3).

**Example: ****`Registration`**** Event**
```xcodeblock
(code curl http)
// Example: A user completes a registration
TikTokBaseEvent registrationEvent = new TikTokBaseEvent(TTEventName.TTEventNameRegistration);
TikTokBusinessSDK.TrackTTEvent(registrationEvent);
(/code)
```

#### Adding properties to standard events
For events that include details about products or value, builder classes are available for the Standard events below, and `TikTokBaseEvent` may be used for all other standard events:
```xtable
|Standard event {30%}|Builder Class{70%}|
|---|---|
|Add to Cart|`TikTokAddToCartEvent`|
|Add to Wishlist|`TikTokAddToWishlistEvent`|
|Checkout|`TikTokCheckoutEvent`|
|Purchase|`TikTokPurchaseEvent`|
|View Content|`TikTokViewContentEvent`|
```
**Example: ****`ViewContent`**** event builder class**
```xcodeblock
(code curl http)
// Example: A user adds an item to the cart
TikTokAddToCartEvent cartEvent = new TikTokAddToCartEvent();
cartEvent.SetDescription("Description of the product");
cartEvent.SetCurrency(TTCurrency.TTCurrencyUSD);
cartEvent.SetValue("4.99");
cartEvent.SetContentType("product_group");
cartEvent.SetContentId("ABC-123");

TikTokContentParams eventContent = new TikTokContentParams();
eventContent.price = 4.99;
eventContent.quantity = 1;
eventContent.brand = "AwesomeBrand";
eventContent.contentName = "The Best Product";
TikTokContentParams[] contents = { eventContent };

cartEvent.SetContents(contents);
TikTokBusinessSDK.TrackTTEvent(cartEvent);
(/code)
```

**Example: ****`CompleteTutorial`**** event**

For other standard events with standard properties, use the `TikTokBaseEvent` builder with a constant from the `TTEventName` enum.
```xcodeblock
(code curl http)
// Example: A user completes the onboarding tutorial
TikTokBaseEvent tutorialEvent = new TikTokBaseEvent(TTEventName.TTEventNameCompleteTutorial);

// Standard properties (replace with actual values)
tutorialEvent.AddProperty("currency", TTCurrency.TTCurrencyUSD); // ISO 4217 currency code
tutorialEvent.AddProperty("value", 9.99);                        // Order or items value
tutorialEvent.AddProperty("content_id", "CONTENT_ID");           // Unique product/content ID
tutorialEvent.AddProperty("content_type", "CONTENT_TYPE");       // Type of content in the event
tutorialEvent.AddProperty("price", 9.99);                        // Item price
tutorialEvent.AddProperty("quantity", 1);                        // Number of items

TikTokBusinessSDK.TrackTTEvent(tutorialEvent);
(/code)
```

> <span style="color: DodgerBlue">**Note**</span><br>
> For a full list of supported standard properties, refer to the [Properties object reference](https://business-api.tiktok.com/portal/docs/setup-guide-for-app/v1.3#item-link-properties%20parameters). Custom property keys are not supported and will be discarded.

#### In-app ad revenue
Starting from SDK version 1.5.0, in-app advertising mediation revenue data can be reported using `TikTokAdRevenueEvent`. Refer to [In-app ad revenue reporting](https://business-api.tiktok.com/portal/docs/in-app-ad-revenue-reporting/v1.3) for detailed implementation guidelines for AppLovin Max, ironSource, and Google AdMob.
#### Custom events
To track an event not listed in the standard events, use `TikTokBaseEvent` with a custom event name string.
```xcodeblock
(code curl http)
// Example: A user shares content
TikTokBaseEvent baseEvent = new TikTokBaseEvent("ShareContent");
TikTokBusinessSDK.TrackTTEvent(baseEvent);
(/code)
```

****

## Enable advanced matching
To improve audience matching, use `Identify()` to set advanced matching properties when user information becomes available (e.g., after login or sign-up), and `Logout()` to clear it.
```xcodeblock
(code curl http)
// When user logs in or signs up
TikTokBusinessSDK.Identify(
    "user-external-id-123",  // externalId
    "user-name-456",         // externalUserName
    "+15551234567",          // phoneNumber (E.164 format)
    "user@example.com"       // email
);

// When user logs out
TikTokBusinessSDK.Logout();
(/code)
```

**Supported Properties**


<table>
<thead>
  <tr>
    <th style="width:25%">Property</th>
    <th style="width:50%">Formatting Requirements</th>
    <th style="width:25%"Example</th>
  </tr>
  </thead>
<tbody>
  <tr>
    <td><code>externalId</code><br><br>Unique ID from the advertiser, such as loyalty membership ID or user ID.</td>
    <td>Unhashed plaintext. Trim leading/trailing spaces.</td>
    <td>"user-id-abc"</td>
  </tr>
  <tr>
    <td><code>externalUserName</code><br><br>Username from the advertiser.</td>
    <td>Unhashed plaintext. Trim leading/trailing spaces.</td>
    <td>"testuser"</td>
  </tr>
  <tr>
    <td><code>email</code></td>
    <td>Unhashed plaintext. Trim leading/trailing spaces and lowercase all characters.</td>
    <td>"email@example.com"</td>
  </tr>
  <tr>
    <td><code>phoneNumber</code></td>
    <td>Unhashed plaintext. Normalize to E.164 format (e.g., <code>+[country code][number]</code>).</td>
    <td>"+15551234567"</td>
  </tr>
</tbody>
</table>

## Verify your integration
Use TikTok Events Manager to verify that events are being received correctly. This sends events to a separate endpoint for testing and does not affect your production data.
### Codeless test events (recommended)
This method enables device-specific event debugging without code changes. Before you start, ensure that:
- The latest version of the TikTok app is installed on your test device.
- You have SDK version 1.5.0 or higher in your app
- (iOS only) You have granted App Tracking Transparency (ATT) permission for both your app and the TikTok app.

```xtable
|Step{25%}|Instructions{75%}|
|---|---|
|Generate QR Code|<ul><li>In <b>Events Manager</b> for your app, go to the <b>Test Events</b> tab.</li><li>Click <b>Generate QR code</b>. A QR code is generated, valid for 24 hours.</li><li>To force expiry before the 24 hours is over, click <b>Disable code</b>.</li></ul>|
|Bind Device|<ul><li>Open the TikTok app on your test device and go to your <b>Profile</b>.</li><li>Tap the menu icon (☰) in the top right, then select <b>My QR code</b>.</li><li>Tap the scan icon in the top right and scan the QR code from Events Manager.</li><li>Tap <b>Authorize</b> to bind your device and enable test mode.</li></ul>|
|Verify Events|<ul><li>Trigger events in your app. They will appear in the <b>Test Event</b> tab in real-time.</li><li>If you configured multiple TikTok App IDs, test events will only appear in the Events Manager for the <b>first</b> ID in the list.</li></ul>|
```

### Manual debug mode
> <span style="color:darkred">**Important**</span><br>
> Remember to disable manual debug mode and verbose logging before releasing your app to production. Test events are discarded and not used for campaign reporting.

#### Enabling debug mode
Add `OpenDebugMode()` to your `TikTokConfig` during initialisation. You can also use `SetLogLevel()` for more detailed output.
```xcodeblock
(code curl http)
TikTokConfig config = new TikTokConfig(/*...*/);
config.OpenDebugMode();
config.SetLogLevel(TiktokLogLevel.VERBOSE); // Optional

TikTokBusinessSDK.InitializeSdk(config);
(/code)
```

The available log levels are `NONE`, `INFO`, `WARN`, `DEBUG`, and `VERBOSE`.
#### Disabling debug mode
Once your integration is verified, remove the `OpenDebugMode()` and `SetLogLevel()` calls before submitting your app.
```xcodeblock
(code curl http)
// REMOVEME
// config.OpenDebugMode();
// config.SetLogLevel(TiktokLogLevel.VERBOSE);
(/code)
```

### Generate and verify events
After enabling debug mode, run your app and trigger some events. You can view the events as they arrive in real-time under the **Test Event** tab in your app's Events Manager page.

If multiple TikTok App IDs are present in your SDK configuration, test events will only show in the Events Manager ad account associated with the **first** TikTok App ID in the configuration string.

![iOS test events](https://images.ctfassets.net/5jqet0ab9qci/3dfGPuRElVFFmaKkD0bqxs/b245f2eb9d67b7ea91431ec1596237ba/iOS_test_events.png)


## Additional features and guidance
- [Advanced Predictive Measurement](https://business-api.tiktok.com/portal/docs/advanced-predictive-measurement/v1.3)
- [Deeplinks and deferred deeplinks](https://business-api.tiktok.com/portal/docs/deeplinks-and-deferred-deeplinks/v1.3)
- [Enhance data postback](https://business-api.tiktok.com/portal/docs/enhance-data-postback/v1.3)
- [In-app ad revenue reporting](https://business-api.tiktok.com/portal/docs/in-app-ad-revenue-reporting/v1.3)
- [SKAdNetwork reporting and schema mapping](https://business-api.tiktok.com/portal/docs/skadnetwork-reporting-and-schema-mapping/v1.3)
- [Compliance best practices](https://business-api.tiktok.com/portal/docs/app-events-sdk-compliance-best-practices/v1.3)


