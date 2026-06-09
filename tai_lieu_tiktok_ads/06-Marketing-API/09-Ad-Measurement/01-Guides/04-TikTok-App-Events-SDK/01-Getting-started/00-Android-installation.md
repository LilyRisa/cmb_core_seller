---
title: "Android installation"
doc_id: 1739585434183746
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Getting started / Android installation"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Android installation

> <span style="color:DarkRed">**Important**</span><br>
> Use of the TikTok App Events SDK is governed by the [TikTok For Business Commercial Terms of Service](https://ads.tiktok.com/i18n/official/policy/commercial-terms-of-service). By using the TikTok App Events SDK, you also agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.

The TikTok App Events SDK for Android is maintained in a [public GitHub repository](https://github.com/tiktok/tiktok-business-android-sdk).
## Prerequisites
Before you begin, ensure you have the following from your TikTok for Business account:
- Your Android App's **App ID** (e.g., `com.example.app`). This is your application's package name.
- A **TikTok App ID** from your app's settings page in Events Manager.
- An **App Secret** for verification.
If you do not have these, refer to [Getting started](https://business-api.tiktok.com/portal/docs/tiktok-app-events-sdk-getting-started/v1.3) before proceeding further.

## Add the SDK as a build dependency
### Maven integration (recommended)
1. In your project-level `build.gradle` file, ensure `mavenCentral()`, `google()`, and `jitpack.io` are declared in the repositories block.
```xcodeblock
(code curl http)
// project/build.gradle
allprojects {
    repositories {
        google()
        mavenCentral()
        maven { url 'https://jitpack.io' }
    }
}
(/code)
```

2. In your app-level `build.gradle` file, add the implementation dependencies for the TikTok SDK and its required libraries.
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
    implementation 'com.github.tiktok:tiktok-business-android-sdk:1.6.1'

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

### Manual integration
1. Download the `.aar` binary asset from the [latest GitHub release](https://github.com/tiktok/tiktok-business-android-sdk/releases/latest/).
2. Create a `libs` directory in your app module (e.g., `app/libs`) if it does not already exist.
3. Copy the downloaded `.aar` file into the `app/libs` directory.
4. Add the dependencies to your app-level `build.gradle` file.
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
    // TikTok App Events SDK (Manual)
    implementation files('libs/tiktok-business-android-sdk-1.6.1.aar') // Ensure the filename matches

    // Add the same required libraries as in the Maven setup
    implementation 'androidx.lifecycle:lifecycle-process:2.3.1'
    implementation 'androidx.lifecycle:lifecycle-common-java8:2.3.1'
    implementation 'com.android.billingclient:billing:6.1.0'
    implementation 'com.android.installreferrer:installreferrer:2.2'
}
(/code)
```

### Add permissions
In your `AndroidManifest.xml` file, declare the `INTERNET` and `AD_ID` permissions.
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

### Proguard obfuscation
To prevent the SDK from being removed or broken in release builds, add the following rules to your app's `proguard-rules.pro` file:
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


## Configure and initialize the SDK
In your `Application` class, create a `TTConfig` instance with your App Secret, App ID, and TikTok App ID(s) from Events Manager. Then, call `TikTokBusinessSdk.initializeSdk` to start the SDK. We recommend using the completion handler to manage success or failure.
> <span style="color:darkred">**Important**</span><br>
> The SDK only supports initialization and use in the main process and main thread. On the first initialization, the SDK will also obtain the device user agent using WebView. To prevent lock conflicts, please use WebView only after initialization is successful.

****
> <span style="color: DodgerBlue">**Note**</span><br>
> **Handling Multiple TikTok App IDs** <br>
> If you work with multiple Ad Accounts, you can provide a comma-separated string of TikTok App IDs. Ensure the string contains no spaces and does not end with a comma. The SDK will not initialize if there is a mismatch between the App ID and TikTok App ID(s).

```xcodeblock
(code curl http)
// YourApplicationClass.java
import com.tiktok.TikTokBusinessSdk;
import com.tiktok.TikTokBusinessSdk.TTConfig;

public class YourApplicationClass extends Application {
    @Override
    public void onCreate() {
        super.onCreate();

        TTConfig ttConfig = new TTConfig(getApplicationContext(), "YOUR_APP_SECRET")
            .setAppId("YOUR_APP_ID") // Your app's package name, e.g., com.example.app
            .setTTAppId("YOUR_TIKTOK_APP_ID");
            
        // For multiple TikTok App IDs:
        // TTConfig ttConfig = new TTConfig(getApplicationContext(), "YOUR_APP_SECRET")
        //    .setAppId("YOUR_APP_ID") // Your app's package name, e.g., com.example.app
        //    .setTTAppId("TIKTOK_APP_ID1,TIKTOK_APP_ID2,TIKTOK_APP_ID3");
        
        // Continue to initialisation...
        TikTokBusinessSdk.initializeSdk(ttConfig, new TikTokBusinessSdk.TTInitCallback() {
            @Override
            public void success() {
                // SDK initialized successfully
            }
            @Override
            public void fail(int code, String msg) {
                // SDK initialisation failed
            }
        });
    }
}
(/code)
```

### Delaying event transmission
When integrating TikTok App Events SDK, clients may need to configure the SDK to delay data transmission and event collection pursuant to jurisdictional-specific legal requirements. TikTok offers resources to support clients' compliance with these laws and can be integrated by following [Resources for data sharing after user consent](https://business-api.tiktok.com/portal/docs/resources-for-data-sharing-after-user-consent/v1.3).

## App event reporting
### Automatically logged events
The SDK automatically logs the following events if they are not disabled during configuration.
```xtable
|Event {20%}|Description {80%}|
|---|---|
|`InstallApp`|First-time app launch after installation.<br><br>You may notice an increase in reported install events when the SDK is integrated for the first time. This is expected, as installs are measured based on the first detectable app launch by the SDK. If this is a concern, allow a few days for your existing user base to update their apps before running paid campaigns.|
|`LaunchAPP`|On every app launch.|
|`2DRetention`|App is opened the day after the first app launch.|
|`Purchase`|A successful transaction is completed via the Google Play Billing Library. Non-Google Play transactions must be logged manually.|
```
You have the option to disable automatic event logging. Disabling automatic event logging means that events such as app installs and app launches will no longer be tracked by default.If you require these events for measurement or analytics, you must implement manual logging in your app code to capture and send them.
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

### Manually logged events
#### Standard events
For simple events without properties, use `trackTTEvent()` with an event constant from the `EventName` enum. For a full list of `EventName` enums, refer to [Standard event constants and classes](https://business-api.tiktok.com/portal/docs/standard-event-constants-and-classes).

**Example: ****`Registration`**** Event**
```xcodeblock
(code curl http)
// Example: Track a user registration
TikTokBusinessSdk.trackTTEvent(EventName.REGISTRATION);
(/code)
```

#### Adding properties to standard events
For events that include details about products or value, use the `TTContentsEvent` builder class for the following standard events, and `TTBaseEvent` for all other standard events:
```xtable
|Standard event<br><br>{20%}|Builder class<br><br>{80%}|
|---|---|
|Add to Cart|`TTAddToCartEvent`|
|Add to Wishlist|`TTAddToWishlistEvent`|
|Checkout|`TTCheckoutEvent`|
|Purchase|`TTPurchaseEvent`|
|View Content|`TTViewContentEvent`|
```
****

**Example: ****`ViewContent`**** event builder class**
```xcodeblock
(code curl http)
// Example: A user views a product
TTContentsEvent viewContentEvent = TTViewContentEvent.newBuilder()
    .setContentType("sku")
    .setCurrency(Currency.USD)
    .setValue(99.99)
    .setDescription("A fantastic product")
    .setContents(TTContentParams.newBuilder()
        .setPrice(99.99f)
        .setQuantity(1)
        .setContentId("12345")
        .setContentCategory("Electronics")
        .setContentName("Wireless Headphones")
        .setBrand("AudioBrand")
        .build())
    .build();

TikTokBusinessSdk.trackTTEvent(viewContentEvent);
(/code)
```

**Example: ****`CompleteTutorial`**** event**

For other standard events with standard properties, use the `TTBaseEvent` builder with a constant from the `EventName` enum.

```xcodeblock
(code curl http)
// Example: A user completes the onboarding tutorial
TTBaseEvent tutorialEvent = TTBaseEvent.newBuilder(EventName.COMPLETE_TUTORIAL)
    .addProperty("currency", "<currency>") // The ISO 4217 currency code
    .addProperty("value", "<value>") // Value of the order or items sold
    .addProperty("content_id", "<content id value>") // Unique ID of the product or content
    .addProperty("content_type", "<content type value>") // The type of content in the event
    .addProperty("price", "<price>") // The price of the item
    .addProperty("quantity", "<quantity>") // The number of items
    .build();

TikTokBusinessSdk.trackTTEvent(tutorialEvent);
(/code)
```

> <span style="color: DodgerBlue">**Note**</span><br>
> For a full list of supported standard properties, refer to the [Properties object reference](https://business-api.tiktok.com/portal/docs/setup-guide-for-app/v1.3#item-link-properties%20parameters). Custom property keys are not supported and will be discarded.

#### In-app ad revenue
Starting from SDK version 1.5.0, in-app advertising mediation revenue data can be reported using `TTAdRevenueEvent`. Refer to [In-app ad revenue reporting](https://business-api.tiktok.com/portal/docs/in-app-ad-revenue-reporting/v1.3) for detailed implementation guidelines for AppLovin Max, ironSource, and Google AdMob.
#### Custom events
To track an event not listed in the standard events, use the `TTBaseEvent` builder with a custom event name string.
```xcodeblock
(code curl http)
// Example: A user shares content to a social platform
TTBaseEvent customEvent = TTBaseEvent.newBuilder("ShareContent")
    .build();

TikTokBusinessSdk.trackTTEvent(customEvent);
(/code)
```


## Enable Advanced Matching
To improve audience matching, use `identify()` to set advanced matching properties when user information becomes available (e.g., login, sign-up), and `logout()` to clear it.
```xcodeblock
(code curl http)
// When user logs in or signs up
TikTokBusinessSdk.identify(
    "user-external-id-123",  // externalId
    "user-name-456",         // externalUserName
    "+15551234567",         // phoneNumber (E.164 format)
    "user@example.com"     // email
);

// When user logs out
TikTokBusinessSdk.logout();
(/code)
```


## Verify your integration
Use TikTok Events Manager to verify that events are being received correctly. This sends events to a separate endpoint for testing and does not affect your production data.
### Codeless test events (recommended)
This method enables device-specific event debugging without code changes. Before you start, ensure that:
- The latest version of the TikTok app is installed on your device
- You have SDK version 1.5.0 or higher in your app
- You have granted Advertising ID permission for both your app and the TikTok app.

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
The SDK must be initialized to debug mode for all generated events to be marked as test events. To do so, add `enableDebugMode()` to your `TTConfig` during initialisation. You can also use `setLogLevel()` for more detailed log output.

```xcodeblock
(code curl http)
TTConfig ttConfig = new TTConfig(getApplicationContext(), "YOUR_APP_SECRET")
    .setAppId("YOUR_APP_ID")
    .setTTAppId("YOUR_TIKTOK_APP_ID")
    .openDebugMode() // Enable debug mode
    .setLogLevel(TikTokBusinessSdk.LogLevel.DEBUG) // Enable logging

TikTokBusinessSdk.initializeSdk(ttConfig);
(/code)
```

The available log levels are:
- `NONE`: No logging.
- `INFO`: Log informational messages and exceptions.
- `WARN`: Log warnings and exceptions.
- `DEBUG`: Log detailed debug output, exceptions, and messages.
#### Disabling debug mode
Once the integration is verified, remove any `openDebugMode()` and `setLogLevel()` calls before submitting your app.
```xcodeblock
(code curl http)
// REMOVEME
// ttConfig.openDebugMode()
// ttConfig.setLogLevel(TikTokBusinessSdk.LogLevel.DEBUG)
(/code)
```

### Generate and verify events
After enabling debug mode, run your app and trigger some events. You can view the events as they arrive in real-time under the **Test Event** tab in your app's Events Manager page.

If multiple TikTok App IDs are present in your SDK configuration, test events will only show in the Events Manager ad account associated with the **first** TikTok App ID in the configuration string.

![Test event](https://images.ctfassets.net/5jqet0ab9qci/5YdxHoZ7YOJu6AnrhCHSwH/abdc782a025d645abbe91e463039e05b/Test_event.png)


## Additional features and guidance
- [Deeplinks and deferred deeplinks](https://business-api.tiktok.com/portal/docs/deeplinks-and-deferred-deeplinks/v1.3)
- [Enhance data postback](https://business-api.tiktok.com/portal/docs/enhance-data-postback/v1.3)
- [In-app ad revenue reporting](https://business-api.tiktok.com/portal/docs/in-app-ad-revenue-reporting/v1.3)
- [Compliance best practices](https://business-api.tiktok.com/portal/docs/app-events-sdk-compliance-best-practices/v1.3)


