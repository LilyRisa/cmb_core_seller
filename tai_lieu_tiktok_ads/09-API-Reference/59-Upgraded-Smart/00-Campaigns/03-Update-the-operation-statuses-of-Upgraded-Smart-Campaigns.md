---
title: "Update the operation statuses of Upgraded Smart+ Campaigns"
doc_id: 1843312888885314
path: "API Reference / Upgraded Smart+ / Campaigns / Update the operation statuses of Upgraded Smart+ Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update the operation statuses of Upgraded Smart+ Campaigns

Use this endpoint to enable, pause, or delete Upgraded Smart+ Campaigns.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/status/update/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed value: `application/json`.  |
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| campaign_ids {Required} | string[] | The IDs of the campaigns to operate on.<br><br>To obtain campaign IDs, use [/smart_plus/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1843312818332930).<br><br>Size range:  1-20. |
| operation_status {Required} | string | The operation to perform on the campaigns.<br><br>Enum values: `DELETE` (delete),` DISABLE` (pause), `ENABLE` (enable).<br><br><p><span style="color:darkred"><b>Note</b></span>: The status of deleted campaigns cannot be modified.</p> |
| postback_window_mode | string | Valid only when the following conditions are all met:<ul><li>The campaigns (`campaign_ids`) are Dedicated Campaigns (`campaign_type` is `IOS14_CAMPAIGN`).</li><li>You specify `operation_status` as `DISABLE` at the same time in the request.</li><li>`postback_window_mode` has not been configured for the campaign.</li><li>You have enabled SKAN 4.0 for your App.</li></ul><br>The mode that defines which SKAN (SKAdNetwork) 4.0 postback you want to secure. Options with longer windows require more time to receive and, as a result, more time to release the campaign back to the available number.<br><br>Enum values:<ul><li>`POSTBACK_WINDOW_MODE1`: This option secures the first postback, which corresponds to the 0-2 day attribution window. The data can take up to 4 days to return, and the campaign will wait for 4 days to release the campaign quota.</li><li>`POSTBACK_WINDOW_MODE2`: This mode secures the first two postbacks, which corresponds to the 3-7 day attribution window. The data can take up to 13 days to return, and the campaign will wait for 13 days to release the campaign quota.</li><li>`POSTBACK_WINDOW_MODE3`: This mode secures all three postbacks, which corresponds to the 8-35 day attribution window. The data can take up to 41 days to return, and the campaign will wait for 41 days to release the campaign quota.</li></ul><br>To learn more about SKAN 4.0 attribution window configuration and campaign quota, see [Dedicated Campaigns](https://business-api.tiktok.com/portal/docs?id=1740029173531649).<br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>If you have set up Mobile Measurement Partner (MMP) Tracking with <b>Adjust</b>, <b>Airbridge</b>, <b>Appsflyer</b>, <b>Branch</b>, <b>Kochava</b>, or <b>Singular</b> for your App and your MMP SDK version is updated to a SKAN 4.0 supported SDK, you can transition your App to SKAN 4.0 on Events Manager. To learn about how to set up MMP tracking for your App, see [How to Set Up App Attribution in TikTok Ads Manager](https://ads.tiktok.com/help/article/set-up-app-attribution-tiktok-ads-manager). To learn more about how to transition your App to SKAN 4.0, see [About SKAN 4.0 and TikTok](https://ads.tiktok.com/help/article/about-skan-4-0-and-tiktok) and [How to transition to SKAN 4.0](https://ads.tiktok.com/help/article/how-to-transition-to-skan-4-0).</li><li>If `operation_status` is set to `ENABLE` or not passed, and you pass in this field at the same time, an error will occur.</li><li>If you pass in this field when you have not enabled SKAN 4.0 for your App (`app_id`), an error will occur.</li><li>If this field is not passed when `campaign_type` is set to `IOS14_CAMPAIGN`, `operation_status` is set to `DISABLE`, and you have enabled SKAN 4.0 for your App, this field will default to `POSTBACK_WINDOW_MODE1`.</li><li>The postback window mode configuration can be partially successful. If you provide `campaign_ids` for non-Dedicated Campaigns, or Dedicated Campaigns for which `postback_window_mode` has been configured, those campaigns will be ignored and their `postback_window_mode` will not be modified. Only the Dedicated Campaigns that meet the conditions mentioned above will have their `postback_window_mode` configured.</li><li>If you have enabled SKAN 4.0 for your App, ensure that you target devices running iOS 16.1 and later so that you can receive SKAN 4.0 postbacks. To only target iOS 16.1+ devices, set `min_ios_version` to `16.1` at the ad group level.</li></ul></p> |
```

### Example

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/status/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_ids": ["{{campaign_ids}}"],
    "operation_status": "DISABLE"
}'
(/code)
```

## Response

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|campaign_list |object[]|A list of campaigns for which the operation was successful.|
##|campaign_id |string|The ID of the campaign for which the operation was successful.|
##|status |string|The current status of the campaign for which the operation was successful.<br><br>Enum values: `DISABLE` (paused), `ENABLE` (enabled), `DELETE` (deleted).|
##|postback_window_mode |string|The mode that determines which SKAN 4.0 postback you want to secure.<br>Enum values:<ul><li>`POSTBACK_WINDOW_MODE1`: This option secures the first postback, which corresponds to the 0-2 day attribution window. The data can take up to 4 days to return, and the campaign will wait for 4 days to release the campaign quota.</li><li>`POSTBACK_WINDOW_MODE2`: This mode secures the first two postbacks, which corresponds to the 3-7 day attribution window. The data can take up to 13 days to return, and the campaign will wait for 13 days to release the campaign quota.</li><li>`POSTBACK_WINDOW_MODE3`: This mode secures all three postbacks, which corresponds to the 8-35 day attribution window. The data can take up to 41 days to return, and the campaign will wait for 41 days to release the campaign quota.</li></ul>|
#|error_list |object[]|A list of errors and the corresponding failed campaigns.|
##|campaign_id |string|The ID of the campaign for which the operation failed.|
##|error_message |string|The error message associated with the failed campaign.|
```

### Example

```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "campaign_list": [
            {
                "campaign_id": "{{campaign_id}}",
                "status": "DISABLE"
            }
        ],
        "error_list": []
    }
}
(/code)
```
