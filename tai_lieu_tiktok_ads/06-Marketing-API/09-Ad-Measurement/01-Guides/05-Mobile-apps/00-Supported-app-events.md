---
title: "Supported app events"
doc_id: 1739585574120449
path: "Marketing API / Ad Measurement / Guides / Mobile apps / Supported app events"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported app events


> <span style="color: dodgerblue">**Notes**</span><br>Event names for data connection purposes (the `event` field) are different from event names in ad group creation (the `optimization_event` field). Please see the corresponding columns for the correct values. 

  
```xtable
|Event name for event data connection (value for event field){25}|Description{25%}|Recommended values for properties field {25%}|Event name for ad group creation (value for optimization_event){25%}|
|---|--- |--- |
|AchieveLevel|Achieve a level|| `UPDATE_LEVEL` |
|AddPaymentInfo|Add payment information||`ADD_PAYMENT_INFO` |
|AddToCart|Add to cart|`content_type`, `content_id`, `description`, `currency`, `value`|`IN_APP_CART`|
|AddToWishlist|Add to wishlist|`content_type`, `content_id`, `description`, `currency`, `value`|`ADD_TO_WISHLIST`|
|Checkout|Checkout|`description`, `content_id`, `quantity`, `currency`, `value`|`IN_APP_ORDER`|
| CompleteTutorial|Complete the tutorial||`COMPLETE_TUTORIAL`  |
|CreateGroup|Create a group||`CREATE_GROUP`|
|CreateRole|Create a role||`CREATE_GAMEROLE`|
|GenerateLead|Generate a lead||`SALES_LEAD` |
|InAppADClick|In-app ad click||`IN_APP_AD_CLICK` |
|InAppADImpr|In-app ad impression|| `IN_APP_AD_IMPR`|
|InstallApp|Install the app||`ACTIVE` |
|JoinGroup|Join a group|| `JOIN_GROUP`|
|LaunchAPP|Launch the app||`LAUNCH_APP`|
|LoanApplication|Apply for a loan||`LOAN_APPLY`|
|LoanApproval|Loan is approved||`LOAN_CREDIT`|
|LoanDisbursal|Loan is disbursed| |`LOAN_COMPLETION`|
|Login|Log in successfully||`LOGIN` |
|Purchase|Purchase|`content_type`, `content_id`, `description`, `currency`, `value`|`ACTIVE_PAY`|
|Rate|Rate||`RATE` |
|Registration|Complete the registration||`ACTIVE_REGISTER`|
|Search|Search||`SEARCH` |
|SpendCredits|Spend credits||`SPEND_CREDITS` |
|StartTrial|Start the trial||`START_TRIAL` |
|Subscribe|Subscribe|| `SUBSCRIBE`|
|UnlockAchievement|Unlock an achievement||`UNLOCK_ACHIEVEMENT`|
|ViewContent|View details|`content_type`, `content_id`, `description`, `currency`, `value`|`IN_APP_DETAIL_UV`|
```
