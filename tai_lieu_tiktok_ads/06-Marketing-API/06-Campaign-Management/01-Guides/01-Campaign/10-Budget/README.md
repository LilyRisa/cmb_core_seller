---
title: "Budget"
doc_id: 1739381246298114
path: "Marketing API / Campaign Management / Guides / Campaign / Budget"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Budget

To give more control over your advertising cost, TikTok For Business supports budget settings at both campaign and ad group level.

For effectiveness, TikTok for Business limits the minimum amount for lifetime and daily budgets. 
* **Campaign level minimum budget**
	- For campaigns, with the exception of those with `objective_type` as `PRODUCT_SALES` and` campaign_product_source` as `STORE`, both daily budgets and lifetime budgets must exceed 50 USD.
	- For campaigns with `objective_type` as `PRODUCT_SALES` and` campaign_product_source` as `STORE`, both daily budgets and lifetime budgets must exceed 10 USD.
* **Ad group level minimum budget**
	- For ad groups, with the exception of those with `product_source` as `STORE` or `SHOWCASE`, daily budgets must exceed 20 USD. Lifetime budgets will be calculated as the minimum daily budget (20 USD) × scheduled days.
	- For ad groups with `product_source` as `STORE` or `SHOWCASE`, daily budgets must exceed 10 USD. Lifetime budgets will be calculated as the minimum daily budget (10 USD) × scheduled days.

For detailed information, please refer to [About Budget](https://ads.tiktok.com/help/article/budget?redirected=1).

## Budget modes at different levels


<table style="width: 1100px">
<colgroup>
<col style="width:20%">
<col style="width:20%">
<col style="width:60%">
</colgroup>
<thead>
  <tr>
    <th>Ad Level</th>
    <th>Campaign Budget Optimization (CBO)</th>
    <th>Buget Mode<br></th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="2">Campaign<br></td>
    <td>Enabled<br></td>
    <td>When CBO is enabled, <code>budget_mode</code> at the campaign level supports the following enum values:<ul><li><code>BUDGET_MODE_TOTAL</code>: Lifetime budget.</li><li><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>: Dynamic daily budget. It is the average daily budget over a week. Daily costs will not exceed 125% of the average daily budget. Weekly costs will not exceed the average daily budget * 7.<ul><li>When <code>objective_type</code> is <code>TRAFFIC</code>, <code>APP_PROMOTION</code>, <code>WEB_CONVERSIONS</code>, <code>LEAD_GENERATION</code>, <code>PRODUCT_SALES</code> (Video Shopping Ads only), <code>REACH</code>, <code>VIDEO_VIEWS</code>, or <code>ENGAGEMENT</code>, and you want to set a non-lifetime budget, set this field to <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>.<br><p><span style="color:darkred"><b>Note</b></span>: <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> for campaign objectives <code>REACH</code>, <code>VIDEO_VIEWS</code>, and <code>ENGAGEMENT</code> is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul></li><li><code>BUDGET_MODE_DAY</code>: Daily budget.</li></ul></td>
  </tr>
  <tr>
    <td>Disabled<br></td>
    <td>When CBO is disabled, <code>budget_mode</code> at the campaign level supports the following enum values:<li><code>BUDGET_MODE_INFINITE</code>: Unlimited budget.<li><code>BUDGET_MODE_TOTAL</code>: Lifetime budget.<li><code>BUDGET_MODE_DAY</code>: Daily budget.</td>
  </tr>
  <tr>
    <td rowspan="2">Ad Group<br></td>
    <td>Enabled<br></td>
    <td>When CBO is enabled, <code>budget_mode</code> at the ad group level will be ignored.<br></td>
  </tr>
  <tr>
    <td>Disabled<br></td>
    <td>When CBO is disabled, <code>budget_mode</code> at the ad group level supports the following enum values:<ul><li><code>BUDGET_MODE_TOTAL</code>: Lifetime budget.</li><li><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>: Dynamic daily budget. It is the average daily budget over a week. Daily costs will not exceed 125% of the average daily budget. Weekly costs will not exceed the average daily budget * 7.<ul><li>When <code>objective_type</code> is <code>TRAFFIC</code>, <code>APP_PROMOTION</code>, <code>WEB_CONVERSIONS</code>, <code>LEAD_GENERATION</code>, <code>PRODUCT_SALES</code> (Video Shopping Ads only), <code>REACH</code>, <code>VIDEO_VIEWS</code>, or <code>ENGAGEMENT</code>, and you want to set a non-lifetime budget, set this field to <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>.<br><p><span style="color:darkred"><b>Note</b></span>: <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> for campaign objectives <code>REACH</code>, <code>VIDEO_VIEWS</code>, and <code>ENGAGEMENT</code> is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul></li><li><code>BUDGET_MODE_DAY</code>: Daily budget.</li></ul></td>
  </tr>
</tbody>
</table>

## Budget setting guidelines
Allowed advertising budget is related to the currency of your advertiser account. Note that the verification of the budget you specify is threefold:
- Currency precision. Different currencies may vary in the maximum decimal places you can specify. For instance, for US Dollar (USD) you can keep two decimal places at most, but for Japanese Yen (JPY) you can only specify integers. 
- Budget value range. The value range of the daily advertising budget for each currency is calculated as <strong>base daily budget value range</strong> * <strong>Budget Verification Ratio for the currency</strong>. 
	- For campaigns, with the exception of those with `objective_type` as `PRODUCT_SALES` and `campaign_product_source` as `STORE` , the base daily budget value range is \[50, 10 000 000). For campaigns with `objective_type` as `PRODUCT_SALES` and `campaign_product_source` as `STORE`, the base daily budget value range is \[10, 10 000 000).    
	- For ad groups, with the exception of those with `product_source` as `STORE` or `SHOWCASE`, the base daily budget value range is \[20,10 000 000). For ad groups with `product_source` as `STORE` or `SHOWCASE`, the base daily budget value range is \[10, 10 000 000). 
	- For example, if the currency of your advertiser account is JPY (the corresponding Budget Verification Ratio is 100) and the ad group is not with the `product_source` as `STORE` or `SHOWCASE`, the allowed daily budget value range at the ad group level is \[2 000,1 000 000 000).
- If you are updating the budget at the ad group level or campaign level, the new budget should be at least 105% of the current spend. You can run [a synchronous report](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353) or [an asynchronous report]( https://ads.tiktok.com/marketing_api/docs?id=1740302766489602) to get the spend data via the Basic data metric `spend`. 
 
><span style="color:DodgerBlue">**Note**</span><br>
>You can see the precision and Budget Verification Ratio of each currency in [Currency-Budget verification ratio and precision of each currency](https://ads.tiktok.com/marketing_api/docs?id=1737585839634433). <strong>OR</strong> you can see the maximum and minimum daily budget for each currency directly in [Currency-Daily budget value range](https://ads.tiktok.com/marketing_api/docs?id=1737585839634433).<br><br>If the ad group is created from the TikTok Ads Manager in <strong>Simplified Mode</strong>, the base minimum daily budget value at the ad group level may not be 20:<ul><li> For advertising goal Traffic, the base minimum daily budget is 5.</li><li> For advertising goal Community interaction, Lead generation, or Conversions, the base minimum daily budget is 10.</li><li> For other advertising goals, the base minimum daily budget is 20.</li></ul>

##  Budget mode combinations

`budget_mode` at the ad group level can only be set as daily budget in either of these scenarios:

1. For ad groups with delivery mode (`schedule_type`) specified as continuous delivery (`SCHEDULE_FROM_NOW`), `budget_mode` should be either `BUDGET_MODE_DAY` or `BUDGET_MODE_DYNAMIC_DAILY_BUDGET`.
2. When the `budget_mode` for the corresponding campaign is daily budget (`BUDGET_MODE_DAY`), then ad group `budget_mode` must be `BUDGET_MODE_DAY` as well.

See the table below for the allowed combinations of budget modes at campaign and ad group levels.


|  |Ad Group Lifetime Budget<p></p>`BUDGET_MODE_TOTAL` |Ad Group Daily Budget <p></p>`BUDGET_MODE_DAY`|Ad Group Dynamic Daily Budget<p></p>`BUDGET_MODE_DYNAMIC_DAILY_BUDGET`
| --- | --- | --- | --- |
|Campaign Unlimited Budget <p></p>`BUDGET_MODE_INFINITE`  | ☑️ |  ☑️ | ☑️|
|Campaign Lifetime Budget  <p></p>`BUDGET_MODE_TOTAL` |  ☑️ | ☑️  | ☑️|
|Campaign Daily Budget <p></p>`BUDGET_MODE_DAY` |  ❎ |  ☑️ | ☑️|
|Campaign Dynamic Daily Budget <p></p>`BUDGET_MODE_DYNAMIC_DAILY_BUDGET` |  The setting will be ignored as CBO is enabled. |  The setting will be ignored as CBO is enabled.   |  The setting will be ignored as CBO is enabled.  |

## Related links

* [Budget Best Practices](https://ads.tiktok.com/help/article?aid=744163857974606118)
