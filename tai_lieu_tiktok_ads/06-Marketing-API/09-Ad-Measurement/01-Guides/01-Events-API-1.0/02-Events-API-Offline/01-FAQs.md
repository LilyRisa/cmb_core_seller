---
title: "FAQs"
doc_id: 1758052897775617
path: "Marketing API / Ad Measurement / Guides / Events API 1.0 / Events API: Offline / FAQs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# FAQs

**Q1: I see 40002 response code like the following instead of 200, what can I do?**
```xcodeblock
(code)
{"code":40002,"message":"No permission to operate pixel code: D1VF1C8AIKHA8PLC3GAM","request_id":"3021042921460500023604014862AFFF38"}
(/code)
```

A1: Make sure you are the admin or operator under this advertiser account. Please refer to [Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097) to learn more about different return codes used by Events API.

**Q2: Is there any rate limit that we should be aware of?**

A2: Fore rate limits, see [here](https://ads.tiktok.com/marketing_api/docs?id=1740029171730433). You can find out your current develoepr app QPS limitation under My Apps.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/939965e57eb8726019ceb20d9f663046" width="20%" style="color:#808080"/>

**Q3: What is the max count of events that can be sent in a batch?**

A3: Recommendation is less than **100 per batch** with a total batch size less than **10 MBs**.


**Q4: If I upload offline events from 90 days ago, will TikTok still match them to ads and show them in the reporting?**

A4: No, TikTok will not match and report on offline events that occurred 90 days ago. For offline events, you have 28 days to upload your Offline Event Set data. The maximum attribution window for click-through attribution (CTA) is 28 days, while the maximum attribution window for view-through attribution (VTA) is 7 days. This means that offline events beyond these attribution windows will not be matched to ads or displayed in the reporting. 
