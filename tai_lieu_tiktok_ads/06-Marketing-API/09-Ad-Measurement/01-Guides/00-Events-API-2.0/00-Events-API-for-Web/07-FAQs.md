---
title: "FAQs"
doc_id: 1771101001228290
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for Web / FAQs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# FAQs

## Q1: I see a 40001 response code like the following instead of 0. What can I do?

```xcodeblock
(code)
{
    "code":40001,
    "message":"No permission to operate pixel code: D1VF1C8AIKHA8PLC3GAM",
    "request_id":"{{request_id}}"
}
(/code)
```

A1: Make sure you are the admin or operator under this advertiser account. To learn more about different return codes, refer to [Responses and errors - API response codes](https://ads.tiktok.com/marketing_api/docs?id=1771101162079234#item-link-API%20response%20codes).

## Q2: Where can I find the Pixel Code?

A2: 
- (1) If you already have a Pixel, you can find the Pixel Code by logging into your [TikTok Ads Manager](https://ads.tiktok.com/i18n/home) account and navigating to **Tools** > **Events** > **Data sources**. In the following example, your Pixel Code value is `CUSG5HBC77UD11VVRQEG`. 

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ba50dbaf424f25ecefdb38d4d5bb5573" alt="Pixel Code value" width="60%" style="color:#808080"/>

If we have received your events sent from Events API, you will see "Server" under Connection Method within the "Overview" tab. Please refresh your page to see the latest status. This means the events forwarded to us will show up under that pixel. Please be aware that if you are sending overlapping events from both Pixel SDK and Events API, you must set up event deduplication. If you are sending events from TikTok Pixel and Events API to the same Pixel Code for [events deduplication](https://ads.tiktok.com/marketing_api/docs?id=1771100965992450), make sure to enable Automatic Advanced Matching.

- (2) If you don't have a Pixel, or don't want to use the existing Pixel and want to create a Pixel from scratch, go to **Tools** > **Events** > **Connect data source**  > **Web**, and follow the steps to create a pixel.
