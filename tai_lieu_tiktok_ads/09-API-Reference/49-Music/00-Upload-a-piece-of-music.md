---
title: "Upload a piece of music"
doc_id: 1740052650395650
path: "API Reference / Music / Upload a piece of music"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload a piece of music

Use this endpoint to upload music and get the music ID, to add music to the Liked list or History list, or to remove music from the Liked list.

>  <span style="color:DarkRed">**Copyright Notice**</span><br>Please make sure you have the right to use the music. If you infringe the copyright of the author, all responsibilities will be carried by you.

 ## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/file/music/upload/|/v1.3/file/music/upload/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|New request parameters|/|`music_scene`<br>`material_action`<br>`material_id`|
|Response parameter deprecated in v1.3 |/|`name`: string|
|New response parameters|/|`author`<br>`liked`<br>`cover_url`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/file/music/upload/

**Method** POST

**Header**

```xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type. If `upload_type` is `UPLOAD_BY_FILE`, use `multipart/form-data`. If `upload_type` is `UPLOAD_BY_FILE_ID`, use `application/json`. |
```

**Parameters**

```xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
| music_scene | string | The scenario where the piece of music is used. <br>Enum values:   <ul><li>`CREATIVE_ASSET`: Creative Tools. </li><li>`CAROUSEL_ADS`: Non-Shopping Ads - Carousel format.</li><li>`CATALOG_CAROUSEL`: Shopping Ads (VSA) - Carousel format.</li></ul>Default value: `CREATIVE_ASSET`.   |
| upload_type {+Conditional} | string |  When `music_scene` is set to `CAROUSEL_ADS` or `CATALOG_CAROUSEL`, you need to pass in either `upload_type` or `material_action`. <br> When `music_scene` is set to `CREATIVE_ASSET` or not passed, this field is optional and defaults to `UPLOAD_BY_FILE`.<br><br> The way you upload your music.  <br> Enum values:  <ul><li>`UPLOAD_BY_FILE`: To upload a piece of music via file. </li><li> `UPLOAD_BY_FILE_ID`: To upload a piece of music via file ID. </li></ul>    |
| material_action {+Conditional} | string | When `music_scene` is set to `CAROUSEL_ADS` or `CATALOG_CAROUSEL`, you need to pass in either `upload_type` or `material_action`.  <br>Not supported when `music_scene` is set to `CREATIVE_ASSET` or not passed.  <br>The type of operation you want to perform on a piece of music. You need to pass in `material_id` as well.  <br> Enum values:  <ul><li>`ADD_TO_LIKED`: To add a piece of music to the Liked list.</li><li>`ADD_TO_HISTORY`: To add a piece of music to the History list. </li><li>`REMOVE_FROM_LIKED`: To remove a piece of music from the Liked list.  </li></ul> <p><span style="color:darkred"><b>Note</b></span>:  The Liked list of an ad account can have up to 1,000 pieces of music. When the limit is reached, you cannot set this field to `ADD_TO_LIKED` to add more music to the Liked list.   |
|music_file {+Conditional}|file|Required when `upload_type` is `UPLOAD_BY_FILE` or not specified.<br>Music file. <br><p><span style="color:darkred"><b>Note</b></span>: When `music_scene` is set to `CAROUSEL_ADS`  or `CATALOG_CAROUSEL`, the customized music file that you specify through this field should meet the following specifications requirements:<ul><li>File Type: MP3.</li><li>File Size: no greater than 10 MB.</li></ul> |
|music_signature {+Conditional}|string|Required when `upload_type` is `UPLOAD_BY_FILE` or not specified. <br> Music MD5 (used for server verification).|
|file_name |string |Valid only when `upload_type` is passed.  <br>Music name. <br>Length limit: 1-100 characters. <br>The default is the local file name.|
|file_id {+Conditional}| string | Required when `upload_type` is `UPLOAD_BY_FILE_ID`.<br> The `file_id` of the file that you want to upload. <br>This field is for files that are uploaded to the file repository. You can get `file_id` via the [Upload Files](https://ads.tiktok.com/marketing_api/docs?id=1737719988918274) endpoints.<br><p><span style="color:darkred"><b>Note</b></span>: When `music_scene` is set to `CAROUSEL_ADS` or `CATALOG_CAROUSEL`, the ID that you specify through this field should correspond to a customized music file that meets the following specifications requirements:<ul><li>File Type: MP3.</li><li>File Size: no greater than 10 MB.</li></ul>  |
| material_id {+Conditional} | string | Required when `music_scene` is set to `CAROUSEL_ADS`  or `CATALOG_CAROUSEL` and `material_action` is passed.<br>Not supported when `music_scene` is set to `CREATIVE_ASSET` or not passed.   <br><br>Material ID for the piece of music, which is used for adding music to the Liked list or History list, or for removing music from the Liked list.  <br>To obtain a valid material ID , you can:<ul><li>upload a piece of customized music for Carousel Ads through this endpoint, </li><li>OR retrieve the material IDs of music for Carousel Ads under an ad account via `material_id` returned by [/file/music/get/](https://ads.tiktok.com/marketing_api/docs?id=1740053909509122).</li></ul>|
```
### Example

```xcodeblock
(code curl http)
curl --location --request GET 'https://ads.tiktok.com/open_api/v1.3/file/music/upload/' \
--header 'Access-Token: 3redfsdqewrefdv' \
--header 'Content-Type: multipart/form-data' \
--form 'advertiser_id="9999999"' \
--form 'upload_type="UPLOAD_BY_FILE"' \
--form 'music_file=@"/home/Desktop/test.mp3"' \
--form 'music_signature="34retrgdsfdaadzx"' \
--form 'file_name="test_file"'
(/code)
```


## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data. |
#| music_id | string | Music ID. |
#| material_id | string | Material ID. |
#| sources | string[] | Music source. `USER` means the advertiser's uploaded music; `SYSTEM` means the system music. |
#| author | string | Returned only for music that can be used in Carousel Ads.  <br>Author of the piece of music.   |
#| liked | boolean | Returned only for music that can be used in Carousel Ads.  <br>Whether the piece of music is added to the Liked list.   |
#| cover_url | string | Returned only for music that can be used in Carousel Ads.  <br>URL of the cover for the piece of music.   |
#| url | string | Music link. Effective duration: 12h. |
#|duration {+ Conditional} | float | Music duration (in seconds). Only returned for system music. |
#| style {+ Conditional} | string | Music style. Only returned for system music.  |
#| signature | string | Music file MD5. |
#| file_name | string | File Name. |
#| copyright | string | Copyright information. Enum values: <ul><li>`MUSIC_FORBID_VIDEO_ALLOW` : Music is not allowed to download, but the generated video is allowed to download.</li><li>`MUSIC_FORBID_VIDEO_FORBID` : Both music and the generated video are not allowed to download. </li></ul>|
#| create_time {+ Conditional} | string | Creation time. Only returned for advertiser's music. UTC Time. Format: 2020-06-10 T07: 39: 14Z. |
#| modify_time {+ Conditional} | string | Modified time. Only returned for advertiser's music. Custom Soundtracks return this field, system music does not return this field. UTC Time. Format: 2020-06-10 T07: 39: 14Z. |
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code example python)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "copyright": "MUSIC_FORBID_VIDEO_ALLOW",
        "url": "https://p9-dy.byteimg.com/obj/iesmusic-cn-local/v1/m/42b00000ca0384107766",
        "file_name": "music_name_test",
        "music_id": "6836297245562994693",
        "material_id": "6836297245562994693",
        "sources": [
            "USER"
        ],
        "create_time": "2020-12-06T07:08:09Z",
        "modify_time": "2020-12-06T07:08:09Z",
        "signature": "e360fd0e868c3183a0da849d38726739"
    },
    "request_id": "2020120607080801011515319101A3194C"
}
(/code)
```

