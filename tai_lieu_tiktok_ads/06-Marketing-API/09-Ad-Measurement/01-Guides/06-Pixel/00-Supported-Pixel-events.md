---
title: "Supported Pixel events"
doc_id: 1739585696931842
path: "Marketing API / Ad Measurement / Guides / Pixel / Supported Pixel events"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported Pixel events

# Pixel events


Please find all the 18 Web events in the following table. 


```xtable
|Event name for ad creation (<code>optimization_event</code>){25%}|Event name for event data connection (<code>event</code>){25%}|Description{25%}|Recommended fields<br> in <code>properties</code> object{25%}|
|---|---|---|---|
|`ON_WEB_DETAIL`|ViewContent|When a page is viewed.|`content_type`, `quantity`, `description`, `content_id`, `currency`, `value`|
|`ON_WEB_SEARCH`|Search|When a search is made.| |
|`ON_WEB_ADD_TO_WISHLIST`| AddToWishlist|When an item is added to a wishlist.||
|`ON_WEB_CART`|AddToCart|When an item is added to the shopping cart.|`content_type`, `quantity`, `description`, `content_id`, `currency`, `value`|
|`INITIATE_ORDER`|InitiateCheckout|When the checkout process is started.||
|`ADD_BILLING`|AddPaymentInfo|When payment information is added in the checkout flow.||
|`SHOPPING`|Purchase|When a payment is completed.|`content_type`, `quantity`, `description`, `content_id`, `currency`, `value`|
|`CONSULT`|Contact|When contact or consultation occurs.| |
|`DOWNLOAD_START`|Download|When a button to open an external browser download page is clicked.||
|`FORM`|Lead|When a form is submitted.||
|`ON_WEB_REGISTER`|CompleteRegistration|When a registration is completed.||
|`ON_WEB_SUBSCRIBE`| Subscribe|When a subscription is made.||
|  | CustomizeProduct | When someone customizes a product. | |
|  | FindLocation | When someone tries to find your location. | |
|  | Schedule | When someone makes an appointment to speak with your business or visit your location. | |
| | ApplicationApproval | When an application is approved.  |  |
| | StartTrial | When someone begins a free trial of a product or service. | `currency`, `value`  |
| | SubmitApplication| When someone submits an application |  |
```
