---
title: "(Deprecated) Create catalog videos using custom templates"
doc_id: 1754263135747073
path: "Marketing API / Catalog Management / Guides / (Deprecated) Create catalog videos using custom templates"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Create catalog videos using custom templates

Catalog video can be a template video that features products listed in advertisers' catalog. This article discusses how to create and manage catalog videos using custom templates. 
1. Use [/catalog/template/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740665346584577) to upload a custom video template and associate it with catalogs.  

See [Custom Template Design Guidelines](https://bytedance.feishu.cn/docs/doccnGxGlxEVZVhvGWyq17QTKNe) to learn about how to design a custom video template. In addition, in a custom video template, the image layers and text layers need to be named in formats that are supported by TikTok. For details, see [Layer Naming Guidelines](https://ads.tiktok.com/marketing_api/docs?id=1739470990574594).

2. Use [/catalog/video_package/create/](https://ads.tiktok.com/marketing_api/docs?id=1740665273039873) to create a green screen type of video package.  

A video package is a group of videos that you can directly use in ads, for example, Shopping Ads. See [Create a video package](https://ads.tiktok.com/marketing_api/docs?id=1739470992872450) to learn about the materials needed and specific steps. ` shopping_ads_video_template_id` will be returned in the response. 

3. If you want to use catalog videos to create ads, then use [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) and pass in the value of `shopping_ads_video_template_id` that you get from Step 2 to the `shopping_ads_video_package_id` field.  

4. If you want to manage your catalog videos, you can use the following endpoints. 
  -  To get information about all catalog videos under your Business Center, use [/catalog/video_package/get/](https://ads.tiktok.com/marketing_api/docs?id=1740574099715073&rid=svyeg5clx). 
  -  To update the name of a video package, use [/catalog/video_package/update/](https://ads.tiktok.com/marketing_api/docs?id=1740665303669761). 
  -  To delete a video package, use [/catalog/video_package/delete/](https://ads.tiktok.com/marketing_api/docs?id=1740665332455426). 
