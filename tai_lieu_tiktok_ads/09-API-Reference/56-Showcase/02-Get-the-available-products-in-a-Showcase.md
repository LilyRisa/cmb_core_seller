---
title: "Get the available products in a Showcase"
doc_id: 1759233576199169
path: "API Reference / Showcase / Get the available products in a Showcase"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the available products in a Showcase

Use this endpoint to get the Showcase products that can be used to target the specified country or region.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/showcase/product/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| identity_id {Required} | string | The ID of the identity that is bound to a Showcase and the identity needs to have permission of the Showcase. |
| identity_type {Required} | string | Identity type. Enum values: <ul><li>`TT_USER`: TikTok User.</li><li>`BC_AUTH_TT`: TikTok Account User in Business Center. </li></ul> See [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097) to learn more about different identity types. |
| identity_authorized_bc_id  {+Conditional} | string | Required when `identity_type` is `BC_AUTH_TT`. <br><br>ID of the Business Center that a TikTok Account User in Business Center identity is associated with.|
| region_codes {Required} | string[] | The list of country or region codes that you want to use products in the Showcase to target. |
| filtering | object | Filtering conditions. |
#| item_group_ids | string[] | Product SPU IDs. <br><br>Max size: 10. |
| page| number | Current page number. <br>Default value: 1. Value range: ≥1. |
| page_size| number |Page size. <br> Default value: 10. Value range: [1, 1000]. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/showcase/product/get/?identity_id={{identity_id}}&identity_type={{identity_type}}&advertiser_id={{advertiser_id}}&region_codes=["ID"]' \
--header 'Access-Token: {{Access-Token}}' \
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| showcase_products | object[] | The list of products in the Showcase that you can use to target the specified countries or regions. |
##| item_group_id | string | SPU ID of the product. |
##| title | string | The title of the product. |
##| product_image_url | string | The URL of the product image. |
##| min_price | string | The minimum price of the product. <br><br><p><span style="color:darkred"><b>Note</b></span>: If you specify one fixed price for the product, the values of `min_price` and `max_price` will be the same. |
##| max_price | string | The maximum price of the product. <br><br><p><span style="color:darkred"><b>Note</b></span>: If you specify one fixed price for the product, the values of `min_price` and `max_price` will be the same. |
##| currency | string | The code of the currency in which the prices (`min_price` and `max_price`) are specified. <br><br>To learn about the currency that a currency code corresponds to, see [Budget verification ratio and precision of each currency](https://business-api.tiktok.com/portal/docs?id=1737585839634433). |
##| category | string | The category of the product. |
##| status | string | The status of the product. <br><br>Enum values: <ul><li> `AVAILABLE`: The product is available for use in ads.</li><li>`NOT_AVAILABLE`: The product is not available for use in ads.</li></ul>|
##| catalog_id{-To-be-deprecated}| string | The ID of the catalog that the product (`item_group_id`) belongs to.<br><br><p><span style="color:darkred"><b>Note</b></span>: Starting June 30th, 2024, this field will no longer be returned. |
##| store_id | string | The ID of the store that the product (`item_group_id`) belongs to.<br> Note that the only supported store type is TikTok Shop. |
#| page_info | object | Pagination information. |
##| page | number | Current page number. |
##| page_size | number | Paging size. |
##| total_number | number | Total number of results. |
##| total_page | number | Total pages of results. |
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
        "showcase_products": [
            {
                "category": "{{category}}",
                "title": "{{title}}",
                "status": "NOT_AVAILABLE",
                "item_group_id": "{{item_group_id}}",
                "store_id": "{{store_id}}",
                "catalog_id": "{{catalog_id}}",
                "currency": "IDR",
                "min_price": "1000",
                "product_image_url": "{{product_image_url}}",
                "max_price": "1000"
            }
        ],
        "page_info": {
            "total_page": 1,
            "page_size": 10,
            "total_number": 1,
            "page": 1
        }
    }
}
(/code)
```

