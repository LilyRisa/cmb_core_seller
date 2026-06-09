---
title: "(Deprecated) Submit materials"
doc_id: 1740317263937538
path: "API Reference / (Deprecated) Dynamic Scene / (Deprecated) Submit materials"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Submit materials

Use this endpoint to submit materials for dynamic scene creation.

After you submit the materials, use [/dynamic_scene/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740317320231938) to create an asynchronous task.
 > <span style="color: dodgerblue">**Note**</span><br>The Dynamic Scene API is now in beta. If you would like access to this feature, please apply for the allowlisting using the [Allowlist Management](https://ads.tiktok.com/marketing_api/blog/detail/7102266224578396162) page. 

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/dynamic_scene/material/submit/| /v1.3/dynamic_scene/material/submit/|
|Request parameter data type |`advertiser_id`: number |`advertiser_id`: string |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/dynamic_scene/material/submit/

**Method** POST

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
#|slots {Required}|object[]|Max size is 6.|
##|order {Required}|number|The order of slot in the story arc, ranging from 0 to 5.|
##|tag {Required}|string|The tag of a slot.|
##|video_ids {Required}|string[]|You can get video IDs through [Video Upload](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449) endpoint. Note: You can only pass in no more than 10 videos in this field. |
##|mute|boolean|Whether to turn on the original soundtrack of a slot. The default value is `false`. If you set it to `true`, then `bgm_volume` is `100`. <br> Note: In the case of no background music (`auto_soundtrack` is `false` and there is no `music_ids`), you are not allowed to set `mute` to `true`. |
##|bgm_volume|number|The volume of background music, should be an integer, ranging from [0, 100].|
|auto_soundtrack|boolean|Whether to use automatic soundtrack provided by the system. If you want auto soundtrack, set it equals to `true`. The default value is `false`.|
|music_ids|string[]|Music materials. You can get music IDs through [Music Upload](https://ads.tiktok.com/marketing_api/docs?id=1740052650395650) endpoint. |
```

### Example
``` xcodeblock
(code curl http)
{
    "slots": [{
        "order": 0,
        "tag": "asd",
        "video_ids": ["32wdsd3232784yjfvh343dsf"],
        "mute": false,
        "bgm_volume: 30
    }],
    "auto_soundtrack": false,
    "advertiser_id": 75960823018
}
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|material_package_id|string|Material package ID.|
|request_id |string|The log id of the request, which uniquely identifies a request.  |
```
