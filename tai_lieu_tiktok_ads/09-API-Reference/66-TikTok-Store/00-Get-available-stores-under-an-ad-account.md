---
title: "Get available stores under an ad account"
doc_id: 1752267762718722
path: "API Reference / TikTok Store / Get available stores under an ad account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get available stores under an ad account

Use this endpoint to get the list of available first-party stores (TikTok Shops) under an ad account.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/store/list/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID. |
| store_id | string | ID of the first-party store (TikTok Shop) that you want to filter by. If not specified, the results for all available `store_id` under the specified advertiser account will be returned. <br><p><span style="color:darkred"><b>Note</b></span>: To get the TikTok Shop ID, you can use [/bc/asset/get/](https://ads.tiktok.com/marketing_api/docs?id=1739432717798401):<ul><li>When in the response `asset_type` is `TIKTOK_SHOP`, the returned `asset_id` is the TikTok Shop ID.</li></ul> |
| store_type | string | Store type that you want to filter by. <br>Enum values: `TIKTOK_SHOP` (TikTok Shop) .<br>If not specified, the results for all stores under the specified advertiser account will be returned. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api/open_api/v1.3/store/list/?advertiser_id={{advertiser_id}}' \
--header 'Access-Token: {{Access-Token}}' \
(/code)
```

## Response
``` xtable
| Field {35%}| Data Type{15%} | Description{50%} |
|---|---|---|
| code | number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
| message | string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
| data | object | Returned data. |
#| stores | object[] | Information about the available stores under the ad account. |
##| store_authorized_bc_id | string | ID of the Business Center that is authorized to access the store (`store_id`). One store may be accessed by more than one Business Center. |
##| store_id | string | ID of the TikTok Shop. See [Create Video Shopping Ads with products from TikTok Shopping-Create an ad group](https://ads.tiktok.com/marketing_api/docs?id=1750361719059457) to learn about how to use `store_id` to create Shopping Ads |
##| store_type | string | Store type. Enum values:`TIKTOK_SHOP` (TikTok Shop). |
##| store_name | string | Store name. |
##|store_code | string | Returned when the  `store_type` of the store is `TIKTOK_SHOP`.<br><br>The shop code of the first-party store (TikTok Shop).|
##| catalog_id {-To-be-deprecated}| string | ID of the catalog that is bound to the store. One catalog can be bound to only one store. |
##| targeting_region_codes | string[] | Region codes that the store can be used to target. See [Appendix-Location code](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010) to find out what the region codes stand for. |
| request_id | string | The log ID of a request, which uniquely identifies the request. |
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
        "stores": [
            {
                "tiktok_account_binding_status": null,
                "store_id": "{{store_id}}",
                "store_type": "TIKTOK_SHOP",
                "catalog_id": "{{catalog_id}}",
                "store_authorized_bc_id": "{{store_authorized_bc_id}}",
                "store_name": "{{store_name}}",
				"store_code": "{{store_code}}",
                "targeting_region_codes": [
                    "ID"
                ]
            }
        ]
    }
} 
(/code)
```

