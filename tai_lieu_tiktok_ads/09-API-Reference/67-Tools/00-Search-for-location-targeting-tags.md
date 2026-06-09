---
title: "Search for location targeting tags"
doc_id: 1761236883355649
path: "API Reference / Tools / Search for location targeting tags"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Search for location targeting tags

Use this endpoint to search for location targeting tags by keyword. 

You can search for two types of targeting tags that are used to target ad audiences: zip code IDs (currently only supported for the US) or postal code IDs (Canada, Brazil, Indonesia, Thailand, and Vietnam) and location IDs. Then to apply targeting tags to your ads, you can pass the returned zip code IDs or postal code IDs to `zipcode_ids` or location IDs to `location_ids` when calling [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114), [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745) or [/ad/audience_size/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1740302379236353).

Both [/tool/region/](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713) and `/tool/targeting/search/` can be used to get location IDs.

><span style="color:DodgerBlue">**Note**</span><br><ul><li><span style="color:red">This endpoint requires POST method, because for GET requests the length of URL will be limited.</span></li> <li>Targeting postal code areas in Brazil, Indonesia, Thailand, and Vietnam is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li> <li>Targeting the newly available countries or regions (Azerbaijan, Bulgaria, Bolivia, Cyprus, Algeria, Estonia, Croatia, Kenya, Sri Lanka, Lithuania, Latvia, Nigeria, Puerto Rico, Paraguay, Serbia, Slovenia, Slovakia) using the advertising objective `TRAFFIC`, `LEAD_GENERATION`, `APP_PROMOTION`, or `WEB_CONVERSIONS` and the TikTok placement are currently separate allowlist-only features. If you would like to access them, contact your TikTok representative. You need to apply for a separate allowlist for each newly available country or region.</li><li>Targeting the newly available countries or regions (Azerbaijan, Bulgaria, Bolivia, Cyprus, Algeria, Estonia, Croatia, Kenya, Sri Lanka, Lithuania, Latvia, Puerto Rico, Paraguay, Serbia, Slovenia, Slovakia) using the advertising objective `REACH`, `VIDEO_VIEWS`, or `ENGAGEMENT` and the TikTik placement are currently separate allowlist-only features. If you would like to access them, contact your TikTok representative. You need to apply for a separate allowlist for each newly available country or region. <ul><li> When you target these countries or regions using the advertising objective `REACH`, `VIDEO_VIEWS`, or `ENGAGEMENT`, pre-bid third-party Brand Safety solutions are not supported. Therefore, you cannot set `brand_safety_type` to `THIRD_PARTY`.</li></ul></li></ul>

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/targeting/search/

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
| objective_type {Required} | string | Campaign objective. <br>The allowed enum values: `REACH`, `TRAFFIC`, `VIDEO_VIEWS`, `LEAD_GENERATION`, `ENGAGEMENT`, `APP_PROMOTION`, `WEB_CONVERSIONS`, `PRODUCT_SALES`.<br>For descriptions of the objectives, see [Objectives](https://ads.tiktok.com/marketing_api/docs?id=1737585562434561). |
|promotion_type{+Conditional} |string|Promotion type and you can decide where you'd like to promote your products using this field. You need to specify the field when advertising objective (`objective_type`) for your campaign is NOT set as `REACH`, `VIDEO_VIEWS`, or `ENGAGEMENT`. <br>For enum values, see [Enumeration - Promotion Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
|placements {Required}|string[]|The apps where you want to deliver your ads.<br><br> For enum values, see [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).<br><br><p><span style="color:darkred"><b>Note</b></span>:  If you want to fuzzy search for a zip code ID to be used for zip code targeting or a postal code ID to be used for postal code targeting, the value of this field needs to include `PLACEMENT_TIKTOK`. |
| search_type {Required} | string | The type of search you want to perform. <br><br>Enum values: <ul><li>`FUZZY_SEARCH`: To fuzzy search for a single location ID, a zip code ID, or a postal code ID. <br> For this search type, you can only pass one keyword to `keywords` and a maximum of 100 results will be returned.</li><li>`BATCH_REGION_SEARCH`: To search for location IDs in batch. Fuzzy search is supported. <br>For this search type, you can pass up to 1,000 keywords to `keywords`, and for each keyword a maximum of 5 results ordered by relevance will be returned. </li><li>`BATCH_ZIPCODE_SEARCH`: To search for zip code IDs or postal code IDs in batch. Exact search is supported. <br>For this search type, you can pass up to 1,000 keywords to `keywords`, and for each keyword one result or no result will be returned.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Targeting postal code areas in Brazil, Indonesia, Thailand, and Vietnam is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li> The enum values `BATCH_ZIPCODE_SEARCH` and `BATCH_REGION_SEARCH` can only be used to search for IDs of zip codes (or postal codes) or locations. In contrast, you can use `FUZZY_SEARCH` to search for a single ID of a zip code (or postal code) or location. </li></ul> |
| keywords {Required} | string[] | The keywords that you use to search for targeting tags. <br><br>Length limit: <ul><li>1 when `search_type` is `FUZZY_SEARCH`.</li><li>1,000 when `search_type` is `BATCH_REGION_SEARCH` or `BATCH_ZIPCODE_SEARCH`.</li></ul> <br> If you set `search_type` as `BATCH_ZIPCODE_SEARCH`, you need to pass zip codes or postal codes as keywords because this search type only supports exact search.<ul><li>For US zip code targeting, provide the five-digit US zip codes as keywords. Example: `["10001","10002"]`.</li><li>For Canadian postal code targeting, provide the three-character Forward Sortation Area (FSA) portions of Canadian postal codes as keywords. Example: `["A0A","A0B"]`.</li><li>For Brazil postal code targeting, provide the first five digits of postal codes as keywords. Example: `["81480","18072"]`.</li><li>For Indonesia postal code targeting, provide the five-digit postal codes as keywords. Example: `["20371","12120"]`.</li><li>For Thailand postal code targeting, provide the five-digit postal codes as keywords. Example: `["30000","40000"]`. </li><li>For Vietnam postal code targeting, provide the first three digits of postal codes as keywords. Example: `["718","719"]`.</li></ul> |
| geo_types | string[] | The types of locations that you want to filter the results by. Enum values: `COUNTRY` (country or region), `PROVINCE` (province), `CITY` (city or county), `DISTRICT` (district or city), `DMA` (DMA), `ZIP_CODE` (zip code or postal code). <ul><li> When you specify `search_type` as `FUZZY_SEARCH`, the allowed enum values for this field are `ZIP_CODE`, `COUNTRY`, `PROVINCE`, `CITY`, `DISTRICT`, and `DMA`. </li><li> When you specify `search_type` as `BATCH_ZIPCODE_SEARCH`, the allowed enum value for this field is `ZIP_CODE`. </li><li> When you specify `search_type` as `BATCH_REGION_SEARCH`, the allowed enum values for this field are `COUNTRY`, `PROVINCE`, `CITY`, `DISTRICT`, and `DMA`.</li></ul> |
| region_codes {+Conditional} | string[] | The codes of targeted countries or regions that you want to filter the results by. <ul><li> When you specify `search_type` as `BATCH_ZIPCODE_SEARCH` or `BATCH_REGION_SEARCH`, this field is required and needs to be set as `US` (the US) , `CA` (Canada), `BR` (Brazil), `ID` (Indonesia), `TH` (Thailand), or `VN` (Vietnam).</li><li> When you specify `search_type` as `FUZZY_SEARCH`, this field is optional and you can pass any supported country or region code to this field. To find out the complete list of country or region codes, see [Location code](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010). </li></ul> |
| operating_system | string | Device operating system that you want to target. Enum values: `ANDROID`, `IOS`. |
|brand_safety_type|string|Brand safety type. Valid only when `placements` is set as `PLACEMENT_TIKTOK`. Default value: `NO_BRAND_SAFETY`. Enum values: <ul><li>`NO_BRAND_SAFETY`:  To not use any brand safety solution.  Full inventory, which means your ads may appear next to some content featuring mature themes. </li><li>`EXPANDED_INVENTORY`: Use TikTok's brand safety solution. Expanded inventory means that your ads will appear next to content where most inappropriate content has been removed, and that does not contain mature themes. In the next API version, `EXPANDED_INVENTORY` will replace `NO_BRAND_SAFETY` and will be the default brand safety option.</li><li>`STANDARD_INVENTORY`: Use TikTok's brand safety solution. Standard inventory means that ads will appear next to content that is appropriate for most brands. </li><li>`LIMITED_INVENTORY`: Use TikTok's brand safety solution. Limited inventory means that your ads will not appear next to content that contains mature themes.</li><li>`THIRD_PARTY`: Use a third-party brand safety solution. You also need to pass in a value to the `brand_safety_partner` field.</li></ul> To get the countries and regions that your ads can be deployed to based on your brand safety settings, use the [/tool/region/](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713) endpoint. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Pre-bid first-party Brand Safety solutions for `APP_PROMOTION`, `WEB_CONVERSIONS`, `TRAFFIC`,  `LEAD_GENERATION` objectives in Auction ads, and pre-bid third-party brand safety solutions are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.</li><li> See [Brand safety](https://ads.tiktok.com/marketing_api/docs?id=1739381752981505) to learn about the supported advertising objectives for pre-bid Brand Safety filtering. </li></ul>|
|brand_safety_partner{+Conditional}|string| Required only when `brand_safety_type` is `THIRD_PARTY`.<br>Brand safety partner. <br> Enum values: `IAS`, `OPEN_SLATE`(The partner is named <strong>DoubleVerify</strong> on TikTok Ads Manager because the partner has been acquired by DoubleVerify). <br><p><span style="color:darkred"><b>Note</b></span>: Pre-bid third-party brand safety solutions are currently allowlist-only features. If you would like to access them, please contact your TikTok representative. |
```

### Example

#### Search for a single Canadian postal code ID
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/tool/targeting/search/' \
--header 'Access-Token:  {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id":"{{advertiser_id}}",
    "objective_type":"{{objective_type}}",
    "placements":["PLACEMENT_TIKTOK"],
    "promotion_type":"{{promotion_type}}",
    "search_type":"FUZZY_SEARCH",
    "keywords":["A0A"],
    "region_codes":["CA"]
}'
(/code)
```

#### Search for a single US zip code ID
```xcodeblock
(code curl http)
curl --location "https://business-api.tiktok.com/open_api/v1.3/tool/targeting/search/" \
--header "Access-Token: {{Access-Token}}" \
--header "Content-Type: application/json" \
--data "{
    "advertiser_id":"{{advertiser_id}}",
    "objective_type":"{{objective_type}}",
    "placements":["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"],
    "promotion_type":"{{promotion_type}}",
    "search_type":"FUZZY_SEARCH",
    "keywords":["40601"]
}"
(/code)
```

#### Search for Brazil postal code IDs in batch
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tool/targeting/search/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id":"{{advertiser_id}}",
    "objective_type":"APP_PROMOTION",
    "placements":["PLACEMENT_TIKTOK"],
    "promotion_type":"APP_IOS",
    "search_type":"BATCH_ZIPCODE_SEARCH",
    "keywords":["81480","18072"],
    "region_codes":["BR"]
}'
(/code)
```

#### Search for location IDs in batch
```xcodeblock
(code curl http)
curl --location "https://business-api.tiktok.com/open_api/v1.3/tool/targeting/search/" \
--header "Access-Token: {{Access-Token}}" \
--header "Content-Type: application/json" \
--data "{
    "advertiser_id":"{{advertiser_id}}",
    "objective_type":"{{objective_type}}",
    "placements":["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"],
    "promotion_type":"{{promotion_type}}",
    "search_type":"BATCH_REGION_SEARCH",
    "keywords":["Kentucky","Virginia"],
    "region_codes":["US"]
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
#| targeting_tag_list | object[] | Information about the targeting tags that are matched by the specified keyword or keywords. <br><p><span style="color:darkred"><b>Note</b></span>: Multiple targeting tags of different names (`name`) may be returned for the same `keyword`. |
##| keyword | string | The keyword that is used to search for targeting tags. |
##| targeting_type | string | The targeting type that the targeting tag is used for. <br>Enum values: `GEO` (Geographical targeting, which consists of administrative region targeting and zip code targeting (or postal code targeting)). |
##| name | string | The name of the targeting tag. |
##| status_info | object | Information about the status of the targeting tag. |
###| status | string | The status of the targeting tag. <br>Enum values: `ENABLED` (can be used for targeting), `DISABLED` (cannot be used for targeting). |
###| reason | string | The reason why the targeting tag cannot be used for targeting. Returned when `status` = `DISABLED`. Enum values: <ul><li> `OFFLINE`: The targeting tag is offline. </li><li> `NOT_SUPPORTED`: The targeting tag is not offline but is not supported for the specified settings.</li></ul> |
##| geo | object | Information about the targeting tag that is used for location targeting. <br>Returned when `targeting_type` is `GEO`. |
###| geo_id | string | Location ID, zip code ID, or postal code ID. <br>Location IDs can be used for administrative region targeting while zip code IDs can be used for zip code targeting and postal code IDs can be used for postal code targeting. <ul><li> When the corresponding `geo_type` is `ZIP_CODE`, you can pass the value of this field as zip code ID or postal code ID to `zipcode_ids` when calling [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114), [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745) or [/ad/audience_size/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1740302379236353).</li><li> When the corresponding `geo_type` is not `ZIP_CODE`, you can pass the value of this field as location ID to `location_ids` when calling [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114), [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745) or [/ad/audience_size/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1740302379236353).</li></ul>|
###| geo_type | string | The type of location that the `geo_id` corresponds to. <br>Enum values: `COUNTRY` (country), `PROVINCE` (province), `CITY` (city or county), `DISTRICT` (district or city), `DMA` (DMA), `ZIP_CODE` (zip code or postal code).|
###| description | string | The description of the targeted location. |
###| region_code | string | The code of the country or region that the targeted location belongs to. |
###| parent_id | string | ID of the parent location. <br>Returns `0` if no parent location exists. |
#| parent_tags | object[] | Information about all the parent targeting tags. |
##| targeting_type | string | The targeting type that the parent targeting tag is used for. <br>Enum values: `GEO` (Geographical targeting, which consists of administrative region targeting and zip code targeting (or postal code targeting)). |
##| name | string | The name of the parent targeting tag. |
##| status_info | object | Information about the status of the parent targeting tag. |
###| status | string | The status of the parent targeting tag. <br>Enum values: `ENABLED` (can be used for targeting), `DISABLED` (cannot be used for targeting). |
###| reason | string | The reason why the parent targeting tag cannot be used for targeting. <br>Returned when `status` = `DISABLED`. <br>Enum values: <ul><li>`OFFLINE`: The parent targeting tag is offline.</li><li>`NOT_SUPPORTED`: The parent targeting tag is not offline but is not supported for the specified settings.</li></ul>  |
##| geo | object | Information about the parent targeting tag that is used for location targeting. <br>Returned when `targeting_type` is `GEO`. |
###| geo_id | string | Parent location ID, zip code ID, or postal code ID. <ul><li> When the corresponding `geo_type` is `ZIP_CODE`, you can pass the value of this field to `zipcode_ids` when calling [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114), [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745) or [/ad/audience_size/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1740302379236353).</li><li> When the corresponding `geo_type` is not `ZIP_CODE`, you can pass the value of this field to `location_ids` when calling [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114), [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745) or [/ad/audience_size/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1740302379236353).</li></ul>|
###| geo_type | string | The type of location that the `geo_id` corresponds to. <br>Enum values: `COUNTRY` (country), `PROVINCE` (province), `CITY` (city or county), `DISTRICT` (district or city), `DMA` (DMA), `ZIP_CODE` (zip code or postal code). |
###| description | string | The description of the parent location. |
###| region_code | string | The code of the country or region that the parent location belongs to. |
###| parent_id | string | ID of the parent location for the parent location. <br>Returns `0` if no parent location exists. |
```

### Example

#### Search for a single Canadian postal code ID
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
                    "reason": null,
                    "status": "ENABLED"
                },
                "geo": {
                    "geo_id": "6251999",
                    "geo_type": "COUNTRY",
                    "parent_id": "0",
                    "region_code": "CA",
                    "description": "Country"
                },
                "isp": null,
                "name": "Canada",
                "targeting_type": "GEO"
            },
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "geo": {
                    "geo_id": "6354959",
                    "geo_type": "PROVINCE",
                    "parent_id": "6251999",
                    "region_code": "CA",
                    "description": "province"
                },
                "isp": null,
                "name": "Newfoundland and Labrador",
                "targeting_type": "GEO"
            }
        ],
        "targeting_tag_list": [
            {
                "keyword": "A0A",
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "geo": {
                    "geo_id": "8124000000533",
                    "geo_type": "ZIP_CODE",
                    "parent_id": "6354959",
                    "region_code": "CA",
                    "description": "postcode"
                },
                "isp": null,
                "name": "A0A",
                "targeting_type": "GEO"
            }
        ]
    }
}
(/code)
```


#### Search for a single US zip code ID
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
                "geo": {
                    "description": "postcode",
                    "parent_id": "4292188",
                    "geo_type": "ZIP_CODE",
                    "region_code": "US",
                    "geo_id": "8840000440601"
                },
                "keyword": "40601",
                "name": "40601",
                "targeting_type": "GEO",
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                }
            }
        ],
        "parent_tags": [
            {
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "geo": {
                    "description": "State",
                    "parent_id": "6252001",
                    "geo_type": "PROVINCE",
                    "region_code": "US",
                    "geo_id": "6254925"
                },
                "targeting_type": "GEO",
                "name": "Kentucky"
            },
            {
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "geo": {
                    "description": "County",
                    "parent_id": "6254925",
                    "geo_type": "CITY",
                    "region_code": "US",
                    "geo_id": "4292202"
                },
                "targeting_type": "GEO",
                "name": "Franklin County"
            },
            {
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "geo": {
                    "description": "City",
                    "parent_id": "4292202",
                    "geo_type": "DISTRICT",
                    "region_code": "US",
                    "geo_id": "4292188"
                },
                "targeting_type": "GEO",
                "name": "Frankfort"
            },
            {
                "status_info": {
                    "status": "ENABLED",
                    "reason": null
                },
                "geo": {
                    "description": "state",
                    "parent_id": "0",
                    "geo_type": "COUNTRY",
                    "region_code": "US",
                    "geo_id": "6252001"
                },
                "targeting_type": "GEO",
                "name": "United States"
            }
        ]
    }
}
(/code)
```

#### Search for Brazil postal code IDs in batch
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
                    "reason": null,
                    "status": "ENABLED"
                },
                "isp": null,
                "name": "Parana",
                "geo": {
                    "geo_type": "PROVINCE",
                    "region_code": "BR",
                    "geo_id": "3455077",
                    "description": "state",
                    "parent_id": "3469034"
                },
                "targeting_type": "GEO"
            },
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "isp": null,
                "name": "Sao Paulo",
                "geo": {
                    "geo_type": "PROVINCE",
                    "region_code": "BR",
                    "geo_id": "3448433",
                    "description": "state",
                    "parent_id": "3469034"
                },
                "targeting_type": "GEO"
            },
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "isp": null,
                "name": "Brazil",
                "geo": {
                    "geo_type": "COUNTRY",
                    "region_code": "BR",
                    "geo_id": "3469034",
                    "description": "Country",
                    "parent_id": "0"
                },
                "targeting_type": "GEO"
            }
        ],
        "targeting_tag_list": [
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "isp": null,
                "name": "81480",
                "geo": {
                    "geo_type": "ZIP_CODE",
                    "region_code": "BR",
                    "geo_id": "8052000011871",
                    "description": "postcode",
                    "parent_id": "3455077"
                },
                "targeting_type": "GEO",
                "keyword": "81480"
            },
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "isp": null,
                "name": "18072",
                "geo": {
                    "geo_type": "ZIP_CODE",
                    "region_code": "BR",
                    "geo_id": "8052000002545",
                    "description": "postcode",
                    "parent_id": "3448433"
                },
                "targeting_type": "GEO",
                "keyword": "18072"
            }
        ]
    }
}
(/code)
```


#### Search for location IDs in batch
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
                "name": "Kentucky",
                "geo": {
                    "geo_type": "PROVINCE",
                    "description": "State",
                    "parent_id": "6252001",
                    "geo_id": "6254925",
                    "region_code": "US"
                },
                "keyword": "Kentucky",
                "targeting_type": "GEO"
            },
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "name": "Virginia Beach",
                "geo": {
                    "geo_type": "DISTRICT",
                    "description": "City",
                    "parent_id": "4791271",
                    "geo_id": "4791259",
                    "region_code": "US"
                },
                "keyword": "Virginia",
                "targeting_type": "GEO"
            },
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "name": "Virginia Beach",
                "geo": {
                    "geo_type": "CITY",
                    "description": "County",
                    "parent_id": "6254928",
                    "geo_id": "4791271",
                    "region_code": "US"
                },
                "keyword": "Virginia",
                "targeting_type": "GEO"
            },
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "name": "West Virginia",
                "geo": {
                    "geo_type": "PROVINCE",
                    "description": "State",
                    "parent_id": "6252001",
                    "geo_id": "4826850",
                    "region_code": "US"
                },
                "keyword": "Virginia",
                "targeting_type": "GEO"
            },
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "name": "Virginia",
                "geo": {
                    "geo_type": "PROVINCE",
                    "description": "State",
                    "parent_id": "6252001",
                    "geo_id": "6254928",
                    "region_code": "US"
                },
                "keyword": "Virginia",
                "targeting_type": "GEO"
            },
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "name": "Virginia",
                "geo": {
                    "geo_type": "DISTRICT",
                    "description": "City",
                    "parent_id": "5068853",
                    "geo_id": "5081185",
                    "region_code": "US"
                },
                "keyword": "Virginia",
                "targeting_type": "GEO"
            }
        ],
        "parent_tags": [
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "geo": {
                    "geo_type": "PROVINCE",
                    "description": "State",
                    "parent_id": "6252001",
                    "geo_id": "5073708",
                    "region_code": "US"
                },
                "targeting_type": "GEO",
                "name": "Nebraska"
            },
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "geo": {
                    "geo_type": "CITY",
                    "description": "County",
                    "parent_id": "5073708",
                    "geo_id": "5068853",
                    "region_code": "US"
                },
                "targeting_type": "GEO",
                "name": "Gage County"
            },
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "geo": {
                    "geo_type": "COUNTRY",
                    "description": "Country",
                    "parent_id": "0",
                    "geo_id": "6252001",
                    "region_code": "US"
                },
                "targeting_type": "GEO",
                "name": "United States"
            },
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "geo": {
                    "geo_type": "PROVINCE",
                    "description": "State",
                    "parent_id": "6252001",
                    "geo_id": "6254928",
                    "region_code": "US"
                },
                "targeting_type": "GEO",
                "name": "Virginia"
            },
            {
                "status_info": {
                    "reason": null,
                    "status": "ENABLED"
                },
                "geo": {
                    "geo_type": "CITY",
                    "description": "County",
                    "parent_id": "6254928",
                    "geo_id": "4791271",
                    "region_code": "US"
                },
                "targeting_type": "GEO",
                "name": "Virginia Beach"
            }
        ]
    }
}
(/code)
```




