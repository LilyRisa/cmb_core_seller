---
title: "Ads structure"
doc_id: 1739381193120770
path: "Marketing API / Campaign Management / Overview / Ads structure"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Ads structure

The ads structure is made up of three levels. 

* Campaign: The highest level of advertising. It is used to set advertising objectives. An ad account can have at most 999 campaigns (excluding [Promote campaigns](https://business-api.tiktok.com/portal/docs?id=1785880454546433)), and each campaign can include at most 999 ad groups. 
* Ad group: The middle level of advertising. It is used to plan the delivery of strategic ads. An ad group can include at most 20 or 50 ads.
  - If the [advertising objective](https://business-api.tiktok.com/portal/docs?id=1737585562434561) of the campaign that the ad group belongs to is `RF_REACH`, the ad group can include at most **20** ads.
  - If the advertising objective of the campaign that the ad group belongs to is `PRODUCT_SALES`, the ad group can include at most **20** ads (when `campaign_product_source` is `STORE`) or **50** ads (when `campaign_product_source` is `CATALOG`).
  - If the advertising objective of the campaign that the ad group belongs to is **not** `PRODUCT_SALES` or `RF_REACH`, the ad group can include at most **50** ads.
* Ad: The smallest advertising unit. It determines the information related to the ad that will be displayed.

![](https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/eae04baca3b01405183096c79107d5df)


# Campaign

Creating a campaign is the first step where you will need to set up your advertising objective and campaign budget. See [Create a campaign](https://ads.tiktok.com/marketing_api/docs?id=1737719523561474) for details.  

# Ad group

At the ad group level, you can configure the following settings for ads in the ad group. See [Create an ad group](https://ads.tiktok.com/marketing_api/docs?id=1738857874837506) for details. 

- Ad placement
- Target audience
- Budget settings
- Ad delivery schedule
- Optimization goal
- Bidding strategies

> <span style="color: DodgerBlue">**Note**</span><ul><li>The advertising objectives set at the campaign level will affect the optimization goals, and related bidding and optimization methods set at the ad group level. When adding ads to a campaign, the system checks the settings and ensures that ads under the same campaign all have the same advertising objectives.</li><li>Depending on the country or region where the ad account was registered or created, the available placements and locations where their ads can be deployed are different.</li><li>In order to ensure the integrity and accuracy of user data, we introduced an optimistic locking mechanism in the CREATE and UPDATE endpoints of Automatic Creative Optimization. The lock may be triggered when you create or update the same material multiple times simultaneously. For example, if you try to create multiple ads under the same ad group, or update the same field of an ad group or ad multiple times simultaneously, the lock will be applied. In this case, we recommend you make sequential requests, or create/update multiple ads with multiple materials in one request, and then retry properly.<ul><li>The optimistic lock will trigger the error message below: "Request failed due to optimistic locking to ensure data integrity. Please try not to use simultaneous requests or decrease your request frequency and try again."


# Ad

An ad is the content that is presented to your target audience. This may include video or image material, ad text, and call-to-action. You can upload new videos or pictures, or use existing content from the material library as the ad materials while managing an ad. See [Create an ad](https://ads.tiktok.com/marketing_api/docs?id=1745292553358338) for details. 

> <span style="color: DodgerBlue">**Note**</span><ul><li>Different placements support different creative asset specifications. See the [Ad Formats](https://ads.tiktok.com/help/article/image-ads-specification?redirected=1) chapter on TikTok Business Help Center for details.</li><li>Automated Creative Optimization (ACO) ad groups differs from custom ad groups. Updating ad materials for ACO-enabled ad groups is different from custom ad creatives. For details, see [Automated Creative Optimization ads](https://ads.tiktok.com/marketing_api/docs?id=1739470718398465).

