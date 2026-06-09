---
title: "Create a test lead"
doc_id: 1739047132614722
path: "API Reference / Leads / Create a test lead"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a test lead

Use this endpoint to perform end-to-end testing for lead generation.

For each Instant Form, you can create one test lead. If you need to create a new test lead, you must delete the existing test lead using [/page/lead/mock/delete/](https://business-api.tiktok.com/portal/docs?id=1739049313198081) first. If you want to fetch the Instant Form fields, you can use [/page/field/get/](https://ads.tiktok.com/marketing_api/docs?id=1739054080455681) to get field info directly. 



## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/pages/leads/mock/create/|/v1.3/page/lead/mock/create/|
|Request parameter data type |`advertiser_id`:number<br>`library_id `:number<br>`page_id`:number|`advertiser_id`: string<br>`library_id `:string<br>`page_id`:string|
|Response parameter data type|`lead_id`:number<br>`page_id`: number<br>`campaign_id`: number<br>`adgroup_id`:number<br>`ad_id`:number<br>`create_time`:number|`lead_id`:string<br>`page_id`: string<br>`campaign_id`: string<br>`adgroup_id`:string<br>`ad_id`:string<br>`create_time`:datetime|
```


## Request
**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/page/lead/mock/create/

**Method** POST

**Header**
```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string| The content type of the request. For this endpoint, use `application/json`.|
```

**Parameters**
```xtable
| Field | Type | Description |
|---|---|---|
| lead_source | string | The source of the lead. <br><br>Enum values:<ul><li>`INSTANT_FORM`: a lead generated through an Instant Form.</li><li>`DIRECT_MESSAGE`: a lead generated from a direct message of the associated Business Account.</li></ul><br>Default value: `INSTANT_FORM`. |
|advertiser_id {+Conditional}|string| Required when you want to create a test lead under an ad account.<br><br>Advertiser ID.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you pass in this field, you need to be an Admin of the ad account. To obtain the list of ad accounts that you have Admin access to, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401). Set `asset_type` to `ADVERTISER` and select ad accounts with the returned `advertiser_role` as `ADMIN`. |
|library_id {+Conditional}|string|Required when you want to create a test lead under a form library of a Business Center.<br><br>ID of a form library.<br><br>To obtain the list of form libraries within a Business Center, use [/page/library/get/](https://ads.tiktok.com/marketing_api/docs?id=1740029915327489). |
| page_id {+Conditional}| string |<ul><li>Required when `lead_source` is `INSTANT_FORM` or not specified.</li><li>Not supported when `lead_source` is `DIRECT_MESSAGE`.</li></ul><br>The page ID of an Instant Form.<br><br>To obtain a list of Instant Forms, use [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586) and set `business_type` to `LEAD_GEN`. |
```

### Example
```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID", 
    "page_id": "PAGE_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/page/lead/mock/create/
(/code)
```

## Response
```xtable
|Field|Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log id of the request, which uniquely identifies a request. |
|data |object|The returned data. |
#|lead_data|object| The lead data. |
#|meta_data|object| Metadata about the lead. |
##| lead_source | string | The source of the lead. <br><br> Enum values:<ul><li>`INSTANT_FORM`: a lead generated through an Instant Form.</li><li>`DIRECT_MESSAGE`: a lead generated from a direct message of the associated Business Account.</li></ul> |
##|lead_id|string| The lead ID.|
##|page_id|string| Returned only when `lead_source` is `INSTANT_FORM`. <br><br>The page ID of the Instant Form. |
##|campaign_id|string| The campaign ID. |
##|campaign_name|string| The campaign name. |
##|adgroup_id|string| The ad group ID.|
##|adgroup_name|string| The ad group name.|
##|ad_id|string| The ad ID |
##|ad_name|string| The ad name. |
##|create_time|datetime| The creation time of the test lead. |
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code":0,
    "message":"OK",
    "request_id":"202208010308080102450041491221C41E",
    "data":{
        "meta_data":{
            "create_time":1659323289,
            "campaign_id":1679105346952197,
            "campaign_name":"test lead: dummy data for campaign name",
            "adgroup_id":1679105346954261,
            "adgroup_name":"test lead: dummy data for ad group name",
            "lead_id":7126711421177430274,
            "ad_id":1679105346954262,
            "ad_name":"test lead: dummy data for ad name",
            "page_id":6873052183063953666
        },
        "lead_data":{
            "name":"Jane Doe",
            "phone_number":"8001000000",
            "Availability":"test lead: dummy data for Availability"
        }
    }
}
(/code);
```
