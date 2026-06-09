---
title: "BC Asset Groups"
doc_id: 1749001426121730
path: "API Reference / BC Asset Groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# BC Asset Groups

Business Center  (BC) is a one-stop business hub for organizations of all sizes to activate marketing activities and reach desired audiences in a collaborative way on TikTok. An Asset Group is a collection of assets (for example, advertiser accounts) in a Business Center. BC Asset Groups API enables you to manage Asset Groups in a Business Center. For instance, Admin users of Agency Business Centers can use BC Asset Groups API to add advertiser accounts into a single Asset Group and assign Asset Group permissions to specific members in one go. To find out more details of Asset Groups, see [Asset Groups](https://ads.tiktok.com/marketing_api/docs?id=1754705410074626).

> <span style="color: DodgerBlue">**Note**</span><ul><li>Currently, Asset Group API only supports advertiser account assets (`asset_type`= `ADVERTISER`).</li><li>Only Admin users (`user_role`=`ADMIN`) of the Business Center can create Asset Groups and add assets to them. Standard members (`user_role`=`STANDARD`) can only view the Asset Groups that they are a part of. 
  
This group consists of the following endpoints: 
- [Create an Asset Group](https://ads.tiktok.com/marketing_api/docs?id=1749001658583105)
- [Update an Asset Group](https://ads.tiktok.com/marketing_api/docs?id=1749001662156801)
- [Get all Asset Groups](https://ads.tiktok.com/marketing_api/docs?id=1749001665910786)
- [Get the details of an Asset Group](https://ads.tiktok.com/marketing_api/docs?id=1749001669670913)
- [Delete Asset Groups](https://ads.tiktok.com/marketing_api/docs?id=1749001673406466)
