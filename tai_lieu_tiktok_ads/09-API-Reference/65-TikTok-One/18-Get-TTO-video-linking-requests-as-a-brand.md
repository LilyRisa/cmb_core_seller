---
title: "Get TTO video linking requests as a brand"
doc_id: 1824450033584129
path: "API Reference / TikTok One / Get TTO video linking requests as a brand"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get TTO video linking requests as a brand

Use this endpoint to retrieve the requests of a TikTok One (TTO) Creator Marketplace account for linking creators' public videos to TTO Creator Marketplace campaigns.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/campaign/link/status/

**Method** GET

**Header**

```xtable
|Field{32%}|Type{15%}|Description{53%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TikTok One Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
```

**Parameters**

``` xtable
|Field{32%}|Type{15%}|Description{53%}|
|--- |--- |--- |
| tto_tcm_account_id {Required} | string | The ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
| campaign_ids | string[] | The IDs of existing TTO Creator Marketplace campaigns to filter the results by. <br><br> To obtain a list of TTO Creator Marketplace campaigns, use [/tto/tcm/campaign/](https://business-api.tiktok.com/portal/docs?id=1815693562720258). <br><br>Max size: 50. |
| handle_names | string[] | The creator handle names to filter the results by. <br><br>Max size: 50. |
| campaign_type | string | The type of the TTO Creator Marketplace campaign.<br><br>Enum values:<ul><li>`CAMPAIGN`: A campaign with a [campaign-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Campaign-level%20invite%20link).</li><li>`BRAND_LINK`: A campaign with a [brand-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Brand-level%20invite%20link).</li></ul> If this field is not specified, all campaigns will be returned.|
| page | integer | Current page number. <br><br>Value range: ≥ 1.<br>Default value: 1.  |
| page_size | integer | Page size. <br><br>Value range: 1-50.  <br>Default value: 10.|
```
### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/campaign/link/status/?tto_tcm_account_id={{tto_tcm_account_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

``` xtable
|Field{32%}|Type{15%}|Description{53%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| video_infos | object[] | Information about the linking requests for videos. |
##| video_id | string | The ID of a creator's public video to be linked to the campaign. |
##| status | string | The status of the request.  <br><br>Enum values:<ul><li>`IN_REVIEW`: Sent to the creator and pending the creator's acceptance or rejection.</li><li> `APPROVE`: Approved by the creator.</li><li>`REJECTED`: Rejected by the creator.</li><li> `REVOKE`: Revoked.</li></ul> |
##| campaign_id | string | The ID of the TTO Creator Marketplace campaign. |
##| last_reminded_timestamp | string | The last time when the linking request or the reminder notification was sent to the creator, in [ISO-8601](https://en.wikipedia.org/wiki/ISO_8601) format.<br><br>Example: `2025-01-01T00:00:01Z`. |
##| number_of_requests | number | The number of requests for linking the video (`video_id`) to the campaign (`campaign_id`) that you have sent to the creator. <br><br>Example: 1. |
##| campaign_type | string | The type of the TTO Creator Marketplace campaign.<br><br>Enum values:<ul><li>`CAMPAIGN`: A campaign with a [campaign-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Campaign-level%20invite%20link).</li><li>`BRAND_LINK`: A campaign with a [brand-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Brand-level%20invite%20link).</li></ul> |
##| link_request_id | string | ID of the linking request. <br><br>You can share the ID with the invited creator so that the creator can use [/tto/creator/link/request/confirm/](https://business-api.tiktok.com/portal/docs?id=1837247744649345) to approve or reject the linking request.|
#| page_info | object | Pagination information. |
##| page | integer | Current page number. |
##| page_size | integer | Current page size. |
##| total_page | integer | Total number of pages. |
##| total_number | integer | Total number of campaigns. |
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
        "page_info": {
            "page": 1,
            "page_size": 10,
            "total_number": 2,
            "total_size": 1
        },
        "video_infos": [
            {
                "campaign_id": "{{campaign_id}}",
                "last_reminded_timestamp": "2025-02-07T21:44:57Z",
                "number_of_requests": 1,
                "status": "APPROVE",
                "campaign_type": "CAMPAIGN",
				"video_id": "{{video_id}}"
            },
            {
                "campaign_id": "{{campaign_id}}",
                "last_reminded_timestamp": "2025-02-07T21:12:44Z",
                "number_of_requests": 1,
                "status": "REVOKE",
                "campaign_type": "CAMPAIGN",
				"video_id": "{{video_id}}"
            }
        ]
    }
}
(/code)
```
