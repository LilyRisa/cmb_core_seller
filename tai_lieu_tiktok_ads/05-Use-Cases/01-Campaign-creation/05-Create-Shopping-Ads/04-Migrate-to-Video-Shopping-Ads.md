---
title: "Migrate to Video Shopping Ads"
doc_id: 1754726939130882
path: "Use Cases / Campaign creation / Create Shopping Ads / Migrate to Video Shopping Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Migrate to Video Shopping Ads

**If you have created Collection Ads or Dynamic Showcase Ads before Feb.16, 2023, please read this doc to find out the changes and actions you should take.** 

# Background

Starting from Feb.16, 2023, you can no longer create Collection Ads or Dynamic Showcase Ads. The modification of existing Collection Ads or Dynamic Showcase Ads through API will be supported till the end of March, and may be extended on a case-by-case basis. If you need extra transition time, please contact your TikTok representatives. To ensure a smooth API integration, we recommend you migrate to [Video Shopping Ads](https://ads.tiktok.com/marketing_api/docs?id=1741942291730434&rid=tv5sdeafqp) under Product Sales objective as early as possible.  

# What's changing 
* Existing Commerce catalog-based solutions, Dynamic Showcase Ads and Collection Ads, will be sunset in phases, starting in February 2023. 
* Core functionalities and benefits of Collection Ads or Dynamic Showcase Ads have been incorporated into our newest and improved solution, Video Shopping Ads under Product Sales objective. Additionally,  new optimization features and formats are introduced to help you succeed in your marketing campaigns.
* At the end of this process, Video Shopping Ads will be the dedicated ad solution for Commerce advertisers on TikTok Ads Manager.

# What actions you should take

- Check Video Shopping Ads eligibility - All existing Collection Ads or Dynamic Showcase Ads advertisers should have access to Product Sales objective from mid-Jan, 2023. 
- Check API version - Video Shopping Ads is only available in our latest API version 1.3. Please make sure to [migrate to v1.3](https://ads.tiktok.com/marketing_api/docs?id=1740591846408194&rid=q46sy9ikhir) before accessing Video Shopping Ads API. 
- Migrate to Video Shopping Ads - Start creating new campaigns under the Product Sales objective. See [Video Shopping Ads](https://ads.tiktok.com/marketing_api/docs?id=1741942291730434&rid=tv5sdeafqp) to find out the detailed creation steps via API. 

#  FAQs
**What happens to my existing campaigns?**
- Campaigns you've created with the previous objectives (Collection Ads or Dynamic Showcase Ads) will remain active and there is no need to make any changes to your existing campaigns. Advertisers will also be able to edit  your existing campaigns in TikTok Ads Manager till the end of March 2023. 

**My advertiser does not want to leverage Dynamic Showcase Ads anymore. Do we still have to do the migration?**
- If your client does not wish to use Dynamic Showcase Ads anymore, they do not need to migrate their campaigns and can leave it to sunset. However, should they change their mind after 'Catalog Sales' sunsets, we recommend that they test the new Video Shopping Ads flow to create a new campaign. 

**Will the platform automatically migrate existing Collection Ads or Dynamic Showcase Ads campaigns for my advertisers?**
- New Video Shopping Ads will not be automatically created for existing clients as Video Shopping Ads have a different setup flow and new features compared to Collection Ads or Dynamic Showcase Ads. See [Video Shopping Ads](https://ads.tiktok.com/marketing_api/docs?id=1741942291730434&rid=tv5sdeafqp) to find out the detailed creation steps via API. 

**What is the migration timeline for TikTok Shop clients and the sunset of Shop Purchase objective?**
- Migration for Shop Purchase objective will start later than Non-shop, in March 2023. 

**My advertiser does not belong to Commerce vertical but is currently active on Dynamic Showcase Ads or Collection Ads. What is the recommendation?**
- All existing Collection Ads / Dynamic Showcase Ads advertisers are encouraged to migrate over to Video Shopping Ads as this will be the dedicated catalog based solution. 

**My advertiser is using Collection Ads on Reach and Video View objectives. Can they do the same on Video Shopping Ads?**
- Video Shopping Ads are best suited for optimization for pixel events (e.g. Purchase, Add to Cart, View Content) or traffic. Advertisers are recommended to leverage other branding objectives if they wish to achieve reach or views for their marketing campaigns. 
