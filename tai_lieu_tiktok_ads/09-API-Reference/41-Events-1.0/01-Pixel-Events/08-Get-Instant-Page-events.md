---
title: "Get Instant Page events"
doc_id: 1740898639412226
path: "API Reference / Events 1.0 / Pixel Events / Get Instant Page events"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Instant Page events

Use this endpoint to get the supported events for Instant Pages based on your objective types and optimization goals.
> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.
## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/pixel/instant_page/event/|/v1.3/pixel/instant_page/event/|
|Request parameter type|`advertiser_id`: number<br>|`advertiser_id`: string<br>|
|Request parameter name|`optimize_goal`|`optimization_goal`|
|Response parameter name|`optimize_goals` <br> `optimize_goal` <br> `external_actions` <br> |`optimization_goals` <br> `optimization_goal` <br> `optimization_events` |
|Response parameter enum value name|`business_type`: `CUSTOM_INSTANT_PAGE`|`business_type`: `TIKTOK_INSTANT_PAGE`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/pixel/instant_page/event/

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID|
|objective_type {Required}|string|Objective type. Must be either `CONVERSIONS` or `APP_PROMOTION`.|
|optimization_goal {Required}|string|Optimization goal|
|is_retargeting|boolean| Whether it is for retargeting|
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST https://business-api.tiktok.com/open_api/pixel/instant_page/event/?advertiser_id={{advertiser_id}}&objective_type=CONVERSIONS&optimization_goal=CONVERT&is_retargeting=true
--header 'Access-Token: {{Access-Token}}'
--header 'Content-Type: application/json'
--data-raw '{
    "event_name": "{{event_name}}",
    "event_time": {{event_time}},
    "event_id": "{{event_id}}",
    "properties": {
        "contents": [
            {
                "content_id": "{{content_id}}",
                "content_type": "product"
            }
        ],
        "currency": "USD",
        "value": {{value}}
    }
}'
(/code)
```

## Response

```xtable
|Field{40%}|Data Type{10%}|Description{50%}|
|---|---|---|
|code |number|The return code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Return data|
#|list|object[]|Data list|
##|business_type|string| Business type. Currently, the only supported value is `TIKTOK_INSTANT_PAGE`.|
##|instant_page_events|object| Instant page events|
###|objective_types|object[]|List of objective types|
####|objective_type|string|Objective type. Can be `CONVERSIONS`, `APP_PROMOTION`. |
####|optimization_goals|object[]|List of optimization goals|
#####|optimization_goal|string|Optimization goal|
#####|optimization_events|string[]|List of external actions. Currently, the only allowed value is `BUTTON`, which means button clicks.|
|request_id|string| Unique ID of the request|
```

### Example

```xcodeblock
(code json JSON)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "LOG_ID",
    "data": {
        "list": [
            {
                "business_type": "TIKTOK_INSTANT_PAGE",
                "instant_page_events": {
                    "objective_types": [
                        {
                            "optimization_goals": [
                                {
                                    "optimization_goal": "CONVERT",
                                    "optimization_events": [
                                        "BUTTON"
                                    ]
                                }
                            ],
                            "objective_type": "CONVERSIONS"
                        }
                    ]
                }
            }
        ]
    }
}
(/code);
```
