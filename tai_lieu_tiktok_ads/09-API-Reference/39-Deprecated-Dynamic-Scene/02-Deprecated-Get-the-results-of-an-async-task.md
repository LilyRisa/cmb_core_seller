---
title: "(Deprecated) Get the results of an async task"
doc_id: 1740317337705473
path: "API Reference / (Deprecated) Dynamic Scene / (Deprecated) Get the results of an async task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Get the results of an async task

Use this endpoint to get the results of an async task.

If the task completes (`status` = `SUCCESS`), you can call [/dynamic_scene/get/](https://ads.tiktok.com/marketing_api/docs?id=1740317360823297) to download videos that are generated from the material package you submit.

> <span style="color: dodgerblue">**Note**</span><br>The Dynamic Scene API is now in beta. If you would like access to this feature, please apply for the allowlisting using the [Allowlist Management](https://ads.tiktok.com/marketing_api/blog/detail/7102266224578396162) page. 

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/dynamic_scene/task/get/| /v1.3/dynamic_scene/task/get/|
|Request parameter data type |`advertiser_id`: number |`advertiser_id`: string |
|Response parameter name|`url` |`video_preview_url` |
```


## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/dynamic_scene/task/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser  ID. |
|task_id|string|Task ID, in the response of [/dynamic_scene/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740317320231938) endpoint.|
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|status|string|Status of the task. Enum values: `PROCESSING`, `FAIL`, `SUCCESS`.|
#|error_msg|string|The error massage if the task has exceptions.|
#|material_package_id|string|The mapping material package ID of the task. |
#|available_quota|number|The number of videos remaining in your quota. You can create a new task to generate more videos, but the number of videos is not allowed to exceed the remaining quota. |
#|videos|object[]|Max size=8, Videos created by Dynamic Scene API.|
##|music_id|string|Music ID. |
##|music_name|string|Music name. |
##|music_preview_url |string|Music preview link. |
##|width|number| Video width.|
##|height|number| Video height.|
##|duration|number|Video Duration (seconds). |
##|story_arc|string[]|Refers to the structure and shape of a story, consisting of multiple slots.|
##|video_id|string|Video ID. You can use it to create Ads.|
##|video_preview_url|string|Preview link of the video. Notice that the period of validity is 24 hours. |
##|slots |object[]|Slot information.|
###|order |number|The order of slot in the story arc, ranging from 0 to 5.|
###|tag |string|The tag of a slot.|
###|video_ids|string[]|Video materials.|
###|mute|boolean|Whether to turn on the soundtrack in a slot. |
###|bgm_volume|number|The volume of background music, as an integer, ranging from [0, 100].|
|request_id |string|The log id of the request, which uniquely identifies a request.  |
```

### Example

``` xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "20220624071802010245246039081FD421",
    "data": {
        "status": "SUCCESS",
        "material_package_id": "3361016942373889",
        "available_quota": 0,
        "error_msg": null,
        "videos": [
            {
                "url": "http://v16m-default.akamaized.net/6cd7e6da75783cc83daaad8b2f8e6ae1/62b5b99d/video/tos/alisg/tos-alisg-v-0000/5e3f7aa540a04969931c3620fd7cee98/?a=0&ch=0&cr=0&dr=0&er=0&lr=default&cd=0%7C0%7C0%7C0&br=484&bt=242&btag=80000&cs=0&ds=1&ft=uSV.Ng3-InzlkO0oABM&mime_type=video_mp4&qs=0&rc=OTk0ZTllOmlpOGlnZWZpOUBpampxNjk6ZmszZDMzODYzNEBeNjRjLTFfNTAxMl8uYjMwYSNjaW1ncjRnbm5gLS1kMC1zcw%3D%3D&l=2022062407180201024524424702000EEF",
                "music_id": "6989830756676960258",
                "slots": [
                    {
                        "tag": "asd",
                        "mute": true,
                        "bgm_volume": 100,
                        "order": 0,
                        "video_id": "v10e84g50000c2ee7s5gt180ugq54gm0"
                    }
                ],
                "music_url": "https://sf-tk-sg.ibytedtos.com/obj/tos-alisg-ve-2774/cd27dc1363f541b39f5cd06959f77263",
                "video_id": "v10033g50000caq0qhjc77u6j3pnlgfg",
                "music_name": "Sleepmode",
                "duration": 19.538,
                "story_arc": [
                    "asd"
                ],
                "height": 1280,
                "width": 720
            },
            {
                "url": "http://v16m-default.akamaized.net/50010b1fab7474435054e71b79c747f6/62b5b99d/video/tos/alisg/tos-alisg-v-0000/5ca2a7dc601b4d26b24aace09301fbbb/?a=0&ch=0&cr=0&dr=0&er=0&lr=default&cd=0%7C0%7C0%7C0&br=484&bt=242&btag=80000&cs=0&ds=1&ft=uSV.Ng3-InzlkO0oABM&mime_type=video_mp4&qs=0&rc=NzszZTw6ZDZmNmZoOTM8aEBpM21sMzY6ZmszZDMzODYzNEAzYy0xL2I0XjAxXzAtMDEtYSMxXl4ycjRnbm5gLS1kMC1zcw%3D%3D&l=202206240718020102452430990E000101",
                "music_id": "6904872562267848706",
                "slots": [
                    {
                        "tag": "asd",
                        "mute": true,
                        "bgm_volume": 100,
                        "order": 0,
                        "video_id": "v10e84g50000c2ee7s5gt180ugq54gm0"
                    }
                ],
                "music_url": "https://sf16-sg-default.akamaized.net/obj/tiktok-obj/df3d4e1830a1128bdebf83bedac8df69.mp3",
                "video_id": "v10033g50000caq0qhjc77u350aiaj40",
                "music_name": "Happy and fun corporate music for advertising.",
                "duration": 19.525,
                "story_arc": [
                    "asd"
                ],
                "height": 1280,
                "width": 720
            },
            {
                "url": "http://v16m-default.akamaized.net/5aaab797e0dcc4306b0e27fd8a2b3eb3/62b5b99e/video/tos/alisg/tos-alisg-v-0000/6a26ca2b7f9448d68deef8bc4783b63f/?a=0&ch=0&cr=0&dr=0&er=0&lr=default&cd=0%7C0%7C0%7C0&br=484&bt=242&btag=80000&cs=0&ds=1&ft=uSV.Ng3-InzPkO0oABM&mime_type=video_mp4&qs=0&rc=aDZoN2Q1aThpOmk8NWVmPEBpam53NmY6ZmwzZDMzODYzNEAuNGFfX2MwX14xLi4zLl5iYSNgLy5fcjRfbm5gLS1kMC1zcw%3D%3D&l=2022062407180301024524804306000435",
                "music_id": "6836343392953384966",
                "slots": [
                    {
                        "tag": "asd",
                        "mute": true,
                        "bgm_volume": 100,
                        "order": 0,
                        "video_id": "v10e84g50000c2ee7s5gt180ugq54gm0"
                    }
                ],
                "music_url": "http://sf-tk-sg.ibytedtos.com/obj/cc-music-sg/6723099489072057095",
                "video_id": "v10033g50000caq0qibc77ucb31t2kcg",
                "music_name": "r&b loop",
                "duration": 19.544,
                "story_arc": [
                    "asd"
                ],
                "height": 1280,
                "width": 720
            }
        ]
    }
}
(/code);
```
