---
title: "Get the results of an asynchronous copy task for a Manual Campaign"
doc_id: 1788434463507458
path: "API Reference / Campaign / Get the results of an asynchronous copy task for a Manual Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the results of an asynchronous copy task for a Manual Campaign

Use this endpoint to check the results of an asynchronous copy task for a Manual Campaign.

We recommend waiting approximately one minute after creating a task using [/campaign/copy/task/create/](https://business-api.tiktok.com/portal/docs?id=1788434394151938) before checking the results. 

To check the results of an asynchronous copy task for an Upgraded Smart+ Campaign, use [/smart_plus/campaign/copy/task/check/](https://business-api.tiktok.com/portal/docs/get-the-results-of-an-asynchronous-copy-task-for-an-upgraded-smart-campaign/v1.3) instead.

><span style="color:DodgerBlue">**Note**</span><br>
>  <ul><li>Asynchronous Campaign Copy API is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>The rate limits for this endpoint are 2 queries per second (QPS) and 60 queries per minute (QPM) per developer app. [Global rate limits](https://business-api.tiktok.com/portal/docs?id=1740029171730433#item-link-Global%20rate%20limits) are not applicable to this endpoint. </li></ul>

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/campaign/copy/task/check/

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
| task_id {Required} | string | ID of the asynchronous campaign copy task.<br><br>To get the task ID, use [/campaign/copy/task/create/](https://business-api.tiktok.com/portal/docs?id=1788434394151938). |
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/campaign/copy/task/check/' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "task_id": "{{task_id}}"
}'
(/code)
```
## Response

``` xtable
|Field{35%}|Type{15%}|Description{50%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| task_status| string | The status of the asynchronous campaign copy task. <br><br>Enum values: <ul><li>`RUNNING`: The task is being processed. </li><li>`SUCCESS`: The task has been processed. Check the `task_result` to see if the task has succeeded.</li><li>`FAILURE`: The task fails to be processed.</li></ul>|
#|task_info | object | Overview of the task result. |
##| total_ad_count | number | The total number of ads that you tried to copy. |
##| success_ad_count | number | The number of ads that have been successfully copied. |
##| total_smart_creative_adgroup_count | number | The total number of Smart Creative ad groups that you tried to copy. |
##| success_smart_creative_adgroup_count | number | The number of Smart Creative ad groups that have been successfully copied. |
#| task_result | object | The details of the task result. |
##| campaign_id | string | The ID of the newly created campaign. |
##| campaign_name| string | The name of the newly created campaign. <br><br> If the campaign copy process fails, this field will still be returned to indicate the campaign that was not successfully copied. |
##| campaign_error_infos | string[] | The errors encountered during the campaign copy process. <br><br> If no errors occurred, the value of this field will be an empty list (`[]`). |
##| adgroup_result_list | object[] | The details of the ad group copy results. |
###| adgroup_id | string | The ID of the newly created ad group. |
###| adgroup_name | string | The name of the newly created ad group. <br><br>If the ad group copy process fails, this field will still be returned to indicate the ad group that was not successfully copied. 
###| total_ad_count | number | The number of ads that you tried to copy into the new ad group. |
###| success_ad_count | number | The number of ads that have been successfully copied into the new ad group. |
###| adgroup_error_list | string[] | The errors encountered during the ad group copy process. <br><br> If no errors occurred, the value of this field will be an empty list (`[]`). |
###| ad_status | string | The result of copying the ads from the original ad group to the newly created ad group. <br><br>Enum values: <ul><li>`ALL_SUCCESS`: All ads from the original ad group were successfully copied. </li><li>`PARTIAL_SUCCESS`: Some or all ads from the original ad group failed to be copied.</li></ul> |
###| is_smart_creative | boolean | Whether the new ad group is a Smart Creative ad group. <br><br>Enum values: `true`, `false`. |
###| smart_creative_result | object | Returned only when `is_smart_creative` is `true`.<br><br> The result details of the copy of the ad creatives in the original Smart Creative ad group. |
####| is_success | boolean | Whether the copy of the ad creatives used to generate Smart Creative ads in the original Smart Creative ad group was successful. <br><br>Enum values: `true`, `false`. |
####| smart_creative_error_list | string[] | The errors encountered during the copy of the ad creatives in the original Smart Creative ad group. <br><br> If no errors occurred, the value of this field will be an empty list (`[]`). |
###| ad_result_list | object[] | The details of the ad copy results. |
####| is_success | boolean | Whether the copy of the original ad was successful. <br><br>Enum values: `true`, `false`. |
####| ad_id | string | The ID of the newly created ad. |
####| ad_name | string | The name of the newly created ad. <br><br>If the ad copy process fails, this field will still be returned to indicate the ad that was not successfully copied. |
####| ad_error_list | string[] | The errors encountered during the ad copy process. <br><br> If no errors occurred, the value of this field will be an empty list (`[]`). |
```
### Example
The results of the copy task may vary based on the type of the original ad groups and the outcome of the task. The following code examples demonstrate the various scenarios:

#### Successful copy task for non-ACO, non-Smart Creative ad groups
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "task_result": {
            "campaign_name": "{{campaign_name}}",
            "adgroup_result_list": [
                {
                    "total_ad_count": 1,
                    "adgroup_id": "{{adgroup_id}}",
                    "adgroup_name": "{{adgroup_name}}",
                    "adgroup_error_list": [],
                    "success_ad_count": 1,
                    "ad_result_list": [
                        {
                            "ad_name": "{{ad_name}}",
                            "is_success": true,
                            "ad_error_list": [],
                            "ad_id": "{{ad_id}}"
                        }
                    ],
                    "is_smart_creative": false,
                    "ad_status": "ALL_SUCCESS"
                }
            ],
            "campaign_id": "{{campaign_id}}",
            "campaign_error_infos": []
        },
        "task_status": "SUCCESS",
        "task_info": {
            "total_ad_count": 1,
            "total_smart_creative_adgroup_count": 0,
            "success_smart_creative_adgroup_count": 0,
            "success_ad_count": 1
        }
    }
}
(/code)
```
#### Successful copy task for Smart Creative ad groups
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "task_info": {
            "success_ad_count": 0,
            "success_smart_creative_adgroup_count": 1,
            "total_ad_count": 0,
            "total_smart_creative_adgroup_count": 1
        },
        "task_result": {
            "campaign_id": "{{campaign_id}}",
            "campaign_name": "{{campaign_name}}",
            "campaign_error_infos": [],
            "adgroup_result_list": [
                {
                    "is_smart_creative": true,
                    "adgroup_name": "{{adgroup_name}}",
                    "adgroup_id": "{{adgroup_id}}",
                    "smart_creative_result": {
                        "smart_creative_error_list": [],
                        "is_success": true
                    },
                    "ad_status": "ALL_SUCCESS",
                    "adgroup_error_list": [],
                    "ad_result_list": [],
                    "success_ad_count": 0,
                    "total_ad_count": 0
                }
            ]
        },
        "task_status": "SUCCESS"
    }
}
(/code)
```
#### Partially successful copy task for non-ACO, non-Smart Creative ad groups
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "task_info": {
            "success_ad_count": 1,
            "success_smart_creative_adgroup_count": 0,
            "total_ad_count": 2,
            "total_smart_creative_adgroup_count": 0
        },
        "task_result": {
            "campaign_id": "{{campaign_id}}",
            "campaign_name": "{{campaign_name}}",
            "campaign_error_infos": [],
            "adgroup_result_list": [
                {
                    "is_smart_creative": false,
                    "adgroup_name": "{{adgroup_name}}",
                    "adgroup_id": "{{adgroup_id}}",
                    "ad_status": "ALL_SUCCESS",
                    "adgroup_error_list": [],
                    "ad_result_list": [
                        {
                            "ad_error_list": [],
                            "ad_id": "{{ad_id}}",
                            "ad_name": "{{ad_name}}",
                            "is_success": true
                        }
                    ],
                    "success_ad_count": 1,
                    "total_ad_count": 1
                },
                {
                    "is_smart_creative": false,
                    "adgroup_name": "{{adgroup_name}}",
                    "adgroup_id": "0",
                    "ad_status": "PARTIAL_SUCCESS",
                    "adgroup_error_list": [],
                    "ad_result_list": [
                        {
                            "ad_error_list": [
                                "Unable to access image. Make sure your account has access and it's in a supported format (JPG, JPEG, PNG, GIF)."
                            ],
                            "ad_id": null,
                            "ad_name": "{{ad_name}}",
                            "is_success": false
                        }
                    ],
                    "success_ad_count": 0,
                    "total_ad_count": 1
                }
            ]
        },
        "task_status": "SUCCESS"
    }
}
(/code)
```
#### Unsuccessful copy task for non-ACO, non-Smart Creative ad groups
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "task_info": {
            "success_ad_count": 0,
            "success_smart_creative_adgroup_count": 0,
            "total_ad_count": 1,
            "total_smart_creative_adgroup_count": 0
        },
        "task_result": {
            "campaign_id": null,
            "campaign_name": "{{campaign_name}}",
            "campaign_error_infos": [],
            "adgroup_result_list": [
                {
                    "is_smart_creative": false,
                    "adgroup_name": "{{adgroup_name}}",
                    "adgroup_id": "0",
                    "ad_status": "PARTIAL_SUCCESS",
                    "adgroup_error_list": [],
                    "ad_result_list": [
                        {
                            "ad_error_list": [
                                "Unable to find \"Identity_ID\". Try again.",
                                "Update failed due to system error. Please try again."
                            ],
                            "ad_id": null,
                            "ad_name": "{{ad_name}}",
                            "is_success": false
                        }
                    ],
                    "success_ad_count": 0,
                    "total_ad_count": 1
                }
            ]
        },
        "task_status": "SUCCESS"
    }
}
(/code)
```

