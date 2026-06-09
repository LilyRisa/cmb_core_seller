---
title: "Contextual targeting"
doc_id: 1745292519923713
path: "Marketing API / Campaign Management / Guides / Ad group / Targeting / Contextual targeting"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Contextual targeting

As a new targeting type, contextual targeting is used to present ads based on the subject and understanding of the media being consumed, rather than based on an understanding of the person viewing it. It enables advertisers to tap into TikTok's organic content with relevant messages in safe environments, and ultimately drive more business outcomes. 
><span style="color:DodgerBlue">**Note**</span><br><ul><li>Contextual targeting is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li><li>Contextual targeting cannot be used together with third-party Brand Safety solutions.</li></ul>

## How to use contextual targeting 
Contextual targeting is set at the ad group level, and can be used along with audience targeting. 
><span style="color:DodgerBlue">**Note**</span><br>Currently contextual tags can only be used together with audience targeting settings that target the following countries: <ul><li>General tags: `JP` (Japan), `KR` (South Korea), `NZ`(New Zealand),`PH`(the Philippines), `ID`(Indonesia), `TH`(Thailand), `MY`(Myanmar), `SG`(Singapore), `AE`(United Arab Emirates),`SA`(Saudi Arabia).</li><li>Premium tags (Pulse campaign): `US` (the United States), `CA` (Canada), `GB` (the United Kingdom), `DE` (Germany), `FR` (France), `IT` (Italy) , `ES` (Spain),  `AU` (Australia)，`BR` (Brazil), `MX`(Mexico).</li></ul>

1. Use [/tool/contextual_tag/get/](https://ads.tiktok.com/marketing_api/docs?id=1747747118654465) to get available contextual tags (`contextual_tag_id`).
2. Select IDs of the contextual tags you want to target and specify them in the `contextual_tag_ids` field in [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114) (for Auction ads) and [/adgroup/rf/create/](https://ads.tiktok.com/marketing_api/docs?id=1738235338194945) (for Reach&Frequency ads).  Then complete other settings at the ad group level.  
3.  To view contextual targeting information for auction ads in reports, create an [audience report](https://ads.tiktok.com/marketing_api/docs?id=1738864928947201) using [/report/integrated/get/](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353) or [/report/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602).  You can specify the dimension of  `contextual_tag` in your request, and then get the corresponding results.

## Difference between Contextual targeting and TikTok Pulse 
Contextual targeting is a targeting type that allows advertisers to present ads directly after a certain type of content videos from creators on the For You Page. TikTok Pulse uses contextual targeting capability and also curates the most popular content (using Pulse Score) and layers on the tightest brand safety tier.

Contextual targeting is available both in Auction and Reach & Frequency ads, while TikTok Pulse is only available in Reach & Frequency ads. 
