---
title: "FAQs"
doc_id: 1749819287162881
path: "Marketing API / Ad Measurement / Guides / Events API 1.0 / Events API : Web / FAQs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# FAQs

**Q1: I see 40001 response code like the following instead of 0, what can I do?**

```xcodeblock
(code)
{
    "code":40001,
    "message":"No permission to operate pixel code: D1VF1C8AIKHA8PLC3GAM",
    "request_id":"{{request_id}}"
}
(/code)
```

A1: Make sure you are the admin or operator under this advertiser account. Please refer to [Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097) to learn more about different return codes used by Events API. 


**Q2: Where can I find the `pixel_code`?**

A2: You can find the `pixel_code` by logging into your TikTok Ads Manager account and navigating to **Tools** > **Events** > **Web Events**. In the following example, your `pixel_code` value is `CDRFH4RC77U93DJGJTRG`. If we have received your events sent from Events API, you will see "Server" under Connection Method within the "Overview" tab. Please refresh your page to see the latest status.


<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/3391508d8cad2b227dc37c750444c116" width="60%" style="color:#808080"/>

This means the events forwarded to us will show up under that pixel. Please be aware that if you are sending overlapping events from both Pixel SDK and Events API, you must set up [Event deduplication](https://ads.tiktok.com/marketing_api/docs?rid=p41a33fdhon&id=1739584864945154). 

If you are sending events from TikTok Pixel and Events API to the same Pixel ID for events deduplication, make sure to enable Automatic Advanced Matching.


If you don't want to use the existing pixel and want to create pixel from scratch, go to **Tools** > **Events** > **Web Events**. Click on **Set Up Web Events** and select **Events API**.




