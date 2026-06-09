---
title: "Update an Offline Event set"
doc_id: 1765596741157889
path: "API Reference / Events 1.0 / Offline Events / Update an Offline Event set"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update an Offline Event set

Use this endpoint to update the name, the Auto Tracking setting, or both of an Offline Event set.


> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.
## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/offline/update/

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
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID. |
| event_set_id {Required} | string | ID of the Offline Event set that you want to update. <br> You can get Offline Event set IDs under an advertiser account via [/offline/get/](https://ads.tiktok.com/marketing_api/docs?id=1765596808589313). |
| name | string | New name for the Offline Event set. <br>Length limit: 40 characters.<br>Example: `in_store_purchases`. <br><p><span style="color:darkred"><b>Note</b></span>: You cannot specify a name that has been used by another event set. Otherwise, an error will occur. |
| auto_tracking| boolean | Whether to enable Auto Tracking for the Offline Event set. <br> Enum values: `true`, `false`. <br>If this field is set to `true`, new campaigns created afterwards under this advertiser account will use this event set for ad tracking and attribution. <br><p><span style="color:darkred"><b>Note</b></span>: The maximum number of auto-tracking Offline Event sets under an advertiser account is 10. If the limit is reached, you cannot change a non-auto-tracking Offline Event set into an auto-tracking one. However, you can delete an existing Offline Event set using [/offline/delete/](https://ads.tiktok.com/marketing_api/docs?id=1765596790860802). |
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/offline/update/' 
--header 'Access-Token: {{Access-Token}}' 
--header 'Content-Type: application/json' 
--data-raw '{
    "advertiser_id":"{{advertiser_id}}",
    "event_set_id":"{{event_set_id}}",
    "auto_tracking":"true"
}'
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
#| advertiser_id | string | Advertiser ID. |
#| event_set_id | string | ID of the Offline Event set. |
#| name | string | Name of the Offline Event set. |
#| description | string | Description that you specified for the Offline Event set. <br>Returns `""` if no description has been specified. |
#| auto_tracking | boolean | Whether Auto Tracking is enabled for the Offline Event set. |
#| create_time | string | The time when the Offline Event set was created, in [ISO 8601](https://en.wikipedia.org/wiki/ISO_8601) format. <br>Example: `2023-04-15T00:23:38Z`. |
#| update_time | string | The time when the event set was last updated, in [ISO 8601](https://en.wikipedia.org/wiki/ISO_8601) format. <br>Example: `2023-04-15T00:23:38Z`. |
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
        "name": "{{name}}",
        "advertiser_id": "{{advertiser_id}}",
        "update_time": "2023-05-05T09:53:02Z",
        "create_time": "2023-05-05T09:51:47Z",
        "event_set_id": "{{event_set_id}}",
        "auto_tracking": true,
        "description": "{{description}}"
    }
}
(/code)
```
