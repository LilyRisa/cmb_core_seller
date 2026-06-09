---
title: "Create an Asset Group"
doc_id: 1749001658583105
path: "API Reference / BC Asset Groups / Create an Asset Group"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an Asset Group

Use this endpoint to create an Asset Group in your Business Center. You need to be an Admin user of the Business Center.

# Request
**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/asset_group/create/

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
| asset_group_name  {Required} | string | Asset Group name. |
| assets {Required} | object[] | Assets you want to add to the Asset Group. Max size is 200. |
#| asset_id {Required}  | string| Asset ID. When the asset type (`asset_type`) is `ADVERTISER`, this is the advertiser ID. |
#| asset_type| string | Asset type. Currently, the only supported type is `ADVERTISER`. You don't have to specify this field since `ADVERTISER` is used by default.|
| members {Required} | object[] | Business Center members you want to grant access to your Asset Group. Max size is 50. |
#| member_id {Required}  | string| Member ID.  |
#| asset_roles | map < string, string>| The roles you want to assign to the member according to the asset type. <br>Key: Asset type. Enum value: `ADVERTISER`.<br>Value: Assigned role. Enum values:<ul><li>`ADVERTISER_ROLE_ADMIN`:<br>Manage ad accounts: Control ad account settings, finances and permissions.<br>Manage campaigns: Create and edit ads.<br>View performance: Access reports and view ads.</li><li>`ADVERTISER_ROLE_OPERATOR`: <br>Manage campaigns: Create and edit ads. <br>View performance: Access reports and view ads.</li><li>`ADVERTISER_ROLE_ANALYST`: <br>View performance: Access reports and view ads.</li></ul>Default value: `&#123;"ADVERTISER":"ADVERTISER_ROLE_ADMIN"&#125;`.
```
### Example
```xcodeblock
(code)
curl --location --request POST https://business-api.tiktok.com/open_api/v1.3/bc/asset_group/create/
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "bc_id": "{{bc_id}}",
    "asset_group_name": "{{asset_group_name}}",
    "assets": [
        {
            "asset_id": "{{asset_id_1}}"
        },
        {
            "asset_id": "{{asset_id_2}}"
        }
    ],
    "members": [
        {
            "member_id": "{{member_id}}",
            "asset_roles": "{{asset_roles}}"
        }
    ]
}'
(/code)
```

# Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#|asset_group_id |string| ID of the Asset Group that was created. |
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
        "asset_group_id": "{{asset_group_id}}"
    }
}
(/code)
```
