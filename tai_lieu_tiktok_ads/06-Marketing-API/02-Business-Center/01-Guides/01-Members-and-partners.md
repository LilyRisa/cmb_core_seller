---
title: "Members and partners"
doc_id: 1754705315233793
path: "Marketing API / Business Center / Guides / Members and partners"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Members and partners

You (**Admin users of a Business Center**) can use Business Center API to add people to your Business Center and assign them roles to manage your assets. 
# About roles 
Basic roles (`user_role`) within Business Center include Admin and Standard. Admins can manage the Business Center's members and assets, while Standard members can only work on the ad accounts they've been assigned to. 

Finance roles (`finance_role`) within Business Center include Finance Analyst and Finance Manager. Finance Analyst can view, export, and download payment transaction records for all ad accounts owned or created by the Business Center. Finance Manager has all the permissions as the Finance Analyst, and has the ability to a series of editing permissions, which they can use to make payments, top up ad accounts, and modify contact information for billing and invoicing, for all of the ad accounts owned or created by the Business Center. 

See [Business Center Roles](https://ads.tiktok.com/help/article/add-users-tiktok-business-center?lang=en#anchor-0:~:text=manage%20specific%20assets.-,Business%20Center%20Roles,-Roles%20specific%20to) on TikTok Ads Manager Help Center for more detailed introductions. 
# Add members to your Business Center
After creating the ad accounts for your clients, you can invite your team members to your Business Center and assign them permissions. 
 
Use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) to invite members and assign permissions via the `user_role` or `finance_role` field. 
# Add partners to your Business Center 
A partner is generally another business you work closely with managing and building TikTok ads campaigns. See [About TikTok Business Center Partners](https://ads.tiktok.com/help/article/tiktok-business-center-partners?lang=en) on TikTok Ads Manager Help Center to learn more about partners. 

Use [/bc/partner/add/](https://ads.tiktok.com/marketing_api/docs?id=1739662756510721) to add a partner to your Business Center. To assign your partner permissions, use `advertiser_role` or `catalog_role` fields. 
# Related endpoints
See [API reference](https://ads.tiktok.com/marketing_api/docs?id=1739562438061058) to find out the available endpoints for Business Center **members** and **partners**.  


