---
title: "Get CRM Event Sets"
doc_id: 1780896521680898
path: "API Reference / Events 1.0 / CRM Events / Get CRM Event Sets"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get CRM Event Sets

Use this endpoint to get a list of CRM (Customer relationship management) Event Sets within an advertiser account. You can filter results by CRM Event Set ID or name.

> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.

## Before you start
Ensure that you have CRM Event Sets within your advertiser account. If you don't have CRM Event Sets, use [/crm/create/](https://business-api.tiktok.com/portal/docs?id=1858830704718658) to create them.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/crm/list/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| name | string | The CRM Event Set name to filter the results by. <br><br>Length limit: 1-40 characters. <br><br>Exact match is supported. <br><br><ul><li> If this field is not passed, all CRM Event Sets within the advertiser account will be returned.</li><li> If you pass a name that cannot be matched, the results will be empty. </li></ul> |
| event_set_ids | string[] | The list of CRM Event Set IDs to filter the results by. <br><br>Max size: 50. <br><br>Exact match is supported. <br><br><ul><li> If this field is not passed, all CRM Event Sets within the advertiser account will be returned.</li><li> Only the CRM Event Sets that match the IDs you specify in this field will be returned. Unmatched IDs will be excluded from the returned results.</li><li> If you pass both `event_set_ids` and `name`, the result will be either one event set that matches the specified name and has an ID that corresponds to one of the specified event set IDs, or empty if no event set meets the criteria. </li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: The maximum number of CRM Event Sets allowed within an advertiser account is 50. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET "https://business-api.tiktok.com/open_api/v1.3/crm/list/?advertiser_id={{advertiser_id}}" 
--header "Access-Token: {{Access-Token}}" 
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
#| crm_event_sets | object[] | The list of matched CRM Event Sets within the advertiser account. |
##| event_set_id | string | The ID of the CRM Event Set. |
##| name | string | The name of the CRM Event Set. |
##| create_time| string | The time when the CRM Event Set was created, in [ISO-8601](https://en.wikipedia.org/wiki/ISO_8601) format. <br><br>Example: `"2023-04-15T00:23:38Z"`. |
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
        "crm_event_sets": [
            {
                "event_set_id": "{{event_set_id}}",
                "name": "{{name}}",
                "create_time": "2023-10-06T08:42:36Z"
            },
            {
                "event_set_id": "{{event_set_id}}",
                "name": "{{name}}",
                "create_time": "2023-12-07T14:45:07Z"
            }
        ]
    }
}
(/code)
```
