---
title: "Account structure"
doc_id: 1740029179343874
path: "Get Started / Concepts / Account structure"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Account structure

A developer account can have up to 5 developer apps. These developer apps can be for different ad accounts. 


An advertiser account is the basic account on TikTok For Business platform. Before being able to create and manage ads with TikTok For Business, an advertiser must either own an advertiser account or be granted permission by other advertisers to manage their advertiser account and assets. The relationship between developer apps and advertiser accounts is many to many (see the image below). Once authorization is completed, the scope of the advertiser accounts that can be viewed or managed by a developer app depends on the relationship between the advertiser and the advertiser account.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/9ec6ad6f6e784951029012439ebd0cae" alt="Account structure" width="80%" style="color:#808080" />


<!-- <img src="https://sf16-scmcdn-sg.ibytedtos.com/obj/ad-platform-feedas-i18n/52ec9803a3650b74e96fdd51dd9e66bd.png" alt="image.png" width="80%" style="color:#808080" />. -->


> <span style="color: DodgerBlue">**Note**</span><br><ul><li>Agency Business Center accounts can create and modify ad accounts. For details, see [Create ad account](https://ads.tiktok.com/marketing_api/docs?id=1739939020318721) and [Update ad account](https://ads.tiktok.com/marketing_api/docs?id=1739939050770434). <li>For ad accounts created by agencies in Business Center, relationships between users and ad accounts can be managed via the following API endpoints:<ul><li>[Get assets](https://ads.tiktok.com/marketing_api/docs?id=1739432717798401)<li>[Get assets as admin](https://ads.tiktok.com/marketing_api/docs?id=1739433007779841)<li> [Assign an asset](https://ads.tiktok.com/marketing_api/docs?id=1739438211077121)<li> [Unassign an asset](https://ads.tiktok.com/marketing_api/docs?id=1739448126749698)

