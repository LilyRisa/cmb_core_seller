---
title: "Get audience details"
doc_id: 1739940507792385
path: "API Reference / Audience / Get audience details"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get audience details

Use this endpoint to obtain the details of specified audiences, such as current status and modification record. Details for both owned and shared audiences will be returned. You can use `is_creator` to check whether this advertiser is the owner of this audience.


 
>  <span style="color:DodgerBlue">**Note**</span><br>A maximum of 100 audiences can be queried per request.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/dmp/custom_audience/get/|/v1.3/dmp/custom_audience/get/|
|Request parameter data type|`advertiser_id`: number<br>`custom_audience_ids`: number[]|`advertiser_id`: string<br>`custom_audience_ids`: string[]|
|New request parameter |/|`history_size`: number|
|New response parameter|/|`shared`: boolean<br>`is_creator`: boolean<br>`create_time`: string: string<br>`owner_id`: string<br> `lookalike_spec`: object[]<br>` source_audience_id`: string<br>`include_source`: boolean<br>`mobile_os`: string<br>`placements`: string<br>`location_ids`: string[]<br>`audience_size`: string
|Response parameter data type|`audience_id`: number|`audience_id`: string|

```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|custom_audience_ids {Required}|string[]|A list of Audience IDs. Length range is ` [1, 100] `.|
|history_size|number|The size of the history data which the user requests.|
```

### Example

``` xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \\
--data-urlencode "advertiser_id=ADVERTISER_ID" \\
--data-urlencode "custom_audience_ids=[\"CUSTOM_AUDIENCE_IDS\"]" \\
https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/get/
(/code)
```

## Response

``` xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned Data.|
#|list |object[]|A list of audience details.|
##|audience_details |object|Audience data.|
###|audience_id |string|Audience ID.|
###|msg|string|Information about audience status.|
###|audience_sub_type|string| Audience sub type, indicating the type of ads that the audience can be used. Enum values: `NORMAL`: Normal audience. It can be used in non-R&F ads. `REACH_FREQUENCY`: Audience for Reach & Frequency. It can only be used in R&F ads.|
###|error_msg |string|Error information when creating or modifying an audience. |
###|is_valid |boolean|Audience availability. An audience becomes available after it has been processed. An audience becomes invalid after it is expired.  |
###|is_expiring|boolean|Whether an audience is expiring. When an audience is expiring, it will expire in less than 60 days. |
###|expired_time|string|Time when the audience expires, in the format of "YYYY-MM-DD HH:MM:SS".|
###|name |string|Audience name.|
###|rule |string|If this audience is an engagement audience, app activity audience or website traffic audience, this is the creation rule for it.|
###|is_auto_refresh |boolean|If this audience is an engagement audience / app activity audience or website traffic audience, this indicates whether the audience refreshes daily.|
###|shared |boolean|Whether this audience is shared with others. Enum values: `True`, `False`.
###|is_creator |boolean|Whether this advertiser is the owner of the audience.  Enum values: `true`, `false`. You can use `shared` and `is_creator` to check audience ownership and shared status. The rules are as follows:<br>1. `shared` = `true` and `is_creator` = `true`: The advertiser is the owner and the audience is shared to others. <br>2. `shared` = `true` and `is_creator` = `false`: The advertiser is NOT the owner and the audience is shared by others. <br>3. `shared` = `false` and `is_creator` = `true`: The advertiser is the owner and the audience has not been shared to others. 
###|owner_id| string | Advertiser ID of the audience creator. <ul><li>If `is_creator`=`true`, `owner_id` will be `advertiser_id` that you specified in the request.</li><li> If `is_creator`=`false`, `owner_id` will be the ID of the advertiser that created the audience.</li></ul>|
###|create_time|string|Time when the audience was created, in the format of "YYYY-MM-DD HH:MM:SS". 
###|type |string|Audience type. Enum values:<br><ul><li>`Customer File Audience` ([Customer File Audience](https://ads.tiktok.com/help/article?aid=6721963343558475781)). </li><li>`Engagement Audience` ([Engagement Audience](https://ads.tiktok.com/help/article?aid=6721963298155134982)). </li><li>`App Activity Audience` ([App Activity Audience](https://ads.tiktok.com/help/article?aid=6721962999092871173)). </li><li>`Website Audience` ([Website Traffic Audience](https://ads.tiktok.com/help/article?aid=6721962903081058309)). </li><li>`Lookalike Audience` ([Lookalike Audience](https://ads.tiktok.com/help/article?aid=6746381707865227270)).</li><li>`Lead Generation `([Lead Generation Audience](https://ads.tiktok.com/help/article/lead-generation-audience?lang=en))</li><li>`Rule Business Account` ([Business Account Audience](https://ads.tiktok.com/help/article?aid=10011405&redirected=1))</li><li>`Rule ad shop` ([Shop Activity Audience](https://ads.tiktok.com/help/article/shop-retargeting?lang=en))</li><li>`Partner` (Partner Audience)</li><li> `Challenge` (Challenge Audience) </li><li>`Preferred Population` (Premium Audience)</li></ul><p><span style="color:darkred"><b>Note</b></span>: Currently, Partner Audience, Challenge Audience and Premium Audience cannot be created through the API.|
###|cover_num |number|TikTok coverage, or number of matched users on TikTok.|
###|calculate_type {+Conditional}|string |Encryption type. For enum values, see [Enumeration - Encryption Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
###|lookalike_spec |object[]|Lookalike audience-specific information.<br>Returned only when the audience is a lookalike audience.|
####|source_audience_id|string|Source (seed) audience ID.  |
####|include_source|boolean|Whether to include the source audience in the new lookalike audience.|
####|mobile_os|string|Device operating systems. Enum values support `ALL` for both `ANDROID` and `IOS`. See [Enumeration-Audience Management-Lookalike System](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for details.|
####|placements|string|The apps where you want to deliver your ads. Enum values: `TikTok`, `TopBuzz & BuzzVideo`, `Pangle`. See [Enumeration - Audience Management-Lookalike Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for details.|
####|location_ids|string[]|IDs of the locations that you want to target. For enum values, see [Enumeration-Lookalike Locations](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
####|audience_size|string|Size of lookalike audience. Enum values: `NARROW`, `BALANCED` and `BROAD`.|
##|audience_history |object[]|List of audience modification history. |
###|action |string|Modification made. To learn about the meaning of the values, see <strong>"Mapping between `action` value and operation via /dmp/custom_audience/update/"</strong> section below.|
###|action_detail |string|Modification result. |
###|editor |string|Operator used. If modification was made through the TikTok API for Business, the value is "open_api".|
###|msg |string|If the operation is partial success, the information will be shown <br>here.|
###|opt_time |string|The operation time. Formats such as：2019-04-14 06:52:56.|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "123",
    "data": {
        "list": [
            {
                "audience_history": [
                    {
                        "msg": null,
                        "action_detail": "6489086",
                        "editor": "open_api",
                        "opt_time": "2022-05-26 14:12:12",
                        "action": "create"
                    }
                ],
                "audience_details": {
                    "audience_id": "123",
                    "is_expiring": false,
                    "is_auto_refresh": false,
                    "is_creator": true,
                    "expired_time": "2023-09-07 18:34:20",
                    "is_valid": true,
                    "shared": true,
                    "create_time": "2022-05-26 14:12:12",
                    "msg": null,
                    "error_msg": null,
                    "type": "Lookalike Audience",
                    "audience_sub_type": "NORMAL",
                    "calculate_type": null,
                    "cover_num": 6489086,
                    "name": "CUSTOM_AUDIENCE_NAME",
                    "rule": "{\"inclusions\": null, \"exclusions\": null}",
					"owner_id": "{{owner_id}}",
                     "lookalike_spec": null
                }
            }
        ]
    }
}
(/code);
```
### Mapping between `action` value and operation via /dmp/custom_audience/update/
``` xtable
| <code>action</code> value{20%} | Description{35%} | Corresponding operation via /dmp/custom_audience/update/{45%} |
|---|---|---|
| `modify` | The name of the Custom Audience has been edited. | `custom_audience_name` is passed |
| `upload - replace` | Files have been uploaded to replace the Customer File audience. | `file_paths` is passed and `action` is set as `REPLACE` or not passed |
| `upload - add` | Files have been uploaded to expand the Customer File audience. | `file_paths` is passed and `action` is set as `APPEND` |
| `upload remove` | Files have been uploaded to remove the Customer File audience. | `file_paths` is passed and `action` is set as `REMOVE` |
| `convert_to_rf` | The Custom Audience has been enabled to be used in Reach & Frequency campaigns. | `audience_sub_type` is passed and set as `REACH_FREQUENCY` |
``` 
