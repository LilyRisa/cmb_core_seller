---
title: "Conversion events"
doc_id: 1739361474981889
path: "Marketing API / Campaign Management / Guides / Ad group / Conversion events"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Conversion events

# App events
The table below lists the enum values for conversion event (`optimization_event`) in App events for campaign management.
```xtable
|Enum value for <code>optimization_event</code> {30%}|Event type{20%}|Notes{50%}|
|--- |--- |---|
|`ACTIVE`|Install| |
|`ACTIVE_PAY`|Purchase| |
|`ACTIVE_REGISTER`|Registration| |
|`ADD_TO_WISHLIST`|Add to Wishlist| |
|`COMPLETE_TUTORIAL`|Complete Tutorial| |
|`CREATE_GAMEROLE`|Create Role| |
|`CREATE_GROUP`|Create Group| |
|`IN_APP_AD_CLICK`|In-App Ad Click| |
|`IN_APP_AD_IMPR`|In-App Ad Impression| |
|`IN_APP_CART`|Add to Cart| |
|`IN_APP_DETAIL_UV`|View Content| |
|`JOIN_GROUP`|Join Group| |
|`LAUNCH_APP`|Launch App| |
|`LIVE_CLICK_PRODUCT_ACTION`| /| Number of clicks on the product links during the live shopping sessions. Valid when `promotion_type` is `LIVE_SHOPPING` and `optimization_goal` is `PRODUCT_CLICK_IN_LIVE`. <br><br><p><span style="color:DarkRed"><b>Note</b></span>: `LIVE_CLICK_PRODUCT_ACTION` is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
|`LIVE_STAY_TIME`| /|Time spent staying in the live shopping room. Valid when `promotion_type` is `LIVE_SHOPPING` and `optimization_goal` is `MT_LIVE_ROOM`. <br><br><p><span style="color:DarkRed"><b>Note</b></span>: `LIVE_STAY_TIME` is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
|`LOAN_COMPLETION`|Loan Disbursement| |
|`LOGIN`|Login| |
|`NEXT_DAY_OPEN`|Day 1 Retention| |
|`RATE`|Rate| |
|`SALES_LEAD`|Generate Lead| |
|`SEARCH`|Search| |
|`SPEND_CREDITS`|Spend Credit| |
|`UNLOCK_ACHIEVEMENT`|Unlock Achievement| |
|`UPDATE_LEVEL`|Achieve Level| |
|`ENGAGED_VIEW`| /|Valid only when `optimization_goal` is `ENGAGED_VIEW`. <br><br>6-second views (Focused view).<br>The number of times your video has been played at least 6 seconds, or received at least 1 engagement within 1 day of the user seeing a paid ad. Engagements to be measured: Likes, shares, follows, profile visits, clicks, hashtag clicks, music clicks, anchor clicks, and interactive add-ons activity clicks.  |
|`ENGAGED_VIEW_FIFTEEN`| /|Valid  only when `optimization_goal` is `ENGAGED_VIEW_FIFTEEN`. <br><br>15-second views (Focused view).<br>The number of times your video has been played at least 15 seconds, or received at least 1 engagement within 1 day of the user seeing a paid ad. Engagements to be measured: Likes, shares, follows, profile visits, clicks, hashtag clicks, music clicks, anchor clicks, and interactive add-ons activity clicks.  |
| `LANDING_PAGE_VIEW`|/ |Landing Page View. The user clicks and loads the landing page successfully. <br><br>The `LANDING_PAGE_VIEW` conversion event is supported for both Traffic and Product Sales objectives.<ul><li>To use `LANDING_PAGE_VIEW` for the Traffic objective, see [Enumerations - Optimization Goal](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Optimization%20Goal).</li><li>To use `LANDING_PAGE_VIEW` for the Product Sales objective, see [Optimize Landing page view for Video Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1775099621140482).</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>If `optimization_event` of an ad group is set as `LANDING_PAGE_VIEW` and `objective_type` is `TRAFFIC`, the ad group cannot be bound to a split test. |
|`PAGE_VISIT`|/|TikTok Profile Page visits and other TikTok page visits(including playlist page, hashtag page, music page, effect page, etc.)<br><p><span style="color:darkred"><b>Note</b></span>: When `optimization_goal` is `PAGE_VISIT`,  `optimization_event` will be automatically set as `PAGE_VISIT`.|
| `MESSAGE` | / | Valid only when `promotion_type` is `LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE` or `LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE` and `optimization_goal` is `CONVERSATION`. <br><br> Collect leads by TikTok direct messages or instant messaging app messages. <br><br><p><span style="color:darkred"><b>Note</b></span>: If `promotion_type` is set to `LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE` or `LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE` and `optimization_goal` is `CONVERSATION`, `optimization_event` can be omitted and will default to `MESSAGE`. |
|  `DESTINATION_VISIT`| / |Valid only when `optimization_goal` is `DESTINATION_VISIT`.<br><br>Destination Visit. Ads with this optimization goal will introduce users to your in-app page or fallback to the website. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> The optimization goal Destination Visit (`DESTINATION_VISIT`) is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li> Once you set `optimization_goal` to `DESTINATION_VISIT`, `optimization_event` can be omitted and will default to `DESTINATION_VISIT`. If you set a different value in the `optimization_event` field, the value will be ignored. </li></ul>|
|  `AD_REVENUE_VALUE`| Ad revenue|Valid only for VBO IAA (Value-Based Optimization for in-app advertising) scenarios.<br><br>Ad revenue value. <br>Optimize your revenue from in-app advertising.<br><br>To learn more about VBO IAA, see [Value-Based Optimization - Value types](https://business-api.tiktok.com/portal/docs?id=1739381743067137#item-link-Value%20types).|
```


## Secondary optimization goal

The table below lists the enum values for deep conversion event (`secondary_optimization_event`) in App events for campaign management. This field specifies the secondary optimization goal when `optimization_goal` is `INSTALL` or `VALUE`.

```xtable
|Enum values for <code>secondary_optimization_event</code> {40%}|Description{60%}|
|--- |--- |
|`ACTIVE_PAY`|Purchase. A user finishes the payment process in your app. <br>Valid when `optimization_goal` is `INSTALL`.|
|`ACTIVE_REGISTER`|Registration. A user registers in your app.<br>Valid when `optimization_goal` is `INSTALL`.|
|`ADD_PAYMENT_INFO`|Add Payment Info|
|`IN_APP_CART`|Add To Cart. A user adds an item to a shopping cart in your app. <br>Valid when `optimization_goal` is `INSTALL`.|
|`IN_APP_ORDER`|Checkout|
|`LOAN_APPLY`{-Deprecated}|Loan Apply|
|`LOAN_CREDIT`{-Deprecated}|Loan Approval|
|`NEXT_DAY_OPEN`|Day 1 Retention. <br>Valid when `optimization_goal` is `INSTALL`.|
|`PURCHASE_ROI`|ROI of the purchase. <br>Valid when `optimization_goal` is `VALUE`.|
|`START_TRIAL`{-Deprecated}|Start Trial|
|`SUBSCRIBE`|Subscribe. A user subscribes to a channel or service in your app. <br>Valid when `optimization_goal` is `INSTALL`.|
|`UPDATE_LEVEL`|Achieve Level. A user reaches a certain level that you have defined in your game. <br>Valid when `optimization_goal` is `INSTALL`.|
| `DAY7_RETENTION`|Day 7 retention. <br> Valid when the conditions below are all met: <ul><li>At the campaign level：`objective_type`= `APP_PROMOTION` or `APP_INSTALL`.</li><li>At the ad group level：<ul><li>`placements`  = `PLACEMENT_PANGLE`</li><li>`optimization_goal` = `INSTALL`</li><li>`optimization_event` = `ACTIVE`</li></ul></li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: Day 7 retention is currently an allowlist-only feature. If you would like access to it, please contact your TikTok representative.|
| `PREFERRED_LEAD` | Preferred Leads. Show your ads to people most likely to meet your preferred lead qualifications.|
```

# Pixel events
The table below lists the enum values for conversion event (`optimization_event`) in web events for campaign management.
```xtable
|Enum values for optimization_event{50%}|Description {50%}|
|--- |--- |
|`ADD_BILLING`|Add Payment Info|
|`BUTTON`{-Deprecated}|Click Button |
|`CONSULT`|Consult |
|`DOWNLOAD_START`|Download |
|`FORM`|Lead |
|`INITIATE_ORDER`|Initiate Checkout. For live shopping scenario (`promotion_type`=`LIVE_SHOPPING`), this event is managed by allowlist and valid only when `optimization_goal` is `CONVERT`.|
|`ON_WEB_ADD_TO_WISHLIST`| Add to Wishlist |
|`ON_WEB_CART`|Add to Cart |
|`ON_WEB_DETAIL`|View Content |
|`ON_WEB_ORDER`{-Deprecated}|Place an Order |
|`ON_WEB_REGISTER`|Complete Registration |
|`ON_WEB_SEARCH`|Search |
|`ON_WEB_SUBSCRIBE`| Subscribe|
|`SHOPPING`|Purchase|
```
