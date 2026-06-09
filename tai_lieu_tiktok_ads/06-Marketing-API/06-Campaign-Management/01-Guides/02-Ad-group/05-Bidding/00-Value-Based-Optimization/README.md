---
title: "Value-Based Optimization"
doc_id: 1739381743067137
path: "Marketing API / Campaign Management / Guides / Ad group / Bidding / Value-Based Optimization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Value-Based Optimization

Value-Based optimization (VBO) is a method of delivering ads to people who have a potential for becoming a high value customer. It comprises an optimization goal called `VALUE`, which includes two distinct value types, and two bidding strategies. The two bidding strategies are configured via the `deep_bid_type` field.



## Value types (for App)
Once you have set the optimization goal as Value, you can choose from the following value types:
- **Purchase Value**
<br>By selecting Purchase Value, also known as VBO IAP (Value-Based Optimization for in-app purchases), you can maximize your revenue from in-app purchases. This value type is designed to optimize your earnings by targeting users who are more likely to make valuable purchases within your app.
- **Ad Revenue Value**
<br>Alternatively, you can choose Ad Revenue Value, also known as VBO IAA (Value-Based Optimization for in-app advertising). This value type prioritizes targeting users who are more likely to generate higher ad revenue, helping you maximize your earnings from in-app advertisements.

Note that the Value optimization goal is also supported for websites, allowing you to optimize for value-based outcomes on your website. However, the preceding value types are specific to app-based Value-Based Optimization.

By selecting the appropriate value type in VBO, you can align your advertising strategy with your specific revenue goals.

> <span style="color: DodgerBlue">**Note**</span><br>VBO IAP (Value-Based Optimization for in-app purchases) and VBO IAA (Value-Based Optimization for in-app advertising) in different scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative. Some VBO IAA and VBO IAP features are available after you meet the unlocking criteria. To find out the unlocking criteria, refer to [Enable Value-Based Optimization-Unlocking criteria for VBO](https://ads.tiktok.com/marketing_api/docs?id=1770019181843458#item-link-Unlocking%20criteria%20for%20VBO).

## Bidding strategies

* **`VO_HIGHEST_VALUE`**: Highest Value. Bid without any ROAS goal or cost cap.

* **`VO_MIN_ROAS`**: Minimum ROAS. Bid with an ROAS goal.

* **`VO_MIN`** (<span style="color:red">deprecated</span>): Cost Cap. Bid with a cost cap. Note that this strategy is only supported for the advertising objective `PRODUCT_SALES`, and has been deprecated by the end of June, 2023.



## Limits

* Three advertising objectives are supported: `APP_PROMOTION`, `WEB_CONVERSIONS`, `PRODUCT_SALES`.
* You can promote websites or Android or iOS apps.
* Only the oCPM Billing Event is supported.


## Enable VBO
To learn about how to enable VBO, refer to [Enable Value-Based Optimization in Smart+ Campaigns](https://business-api.tiktok.com/portal/docs?id=1840137502583809) and [Enable Value-Based Optimization in Manual Campaigns](https://business-api.tiktok.com/portal/docs?id=1770019181843458).

