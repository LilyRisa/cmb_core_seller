---
title: "Get internet service providers"
doc_id: 1762962378261506
path: "API Reference / Tools / Get internet service providers"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get internet service providers

Use this endpoint to get targeting tags. 

You can get one single type of targeting tag that is used to target ad audiences: ISP IDs. Then to apply ISP IDs to your ads, you can pass the returned ISP IDs to `isp_ids`  when calling  [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114), [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745) or [/ad/audience_size/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1740302379236353).

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/targeting/list/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id{Required} | string | Advertiser ID. |
| location_ids {Required} | string[] | IDs of the locations that you want to get the supported `ISP` IDs for. <br>Max size: 1000. <br>To find out the list of location IDs, see [Location IDs](https://ads.tiktok.com/marketing_api/docs?id=1739311040498689).<br><p><span style="color:darkred"><b>Note</b></span>: You need to pass location IDs at the country or region level. To get the available locations and corresponding IDs based on your placement and objective, use the [/tool/region/](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713) and the returned `level` for location IDs at the country or region level will be `COUNTRY`. |
| scene {Required} | string | The targeting type that the targeting tags are used for. <br>The allowed enum values: `ISP` ( Internet Service Provider targeting). |
```
### Example
```xcodeblock
(code curl http)
curl --location "https://business-api.tiktok.com/open_api/v1.3/tool/targeting/list/?advertiser_id={{advertiser_id}}&location_ids=["357994"]&scene=ISP" 
--header "Access-Token: {{Access-Token}}" 
--header "Content-Type: application/json" 
(/code)
```


## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|targeting_tag_list| object[] | Information about the targeting tags. |
##|targeting_type| string | The targeting type that the targeting tag is used for. <br>Enum values: `ISP` ( Internet Service Provider targeting). |
##| name | string | The name of the targeting tag. |
##| status_info | object | Information about the status of the targeting tag. |
###| status | string | The status of the targeting tag. <br>Enum values: `ENABLED` (can be used for targeting), `DISABLED`(cannot be used for targeting). |
###| reason | string | The reason why the targeting tag cannot be used for targeting. <br>Returned when `status` = `DISABLED`. <br>Enum values: <ul><li>`OFFLINE`: The targeting tag is offline. </li><li>`NOT_SUPPORTED`: The targeting tag is not offline but is not supported for the specified settings. </li></ul>|
##| isp | object | Information about the targeting tag that is used for Internet service provider targeting. |
###| isp_id | string | The ID of the Internet service provider that you can target. |
###| location_id | string | The location ID that the `isp_id` can be used together with. <br>Only location IDs at the country or region level are supported. |
###| region_code | string | The code of the region or country that the `isp_id` belongs to. |
##| geo | object | Information about the targeting tag that is used for location targeting. |
#| parent_tags | object[] | Information about all the parent targeting tags. |
```
### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "parent_tags": [],
        "targeting_tag_list": [
            {
                "name": "Etisalat Misr",
                "targeting_type": "ISP",
                "geo": null,
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "isp": {
                    "isp_id": "EG00045",
                    "location_id": "357994",
                    "region_code": "EG"
                }
            },
            {
                "name": "Orange Egypt",
                "targeting_type": "ISP",
                "geo": null,
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "isp": {
                    "isp_id": "EG00081",
                    "location_id": "357994",
                    "region_code": "EG"
                }
            },
            {
                "name": "Telecom Egypt",
                "targeting_type": "ISP",
                "geo": null,
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "isp": {
                    "isp_id": "EG00098",
                    "location_id": "357994",
                    "region_code": "EG"
                }
            },
            {
                "name": "Vodafone Egypt",
                "targeting_type": "ISP",
                "geo": null,
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "isp": {
                    "isp_id": "EG00105",
                    "location_id": "357994",
                    "region_code": "EG"
                }
            },
            {
                "name": "other",
                "targeting_type": "ISP",
                "geo": null,
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "isp": {
                    "isp_id": "EG0",
                    "location_id": "357994",
                    "region_code": "EG"
                }
            }
        ]
    }
}
(/code)
```

