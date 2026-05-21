# Publishing global product

> Source: https://open.shopee.com/developer-guide/215
> Category: 
> Scraped: 2026-05-20T20:37:45.536Z

---

\*This article applies to cross-border sellers who have upgraded CNSC/KRSC

After creating a global product, you can publish the global product to each market, but the global product can only have one shop product in each market.

  

Below we will explain the API call flow.

# Step 1: Getting the list of publishable shops

API: [v2.global\_product.get\_publishable\_shop](https://open.shopee.com/documents/v2/v2.global_product.get_publishable_shop?module=90&type=1)

You can get the list of publishable shops for global products through this API, but we will not return the corresponding shops for the following cases:

-   shops that have not done the shop authorization
-   SIP affiliated shops
-   Shops of published market

# Step 2: Getting the shop channel

API: [v2.logistics.get\_channel\_list](https://open.shopee.com/documents/v2/v2.logistics.get_channel_list?module=95&type=1)

You need to call [v2.logistics.get\_channel\_list](https://open.shopee.com/documents/v2/v2.logistics.get_channel_list?module=95&type=1) API and select the channel with enable=true and mask\_channel\_id=0 for shop products. If you publish global products without uploading shop channels, we will choose the enabled and available shop channels for you by default.

# Step 3: Publishing global product

API: [v2.global\_product.create\_publish\_task](https://open.shopee.com/documents/v2/v2.global_product.create_publish_task?module=90&type=1)

For the optional fields of [v2.global\_product.create\_publish\_task](https://open.shopee.com/documents/v2/v2.global_product.create_publish_task?module=90&type=1) , if you do not upload, Shopee will do some processing and upload to shop products. If you upload, it will be a custom value and Shopee will not do any processing. The specific logic is as follows:

<table><tbody><tr><td colspan="" rowspan=""><span><span>Field Name</span></span></td><td colspan="" rowspan=""><span><span>Upload the custom value</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>item_name</span></span></td><td colspan="" rowspan=""><span><span>YES</span><span>: Seller need to translate into local language then upload </span></span><span><span>NO</span><span>: Shopee will help translate into the local language</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>description_info/description</span></span></td><td colspan="" rowspan=""><span><span>YES</span><span>: Seller need to translate into local language then upload</span></span><span><span>NO</span><span>: Shopee will help translate into the local language</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>original_price</span></span></td><td colspan="" rowspan=""><span><span>YES</span><span>: Seller need to upload the price in local currency</span></span><span><span>NO</span><span>: Shopee calculates local prices based on the price of global products and calculation formulas.</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>tier_variation--name</span></span></td><td colspan="" rowspan=""><span><span>YES</span><span>: Seller need to translate into local language then upload</span></span><span><span>NO</span><span>: Shopee will help translate into the local language</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>tier_variation--option_list--option</span></span></td><td colspan="" rowspan=""><span><span>YES</span><span>: Seller need to translate into local language then upload</span></span><span><span>NO</span><span>: Shopee will help translate into the local language</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>image</span></span></td><td colspan="" rowspan=""><span><span>YES</span><span>: Seller uploads the custom images</span></span><span><span>NO</span><span>: Shopee will copy the images of global products</span></span></td></tr></tbody></table>

  

After a successful API call, you will get a publish\_task\_id.

API: [v2.global\_product.get\_publish\_task\_result](https://open.shopee.com/documents/v2/v2.global_product.get_publish_task_result?module=90&type=1)

This API will return whether the publish task was successful or not. If it succeeds, it will return the item\_id, shop\_id, and region information, if it fails, it will return the specific reason for the failure.

  

Please note: If the published shop is the SIP primary shop, then after the successful publication, Shopee will automatically publish the global product to the affiliated shops under the SIP primary shop.

# Step 4: Getting the list of published shop products

API: [v2.global\_product.get\_published\_list](https://open.shopee.com/documents/v2/v2.global_product.get_published_list?module=90&type=1)

API will return the item\_id and shop\_id of all the shops that have been successfully published for this global product, including the shop products that Shopee automatically publishes to the affiliated shops.

  

Please note: This API does not return shop products that have been published but have not done the shop authorization.
