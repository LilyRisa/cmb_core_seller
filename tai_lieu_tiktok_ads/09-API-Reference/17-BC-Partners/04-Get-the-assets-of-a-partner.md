---
title: "Get the assets of a partner"
doc_id: 1739662828320769
path: "API Reference / BC Partners / Get the assets of a partner"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the assets of a partner

Use this endpoint to get assets that you have shared with a partner, or assets that are shared by a partner. You need to be an Admin of the Business Center.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/bc/partner/asset/get/| /v1.3/bc/partner/asset/get/
|Request parameter data type|`bc_id`: number<br>`partner_id`: number|`bc_id`: string <br>`partner_id`: string|
|Response parameter data type|`asset_id`: number |`asset_id`: string|
|New response parameter|/|  `advertiser_account_type`: string|
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/partner/asset/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required} | string | Business Center ID |
|partner_id {Required} | string | Partner Business Center ID |
|asset_type {Required} | string | Asset type. <br><br>Enum values: `ADVERTISER`, `CATALOG`.  |
|share_type {Required} | string | Share type. <br><br>Enum values: <ul><li>`SHARED`: Assets that are shared with you by a partner</li><li>`SHARING`: Assets that you share with a partner</li></ul> |
|filtering | object | Filtering by keyword |
#|keyword  | string | Keyword you want the assets filtered by |
|page  |	number|	Current page number. Default value: 1|
|page_size |	number|	Page size. Default value: 10 |
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "bc_id=BC_ID" \
--data-urlencode "partner_id=PARTNER_ID" \
--data-urlencode "asset_type=ASSET_TYPE" \
--data-urlencode "share_type=SHARE_TYPE" \
--data-urlencode "filtering={\"keyword\": \"KEYWORD\"}" \
--data-urlencode "page=PAGE" \
--data-urlencode "page_size=PAGE_SIZE" \
https://business-api.tiktok.com/open_api/v1.3/bc/partner/asset/get/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The log id of the request, which uniquely identifies a request. |
|data |object| Returned data. |
#| list | object[] | List of partner assets |
##| asset_id | string | Asset ID. <ul><li>If the `asset_type` is `ADVERTISER`, this is the advertiser ID. <li>If the `asset_type`  is `CATALOG`, this is the catalog ID. |
##| asset_type | string | Asset type. <br><br>Enum values: `ADVERTISER`, `CATALOG`. |
##| asset_name | string | Asset name. |
##| advertiser_role | string | Returned only when `asset_type` is `ADVERTISER`. <br><br>Ad account role.<br><br> Enum values: `ADMIN`, `OPERATOR`, `ANALYST`. |
##|advertiser_account_type |string|Type of the ad account.<br><br> Enum values: `RESERVATION` (reservation ad account), `AUCTION`(auction ad account).|
##| catalog_role | string | Returned when `asset_type` is `CATALOG`. <br><br>Business Center user's permissions to the catalog. <br><br>Enum values: <ul><li>`ADMIN`: The Business Center user has Admin permissions to the catalog. </li><li>`AD_PROMOTE`: The Business Center user can only use the catalog for promotions.  </li></ul> |
#|page_info|object|Pagination information |
##|page|number|Current page number. |
##|page_size|number|Page size |
##|total_number|number|Total number of results |
##|total_page|number|Total pages of results |
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


