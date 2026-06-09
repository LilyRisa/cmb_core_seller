---
title: "Get the results of an asynchronous copy task for an Upgraded Smart+ Campaign"
doc_id: 1866529943741441
path: "API Reference / Upgraded Smart+ / Campaigns / Get the results of an asynchronous copy task for an Upgraded Smart+ Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the results of an asynchronous copy task for an Upgraded Smart+ Campaign

Use this endpoint to check the results of an asynchronous copy task for an Upgraded Smart+ Campaign.

We recommend waiting approximately five minutes after creating a task using [/smart_plus/campaign/copy/task/create/](https://business-api.tiktok.com/portal/docs/create-an-asynchronous-copy-task-for-an-upgraded-smart-campaign/v1.3) before checking the results.

> <span style="color:DodgerBlue">**Note**</span><br>
> - Asynchronous Campaign Copy API is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.
> - [Global rate limits](https://business-api.tiktok.com/portal/docs?id=1740029171730433#item-link-Global%20rate%20limits) are applicable to this endpoint.

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/copy/task/check/


**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162). |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID.|
| task_id {Required} | string | ID of the asynchronous campaign copy task.<br><br>To get the task ID, use [/smart_plus/campaign/copy/task/create/](https://business-api.tiktok.com/portal/docs/create-an-asynchronous-copy-task-for-an-upgraded-smart-campaign/v1.3).|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/copy/task/check/?advertiser_id={{advertiser_id}}&task_id={{task_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs/return-codes-appendix/v1.3).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs/return-codes-appendix/v1.3).|
|request_id|string| The log ID of the request, which uniquely identifies a request.|
|data|object|Returned data.|
#| task_status | string | The status of the asynchronous campaign copy task.<br><br>Enum values:<ul><li>`RUNNING`: The task is being processed.</li><li>`SUCCESS`: The task has been processed. Check the `task_result` to see if the task has succeeded.</li><li>`FAILURE`: The task fails to be processed.</li></ul>|
#| task_info | object | Overview of the task result.|
##| total_ad_count | number | The total number of ads that you tried to copy.|
##| success_ad_count | number | The number of ads that have been successfully copied.|
#| task_result | object | The details of the task result.|
##| campaign_id | string | The ID of the newly created campaign.|
##| campaign_name | string | The name of the newly created campaign.<br><br>If the campaign copy process fails, this field is still returned to indicate the campaign that was not successfully copied.|
##| campaign_error_infos | string[] | The errors encountered during the campaign copy process.<br><br>If no errors occurred, the value of this field is an empty list (`[]`).|
##| adgroup_result_list | object[] | The details of the ad group copy results.|
###| adgroup_id | string | The ID of the newly created ad group.|
###| adgroup_name | string | The name of the newly created ad group.<br><br>If the ad group copy process fails, this field is still returned to indicate the ad group that was not successfully copied.|
###| total_ad_count | number | The number of ads that you tried to copy into the new ad group.|
###| success_ad_count | number | The number of ads that have been successfully copied into the new ad group.|
###| adgroup_error_list | string[] | The errors encountered during the ad group copy process.<br><br>If no errors occurred, the value of this field is an empty list (`[]`).|
###| ad_status | string | The result of copying the ads from the source ad group to the newly created ad group.<br><br>Enum values:<ul><li>`ALL_SUCCESS`: All ads from the source ad group were successfully copied.</li><li>`PARTIAL_SUCCESS`: Some or all ads from the source ad group failed to be copied.</li></ul>|
###| ad_result_list | object[] | The details of the ad copy results.|
####| is_success | boolean | Whether the copy of the source ad was successful.<br><br>Supported values: `true`, `false`.|
####| smart_plus_ad_id | string | The ID of the newly created ad.|
####| ad_name | string | The name of the newly created ad.<br><br>If the ad copy process fails, this field is still returned to indicate the ad that was not successfully copied.|
####| ad_error_list | string[] | The errors encountered during the ad copy process.<br><br>If no errors occurred, the value of this field is an empty list (`[]`).|
```
### Example
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "task_info": {
            "success_ad_count": 1,
            "total_ad_count": 1
        },
        "task_result": {
            "adgroup_result_list": [
                {
                    "ad_result_list": [
                        {
                            "ad_error_list": [],
                            "ad_name": "{{ad_name}}",
                            "is_success": true,
                            "smart_plus_ad_id": "{{smart_plus_ad_id}}"
                        }
                    ],
                    "ad_status": "ALL_SUCCESS",
                    "adgroup_error_list": [],
                    "adgroup_id": "{{adgroup_id}}",
                    "adgroup_name": "{{adgroup_name}}",
                    "success_ad_count": 1,
                    "total_ad_count": 1
                }
            ],
            "campaign_error_infos": [],
            "campaign_id": "{{campaign_id}}",
            "campaign_name": "{{campaign_name}}"
        },
        "task_status": "SUCCESS"
    }
}
(/code)
```


