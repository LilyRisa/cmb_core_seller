---
title: "Obtain details about location targeting tags by ID"
doc_id: 1761237001980929
path: "API Reference / Tools / Obtain details about location targeting tags by ID"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Obtain details about location targeting tags by ID

Use this endpoint to get information about location targeting tags by ID. 

You can get details about three types of targeting tags: zip code IDs  (currently only supported for the US) or postal code IDs (currently only supported for Canada, Brazil, Indonesia, Thailand, and Vietnam), location IDs, and ISP IDs.

 See the <strong>"Supported parameters for different types of targeting tag"  </strong> section below to learn about the request parameters you need to pass for different types of targeting tags.

><span style="color:DodgerBlue">**Note**</span><br><ul><li>You can only use this endpoint, rather than [/too/region/](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713),  to get the information about zip code IDs or postal code IDs.</li><li><span style="color:red">This endpoint requires POST method, because for GET requests the length of URL will be limited.</span></li></ul>

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/targeting/info/

**Method** POST

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
| advertiser_id {Required} | string | Advertiser ID. |
|scene|string|The targeting type that the specified `targeting_ids` is used for. <br>Enum values: <ul><li>`GEO`: Geographical targeting, which consists of administrative region targeting and zip code targeting (or postal code targeting).</li><li>`ISP` : Internet Service Provider targeting.</li></ul><br>Default value: `GEO`. |
|targeting_ids {Required}|string[]|Targeting tag IDs.<br>You can pass in location IDs, zip code IDs or postal code IDs, a combination of location IDs, zip code IDs or postal code IDs, or ISP IDs. <br>Max size: 1,000. <br>You can get location IDs, zip code IDs, or postal code IDs via `geo_id` returned from [/tool/targeting/search/](https://ads.tiktok.com/marketing_api/docs?id=1761236883355649), or get location IDs via `location_id` returned from [/tool/region/](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713).<br>You can get ISP IDs via `isp_id` returned from [/tool/targeting/list/](https://ads.tiktok.com/marketing_api/docs?id=1762962378261506).|
| objective_type{+Conditional}| string |Required when `scene` is set as `GEO`.<br> Campaign objective. <br>The allowed enum values: `REACH`, `TRAFFIC`, `VIDEO_VIEWS`, `LEAD_GENERATION`, `ENGAGEMENT`, `APP_PROMOTION`, `WEB_CONVERSIONS`, `PRODUCT_SALES`.<br>For descriptions of the objectives, see [Objectives](https://ads.tiktok.com/marketing_api/docs?id=1737585562434561). |
|promotion_type{+Conditional} |string|Required when `scene` is set as `GEO` and `objective_type` is NOT set as `REACH`, `VIDEO_VIEWS`, or `ENGAGEMENT`. <br>Promotion type and you can decide where you'd like to promote your products using this field. <br>For enum values, see [Enumeration - Promotion Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
|placements {+Conditional}|string[]|Required when `scene` is set as `GEO`.<br>The apps where you want to deliver your ads. For enum values, see [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).<br><p><span style="color:darkred"><b>Note</b></span>:  If you want to get information about zip code IDs to be used for zip code targeting or postal code IDs to be used for postal code targeting, the value of this field needs to include `PLACEMENT_TIKTOK`. |
| operating_system | string |Valid only when `scene` is set as `GEO`.<br> Device operating system that you want to target. Enum values: `ANDROID`, `IOS`. |
|brand_safety_type|string|Brand safety type. Valid only when `scene` is set as `GEO` and `placements` is set as `PLACEMENT_TIKTOK`. Default value: `NO_BRAND_SAFETY`. Enum values: <ul><li>`NO_BRAND_SAFETY`:  To not use any brand safety solution.  Full inventory, which means your ads may appear next to some content featuring mature themes. </li><li>`EXPANDED_INVENTORY`: Use TikTok's brand safety solution. Expanded inventory means that your ads will appear next to content where most inappropriate content has been removed, and that does not contain mature themes. In the next API version, `EXPANDED_INVENTORY` will replace `NO_BRAND_SAFETY` and will be the default brand safety option.</li><li>`STANDARD_INVENTORY`: Use TikTok's brand safety solution. Standard inventory means that ads will appear next to content that is appropriate for most brands. </li><li>`LIMITED_INVENTORY`: Use TikTok's brand safety solution. Limited inventory means that your ads will not appear next to content that contains mature themes.</li><li>`THIRD_PARTY`: Use a third-party brand safety solution. You also need to pass in a value to the `brand_safety_partner` field.</li></ul> To get the countries and regions that your ads can be deployed to based on your brand safety settings, use the [/tool/region/](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713) endpoint. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Pre-bid first-party Brand Safety solutions for `APP_PROMOTION`,  `WEB_CONVERSIONS`, `TRAFFIC`,  `LEAD_GENERATION` objectives in Auction ads, and pre-bid third-party brand safety solutions are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.</li><li> See [Brand safety](https://ads.tiktok.com/marketing_api/docs?id=1739381752981505) to learn about the supported advertising objectives for pre-bid Brand Safety filtering. </li></ul>|
|brand_safety_partner{+Conditional}|string| Required only when `brand_safety_type` is `THIRD_PARTY`.<br>Brand safety partner. <br> Enum values: `IAS`, `OPEN_SLATE`(The partner is named <strong>DoubleVerify</strong> on TikTok Ads Manager because the partner has been acquired by DoubleVerify). <br><p><span style="color:darkred"><b>Note</b></span>: Pre-bid third-party brand safety solutions are currently allowlist-only features. If you would like to access them, please contact your TikTok representative. |
```
### Supported parameters for different types of targeting tag
 See the list below to learn about the supported parameters for different types of targeting tag. The parameters can be required (✅), optional (✔) or unsupported (❌) for the type of targeting tag.
```xtable
| Field{25%} | Zip Code ID{25%} | Location ID {25%}| ISP ID{25%} |
|---|---|---|---|
| `advertiser_id` | ✅ | ✅ | ✅ |
| `scene` | ✅ <br>Must be set to `GEO`.| ✅ <br> Must be set to `GEO`. | ✅ <br>Must be set to `ISP`.|
| `targeting_ids` | ✅ | ✅ | ✅ |
| `objective_type` | ✅ | ✅ | ❌ |
| `promotion_type` | ✔ | ✔ | ❌ |
| `placements` | ✅ | ✅ | ❌ |
| `operating_system` | ✔ | ✔ | ❌ |
| `brand_safety_type` | ✔ | ✔ | ❌ |
| `brand_safety_partner` | ✔ | ✔ | ❌ |
```

### Example

#### Obtain info about zip code IDs
```xcodeblock
(code curl http)
curl --location "https://business-api.tiktok.com/open_api/v1.3/tool/targeting//info/" \
--header "Access-Token: {{Access-Token}}" \
--header "Content-Type: application/json" \
--data "{
    "advertiser_id":"{{advertiser_id}}",
    "objective_type":"{{objective_type}}",
    "promotion_type":"{{promotion_type}}",
    "placements":["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"],
    "targeting_ids":["8840000454548","8840000454525"]
}"
(/code)
```
#### Obtain info about ISP IDs
```xcodeblock
(code curl http)
curl --location "https://business-api.tiktok.com/open_api/v1.3/tool/targeting//info/" 
--header "Access-Token: {{Access-Token}}" 
--header "Content-Type: application/json" 
--data "{
    "advertiser_id":"{{advertiser_id}}",
    "scene":"ISP",
    "targeting_ids":["EG00045"]
}"
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
#| targeting_tag_list | object[] | Information about the targeting tags that are matched by the specified targeting tag IDs.|
##| targeting_type | string | The targeting type that the targeting tag is used for. <br>Enum values: <ul><li>`GEO`: Geographical targeting, which consists of administrative region targeting and zip code targeting (or postal code targeting).</li><li>`ISP`: Internet Service Provider targeting.</li></ul> |
##| name | string | The name of the targeting tag. |
##| status_info | object | Information about the status of the targeting tag. |
###| status | string | The status of the targeting tag. <br>Enum values: `ENABLED` (can be used for targeting), `DISABLED`(cannot be used for targeting). |
###| reason | string | The reason why the targeting tag cannot be used for targeting. Returned when `status` = `DISABLED`. Enum values: <ul><li> `OFFLINE`: The targeting tag is offline. </li><li> `NOT_SUPPORTED`: The targeting tag is not offline but is not supported for the specified settings.</li></ul> |
##| geo | object | Information about the targeting tag that is used for location targeting. <br>Returned when `targeting_type` is `GEO`. |
###| geo_id | string | Location ID, zip code ID, or postal code ID. <br>Location IDs can be used for administrative region targeting while zip code IDs can be used for zip code targeting and postal code IDs can be used for postal code targeting. <ul><li> When the corresponding `geo_type` is `ZIP_CODE`, you can pass the value of this field as zip code ID or postal code ID to `zipcode_ids` when calling [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114), [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745) or [/ad/audience_size/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1740302379236353).</li><li> When the corresponding `geo_type` is not `ZIP_CODE`, you can pass the value of this field as location ID to `location_ids` when calling [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114), [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745) or [/ad/audience_size/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1740302379236353).</li></ul>|
###| geo_type | string | The type of location that the `geo_id` corresponds to. <br>Enum values: `COUNTRY` (country), `PROVINCE` (province), `CITY` (city), `DISTRICT` (district), `DMA` (DMA), `ZIP_CODE` (zip code or postal code).|
###| description | string | The description of the targeted location. |
###| region_code | string | The code of the country or region that the targeted location belongs to. |
###| parent_id | string | ID of the parent location. <br>Returns `0` if no parent location exists. |
##| isp | object | Information about the targeting tag that is used for Internet service provider targeting. <br>Returned when `targeting_type` is `ISP`. |
###| isp_id | string | The ID of the Internet service provider that you can target. |
###| location_id | string | The location ID that the `isp_id` can be used together with. Only location IDs at the country or region level are supported. |
###| region_code | string | The code of the region or country that the `isp_id` belongs to. |
#| parent_tags | object[] | Information about all the parent targeting tags. |
##| targeting_type | string | The targeting type that the parent targeting tag is used for. <br>Enum values: `GEO` (Geographical targeting, which consists of administrative region targeting and zip code targeting (or postal code targeting)). |
##| name | string | The name of the parent targeting tag. |
##| status_info | object | Information about the status of the parent targeting tag. |
###| status | string | The status of the parent targeting tag. <br>Enum values: `ENABLED` (can be used for targeting), `DISABLED` (cannot be used for targeting). |
###| reason | string | The reason why the parent targeting tag cannot be used for targeting. <br>Returned when `status` = `DISABLED`. <br>Enum values: <ul><li>`OFFLINE`: The parent targeting tag is offline.</li><li>`NOT_SUPPORTED`: The parent targeting tag is not offline but is not supported for the specified settings.</li></ul>  |
##| geo | object | Information about the parent targeting tag that is used for location targeting. <br>Returned when `targeting_type` is `GEO`. |
###| geo_id | string | Parent location ID, zip code ID, or postal code ID. <ul><li> When the corresponding `geo_type` is `ZIP_CODE`, you can pass the value of this field to `zipcode_ids` when calling [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114), [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745) or [/ad/audience_size/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1740302379236353).</li><li> When the corresponding `geo_type` is not `ZIP_CODE`, you can pass the value of this field to `location_ids` when calling [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114), [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745) or [/ad/audience_size/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1740302379236353).</li></ul>|
###| geo_type | string | The type of location that the `geo_id` corresponds to. <br>Enum values: `COUNTRY` (country), `PROVINCE` (province), `CITY` (city), `DISTRICT` (district), `DMA` (DMA), `ZIP_CODE` (zip code or postal code). |
###| description | string | The description of the parent location. |
###| region_code | string | The code of the country or region that the parent location belongs to. |
###| parent_id | string | ID of the parent location for the parent location. <br>Returns `0` if no parent location exists. |
```

### Example

#### Obtain info about zip code IDs
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "parent_tags": [
            {
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "name": "United States",
                "geo": {
                    "region_code": "US",
                    "geo_type": "COUNTRY",
                    "description": "state",
                    "parent_id": "0",
                    "geo_id": "6252001"
                },
                "targeting_type": "GEO"
            },
            {
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "name": "Wisconsin",
                "geo": {
                    "region_code": "US",
                    "geo_type": "PROVINCE",
                    "description": "State",
                    "parent_id": "6252001",
                    "geo_id": "5279468"
                },
                "targeting_type": "GEO"
            },
            {
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "name": "Oneida County",
                "geo": {
                    "region_code": "US",
                    "geo_type": "CITY",
                    "description": "County",
                    "parent_id": "5279468",
                    "geo_id": "5265726"
                },
                "targeting_type": "GEO"
            },
            {
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "name": "Minocqua",
                "geo": {
                    "region_code": "US",
                    "geo_type": "DISTRICT",
                    "description": "City",
                    "parent_id": "5265726",
                    "geo_id": "5263156"
                },
                "targeting_type": "GEO"
            },
            {
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "name": "Iron County",
                "geo": {
                    "region_code": "US",
                    "geo_type": "CITY",
                    "description": "County",
                    "parent_id": "5279468",
                    "geo_id": "5257533"
                },
                "targeting_type": "GEO"
            },
            {
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "name": "Gile",
                "geo": {
                    "region_code": "US",
                    "geo_type": "DISTRICT",
                    "description": "City",
                    "parent_id": "5257533",
                    "geo_id": "116027566"
                },
                "targeting_type": "GEO"
            }
        ],
        "targeting_tag_list": [
            {
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "name": "54548",
                "geo": {
                    "region_code": "US",
                    "geo_type": "ZIP_CODE",
                    "description": "postcode",
                    "parent_id": "5263156",
                    "geo_id": "8840000454548"
                },
                "targeting_type": "GEO"
            },
            {
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "name": "54525",
                "geo": {
                    "region_code": "US",
                    "geo_type": "ZIP_CODE",
                    "description": "postcode",
                    "parent_id": "116027566",
                    "geo_id": "8840000454525"
                },
                "targeting_type": "GEO"
            }
        ]
    }
}
(/code)
```


#### Obtain info about ISP IDs
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "targeting_tag_list": [
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "targeting_type": "ISP",
                "geo": null,
                "isp": {
                    "region_code": "EG",
                    "isp_id": "EG00045",
                    "location_id": "357994"
                },
                "name": "Etisalat Misr"
            }
        ],
        "parent_tags": []
    }
}
(/code)
```

