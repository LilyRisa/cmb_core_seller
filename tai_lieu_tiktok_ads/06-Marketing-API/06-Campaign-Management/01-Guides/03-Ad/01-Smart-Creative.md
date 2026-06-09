---
title: "Smart Creative"
doc_id: 1767322784934914
path: "Marketing API / Campaign Management / Guides / Ad / Smart Creative"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Smart Creative

Smart Creative is a comprehensive AI-powered tool that houses creative diagnose and asset reporting, auto-generation, and delivery optimization under one roof. It helps advertisers generate more ready-for-delivery creatives with limited materials and maximize the revenue of these creatives. 


> <span style="color:DarkRed">**Warning**</span><br>You can not create Smart Creative ads under existing ad groups that already have ads created within them. 

## Create Smart Creative ads
1. Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602).
- Currently, Smart Creative only supports `APP_PROMOTION`,`WEB_CONVERSIONS`, `TRAFFIC`, or `LEAD_GENERATION` as advertising objective (`objective_type`) at the campaign level. 
2. Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114).
- Set `creative_material_mode` to `CUSTOM`.
3. Upload creatives using the following endpoints:
- Upload videos using [/file/video/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449), and get `video_id` in response.
- Upload images using [/file/image/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642), and get `image_id` in response.
- Get standard or dynamic call-to-action texts using [/creative/cta/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1739362202742785). See [CTA recommendations](https://ads.tiktok.com/marketing_api/docs?id=1740307296329730) for details.
4. Create a Smart Creative ad using [/ad/aco/create/](https://ads.tiktok.com/marketing_api/docs?id=1739473063234626).
- Set `is_smart_creative` as `true`. 
> <span style="color:DodgerBlue">**Note**</span><br>When `is_smart_creative` is set to `true`, the system will automatically process `creative_material_mode` as `SMART_CREATIVE` at the ad group level. 
- Enter image ID and video ID that you get from Step 3 in the `web_uri` and `video_id` fields (in `media_info`) respectively.
> <span style="color:DodgerBlue">**Note**</span><br>You can enter no more than 30 materials in the `media_info` object. The materials should not be duplicated.
- Enter call-to-action texts you get from Step 3 in the `call_to_action_list` or `call_to_action_id` fields.
> <span style="color:DodgerBlue">**Note**</span><br>You can enter no more than three call-to-action texts. These texts should not be duplicated.
- Enter ad texts in `title_list`.
> <span style="color:DodgerBlue">**Note**</span><br>You can enter no more than five texts. These texts should not be duplicated.
- Enter the instant page ID in the `page_id` field.<ul><li>To obtain pages within your ad account, use [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586).</li><li>To create instant pages, use [Instant Page Editor SDK](https://business-api.tiktok.com/portal/docs?id=1740307202170881).</li></ul>
