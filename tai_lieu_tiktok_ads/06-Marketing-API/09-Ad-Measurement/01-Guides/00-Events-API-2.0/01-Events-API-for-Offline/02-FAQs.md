---
title: "FAQs"
doc_id: 1771101064635394
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for Offline / FAQs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# FAQs

## Q1: Is there any rate limit that we should be aware of?

A1: To learn about the rate limit of Events API 2.0, refer to [Events API 2.0 - API limits](https://ads.tiktok.com/marketing_api/docs?id=1771100779668482#item-link-API%20limits).


## Q2: What is the max count of events that can be sent in a batch?

A2: It is recommended to send less than **100 events per batch**, with a total batch size less than **10 MB**.  

## Q3: If I upload offline events from 90 days ago, will TikTok still match them to ads and show them in the reporting?

A3: No, TikTok will not match and report on offline events that occurred 90 days ago. For offline events, you have 28 days to upload your Offline Event Set data. The maximum attribution window for click-through attribution (CTA) is 28 days, while the maximum attribution window for view-through attribution (VTA) is 7 days. This means that offline events beyond these attribution windows will not be matched to ads or displayed in the reporting. 
