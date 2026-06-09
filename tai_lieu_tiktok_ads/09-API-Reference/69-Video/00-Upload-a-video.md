---
title: "Upload a video"
doc_id: 1737587322856449
path: "API Reference / Video / Upload a video"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload a video

Use this endpoint to upload a video to the Asset Library and use the obtained video ID for creating ads. 

Request timeout for this interface is 10s, and the transmission speed depends on network bandwidth. Please make sure that the file size is reasonable.

If you upload a video that already exists in the advertiser account, the API response will be the information of the existing video.

> <span style="color:DodgerBlue">**Note**</span><br>
> - We recommend that you turn on Smart Fix when uploading your videos. This API will automatically detect and fix issues in your videos, ensuring that they meet our requirements for delivery. To learn about how to enable Smart Fix, please refer to [Smart Fix](https://business-api.tiktok.com/portal/docs?id=1741472523309058).
> - Starting April 24th, 2025,  Smart Fix will limit automatic fixes only to the flaw types `LOW_RESOLUTION` and `ILLEGAL_VIDEO_SIZE` and function as follows: 
> 	- Resolution adjustments:
> 		- `LOW_RESOLUTION`: If this issue is detected, the video is automatically enhanced to a standard resolution of 1280x720 pixels, also referred to as 720p.
>   	- `ILLEGAL_VIDEO_SIZE`: If this issue is detected, the video is automatically adjusted to one of the standard aspect ratios: 1:1 (square), 9:16 (vertical), or 16:9 (horizontal). 
>  	- Output limitation: Only one fixed version of the video will be returned.
>  	- Naming conventions: The fixed video will be named either according to the custom name specified via the `file_name` parameter or following the default naming convention if no custom name is given (see the description of `file_name` for details).


 
    



## Before you start
You need to confirm the video meets the [Creative requirements for delivery](https://ads.tiktok.com/help/article?aid=6669199281502552069) before the video can be used in ads.


 ## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/file/video/ad/upload/|/v1.3/file/video/ad/upload/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|New request parameter |/|`is_third_party`: boolean<br>`flaw_detect`: boolean<br>`auto_fix_enabled`: boolean<br>`auto_bind_enabled`: boolean<br>`video_id`: string|
|Response parameter name|`poster_url` <br>`url`  |`video_cover_url`<br>`preview_url`|
|Response parameter deprecated in v1.3|/|`id`|
|New response parameter|/|`fix_task_id`:string<br>`flaw_types`: string[]<br>`preview_url_expire_time`: string|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/file/video/ad/upload/

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>If `upload_type` is `UPLOAD_BY_FILE`, use `multipart/form-data` . <br>If `upload_type` is `UPLOAD_BY_URL` or `UPLOAD_BY_FILE_ID`, use `application/json`.|

```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
| advertiser_id {Required}|string| Advertiser ID.|
| file_name |string |Video name. <br><br>Length limit: 1 - 100 characters. <br><br>Default value: <ul><li>When Smart Fix is not enabled (`flaw_detect` and `auto_fix_enabled` are set to `false`): <ul><li>If you upload a local file, the default name will be the original file name.</li><li>If you provide a URL, the default name will be the filename portion of the URL path.</li></ul>If the original file name or the filename portion of the URL path exceeds 100 characters, it will be truncated to use only the first 100 characters.</li><li>When Smart Fix is enabled (`flaw_detect` and `auto_fix_enabled` are set to `true`):<ul><li> The default name will be either the original file name (for a local file) or the filename portion of the URL path (for a URL). If you specify a custom video name via `file_name`, the `file_name` value will be the name of the fixed video.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Videos under the same `advertiser_id` cannot have duplicated file names. You can call [/file/name/check/](https://ads.tiktok.com/marketing_api/docs?id=1759130033155073) to check whether the file name has been used.<br>If you get an error about duplicated file names, please rename the files or append timestamps to the original file names (for example, in the format of `<original_filename>_<timestamp>`, and upload the videos again. |
| upload_type |string|Video upload method. <br><br>Default value: `UPLOAD_BY_FILE`<br><br>Enum values: `UPLOAD_BY_FILE`, `UPLOAD_BY_URL`, `UPLOAD_BY_FILE_ID`, `UPLOAD_BY_VIDEO_ID`.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you set this field to `UPLOAD_BY_FILE`, `UPLOAD_BY_URL` or `UPLOAD_BY_FILE_ID`, a new video ID (`video_id`) will be returned. If you upload the same video multiple times using any of these three methods, you'll obtain a new video ID for each upload.|
| video_file {+Conditional}|file| Required when `upload_type` is `UPLOAD_BY_FILE`. <br><br>Video file.<br><br>Recommended settings： <br>(1) File size：500 MB limit <br>(2) ratio：9:16, 16:9, and 1:1.<br>(3) Format：.mp4, .mov, .mpeg, .avi.<br><br>Example: `'video_file=@"/Users/admin/Downloads/sample-mov-file.mov"'`<br><br><p><span style="color:darkred"><b>Note</b></span>: Before uploading, make sure that the file is <b>playable</b> and in a <b>supported format</b>. |
| video_signature {+Conditional}|string|Required when `upload_type` is `UPLOAD_BY_FILE`.<br><br>Video MD5 (used for server verification). |
| video_url {+Conditional} | string | Required when `upload_type` is `UPLOAD_BY_URL`.<br><br>Video URL address, such as http://xxx.xxx. <br>(1) File size: better within 10MB. <br>(2) Verification: We will verify `Content-Type` in the response header. A common invalid media type is text (`Content-Type` = text/*, such as text/html, text/plain). Also, we will verify the data if you set a `Content-MD5` in the response header. <br>(3) Encoding: The URL needs to be a valid URL in the browser. For instance, the spaces in the URL need to be encoded into `%20`. You can copy and paste the URL into a browser address bar, then the URL will be automatically encoded.<br>(4) Others: ratio, format, resolution and bitrate limitation is the same as `video_file`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Before uploading, make sure that the video URL is <b>playable</b> and in a <b>supported format</b>. If the URL is invalid or in an unsupported format (i.e. txt.), you may see an error message or obtain an invalid video ID that cannot be used for ad creation. |
|file_id{+Conditional}| string |Required when `upload_type` is `UPLOAD_BY_FILE_ID`. <br><br>The `file_id` of the file that you want to upload. This field is for files that are uploaded to the file repository. You can get `file_id` via the [Upload Files](https://ads.tiktok.com/marketing_api/docs?id=1737719988918274) endpoints. |
|video_id{+Conditional}|string|Required when `upload_type` is `UPLOAD_BY_VIDEO_ID`.<br><br>Video ID. <br><br><p><span style="color:darkred"><b>Note</b></span>: You should pass in the `video_id` of the videos created on <b>TikTok Ads Manager</b> or through <b>API</b>. If you pass in the value from other sources, you may see an error message or cannot use the uploaded video for ad creation.<br> You can obtain `video_id` in the response of the [/file/video/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1737587322856449) endpoint or by using the [/file/video/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740050472224769) endpoint. |
|is_third_party|boolean| The video is third party or not.|
|flaw_detect|boolean|Whether to automatically detect an issue in your video. <br><br>Default value: `false`. |
|auto_fix_enabled|boolean|Whether to automatically fix the detected issue. <br><br>Default value : `false`. <br><br> If an issue is detected in your video: <ul><li>When `auto_fix_enabled` is set to `false`, we'll return an error message with flaw types indicated.</li><li>When `auto_fix_enabled` is set to `true`, we'll automatically fix the `LOW_RESOLUTION` and `ILLEGAL_VIDEO_SIZE` issues  and return `fix_task_id` and `flaw_types`. </li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When a `LOW_RESOLUTION` issue is detected, the video is automatically enhanced to a standard resolution of 1280x720 pixels, also referred to as 720p.</li><li>When an `ILLEGAL_VIDEO_SIZE` issue is detected, the video is automatically adjusted to one of the standard aspect ratios: 1:1 (square), 9:16 (vertical), or 16:9 (horizontal).</li></ul>|
|auto_bind_enabled|boolean| Valid only when `flaw_detect` = `true`  and `auto_fix_enabled` = `true`. <br><br>Whether to automatically upload the fixed video to your creative library. <br><br>Default value : `false`. |
```

### Example
#### Upload by file with Smart Fix enabled

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/file/video/ad/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--form 'advertiser_id="{{advertiser_id}}"' \
--form 'file_name="{{file_name}}"' \
--form 'upload_type="UPLOAD_BY_FILE"' \
--form 'video_file=@"/Desktop/Example.mp4"' \
--form 'video_signature="{{video_signature}}"' \
--form 'flaw_detect="true"' \
--form 'auto_fix_enabled="true"' \
--form 'auto_bind_enabled="true"'
(/code)
```
#### Upload by file with Smart Fix disabled

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/file/video/ad/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--form 'advertiser_id="{{advertiser_id}}"' \
--form 'file_name="{{file_name}}"' \
--form 'upload_type="UPLOAD_BY_FILE"' \
--form 'video_file=@"/Desktop/Example.mp4"' \
--form 'video_signature="{{video_signature}}"' 
(/code)
```
#### Upload by URL with Smart Fix enabled

```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/file/video/ad/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "advertiser_id":"{{advertiser_id}}",
    "file_name":"{{file_name}}",
    "upload_type":"UPLOAD_BY_URL",
    "video_url":"{{video_url}}",
    "flaw_detect":true,
    "auto_fix_enabled":true,
    "auto_bind_enabled":true
}'
(/code)
```

## Response



```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log id of a request, which uniquely identifies the request.|
|data |object[]|Returned data. For compatibility reasons, an array, instead of an object, is returned, and the array contains only one object. <br><p><span style="color:darkred"><b>Note</b></span>: Due to the latency between client and server, it is possible that only `video_id` is returned. In this case, you can wait for 30 seconds to five minutes and use the [/file/video/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740050472224769) endpoint to retrieve other data.  |
#|video_cover_url |string|Temporary URL for video cover, valid for six hours and needs to be re-acquired after expiration. <br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds. <br>Example: `http://p16-sign-sg.tiktokcdn.com/v0201/b99a388e3709470be5c~tplv-noop.image?x-expires=<strong>1671742348</strong>&x-signature=FziJhvED9NDTDmPofv3I%3D`.|
#|format |string|Video format.  |
#|preview_url |string|Video preview link, valid for six hours and needs to be re-acquired after expiration. <br>To find out the expiration time of the preview link, see `preview_url_expire_time`.|
#|preview_url_expire_time|string|The expiration time of the video preview link, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0).|
#|file_name |string |Video name. |
#|displayable |boolean |Whether it can be displayed on the platform.|
#|height |number|Video height.  |
#|width |number|Video width.  |
#|bit_rate |number|Bit rate in bps.|
#|create_time |string |Creation time. UTC time. Format: 2020-06-10T07:39:14Z. |
#|modify_time |string |Modification time. UTC time. Format: 2020-06-10T07:39:14Z. |
#|signature |string|Video file MD5. |
#|duration |float|Video duration, in seconds.  |
#|video_id |string|Video ID, which can be used to create ads.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If you upload via video ID (`upload_type` as `UPLOAD_BY_VIDEO_ID`), the same video ID will be returned.</li><li>If you upload via file , URL or file ID (`upload_type` as `UPLOAD_BY_FILE`, `UPLOAD_BY_URL` or `UPLOAD_BY_FILE_ID`), a new video ID (`video_id`) will be returned. If you upload the same video multiple times using any of these three methods, you'll obtain a new video ID for each upload.</li></ul> |
#|size |number|Video size, in bytes. |
#|material_id|string|Material ID|
#|allowed_placements |string[] |Available placements. Due to music copyright, some materials generated by creative tools can only be shown on TikTok. It won't pass when they are created on other placements. For enum values, see [Enumerations-Ad Management-Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).<br><br><p><span style="color:darkred"><b>Note</b></span>: The values `PLACEMENT_TOPBUZZ` and `PLACEMENT_HELO` for this field cannot be used for ad creation and will be deprecated in the next API version.|
#|allow_download |boolean |Whether the video is downloadable. Due to the music copyright, some materials generated by creative tools are only allowed to preview. It is prohibited to download and disseminate them. |
#|fix_task_id|string|Returned only when you've set both `flaw_detect` and  `auto_fix_enabled` to `true` in request, and video issues are detected. <br><br>Fix task ID.  <br><br>To obtain the fixed video, you can use one of the following methods:<ul><li>Pass the value of this field to the `task_id` field in [/video/fix/task/get/](https://business-api.tiktok.com/portal/docs?id=1741469487859714).</li><li>Retrieve the fixed video from [/file/video/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740050472224769) if you have set `auto_bind_enabled` to `true` in the request.</li></ul>|
#|flaw_types|string[]|Returned only when you've set both `flaw_detect` and  `auto_fix_enabled` to `true` in request, and video issues are detected.<br><br>Video issue types. <br><br> Enum values: <ul><li>`LOW_RESOLUTION`: Video resolution is lower than 540x960 px, which doesn't meet our requirements.</li><li>`ILLEGAL_VIDEO_SIZE`: The video size is not correct. Use the standard video size: Square (1:1) / Vertical (9:16) / Horizontal (16:9). </li><li>`NO_BGM`(<span style="color:Red">deprecated</span>): The ad or video has no background audio, or the background audio is incoherent/unclear. </li><li>`BLACK_EDGE`(<span style="color:Red">deprecated</span>) :  A video image contains black bars, which affects user experience and is not allowed. </li><li>`ILLEGAL_DURATION`(<span style="color:Red">deprecated</span>): Video length is either longer than 60s or shorter than 5s, which doesn't meet our requirements.|
```


### Example
#### Upload by file with Smart Fix enabled and issue detected
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": [
        {
            "fix_task_id": "{{fix_task_id}}",
            "flaw_types": [
                "LOW_RESOLUTION"
            ]
        }
    ]
}
(/code)
```
#### Upload by file with Smart Fix disabled
> <span style="color:DodgerBlue">**Note**</span><br>
>Due to the latency between client and server, it is possible that only `video_id` is returned. In this case, you can wait for 30 seconds to five minutes and use the [/file/video/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740050472224769) endpoint to retrieve other data.

```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": [
        {
            "video_id": "{{video_id}}"
        }
    ]
}
(/code)
```
#### Upload by URL with Smart Fix enabled and issue detected
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": [
        {
            "fix_task_id": "{{fix_task_id}}",
            "flaw_types": [
                "ILLEGAL_VIDEO_SIZE"
            ]
        }
    ]
}
(/code)
```
