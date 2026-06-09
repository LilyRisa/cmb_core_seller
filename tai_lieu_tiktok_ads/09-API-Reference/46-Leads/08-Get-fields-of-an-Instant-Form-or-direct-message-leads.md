---
title: "Get fields of an Instant Form or direct message leads"
doc_id: 1832112377641026
path: "API Reference / Leads / Get fields of an Instant Form or direct message leads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get fields of an Instant Form or direct message leads

Use this endpoint to retrieve the fields of an Instant Form or direct message leads.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/lead/field/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| lead_source  {Required}| string | The source of the leads.<br><br>Enum values:<ul><li>`INSTANT_FORM`: leads generated through an Instant Form.</li><li>`DIRECT_MESSAGE`: leads generated from direct messages of the associated Business Account.</li></ul> |
| advertiser_id  {+Conditional} | string | Required when the leads are under an ad account.<br><br>Advertiser ID. |
| library_id  {+Conditional} | string |  Required when the leads are under a form library of a Business Center.<br><br>ID of a form library.<br><br>To obtain the list of form libraries within a Business Center, use [/page/library/get/](https://business-api.tiktok.com/portal/docs?id=1740029915327489). |
| page_id  {+Conditional} | string |  <ul><li>Required when `lead_source` is `INSTANT_FORM`.</li><li>Not supported when `lead_source` is `DIRECT_MESSAGE`.</li></ul><br>The page ID of an Instant Form.<br><br>To obtain a list of Instant Forms, use [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586) and set `business_type` to `LEAD_GEN`. |
```

### Example
#### Get fields of an Instant From
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/lead/field/get/?advertiser_id={{advertiser_id}}&lead_source=INSTANT_FORM&page_id={{page_id}}' \
--header 'Access-token: {{Access-token}}'
(/code)
```
#### Get fields of direct message leads
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/lead/field/get/?advertiser_id={{advertiser_id}}&lead_source=DIRECT_MESSAGE' \
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
#| fields | string[] | The fields of the Instant Form or direct message leads. <br><br><p><span style="color:darkred"><b>Note</b></span>: The data within this string array depends on the questions that you have set in the Instant Form or the questions collected in the direct message leads. |
#| meta_data | object | Metadata about the Instant Form or direct message leads. |
##| create_time | string | The time when the Instant Form was created or when the direct message lead was collected, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0 time). <br><br>Example: `2025-01-01 00:00:01`. |
##| page_id | string | The page ID of the Instant Form. <br><br>When `lead_source` is `DIRECT_MESSAGE` in the request, this field will be `"0"`. |
##| page_name | string | The page name of the Instant Form. <br><br>When `lead_source` is `DIRECT_MESSAGE` in the request, this field will be an empty string (`""`). |
##| page_url | string | The URL of the Instant Form. <br><br> When `lead_source` is `DIRECT_MESSAGE` in the request, this field will be an empty string (`""`). |
```

### Example
#### Get fields of an Instant From
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "fields": [
            "email"
        ],
        "meta_data": {
            "create_time": "{{create_time}}",
            "page_id": "{{page_id}}",
            "page_name": "{{page_name}}",
            "page_url": "{{page_url}}"
        }
    }
}
(/code)
```
#### Get fields of direct message leads
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "fields": [
            "name",
            "tiktok",
            "email",
            "phone_number"
        ],
        "meta_data": {
            "create_time": "{{create_time}}",
            "page_id": "0",
            "page_name": "",
            "page_url": ""
        }
    }
}
(/code)
```
