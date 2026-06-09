---
title: "Update a Billing Group"
doc_id: 1738947843447809
path: "API Reference / BC Billing Groups / Update a Billing Group"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update a Billing Group

Use this endpoint to update the settings of a Billing Group.

Both Agency and Direct Business Centers are able to perform billing group related operations.
> <span style="color: dodgerblue">**Note**</span><br>Only users with Finance Manager or Finance Analyst permission can manage payments and invoices in a Business Center. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members.
## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints.
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/bc/billing_group/update/|/v1.3/bc/billing_group/update/   |
| Request parameter name | `billing_group_name` <br>`billing_group_emails` | `new_billing_group_name` <br>`new_billing_group_emails`|
| Request parameter data type | `bc_id`: number <br>`billing_group_id`: number <br>`delete_advertiser_ids`: number[]<br> `add_advertiser_ids`: number[] |`bc_id`: string <br>`billing_group_id`: string <br>`delete_advertiser_ids`: string[]<br> `add_advertiser_ids`: string[] |
```
## Request
**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/billing_group/update/

**Method**  POST

**Header**
```xtable
|Field{30%}|Data Type{35%}|Description{35%}|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type{Required}|string|The content type of the request. <br>Allowed value: `"application/json"`.|
```
**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
| bc_id {Required} | string | Business Center ID |
| billing_group_id{Required} | string | ID of the billing group you'd like to edit |
| new_billing_group_name | string | New billing group name |
| new_billing_group_emails | string[] | List of emails for the billing group. If this field is provided, this new list of emails will overwrite the billing group's existing emails |
| add_advertiser_ids | string[] | List of ad account IDs you want to add to this billing group |
| delete_advertiser_ids | string[] | List of ad account IDs you want to remove from this billing group |
| is_primary | boolean| Whether to set this billing group as the default billing group for the Business Center |
```
### Example
```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "bc_id": "BC_ID",
    "billing_group_id": "BILLING_GROUP_ID",
    "new_billing_group_name": "NEW_BILLING_GROUP_NAME",
    "new_billing_group_emails": "NEW_BILLING_GROUP_EMAILS",
    "add_advertiser_ids": [
        "ADD_ADVERTISER_IDS"
    ],
    "delete_advertiser_ids": [
        "DELETE_ADVERTISER_IDS"
    ],
    "is_primary": "IS_PRIMARY"
}' \
https://business-api.tiktok.com/open_api/v1.3/bc/billing_group/update/
(/code)
```
## Response
```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
|request_id|string|The log id of the request, which uniquely identifies a request. |
```
### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code)
```

