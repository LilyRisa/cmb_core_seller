---
title: "Asset Groups"
doc_id: 1754705410074626
path: "Marketing API / Business Center / Guides / Assets / Asset Groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Asset Groups

You (**Admin users of Agency Business Centers**) can use Business Center API  to manage Asset Groups. Below outlines the benefits and common use cases for Asset Groups.  

An Asset Group is a collection of assets (for example, advertiser accounts) in the Business Center. You can add advertiser accounts into a single Asset Group and assign Asset Group permissions to specific members in one go. For details, see [About Asset Group](https://ads.tiktok.com/help/article?aid=10001796&q=asset%20group) on TikTok Business Help Center. 
  
# Benefits
- **Organize assets in bulk**: You can create Asset Groups based on brands, regions, agencies, etc. After creation, you can quickly grant Asset Group access to members, which will grant them access to all assets under the Asset Group. For example, if you manage 10 advertiser accounts on behalf of a client, you can add these 10 advertiser accounts into a single Asset Group. Then, you can give members of your Business Center permission to access the Asset Group, which will grant them access to all 10 advertiser accounts in one go.
- **Assign permissions to members automatically**: When you add new assets to the Asset Group, members in this Asset Group will automatically get access to the assets based on the permissions you've assigned to them. In this case, you no longer have to manually assign permissions to each member. 
  


> <span style="color: DodgerBlue">**Note**</span><ul><li>Currently, Asset Group API only supports Advertiser account assets (`asset_type`= `ADVERTISER`).</li><li>Only the Admin (`user_role`=`ADMIN`) of the Business Center can create Asset Groups and add assets to them. Standard members can only view the Asset Groups that they are a part of. 

# Create and manage Asset Groups
Use [/bc/asset_group/create/](https://ads.tiktok.com/marketing_api/docs?id=1749001658583105) to create an Asset Group. You need to set asset permissions for members by specifying `asset_roles`. After creation, you can change assets, members or the name of an Asset Group via [/bc/asset_group/update/](https://ads.tiktok.com/marketing_api/docs?id=1749001662156801). To get all Asset Groups in your Business Center, use [/bc/asset_group/list/](https://ads.tiktok.com/marketing_api/docs?id=1749001665910786). To get the assets or members of an Asset Group, use [/bc/asset_group/get/](https://ads.tiktok.com/marketing_api/docs?id=1749001669670913). You can remove members' access to an Asset Group with [/bc/asset_group/delete/](https://ads.tiktok.com/marketing_api/docs?id=1749001673406466).
  
# Related endpoints
See [API reference](https://ads.tiktok.com/marketing_api/docs?id=1739562438061058) to find out the available endpoints for Business Center **Asset Groups**. 
  
