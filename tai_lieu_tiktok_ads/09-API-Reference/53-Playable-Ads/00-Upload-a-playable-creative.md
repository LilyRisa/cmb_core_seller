---
title: "Upload a playable creative"
doc_id: 1737730926765057
path: "API Reference / Playable Ads / Upload a playable creative"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload a playable creative

Use this endpoint to upload a playable creative.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/playable/upload/|/v1.3/playable/upload/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Response parameter data type|`playable_id`: number|`playable_id`: string|
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/playable/upload/

**Method** POST

**Header**
``` xtable
| Field | Type | Description |
|---|---|---|
| Access-Token {Required} | string | Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
| Content-Type {Required} | string | Request message type <br> Allowable value:`"multipart/form-data"` |
```
**Parameters**
``` xtable
| Field | Type | Description |
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID |
| upload_type | string | The way you upload your playable ads. Default value: `UPLOAD_BY_FILE`. Enum values: `UPLOAD_BY_FILE`, `UPLOAD_BY_FILE_ID`.|
| playable_package {+Conditional} | file | Playable Ad asset package <br> Format description: <br data-to-mark-pass> 1. The format is ZIP, and the size is no more than 5MB <br> 2. PlayableJS-SDK has been accessed, and the calling method is `window.openAppStore();`<br> 3. The main HTML file needs to be named `index.html` and located in the primary directory <br> 4. The Playable Ad playback orientation field should be stored in the `config.json` file, located in the first directory, the value is 0 (`BOTH`), 1 (`PORTRAIT`), 2 (`LANDSCAPE`) <br> 5. File names only support uppercase and lowercase letters, numbers, minus signs and underscores <br> 6. The `mraid.js` format is not allowed in the package. <br>7. The package is not allowed to load dynamic material over an external network <br> 8. JS redirection is not allowed in the asset package. <br>9. The asset file does not allow an HTTP request <br> 10. Files with empty contents are not allowed in the asset package |
|file_id{+conditional}| string | The `file_id` of the file that you want to upload. This field is for files that are uploaded to the file repository. You can get `file_id` via the [Upload Files](https://ads.tiktok.com/marketing_api/docs?id=1737719988918274) endpoint. This field is required when `upload_type` is `UPLOAD_BY_FILE_ID`.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/playable/upload/' \
--header "Access-Token:xxx" \
--form 'advertiser_id=ADVERTISER_ID' \
--form 'playable_package=@PLAYABLE_PACKAGE_PATH'
(/code)
```

## Response
``` xtable
| Field | Type | Description |
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return message. For details, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data|
# | playable_id | string | Playable creative ID. |
| request_id | string | The log id of a request, which uniquely identifies the request.|
```
### Example
``` xcodeblock
(code Success-Response http)
HTTPS / 1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "playable_id": "6765"
    }
}
(/code);
```
