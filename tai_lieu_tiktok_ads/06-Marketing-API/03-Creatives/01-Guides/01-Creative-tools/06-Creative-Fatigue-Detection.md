---
title: "Creative Fatigue Detection"
doc_id: 1767568085608450
path: "Marketing API / Creatives / Guides / Creative tools / Creative Fatigue Detection"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Creative Fatigue Detection

Creative Fatigue Detection is a feature that enables you to identify Creative Fatigue, which occurs when the performance of an ad starts declining due to repetitive or excessive exposure. With the Creative Fatigue Detection feature, you can actively monitor whether your ads have been repeatedly shown to the same audience. After a fatigued ad has been detected, you can optimize the performance of your ad campaign by adding new ads to the same ad group.

> <span style="color:DodgerBlue">**Note**</span><br>
> Creative Fatigue Detection is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.

## Considerations for detecting Creative Fatigue
Creative Fatigue Detection is performed by considering the following aspects:
- Declining performance:
  - The CPA (Cost Per Action) of the ad increases beyond a certain threshold.
  - The cost of the ad decreases below a certain threshold.
- Reduced reach to new audiences:
  - The number of new audiences reached by the ad is decreasing and is predicted to continue decreasing.

## How to detect Creative Fatigue
1. **Create a campaign** using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602).
2. **Create an ad group** using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114).
3. **Create an ad** using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354).
4. **Detect Creative Fatigue in ads** using [/creative_fatigue/get/](https://ads.tiktok.com/marketing_api/docs?id=1767568466842626).
5. (Optional) If a fatigued ad is detected, **add new ads to the same ad group** using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354).

## How to subscribe to fatigue status
To learn about how to subscribe to the fatigue status of an ad, all ads within an ad group, or all ads within an advertiser account, see [Webhook subscription-Subscribe to the fatigue status of your ad](https://ads.tiktok.com/marketing_api/docs?id=1738964126095362#item-link-Subscribe%20to%20the%20fatigue%20status%20of%20your%20ad).

## Related docs
- [Get Creative Fatigue Detection results](https://ads.tiktok.com/marketing_api/docs?id=1767568466842626)
- [Subscription API](https://ads.tiktok.com/marketing_api/docs?id=1739091185604609)
