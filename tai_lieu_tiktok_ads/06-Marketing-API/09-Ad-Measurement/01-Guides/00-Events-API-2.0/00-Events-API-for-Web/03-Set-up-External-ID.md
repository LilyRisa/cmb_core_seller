---
title: "Set up External ID"
doc_id: 1771100952291330
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for Web / Set up External ID"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Set up External ID

External ID is an identifier that represents a user on an advertiser’s platform, such as a customer ID, loyalty card number or order ID. When shared, TikTok will try to match the ID with a user on TikTok. This increases match rates, ultimately leading to downstream benefits in measurement and optimization.

External ID can be sent to TikTok using one of the following TikTok for Business tools: Pixel SDK or Events API.

# Benefits
If you assign an ID to users visiting your site, External ID allows you to:
- **Improve signal performance**: Improve match rates across channels and capture more events.
- **Boost campaign optimization**: Power campaign bidding models to amplify conversion events.
- **Measure full-funnel events**: Capture more events with complete cross-device and cross-session matching.

> <span style="color:DodgerBlue">**Note**</span><br>
> In order to maximize the benefits of External ID, it is recommended to also have Manual and Automatic Matching set up. For Advanced Matching instructions for Pixel SDK, see [here](https://ads.tiktok.com/marketing_api/docs?id=1739585700402178).

# How does External ID work
1. A user views a specific product on your website
2. You send us a ViewContent event via browser Pixel containing:
- Hashed `external id` for that user
- Either or both of the below:
  - Hashed customer PII (email/phone)
  - Cookie information which is automatically included with all pixels
3. Within TikTok, we use the hashed PII associated with the external ID to find a match. If we find a match, we will associate the provided external ID with the TikTok user
4. A couple of days later, that same user completes a purchase on your website.
5. You send us a new event. This time, you use Events API to share the event. In the event, you include `external_id`.
6. We get your `external_id`. We're able to match this signal with the customer's prior browsing activity from the previous PII to TikTok user match.
7. The signals can then be used downstream to improve your campaign performance.

# Manage External ID
If you are sharing `external_id` via TikTok Pixel SDK or Events API and would not like TikTok to use the parameter for matching or measurement purposes, please update your setup to remove the `external_id` field.
