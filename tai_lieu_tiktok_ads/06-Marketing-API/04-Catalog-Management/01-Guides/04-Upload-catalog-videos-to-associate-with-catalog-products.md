---
title: "Upload catalog videos to associate with catalog products"
doc_id: 1803654904260674
path: "Marketing API / Catalog Management / Guides / Upload catalog videos to associate with catalog products"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload catalog videos to associate with catalog products

Uploaded catalog videos are a feature that allows you to associate a video with multiple products in your E-commerce catalog. Once the associations between videos and catalog products are established, you can use the uploaded videos in Video Shopping Ads (VSA) for Catalog. The videos featuring the associated products will automatically display in your ads. This article discusses how to upload and manage catalog videos in your catalogs.

## Key benefits
- Associate videos with specific catalog products, allowing the videos to dynamically showcase the associated products in Video Shopping Ads.
- Easily manage your catalog video library using API endpoints.

## Steps

### 1. Upload catalog videos
Use [/catalog/video/file/](https://business-api.tiktok.com/portal/docs?id=1803655037415489) to upload catalog videos via a CSV feed template. With this endpoint, you can associate each video with up to 20 products from your E-commerce catalog.

### 2. Check upload results
Verify the catalog video handling results using [/catalog/video/log/](https://business-api.tiktok.com/portal/docs?id=1803655061642242). Pass in the `feed_log_id` obtained from Step 1. If the field `error_affected_products` in the response is not null, examine the issue details and return to Step 1 to re-upload the video.

### 3. Create Video Shopping Ads
If you want to use the uploaded catalog videos to create VSA for Catalog with catalog video format, follow the instructions in Create Video Shopping Ads with products from catalogs. Note that you need to set `promotion_type` to `WEBSITE` at the ad group level and `vertical_video_strategy` to `CATALOG_UPLOADED_VIDEOS` at the ad level. 

### 4. (Optional) Manage uploaded videos
If you want to manage your uploaded catalog videos, you can use the following endpoints:
- To get information about uploaded catalog videos within an E-commerce catalog, use [/catalog/video/get/](https://business-api.tiktok.com/portal/docs?id=1803655082498050).
- To delete an uploaded catalog video, use [/catalog/video/delete/](https://business-api.tiktok.com/portal/docs?id=1803655103069185).
