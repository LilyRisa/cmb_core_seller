---
title: "Update an Asset Group"
doc_id: 1749001662156801
path: "API Reference / BC Asset Groups / Update an Asset Group"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update an Asset Group

Use this endpoint to update assets, members or the name of an Asset Group. You need to be an Admin user of the Business Center.

# Request
**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/asset_group/update/ 

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type{Required}|string|The content type of the request. <br>Allowed value: `"application/json"`. |
```
**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required} | string | Business Center ID. |
|asset_group_id  {Required} | string | ID of the Asset Group you want to update.|
|update_entity   {Required} | string | The entity that you want to update. Enum values: `ASSET`, `MEMBER`, `NAME`.|
| asset_group_name  {+Conditional} | string | Asset Group name. Required when the `update_entity` is `NAME`. |
| action  {+Conditional} | string | Type of action to be performed. Enum values: `ADD`, `DELETE`. Required when the `update_entity` is `ASSET` or `MEMBER`. |
| assets {+Conditional} | object[] | Asset information. Max size is 200. Required when the `update_entity` is `ASSET`.|
#| asset_id  | string| Asset ID. When the asset type (`asset_type`) is `ADVERTISER`, this is the advertiser ID. |
#| asset_type| string | Asset type. Currently, the only supported type is `ADVERTISER`. You don't have to specify this field since `ADVERTISER` is used by default.|
| members {+Conditional} | object[] |Member information. Max size is 50. Required when the `update_entity` is `MEMBER`.|
#| member_id   | string| Member ID.  |
#| asset_roles | map < string, string>| The roles you want to assign to the member according to the asset type. <br>Key: Asset type. Enum value: `ADVERTISER`.<br>Value: Assigned role. Enum values:<ul><li>`ADVERTISER_ROLE_ADMIN`:<br>Manage ad accounts: Control ad account settings, finances and permissions.<br>Manage campaigns: Create and edit ads.<br>View performance: Access reports and view ads.</li><li>`ADVERTISER_ROLE_OPERATOR`: <br>Manage campaigns: Create and edit ads. <br>View performance: Access reports and view ads.</li><li>`ADVERTISER_ROLE_ANALYST`: <br>View performance: Access reports and view ads.</li></ul>Default value: `"ADVERTISER: ADVERTISER_ROLE_ADMIN"`.
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET https://business-api.tiktok.com/open_api/v1.3/bc/invoice/get/?bc_id={{bc_id}}&invoice_types=["CREDIT"]&page_size=50
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json'
(/code)
```
# Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
|request_id|string|The log ID of the request, which uniquely identifies a request. |
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
                "account_name": "{{account_name}}",
                "address": "{{address}}",
                "amount_excluding_tax": 293.5,
                "status": "APPROVED",
                "amount": 293.5,
                "positive_invoice_ids": [
                    {
                        "invoice_id": "{{invoice_id}}",
                        "serial_number": "{{serial_number}}"
                    }
                ],
                "country_code": "VG",
                "invoice_type": "CREDIT",
                "invoice_id": "{{invoice_id}}",
                "serial_number": "{{serial_number}}-CN",
                "invoice_title": "{{account_name}}",
                "currency_code": "USD",
                "unpaid_amount": 293.5,
                "emails": [
                    "{{email}}",
                    "{{email}}"
                ],
                "billing_group_id": "{{billing_group_id}}",
                "entity_serials": [
                    "{{entity_serial}}"
                ]
            },
            {
                "account_name": "{{account_name}}",
                "address": "{{address}}",
                "amount_excluding_tax": 179,
                "status": "APPROVED",
                "amount": 179,
                "positive_invoice_ids": [
                    {
                        "invoice_id": "{{invoice_id}}",
                        "serial_number": "{{serial_number}}"
                    }
                ],
                "country_code": "VG",
                "invoice_type": "CREDIT",
                "invoice_id": "{{invoice_id}}",
                "serial_number": "{{serial_number}}-CN",
                "invoice_title": "{{account_name}}",
                "currency_code": "USD",
                "unpaid_amount": 179,
                "emails": [
                    "{{email}}",
                    "{{email}}"
                ],
                "billing_group_id": "{{billing_group_id}}",
                "entity_serials": [
                    "{{entity_serial}}"
                ]
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 50,
            "total_page": 1,
            "total_number": 8
        }
    }
}
(/code)
```
