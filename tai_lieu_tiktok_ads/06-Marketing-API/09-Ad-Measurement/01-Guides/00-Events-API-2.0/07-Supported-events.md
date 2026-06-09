---
title: "Supported events"
doc_id: 1771101186666498
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Supported events"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported events

Events are defined as actions a visitor takes to achieve a business goal, such as adding an item to a cart, filling out a form, or making a purchase. 

TikTok supports two categories of events: 
- **Standard Events**: These are pre-defined actions with specific names created by TikTok. Standard events are compatible with all TikTok integration tools and can be used for reporting, conversion optimization, and audience building. For a list of supported standard events, continue reading below.
- **Custom Events**: These are actions that TikTok partners can define beyond the list of pre-defined standard events. Custom events can be set up using the Event Builder in Events Manager or reported directly through the Pixel and Events API.  <br>**Custom events (currently only custom web, app, and CRM (Customer relationship management) events are supported) are available for reporting and audience creation purposes. Optimization is NOT supported at this time.** To learn about how to use custom events, refer to [Standard and Custom Events](https://ads.tiktok.com/help/article/standard-events-parameters?lang=en).

## Web Standard Events
The table below lists all the standard events supported on the web, including offline events where the `event_source` is `web`, along with the recommended fields for the `properties` object. Note that the names of the events are case-sensitive, so it is important to use the exact name shown in the **Event** column as the `event` parameter in [/event/track/](https://ads.tiktok.com/marketing_api/docs?id=1771101303285761).

```xtable
| Event{25%} | Description{37%} | Recommended fields in <code>properties</code> object{38%} |
|---|---|---|
| `AddPaymentInfo` | When payment information is added in the checkout flow.   |   |
| `AddToCart` | When an item is added to the shopping cart. | `content_type`, `quantity`, `description`, `content_id`, `currency`, `value` |
| `AddToWishlist` | When an item is added to a wishlist.   |  |
| `ApplicationApproval` | When an application is approved.  |  |
| `CompleteRegistration` | When a registration is completed.   | |
| `Contact` | When contact or consultation occurs.   | |
| `CustomizeProduct` | When someone customizes a product. |  |
| `Download` | When a button to open an external browser download page is clicked.   | |
| `FindLocation` | When someone tries to find your location.  |  |
| `InitiateCheckout` | When the checkout process is started.   | |
| `Lead` | When a form is submitted.   | |
| `Purchase` | When a payment is completed. | `content_type`, `quantity`, `description`, `content_id`, `currency`, `value`  |
| `Schedule` | When someone makes an appointment to speak with your business or visit your location.| |
| `Search` | When a search is made.   |  |
| `StartTrial` | When someone begins a free trial of a product or service. | `currency`, `value`  |
| `SubmitApplication` | When someone submits an application |  |
| `Subscribe` | When a subscription is made.   | |
| `ViewContent` | When a page is viewed. | `content_type`, `quantity`, `description`, `content_id`, `currency`, `value` |
```

## Offline Standard Events
The set of standard events supported for offline events is identical to that supported for web events.
```xtable
| Event{25%} | Description{37%} | Recommended fields in <code>properties</code> object{38%} |
|---|---|---|
| `AddPaymentInfo` | When payment information is added in the checkout flow.   |   |
| `AddToCart` | When an item is added to the shopping cart. | `content_type`, `quantity`, `description`, `content_id`, `currency`, `value` |
| `AddToWishlist` | When an item is added to a wishlist.   |  |
| `ApplicationApproval` | When an application is approved.  |  |
| `CompleteRegistration` | When a registration is completed.   | |
| `Contact` | When contact or consultation occurs.   | |
| `CustomizeProduct` | When someone customizes a product. |  |
| `Download` | When a button to open an external browser download page is clicked.   | |
| `FindLocation` | When someone tries to find your location.  |  |
| `InitiateCheckout` | When the checkout process is started.   | |
| `Lead` | When a form is submitted.   | |
| `Purchase` | When a payment is completed. | `content_type`, `quantity`, `description`, `content_id`, `currency`, `value`  |
| `Schedule` | When someone makes an appointment to speak with your business or visit your location.| |
| `Search` | When a search is made.   |  |
| `StartTrial` | When someone begins a free trial of a product or service. | `currency`, `value`  |
| `SubmitApplication` | When someone submits an application |  |
| `Subscribe` | When a subscription is made.   | |
| `ViewContent` | When a page is viewed. | `content_type`, `quantity`, `description`, `content_id`, `currency`, `value` |
```

## App Standard Events
> <span style="color:DodgerBlue">**Note**</span><br>
> Event names for data connection purposes (the `event` field) are different from event names in ad group creation (the `optimization_event` field). See the corresponding columns for the correct values.
```xtable
| Event name for event data connection <br>(value for <code>event</code> field) {30%}| Description{20%} | Recommended values for <code>properties</code> field {25%} | Event name for ad group creation <br>(value for <code>optimization_event</code>){25%} |
|---|---|----|---|
| `AchieveLevel` | Achieve a level   |  | `UPDATE_LEVEL` |
| `AddPaymentInfo` | Add payment information   |  |`ADD_PAYMENT_INFO` |
| `AddToCart` | Add to cart | `content_type`, `content_id`, `description`, `currency`, `value` | `IN_APP_CART` |
| `AddToWishlist` | Add to wishlist |  `content_type`, `content_id`, `description`, `currency`, `value`| `ADD_TO_WISHLIST` |
| `Checkout` | Place an order | `content_type`, `content_id`, `quantity`, `currency`, `value` | `IN_APP_ORDER` |
| `CompleteTutorial` | Complete the tutorial   |   |`COMPLETE_TUTORIAL` |
| `CreateGroup` | Create a group   |  | `CREATE_GROUP` |
| `CreateRole` | Create a role   |   |`CREATE_GAMEROLE` |
| `GenerateLead` | Generate a lead   |   |`SALES_LEAD` |
| `InAppADClick` | In-app ad click   |  | `IN_APP_AD_CLICK` |
| `InAppADImpr` | In-app ad impression   | | `IN_APP_AD_IMPR` |
| `InstallApp` | Install the app   |  | `ACTIVE` |
| `JoinGroup` | Join a group   |  | `JOIN_GROUP` |
| `LaunchAPP` | Launch the app   |  | `LAUNCH_APP` |
| `LoanApplication` | Apply for a loan  |   | `LOAN_APPLY` |
| `LoanApproval` | Loan is approved   |  | `LOAN_CREDIT` |
| `LoanDisbursal` | Loan is disbursed   |  | `LOAN_COMPLETION` |
| `Login` | Log in successfully     | | `LOGIN` |
| `Purchase` | Complete payment | `content_type`, `content_id`, `description`, `currency`, `value` | `ACTIVE_PAY` |
| `Rate` | Rate   |   |`RATE` |
| `Registration` | Complete the registration   |  | `ACTIVE_REGISTER` |
| `Search` | Search    | | `SEARCH` |
| `SpendCredits` | Spend credits  |   | `SPEND_CREDITS` |
| `StartTrial` | Start the trial    | | `START_TRIAL` |
| `Subscribe` | Subscribe   |  | `SUBSCRIBE` |
| `UnlockAchievement` | Unlock an achievement   |  | `UNLOCK_ACHIEVEMENT` |
| `ViewContent` | View details | `content_type`, `content_id`, `description`, `currency`, `value` | `IN_APP_DETAIL_UV` |
```

## CRM Standard Events
The set of standard events supported for CRM events is identical to that supported for web events.

```xtable
| Event{25%} | Description{37%} | Recommended fields in <code>properties</code> object{38%} |
|---|---|---|
| `AddPaymentInfo` | When payment information is added in the checkout flow.   |   |
| `AddToCart` | When an item is added to the shopping cart. | `content_type`, `quantity`, `description`, `content_id`, `currency`, `value` |
| `AddToWishlist` | When an item is added to a wishlist.   |  |
| `ApplicationApproval` | When an application is approved.  |  |
| `CompleteRegistration` | When a registration is completed.   | |
| `Contact` | When contact or consultation occurs.   | |
| `CustomizeProduct` | When someone customizes a product. |  |
| `Download` | When a button to open an external browser download page is clicked.   | |
| `FindLocation` | When someone tries to find your location.  |  |
| `InitiateCheckout` | When the checkout process is started.   | |
| `Lead` | When a form is submitted.   | |
| `Purchase` | When a payment is completed. | `content_type`, `quantity`, `description`, `content_id`, `currency`, `value`  |
| `Schedule` | When someone makes an appointment to speak with your business or visit your location.| |
| `Search` | When a search is made.   |  |
| `StartTrial` | When someone begins a free trial of a product or service. | `currency`, `value`  |
| `SubmitApplication` | When someone submits an application |  |
| `Subscribe` | When a subscription is made.   | |
| `ViewContent` | When a page is viewed. | `content_type`, `quantity`, `description`, `content_id`, `currency`, `value` |
```

