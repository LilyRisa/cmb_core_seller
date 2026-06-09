---
title: "Get the activity log of a Business Center"
doc_id: 1820767460168705
path: "API Reference / BC Management / Get the activity log of a Business Center"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the activity log of a Business Center

Use this endpoint to get the activity log of Business Centers. The activity log records the operation history of the Business Center.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/changelog/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. <br><br>For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| bc_id {Required} | string | Business Center ID. <br><br>To obtain the list of Business Centers that you can access, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826). |
| filtering | object | Filtering conditions. |
#| start_date | string | Query start date, in the format of `YYYY-MM-DD` (UTC+0). <br><br><ul><li>You need to specify both `start_date` and `end_date` or neither. </li><li>If both `start_date` and `end_date` are not specified, the activity log for the past seven days will be returned. </li></ul><br>Recommended date range: six months. <br><br>Example: `2025-01-01`. |
#| end_date | string | Query end date, in the format of `YYYY-MM-DD` (UTC+0). <br><br><ul><li>You need to specify both `start_date` and `end_date` or neither. </li><li>If both `start_date` and `end_date` are not specified, the activity log for the past seven days will be returned. </li></ul><br>Recommended date range: six months. <br><br>Example: `2025-01-01`. |
#| activity_type | string | Activity type to query. <br><br>Enum values: <ul><li>`ALL`: All types of changes.</li><li>`USER`: User. Changes mainly related to members and organization, including but not limited to the following scenarios:<ul><li>Invitation of a member to the Business Center</li><li>Acceptance of the invitation to join the Business Center</li><li>Removal of a member from the Business Center</li></ul></li><li>`ACCOUNT`: Account. Changes mainly related to creation and access management of ad accounts, including but not limited to the following scenarios: <ul><li>Creating an ad account</li><li>Assigning a member access to an ad account</li><li>Revoking a member's access to an ad account </li><li>Sharing access to an ad account with a partner</li><li>Revoking a partner's access to an ad account</li></ul></li><li>`ASSET`: Asset. Changes mainly related to assets, including but not limited to the following scenarios:<ul><li>Creating or deleting a catalog</li><li>Requesting to add or approving the request to add a TikTok Shop</li><li>Assigning a member access to an asset</li><li>Revoking a member's access to an asset</li></ul></li><li>`BUSINESS`: Business. Changes mainly related to the Business Center, including but not limited to the following scenarios: <ul><li> Creating the Business Center </li><li>Changing the setting of the Business Center, including name and company name </li></ul></li></ul>Default value: `ALL`. |
| lang | string | The language of the activity log. <br><br>Default value: `en`.<br><br>To find out the enum values, see <a href="#item-link-Enum values for lang">Enum values for lang</a>. |
| sort_field | string | Field to sort by. <br><br>Enum value: <ul><li>`operation_time`: To sort by the time when the activity occurred, which corresponds to the `time` parameter within the returned object array `changelog_list`.</li></ul>Default value: `operation_time`. |
| sort_type | string | Sorting order. <br><br>Enum values:<ul><li>`DESC`: Descending.<ul><li>When `sort_field` is `operation_time` or not passed, this value means to sort from the most recent to earliest activity.</li></ul></li><li>`ASC`: Ascending.<ul><li>When `sort_field` is `operation_time` or not passed, this value means to sort from the earliest to the most recent activity. </li></ul></li></ul> Default value: `DESC`. |
| page | integer | Current page number. <br><br> Value range: ≥ 1.<br> Default value: 1.|
| page_size | integer | Page size. <br><br>Value range: 1-50.<br> Default value: 10. |
```

### Enum values for `lang`

```xtable
| Value {30%} | Description{70%}|
|---|---|
| `ar` | Arabic |
| `cs-CZ` | Czech |
| `de` | German |
| `en` | English |
| `es` | Spanish |
| `fil` | Filipino |
| `fr` | French |
| `id` | Indonesian |
| `it` | Italian |
| `ja` | Japanese |
| `ko` | Korean |
| `ms` | Malay |
| `pl-PL` | Polish |
| `pt` | Portuguese (Brazil) |
| `ru` | Russian |
| `sv-SE` | Swedish |
| `th` | Thai |
| `tr` | Turkish |
| `vi` | Vietnamese |
| `zh` | Chinese |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/changelog/get/?bc_id={{bc_id}}&filtering={"start_date":"{{start_date}}","end_date":"{{end_date}}", "activity_type":"ALL"}&page_size=50' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| changelog_list | object[] | Change log information. |
##| time | string | The time of the activity, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0).<br><br> Example: `2025-01-01 00:00:01`. |
##| activity_type | string |  Activity type. <br><br>Enum values: <ul><li>`USER`: User. Changes mainly related to members and organization, including but not limited to the following scenarios:<ul><li>Invitation of a member to the Business Center</li><li>Acceptance of the invitation to join the Business Center</li><li>Removal of a member from the Business Center</li></ul></li><li>`ACCOUNT`: Account. Changes mainly related to creation and access management of ad accounts, including but not limited to the following scenarios: <ul><li>Creating an ad account</li><li>Assigning a member access to an ad account</li><li>Revoking a member's access to an ad account </li><li>Sharing access to an ad account with a partner</li><li>Revoking a partner's access to an ad account</li></ul></li><li>`ASSET`: Asset. Changes mainly related to assets, including but not limited to the following scenarios:<ul><li>Creating or deleting a catalog</li><li>Requesting to add or approving the request to add a TikTok Shop</li><li>Assigning a member access to an asset</li><li>Revoking a member's access to an asset</li></ul></li><li>`BUSINESS`: Business. Changes mainly related to the Business Center, including but not limited to the following scenarios: <ul><li> Creating the Business Center </li><li>Changing the setting of the Business Center, including name and company name </li></ul></li></ul> |
##| operator_id | string | The user ID of the operator. |
##| activity_log | string | Activity log details. |
#| page_info | object | Page information. |
##| page | number | Current page number. |
##| page_size | number | Page size. |
##| total_number | number | Total number of results. |
##| total_page | number | Total number of pages. |
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
        "changelog_list": [
            {
                "activity_type": "USER",
                "activity_log": "{{activity_log}}",
                "operator_id": "{{operator_id}}",
                "time": "{{time}}"
            },
            {
                "activity_type": "ASSET",
                "activity_log": "{{activity_log}}",
                "operator_id": "{{operator_id}}",
                "time": "{{time}}"
            }
        ],
        "page_info": {
            "total_number": 2,
            "page_size": 50,
            "total_page": 1,
            "page": 1
        }
    }
}
(/code)
```
