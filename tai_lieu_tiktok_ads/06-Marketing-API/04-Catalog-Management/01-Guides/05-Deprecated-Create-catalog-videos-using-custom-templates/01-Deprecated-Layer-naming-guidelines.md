---
title: "(Deprecated) Layer naming guidelines"
doc_id: 1739470990574594
path: "Marketing API / Catalog Management / Guides / (Deprecated) Create catalog videos using custom templates / (Deprecated) Layer naming guidelines"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Layer naming guidelines

This article describes how to name the layers in a custom template for videos of ads, for example, Shopping Ads.

To learn about the complete process to design a custom video template, refer to [Custom Template Design Guidelines](https://bytedance.feishu.cn/docs/doccnGxGlxEVZVhvGWyq17QTKNe).

Images and texts in a video template for ads, for example, Shopping Ads, will be replaced with real product information, such as prices or product images, when the ads are deployed. If a custom video template is used, designers from advertisers side need to identify which parts can be replaced in the custom video template. Designers need to name image and text layers in Adobe After Effects in formats that are supported by TikTok.

When your custom template is ready for use, you can upload it using the `/catalog/template/upload/` endpoint. For details about this endpoint, see [Upload a video template](https://ads.tiktok.com/marketing_api/docs?id=1740665346584577).

## Image layers

Image layer names are in the format of `product_image_x`, where x is a number that represents the order of the image. TikTok will replace `product_image_1` with the main image. Additional images will be used to replace the image layers in sequence (`product_image_2` and then `product_image_3`).

Please see below for the mapping between product keywords and Adobe AfterEffects layer names.

|Product keyword| Adobe After Effects layer name|
|---|---|
|Product image 1|product_image_1|
|Product image 2|product_image_2|
|product_image_3|product_image_3|
|product_image_4|product_image_4|

## Text layers

There are four main keywords in a catalog. We must name them differently so that TikTok can recognize them and replace them with actual product information.

Please see below for the mapping between product keywords and Adobe AfterEffects layer names.

|Product keyword| Adobe After Effects layer name|
|---|---|
|Price|price|
|Sale Price|sale_price|
|Product Name|product_name|
|description|description|
|brand|brand|
|custom_label_0|custom_label_0|
|custom_label_1|custom_label_1|
|custom_label_2|custom_label_2|
|custom_label_3|custom_label_3|
|custom_label_4|custom_label_4|

