# New fields added to Product API for Shop Ads

> Source: https://partner.tiktokshop.com/docv2/page/new-fields-added-to-product-api-for-shop-ads
> Section: Changelog
> Scraped: 2026-05-21T00:41:16.254Z

---

# Summary

More sellers are deploying ShopAds. To make sure your connector app is ads-ready, please ensure two fields are included when you are calling the Create Product and Edit Product API: external\_sku\_id (The SKU ID in the other platform) and external\_url (The product listing page URL). All TikTok 1P connector apps are making this update as well.

These two fields are under [Create Product API / Edit Product API -> Skus](create-product).

To construct the external\_url, you can combine store domain with product handle using the relevant APIs from the connector platform.
