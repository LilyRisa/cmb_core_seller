---
title: "Obtain Catalog Product Insights"
doc_id: 1805642743426050
path: "Marketing API / Catalog Management / Guides / Obtain Catalog Product Insights"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Obtain Catalog Product Insights

This article provides comprehensive instructions for accessing Catalog Product Insights, a valuable feature for identifying the popularity of products within your catalog and product categories on TikTok. By leveraging this feature, you can discover trending items and categories, which can inform your marketing strategies and inventory management.

For a detailed overview of how the Catalog Product Insights features in the Catalog Manager correspond to the API configurations, see [Mapping between Catalog Product Insights features in Catalog Manager and API configurations](https://business-api.tiktok.com/portal/docs?id=1805642902406145).

## Prerequisites
Before proceeding with the retrieval of Catalog Product Insights, ensure that an E-commerce catalog has been created and populated with a minimum of 20 products. For detailed instructions on catalog creation and product addition, see [Create catalogs and add products](https://business-api.tiktok.com/portal/docs?id=1754263112945665).

After the product upload is complete, allow a 24-hour period for the Catalog Product Insights data to become available.

## Steps
Catalog Product Insights are divided into two main categories:
1. Trending catalog products within your catalog
  - Use [/catalog/insight/product/get/](https://business-api.tiktok.com/portal/docs?id=1805640886872066) to retrieve a list of up to 50 products that are gaining traction based on user interactions on TikTok within your E-commerce catalog.
  - Optionally, if you require insights filtered by TikTok product category, brand name, or availability status, use [/catalog/insight/filter/get/](https://business-api.tiktok.com/portal/docs?id=1805640864553985) to retrieve the available filter values. These values can then be applied to `/catalog/insight/product/get/` to refine your results.
2. Trending catalog product categories on TikTok
  - Use [/catalog/insight/category/get/](https://business-api.tiktok.com/portal/docs?id=1805640900563969) to retrieve the top 50 trending product categories on TikTok, along with the percentage of available products within your catalog that fall into these categories.
