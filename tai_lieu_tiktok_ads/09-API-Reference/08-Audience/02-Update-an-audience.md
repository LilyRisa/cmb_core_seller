---
title: "Update an audience"
doc_id: 1739940572667906
path: "API Reference / Audience / Update an audience"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update an audience

Use this endpoint to update the details of an audience, like audience name and audience file.

The TikTok API for Business supports three types of audience modifications for customer file audience:  

1. `REPLACE` (Replace)：Replace previous (all) data files.
2. `APPEND` (New): Add new data files.
3. `REMOVE` (Remove): Remove specified data files.

## Notes
See the table below to learn about the restrictions on quota, multitasking, and supported scope for this endpoint.
 
``` xtable
| Type {30%}| Details{70%} |
|---|---|
| Quota | <ul><li> There is a quota of 24 Audience update operations (Append, Remove, Replace) any consecutive 24 hours. This limit is at audience level. </li><li>Only one file-based audience `replace` operation is allowed per day. This limit is at audience level. </li></ul>|
|Multitasking |<ul><li> Please wait until the current operation completes on an audience before re-trying. This limit is at audience level. </li><li> `Replace` operations will be rejected if there is an existing unfinished `replace` operation. </li></ul>|
| Supported scope | <ul><li> Only customer file audience supports changing the audience file.</li><li> For Engagement audience, App Activity audience, Website Traffic audience and Lookalike Audience, they only support updating audience name.</li><li>An expiring or expired audience cannot be modified. It can only be deleted. You can use `is_expiring` and `expired_time` to find out whether an audience is expiring or expired.</li><li>The `REPLACE`, `APPEND`, and `REMOVE` operations will fail if the audience size is less than 1,000 after the operation.</li><li>For `REMOVE` (Remove): You cannot remove all data files. </li></ul>|
| SLA |Upon making the API call, you'll immediately receive a success/error response. In case of success, our system will process the IDs asynchronously, for which the SLA is 48 hours.|
```


## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/dmp/custom_audience/update/|/v1.3/dmp/custom_audience/update/|
|Request parameter data type|`advertiser_id`: number<br>`custom_audience_id`: number<br>|`advertiser_id`: string<br>`custom_audience_id`: string<br>|
|New request parameter|/|`file_paths`: string[]<br>|
|Request parameter deprecated in v1.3|/|`file_path`: string<br>|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/update/

**Method** POST

**Header**

``` xtable
|Field{30%}|Data Type{10%}|Description{60%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `"application/json"`.|
```

**Parameters**

```xtable
|Field{30%}|Data Type{10%}|Description{60%}|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID.|
|custom_audience_id {Required}|string|Custom audience ID.|
|custom_audience_name {+Conditional}|string |New name for the audience. Length limit: 128 characters. You must pass in either this field or `file_paths`.|
|audience_sub_type|string| Audience sub type, indicating the type of ads that the audience can be used for. <br>The allowed enum value: `REACH_FREQUENCY`(To enable the audience to be used in Reach & Frequency campaigns). It means you can only convert audience sub type from `NORMAL` to `REACH_FREQUENCY`, but not the other way round. <br><p><span style="color:darkred"><b>Note</b></span>: <p><ul><li>If you update this field, changes to other data fields will be ignored.<li>If you want to update a Lookalike audience, you cannot change the `audience_sub_type` to `REACH_FREQUENCY`. Otherwise, an error will occur. |
|file_paths {+Conditional} |string[] |The files you upload to update the Customer File audience. You must pass in either this field or `custom_audience_name`. <br>The recommended size is <b><50</b> per request to ensure stability.<br>Once you pass in this field, you can specify the modification type (`APPEND`, `REMOVE` or `REPLACE`) via `action`.<br> The files must be encrypted with the same encryption method/type with the audience that you want to update.|
|action |string|Modification type.  This field is valid when `file_paths` is passed. Enum values: <ul><li>`APPEND`: Upload files to expand the Customer File audience. Only data not included in the original file will be added.</li><li>`REMOVE`: Upload files to remove the Customer File audience. Only the data that coincide with the original file will be removed. </li><li>`REPLACE`: Upload files to replace the Customer File audience. All the data in the original file will be replaced.</li></ul> Default value: `REPLACE`. <br><p><span style="color:darkred"><b>Note</b></span>: The `APPEND`, `REMOVE` and `REPLACE` operations will fail if the audience size is less than 1,000 after the operation.|
```

### Example

``` xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \\
-d '{
    "action": "ACTION", 
    "advertiser_id": "ADVERTISER_ID", 
    "custom_audience_id": "CUSTOM_AUDIENCE_ID", 
    "file_paths": ["FILE_PATHS"]
}' \\
https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/update/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data. <br>This field will be an empty object (`{}`).|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code);
```

