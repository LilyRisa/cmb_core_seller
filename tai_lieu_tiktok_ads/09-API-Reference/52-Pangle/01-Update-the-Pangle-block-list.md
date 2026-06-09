---
title: "Update the Pangle block list"
doc_id: 1740040139110466
path: "API Reference / Pangle / Update the Pangle block list"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update the Pangle block list

Use this endpoint to update the Pangle block list.
      
## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/pangle_block_list/update/|/v1.3/pangle_block_list/update/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
```
## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/pangle_block_list/update/

**Method** POST

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type<br>Allowed format: `"application/json"`.|
```

**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|add_app_list |string[]| Apps to add in the block list. iTunes ID for iOS app and package name for Android apps. |
|delete_app_list |string[]| Apps to delete in the block list. iTunes ID for iOS app and package name for Android apps. |
|clear_old_app |boolean| Whether to clear the original App block list, default value: `False`. If `False`, the corresponding App will be deleted and added based on the original list; if `True`, the original list will be cleared and the corresponding App will be added. |
```

### Example
```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID", 
    "clear_old_app": "CLEAR_OLD_APP", 
    "add_app_list": [
        "ADD_APP_LIST"
    ], 
    "delete_app_list": [
        "DELETE_APP_LIST"
    ]
}' \
https://ads.tiktok.com/open_api/v1.3/pangle_block_list/update/
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return messages. For details, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|Returned data.  |
#|success_count|number|The number of apps that have been sucessfully added to the block list. The success count will not include deletion, regardless of whether the deletion is successful or not. | 
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
        "success_count": 2
    },
    "request_id": "2020082611320501023125009013028"
}
(/code);
```
