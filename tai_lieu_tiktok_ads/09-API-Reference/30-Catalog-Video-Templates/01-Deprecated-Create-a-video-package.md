---
title: "(Deprecated) Create a video package"
doc_id: 1740665273039873
path: "API Reference / Catalog Video Templates / (Deprecated) Create a video package"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Create a video package

Use this endpoint to create a video package.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{28%}|v1.2{36%}|v1.3{36%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/video_package/create/| /v1.3/catalog/video_package/create/|
|Request parameter name |`name`|`video_package_name`|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number|`bc_id`: string<br>`catalog_id`: string|
|Response parameter name |`dpa_video_package_id`|`shopping_ads_video_package_id`|
```


## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/catalog/video_package/create/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request content type. <br>Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|bc_id {required}|string| Business Center ID. |
|catalog_id {required}|string| Catalog ID.|
|video_package_type {required}|string| Video package type. Currently, only `GREEN_SCREEEN` is supported.|
|videos {+conditional}|object[]| List of videos that will be used to create the video pakcage. This field is required when `video_package_type` is `GREEEN_SCREEN`.|
#|video_id {+conditional}|string|Video ID. This field is required when `video_package_type` is `GREEEN_SCREEN`.|
#|is_green_screen_video {+conditional}|boolean|Whether this is a green screen video. In the list of videos, you need to and can only have one green screen video. This field is required when `video_package_type` is `GREEEN_SCREEN`.|
|music_id {+conditional}| string|Music ID. This field is required when `video_package_type` is `GREEEN_SCREEN`.|
|template_id {+conditional}|string|Template ID. This field is required when when `video_package_type` is `GREEEN_SCREEN`|
|video_package_name|string|Name of the video package that you are about to create. If not specified, a name will be automatically generated in the format of `[VIDEO_TYPE]_[CREATE_TIME]`. For example, GreenScreenVideo_20210720_17:20. UTC+0 time will be used in generating video package names.|
```

### Example

```xcodeblock
(code cURL cURL)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/video_package/create/' \
--header 'Access-Token: xxxx \
--header 'Content-Type: application/json' \
--data-raw '{
    "bc_id": 6725320885500118022,
    "catalog_id": 6986901069595002626,
    "video_package_type": "GREEN_SCREEN",
    "videos": [
        {"video_id": "v07033g50000c38pjfoupb082t6ln5f0", "is_green_screen_video": false},
        {"video_id": "v10033g50000c4574b3c77uaa2tq5100", "is_green_screen_video": true},
        {"video_id": "v07033g50000c23ptb2h6o7cqvaadut0", "is_green_screen_video": false}  
    ],
    "template_id": "6992826957676134402",
    "video_package_name": "green screen generation",
    "music_id": "6904951200983285762"
}'
(/code)
```

## Response

```xtable
|Field{40%}|Data Type{15%}|Description{45%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| The log id of a request, which uniquely identifies the request.|
|data|object| Returned data.|
#|shopping_ads_video_package_id|string| ID of the video package that has just been created.|
```
### Example
```xcodeblock
(code JSON JSON)
{
    "code": 0,
    "message": "OK",
    "request_id": "20210810121500010244053018340BC3B3",
    "data": {
        "shopping_ads_video_package_id": "4c703748-e408-4364-9798-44970f85be23"
    }
}
(/code)
```

