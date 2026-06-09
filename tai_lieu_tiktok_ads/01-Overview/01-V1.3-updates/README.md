---
title: "V1.3 updates"
doc_id: 1740579480076290
path: "Overview / V1.3 updates"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# V1.3 updates

Based on developer feedback, we've introduced TikTok API for Business v1.3. This release is designed to improve your developer experience, introduce new API First features and ensure feature parity with TikTok Ads Manager. This document gives an overview of the major updates in v1.3. <b>To get a complete list of v1.3 product enhancements, see [What's New](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1740029165513730). </b>

**To help you quickly find the detailed changes to each endpoint, we've added a v1.2 vs v1.3 comparison table to each endpoint in the [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521
) chapter. We strongly encourage you to read these comparison tables before migration.**  

# Timeline

V1.3 was launched on August 15, 2022, and v1.2 will be deprecated on August 15, 2023.  After August 15, 2023, v1.2 will no longer be supported and all calls to v1.2 will fail permanently. 

# Major updates

## Version number changes in all URLs

You need to update the version number in all the endpoints from v1.2 to v1.3.

##  User experience improvements

We've made quite a few changes to improve the overall usability and developer experiences by providing more consistent API interfaces across our APIs. **These changes apply to all endpoints.**

###  IDs changed to string type

All the ID parameters that were long integer types in v1.2 are now changed to string type in v1.3. This is to address API compatibility issues for some client libraries such as Node.js.

### Parameter name changes

In v1.3, we are making sure all API parameters have consistent naming styles (in snake_case), and are clear and easy to understand.

For example, in the [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114) endpoint, `ios_osv` is renamed to `min_ios_version`, `deep_external_action` is renamed to `secondary_optimization_event`, and `store_authorized_bc` is renamed to `store_authorized_bc_id`. 

Another example is to improve consistency. For example, all filtering objects are now named as `filtering` consistently. And parameter names (e.g. `optimization_event`) are now consistent in different endpoints if they have the same meaning.

### True/False in boolean parameters

To improve consistency, all boolean type parameters now only use `True`/`False` rather than `1`/`0` or `Yes`/`No`.

###  Consistent response format

The response format is now consistent across all endpoints. Below is an example of the successful response. And failed response also has a similar format with clear error messages and error codes.

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code)
```

###  Consistent time format

The time type parameters, for example, `create_time`,  are now in the format of datetime (YYYY-MM-DD HH:MM:SS, ISO 8601) instead of timestamp. 

###  URL updates

Some of the API paths in v1.2 are a bit difficult to understand and the request types are counterintuitive for some endpoints. To improve these in v1.3, we've updated the URLs for the following endpoints. Also, the entities in the URL path are changed to singular to be more consistent with other APIs.

* **Business Account**

```xtable
|Old URL{50%} | New URL{50%}|
|---|---|
| /business/get/ (POST)                  | /business/get/ (GET)                |
| /business/videos/list/ (POST)          | /business/video/list/ (GET)       |
| /business/comments/list/  (POST)         | /business/comment/list/ (GET)      |
| /business/comments/replies/list/ (POST) | /business/comment/reply/list/ (GET) |
| /business/comments/create/                  | /business/comment/create/               |
| /business/comments/replies/create/          | /business/comment/reply/create/         |
| /business/comments/delete/                  | /business/comment/delete/               |
| /business/comments/hide/                    | /business/comment/hide/                 |
| /business/comments/like/                    | /business/comment/like/                 |
| /business/comments/pin/                     | /business/comment/pin/                  |
| /business/videos/publish/                   | /business/video/publish/                |
```

* **Business Center**

```xtable
|Old URL{50%} | New URL{50%}|
|---|---|
| /asset/ad/bind_info/              | /asset/bind/quota/              |
```

* **Campaign Management**

```xtable
|Old URL{50%} | New URL{50%}|
|---|---|
| /ad/tools/audience_size/       | /ad/audience_size/estimate/ |
| /adgroup/rf/inventory/estimate/ | /rf/inventory/estimate/      |
| /adgroup/rf/order/cancel/       | /rf/order/cancel/            |
| /campaign/update/status/        | /campaign/status/update/      |
|/adgroup/update/status/|/adgroup/status/update/|
|/ad/update/status/|/ad/status/update/|
|/adgroup/update/budget/|/adgroup/budget/update/|
|/pangle_flow_package/get/|/pangle_audience_package/get/|
```

*  **Catalog**

```xtable
| Old URL    {50%}                  | New URL       {50%}              |
| ---------------------------------- | -------------------------------- |
| /catalog/set/info/                 | /catalog/set/product/get/        |
| /catalog/video/get/                | /catalog/video_package/get/    |
| /catalog/eventsource_bind/create/ | /catalog/eventsource/bind/       |
| /catalog/available_countries/get/ | /catalog/available_country/get/ |
| /catalog/eventsource_bind/delete/ | /catalog/eventsource/unbind/     |
```

*   **Change log**

```xtable
|Old URL{50%} | New URL{50%}|
|---|---|
| /activities/task/check/    | /changelog/task/check/   |
| /activities/task/create/   | /changelog/task/create/  |
| /activities/task/download/ | /changelog/task/download/ |
```

* **Creative**

```xtable
|Old URL{50%} | New URL{50%}|
|---|---|
| /creative/image/tailor/ | /creative/image/edit/ |
| /creative/reports/get/  | /creative/report/get/ |
| /creative/recommend_asset/get/  | /creative/cta/recommend/ |
```

* **Events**

```xtable
|Old URL{50%} | New URL{50%}|
|---|---|
| /app/external_action/ | /app/optimization_event/|
| /app/external_action/retargeting/ | /app/optimization_event/retargeting/|
```

* **Leads**

```xtable
|Old URL{50%} | New URL{50%}|
|---|---|
| /pages/leads/mock/create/   | /page/lead/mock/create/   |
| /pages/leads/mock/delete/   | /page/lead/mock/delete/   |
| /pages/leads/mock/get/      | /page/lead/mock/get/      |
| /pages/leads/task/          | /page/lead/task/          |
| /pages/leads/task/download/ | /page/lead/task/download/ |
| /pages/fields/get/    | /page/field/get/    |
| /pages/library/get/         | /page/library/get/        |
| /pages/library/transfer/    | /page/library/transfer/   |
```

* **Page**

```xtable
|Old URL{50%} | New URL{50%}|
|---|---|
| /pages/get/    | /page/get/      |
```

* **Pangle**

```xtable
|Old URL{50%} | New URL{50%}|
|---|---|
| /pangle_flow_package/get/   | /pangle_audience_package/get/    |
```

* **Reporting**

```xtable
|Old URL{50%} | New URL{50%}|
|---|---|
| /reports/integrated/get/ (GET)   | /report/integrated/get/ (GET)            |
| /reports/integrated/get/ (POST)  | /report/task/create/ (POST) |
| /reports/task/check/             | /report/task/check/                 |
| /reports/task/download/          | /report/task/download/              |
| /reports/ad_benchmark/get/      | /report/ad_benchmark/get/          |
| /reports/video_performance/get/ | /report/video_performance/get/     |
```

*  **TikTok Creator Marketplace**

```xtable
|Old URL{50%} | New URL{50%}|
|---|---|
| /tcm/spark_ads/status/get/     | /tcm/spark_ad/status/get/          |
| /tcm/spark_ads/apply/          | /tcm/spark_ad/apply/               |
| /tcm/reports/get/               | /tcm/report/get/                    |
| /tcm/item/audit_status/get/    | /tcm/video/audit_status/get/       |
| /tcm/item/audit_status/update/ | /tcm/video/audit_status/update/    |
| /creator/get/                   | /tcm/creator/authorized/            |
| /creator/video/list/            | /tcm/creator/authorized/video/list/ |
| /tcm/creator/search/         | /tcm/creator/public/ |
| /tcm/creator/video/list/      | /tcm/creator/public/video/list/ |
```

* **Tool**

```xtable
|Old URL{50%} | New URL{50%}|
|---|---|
| /tools/target_recommend_tags/              | /tool/targeting_category/recommend/                       |
| /tools/suggest_bid/                         | /tool/bid/recommend/                          |
| /tools/hashtag/recommend/                    | /tool/hashtag/recommend/                      |
| /tools/hashtag/get/                          | /tool/hashtag/get/                            |
| /tools/diagnosis/get/                        | /tool/diagnosis/get/                          |
| /tools/suggestion/adopt/                     | /tool/diagnosis/adopt/                        |
| /tools/action_category/                     | /tool/action_category/                       |
| /tools/carrier/                              | /tool/carrier/                                |
| /tools/device_model/                        | /tool/device_model/                          |
| /tools/interest_category/                   | /tool/interest_category/                     |
| /tools/interest_keywords/                   | /tool/interest_keyword/                      |
| /tools/language/                             | /tool/language/                               |
| /tools/regions/                              | /tool/region/                                 |
| /tools/os_version/                          | /tool/os_version/                            |
| /tools/open_url/                            | /tool/open_url/                              |
| /tools/timezone/                             | /tool/timezone/                               |
| /tools/brand_safety/partner/authentication/ | /tool/brand_safety/partner/authorize/status/ |
```

## New features

In addition to the improvements above, we also list the major changes to the following API features. 

### Audience Management

*   We now enable you to upload a file with one or multiple IDs using [File API](https://ads.tiktok.com/marketing_api/docs?id=1739940567842818). 
*   We have launched [/v1.3/dmp/custom_audience/apply/](https://ads.tiktok.com/marketing_api/docs?id=1758615097524225) to help you apply or disconnect a Custom Audience from multiple ad groups and [/v1.3/dmp/custom_audience/apply/log/](https://ads.tiktok.com/marketing_api/docs?id=1758615737388033) to get the latest application log for Custom Audience.
*   We have increased the maximum file upload size from 50MB to **250MB** in [/v1.3/dmp/custom_audience/file/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739940567842818&rid=ghrv306q4rj).


### Business center

- We have launched a new endpoint [/bc/advertiser/disable/](https://ads.tiktok.com/marketing_api/docs?id=1752349244331009) to enable you to disable ad accounts in the Business Center.
- We have updated the endpoint [/bc/advertiser/create/](https://ads.tiktok.com/marketing_api/docs?id=1739939020318721) to support creating a reservation or auction ad account in the Agency or Direct Business Center.
- We have launched [BC Asset Groups](https://ads.tiktok.com/marketing_api/docs?id=1749001426121730) APIs for you to create, update, get, and delete Asset Groups. 

###  Campaign management

Before you migrate your calls to v1.3, please see [Migrate campaign management APIs](https://ads.tiktok.com/marketing_api/docs?id=1749537346290689) to learn about the highlighted differences between v1.2 and v1.3 campaign management APIs.

#### Automated Creative Optimization

* We've introduced new endpoints for automated creative optimization. These changes are intended to create greater parity between TikTok Ads Manager and TikTok API for Business. The new endpoints are [/ad/aco/create/](https://ads.tiktok.com/marketing_api/docs?id=1739473063234626), [/ad/aco/update/](https://ads.tiktok.com/marketing_api/docs?id=1739473077112833), [/ad/aco/get/](https://ads.tiktok.com/marketing_api/docs?id=1739473020978177) and [/ad/aco/material_status/update/](https://ads.tiktok.com/marketing_api/docs?id=1739506701165570), and meanwhile, the old endpoints (/ad/dynamic/create/, /ad/dynamic/get/, and /ad/dynamic/update/) are deprecated. 

* In these new endpoints, advertising objectives, Lead Generation and Engagement, are supported. You can specify the `call_to_action` and `call_to_action_id` fields to enable the Dynamic Call to Action feature, which helps improve your click through rates by displaying the most relevant Call to Action text to each person who sees your ad. In addition, the number limit of `media_info` is increased from 10 to 30. Spark Ads can also be created using these endpoints.

#### Campaign/Ad group/Ad

* We now return the full information of ad entity when you are creating or updating ads via [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602)，[/campaign/update/](https://ads.tiktok.com/marketing_api/docs?id=1739320422086657)，[/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114) ，[/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745) , [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) and  [/ad/update/](https://ads.tiktok.com/marketing_api/docs?id=1739953405142018).  This avoids additional calls to the GET endpoint to get the entire results. 

* The new advertising objectives of [App promotion](https://ads.tiktok.com/marketing_api/docs?id=1747553433902082) and [Website conversions](https://ads.tiktok.com/marketing_api/docs?id=1747553507237889) are now available to help streamline your campaign creation experience. 

* You can now create ad groups with optimization goal focusing on the number of views on the landing page. A new enum value `TRAFFIC_LANDING_PAGE_VIEW` has been added to the field optimization_goal in [Enumerations - Optimization Goal](https://ads.tiktok.com/marketing_api/docs?id=1701890985340929). The corresponding enum value `LANDING_PAGE_VIEW` has also been added to the field optimization_event in [Conversion events](https://ads.tiktok.com/marketing_api/docs?id=1701890988826626). 

* You can now use Full Inventory or Standard Inventory to configure your [Brand Safety](https://ads.tiktok.com/marketing_api/docs?id=1739381752981505) settings. You can also measure brand safety and viewability through more third-party solutions(for example,`DOUBLE_VERIFY` and` IAS`) after your ads have been delivered.


#### Campaign Budget Optimization

We have supported [Campaign Budget Optimization](https://ads.tiktok.com/marketing_api/docs?id=1739381757818881) (CBO) to help maximize campaign results. 

#### Estimated Delivery Result API

We've launched Estimated Delivery Result (EDR) , a feature that helps you improve ad performance by providing budget and bid recommendations. You can now use [/delivery/budget/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1746463695763458) to get 11 sets of recommended budgets and the corresponding estimated conversion data for each budget, or use [/delivery/bid/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1746463732779009) to get 7 sets of recommended bids and the corresponding estimated cost data for each bid. Before you use the EDR feature, please see [Get recommended budgets](https://ads.tiktok.com/marketing_api/docs?id=1746463333984258) and [Get recommended bids](https://ads.tiktok.com/marketing_api/docs?id=1746463347473410) to know about the preconditions and steps.

#### iOS 14 Dedicated Campaign Quota Optimization

You can now create up to 11 iOS 14 DCs for each ad network with an iOS app. See [iOS 14 Dedicated Campaign Quota Optimization](https://ads.tiktok.com/marketing_api/docs?id=1752256248260610) for details. 

#### Lead Generation

We have supported [creating a Lead Generation ad with a **Website Form**](https://ads.tiktok.com/marketing_api/docs?id=1739470742024193) in [/v1.3/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). 

#### Carousel Ads

We have supported [creating Carousel Ads](https://ads.tiktok.com/marketing_api/docs?id=1766217791987713). Carousel is a new ad format on TikTok that supports images for in-feed ads.


#### Shopping Ads

We've introduced [Shopping Ads](https://ads.tiktok.com/marketing_api/docs?id=1741942179356738), which is a catalog powered, smarter, and simplified paid advertising solution designed to drive maximum impact for client's business.

#### Split Testing

We have introduced new Split Testing APIs for v1.3 to reduce feature gap with TikTok Ads Manager, and provide more functionalities as well as flexibilities. Meanwhile, the old Split Testing APIs are deprecated for v1.2.  See  [split testing](https://ads.tiktok.com/marketing_api/docs?id=1742668394531842) to find some important notes and different use cases.  The related endpoint are [here](https://ads.tiktok.com/marketing_api/docs?id=1742666215544834).

#### Targeting

- We have added the parameter `zipcode_ids` to support zip code targeting via Ad Group endpoints ([/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114), [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745), [/adgroup/get/](https://ads.tiktok.com/marketing_api/docs?id=1739314558673922), [/ad/audience_size/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1740302379236353)), and it is currently only supported for the U.S.
- We have supported more location IDs for targeting locations in the U.K., Germany, France, the United States, the Philippines, Canada.
- We have added a new targeting option `spending_power` in [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114&rid=4tvcyoykuxf), [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745&rid=4tvcyoykuxf) and [/ad/audience_size/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1740302379236353&rid=4tvcyoykuxf) to target high spending users.
- We have launched the **purchase intent targeting** option via Ad Group endpoints ([/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114), [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745), [/adgroup/get/](https://ads.tiktok.com/marketing_api/docs?id=1739314558673922), [/ad/audience_size/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1740302379236353)) to target audience whose recent behavior suggests they may buy something related to a content category.

* We have added a new endpoint [/v1.3/tool/targeting/list/](https://ads.tiktok.com/marketing_api/docs?id=1762962378261506) to support getting ISP IDs for audience targeting.

#### Reach & Frequency

* We now return the full information of ad entity when you are creating or updating ad groups via [/adgroup/rf/create/](https://ads.tiktok.com/marketing_api/docs?id=1738235338194945) or [/adgroup/rf/update/](https://ads.tiktok.com/marketing_api/docs?id=1738235402874882). This avoids additional calls to the GET endpoint to get the entire results.

* We have reduced the feature gap of R&F between TikTok Ads Manager and TikTok API for Business with the v1.3 update. For example, we add the `rf_purchased_type`, `budget`, `purchased_impression` and `purchased_reach` fields in  [/rf/inventory/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1738235487941633) to give you more options to reserve and purchase ad inventory. 


*  `estimate_key` is removed from  [/adgroup/rf/create/](https://ads.tiktok.com/marketing_api/docs?id=1738235338194945), [/adgroup/rf/update/](https://ads.tiktok.com/marketing_api/docs?id=1738235402874882) and [/rf/inventory/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1738235487941633).

### Catalog management

- We have added a new field `update_mode` to the request parameter to [/v1.3/catalog/product/file/](https://ads.tiktok.com/marketing_api/docs?id=1740496787164161) to support choosing overwrite or incremental update mode when uploading products via a file URL.
- We have launched the new endpoint [/catalog/feed/switch/](https://ads.tiktok.com/marketing_api/docs?id=1750349624999937) to allow the update of feed schedule status.
- We have supported `SUPPLEMENT` update mode in [/catalog/feed/update/](https://ads.tiktok.com/marketing_api/docs?id=1740665197662210&rid=5dyeivzp8wu). 
- We have supported incremental updates and custom label fields in  [/catalog/product/update/](https://ads.tiktok.com/marketing_api/docs?id=1740562287852546).

### Creative management

#### Ads preview

* You can now preview shopping ads using preview endpoints. Shopping ads is a catalog powered advertising solution to drive maximum impact for client's business. We have added a couple of shopping ad related parameters(e.g. `shopping_ads_type`) in [/creative/ads_preview/create/](https://ads.tiktok.com/marketing_api/docs?id=1739403070695426) to help you preview shopping ads. 

* We have added new preview types of `PAGE`, `CARD`, and `ADS_CREATION` in [/creative/ads_preview/create/](https://ads.tiktok.com/marketing_api/docs?id=1739403070695426) to enable you to preview page, card and ads without creating one.  In [/creative/ads_preview/create/](https://ads.tiktok.com/marketing_api/docs?id=1739403070695426), `material_type` is renamed to preview_type and has new enum values of `PAGE`, `CARD` and `ADS_CREATION`. To preview ads without creating one, you need to specify `ADS_CREATION` in `preview_type` and specify ad related parameters, and then use the returned preview URL to see the ad. 

* We've improved the user interface when generating a preview, more closely aligning with how the ad will appear on TikTok. 

#### Creative portfolios

We have supported the portfolio types of `STICKER` (countdown sticker), `DOWNLOAD_CARD` (Download Card), `PRODUCT_CARD` (Product Card), `SHOWCASE` (TikTok Showcase) in [/creative/portfolio/create/](https://ads.tiktok.com/marketing_api/docs?rid=wtljluwz8r&id=1739091950439426) and [/creative/portfolio/get/](https://ads.tiktok.com/marketing_api/docs?id=1739092113671170).

#### Smart fix

We have launched [Smart Fix](https://ads.tiktok.com/marketing_api/docs?id=1741472523309058) to help you identify and fix video issues.

#### Video Insights

We now support getting [video insights](https://ads.tiktok.com/marketing_api/docs?id=1749023065231362) data into keyframe metrics about a video via the [/v1.3/report/video_performance/get/](https://ads.tiktok.com/marketing_api/docs?id=1738825259075586) endpoint. 

### Reporting

* We provide two endpoints ([/report/integrated/get/](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353) and [/report/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602)) to run sync and async reports separately. Previously, there was only one endpoint (/reports/integrated/get/) with Get/POST methods to differentiate sync and async reports. 

* The CHUNK mode in [/report/integrated/get/](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353) is now in GA. Previously, you had to apply for allowlisting to access it. With `CHUNK` mode specified in the query_mode field, data in your reports will be returned much faster in a more stable way. 

* We've added new metrics to [basic reports](https://ads.tiktok.com/marketing_api/docs?id=1738864915188737), [audience reports](https://ads.tiktok.com/marketing_api/docs?id=1738864928947201) and [DSA reports](https://ads.tiktok.com/marketing_api/docs?id=1738864960144385) to create greater parity between TikTok Ads Manager and TikTok API for Business. 

* We add a response header X-Tt-Ads-Throttle to [/report/integrated/get/](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353), and will return a warning in the response header when the number of returned ad ids exceeds 10,000.  

* We will return an error message when your query dimensions or metrics conflict with each other or a data timeout error occurs. 


## Deprecated Endpoints

The following endpoints have been deprecated and replaced. 

### Authentication

*   /oauth2/access_token_v2/ is deprecated and replaced by [/oauth2/access_token/](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).

*   /oauth2/refresh_token/ is deprecated since  long-term access tokens are provided.

*   /oauth2/creator_token/ is deprecated and replaced by [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?rid=7xusz43oh3h&id=1768493902119937#item-link-Get%20an%20access%20token)（new endpoint).


### Automated creative optimization

*   /ad/dynamic/create/ is deprecated and replaced by [/ad/aco/create/](https://ads.tiktok.com/marketing_api/docs?id=1739473063234626) (new endpoint). 

*   /ad/dynamic/get/ is deprecated and replaced by [/ad/aco/get/](https://ads.tiktok.com/marketing_api/docs?id=1739473020978177) (new endpoint). 

*   /ad/dynamic/update/ is deprecated and replaced by [/ad/aco/update/](https://ads.tiktok.com/marketing_api/docs?id=1739473077112833) (new endpoint).

###  Reporting

*  The /audience/ad/get/, /audience/adgroup/get/, and /audience/campaign/get/ endpoints are deprecated. If you want to query the audience data for campaign, ad group or ad, use [/report/integrated/get/](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353). 
*  The /reports/ad/get/, /reports/adgroup/get/, /reports/advertiser/get/ and /reports/campaign/get/ endpoints are deprecated. If you want to query the data for advertiser, campaign, ad group or ad, use [/report/integrated/get/](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353).


# Next steps 

[Migrate to v1.3](https://ads.tiktok.com/marketing_api/docs?id=1740591846408194)










