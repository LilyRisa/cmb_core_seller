---
title: "Get Offline Event sets"
doc_id: 1765596808589313
path: "API Reference / Events 1.0 / Offline Events / Get Offline Event sets"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Offline Event sets

Use this endpoint to get a list of Offline Event sets under an advertiser account. You can filter results by Offline Event set ID or name.

> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.
## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/offline/get/

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
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID. |
| event_set_ids | string[] | A list of Offline Event set IDs that you want to filter the results by. <br> If this field is not passed, all Offline Event sets under the advertiser account will be returned.|
| name | string | Offline Event set name that you want to filter the results by. <br>Length limit: 40 characters. <br>If you pass in both `event_set_ids` and `name`, you will get either one result when an event set is matched with the specified name and its ID is one of the specified event set IDs, or zero result when no event set is matched. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET "https://business-api.tiktok.com/open_api/v1.3/offline/get/?advertiser_id={{advertiser_id}}" 
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
#|event_set_list|object[]|The list of matched Offline Event sets under the specified advertiser account.|
##| advertiser_id | string | Advertiser ID. |
##| event_set_id | string | ID of the Offline Event set. |
##| name | string | Name of the Offline Event set. |
##| description | string | Description that you specified for the Offline Event set. <br>Returns `""` if no description has been specified. |
##| auto_tracking | boolean | Whether Auto Tracking is enabled for the Offline Event set. |
##| create_time | string | The time when the Offline Event set was created, in [ISO-8601](https://en.wikipedia.org/wiki/ISO_8601) format. <br>Example: `2023-04-15T00:23:38Z`. |
##| update_time | string | The time when the event set was last updated, in [ISO-8601](https://en.wikipedia.org/wiki/ISO_8601) format. <br>Example: `2023-04-15T00:23:38Z`. |
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
        "event_set_list": [
            {
                "name": "{{name}}",
                "advertiser_id": "{{advertiser_id}}",
                "update_time": "2022-12-08T06:50:37Z",
                "create_time": "2022-12-06T08:42:36Z",
                "event_set_id": "{{event_set_id}}",
                "auto_tracking": true,
                "description": "{{description}}"
            },
            {
                "name": "{{name}}",
                "advertiser_id": "{{advertiser_id}}",
                "update_time": "2022-12-07T14:45:07Z",
                "create_time": "2022-12-07T14:45:07Z",
                "event_set_id": "{{event_set_id}}",
                "auto_tracking": true,
                "description": "{{description}}"
            }
        ]
    }
}
(/code)
```

