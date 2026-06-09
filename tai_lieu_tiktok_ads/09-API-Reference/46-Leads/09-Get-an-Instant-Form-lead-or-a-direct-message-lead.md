---
title: "Get an Instant Form lead or a direct message lead"
doc_id: 1832112833212434
path: "API Reference / Leads / Get an Instant Form lead or a direct message lead"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get an Instant Form lead or a direct message lead

Use this endpoint to retrieve an Instant Form lead or a direct message lead.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/lead/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| lead_source  {Required}| string | The source of the leads.<br><br>Enum values:<ul><li>`INSTANT_FORM`: leads generated through an Instant Form.</li><li>`DIRECT_MESSAGE`: leads generated from direct messages of the associated Business Account.</li></ul> |
| advertiser_id  {+Conditional} | string | Required when the leads are under an ad account.<br><br>Advertiser ID. |
| library_id  {+Conditional} | string |  Required when the leads are under a form library of a Business Center.<br><br>ID of a form library.<br><br>To obtain the list of form libraries within a Business Center, use [/page/library/get/](https://business-api.tiktok.com/portal/docs?id=1740029915327489). |
| page_id  {+Conditional} | string |  <ul><li>Required when `lead_source` is `INSTANT_FORM`.</li><li>Not supported when `lead_source` is `DIRECT_MESSAGE`.</li></ul><br>The page ID of an Instant Form.<br><br>To obtain a list of Instant Forms, use [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586) and set `business_type` to `LEAD_GEN`. |
```

### Example
#### Get an Instant From lead
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/lead/get/?lead_source=INSTANT_FORM&advertiser_id={{advertiser_id}}&page_id={{page_id}}' \
--header 'Access-token: {{Access-token}}'
(/code)
```
#### Get a direct message lead
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/lead/get/?lead_source=DIRECT_MESSAGE&advertiser_id={{advertiser_id}}' \
--header 'Access-token: {{Access-token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| lead_data | object | The lead data. <br><br><p><span style="color:darkred"><b>Note</b></span>: The data within this object depends on the questions that you have set in the Instant Form or the questions collected in the direct message lead. |
#| meta_data | object | Metadata about the lead.|
##| lead_source | string | The source of the lead. <br><br>Enum values:<ul><li>`INSTANT_FORM`: a lead generated through an Instant Form.</li><li>`DIRECT_MESSAGE`: a lead generated from a direct message of the associated Business Account.</li></ul> |
##| lead_id | string | The lead ID. |
##| page_id | string | Returned only when `lead_source` is `INSTANT_FORM`. <br><br> The page ID of the Instant Form. |
##| campaign_id | string | The campaign ID.|
##| campaign_name | string | The campaign name. |
##| adgroup_id | string | The ad group ID.|
##| adgroup_name | string | The ad group name. |
##| ad_id | string | The ad ID.|
##| ad_name | string | The ad name.
##| create_time | dtring| The time when the lead was created, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0 time). <br><br> Example: `2025-01-01 00:00:01`. |
```

### Example
#### Get an Instant From lead
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "lead_data": {
            "email": "{{email}}"
        },
        "meta_data": {
            "ad_id": "{{ad_id}}",
            "ad_name": "{{ad_name}}",
            "adgroup_id": "{{adgroup_id}}",
            "adgroup_name": "{{adgroup_name}}",
            "campaign_id": "{{campaign_id}}",
            "campaign_name": "{{campaign_name}}",
            "create_time": "{{create_time}}",
            "lead_id": "{{lead_id}}",
            "lead_source": "INSTANT_FORM",
            "page_id": "{{page_id}}"
        }
    }
}
(/code)
```
#### Get a direct message lead
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "lead_data": {
            "email": "{{email}}",
            "name": "{{name}}",
            "phone_number": "{{phone_number}}",
            "tiktok": "{{tiktok}}"
        },
        "meta_data": {
            "ad_id": "{{ad_id}}",
            "ad_name": "{{ad_name}}",
            "adgroup_id": "{{adgroup_id}}",
            "adgroup_name": "{{adgroup_name}}",
            "campaign_id": "{{campaign_id}}",
            "campaign_name": "{{campaign_name}}",
            "create_time": "{{create_time}}",
            "lead_id": "{{lead_id}}",
            "lead_source": "DIRECT_MESSAGE",
            "page_id": "{{page_id}}"
        }
    }
}
(/code)
```
