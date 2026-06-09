---
title: "Get App Conversion Events"
doc_id: 1740859338750977
path: "API Reference / Events 1.0 / App Events / Get App Conversion Events"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get App Conversion Events

Use this endpoint to obtain information about an app conversion event.
> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.
## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/app/external_action/| /v1.3/app/optimization_event/|
|Request parameter name|`is_dedicated` <br>`optimize_goal`|`is_skan`<br>`optimization_goal` |
|Request parameter data type|`advertiser_id`: number <br>`app_id`: number |`advertiser_id`: string <br>`app_id`: string |
| New request parameter | / | `app_promotion_type`<br>`placement_type`|
|Response parameter name |`external_actions` <br>`external_action` (in `external_actions`)<br>`is_no_bid_enabled` <br>`is_auto_bid_enabled`<br>`is_multi_bid_enabled`<br>`deep_external_actions` <br>`external_action` (in `deep_external_actions`) |`optimization_events` <br>`optimization_event` (in `optimization_events`)<br>`no_bid_enabled` <br>`auto_bid_enabled` <br>`multi_bid_enabled` <br>`deep_optimization_events` <br>` optimization_event`(in `deep_optimization_events`)|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/app/optimization_event/

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID |
|app_id {Required}|string|Your App ID, obtained after successfully creating your app. |
|placement_type |string|The placement strategy that decides where your ads will be shown. <br>Enum values: `PLACEMENT_TYPE_AUTOMATIC` (Automatic placement), `PLACEMENT_TYPE_NORMAL` (Select placement). <br>Default value: `PLACEMENT_TYPE_NORMAL`.|
|placement {+Conditional}|string[]|The apps where you want to deliver your ads. <br>Required when `placement_type` is `PLACEMENT_TYPE_NORMAL`, and ignored when `placement_type` is `PLACEMENT_TYPE_AUTOMATIC`. <br><p><span style="color:darkred"><b>Note</b></span>:  <ul><li>Currently, we support `PLACEMENT_TIKTOK`, `PLACEMENT_PANGLE` and `PLACEMENT_GLOBAL_APP_BUNDLE`. Please don't select `PLACEMENT_TOPBUZZ` or `PLACEMENT_HELO` as your `placements` since they've been deprecated. For a full list of enum values, see [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).</li><li>For Product Sales campaigns (`objective_type` = `PRODUCT_SALES`), only TikTok placement (`PLACEMENT_TIKTOK`) is supported.</li></ul>|
|optimization_goal {Required}|string|Optimization goal. For enum values, see [Enumeration-Optimization Goal](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for more|
|objective {Required}|string|Advertising Objective. For enum values, see [Enumeration-Advertising Objective](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) |
| app_promotion_type {+Conditional} | string | App promotion type. Required when `objective` is `APP_PROMOTION`. Enum values: `APP_INSTALL`, `APP_RETARGETING`. <br><p><span style="color:darkred"><b>Note</b></span>: `APP_INSTALL` can be used in an iOS14 Dedicated Campaign, while `APP_RETARGETING` cannot be used. |
|available_only | boolean| Whether to return only available conversion events. The default value: `True` (only return available conversion events)|
|is_skan|boolean|Whether the app is using Skan features. |
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "objective=OBJECTIVE" \
--data-urlencode "placement=[\"PLACEMENT\"]" \
--data-urlencode "app_id=APP_ID" \
--data-urlencode "optimize_goal=OPTIMIZE_GOAL" \
https://business-api.tiktok.com/open_api/v1.3/app/optimization_event/
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Return data|
#|optimization_events|object[]| external action list|
##|optimization_event |string|conversion events that you want to measure. See [Appendix-Conversion Events-App Event Type](https://ads.tiktok.com/marketing_api/docs?id=1739361474981889)|
##|statistic_type| string |In this field, you can choose to bid for `EVERYTIME` (Each Purchase)/ `NONE` (Unique Purchase).|
##|availability_status|string| Event availability status, optional values: `INACTIVE` (unavailable), `INSUFFICIENT_POSTBACK` (unavailable: insufficient postback), `READY` (available) |
##|conversions|number| Conversion events postback in the past 30 days |
##|unlock_threshold|number| Event available conversion event threshold (when `conversions` reach `unlock_threshold`, `availability_status` will change from `INSUFFICIENT_POSTBACK` to `READY`)|
##|is_beta|boolean| Whether the event is in beta |
##|bid_type_availability|object| Availability of bid types and deep bid types |
###|no_bid_enabled|boolean| Whether the event supports `bid_type` = `BID_TYPE_NO_BID` (Maximum Delivery) |
###|auto_bid_enabled|boolean| Whether the event supports `deep_bid_type` = `PACING` (auto bid) |
###|multi_bid_enabled|boolean| Whether the event supports `deep_bid_type` = `MIN` (multi bid) |
##|deep_optimization_events |object| deep conversion events |
###|optimization_event|string|deep conversion events, see[Appendix-Conversion Events-App Secondary-goal Event Type](https://ads.tiktok.com/marketing_api/docs?id=1739361474981889)|
###|statistic_type| string | deep conversion bid statistic type, bid for `EVERYTIME` (Each Purchase)/ `NONE` (Unique Purchase)|
###|availability_status|string| Event availability status. Enum values: `INACTIVE` (unavailable), `INSUFFICIENT_POSTBACK` (unavailable: insufficient postback), `READY` (available) |
###|conversions|number| Conversion events postback in the past 30 days |
###|unlock_threshold|number| Event available conversion event threshold (when `conversions` reach `unlock_threshold`, `availability_status` will change from `INSUFFICIENT_POSTBACK` to `READY`)|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "external_actions": [
            {
                "conversions": 59,
                "unlock_threshold": 50,
                "deep_external_actions": [],
                "statistic_type": "NONE",
                "availability_status": "READY",
                "external_action": "COMPLETE_TUTORIAL",
                "is_beta": false
            },
            {
                "conversions": 12,
                "unlock_threshold": 50,
                "deep_external_actions": [],
                "statistic_type": "NONE",
                "availability_status": "INSUFFICIENT_POSTBACK",
                "external_action": "SUBSCRIBE",
                "is_beta": false
            },
            {
                "conversions": 0,
                "unlock_threshold": 50,
                "deep_external_actions": [],
                "statistic_type": "NONE",
                "availability_status": "INACTIVE",
                "external_action": "ADD_PAYMENT_INFO",
                "is_beta": false
            },
            {
                "conversions": 0,
                "unlock_threshold": 50,
                "deep_external_actions": [],
                "statistic_type": "NONE",
                "availability_status": "INACTIVE",
                "external_action": "SEARCH",
                "is_beta": false
            },
            {
                "conversions": 0,
                "unlock_threshold": 50,
                "deep_external_actions": [],
                "statistic_type": "NONE",
                "availability_status": "INACTIVE",
                "external_action": "ACTIVE_REGISTER",
                "is_beta": false
            },
            {
                "conversions": 0,
                "unlock_threshold": 50,
                "deep_external_actions": [],
                "statistic_type": "NONE",
                "availability_status": "INACTIVE",
                "external_action": "IN_APP_DETAIL_UV",
                "is_beta": false
            },
            {
                "conversions": 0,
                "unlock_threshold": 50,
                "deep_external_actions": [],
                "statistic_type": "NONE",
                "availability_status": "INACTIVE",
                "external_action": "START_TRIAL",
                "is_beta": false
            },
            {
                "conversions": 0,
                "unlock_threshold": 50,
                "deep_external_actions": [],
                "statistic_type": "NONE",
                "availability_status": "INACTIVE",
                "external_action": "LOGIN",
                "is_beta": false
            }
        ],
        "multi_bid_enabled": false
    },
    "request_id": "2020111207432201023125009013555"
}
(/code);
```
