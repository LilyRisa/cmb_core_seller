---
title: "Create catalogs and add products"
doc_id: 1754263112945665
path: "Marketing API / Catalog Management / Guides / Create catalogs and add products"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create catalogs and add products

This article discusses how to create a catalog and add products to the catalog via Catalog APIs. 
# Create a catalog
Use [/catalog/create/](https://ads.tiktok.com/marketing_api/docs?id=1740306481704961) to create a catalog.
# Add products to a catalog
After you create your Catalog, you need to add your products to it. You can add products using product IDs, a file or data feed. 
- **Add products via a JSON schema**: Use [/catalog/product/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740497429681153) to upload your products. This endpoint is mainly used for uploading a small number of products and updating some fields. The system will enter the products in the request asynchronously, and in the response, you will get a task processing ID, `feed_log_id`. With this ID you can view the processing status via the [/catalog/product/log/](https://ads.tiktok.com/marketing_api/docs?id=1740570027173889) endpoint. <br>To learn about the steps to upload products to different catalog types using JSON schema, see [here](https://ads.tiktok.com/marketing_api/docs?id=1766862002851842).

> <span style="color: DodgerBlue">**Note**</span><br>Product images must be 500x500 px or larger in size. Otherwise, the product will not be approved. For details, see [Product image requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770).

- **Add products via a file**: Use [/catalog/product/file/](https://ads.tiktok.com/marketing_api/docs?id=1740496787164161) to upload your products. This endpoint is mainly used for the initial or full upload of products to a catalog. Currently, the API only allows CSV file type Catalog Feeds. The system will enter the products in the request asynchronously, and in the response, you will get a task processing ID, `feed_log_id`. With this ID you can view the processing status via the [/catalog/product/log/](https://ads.tiktok.com/marketing_api/docs?id=1740570027173889) endpoint. 

> <span style="color: DodgerBlue">**Note**</span><br>Product images must be 500x500 px or larger in size. Otherwise, the product will not be approved. For details, see [Product image requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770).

- **Add products via a data feed**: Use [/catalog/feed/create/](https://ads.tiktok.com/marketing_api/docs?id=1740665161957377&rid=6pdx02ym97f) to create a data feed. Using a data feed lets you connect your catalog to a feed, which the system uses to automatically get your product information and regularly update your catalog. You can select your update method to be either `OVERWRITE` or `INCREMENTAL`. 

# Update products in a catalog
Use [/catalog/product/update/](https://ads.tiktok.com/marketing_api/docs?id=1740562287852546&rid=6pdx02ym97f) to update products to a catalog. Incremental update is supported. The system will enter the products in the request asynchronously, and in the response, you will get a task processing ID, `feed_log_id`. With this ID you can view the processing status via the [/catalog/product/log/](https://ads.tiktok.com/marketing_api/docs?id=1740570027173889) endpoint. 

# Create a product set
Use [/catalog/set/create/](https://ads.tiktok.com/marketing_api/docs?id=1740572891104257) to create a product set. A product set is a set of specific products within your catalog. Creating a product set enables you to build groups that can promote specific products instead of your entire catalog.


# Bind an event source to a catalog
Binding your catalog with an app event source allows you to retarget people who have previously engaged with your products on your app and deliver ads featuring your most popular products to the most relevant audience. After app or website events are bound with your catalog, ads will be dynamically shown to people based on how they interacted with your app or website.<br><br>Use [/catalog/eventsource/bind/](https://ads.tiktok.com/marketing_api/docs?id=1740492491200513) to bind an event source to your catalog. You need to specify `app_id` for app events or `pixel_code` for website events.
