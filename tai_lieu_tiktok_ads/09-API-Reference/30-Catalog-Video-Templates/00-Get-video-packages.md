---
title: "Get video packages"
doc_id: 1740574099715073
path: "API Reference / Catalog Video Templates / Get video packages"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get video packages

Use this endpoint to get information about all catalog video packages, or a particular video package, under your Business Center. 

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/catalog/video/get/|/v1.3/catalog/video_package/get/|
|Request parameter name |`dpa_video_package_id`|`shopping_ads_video_package_id`|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number|`bc_id`: string<br>`catalog_id`: string|
|Response parameter name |`package_id`<br>`name`<br> `audit_object`| `shopping_ads_video_package_id` <br>`video_package_name`<br>`rejected_object` |
|Response parameter data type|`catalog_id`: number<br>`id`: number|`catalog_id`: string<br>`id` is deleted in v1.3|
```

## Request

**Endpoint** 
https://business-api.tiktok.com/open_api/v1.3/catalog/video_package/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

``` xtable
| Field | Type | Description |
|--- |--- |--- |
| bc_id {Required} | string | Business Center ID. |
| catalog_id  {Required} | string | Catalog ID. |
| shopping_ads_video_package_id|string|Catalog video package ID. |
```

### Example

```xcodeblock
(code curl http)
curl -X GET -H "Access-Token:YOUR_ACCESS_TOKEN" \
--data-urlencode "bc_id=BC_ID" \
--data-urlencode "catalog_id=CATALOG_ID" \
--data-urlencode "shopping_ads_video_package_id=VIDEO_ID" \

https://business-api.tiktok.com/open_api/v1.3/catalog/video_package/get/
(/code)

```

## Response

```xtable
| Field | Type | Description |
|--- |--- |--- |
| code | number  |Return code, see [Appendix - Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message, see [Appendix-Return message](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|request_id | string | The log id of a request, which uniquely identifies the request. |
|data | object []| Returned data.|
#|shopping_ads_video_package_id | string | Catalog video package ID. |
#|catalog_id | string | Catalog ID. |
#|video_package_name | string | Catalog video package name. |
#|create_time | string | Creation time. |
#|update_time | string | Update time. |
#|status|string| If the video package is available for use. Enum values: `AVAILABLE`, `UNAVAILABLE`.|
#|video_package_type|string| Video package type. Enum values: `GREEN_SCREEN`, `TEMPLATE`, `DVG`.|
#|audit|object| Audit information.|
##|audit_status|string|Audit status. Enum values: `PROCESSING`, `APPROVED`, `REJECTED`.|
##|reject_info|object[]|Rejection information. Returned only when `audit_status` is `REJECTED`.|
###|rejected_object|string| Object that was rejected.|
###|reason|string| Reason for the rejection.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "list": [
            {
                "audit": {
                    "audit_status": "PROCESSING"
                },
                "status": "AVAILABLE",
                "update_time": "2021-08-10 09:53:59",
                "video_package_name": "green screen generation",
                "shopping_ads_video_package_id": "21f62362-6afe-4d89-a83e-0f9713833dfa",
                "video_package_type": "GREEN_SCREEN",
                "create_time": "2021-08-10 09:42:09",
                "catalog_id": "6986901069595002626"
            },
            {
                "audit": {
                    "audit_status": "PROCESSING"
                },
                "status": "UNAVAILABLE",
                "update_time": "2021-08-06 09:00:43",
                "video_package_name": "GreenScreen_20210806_09:00",
                "shopping_ads_video_package_id": "25071f29-5d25-4ebe-8a50-f282e0847a87",
                "video_package_type": "GREEN_SCREEN",
                "create_time": "2021-08-06 09:00:43",
                "catalog_id": "6986901069595002626"
            },
            {
                "audit": {
                    "audit_status": "REJECTED",
                    "reject_info": [
                        {
                            "rejected_object": "Creative Video",
                            "reason": "The ad or video has no background audio or the background audio is incoherent/unclear."
                        }
                    ]
                },
                "status": "AVAILABLE",
                "update_time": "2021-08-07 00:42:06",
                "video_package_name": "GreenScreen_20210806_08:59",
                "shopping_ads_video_package_id": "ec23aa27-1ddf-497a-9777-05de58b1cc40",
                "video_package_type": "GREEN_SCREEN",
                "create_time": "2021-08-06 08:59:41",
                "catalog_id": "6986901069595002626"
            },
            ...
        ]
    },
    "request_id": "2021081012162001024506219218036157"
}
(/code);
```
