---
title: "Standard event constants and classes"
doc_id: 1862287695864834
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Reference / Standard event constants and classes"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Standard event constants and classes

This document lists the standard event name constants for the TikTok App Events SDK. Use these constants to ensure consistent event tracking across both platforms.

For general events, use `TikTokBusinessSdk.trackTTEvent(EventName.YOUR_EVENT_CONSTANT)` on Android and `[TikTokBusiness trackTTEvent:yourEventConstant]` on iOS. For commerce and contents events, use the `TTContentsEvent` builder classes on Android and `TikTokContentsEvent` subclasses on iOS. If you are unable to use the provided constants or builder classes, you may reference the string value in the first "Event" column instead.

> <span style="color:darkred">**Important**</span><br>
> **Deprecation Note**
> <br>Events marked as deprecated should not be used in new integrations. They are maintained for backward compatibility but will be removed in future versions of the SDK.

## General standard events
```xtable
|Event{17%}|Android Constant{17%}|iOS Objective-C String Constant{18%}|iOS Swift Namespace Equivalent{18%}|Unity Constant{17%}|Deprecated {13%}|
|---|---|---|---|---|---|
|AchieveLevel|`EventName.ACHIEVE_LEVEL`|`TTEventNameAchieveLevel`|`TTEventName.achieveLevel.rawValue`|`TTEventName.TTEventNameAchieveLevel`|No|
|AddPaymentInfo|`EventName.ADD_PAYMENT_INFO`|`TTEventNameAddPaymentInfo`|`TTEventName.addPaymentInfo.rawValue`|`TTEventName.TTEventNameAddPaymentInfo`|No|
|CompleteTutorial|`EventName.COMPLETE_TUTORIAL`|`TTEventNameCompleteTutorial`|`TTEventName.completeTutorial.rawValue`|`TTEventName.TTEventNameCompleteTutorial`|No|
|CreateGroup|`EventName.CREATE_GROUP`|`TTEventNameCreateGroup`|`TTEventName.createGroup.rawValue`|`TTEventName.TTEventNameCreateGroup`|No|
|CreateRole|`EventName.CREATE_ROLE`|`TTEventNameCreateRole`|`TTEventName.createRole.rawValue`|`TTEventName.TTEventNameCreateRole`|No|
|GenerateLead|`EventName.GENERATE_LEAD`|`TTEventNameGenerateLead`|`TTEventName.generateLead.rawValue`|`TTEventName.TTEventNameGenerateLead`|No|
|InAppADClick|`EventName.IN_APP_AD_CLICK`|`TTEventNameInAppADClick`|`TTEventName.inAppADClick.rawValue`|`TTEventName.TTEventNameInAppADClick`|No|
|InAppAdImpr|`EventName.IN_APP_AD_IMPR`|`TTEventNameInAppADImpr`|`TTEventName.inAppADImpr.rawValue`|`TTEventName.TTEventNameInAppADImpr`|No|
|ImpressionLevelAdRevenue|`EventName.IMPRESSION_LEVEL_AD_REVENUE`|`TTEventNameImpressionLevelAdRevenue`|`TTEventName.impressionLevelAdRevenue.rawValue`|`TTEventName.TTEventNameImpressionLevelAdRevenue`|No|
|InstallApp|`EventName.INSTALL_APP`|`TTEventNameInstallApp`|`TTEventName.installApp.rawValue`|`TTEventName.TTEventNameInstallApp`|No|
|JoinGroup|`EventName.JOIN_GROUP`|`TTEventNameJoinGroup`|`TTEventName.joinGroup.rawValue`|`TTEventName.TTEventNameJoinGroup`|No|
|LaunchAPP|`EventName.LAUNCH_APP`|`TTEventNameLaunchAPP`|`TTEventName.launchAPP.rawValue`|`TTEventName.TTEventNameLaunchAPP`|No|
|Login|`EventName.LOGIN`|`TTEventNameLogin`|`TTEventName.login.rawValue`|`TTEventName.TTEventNameLogin`|No|
|Rate|`EventName.RATE`|`TTEventNameRate`|`TTEventName.rate.rawValue`|`TTEventName.TTEventNameRate`|No|
|Registration|`EventName.REGISTRATION`|`TTEventNameRegistration`|`TTEventName.registration.rawValue`|`TTEventName.TTEventNameRegistration`|No|
|Search|`EventName.SEARCH`|`TTEventNameSearch`|`TTEventName.search.rawValue`|`TTEventName.TTEventNameSearch`|No|
|SpendCredits|`EventName.SPEND_CREDITS`|`TTEventNameSpendCredits`|`TTEventName.spendCredits.rawValue`|`TTEventName.TTEventNameSpendCredits`|No|
|StartTrial|`EventName.START_TRIAL`|`TTEventNameStartTrial`|`TTEventName.startTrial.rawValue`|`TTEventName.TTEventNameStartTrial`|No|
|Subscribe|`EventName.SUBSCRIBE`|`TTEventNameSubscribe`|`TTEventName.subscribe.rawValue`|`TTEventName.TTEventNameSubscribe`|No|
|UnlockAchievement|`EventName.UNLOCK_ACHIEVEMENT`|`TTEventNameUnlockAchievement`|`TTEventName.unlockAchievement.rawValue`|`TTEventName.TTEventNameUnlockAchievement`|No|
|LoanApplication|`EventName.LOAN_APPLICATION`|`TTEventNameLoanApplication`|`TTEventName.loanApplication.rawValue`|`TTEventName.TTEventNameLoanApplication`|Yes|
|LoanApproval|`EventName.LOAN_APPROVAL`|`TTEventNameLoanApproval`|`TTEventName.loanApproval.rawValue`|`TTEventName.TTEventNameLoanApproval`|Yes|
|LoanDisbursal|`EventName.LOAN_DISBURSAL`|`TTEventNameLoanDisbursal`|`TTEventName.loanDisbursal.rawValue`|`TTEventName.TTEventNameLoanDisbursal`|Yes|
```
## Contents event classes
```xtable
|Event{17%}|Android TTContentsEvent builder subclass{21%}|iOS TikTokContentsEvent builder subclass{22%}|Unity TikTokContentsEvent builder subclass{23%}|Deprecated{17%}|
|---|---|---|---|---|
|AddToCart|`TTAddToCartEvent`|`TikTokAddToCartEvent`|`TikTokAddToCartEvent`|No|
|AddToWishlist|`TTAddToWishlistEvent`|`TikTokAddToWishlistEvent`|`TikTokAddToWishlistEvent`|No|
|Checkout|`TTCheckoutEvent`|`TikTokCheckoutEvent`|`TikTokCheckoutEvent`|No|
|Purchase|`TTPurchaseEvent`|`TikTokPurchaseEvent`|`TikTokPurchaseEvent`|No|
|ViewContent|`TTViewContentEvent`|`TikTokViewContentEvent`|`TikTokViewContentEvent`|No|
```


