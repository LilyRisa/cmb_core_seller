---
title: "Manage the balance allocation of Business Centers"
doc_id: 1781992772171778
path: "Marketing API / Business Center / Guides / Finance / Manage the balance allocation of Business Centers"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Manage the balance allocation of Business Centers

This article walks you through the steps to manage the balance allocation of Business Centers that have enabled Balance Sharing.

# Introduction
Balance Sharing, also known as automatic balance allocation, allows for automatic allocation of campaign costs from a shared credit balance among ad accounts owned by the Business Center.

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://business-api.tiktok.com/portal/docs?id=1781888800300034) for details.
   - To manage the balance allocation of Business Centers, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the **"Steps"** section) and see [App permissions](https://business-api.tiktok.com/portal/docs?id=1738855280338946) to find out how to configure permissions.
- Ensure that your Business Center has enabled Monthly Invoicing and Balance Sharing. To learn more about the steps, see [About Balance Sharing in Business Center-How to use Balance Sharing in Business Center](https://ads.tiktok.com/help/article/about-the-auto-allocate-balance-in-business-center?lang=en) and [Monthly Invoicing](https://ads.tiktok.com/help/article/credit-line?lang=en). **Once a Business Center has enabled Balance Sharing, the Business Center can NOT switch back to manual allocation mode anymore.**

# Important notes
- The budget mode and budget amount of each ad account can be updated **up to 10 times a calendar day**.
- The minimum value for `budget` is 0. Setting a negative value is not allowed and will result in an error. There is currently no limit to the maximum value.
- When you update your budget amount, certain restrictions will be applied to the value of `budget`, depending on whether you change the budget mode.
 ```xtable
|Change status of budget mode{30%}|Restrictions on budget amount{70%}|
|---|---|---|
|Stay in the same budget mode|`budget` should be no less than 105% of the cumulative spending of the budget mode.|
|Switch budget mode|<ul><li>Switch to `MONTHLY_BUDGET`:`budget` should be no less than 105% of the spending of this month.</li><li>Switch to `DAILY_BUDGET`:`budget` should be no less than 105% of the spending of this day. </li><li>Switch to `CUSTOM_BUDGET`:`budget` should be no less than 105% of the spending of either the current day or the previous day, depending on which day has actual consumption.</li></ul>|
```
- You can update the budget of up to 50 accounts at a time. Duplicate `advertiser_id` are not allowed in a single operation.


# Steps
## Check the budget limit of a Balance Sharing ad account
To check the budget limit of an account within a Business Center with Balance Sharing enabled, use [/advertiser/balance/get/](https://business-api.tiktok.com/portal/docs?id=1739939106470913). 

In the response, obtain the budget limit through `budget_mode` and `budget`.

- If a monthly/daily/custom budget has been set for the ad account, `budget_mode` will be `MONTHLY_BUDGET`/`DAILY_BUDGET`/`CUSTOM_BUDGET` and `budget` will indicate the pre-set monthly/daily/one-time budget limit. Additionally, `budget_cost` will display the actual budget that the ad account has consumed.

- If no monthly/daily/custom budget has been set for the ad account, the ad account will use the default unlimited budget. `budget_mode` will be `UNLIMITED` and both `budget` and `budget_cost` will be 0.

## Customize the budget of a Balance Sharing ad account
To edit the budget mode of an ad account within a Business Center with Balance Sharing enabled, use [/advertiser/update/](https://business-api.tiktok.com/portal/docs?id=1739939050770434). Pass `bc_id` and the object array `advertiser_budgets`. Within the `advertiser_budgets` array, specify the ad account through `advertiser_id`, set `budget_mode` to `MONTHLY_BUDGET`/`DAILY_BUDGET`/`CUSTOM_BUDGET`, and set `budget` to the new monthly/daily/one-time budget.

## Manage the budget mode of a Balance Sharing ad account

To manage the budget mode of an ad account within a Business Center with Balance Sharing enabled, use [/advertiser/update/](https://business-api.tiktok.com/portal/docs?id=1739939050770434). Pass `bc_id` and the object array `advertiser_budgets`. Then set `advertiser_budgets` based on the new budget mode you want to enable for the ad account:
- To change the budget mode from customized budget to unlimited budget:
  - Specify the ad account through `advertiser_id` .
  - Set `budget_mode` to `UNLIMITED`.
- To change the budget mode from unlimited budget to customized budget:
  - Follow the same steps outlined in <a href="#item-link-Customize the budget of a Balance Sharing ad account">Customize the budget of a Balance Sharing ad account</a>.
  
## Manage the credit balance of a Business Center
To increase or decrease the credit balance of a Business Center, use [/bc/transfer/](https://business-api.tiktok.com/portal/docs?id=1739939095321601). Configure the following settings:
- Pass `bc_id`.
- Set `transfer_level` to `BC`.
- Set `transfer_type` to `RECHARGE` or `REFUND`.
- Set `cash_amount` to the amount that you want to add to or deduct from the credit balance of Business Center.
