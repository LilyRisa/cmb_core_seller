---
title: "Overview"
doc_id: 1739562432184322
path: "Marketing API / Business Center / Overview"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Overview

Business Center is a central hub that allows organizations and agencies to efficiently manage multiple TikTok ad accounts, users, assets, and finances in a secure environment. Please note that the developer or advertiser must have a Business Center to be able to use Business Center API. Additionally, the user who authorizes the developer application should have Business Center Admin permission.


See [About TikTok Business Center](https://ads.tiktok.com/help/article/tiktok-business-center?lang=en) on TikTok Business Help Center to find out more details, including when you should use Business Center and how to get started with it. 

# Benefits of Business Center
TikTok Business Center empowers you to:
- **Possess all-in-one platform to check multiple ad accounts**: You can use a single sign in to access all managed ad accounts, reducing the time it takes to switch to different ad accounts.
- **Maintain full control of access permissions and manage assets**: Business Center guarantees security, as the core permissions are under control of the administrator. Clearly see who has access to your ad accounts, and assign, change, or remove roles and permissions as your team changes over time.
- **Ensure transparency of collaboration**: Business Center enables you to directly know who has edited or made changes to the ad accounts.
- **Upgrade decision-making for your future campaigns**: You can get business-level insights and reports for your ad performance across different accounts.

# Business Center types
Business Center types are as below. You can check your Business Center type via the `type` field returned in the [/bc/get/](https://ads.tiktok.com/marketing_api/docs?id=1737115687501826) endpoint. 
- Regular Business Center (`NORMAL`)
- Non-self-serve Direct Business Center (`DIRECT`)
- Non-self-serve Agency Business Center (`AGENCY`)
- Self-serve Direct Business Center (`SELF_SERVICE`)
- Self-serve Agency Business Center (`SELF_SERVICE_AGENCY`)
