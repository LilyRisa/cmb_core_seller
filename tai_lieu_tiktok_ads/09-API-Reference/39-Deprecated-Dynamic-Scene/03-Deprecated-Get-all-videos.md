---
title: "(Deprecated) Get all videos"
doc_id: 1740317360823297
path: "API Reference / (Deprecated) Dynamic Scene / (Deprecated) Get all videos"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Get all videos

Use this endpoint to get all videos that are generated from a material package.

> <span style="color: dodgerblue">**Note**</span><br>The Dynamic Scene API is now in beta. If you would like access to this feature, please apply for the allowlisting using the [Allowlist Management](https://ads.tiktok.com/marketing_api/blog/detail/7102266224578396162) page. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/dynamic_scene/get/| /v1.3/dynamic_scene/get/|
|Request parameter data type |`advertiser_id`: number |`advertiser_id`: string |
|Response parameter name|`url` |`video_preview_url` |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/dynamic_scene/get/

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
|material_package_id {Required}|string|Material Package ID.|
| page | number | Page that you want to get. |
| page_size | number | Number of results on each page. Default value: 10. |
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|list|object[]|Videos generated in a specified material package ID.|
##|video_id|string|Video ID. |
##|video_preview_url|string|Preview link of the video. Notice that the period of validity is 24 hours. |
##|music_id|string|Music materials. You can get music IDs through [Music Upload](https://ads.tiktok.com/marketing_api/docs?id=1740052650395650) endpoint. |
##|music_name|string|Music name. |
##|music_preview_url|string|Music link. |
##|width|number| Video width.|
##|height|number| Video height.|
##|duration|number|Video Duration (Seconds). |
##|story_arc|string[]|Refers to the structure and shape of a story, consisting of multiple slots.|
##|slots |object[]|Slot information.|
###|order |number|The order of the story arc, ranging from 0 to 5.|
###|tag |string|The tag of a slot.|
###|video_ids|string[]|Video materials. You can get video IDs through [Video Upload](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449) endpoint. |
###|mute|boolean|Whether to turn on the soundtrack of a scene. |
###|bgm_volume|number|The volume of background music, as an integer, ranging from [0, 100].|
#|page_info|object| Pagination information. |
##|page |number| Current page number. |
##|page_size |number| Page size. |
##|total_number |number|Total number of results. |
##|total_page |number| Total pages of results. |
|request_id |string|The log id of the request, which uniquely identifies a request.  |
```
### Example

``` xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "202206240324350102452421632116E80F",
    "data": {
        "list": [
            {
                "width": 720,
                "video_id": "v10033g50000caq0qhjc77u6j3pnlgfg",
                "music_id": "6989830756676960258",
                "url": "http://v16m-default.akamaized.net/17eb07f08a57b5308cba3e75f765cb9f/62b582e6/video/tos/alisg/tos-alisg-v-0000/5e3f7aa540a04969931c3620fd7cee98/?a=0&ch=0&cr=0&dr=0&er=0&lr=default&cd=0%7C0%7C0%7C0&br=484&bt=242&btag=80000&cs=0&ds=1&ft=uSV.Ng3-InzuW60oABM&mime_type=video_mp4&qs=0&rc=OTk0ZTllOmlpOGlnZWZpOUBpampxNjk6ZmszZDMzODYzNEBeNjRjLTFfNTAxMl8uYjMwYSNjaW1ncjRnbm5gLS1kMC1zcw%3D%3D&l=202206240324350102440292020C017708",
                "duration": 19.538,
                "story_arc": [
                    "asd"
                ],
                "height": 1280,
                "music_preview_url": "https://sf-tk-sg.ibytedtos.com/obj/tos-alisg-ve-2774/cd27dc1363f541b39f5cd06959f77263",
                "slots": [
                    {
                        "video_id": "v10e84g50000c2ee7s5gt180ugq54gm0",
                        "tag": "asd",
                        "bgm_volume": 100,
                        "order": 0,
                        "mute": true
                    }
                ],
                "music_name": "Sleepmode"
            },
            {
                "width": 720,
                "video_id": "v10033g50000caq0qhjc77u350aiaj40",
                "music_id": "6904872562267848706",
                "url": "http://v16m-default.akamaized.net/1a55f60bba00a1c87d9ce5ce71ba34b4/62b582e6/video/tos/alisg/tos-alisg-v-0000/5ca2a7dc601b4d26b24aace09301fbbb/?a=0&ch=0&cr=0&dr=0&er=0&lr=default&cd=0%7C0%7C0%7C0&br=484&bt=242&btag=80000&cs=0&ds=1&ft=uSV.Ng3-InzuW60oABM&mime_type=video_mp4&qs=0&rc=NzszZTw6ZDZmNmZoOTM8aEBpM21sMzY6ZmszZDMzODYzNEAzYy0xL2I0XjAxXzAtMDEtYSMxXl4ycjRnbm5gLS1kMC1zcw%3D%3D&l=202206240324350102450241122701B0C6",
                "duration": 19.525,
                "story_arc": [
                    "asd"
                ],
                "height": 1280,
                "music_url": "https://sf16-sg-default.akamaized.net/obj/tiktok-obj/df3d4e1830a1128bdebf83bedac8df69.mp3",
                "slots": [
                    {
                        "video_id": "v10e84g50000c2ee7s5gt180ugq54gm0",
                        "tag": "asd",
                        "bgm_volume": 100,
                        "order": 0,
                        "mute": true
                    }
                ],
                "music_name": "Happy and fun corporate music for advertising."
            },
            {
                "width": 720,
                "video_id": "v10033g50000caq0qibc77ucb31t2kcg",
                "music_id": "6836343392953384966",
                "url": "http://v16m-default.akamaized.net/bd02bf656197a57cd15bbeb726c13815/62b582e7/video/tos/alisg/tos-alisg-v-0000/b5b57cfbef854605a62e36f60cf2dd77/?a=0&ch=0&cr=0&dr=0&er=0&lr=default&cd=0%7C0%7C0%7C0&br=1394&bt=697&btag=80000&cs=0&ds=3&ft=uSV.Ng3-InzXW60oABM&mime_type=video_mp4&qs=0&rc=aWk4NzczMzs8N2Y5Zzg8PEBpam53NmY6ZmwzZDMzODYzNEAuXjNjNTUtXjYxLjJiYTJgYSNgLy5fcjRfbm5gLS1kMC1zcw%3D%3D&l=2022062403243601025105814402017216",
                "duration": 19.544,
                "story_arc": [
                    "asd"
                ],
                "height": 1280,
                "music_url": "http://sf-tk-sg.ibytedtos.com/obj/cc-music-sg/6723099489072057095",
                "slots": [
                    {
                        "video_id": "v10e84g50000c2ee7s5gt180ugq54gm0",
                        "tag": "asd",
                        "bgm_volume": 100,
                        "order": 0,
                        "mute": true
                    }
                ],
                "music_name": "r&b loop"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 100,
            "total_page": 1,
            "total_number": 3
        }
    }
}
(/code);
```
