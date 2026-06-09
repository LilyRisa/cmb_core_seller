---
title: "(Deprecated) Automated Creative Optimization"
doc_id: 1739470718398465
path: "Marketing API / Campaign Management / Guides / Ad / (Deprecated) Automated Creative Optimization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Automated Creative Optimization

Automated Creative Optimization (ACO) automatically combines and delivers high-quality creative materials.

> <span style="color:DodgerBlue">**Note**</span><br>
> ACO ads are no longer supported in API. To ensure a smooth API integration, we recommend you migrate to [Smart Creative ads](https://business-api.tiktok.com/portal/docs?id=1767322784934914).  

With ACO, you can upload images or videos, write ad texts, and select your call-to-action (CTA) buttons. Our system will then automatically combine your creative assets into multiple ads for your campaign. These ads get continuously explored, evaluated and optimized to find the optimal combination of variables. The system will then present the best creative to your target audience based on the tested combinations. See [here](https://ads.tiktok.com/help/article?aid=9529) for ACO's advantages and best practices.  

# Supported objectives
ACO is supported only when you have selected one of the following as the advertising objective (`objective_type`) for your campaign.
- App promotion (`APP_PROMOTION`)
- Website conversions (`WEB_CONVERSIONS`)
- Traffic (`TRAFFIC`)
- Lead generation (`LEAD_GENERATION`)

# Ad name generation rules
- If a valid string is passed in to the `name` field, and `file_name` in `image_info` or `video_info` is also passed in, then ad names will be generated in the format of "adName_" followed by sequence numbers, such as "adName_001".
- If a blank string is passed in to the `name` field, and `file_name` in `image_info` or `video_info` is also passed in, then ad names will be generated in the format of "file_name" followed by sequence numbers, such as "image001" or "video_002".
- If a blank string is passed in to the `name` field, but the `file_name` in `image_info` or `video_info` is not passed in, then ad names will be generated in the format of "_"  followed by sequence numbers, such as "001". Try to avoid this case so that ad names can be more meaningful.
