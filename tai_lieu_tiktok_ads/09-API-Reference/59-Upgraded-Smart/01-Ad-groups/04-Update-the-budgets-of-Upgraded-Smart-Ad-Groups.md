---
title: "Update the budgets of Upgraded Smart+ Ad Groups"
doc_id: 1843314914438466
path: "API Reference / Upgraded Smart+ / Ad groups / Update the budgets of Upgraded Smart+ Ad Groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update the budgets of Upgraded Smart+ Ad Groups

Use this endpoint to update the budgets of Upgraded Smart+ Ad Groups.

### Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/budget/update/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed value: `application/json`.|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|budget{+Conditional}|object[]|Either `budget` or `scheduled_budget` has to be set.<ul><li>To update the budgets of one or more ad groups, specify `budget`.</li><li>To set a scheduled budget change to the budgets of one or more ad groups, specify `scheduled_budget`.</li></ul><br>Information about the new budgets that you want to set for one or more ad groups.<br><br>Max size: 20.<br><br><p><span style="color:darkred"><b>Note</b></span>: The changes to the budgets of one or more ad groups will take effect immediately.|
#| adgroup_id {+Conditional}|string|Required when the object array `budget` is specified.<br><br>Ad group ID.<br><br>To obtain ad group IDs, use [/smart_plus/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1843314879617026).|
#| budget {+Conditional}|float|Required when the object array `budget` is specified.<br><br>The new budget for the ad group (`adgroup_id`).|
| scheduled_budget {+Conditional}| object[] | Either `budget` or `scheduled_budget` has to be set.<ul><li>To update the budgets of one or more ad groups, specify `budget`.</li><li>To set a scheduled budget change to the budgets of one or more ad groups, specify `scheduled_budget`.</li></ul><br>Information about a list of scheduled budget adjustments for one or more ad groups.<br><br>Max size: 20.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Configuring scheduled budget changes for your Upgraded Smart+ Ad Groups is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>The scheduled budget changes will take effect from 00:00 a.m. the following day in the ad account's time zone.</li></ul></p>|
#|adgroup_id {+Conditional}|string|Required when the object array `scheduled_budget` is specified.<br><br>Ad group ID.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>If the ad group (`adgroup_id`) is part of a CBO campaign (where `budget_optimize_on` is`true`), a `min_budget` or `max_budget` or both must be configured.</li><li>No prerequisites apply for ad groups within non-CBO campaigns (where `budget_optimize_on` is `false`).</li></ul></p>|
#|scheduled_budget {+Conditional}|float|Required when the object array `scheduled_budget` is specified.<br><br>The maximum budget control or the new budget for the ad group (`adgroup_id`).<br><br><ul><li>For an ad group in a CBO campaign (where `budget_optimize_on` is `true`), a `min_budget` or `max_budget` or both must be configured. This field will set the maximum daily budget control for the ad group.</li><li>For an ad group in a non-CBO campaign (where `budget_optimize_on` is `false`), this field sets the new dynamic daily budget for the ad group, with no prerequisites.</li></ul>|
```

#### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/budget/update/' \
--header 'Access-Token: "{{Access-Token}}"' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "budget": [
        {
            "adgroup_id":"{{adgroup_id}}",
            "budget": "{{budget}}"
        },
        {
            "adgroup_id":"{{adgroup_id}}",
            "budget": "{{budget}}"
        }
    ]
}'
(/code)
```

### Response
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code|number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data.|
```

#### Example
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```
