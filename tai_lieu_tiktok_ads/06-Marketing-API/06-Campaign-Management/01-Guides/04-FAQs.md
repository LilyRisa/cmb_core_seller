---
title: "FAQs"
doc_id: 1767309272869890
path: "Marketing API / Campaign Management / Guides / FAQs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# FAQs

This article summarizes FAQs about the Campaign Management API.

## Ad group 
Q1: **I specified `app_id` in the campaign creation endpoint (for example, /adgroup/create/), but got an error "DB record not found". Why would this happen?** 
<br>Please check the `app_id` you are using. The `app_id` you specified in the request should be the mobile app ID rather than the API App ID that you can find on the Developer Portal. The mobile app ID is added to the ad account under the events manager, which you plan on promoting.

Q2: **How to create an ad group for Value Optimization?**
<br>Check out [Value Optimization](https://ads.tiktok.com/marketing_api/docs?id=1739381743067137) to find out how to enable Value Optimization.

Q3:  **How to configure interests and behavior targeting via API?**
<br>Please use the following endpoints to configure targeting options for interests and behavior.
-  Interest targeting:
  - [/tool/interest_category/](https://ads.tiktok.com/marketing_api/docs?id=1737174348712961) : Get available interest categories.
  - [/tool/interest_keyword/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1763590884474882): Get recommended interest keywords. 
  - [/tool/interest_keyword/get/](https://ads.tiktok.com/marketing_api/docs?id=1763590894544897) : Get available interest keywords by ID.
- Video interactions:
  - [/tool/action_category/](https://ads.tiktok.com/marketing_api/docs?id=1737166752522241) : Get available video interaction categories(action_scene = VIDEO_RELATED in the response). 
  - [/tool/targeting_category/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1736275204260866) : Get recommended video interaction categories ( scene = VIDEO_RELATED in the response)
- Creator interactions: 
  - [/tool/action_category/](https://ads.tiktok.com/marketing_api/docs?id=1737166752522241) : Get available creator interaction categories (action_scene = CREATOR_RELATED in the response). 
  - [/tool/targeting_category/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1736275204260866) :  Get recommended creator interaction categories (scene = CREATOR_RELATED in the response). 
- Hashtag interactions:
  - [/tool/hashtag/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1736271339521025) : Get recommended hashtags.
  - [/tool/hashtag/get/](https://ads.tiktok.com/marketing_api/docs?id=1736280889167874) : Get hashtags by IDs. 
 
## Ad
 Q1: **I received an error message "The creative type is not consistent with your ad group settings. Please change the creative type in your ad group settings." when calling the /ad/create/ endpoint.**
<br>The reason for this error message is your ad group has enabled Automated Creative Optimization (ACO)(`creative_material_mode` = `DYNAMIC`), but you used the 'regular' ad creation endpoints /ad/create/ to create ACO ads. To resolve this, you should use [/ad/aco/create/](https://ads.tiktok.com/marketing_api/docs?id=1739473063234626) instead to create ACO ads. 

Q2: **I received an error message " Unsupported image size. Please upload another image"  when calling the /ad/create/ endpoint. Why would this error message occur?**
<br>Your image did not meet our requirements. See [Image Ads Specification](https://ads.tiktok.com/help/article/image-ads-specification?lang=en) or [Video Ads Specifications](https://ads.tiktok.com/help/article/video-ads-specifications?lang=en) to find out more detailed specifications. 

Q3: **I received an error message "Invalid identity type" when calling the /identity/video/get/ endpoint. Why would this error message occur?** 
<br>It might be due to passing `CUSTOMIZED_USER` as the `identity_type`. `CUSTOMIZED_USER` is not supported in the /identity/video/get/ endpoint, and the supported identity types are `AUTH_CODE`, `TT_USER` and `BC_AUTH_TT`. 
<br>Currently there are four types of authorized identities. The three identity types of `AUTH_CODE`, `TT_USER` and `BC_AUTH_TT`  do not have a `video_id`, but have a `tiktok_item_id` associated with them. If you want to create Spark Ads, you should use the three types. The fourth identity type `CUSTOMIZED_USER` has a `video_id`, meaning that you have an ad that is using a video uploaded to the Ads Manager Creative Library. Check out [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097) to learn more. 


## Other
Q1: **I noticed that some parameters were not displayed in the response or were being ignored by the request, and why would this happen?**
<br>One of the possible causes is you were using v1.2 syntax but the v1.3 API endpoint. 
<br>In API v1.3, we've updated the parameter names and types to make them more clear and meet industry standards.  For example, `placement` was renamed to `placements` in v1.3, and if you still use `placement`  as the request parameter in the /v1.3/adgroup/create/ endpoint, the parameter would be ignored. Please refer to the "Comparing v1.2 and v1.3" table in each API endpoint article to find out the detailed changes. 
