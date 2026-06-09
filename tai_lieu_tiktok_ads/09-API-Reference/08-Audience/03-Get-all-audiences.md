---
title: "Get all audiences"
doc_id: 1739940506015746
path: "API Reference / Audience / Get all audiences"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get all audiences

Use this endpoint to get all audiences. The returned list is paginated with a maximum paging size of 100. For each audience, information like ID, name, type, creation time, expiration time will be returned in the response. This endpoint returns both owned and shared audiences. You can use `is_creator` to check whether this advertiser is the owner of this audience.

To get more details about an audience, such as current status and modification record, use [/dmp/custom_audience/get/](https://ads.tiktok.com/marketing_api/docs?id=1739940507792385)  instead.  

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/dmp/custom_audience/list/|/v1.3/dmp/custom_audience/list/|
|Request parameter data type|`advertiser_id`: number<br>|`advertiser_id`: string<br>|
|Response parameter name|`type`<br>|`audience_type` <br>|
|Response parameter data type|`audience_id`: number<br>|`audience_id`: string<br>|
|New request parameter|/|`custom_audience_ids`: string[]<br>|
|New response parameter|/|`shared`: boolean<br>`is_creator`: boolean|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/list/

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
|custom_audience_ids |string[]|A list of Audience IDs. Length range is [1, 100].|
|page |number|Current page number. Default value: `1`. Value range: ≥ 1.|
|page_size |number|Page size. Defaults is `10`. Value range: 1-100.|
```

### Example

``` xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \\
--data-urlencode "advertiser_id=ADVERTISER_ID" \\
--data-urlencode "page=PAGE" \\
--data-urlencode "page_size=PAGE_SIZE" \\
https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/list/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data.|
#|list |object[]|A list of Audience. |
##|shared |boolean|Whether this audience is shared with others. Enum values: `True`, `False`.
##|is_creator |boolean|Whether this advertiser is the owner of the audience.  Enum values: `true`, `false`. You can use `shared` and `is_creator` to check audience ownership and shared status. The rules are as follows:<br>1. `shared` = `true` and `is_creator` = `true`: The advertiser is the owner and the audience is shared to others. <br>2. `shared` = `true` and `is_creator` = `false`: The advertiser is NOT the owner and the audience is shared by others. <br>3. `shared` = `false` and `is_creator` = `true`: The advertiser is the owner and the audience has not been shared to others. 
##|audience_id |string|Audience ID.|
##|cover_num |number|Coverage, or number of matched users on TikTok.|
##|create_time |string|Time when the audience was created. GMT time.|
##|is_valid |boolean|Audience availability. An audience becomes available after it has been processed. An audience becomes invalid after it is expired.  |
##|is_expiring|boolean|Whether an audience is expiring. When an audience is expiring, it will expire in less than 60 days. |
##|expired_time|string|Time when the audience expires, in the format of "YYYY-MM-DD HH:MM:SS".|
##|name |string|Audience name.|
##|audience_type |string|Audience type. Enum values:<br><ul><li>`Customer File Audience` ([Customer File Audience](https://ads.tiktok.com/help/article?aid=6721963343558475781)). </li><li>`Engagement Audience` ([Engagement Audience](https://ads.tiktok.com/help/article?aid=6721963298155134982)). </li><li>`App Activity Audience` ([App Activity Audience](https://ads.tiktok.com/help/article?aid=6721962999092871173)). </li><li>`Website Audience` ([Website Traffic Audience](https://ads.tiktok.com/help/article?aid=6721962903081058309)). </li><li>`Lookalike Audience` ([Lookalike Audience](https://ads.tiktok.com/help/article?aid=6746381707865227270)).</li><li>`Lead Generation `([Lead Generation Audience](https://ads.tiktok.com/help/article/lead-generation-audience?lang=en))</li><li>`Rule Business Account` ([Business Account Audience](https://ads.tiktok.com/help/article?aid=10011405&redirected=1))</li><li>`Rule ad shop` ([Shop Activity Audience](https://ads.tiktok.com/help/article/shop-retargeting?lang=en))</li><li>`Partner` (Partner Audience)</li><li> `Challenge` (Challenge Audience) </li><li>`Preferred Population` (Premium Audience)</li></ul><p><span style="color:darkred"><b>Note</b></span>: Currently, Partner Audience, Challenge Audience and Premium Audience cannot be created through the API. |
##|calculate_type|string |Encryption type, only applies to customer file audience. For enum values, see [Enumeration - Encryption Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
#|page_info |object | Pagination information. |
##|page |number | Current page number. |
##|page_size |number | Paging size. |
##|total_number |number | Total number of results. |
##|total_page |number |Total pages of results. |
|request_id |string|The log ID of a request, which uniquely identifies the request.|
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "123",
    "data": {
        "page_info": {
            "page_size": 10,
            "total_number": 1,
            "page": 1,
            "total_page": 1
        },
        "list": [
            {
                "cover_num": 6489086,
                "is_valid": false,
                "calculate_type": null,
                "is_creator": false,
                "audience_id": "123",
                "name": "CUSTOM_AUDIENCE_NAME",
                "is_expiring": false,
                "create_time": "2022-05-26 22:12:12",
                "audience_type": "Lookalike Audience",
                "expired_time": "2023-09-08 02:34:20",
                "shared": true
            }
        ]
    }
}
(/code);
```
