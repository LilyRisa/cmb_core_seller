---
title: "Limited Data Use"
doc_id: 1774559446000642
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Compliance best practices / Limited Data Use"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Limited Data Use

**"Limited Data Use" (LDU)** for the **TikTok App Events SDK** is a feature designed to give businesses more control over how their event data is used in TikTok's systems.

In order to assist advertisers in adhering to specific U.S. state privacy laws that grant users the right to opt out of personal data selling and sharing, TikTok presents the Limited Data Use ("LDU") feature. Designed for TikTok's App Events SDK, LDU enables businesses to exercise more authority over the handling of their event data within TikTok's platforms. This feature is accessible in the following U.S. states: California, Colorado, Connecticut, Utah, Virginia, Iowa, Minnesota, Tennessee, Maryland, Delaware, Florida, Montana, Nebraska, New Hampshire, New Jersey, Oregon, Texas, Indiana, Kentucky.

Businesses are encouraged to consult with their legal advisors to ensure compliance with relevant laws when using TikTok's products. For additional information on newly implemented state data protection laws, please refer to our updated [jurisdictional terms](https://ads.tiktok.com/i18n/official/article?aid=10015015).

## About Limited Data Use
1. Businesses can offer their app users the option to opt out of personal data selling or sharing.
2. When utilizing tools like TikTok's App Events SDK to share signals with TikTok, businesses have the option to add a parameter, indicating the advertisers need to limit the processing of event data.
3. TikTok will acknowledge this signal as a request for limited data processing and implement the relevant limitations for eligible locations.
4. Note that Limited Data Use may decrease audience sizes and campaign effectiveness and performance within the following U.S. states: California, Colorado, Connecticut, Utah, Virginia, Iowa, Minnesota, Tennessee, Maryland, Delaware, Florida, Montana, Nebraska, New Hampshire, New Jersey, Oregon, Texas, Indiana, Kentucky, as LDU data is not used for optimization purposes.
5. Please note that it is required to include an IP address in the data sent to TikTok via the Events API to enable Limited Data Use.

## Implementing Limited Data Use (App Events SDK)
To indicate an event for limited data processing, businesses are required to add one extra line of code to their App Events SDK configuration. Once the code is added, all subsequent events will carry the LDU flag.

See example below with the added line indicating LDU (limited data use):

### Android
```xcodeblock
(code curl http)
TikTokBusinessSdk.TTConfig ttConfig = new TikTokBusinessSdk.TTConfig(context)
        .setAppId("APP_ID")
        .setTTAppId("TIKTOK_APP_ID")
        .ttConfig.enableLimitedDataUse(); //Limited data use flag
TikTokBusinessSdk.initializeSdk(ttConfig);
(/code)
```

### iOS
```xcodeblock
(code curl http)
let config = TiTokConfig.init(appId: "APP_ID", tiktokAppId: TIKTOK_APP_ID)
config?.enableLDUMode() //Limited data use flag
TikTokBusiness.initializeSdk(config)
(/code)
```

### Unity
```xcodeblock
(code curl http)
TikTokConfig config = new TikTokConfig("iOS_appId", "iOS_tiktokAppId", "Android_appId", "Android_tiktokAppId");
config.OpenLimitedDataUse();  //Limited data use flag
TikTokBusinessSDK.InitializeSdk(config);
(/code)
```

With "Limited Data Use," TikTok empowers businesses to wield greater authority over their event data while respecting users' privacy choices in accordance with state laws.

