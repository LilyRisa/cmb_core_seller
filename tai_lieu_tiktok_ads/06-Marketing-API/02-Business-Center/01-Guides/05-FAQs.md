---
title: "FAQs"
doc_id: 1739562436183041
path: "Marketing API / Business Center / Guides / FAQs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# FAQs

# BC Administration

Q1:  Can I use the endpoint [/bc/advertiser/create/](https://ads.tiktok.com/marketing_api/docs?id=1739939020318721) to create an ad account for our brand?
<br>A: Yes. You can use the endpoint without applying for allowlisting if you have been allowlisted for the Business Center API.

Q2: How can I check the type of my ad account?
<br>A: Now the feature specifically designed for this is unavailable, but you can see the type (`advertiser_type`) by using endpoint [/bc/billing_group/get/](https://ads.tiktok.com/marketing_api/docs?id=1738944324279297).

Q3:  Can I subscribe to my account status change update?
<br>A：No, you currently cannot.  We now support suscriptions through webhooks (endpoint [/subscription/subscribe/](https://ads.tiktok.com/marketing_api/docs?id=1739092028876801)) to help you get real-time updates for new leads and the review status of your ad (when the ad is rejected). 

# BC Payments

Q1: Why does the data I obtained from endpoint [/advertiser/balance/get/](https://ads.tiktok.com/marketing_api/docs?id=1739939106470913) seem inaccurate after I added money to my account?
<br>A: After you finish the top-up, the endpoint [/advertiser/balance/get/](https://ads.tiktok.com/marketing_api/docs?id=1739939106470913) needs 30 seconds to synchronize the change.

Q2:   Could you clarify the exchange rate for Business Center?
<br>A: There is no exchange rate for Business Center. The currency for Business Center is set by the advertiser, and now Business Center doesn’t support currency conversion.

Q3:  Which endpoint can I use to check whether a certain ad account(`advertiser_id`) can be topped-up by a Business Center (`bc_id`)?
<br>A: You can use the endpoint [/bc/asset/member/get/](https://ads.tiktok.com/marketing_api/docs?id=1739659558931458), and set the `asset_id` as `advertiser_id` in the request. If the response is not null, then the ad account (`advertiser_id`)  can be topped-up by the Business Center (`bc_id`).

# BC Assets

Q1: How can I manage Pixel as an asset in Business Center?
<br>A: You need to have admin permission of the ad account. 
<br>First log in to the Business Center platform, and then once you have Admin permission of the ad account, you can use the [/bc/pixel/transfer/](https://ads.tiktok.com/marketing_api/docs?id=1739659520972802) endpoint and pass in Pixel Code(`pixel_code`) to transfer the pixel to Business Center as asset.






  




