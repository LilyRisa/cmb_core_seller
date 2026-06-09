---
title: "TikTok App Events SDK"
doc_id: 1739583650172929
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# TikTok App Events SDK

## About the App Events SDK

> <span style="color:DarkRed">**Important**</span><br>
> Use of the TikTok App Events SDK is governed by the [TikTok For Business Commercial Terms of Service](https://ads.tiktok.com/i18n/official/policy/commercial-terms-of-service). By using the TikTok App Events SDK, you also agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.

The TikTok App Events SDK enables advertisers and developers to send app events directly from a mobile application to TikTok. By integrating the SDK, you can measure user interactions, such as app installs, purchases, and other in-app actions. This data is then available in the TikTok Events Manager, where it can be used to build audiences for ad targeting, optimise marketing campaigns, and measure performance.

**Important Notice: A combined TikTok App Events SDK and Pangle SDK is now available.**
- The TikTok App Events SDK version 7.1.0.4 (and above), available through [About the combined TikTok App Events and Pangle SDK](https://business-api.tiktok.com/portal/docs?id=1828926473158657), is a combined SDK that includes both the TikTok App Events SDK as a module and the Pangle Ad Monetization SDK as a module, that operate independently of each other. 
- The entities providing these two SDK modules differ by region. Use of the TikTok App Events module is governed by the [TikTok For Business Commercial Terms of Service](https://ads.tiktok.com/i18n/official/policy/commercial-terms-of-service). Use of the Pangle Ad Monetization module is subject to separate legal terms  including as provided under the [Pangle Publisher Platform](https://www.pangleglobal.com/terms) or otherwise provided to you.
- To learn more about the TikTok App Events module, please visit [About TikTok App Events SDK](https://ads.tiktok.com/help/article/about-the-tiktok-app-events-sdk). To learn more about the Pangle Ad Monetization module, please visit [About Pangle Ad Monetization SDK](https://www.pangleglobal.com/integration). 
- You may choose to integrate this combined SDK by following the instructions provided under [About the combined TikTok App Events and Pangle SDK](https://business-api.tiktok.com/portal/docs?id=1828926473158657). The standalone TikTok App Events SDK continues to be offered through GitHub and the instructions provided in the rest of this documentation.
### Core features
- **Event reporting:** Log and report a wide range of standard and custom in-app events. This includes predefined events like `Purchase`, `AddToCart`, and `ViewContent`, as well as any custom events specific to your app's functionality.
- **Automatic event reporting:** The SDK can automatically report key lifecycle events such as `InstallApp`, `LaunchAPP`, and `2DRetention` with minimal setup, providing baseline user engagement metrics out of the box.
- **In-app purchase (IAP) tracking:** Automatically report in-app purchases made through StoreKit 1 or Google Play Billing Library 5.0 and above. The SDK simplifies the process of sending purchase events to TikTok for value-based optimization and Return on Ad Spend (ROAS) measurement.
- **Advanced Matching:** Reach your customers on TikTok securely. As per standard industry practice, customer emails and phone numbers will be hashed with SHA256, a secure hashing algorithm, before reaching TikTok servers for matching. TikTok is ISO 27001 certified in the US and UK. Learn more about how this information is used in our [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms).
- **Deferred deep linking:** Include<del>s</del> functionality to fetch and handle deferred deep links, allowing you to create seamless user experiences that direct users from an ad to a specific location within your app.
- **Configuration and control:** Offer<del>s</del> granular control over the SDK's behavior, including options to enable or disable advertiser ID collection, manage data flushing frequency, and switch between different logging levels for debugging.

### Supported programming languages
- For Android, TikTok App Events SDK currently supports Java & Kotlin. 
- For iOS, TikTok App Events SDK supports Objective-C & Swift. 
- For Unity, TikTok App Events SDK supports C#.
```xtable
| {15%}|GitHub Repository<br><br>{25%}|Documentation<br><br>{20%}|Minimum OS version<br><br>{20%}|Build Package Size<br><br>{20%}|
|---|---|---|---|---|
|Android|[tiktok/tiktok-business-android-sdk](https://github.com/tiktok/tiktok-business-android-sdk)|[Android installation](https://business-api.tiktok.com/portal/docs/android-integration-steps/v1.3)|Android 4.1+|~175KB|
|iOS|[tiktok/tiktok-business-ios-sdk](https://github.com/tiktok/tiktok-business-ios-sdk)|[iOS Quickstart](https://business-api.tiktok.com/portal/docs/ios-integration-steps/v1.3)|iOS 9.0+|~312KB|
|Unity|[tiktok/tiktok-business-unity-sdk](https://github.com/tiktok/tiktok-business-unity-sdk)|[Unity Quickstart](https://business-api.tiktok.com/portal/docs/unity-integration-steps/v1.3)|Unity 2021+|~3.75MB|
```

## Documentation
- Developer documentation is available here, in the [TikTok for Business Developers](https://business-api.tiktok.com/portal/docs/tiktok-app-events-sdk-overview/v1.3) portal.
- Marketing user documentation is available in the [TikTok Business Help Center](https://ads.tiktok.com/help/article/how-to-integrate-tiktok-app-events-sdk).


