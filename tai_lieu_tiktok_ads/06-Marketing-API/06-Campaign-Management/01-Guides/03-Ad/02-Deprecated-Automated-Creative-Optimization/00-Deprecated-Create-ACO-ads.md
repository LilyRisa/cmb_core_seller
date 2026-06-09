---
title: "(Deprecated) Create ACO ads"
doc_id: 1745292500427777
path: "Marketing API / Campaign Management / Guides / Ad / (Deprecated) Automated Creative Optimization / (Deprecated) Create ACO ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Create ACO ads

> <span style="color:DodgerBlue">**Note**</span><br>
> ACO ads are no longer supported in API. To ensure a smooth API integration, we recommend you migrate to [Smart Creative ads](https://business-api.tiktok.com/portal/docs?id=1767322784934914).  


This article introduces how to create Automated Creative Optimization (ACO) ads. 


1. Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602).  
  - Only support `APP_PROMOTION`,`WEB_CONVERSIONS`, `TRAFFIC`,  and `LEAD_GENERATION` as advertising objective (`objective_type`).
  
2. Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). 
  - `creative_material_mode` be set to `DYNAMIC` to enable ACO.  
> <span style="color:DodgerBlue">**Note**</span><br>ACO ads(`creative_material_mode` =  `DYNAMIC`) will be sunset in future versions. We recommend that you create [Smart Creative ads](https://ads.tiktok.com/marketing_api/docs?id=1767322784934914) to ensure a smoother migration journey.

3. Upload creatives using the following endpoints: 
  - Upload videos using [/file/video/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449), and get `video_id` in response. 
  - Upload images using [/file/image/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642), and get `image_id` in response. 
  - Get standard or dynamic call-to-action texts using [/creative/cta/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1739362202742785). See [CTA recommendations](https://ads.tiktok.com/marketing_api/docs?id=1740307296329730) for details.  
4. Create an ACO ad using [/ad/aco/create/](https://ads.tiktok.com/marketing_api/docs?id=1739473063234626).  
  - Enter image ID and video ID that you get from Step 3 in the `web_uri` and `video_id` fields(in `media_info`) respectively. 
  
> <span style="color: DodgerBlue">**Note**</span><br>You can enter no more than 30 materials in the `media_info` object.  The materials should not be duplicated. 

  - Enter call-to-action texts you get from Step 3 in the `call_to_action_list` or  `call_to_action_id` fields. 

> <span style="color: DodgerBlue">**Note**</span><br>You can enter no more than three call-to-action texts. These texts should not be duplicated. 
  - Enter ad texts in `title_list`.

> <span style="color: DodgerBlue">**Note**</span><br>You can enter no more than five texts. These texts should not be duplicated. 
  - Enter the instant page ID in the `page_id` field. You can get page ID using [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586). 
  
**Next steps**

Depending on your business needs, you can perform the following tasks: 
- Use [/ad/aco/update/](https://ads.tiktok.com/marketing_api/docs?id=1739473077112833) to modify automated ad creatives. 
- Use [/ad/aco/material_status/update/](https://ads.tiktok.com/marketing_api/docs?id=1739506701165570) to update the status of creative materials for an automated ad, including ad texts, images, and video materials. 
