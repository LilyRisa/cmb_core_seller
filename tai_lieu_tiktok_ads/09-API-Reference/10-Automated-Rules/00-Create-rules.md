---
title: "Create rules"
doc_id: 1738767670852610
path: "API Reference / Automated Rules / Create rules"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create rules

Use this endpoint to create automated rules.

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/optimizer/rule/create/| /v1.3/optimizer/rule/create/|
|Request parameter data type |`advertiser_id`: number<br>`dimension_ids`: number[]|`advertiser_id`: string<br>`dimension_ids`: string[]|
|Response parameter data type|`rule_ids`: number[]|`rule_ids`: string[]|
```

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/optimizer/rule/create/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type<br>Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID |
|rules|object[]| List of rules|
#|apply_objects {required}|object[]| Objects that you want to apply the rules to. Objects can be campaigns, ad groups, or ads.|
##|dimension {required}|string| Category of the object. Enum values: `CAMPAIGN`, `ADGROUP`, `AD`.|
##|dimension_ids| string[]| IDs of the objects.|
##|pre_condition_type {required}|string| Conditions to filter objects that you want to include. <ul><li>`SELECTED`: Only include those that are selected by IDs.</li><li>`ALL_ACTIVE_CAMPAIGN`: Select all active campaigns.</li><li>`ALL_ACTIVE_AD_GROUP`: Select all active ad groups. </li><li>`ALL_ACTIVE_AD`: Select all active ads.</li><li>` ALL_ACTIVE_AD_GROUP_UNDER_SELECTED`: Select all active ad groups under the selected campaign.</li><li>`ALL_ACTIVE_AD_UNDER_SELECTED`: Select all active ads under selected dimensions</li><li>`ALL_INACTIVE_CAMPAIGN`: Select all inactive campaigns. </li><li>`ALL_INACTIVE_AD_GROUP`: Select all inactive ad groups</li><li>`ALL_INACTIVE_AD`: Select all inactive ads.</li><li>`ALL_INACTIVE_AD_GROUP_UNDER_SELECTED`: Select all inactive ad groups under the selected campaign.</li><li>`ALL_INACTIVE_AD_UNDER_SELECTED`: Select all inactive ads under the selected campaign or ad group (specified by `dimension` and `dimension_ids`)</li></ul>|
#|conditions {required}|object[]| Conditions|
##|subject_type {required}|string| Subjects that the condition will be based on. Enum values: <ul><li>`COST`</li><li>`DAILY_BUDGET_SPENDING_RATE`</li><li>`LIFETIME_BUDGET_SPENDING_RATE`</li><li>`IMPRESSION`</li><li>`CLICK`</li><li>`CONVERSION`</li><li>`CONVERSION_SKAN`</li><li>`CVR`</li><li>`CVR_SKAN`</li><li>`CPA`</li><li>`CPM`</li><li>`CPC`</li><li>`CTR`</li><li>`COST_PER_RESULT`</li><li>`RESULT`</li><li>`RESULT_RATE`</li><li>`BID_STRATEGY`: `values` must be one of these: `COST_CAP`, `BID_CAP`, `MAX_CONVERSION`, `LOWEST_COST`.</li><li>`OPTIMIZATION_GOAL`: `values`must be one of these: `CONVERT`, `CLICK`, `SHOW`, `REACH`, `INSTALL`, `IN_APP_EVENT`, `LEAD_GENERATION`, `TWO_SECOND_VIDEO_VIEW`, `SIX_SECOND_VIDEO_VIEW`</li><li>`OBJECTIVE`: `values`must be one of these: `TRAFFIC`, `APP_PROMOTION`, `WEB_CONVERSIONS`, `VIDEO_VIEWS`, `REACH`, `LEAD_GENERATION`</li><li>`NAME`</li><li>`DAYS_SINCE_CREATION`: `match_type` should be one of `GT`, `LT`, `BETWEEN`, `MATCH`.</li><li>`NO_CONDITION`: Be careful when setting a rule without any conditions to avoid any unintended changes.</li><li>`ROAS_PURCHASE`</li><li>`ROAS_IMPRESSION_ADS`</li><li>`ROAS_PURCHASE_SKAN`</li><li>`ROAS_COMPLETE_PAYMENT`</li><li>`ROAS_PURCHASES_ONSITE`</li><li>`NOT_REAL_TIME_CPA`</li><li>`NOT_REAL_TIME_CONVERSION`</li><li>`NOT_REAL_TIME_CVR`</li></ul>|
##|range_type|string| Time range for the condition. Enum values: <ul><li>`TODAY`</li><li>`YESTERDAY`</li><li>`PAST_THREE_DAYS`</li><li>`PAST_FIVE_DAYS`</li><li>`PAST_SEVEN_DAYS`</li><li>`LIFETIME`</li></ul>|
##|match_type|string| Operator to connect a condition with a value. Enum values: `GT` (greater than), `LT` (less than), `BETWEEN`, `MATCH`. Only when `subject_type` is `NAME`, can below `match_type` be used:<li>`CONTAINS`</li><li>`NOT_CONTAINS`</li><li>`START_WITH`</li><li>`END_WITH`</li><li>`STRING_EQUAL` |
##|values|string[]| Values for the condition. If `match_type` is `BETWEEN`, include two values in this array<br><p><span style="color:darkred"><b>Note</b></span>: Verification rules for the `values` field vary with the `subject_type` you pass in. See <a href="#item-link-Verification Rules for values">Verification Rules for values</a> for details.|
##|calculation_type|string|The `subject_type` has to be `COST`. Set to `OF_EACH_OBJECT` by default if this field is not provided.<li>`ALL_OBJECTS`</li><li>`OF_EACH_OBJECT`</li>|
#|actions {required}|object[]| Actions to be taken when the condition is met.|
##|subject_type {required}|string| Type of actions to take. Enum: `TURN_ON`, `TURN_OFF`, `MESSAGE`, `DAILY_BUDGET`, `LIFETIME_BUDGET`, `BID`.|
##|action_type|string| Enum values: `INCREASE`, `DECREASE`, `ADJUST_TO`.|
##|value_type|string| Enum values: `EXACT`, `PERCENT`.|
##|value {+conditional}|object|The budget value. <br>Required when `subject_type`=`DAILY_BUDGET`, `LIFETIME_BUDGET` or `BID`.|
###|value {+conditional}|float|The budget value you want to increase, decrease or adjust to.<ul><li>If `action_type` =`INCREASE` or `DECREASE`, value range:[0.01, 1000000000000]. </li><li>If `action_type` =`ADJUST_TO`: </li><ul><li>when `dimension`=`CAMPAIGN`, value range: [50, 1000000000000].</li><li>when `dimension`=`ADGROUP`,  value range: [20, 1000000000000].</li></ul></ul>|
###|limit {+conditional}|float|The maximum budget cap (if `action_type` =`INCREASE`) or minimum budget ( if `action_type` =`DECREASE`).<br><br>Required when `action_type` =`INCREASE` or `DECREASE`.<br><ul><li>When `dimension`=`CAMPAIGN`, value range: [50, 1000000000000]. </li><li>When `dimension`=`ADGROUP`, value range: [20, 1000000000000].</li></ul><br>If `action_type`=`ADJUST_TO`, you don't need to pass in this field.|
##|frequency_info|object| Frequency settings. It can be used when the action `subject_type` is `DAILY_BUDGET`, `LIFETIME_BUDGET`, or `BID`.|
###|type|string| Enum values:<ul><li> `ONLY_ONCE`</li><li> `ONCE_IN_24_H`</li><li> `ONCE_IN_48_H`</li><li> `ONCE_IN_1_W`</li><li> `CUSTOM`</li></ul>|
###|custom_frequency_type|string|Custom frequency type. Valid only when `type` is `CUSTOM`. Enum:<ul><li> `N_MINTUE_Y_TIMES`</li><li> `N_HOUR_Y_TIMES`</li><li> `N_DAY_Y_TIMES`</li></ul>|
###|time|number|It is the `N` in `custom_frequency_type`|
###|count|number|It is the `Y` in `custom_frequency_type`|
#|notification {required}|object| Notification settings|
##|notification_type {required}|string|Notification type you need. Enum values: <ul><li>`TASK_FINISH`: Notification will be sent when the task is finished. This option is only for `MESSAGE` subject type.</li><li>`ANY_CHANGES`: If the `subject_type` for the action is not `MESSAGE` but you still want to receive emails, use this option.</li><li>`NOT_NOTIFICATION`: No notifications are needed.</li></ul>|
##|email_setting|object|Email notification settings|
###|notification_period|string|Enum values:<ul><li>`EVERY_TIME`</li><li>`TIME_SCHEDULE`: notifications will be sent together around a specified time.</li></ul>|
###|email_exec_time|string[]|Required when `notification_period` is`TIME_SCHEDULE`. Notification email sent time. For example, "12:00".|
###|no_result_notification|boolean|It will be set as false by default if not provided, meaning no notification will be sent if there is no result.|
###|mute_option|string|This field can be used to mute or unmute email notification for the day. Enum values:<ul><li>`MUTE`</li><li>`UNMUTE`</li></ul>|
#|rule_exec_info {required}|object| Rule run settings.|
##|exec_time_type {required}|string| When the rule will be run. Enum values: <ul><li>`PER_HALF_HOUR`: the rule will be run every half hour.</li><li>`CUSTOM`: the rule will be run at a customized time (specified in the `exec_time` field).</li><li>`HALF_HOUR_IN_SPECIFIC_TIME_PERIOD`: the rule will be run every 30 minutes during specified time range on specifed days during the week.<li>`SPECIFIC_TIME_ACCURATE_ONCE`: the rule will be run only once at a customized time (specified in the `exec_time` field).</li></ul>|
##|exec_time|string| If `exec_time_type` is set to `CUSTOM` or `SPECIFIC_TIME_ACCURATE_ONCE`, use this field to specify the exact time when the rule will be run. <br>Format:<br><ul><li>"HH:MM" when `exec_time_type` is `CUSTOM`. For example: "12:00".</li><li>"YYYY-MM-DD HH:MM" when `exec_time_type` is `SPECIFIC_TIME_ACCURATE_ONCE`. For example: "2023-04-20 04:20".</li></ul></li></ul><span style="color:darkred"><b>Note</b></span>: If `exec_time_type` = `SPECIFIC_TIME_ACCURATE_ONCE`, the `exec_time` cannot exceed one year from the current time. |
##|time_period_info|object[]|Valid when `exec_time_type` is `HALF_HOUR_IN_SPECIFIC_TIME_PERIOD`. It has `start_time`, `end_time`, `date_type` and `num` to specify the time period information.|
###|num{+Conditional}|integers[]|Required when `date_type` is `WEEK` or `MONTH`. Each number in the list represents the day in the week or month.<br><ul><li>When `date_type` is `WEEK`, value range for this field is  [1, 7], meaning Monday to Sunday.<li>When `date_type` is `MONTH`, value range for this field is  [1, 28], meaning 1st day to the 28th day of the month.</li></ul>|
###|start_time|string| Start time of this time period. For example, "12:00".|
###|end_time|string| End time of this time period. For example, "12:00".|
###|date_type|string|Type of date. Enum values: `DAY`, `WEEK`, `MONTH`.|
#|tzone|string|User timezone. In create/update rules, it's used with `mute_option` in notification.email_setting. For acceptable timezone code, see [Time Zone](https://ads.tiktok.com/marketing_api/docs?id=1737586324313089).|
#|name {required}|string| Name of the rule.|
|lang|string| Language of the error messages. Enum values: `ZH` (Simplified Chinese), `EN` (English)(default), `JA` (Japanese).|
```
### Example

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/optimizer/rule/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "rules": [
        {
            "apply_objects": [
                {
                    "dimension": "CAMPAIGN",
                    "dimension_ids": [],
                    "pre_condition_type": "ALL_INACTIVE_CAMPAIGN"
                }
            ],
            "conditions": [
                {
                    "subject_type": "CLICK",
                    "range_type": "LIFETIME",
                    "match_type": "GT",
                    "values": [
                        "0"
                    ]
                }
            ],
            "actions": [
                {
                    "subject_type": "MESSAGE"
                }
            ],
            "notification": {
                "notification_type": "TASK_FINISH"
            },
            "rule_exec_info": {
                "exec_time_type": "CUSTOM",
                "exec_time": "12:00"
            },
            "name": "[API]Automated Rule-example"
        }
    ]
}'
(/code)
```
<br>

### Verification Rules for `values`
Verification rules for the `values` field vary with the `subject_type` you pass in. Please specify the `values` field according to the rules. Otherwise, an error will occur. See the table below for verification rules in detail.
```xtable
|<code>subject_type</code>{50%}|Verification rules for <code>values</code>{50%}|
|---|---|---|
|NAME|Length range: (0,512]|
|COST<br>CPA<br>NOT_REAL_TIME_CPA<br>CPM<br>CPC<br>COST_PER_RESULT|Precision: determined by the currency of your advertiser account(See [Currency-specific precision](https://ads.tiktok.com/marketing_api/docs?id=1772544628718594) for details.)<br>Value range: [0,100000000]|
|DAILY_BUDGET_SPENDING_RATE<br>LIFETIME_BUDGET_SPENDING_RATE<br>CVR<br>CVR_SKAN<br>NOT_REAL_TIME_CVR<br>CTR<br>RESULT_RATE|Precision: 0.01<br>Value range: [0,100]|
|IMPRESSION<br>CLICK<br>CONVERSION<br>CONVERSION_SKAN<br>NOT_REAL_TIME_CONVERSION<br>RESULT|Precision: 1<br>Value range: [0,10000000000]|
|ROAS_PURCHASE<br>ROAS_IMPRESSION_ADS<br>ROAS_PURCHASE_SKAN<br>ROAS_COMPLETE_PAYMENT<br>ROAS_PURCHASES_ONSITE|Precision: 0.01<br>Value range: [0,100000]|
```

## Response
```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| Unique ID of the request.|
|data|object| Returned data.|
#|rule_ids |string[]| IDs of the rules that have been created or updated.|
```

### Example
```xcodeblock
(code JSON JSON)
{
    "code": 0,
    "message": "OK",
    "request_id": "2021072906384801024503714429014AE4",
    "data": {
        "rule_ids": [
            "6984697088064290818"
        ]
    }
}
(/code)
```


