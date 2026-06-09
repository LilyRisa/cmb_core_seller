---
title: "Get the application log of audiences"
doc_id: 1758615737388033
path: "API Reference / Audience / Get the application log of audiences"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the application log of audiences

Use this endpoint to get the latest application log of custom audiences. Note that this endpoint will only get the log of apply operation (`action_mode` = `Apply`).
 

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/apply/log/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|custom_audience_ids{Required} |string[]|A list of custom audience IDs. <br><p><span style="color:darkred"><b>Note</b></span><br>The advertiser must be the owner of the `custom_audience_ids`. Otherwise, an error will occur. You can use the `is_creator` field returned in [/dmp/custom_audience/list/](https://ads.tiktok.com/marketing_api/docs?id=1739940506015746) and [/dmp/custom_audience/get/](https://ads.tiktok.com/marketing_api/docs?id=1739940507792385) to check whether this advertiser is the owner of this audience.
|page|number|Current page number. Default value: 1.
|page_size|number|Page size. Default value: 10.
|timezone|string|The timezone of returned value. For enum values, see [Appendix - Time Zone](https://ads.tiktok.com/marketing_api/docs?id=1737586324313089).  <br>If not specified, UTC will be used.
```

### Example
```xcodeblock
(code)
curl --location -g --request GET 'https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/apply/log?advertiser_id="123"&custom_audience_ids=["123"]' \
--header 'Access-Token: 123' \
--data-raw ''

(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|request_id |string|The log ID of a request, which uniquely identifies the request.|
|data |object|Returned Data. |
#|advertiser_id|string|Advertiser ID.
#|list|list|Array of application data.
##|audience_id|string|Audience ID.
##|adgroup_id|string|Ad group ID.
##|adgroup_name|string|Ad group name.
##|usage_mode|string|Audience's usage mode in the ad group. Enum values: `Include`, `Exclude`. 
##|editor|string|Operator. 
##|action_timestamp|string|Time when the action took place, in the format of "YYYY-MM-DD HH:MM:SS".
#|page_info |object | Pagination information. |
##|page |number | Current page number. |
##|page_size |number | Paging size. |
##|total_number |number | Total number of results. |
##|total_page |number |Total pages of results. |
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
        "advertiser_id": "123",
        "page_info": {
            "total_number": 8,
            "page_size": 10,
            "page": 1,
            "total_page": 1
        },
        "list": [
            {
                "audience_id": "123",
                "adgroup_name": "123",
                "action_timestamp": "2022-06-15 11:31:00",
                "editor": "123",
                "usage_mode": "Include",
                "adgroup_id": "123"
            },
            {
                "audience_id": "123",
                "adgroup_name": "123",
                "action_timestamp": "2023-01-23 22:49:06",
                "editor": "123",
                "usage_mode": "Exclude",
                "adgroup_id": "123"
            }
        ]
    }
}

(/code);
```

