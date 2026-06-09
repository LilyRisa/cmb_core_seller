---
title: "Migrate Campaign Management APIs"
doc_id: 1749537346290689
path: "Overview / V1.3 updates / Migrate to v1.3 / Migrate Campaign Management APIs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Migrate Campaign Management APIs

Please read the following guidance before you migrate your campaign management calls to v1.3.  

1. Use `/campaign/create/` to create a campaign.  See [Create a campaign - Comparing v1.2 and v1.3](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602) for detailed changes, for example,  parameter name changes, data type changes and the full ad entity returned.  

2. Use `/adgroup/create/` to create an ad group. See [Create an ad group - Comparing v1.2 and v1.3](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114) for detailed changes, for example, parameter name changes, data type changes,  and the full ad entity returned.  

><span style="color:DodgerBlue">**Notes**</span><br><ul><li><strong>The enum value of `VIDEO_VIEW` is deprecated in the `optimization_goal` field.</strong> We recommend that you use `ENGAGED_VIEW` or `ENGAGED_VIEW_FIFTEEN` (on allowlist) when `objective_type` is `VIDEO_VIEW` at the campaign level. </li><li>When `objective_type` is `VIDEO_VIEW` and `bid_type` is `BID_TYPE_CUSTOM`,  `bid_display_mode` is required and must be set to `CPV`(Cost per One View). <strong>You can no longer use `CPMV` in the `bid_display_mode` field. </strong> </li><li> <strong>The `cpv_video_duration` field is deprecated. </strong> You can use the optimization goal of `ENGAGED_VIEW`(6-second views (Focused view)) or `ENGAGED_VIEW_FIFTEEN` (15-second views(Focused view), on allowlist) to specify your video play length. </li><li><strong>`pixel_id` can only be used for the advertising objectives of `CONVERSIONS`, `PRODUCT_SALES`.</strong>  If you want to track events for other objectives, use `tracking_pixel_id` at the ad level. </li></ul>

3. Use `/ad/create/` to create an ad.  See [Create ads - Comparing v1.2 and v1.3](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) for detailed changes, for example, parameter name changes, data type changes,  and the full ad entity returned.

><span style="color:DodgerBlue">**Note**</span><br> `identity_id` and `identity_type` are required. 

**OR** use `/ad/aco/create/` to create an automatic ad.  

><span style="color:DodgerBlue">**Note**</span><br>[/ad/aco/create/](https://ads.tiktok.com/marketing_api/docs?id=1739473063234626) is a totally new endpoint in v1.3, and both `identity_id` and `identity_type` are required. 
