---
title: "Create an audience by rule"
doc_id: 1739566532187138
path: "Marketing API / Audience Management / Guides / Create an audience / Create a Custom Audience / Create an audience by rule"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an audience by rule

You can specify rules to create **Engagement Audience, App Activity Audience, Website Traffic Audience, Lead Generation Audience, Shop Activity Audience, or Business Account Audience**. For example, you can create audiences who have clicked the ad in the past seven days and audiences who have added products to the cart but haven't purchased them in the last 14 days.

Use [/dmp/custom_audience/rule/create/](https://ads.tiktok.com/marketing_api/docs?id=1739940509849601) to determine a set of inclusion rules and optionally a set of exclusion rules. If your call is successful, the response will return a `custom_audience_id`, which can be used to update, get, or delete the audience. The structure of your rules (`rule_spec`) is as below: 
- One audience rule contains two rule sets. 
- Each rule set contains rules and an operator. 
- Each rule contains filter sets and audience retention days. 
- Each filter set contains filters and an operator. 


> <span style="color: DodgerBlue">**Note**</span><ul><li>Newly created audiences require about an hour to be analyzed and processed before they become active. </li><li> Use [/dmp/custom_audience/get/](https://ads.tiktok.com/marketing_api/docs?id=1739940507792385) to check the current status of an audience. Only available (`is_valid`) audiences can be operated on.
  
  # Audience rule format

```xtable
| Field | Type | Description |
|---|---|---|
| inclusion_rule_set {Required} | object |A set of inclusion rule object. Quantity range: 1 to 5, without repetition. For the format, see the "Rule set format" section below.|
| exclusion_rule_set{Optional}  | object |A set of exclusion rule object. Quantity range: 0 to 5, without repetition. For the format, see the "Rule set format" section below.|
```
```xcodeblock
(code)
AudienceRule = {  
  "inclusion_rule_set": RULE_SET,
  "exclusion_rule_set": RULE_SET
  }
  (/code)
```
  
# Rule set format

```xtable
| Field | Type | Description |
|---|---|---|
| operator {Required} | object | Operator between rules. `AND` indicates that both rules must be met; `OR` indicates that either one can be met. Only `OR` is supported currently. |
| rules {Required} | object[] |Inclusion rule object. For the format, see the "Rule format" section below. |
```

  
```xcodeblock
(code)
RULE_SET = {
  "operator": BOOLEAN_OPERATOR,
  "rules": [RULE, ...]
}
(/code)
```

  
# Rule format


```xtable
| Field | Type | Description |
|---|---|---|
| event_source_ids {Optional} | string[] |List of event source IDs of the rule. Required when `audience_type` is not `ENGAGEMENT` or `LEAD_GENERATION`.<ul><li> For Engagement Audience, use ad group IDs as event source IDs. If not specified, all available source IDs will be used.<li>For Pixel Audience, use pixel id as event source IDs. <li>For App Activity Audience, use App IDs as event source IDs. <li>For Lead Generation Audience, do not pass in this field. Otherwise, an error will occur. If not specified, all available source IDs will be used.<li>For Shop Activity Audience, use store IDs as event source IDs. (You can use [/store/list/](https://business-api.tiktok.com/portal/docs?id=1752267762718722) to get store IDs.)<li>For Business Account Audience, use core user IDs of the advertiser. (You can use [/user/info/](https://business-api.tiktok.com/portal/docs?id=1739665513181185) to get a core user ID. )<li>For Organic Engagement Audience, use item IDs(video IDs) as event source IDs. (You can use [/identity/live/get/](https://business-api.tiktok.com/portal/docs?id=1740218475032577) to get item IDs.) The maximum allowed number of item IDs is 10. <li>For Live Engagement Audience, use live video ID as event source IDs.  (You can use [/identity/live/get/](https://business-api.tiktok.com/portal/docs?id=1740218475032577) to get live video IDs.) The maximum allowed number of live video IDs is 10. </li><li>For Offline Activity Audience, use Offline Event set IDs as event source IDs. (You can use [/offline/get/](https://business-api.tiktok.com/portal/docs?id=1765596808589313) to get Offline Event set IDs.)</li></ul>|
| retention_days {Required} | number |Retention days of the audience. For enum values, see [Enumerations - Lookback window](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Lookback%20window%20%28retention_days%29). <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If `audience_type` = `BUSINESS_ACCOUNT`, and the `value` in the `filters` object is not `BUSINESS ACCOUNT PROFILE FOLLOW`, then `retention_days` must be `7`, `14`, or `30`.</li><li>If `audience_type` = `ENGAGEMENT_LIVE_VIDEO` or `ENGAGEMENT_ORGANIC_VIDEO`, then `retention_days` must be `7`, `14`, or `30`.</li></ul>|
| filter_set{Required}  | object |List of filter set values. For the format, see the "Filter set format" section below. |
```

```xcodeblock
(code)
Rule = {
    "event_sources": [EVENT_SOURCE],
    "retention": RETENTION,
    "filter_set": FILTER_SET
}
(/code)
```

  
# Filter set format



```xtable
| Field | Type | Description |
|---|---|---|
| operator {Required} | string |Operator between filters.  `AND` indicates that both filter conditions must be met; `OR` indicates that either one can be met. Only `OR` is supported currently. |
| filters {Required} | object[] |A list of filters. Only a single value can be passed in currently. For the format, see the "Filter format" section below. |
```
  
```xcodeblock
(code)
FilterSet = {
    "operator": BOOLEAN_OPERATOR,
    "filters": [FILTER]
}
(/code)
```
  
# Filter format


```xtable
| Field | Type | Description |
|---|---|---|
| field {Required} | string | Filter field. Currently we only supports `EVENT`. |
| operator {Required} | string | Filter operator between filter field and filter value. Enum values: `EQ` (equal to), `GT` (greater than), and `LT` (less than). Only `EQ` is supported currently. |
| value {Required} | string |Filter field value. For enum values, see [Enumeration - Filter Value](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
```

  ```xcodeblock
(code)
Filter = {
    "field": FILTER_FIELD,
    "operator": FILTER_OPERATION,
    "value": FILTER_VALUE
}
(/code)
```
  
## Examples
```xcodeblock
(code)
curl --location 'https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/rule/create/' \
--header 'Content-Type: application/json' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "custom_audience_name": "{{custom_audience_name}}",
    "audience_type": "OFFLINE",
    "rule_spec": {
        "inclusion_rule_set": {
            "operator": "OR",
            "rules": [
                {
                    "event_source_ids": [
                        "{{event_source_ids}}"
                    ],
                    "retention_days": 7,
                    "filter_set": {
                        "operator": "AND",
                        "filters": [
                            {
                                "field": "EVENT",
                                "operator": "EQ",
                                "value": "OFFLINE COMPLETE PAYMENT"
                            }
                        ]
                    }
                }
            ]
        }
    }
}'
(/code)
```
