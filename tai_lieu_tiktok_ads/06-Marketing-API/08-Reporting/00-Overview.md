---
title: "Overview"
doc_id: 1751087777884161
path: "Marketing API / Reporting / Overview"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Overview

This article gives an overview of Reporting API.  

# Synchronous and asynchronous modes
You can run a report in synchronous mode or asynchronous mode. See [Synchronous and asynchronous reports](https://ads.tiktok.com/marketing_api/docs?id=1751088469160001) to learn about how to run a synchronous report or asynchronous report.  

# Ad type
You can run a report on your auction ads or both auction and reservation ads data.

# Report types
We provide six report types: basic report, audience report, playable ads report, Dynamic Showcase Ads(DSA) report, Business Center report, and GMV max ads report. See [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) to find out different use cases. 

# Data level
- If the report is a Business Center report, you don't need to manually set the service type and data level. Business Center reports support both auctions and reservation ads.
- If the report is a Basic report, Audience report, Playable ad report, or DSA report, you can run a report at the campaign level, ad group level, ad level, or ad account level. 

**Below is the relationship between ad type, report type, and report data level for Basic report, Audience report, Playable ad report, and DSA report:**

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2a0ccc82f46c0b69a813427bce3a3957" alt="relationship between ad type, report type, and report data level for Basic report, Audience report, Playable ad report, and DSA report" width="60%"  style="color:#808080" />

- If the report is a  GMV max ads report, you can run a report at the campaign level or ad account level.

# Dimensions, metrics, and filters
Different report types support different dimensions, metrics, and filters. See corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) to find out these dimensions, metrics, and filters.

# Data latency
You might expect latencies for different fields when using Reporting APIs, and please see [Data latency for reports](https://ads.tiktok.com/marketing_api/docs?id=1738864894606337) for details. 
# Reporting API vs Creative Reporting API 
Use Reporting API to run a report at the campaign level, ad group level, ad level, or ad account level, while use Creative Reporting API to run a report at the creative asset level. See [FAQs - Creative insights](https://ads.tiktok.com/marketing_api/docs?id=1738864850353154) for the detailed differences between Reporting API and Creative Reporting API.  
