---
title: "Get the campaign labels of an ad account"
doc_id: 1851286489283585
path: "API Reference / Tools / Get the campaign labels of an ad account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the campaign labels of an ad account

Use this endpoint to retrieve the list of campaign labels within an ad account.

## Request

**Endpoint** 
https://business-api.tiktok.com/open_api/v1.3/campaign_label/get/

**Method** 
GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID. |
| campaign_label_ids | string[] | A list of campaign label IDs.<br><br>Max size: 50.<br><br>Each label ID must be a 19-digit numeric string.<br><br><p><span style="color:darkred"><b>Note</b></span>: This filter is only supported in synchronous basic reports.</p> |
| campaign_label_names | string[] | A list of campaign label names.<br><br>Fuzzy matching is supported.<br><br>Max size: 10. |
| campaign_label_types | string[] | A list of campaign label types.<br><br>Enum values:<ul><li>`GENERAL`: General label.</li><li>`MARKETING_EVENT`: Marketing event label.</li></ul> |
| page | number | Current page number.<br><br>Default value: 1.<br><br>Value range: ≥1. |
| page_size | number | Page size.<br><br>Default value: 10.<br><br>Value range: 1-1,000. |
```

### Example

```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/campaign_label/get/?advertiser_id={{advertiser_id}}&page=1&page_size=1000' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response
```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|list|object[]|Details of the campaign labels within an ad account.|
##|campaign_label_id|string|The ID of the campaign label.|
##|campaign_label_name|string|The name of the campaign label.|
##|campaign_label_type|string|The type of the campaign label.<br><br>Enum values:<ul><li>`GENERAL`: General label.</li><li>`MARKETING_EVENT`: Marketing event label.</li></ul>|
##|campaign_label_color|string|The color of the campaign label.|
##|create_time|string|The time when the campaign label was created, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0).<br><br>Example: `2025-01-01 00:00:01`.|
#|page_info|object|Pagination information.|
##|page|number|Current page number.|
##|page_size|number|Page size.|
##|total_number|number|Total number of results.|
##|total_page|number|Total pages of results.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "campaign_label_color": "pinkFilled",
                "campaign_label_id": "{{campaign_label_id}}",
                "campaign_label_name": "{{campaign_label_name}}",
                "campaign_label_type": "MARKETING_EVENT",
                "create_time": "{{create_time}}"
            },
            {
                "campaign_label_color": "blueOutlined",
                "campaign_label_id": "{{campaign_label_id}}",
                "campaign_label_name": "{{campaign_label_name}}",
                "campaign_label_type": "GENERAL",
                "create_time": "{{create_time}}"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_number": 2,
            "total_page": 1
        }
    }
}
(/code)
```

