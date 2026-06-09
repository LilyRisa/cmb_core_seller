---
title: "Super Split Test"
doc_id: 1742668394531842
path: "Marketing API / Campaign Management / Guides / Campaign / Super Split Test"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Super Split Test

Super Split Test, commonly referred to as A/B testing, is a test mechanism provided by TikTok to help you test different variables of your ad groups or campaigns to determine which strategies work best. You can use super split test to find your optimal ad settings, get accurate and statistically significant results, and improve your advertising strategy.  

><span style="color: DodgerBlue">**Note**</span><br>
>  <ul><li>Currently, we support ad group level and campaign level split tests.<ul><li>Campaign-level split tests are currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>Comparing two ad groups from different campaigns in a split test is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul> </li><li>Starting September 30, 2024, ad groups that contain [Playable Ads](https://business-api.tiktok.com/portal/docs?id=1737730803189826) delivered to TikTok placement can no longer be used to create split tests.</li></ul>


# Campaign

Before creating a split test, please keep the following in mind: 
- For <b>ad group</b> level split tests, you can compare two ad groups from different campaigns in a split test, and the campaigns can have different advertising objectives.  For <b>campaign level</b> split tests, you can compare one to ten campaigns per cell with a maximum of two cells per test.
- For <b>ad group</b> level split tests, you can only compare ad groups that are both under iOS14 dedicated campaigns or non-iOS14 dedicated campaigns. It is not allowed to compare an ad group from an iOS14 dedicated campaign with the other from a non-iOS14 campaign. To compare ad groups under iOS14 dedicated campaigns, note that the two ad groups must belong to the same campaign and use the same app ID. For <b>campaign level</b> split tests, the campaigns in comparison must be iOS14 dedicated campaigns or non-iOS14 dedicated campaigns. To compare iOS14 dedicated campaigns, note that the campaigns must use the same app ID. 
- Campaign Budget Optimization is not supported at the campaign level. 

## Supported objectives

Currently, we support the following Auction Ads objectives (`objective_type`) in a split test at the campaign level.


- Reach (`REACH`)
- Traffic (`TRAFFIC`)
- App promotion (`APP_PROMOTION`)
- Lead generation (`LEAD_GENERATION`)
- Website conversions (`WEB_CONVERSIONS`)
- Engagement (`ENGAGEMENT`)
- Video views (`VIDEO_VIEWS`)
- Product sales (`PRODUCT_SALES`)



# Ad group

Before creating a split test, please keep the following in mind: 

- Currently, we support comparing at most two active ad groups for an <b>ad group</b> level split test. For a <b>campaign level</b> split test, we support comparing 1-10 campaigns per cell with a maximum of two cells per test.
- We support both `SCHEDULE_START_END` and continuous running as the schedule type(`schedule_type`).
- Currently, we only support the same placement for two active ad groups in an <b>ad group</b> level split test.
-  Automated Creative Optimization(ACO) is supported in an <b>ad group</b> level split test, and ACO settings don't have to be the same across two ad groups.

## Supported optimization goals
We support the following ad group optimization goals (`optimization_goal`) in a split test. Check out this table for these goals and the key metrics they support. 

 ```xtable
| Optimization Goal{40%}| Key metrics{60%}|
|---|---|
|Reach<br>  (REACH)|`COST_PER_REACH`|
|Impression<br> (SHOW)|`COST_PER_SHOW`|
|Click<br> (CLICK)|`COST_PER_CLICK`<br>`COST_PER_SHOW`|
|Leads<br> (LEAD_GENERATION)|`COST_PER_CONVERT` <br>`COST_PER_CLICK` <br>`COST_PER_SHOW` <br>`CLICK_THROUGH_RATE` <br>`CONVERT_RATE`|
|Install<br>(INSTALL)|`COST_PER_CONVERT` <br>`COST_PER_CLICK` <br>`COST_PER_SHOW` <br>`CLICK_THROUGH_RATE`<br>`CONVERT_RATE`|
|Conversion<br> (CONVERT)|`COST_PER_CONVERT`<br> `COST_PER_CLICK`<br> `COST_PER_SHOW`<br> `CLICK_THROUGH_RATE`<br> `CONVERT_RATE`|
|In-app event<br> (IN_APP_EVENT)|`COST_PER_CONVERT`<br> `COST_PER_CLICK`<br> `COST_PER_SHOW`<br> `CLICK_THROUGH_RATE` <br>`CONVERT_RATE`|
|Value<br> (VALUE)|`COST_PER_CLICK`<br>`COST_PER_SHOW`<br>`COST_PER_CONVERT`<br>`COMPLETE_PAYMENT_ROAS `|
|Follow<br> (FOLLOWERS)|`COST_PER_CONVERT`<br>`COST_PER_SHOW`|
|Profile visit<br> (PROFILE_VIEWS)|`COST_PER_CONVERT`<br>`COST_PER_SHOW`|
|6-second views (Focused view)<br> (ENGAGED_VIEW) |`COST_PER_SHOW`<br>`COST_PER_FOCUSED_VIEW`<br>`FOCUSED_VIEW_RATE`|
|15-second views (Focused view), allowlisted <br>(ENGAGED_VIEW_FIFTEEN)|`COST_PER_SHOW` <br>`COST_PER_FOCUSED_VIEW` <br>`FOCUSED_VIEW_RATE`|
|Conversation<br> (CONVERSATION) |`COST_PER_CONVERT`<br>`COST_PER_CLICK` <br>`COST_PER_SHOW`<br>`CLICK_THROUGH_RATE` <br>`CONVERT_RATE`|
|Landing Page View<br> (TRAFFIC_LANDING_PAGE_VIEW) |`COST_PER_CLICK` <br>`COST_PER_SHOW`<br>`CLICK_THROUGH_RATE` <br>`COST_PER_LANDING_PAGE_VIEW` <br>`LANDING_PAGE_VIEW_RATE`|
```


# Ad

For <b>ad group</b> level split tests, instant page settings don't have to be the same across ads in two ad groups. You can use instant pages in one group while no instant pages in the other. For <b>campaign level</b> split tests, instant page settings don't have to be the same across ads in all ad groups in the same campaign.

# Related endpoints

*  [Create a split test](https://ads.tiktok.com/marketing_api/docs?id=1742666471475201)
*  [Get split test results](https://ads.tiktok.com/marketing_api/docs?id=1742666557045761)
*  [Run the winning ad group](https://ads.tiktok.com/marketing_api/docs?id=1742666543736834) 
*  [Update split test time](https://ads.tiktok.com/marketing_api/docs?id=1742666501911553)
* [End a split test](https://ads.tiktok.com/marketing_api/docs?id=1742666522399746)




