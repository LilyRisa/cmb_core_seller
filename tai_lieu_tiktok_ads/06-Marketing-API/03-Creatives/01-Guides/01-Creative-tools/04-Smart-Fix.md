---
title: "Smart Fix"
doc_id: 1741472523309058
path: "Marketing API / Creatives / Guides / Creative tools / Smart Fix"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Smart Fix

Smart Fix is a feature that helps you identify and fix video issues, such as video size and resolution, which could later get your ads disapproved or rejected during campaign creation process.

With Smart Fix enabled, we will automatically detect and fix the following issues in your video: 
* `LOW_RESOLUTION`: Video resolution rate is lower than 540x960px, which doesn't meet our requirements.
* `ILLEGAL_VIDEO_SIZE`: The video size is not correct.Please use the standard video size: Square (1:1)/Vertical (9:16)/Horizontal(16:9) .

Smart Fix API's function is two-fold:
- When uploading new videos, you can turn on Smart Fix.  We will conduct a pre-audit of your videos. Moreover, if we detect any of the issues mentioned previously, we will automatically perform a quick fix, so that the videos uploaded can be more compliant to TikTok's standards.
- For existing videos, we offer two new endpoints for you to create an audit task as well as retrieve the task result, enabling you to comprehensively check all your existing videos for potential issues.

## How to enable Smart Fix
### Enable Smart Fix when uploading new videos
1. Use [/file/video/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449) to upload new videos. Make sure to specify the following fields to enable Smart Fix. 
*  Set `flaw_detect` to `true` to automatically detect issues in your video.
*  Set `auto_fix_enabled` to `true` to automatically fix the detected issues. 
*  Set `auto_bind_enabled` to `true` if you want to automatically upload the fixed videos to the creative library.

 If any issues are detected, we'll perform a fix and return `fix_task_id` and `flaw_types` in the response. 

2. Use [/video/fix/task/get/ ](https://ads.tiktok.com/marketing_api/docs?id=1741469487859714) to get task results. 
*  Pass in the fix task ID (`fix_task_id`)you get from Step 1 to the `task_id` field.  
*  Pass in advertiser ID to the `advertiser_id` field. 
 
 We will return task status in the response. If the status is `SUCCESS`,  you can use the returned video ID(`video_id`) to create an ad. 


### Enable Smart Fix for existing videos

1. Use [/video/fix/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1741468875279361) to create an async task to detect and fix video issues.
*  Choose the video that you want to fix and pass in the video ID to the `video_id` field. 
*   Pass in advertiser ID to the `advertiser_id` field. 

 If any issues are detected, we'll perform a fix and return `fix_task_id` and `flaw_types` in the response. 
 
 2. Use[ /video/fix/task/get/](https://ads.tiktok.com/marketing_api/docs?id=1741469487859714)  to get task results. 
*  Pass in the fix task ID (`fix_task_id`) you get from Step 1 to the `task_id` field.  
*  Pass in advertiser ID to the `advertiser_id` field. 
 
 We will return task status in the response. If the status is `SUCCESS`,  you can use the returned video ID (`video_id`) to create an ad. 







