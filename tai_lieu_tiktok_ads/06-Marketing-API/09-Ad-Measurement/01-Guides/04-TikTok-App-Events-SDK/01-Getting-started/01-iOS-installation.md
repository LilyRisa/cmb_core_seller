---
title: "iOS installation"
doc_id: 1739585432134657
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Getting started / iOS installation"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# iOS installation

> <span style="color:DarkRed">**Important**</span><br>
> Use of the TikTok App Events SDK is governed by the [TikTok For Business Commercial Terms of Service](https://ads.tiktok.com/i18n/official/policy/commercial-terms-of-service). By using the TikTok App Events SDK, you also agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.

The TikTok App Events SDK for iOS is maintained in a [public GitHub repository](https://github.com/tiktok/tiktok-business-ios-sdk).

## Prerequisites
Before you begin, ensure you have the following from your TikTok for Business account:
- Your Apple App's **App ID** (e.g., `123456789`). This is your application's numeric Apple App Store ID.
- A **TikTok App ID** from your app's settings page in Events Manager.
- An **App Secret** for verification.
If you do not have these, refer to [Getting started](https://business-api.tiktok.com/portal/docs/tiktok-app-events-sdk-getting-started/v1.3) before proceeding further.


## Add the SDK as a build dependency
### CocoaPods integration
1. In your `Podfile`, add the `TikTokBusinessSDK` pod:
```xcodeblock
(code curl http)
# Podfile
pod 'TikTokBusinessSDK'
(/code)
```

2. Install the pod by running the following command in your project directory:
```xcodeblock
(code curl http)
pod install
(/code)
```

### Swift Package Manager integration
We have supported Swift Package Manager since 1.3.8.

1. In Xcode, go to **File → Add Package Dependencies**

![Add Package Dependencies](https://images.ctfassets.net/5jqet0ab9qci/5ijuHmAnshPT1jsmie8tVt/5aa0c6d0b75c6ae02cd4d02ab02945c9/Add_Package_Dependencies.png)

2. Enter the TikTokBusinessSDK GitHub repository URL in the search field: https://github.com/tiktok/tiktok-business-ios-sdk

![Enter the TikTokBusinessSDK GitHub repository URL](https://images.ctfassets.net/5jqet0ab9qci/5iknJZKfU3shqkhtvKyce9/77ea2db6d2fdbd846c7e0968b9710833/Enter_the_TikTokBusinessSDK_GitHub_repository_URL.png)

3. Select your preferred SDK version and add to desired target

4. Select **Add Package** to finish adding the SDK to your project.

![Add Package](https://images.ctfassets.net/5jqet0ab9qci/6bF0RtxIFScMcnA2ix3DNq/dc48723c25edb817f3333634ec0f6b8c/Add_Package.png)

### Manual integration (xcframework)
1. Download the latest `TikTokBusinessSDK.xcframework.zip` from the [GitHub releases page](https://github.com/tiktok/tiktok-business-ios-sdk/releases/latest).
2. Unzip the downloaded file.
3. In Xcode, navigate to your project's settings, select your app target, and go to the **General** tab.
4. Drag and drop the `TikTokBusinessSDK.xcframework` into the **Frameworks, Libraries, and Embedded Content** section.
### Configure build settings
In your app target's **Build Settings** in Xcode, find **Other Linker Flags** and add `-ObjC` and `-lc++`.

### Configure for App Tracking Transparency (ATT)
Starting with iOS 14.5, you must request user consent to access the device's advertising identifier (IDFA).

1. In your `Info.plist` file, add the `NSUserTrackingUsageDescription` key with a message explaining why you are requesting tracking permission.
```xcodeblock
(code curl http)
<!-- Info.plist -->
<key>NSUserTrackingUsageDescription</key>
<string>YOUR_CUSTOM_DESCRIPTION</string>
(/code)
```

2. Request authorisation from the user at an appropriate point in your app's lifecycle.
```xcodeblock
(code curl http)
// Swift
func requestIDFA() {
  if #available(iOS 14, *) {
    TikTokBusiness.requestTrackingAuthorization { status in
      // Handle authorization status
    }
  }
}
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
- (void)requestIDFA {
  if (@available(iOS 14, *)) {
    [TikTokBusiness requestTrackingAuthorizationWithCompletionHandler:^(NSUInteger status) {
      // Handle authorization status
    }];
  }
}
(/code)
```


## Configure and initialize the SDK
In your `AppDelegate`, configure and initialize the SDK within the `application(_:didFinishLaunchingWithOptions:)` method.

Create a `TikTokConfig` object with your App Secret, App ID, and TikTok App ID(s) from Events Manager. Then, call `TikTokBusiness.initializeSdk` to start the SDK. We recommend using the completion handler to manage success or failure.
> <span style="color: DodgerBlue">**Note**</span><br>
> **Handling Multiple TikTok App IDs** <br>
> If you work with multiple Ad Accounts, you can provide a comma-separated string of TikTok App IDs. Ensure the string contains no spaces and does not end with a comma. The SDK will not initialize if there is a mismatch between the App ID and TikTok App ID(s).

```xcodeblock
(code curl http)
// Swift
// AppDelegate.swift

import TikTokBusinessSDK

@main
class AppDelegate: UIResponder, UIApplicationDelegate {

    func application(_ application: UIApplication, didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?) -> Bool {

        guard let config = TikTokConfig(accessToken: "YOUR_APP_SECRET", appId: "YOUR_APP_ID", tiktokAppId: "YOUR_TIKTOK_APP_ID") else {
            return true
        }

        // For multiple TikTok App IDs:
        // guard let config = TikTokConfig(accessToken: "YOUR_APP_SECRET", appId: "YOUR_APP_ID", tiktokAppId: "TIKTOK_APP_ID1,TIKTOK_APP_ID2,TIKTOK_APP_ID3") else {
        //     return true
        // }

        // Continue to initialisation...
        TikTokBusiness.initializeSdk(config) { (success, error) in
            if success {
                print("TikTok App Events SDK initialized successfully")
            } else {
                print("TikTok App Events SDK initialisation failed: \(error?.localizedDescription ?? "Unknown error")")
            }
        }

        return true
    }
}
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
// AppDelegate.m

#import <TikTokBusinessSDK/TikTokBusinessSDK.h>

@implementation AppDelegate

- (BOOL)application:(UIApplication *)application didFinishLaunchingWithOptions:(NSDictionary *)launchOptions {

    TikTokConfig *config = [TikTokConfig configWithAccessToken:@"YOUR_APP_SECRET" appId:@"YOUR_APP_ID" tiktokAppId:@"YOUR_TIKTOK_APP_ID"];

    // For multiple TikTok App IDs:
    // TikTokConfig *config = [TikTokConfig configWithAccessToken:@"YOUR_APP_SECRET" appId:@"YOUR_APP_ID" tiktokAppId:@"TIKTOK_APP_ID1,TIKTOK_APP_ID2,TIKTOK_APP_ID3"];

    // Continue to initialisation...
    [TikTokBusiness initializeSdk:config completionHandler:^(BOOL success, NSError * _Nullable error) {
        if (success) {
            NSLog(@"TikTok App Events SDK initialized successfully");
        } else {
            NSLog(@"TikTok App Events SDK initialisation failed: %@", error.localizedDescription);
        }
    }];
}
(/code)
```

### Configure SKAdNetwork updates in your app
The SDK supports Apple's SKAdNetwork (SKAN) framework for privacy-preserving attribution. Tracked events are mapped to your configured SKAN schema, and the appropriate Apple APIs are called to update conversion values. A schema map is required to accurately report the conversion values received from Apple. To configure this map, refer to [SKAdNetwork reporting and schema mapping](https://business-api.tiktok.com/portal/docs/skadnetwork-reporting-and-schema-mapping/v1.3).

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

### Delaying event transmission
When integrating TikTok App Events SDK, clients may need to configure the SDK to delay data transmission and event collection pursuant to jurisdictional-specific legal requirements. TikTok offers resources to support clients' compliance with these laws and can be integrated by following [Resources for data sharing after user consent](https://business-api.tiktok.com/portal/docs/resources-for-data-sharing-after-user-consent/v1.3).


## App event reporting
### Automatically logged events
The SDK automatically logs the following events if they are not disabled during configuration.
```xtable
|Event {30%}|Description{70%}|
|---|---|
|`InstallApp`|First-time app launch after installation.<br><br>You may notice an increase in reported install events when the SDK is integrated for the first time. This is expected, as installs are measured based on the first detectable app launch by the SDK. If this is a concern, allow a few days for your existing user base to update their apps before running paid campaigns.|
|`LaunchAPP`|On every app launch.|
|`2DRetention`|App is opened the day after the first app launch.|
|`Purchase`|A successful transaction is completed via Apple's StoreKit. This includes consumable, non-consumable, and subscription purchases.|
```
You have the option to disable automatic event logging. Disabling automatic event logging means that events such as app installs and app launches will no longer be tracked by default.

If you require these events for measurement or analytics, you must implement manual logging in your app code to capture and send them.

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

### Manually logged events
#### Standard events
For simple events without properties, create a `TikTokBaseEvent` with an event constant from the `TTEventName` enum. For a full list of `TTEventName` enums, refer to [Standard event constants and classes](https://business-api.tiktok.com/portal/docs/standard-event-constants-and-classes/v1.3).

**Example: ****`Registration`**** Event**
```xcodeblock
(code curl http)
// Swift
let registrationEvent = TikTokBaseEvent(eventName: TTEventName.registration.rawValue)
TikTokBusiness.trackTTEvent(registrationEvent)
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
TikTokBaseEvent *registrationEvent = [TikTokBaseEvent eventWithName:TTEventNameRegistration];
[TikTokBusiness trackTTEvent:registrationEvent];
(/code)
```

#### Adding properties to standard events
For events that include details about products or value, use the `TikTokContentsEvent` builder class for the following standard events, and `TikTokBaseEvent` for all other standard events:
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
// Swift
// Example: A user views a product
let contentParams = TikTokContentParams()
contentParams.price = 99.99
contentParams.quantity = 1
contentParams.contentId = "12345"
contentParams.contentCategory = "Electronics"
contentParams.contentName = "Wireless Headphones"
contentParams.brand = "AudioBrand"

let viewContentEvent: TikTokContentsEvent = TikTokViewContentEvent()
viewContentEvent.setValue("99.99")
viewContentEvent.setCurrency(TTCurrency.USD)
viewContentEvent.setDescription("A fantastic product")
viewContentEvent.setContentType("product_group")
viewContentEvent.setContents([contentParams])

TikTokBusiness.trackTTEvent(viewContentEvent)
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
// Example: A user views a product
TikTokContentParams *contentParams = [[TikTokContentParams alloc] init];
contentParams.price = @(99.99);
contentParams.quantity = 1;
contentParams.contentId = @"12345";
contentParams.contentCategory = @"Electronics";
contentParams.contentName = @"Wireless Headphones";
contentParams.brand = @"AudioBrand";

TikTokViewContentEvent *viewContentEvent = [[TikTokViewContentEvent alloc] init];
[viewContentEvent setValue:@"99.99"];
[viewContentEvent setCurrency:TTCurrencyUSD];
[viewContentEvent setDescription:@"A fantastic product"];
[viewContentEvent setContentType:@"product_group"];
[viewContentEvent setContents:@[contentParams]];

[TikTokBusiness trackTTEvent:viewContentEvent];
(/code)
```

**Example: ****`CompleteTutorial`**** event**

For other standard events with standard properties, use the `TikTokBaseEvent` builder with a constant from the `TTEventName` enum.
```xcodeblock
(code curl http)
// Swift
// Example: A user completes the onboarding tutorial
let tutorialEvent = TikTokBaseEvent(eventName: TTEventName.completeTutorial.rawValue)
tutorialEvent.addProperty(withKey: "currency", value: TTCurrency.USD) // ISO 4217 currency code
tutorialEvent.addProperty(withKey: "value", value: 9.99) // Order or items value
tutorialEvent.addProperty(withKey: "content_id", value: "CONTENT_ID") // Unique product/content ID
tutorialEvent.addProperty(withKey: "content_type", value: "CONTENT_TYPE") // Type of content
tutorialEvent.addProperty(withKey: "price", value: 9.99) // Item price
tutorialEvent.addProperty(withKey: "quantity", value: 1) // Number of items
TikTokBusiness.trackTTEvent(tutorialEvent)
(/code)
```

```xcodeblock
(code curl http)
// Objective‑C
// Example: A user completes the onboarding tutorial
TikTokBaseEvent *tutorialEvent = [TikTokBaseEvent eventWithName:TTEventNameCompleteTutorial];
[tutorialEvent addPropertyWithKey:@"currency" value:TTCurrencyUSD]; // ISO 4217 currency code
[tutorialEvent addPropertyWithKey:@"value" value:@(9.99)]; // Order or items value
[tutorialEvent addPropertyWithKey:@"content_id" value:@"CONTENT_ID"]; // Unique product/content ID
[tutorialEvent addPropertyWithKey:@"content_type" value:@"CONTENT_TYPE"]; // Type of content
[tutorialEvent addPropertyWithKey:@"price" value:@(9.99)]; // Item price
[tutorialEvent addPropertyWithKey:@"quantity" value:@(1)]; // Number of items
[TikTokBusiness trackTTEvent:tutorialEvent];
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
// Swift
// Example: A user shares content to a social platform
let customEvent = TikTokBaseEvent(eventName: "ShareContent")
TikTokBusiness.trackTTEvent(customEvent)
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
// Example: A user shares content to a social platform
TikTokBaseEvent *customEvent = [TikTokBaseEvent eventWithName:@"ShareContent"];
[TikTokBusiness trackTTEvent:customEvent];
(/code)
```


## Enable Advanced Matching
To improve audience matching, use `identify()` to set advanced matching properties when user information becomes available (e.g., after login or sign-up), and `logout()` to clear it.
```xcodeblock
(code curl http)
// Swift
// When user logs in or signs up
TikTokBusiness.identify(
    withExternalID: "user-external-id-123",
    externalUserName: "user-name-456",
    phoneNumber: "+15551234567", // E.164 format
    email: "user@example.com"
)

// When user logs out
TikTokBusiness.logout()
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
// When user logs in or signs up
[TikTokBusiness identifyWithExternalID:@"user-external-id-123"
                      externalUserName:@"user-name-456"
                           phoneNumber:@"+15551234567" // E.164 format
                                 email:@"user@example.com"];

// When user logs out
[TikTokBusiness logout];
(/code)
```

**Supported Properties**

As per standard industry practice, customer emails and phone numbers will be hashed with SHA256 before leaving the device. TikTok is ISO 27001 certified in the US and UK. Learn more about how this information is used in our [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms).


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
- You have granted App Tracking Transparency (ATT) permission for both your app and the TikTok app.

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
The SDK must be initialized to debug mode for all generated events to be marked as test events. To do so, add `enableDebugMode()` to your `TikTokConfig` during initialisation. You can also use `setLogLevel()` for more detailed output in the Xcode console.

```xcodeblock
(code curl http)
// Swift
guard let config = TikTokConfig(accessToken: "YOUR_APP_SECRET", appId: "YOUR_APP_ID", tiktokAppId: "YOUR_TIKTOK_APP_ID") else {
    return true
}
config.enableDebugMode()
config.setLogLevel(TikTokLogLevelVerbose) // Optional: for detailed logs

TikTokBusiness.initializeSdk(config)
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
TikTokConfig *config = [TikTokConfig configWithAccessToken:@"YOUR_APP_SECRET" appId:@"YOUR_APP_ID" tiktokAppId:@"YOUR_TIKTOK_APP_ID"];
[config enableDebugMode];
[config setLogLevel:TikTokLogLevelVerbose]; // Optional: for detailed logs

[TikTokBusiness initializeSdk:config];
(/code)
```

The available log levels are:
- `TikTokLogLevelNone`
- `TikTokLogLevelInfo`
- `TikTokLogLevelWarn`
- `TikTokLogLevelDebug`
- `TikTokLogLevelVerbose`
#### Disabling debug mode
Once your integration is verified, remove the `enableDebugMode()` and `setLogLevel()` calls before submitting your app to the App Store.
```xcodeblock
(code curl http)
// Swift
// REMOVEME
// config.enableDebugMode()
// config.setLogLevel(TikTokLogLevelVerbose)
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
// REMOVEME
// [config enableDebugMode];
// [config setLogLevel:TikTokLogLevelVerbose];
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


