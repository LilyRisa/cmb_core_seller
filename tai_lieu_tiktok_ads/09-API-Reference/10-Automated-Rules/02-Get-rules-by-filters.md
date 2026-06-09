---
title: "Get rules by filters"
doc_id: 1738768861976578
path: "API Reference / Automated Rules / Get rules by filters"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get rules by filters

Use this endpoint to get rules based on the values of certain filters. 
## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/optimizer/rule/list/| /v1.3/optimizer/rule/list/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Response parameter data type|`dimension_ids`: number[]<br>`task_exec_id`: number<br>`rule_id`: number|`dimension_ids`: string[]<br>`task_exec_id`: string<br>`rule_id`: string|
|Response parameter deprecated in v1.3 |/|`recipients`|
```
## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/optimizer/rule/list/>

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {required}|string|Request message type<br>Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {required}|string| Advertiser ID |
|filtering| object|Filters.|
#|status|string| Status of rules. Enum values: <ul><li>`ON`: on.</li><li>`OFF`: off.</li><li>`DELETED`: deleted.</li></ul>|
#|rule_info|string[]|Rule ids or rule names.|
#|data_dimension|string|Enum values: `CAMPAIGN`, `ADGROUP`, `AD`.|
|tzone|string|If provided, all the time information in the response (`create_datetime`, `email_exec_time`, `check_datetime`, `exec_datetime`) will be shown in user specified timezone. For acceptable timezone code, see [Time Zone](https://ads.tiktok.com/marketing_api/docs?id=1737586324313089).|
|page|number|Current page number. Default: 1|
|page_size|number|Page size. Default: 10|
```

### Example
```xcodeblock
(code JSON JSON)
{
    "advertiser_id": "75960823018",
    "filters":[
        {
            "filter_type":"RULE_INFO",
            "filter_input":["6984697088064290818"]
        }
    ],
    "page":1,
    "page_size":10
}
(/code)
```

## Response

```xtable
|Field{45%}|Data Type{15%}|Description{40%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| Unique ID of the request|
|data|object|Returned data.|
#|rules|object[]| List of rules|
##|apply_objects|object[]| Objects that you want to apply the rules to. Objects can be campaigns, ad groups, or ads.|
###|dimesion |string| Category of the object. Enum values: `CAMPAIGN`, `ADGROUP`, `AD`.|
###|dimension_ids| string[]| IDs of the objects|
###|pre_condition_type |string| Conditions to filter out objects that you do not want to include. Enum values:  <ul><li>`SELECTED`: Only include those that are selected by IDs.</li><li>`ALL_ACTIVE_CAMPAIGN`: Select all active campaigns.</li><li>`ALL_ACTIVE_AD_GROUP`: Select all active ad groups. </li><li>`ALL_ACTIVE_AD`: Select all active ads.</li><li>` ALL_ACTIVE_AD_GROUP_UNDER_SELECTED`: Select all active ad groups under the selected campaign.</li><li>`ALL_ACTIVE_AD_UNDER_SELECTED`: Select all active ads under selected dimensions</li><li>`ALL_INACTIVE_CAMPAIGN`: Select all inactive campaigns. </li><li>`ALL_INACTIVE_AD_GROUP`: Select all inactive ad groups</li><li>`ALL_INACTIVE_AD`: Select all inactive ads.</li><li>`ALL_INACTIVE_AD_GROUP_UNDER_SELECTED`: Select all inactive ad groups under the selected campaign.</li><li>`ALL_INACTIVE_AD_UNDER_SELECTED`: Select all inactive ads under the selected campaign or ad group (specified by `dimension` and `dimension_ids`)</li></ul>|
###|bind_type|string|Bind type of the rule. Enum values: `BIND`, `UNBIND`|
##|conditions|object[]| Conditions|
###|subject_type|string| Subjects that the condition will be based on. Enum values: <ul><li>`COST`</li><li>`DAILY_BUDGET_SPENDING_RATE`</li><li>`LIFETIME_BUDGET_SPENDING_RATE`</li><li>`IMPRESSION`</li><li>`CLICK`</li><li>`CONVERSION`</li><li>`CONVERSION_SKAN`</li><li>`CVR`</li><li>`CVR_SKAN`</li><li>`CPA`</li><li>`CPM`</li><li>`CPC`</li><li>`CTR`</li><li>`COST_PER_RESULT`</li><li>`RESULT`</li><li>`RESULT_RATE`</li><li>`BID_STRATEGY`</li><li>`OPTIMIZATION_GOAL`</li><li>`OBJECTIVE`</li><li>`NAME`</li><li>`DAYS_SINCE_CREATION`</li><li>`NO_CONDITION`</li><li>`ROAS_PURCHASE`</li><li>`ROAS_IMPRESSION_ADS`</li><li>`ROAS_PURCHASE_SKAN`</li><li>`ROAS_COMPLETE_PAYMENT`</li><li>`ROAS_PURCHASES_ONSITE`</li><li>`NOT_REAL_TIME_CPA`</li><li>`NOT_REAL_TIME_CONVERSION`</li><li>`NOT_REAL_TIME_CVR`</li></ul>|
###|range_type |string| Time range for the condition. Enum values: <ul><li>`TODAY`</li><li>`YESTERDAY`</li><li>`PAST_THREE_DAYS`</li><li>`PAST_FIVE_DAYS`</li><li>`PAST_SEVEN_DAYS`</li><li>`LIFETIME`</li></ul>|
###|match_type|string| Operator to connect a condition with a value. Enum values: <li>`GT` (greater than)</li> <li>`LT` (less than)</li> <li>`BETWEEN`</li><li>`MATCH`</li><li>`CONTAINS`</li><li>`NOT_CONTAINS`</li><li>`START_WITH`</li><li>`END_WITH`</li><li>`STRING_EQUAL` |
###|values |string[]| Values for the condition.|
###|calculation_type|string|Enum values:<li>`ALL_OBJECTS`</li><li>`OF_EACH_OBJECT`</li>|
##|action |object[]| Actions to be taken when the condition is met|
###|subject_type|string| Type of actions to take. Enum values: `TURN_ON`, `TURN_OFF`, `MESSAGE`, `DAILY_BUDGET`, `LIFETIME_BUDGET`, `BID`|
###|action_type|string| Enum values: `INCREASE`, `DECREASE`, `ADJUST_TO`|
###|value_type|string| Enum values: `EXACT`, `PERCENT`|
###|value|object|When `action_type` is `INCREASE` or `DECREASE`, users can specify the maximum(if INCREASE) or minimum(if DECREASE) budget using `use_limit` and `limit`. When `action_type` is `ADJUST_TO`, no need to provide `use_limit` and `limit`.|
###|frequency_info|object| Frequency settings. It can be used when the action `subject_type` is `DAILY_BUDGET`, `LIFETIME_BUDGET`, or `BID`|
####|type|string| Enum values:<ul><li> `ONLY_ONCE`</li><li> `ONCE_IN_24_H`</li><li> `ONCE_IN_48_H`</li><li> `ONCE_IN_1_W`</li><li> `CUSTOM`</li></ul>|
####|custom_frequency_type|string|Custom frequency type. Valid only when `type` is `CUSTOM`. Enum:<ul><li> `N_MINTUE_Y_TIMES`</li><li> `N_HOUR_Y_TIMES`</li><li> `N_DAY_Y_TIMES`</li></ul>|
####|time|number|It is the `N` in `custom_frequency_type`|
####|count|number|It is the `Y` in `custom_frequency_type`|
##|notification |object| Notification settings|
###|notification_type | string|Notification type you need. Enum values: <ul><li>`TASK_FINISH`: Notification will be sent when the task is finished. This option is only for `MESSAGE` subject type.</li><li>`ANY_CHANGES`: If the `subject_type` for the action is not `MESSAGE` but you still want to receive emails, use this option.</li><li>`NOT_NOTIFICATION`: No notifications are needed.</li></ul>|
###|email_setting|object|Email notification settings|
####|notification_period|string|Enum values:<ul><li>`EVERY_TIME`</li><li>`TIME_SCHEDULE`: notifications will be sent together around a specified time.</li></ul>|
####|email_exec_time|string[]|Required when `notification_period` is `TIME_SCHEDULE`. Notification email sent time. For example, "12:00".|
####|no_result_notification|boolean|It will be set as false by default if not provided, meaning no notification will be sent if there is no result.|
##|rule_exec_info |object| Rule run settings|
###|exec_time_type|string| When the rule will be run. Enum values: <ul><li>`PER_HALF_HOUR`: the rule will be run every half hour.</li><li>`CUSTOM`: the rule will be run at a customized time (specified in the `exec_time` field).</li><li>`HALF_HOUR_IN_SPECIFIC_TIME_PERIOD`: the rule will be run every 30 minutes during specified time range on specifed days during the week.<li>`SPECIFIC_TIME_ACCURATE_ONCE`: the rule will be run only once at a customized time (specified in the `exec_time` field).</li></ul>|
###|exec_time|string| If `exec_time_type` is set to `CUSTOM` or `SPECIFIC_TIME_ACCURATE_ONCE`, use this field to specify the exact time when the rule will be run. <br>Format:<br><ul><li>"HH:MM" when `exec_time_type` is `CUSTOM`. For example: "12:00".</li><li>"YYYY-MM-DD HH:MM" when `exec_time_type` is `SPECIFIC_TIME_ACCURATE_ONCE`. For example: "2023-04-20 04:20".</li></ul>|
###|time_period_info|object[]|Valid when `exec_time_type` is `HALF_HOUR_IN_SPECIFIC_TIME_PERIOD`. It has `start_time`, `end_time`, `date_type` and `num` to specify the time period information.|
####|num|integers[]| Each number in the list represents the day in the week or month.<ul><li>When `date_type` is `WEEK`, value range for this field is  [1, 7], meaning Monday to Sunday.<li>When `date_type` is `MONTH`, value range for this field is  [1, 28], meaning 1st day to the 28th day of the month.</li></ul>|
####|start_time|string| Start time of this time period. For example, "12:00".|
####|end_time|string| End time of this time period. For example, "12:00".|
####|date_type|string| Type of date. Enum values: `DAY`, `WEEK`, `MONTH`|
##|last_check_result_summary|object| Information about the last rule run|
###|check_datetime|string| Date and time when the rule was checked, in UTC+0 time zone|
###|exec_datetime|string| Date and time when the rule was run, in UTC+0 time zone|
###|change_success|number| Objects that have been changed|
###|no_change|number| Objects that have not been changed|
###|change_fail|number| Objects that failed to be changed|
###|task_exec_id|string| Rule execution ID|
##|rule_id|string|ID of the rule|
##|name |string| Name of the rule|
##|rule_status|string|Rule status. Enum values: <ul><li>`ON`: on.</li><li>`OFF`: off.</li><li>`DELETED`: deleted.</li></ul>|
##|create_datetime|string|Date and time when the rule was created, in UTC+0 time zone|
```

### Example
```xcodeblock
(code JSON JSON)
{
    "code": 0,
    "message": "OK",
    "request_id": "2021072906564801024501305243095511",
    "data": {
        "rules": [
            {
                "last_check_result_summary": {
                    "change_success": 1,
                    "change_fail": 0,
                    "exec_datetime": "2021-07-12 06:00:00",
                    "task_exec_id": "6983915767100080129",
                    "not_change": 0,
                    "check_datetime": "2021-07-12 06:00:00"
                },
                "rule_id": "6982553361971232769",
                "notification": {
                    "notification_type": "TASK_FINISH"
                },
                "conditions": [
                    {
                        "subject_type": "CLICK",
                        "match_type": "GT",
                        "values": [
                            "0"
                        ],
                        "range_type": "LIFETIME"
                    }
                ],
                "name": "[API]Automated Rule--20210712-PPE-8",
                "rule_exec_info": {
                    "exec_time_type": "PER_HALF_HOUR",
                    "exec_time": ""
                },
                "create_datetime": "2021-07-12 04:43:56",
                "actions": [
                    {
                        "subject_type": "MESSAGE"
                    }
                ],
                "rule_status": "OFF",
                "apply_objects": [
                    {
                        "dimension_ids": [
                            "1695172996977714"
                        ],
                        "pre_condition_type": "SELECTED",
                        "dimension": "CAMPAIGN",
                        "bind_type": "BIND"
                    }
                ]
            },
            {
                "last_check_result_summary": {
                    "change_success": 0,
                    "change_fail": 0,
                    "exec_datetime": "0001-01-01 00:00:00",
                    "task_exec_id": "0",
                    "not_change": 0,
                    "check_datetime": "0001-01-01 00:00:00"
                },
                "rule_id": "6984697088064290818",
                "notification": {
                    "notification_type": "TASK_FINISH"
                },
                "conditions": [
                    {
                        "subject_type": "CLICK",
                        "match_type": "GT",
                        "values": [
                            "1"
                        ],
                        "range_type": "LIFETIME"
                    }
                ],
                "name": "[API]Automated Rule--example-update",
                "rule_exec_info": {
                    "exec_time_type": "PER_HALF_HOUR",
                    "exec_time": ""
                },
                "create_datetime": "2021-07-29 06:45:04",
                "actions": [
                    {
                        "subject_type": "MESSAGE"
                    }
                ],
                "rule_status": "ON",
                "apply_objects": [
                    {
                        "dimension_ids": [
                            "1694011873852465"
                        ],
                        "pre_condition_type": "SELECTED",
                        "dimension": "CAMPAIGN",
                        "bind_type": "BIND"
                    }
                ]
            }
        ]
    }
}
(/code)
```
