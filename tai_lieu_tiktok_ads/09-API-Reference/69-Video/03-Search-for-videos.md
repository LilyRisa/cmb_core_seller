---
title: "Search for videos"
doc_id: 1740050472224769
path: "API Reference / Video / Search for videos"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Search for videos

Use this endpoint to search for video creatives that can be used in ads in the Asset Library of an ad account.

The response will only include the 10,000 most recently modified videos.

 ## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/file/video/ad/search/|/v1.3/file/video/ad/search/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Request parameters deprecated in v1.3|/|`height`<br>`ratio`<br>`width`<br>`displayable`|
|Response parameter name|`poster_url` <br>`url` |`video_cover_url`<br>`preview_url`|
|Response parameters deprecated in v1.3|/|`id`|
|New request parameters|/|`video_name`<br>`video_material_sources`|
|New response parameters|/|`preview_url_expire_time`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/file/video/ad/search/

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|filtering |object| Filters on the data. This parameter is an array of filter objects.|
#|video_ids |string[]|A list of video IDs. <br><br>Max size: 100. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If the video cannot be used in ads, the video ID will be ignored.</li><li>If this filter field is not passed, and multiple video IDs have been generated for the same video due to multiple uploads, only one valid video ID will be returned for the video.</li></ul>|
#|material_ids|string[]|A list of material IDs.<br><br> Max size: 20.|
#|video_name|string|Video name. You can perform a fuzzy search by entering the video name.|
#|video_material_sources|string[]|List of video material sources. You can search for videos based on their material sources. <br>Enum values: <ul><li>`UPLOADED_TO_TIKTOK_ADS_MANAGER`: Uploaded to TikTok Ads Manager. </li><li> `UPLOADED_TO_CATALOG`: Uploaded to Catalog. </li><li> `CREATIVE_TOOL_SMART_VIDEO`: Creative Tool - Smart Video. </li><li> `CREATIVE_TOOL_QUICK_OPTIMIZATION`: Creative Tool - Quick Optimization. </li><li> `CREATIVE_TOOL_VIDEO_TEMPLATE`: Creative Tool - Video Template.</li><li>`CREATIVE_TOOL_SMART_VIDEO_SOUNDTRACK`: Creative Tool - Smart Video Soundtrack. </li><li>`CREATIVE_TOOL_TIKTOK_VIDEO_EDITOR`: Creative Tool - TikTok Video Editor. </li><li>`TIKTOK_CREATIVE_EXCHANGE`: TikTok Creative Exchange.</li><li>`CATALOG_VIDEO_TEMPLATE`: Catalog Video Template. </li><li>`DYNAMIC_VIDEO_EDITOR`: Dynamic Video Editor. </li><li>`CREATIVE_CHALLENGE`: Creative challenge. </li><li>`AUTOMATED_CREATIVE_OPTIMIZATION`: Automated Creative Optimization. </li><li>`OTHER`: Other. </li><li> `QUICK_GENERATION`: Quick Generation.</li><li>`CREATIVE_CENTER_VIDEO_UPLOAD`: Creative Center - Video upload.</li><li> `CREATIVE_CENTER_TIKTOK_VIDEO_EDITOR`：Creative Center - TikTok video editor.</li><li> `CREATIVE_CENTER_VIDEO_TEMPLATE`：Creative Center - Video template.</li><li>`DYNAMIC_SCENE`：Dynamic Scene.</li><li>`SMART_OPTIMIZATION_TOOL`: Smart Optimization Tool.</li></ul>|
|page |number|Current page number.  <br><br>Default value: 1. Value range: ≥ 1.|
|page_size |number|Page size.  <br><br>Default value: 20. Value range: 1-100.|
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id={advertiser_id}" \
--data-urlencode "filtering={\"width\": \"{width}\", \"video_ids\": [\"{video_ids}\"], \"ratio\": [\"{ratio}\"], \"height\": \"{height}\"}" \
--data-urlencode "page={page}" \
--data-urlencode "page_size={page_size}" \
https://business-api.tiktok.com/open_api/v1.3/file/video/ad/search/
(/code)
```


## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return Message, see [Appendix-Return Information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|Returned data.|
#|list |object[]|A list of video information. |
##|video_id |string|Video ID, which can be used to create ads.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the filter field `video_ids` is not passed, and multiple video IDs have been generated for the same video due to multiple uploads, only one valid video ID will be returned for the video.|
##|video_cover_url |string|Temporary URL for video cover, valid for six hours and needs to be re-acquired after expiration. <br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds. <br>Example: `http://p16-sign-sg.tiktokcdn.com/v0201/b99a388e3709470be5c~tplv-noop.image?x-expires=<strong>1671742348</strong>&x-signature=FziJhvED9NDTDmPofv3I%3D`.|
##|format |string|video Format|
##|preview_url |string|Video preview link, valid for six hours and needs to be re-acquired after expiration. <br>To find out the expiration time of the preview link, see `preview_url_expire_time`.|
##|preview_url_expire_time|string|The expiration time of the video preview link, in the format of `YYYY-MM-DD HH:MM:SS`(UTC+0).|
##|duration |float|Video duration, in seconds.|
##|height |number|Video height.|
##|width |number|Video width.|
##|bit_rate |float|Bit rate, in bps|
##|signature |string|MD5 of the video file. |
##|size |number|Video size.|
##|material_id|string|Material ID.|
##|allowed_placements |string[] |Available placements. Due to music copyright, some materials generated by creative tools can only be shown on TikTok. It won't pass when they are created on other placements. For enum values, see [Enumerations-Ad Management-Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|allow_download |boolean |Whether the video is downloadable. Due to the music copyright, some materials generated by creative tools are only allowed to preview. It is prohibited to download and disseminate them. |
##|file_name |string |Video name.|
##|create_time |string |Creation time. UTC time. Format: 2020-06-10T07:39:14Z. |
##|modify_time |string |Modification time. UTC time. Format: 2020-06-10T07:39:14Z |
##|displayable |boolean |Whether it can be displayed on the platform.|
#|page_info |object|Pagination information.|
##|page |number|Current page number.|
##|page_size |number|Page size.|
##|total_number |number|Total number of results.|
##|total_page |number|Total pages of results.|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "page_info": {
            "total_number": 1,
            "page": 1,
            "page_size": 20,
            "total_page": 1
        },
        "list": [
            {
                "video_cover_url": "http://p16-sign-sg.tiktokcdn.com/v0201/94ebe1a3e6734559a16f3e81c43787ec~tplv-noop.image?x-expires=1607260002&x-signature=falvnSNOT94w4gjeB8PGGFyAdys%3D",
                "format": "mp4",
                "preview_url": "http://v16m-default.akamaized.net/64d28cac783ab57b70c2d91c30fb1b5d/5fccd762/video/tos/alisg/tos-alisg-v-0000/9a0033884eab42248ad6b46f7b11e44c/?a=0&br=3114&bt=1557&cd=0%7C0%7C0&cr=0&cs=0&dr=0&ds=3&er=&l=2020120607063701011517615517A19974&lr=ad&mime_type=video_mp4&qs=0&rc=anNlbnRuOWU2eDMzZjYzM0Apdjh3ZHJuOWZzZTMzNDY6eWdwMnFebGgzYG1fLS0uMDRzc2RtX2huaDNfMHItLWAwLS06Yw%3D%3D&vl=&vr=",
                "file_name": "wanfadong_test.mp4",
                "displayable": true,
                "height": 720,
                "width": 1280,
                "bit_rate": 1594584,
                "create_time": "2020-11-16T08:19:57Z",
                "modify_time": "2020-11-16T08:19:57Z",
                "signature": "b24f08f569b73a33eec93edb8f4423e2",
                "duration": 5.312,
                "video_id": "v070331c0000bup3cb66kkoqaktb5psg",
				"material_id": "6895633277416374000",
            	"allowed_placements": [
                    "PLACEMENT_TOPBUZZ",
                    "PLACEMENT_TIKTOK",
                    "PLACEMENT_HELO",
                    "PLACEMENT_PANGLE"
            	],
            	"allow_download": true,
                "size": 1058804
            }
        ]
    },
    "request_id": "2020120607063701011517615517A19974"
}
(/code);
```
