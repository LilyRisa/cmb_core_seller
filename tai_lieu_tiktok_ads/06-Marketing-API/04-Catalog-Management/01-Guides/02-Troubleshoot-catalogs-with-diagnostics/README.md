---
title: "Troubleshoot catalogs with diagnostics"
doc_id: 1780803490136066
path: "Marketing API / Catalog Management / Guides / Troubleshoot catalogs with diagnostics"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Troubleshoot catalogs with diagnostics

This article provides step-by-step instructions on troubleshooting catalog products and event sources, including the Catalog Match Rate by using Catalog Diagnostics. The Catalog Diagnostics feature helps you identify and resolve issues with your catalog products, and event sources that are bound to your catalog. 

To find out the mapping between the features for diagnosing catalog products and catalog event sources in Catalog Manager and the corresponding API configurations, see [Mapping between Catalog Diagnostics features in Catalog Manager and API configurations](https://business-api.tiktok.com/portal/docs?id=1780803525320706).

# Troubleshoot catalog products

## Prerequisites
Ensure that you have added products to your catalog. To learn more about the steps, see [Create catalogs and add products](https://business-api.tiktok.com/portal/docs?id=1754263112945665).

## Steps
1. Obtain diagnostic information for catalog products by using one of the following modes:
  - **Synchronous mode**: Use [/diagnostic/catalog/](https://business-api.tiktok.com/portal/docs?id=1771117232728066) to retrieve detailed catalog diagnostic information synchronously.
  -**Asynchronous mode**: 
    - Use [/diagnostic/catalog/product/task/create/](https://business-api.tiktok.com/portal/docs?id=1771117279175682) to create an asynchronous download task for less detailed catalog product diagnostic information. 
    - Then use [/diagnostic/catalog/product/task/get/](https://business-api.tiktok.com/portal/docs?id=1771117294731266) to download a CSV file of the diagnostic information.
2. If any issues with catalog products are identified,  use [/catalog/product/update/](https://business-api.tiktok.com/portal/docs?id=1740562287852546) to update the products and resolve the issues.

# Troubleshoot catalog event sources and Catalog Match Rate 

The **Catalog Match Rate** represents the percentage of signals events shared that can be matched to the SKU ID in your catalog using the Content ID attribute. Maintaining a Catalog Match Rate above 90% is recommended for optimal ad performance with Video Shopping Ads. When an exact match between Content ID and SKU ID cannot be made, the system considers other Catalog attributes to inform ad content.

## Prerequisites
Ensure that you have bound an event source to a catalog in a Business Center by using [/catalog/eventsource/bind/](https://business-api.tiktok.com/portal/docs?id=1740492491200513) or on TikTok Ads Manager. To learn more about how to bind an event source to your catalog on TikTok Ads Manager, see [How to Create and Manage Catalogs - Connect Event Sources](https://ads.tiktok.com/help/article/create-manage-catalogs?lang=en#anchor-18).

To confirm whether an event source has been bound to a catalog, use [/catalog/eventsource_bind/get/](https://business-api.tiktok.com/portal/docs?id=1740492531343362).


## Steps
1. Obtain diagnostic information for event sources (Apps or Pixels) that are bound to a catalog by using [/diagnostic/catalog/eventsource/issue/](https://business-api.tiktok.com/portal/docs?id=1780799405041665).
  - If any issues with the event sources are identified, they will be returned through the `diagnostic_result` field. You can follow the suggestions provided in the `diagnostic_solution` field and refer to the guide [How to troubleshoot catalog match rate issues](https://ads.tiktok.com/help/article/about-catalog-event-match-rates?lang=zh#anchor-4) to resolve these issues.
2. Retrieve the Catalog Match Rate for your catalog by using [/diagnostic/catalog/eventsource/metric/](https://business-api.tiktok.com/portal/docs?id=1780802101608450).
  - Catalog Match Rate will be returned through the `percentage` field when `available_type` is `EVENT_WITH_CONTENT_ID_MATCHING_INVENTORY`. Note that if an event source is connected to your catalog for the first time, the Match Rate will take several days to stabilize.
  - Additionally, you can identify the trend of app events or pixel events received from event sources.
3. If no issues are identified for the event sources, and the Catalog Match Rate for your catalog is over 90%, you can use the catalog to create [Video Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1750361698613249) and retarget audiences who have interacted with the products.
