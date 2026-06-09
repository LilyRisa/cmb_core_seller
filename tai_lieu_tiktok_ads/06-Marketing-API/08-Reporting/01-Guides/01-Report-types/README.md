---
title: "Report types"
doc_id: 1738864835805186
path: "Marketing API / Reporting / Guides / Report types"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Report types

Depending on the data type that you want to run a report on, there are six report types: basic reports, audience reports, playable ads reports, DSA reports, Business Center reports, and GMV max ads reports.

The table below presents an overview of these report types, along with their compatibility with auction ads and reservation ads.



```xtable
| Report type {20%} | Description  {40%}| Support auction ads {20%} | Support reservation ads {20%} |
|---|---|---|---|
| Basic report | Use this type of report to get spending and performance data at four levels: campaigns, ad groups, ads, or ad accounts. <br><br>For details, see [Basic reports](https://ads.tiktok.com/marketing_api/docs?id=1738864915188737).<br><br><p><span style="color:darkred"><b>Note</b></span>: For basic reports, you need to set `service_type` to `AUCTION` and `report_type` to `BASIC`. In contrast, for reservation ad reports, you need to set `service_type` to `RESERVATION` and `report_type` to `BASIC`.| ✅ |  ✅<br><br><p><span style="color:darkred"><b>Note</b></span>: You can retrieve data of TopView ads from basic reports. |
| Audience report | Use this type of report to get audience data. You can group spend and performance data by audience attributes such as age, gender, region, or interest. <br><br>For details, see [Audience reports](https://ads.tiktok.com/marketing_api/docs?id=1738864928947201).| ✅ |  ✅ <br><br><p><span style="color:darkred"><b>Note</b></span>: You can retrieve data of TopView ads from audience reports.|
| Playable ad report | Use this type of report to get data about playable ads.<br><br>For details, see [Playable ad reports](https://ads.tiktok.com/marketing_api/docs?id=1738864940608513). | ✅ | ❌|
| DSA report | Use this type of report to get data about Dynamic Showcase Ads (Dynamic Product Ads) data. <br><br> For details, see [DSA reports](https://ads.tiktok.com/marketing_api/docs?id=1738864960144385).| ✅ | ❌ |
| Business Center report | Use this type of report to get spending and performance data of ad accounts in a Business Center.<br><br>For details, see [Business Center reports](https://ads.tiktok.com/marketing_api/docs?id=1775747432712194). | ✅ | ✅ |
| GMV max ads report | Use this type of report to get spending data on [Product GMV max ads](https://ads.tiktok.com/help/article/how-to-create-product-gmv-max-ads-in-seller-center) and LIVE GMV max ads created from TikTok Shop at two levels: campaign level and ad account level.<br><br>For details, see [GMV max ads reports](https://ads.tiktok.com/marketing_api/docs?id=1803073629472770). | ✅ | ❌ |
```

