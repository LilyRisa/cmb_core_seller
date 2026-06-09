---
title: "(Deprecated) Smart Video Soundtrack, Smart Video, and Quick Optimization"
doc_id: 1749022727865345
path: "Marketing API / Creatives / Guides / Creative tools / (Deprecated) Smart Video Soundtrack, Smart Video, and Quick Optimization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Smart Video Soundtrack, Smart Video, and Quick Optimization

Smart Video Soundtrack, Smart Video, and Quick Optimization tools run in asynchronous mode and help you improve ad performance in an optimized and intelligent way. 

# Smart Video Soundtrack 
Smart Video Soundtrack helps you choose the right background music combined with the visuals and the theme of your ads will greatly improve your ad performance. You can change the "**Music**" and adjust "**Video Volume**" and "**Music Volume**" of your video, thus producing more creative ads. This tool provides 500,000+ pieces of music in 10 genres (Teen, Classic, Country, Ambient, Pop, EDM, MIX, Symphony, Rock, and Folk) for you to choose from. 
# Smart Video
A production tool that intelligently generates videos based on AI technology,  intelligently analyzes original material content and music beats, and generates multiple videos with only one click. See [Smart Video](https://ads.tiktok.com/help/article?aid=9527) for detailed introductions. 
# Quick Optimization
Quick Optimization is a smart tool that lets you instantly transform your brand videos into TikTok ready ad-creative. See [Quick Optimization](https://ads.tiktok.com/help/article/quick-optimization-video-tutorial?redirected=2) for detailed introductions. 
# Use Smart Video Soundtrack, Smart Video, and Quick Optimization 
These creative tools run in asynchronous mode, and  are implemented in an asynchronous and callback manner. You can take the following steps to use these tools:
1. Call the tool endpoint to create a task. A task ID (`task_id`) will be returned in the response.
  - [Smart Video Soundtrack](https://ads.tiktok.com/marketing_api/docs?id=1739089635852289)
  - [Quick Optimization](https://ads.tiktok.com/marketing_api/docs?id=1739355078757377)
  - [Smart Video](https://ads.tiktok.com/marketing_api/docs?id=1739352294717441)
2. Query the status and results of the task using [/creative/status/get/](https://ads.tiktok.com/marketing_api/docs?id=1739351979503618). 
3. If you have provided callback information (`callback_info`) when creating the task. Then when the task execution is completed, the server will callback the address and send you the task result. Refer to [Callback request details](https://ads.tiktok.com/marketing_api/docs?id=1739067004677122). 
