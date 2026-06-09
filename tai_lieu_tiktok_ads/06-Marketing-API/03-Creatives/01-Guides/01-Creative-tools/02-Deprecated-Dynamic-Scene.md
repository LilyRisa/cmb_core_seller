---
title: "(Deprecated) Dynamic Scene"
doc_id: 1740307384545345
path: "Marketing API / Creatives / Guides / Creative tools / (Deprecated) Dynamic Scene"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Dynamic Scene

Dynamic Scene provides a creative automation solution that uses machine learning to dynamically assemble scenes individually provided by advertisers with other ad components (e.g., background music, and transition effects) to derive creative assets, boost ad creation efficiency and drive ad performance. 

By integrating Dynamic Scene API, you (developers) can enable advertisers to:
* Scale creative production by dynamically assembling scenes and other ad components to create hundreds of ad versions in a short period of time.
* Analyze the performance of different scenes and different story arcs in order to identify the best-performing ones to guide and inspire future creative optimization and development.

> <span style="color: dodgerblue">**Note**</span><br>The Dynamic Scene API is now in beta. If you would like access to this feature, please apply for the allowlisting using the [Allowlist Management](https://ads.tiktok.com/marketing_api/blog/detail/7102266224578396162) page. 

## Terms

* Scene: A Scene is a section of a video that is the minimum storytelling unit.
* Story Arc: Story arc refers to the structure and shape of a story, consisting of multiple slots. 

## Create ads with dynamic scenes

1.  **Create a campaign** using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602).
2. **Create an ad group** using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114) and make sure to set the field `creative_material_mode` to be `DYNAMIC`.
3. **Upload original scenes** using [/file/video/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449) and you will get `video_id` in the response. 
   
      The uploaded scenes will be assembled to build dynamic scenes in the next steps.   
	  Recommendation: 
*  Duration: 3-20s  
*  Definition: 720P and above
*  Orientation and Size: 

Recommended: Portrait / vertical (9:16) .   
Requirement:   Portrait / vertical (9:16), Landscape / horizontal (16:9), Square (1:1)  

4. **Generate dynamic scene videos** using the following Dynamic Scene API.
    1. Use [/dynamic_scene/material/submit/](https://ads.tiktok.com/marketing_api/docs?id=1740317263937538) to prepare your scenes, and make sure to pass the value of `video_id` that you received from Step 3 in the `video_ids` field.  You will get the `material_package_id` of the uploaded scenes in the response.  
	2. Use endpoint [/dynamic_scene/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740317320231938) to create an asynchronous task to generate videos using the `material_package_id` , and you will get a `task_id` in the response. 
	3. Use [/dynamic_scene/task/get/](https://ads.tiktok.com/marketing_api/docs?id=1740317337705473) endpoint to check the status of the created task by providing the `task_id`. If the task is successful, you will get the generated dynamic scene video information (for example, `video_ id` and `story_arc`).
	     > <span style="color: dodgerblue">**Note**</span><br>If you are not happy with the videos generated, you can use the two endpoints [/dynamic_scene/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740317320231938) and  [/dynamic_scene/task/get/](https://ads.tiktok.com/marketing_api/docs?id=1740317337705473) together to create a new task and generate more videos(maximum of 8 videos each time).
5. **Create an automated ad** using [/ad/aco/create/](https://ads.tiktok.com/marketing_api/docs?id=1739473063234626) and make sure to pass the video IDs that you received from Step 4-iii in the `video_id` field. 
 6. **Run a report** later using [/dynamic_scene/report/get/](https://ads.tiktok.com/marketing_api/docs?id=1740317382510593) to view the scene level performance data including video completion count and video drop off count. 

## Related endpoints 


* [Submit materials](https://ads.tiktok.com/marketing_api/docs?id=1740317263937538)
* [Create an async task](https://ads.tiktok.com/marketing_api/docs?id=1740317320231938)
* [Get task results](https://ads.tiktok.com/marketing_api/docs?id=1740317337705473)
* [Get all videos](https://ads.tiktok.com/marketing_api/docs?id=1740317360823297)
* [Run a report](https://ads.tiktok.com/marketing_api/docs?id=1740317382510593)
