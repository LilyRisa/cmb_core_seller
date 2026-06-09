---
title: "Get products within a TikTok Shop"
doc_id: 1793482248880130
path: "API Reference / TikTok Store / Get products within a TikTok Shop"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get products within a TikTok Shop

Use this endpoint to retrieve the products within a first-party store (TikTok Shop).

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/store/product/get/

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
| bc_id {Required} | string | Business Center ID. <br><br>To obtain the list of Business Centers that you have access to, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826). |
| store_id {Required} | string | TikTok Shop ID. <br><br>To obtain the ID list of TikTok Shops associated with an ad account, use [/store/list/](https://business-api.tiktok.com/portal/docs?id=1752267762718722). |
| filtering | object | Filtering conditions. |
#| item_group_ids | string[] | Product SPU (standard product unit) IDs. <br><br>Max size: 10. |
#| ad_creation_eligible | string | The type of ad or campaign that the products are eligible for. <br><br> Enum values: <ul><li>`CUSTOM_SHOP_ADS`: Shopping Ads, including [Video Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1750361719059457) and [Product Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1785886237030401).</li><li>`GMV_MAX`: [Product GMV Max Campaigns](https://business-api.tiktok.com/portal/docs?id=1822009220448257).</li></ul><br> If this field is not specified, the response will not include the eligibility of the products for Product GMV Max Campaigns. |
#| product_name | string | Product name. |
| advertiser_id {+Conditional} | string | Required when `ad_creation_eligible` is passed. <br><br> Advertiser ID. |
| sort_field | string | Field to sort by. <br><br> Enum values: <ul><li>`min_price`: the returned `min_price` field.</li><li>`historical_sales`: the returned `historical_sales` field. </li></ul>  |
| sort_type | string | Sorting order. <br><br>Enum values: <ul><li>`ASC`: ascending. </li><li>`DESC`: descending. </li></ul> |
| page | integer | Current number of pages. <br><br>Default value: 1. <br><br>Value range: ≥ 1. |
| page_size | integer | Page size. <br><br> Default value: 10. <br><br>Value range: 1-100. |
```
### Example

#### Get products available for Product GMV Max Campaigns
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/store/product/get/?store_id={{store_id}}&bc_id={{bc_id}}&filtering={"ad_creation_eligible":"GMV_MAX"}&advertiser_id={{advertiser_id}}&page_size=100' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

#### Get products available for Shopping Ads
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/store/product/get/?store_id={{store_id}}&bc_id={{bc_id}}&filtering={"ad_creation_eligible":"CUSTOM_SHOP_ADS"}&advertiser_id={{advertiser_id}}&page_size=100' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

#### Get products within a TikTok Shop without using the `ad_creation_eligible` filter
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/store/product/get/?bc_id={{bc_id}}&store_id={{store_id}}&page_size=100' \
--header 'Access-Token: {{Access-Token}}'
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
#| store_products | object[] | The list of products within the TikTok Shop. |
##| store_id | string | TikTok Shop ID. |
##| item_group_id | string | SPU ID of the product. |
##| catalog_id{-Deprecated} | string | The ID of the catalog that the product (`item_group_id`) belongs to.<br><br><p><span style="color:darkred"><b>Note</b></span>: Starting June 30th, 2024, this field returns a null value. |
##| title | string | The title of the product. |
##| product_image_url | string | The URL of the product image. |
##| min_price | string | The minimum price of the product. <br><br><p><span style="color:darkred"><b>Note</b></span>: If you specify one fixed price for the product, the values of `min_price` and `max_price` will be the same. |
##| max_price | string | The maximum price of the product. <br><br><p><span style="color:darkred"><b>Note</b></span>: If you specify one fixed price for the product, the values of `min_price` and `max_price` will be the same. |
##| currency | string | The code of the currency in which the prices (`min_price` and `max_price`) are specified. <br><br>To learn about the currency that a currency code corresponds to, see [Budget verification ratio and precision of each currency](https://business-api.tiktok.com/portal/docs?id=1737585839634433). |
##| historical_sales | number | The historical sales of the product, indicating the number of times the product has been sold. |
##| category | string | The category of the product. |
##| quantity | number | The number of products within the TikTok Shop.<br><br>Example: 5. |
##| status | string | The status of the product. <br><br>Enum values: <ul><li> `AVAILABLE`: The product is available for use in ads.</li><li>`NOT_AVAILABLE`: The product is not available for use in ads.</li></ul>|
##| gmv_max_ads_status | string | Returned only when the filter field `ad_creation_eligible` is passed in the request. <br><br> The status of the product in an enabled Product GMV Max Campaign. <br><br> Enum values:<ul><li>`OCCUPIED`: The product is being promoted in an enabled Product GMV Max Campaign.</li><li>`UNOCCUPIED`: The product is not being promoted in an enabled Product GMV Max Campaign.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If status is `AVAILABLE` and `gmv_max_ads_status` is `UNOCCUPIED`, the product can be used in a Product GMV Max Campaign. |
##| is_running_custom_shop_ads | boolean | Returned only when the filter field `ad_creation_eligible` is set to `GMV_MAX` in the request. <br><br>Whether the product is being promoted in enabled Video Shopping Ads or Product Shopping Ads. <br><br> Supported values: `true`, `false`. <br><br> If `is_running_custom_shop_ads` is `true`, you can call [/gmv_max/occupied_custom_shop_ads/list/](https://business-api.tiktok.com/portal/docs?id=1822001136924674) and set `occupied_asset_type` to `SPU` to find out the specific Video Shopping Ads or Product Shopping Ads that are using the product. To disable these ads, use [/ad/status/update/](https://business-api.tiktok.com/portal/docs?id=1739953422970882) and set `operation_status` to `DISABLE`. |
#| page_info | object | Pagination information. |
##| page | number | Current page number. |
##| page_size | number | Paging size. |
##| total_number | number | Total number of results. |
##| total_page | number | Total pages of results. |
```

### Example
#### Get products available for Product GMV Max Campaigns
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "store_products": [
            {
                "status": "AVAILABLE",
                "product_image_url": "{{product_image_url}}",
                "catalog_id": null,
                "category": "{{category}}",
                "historical_sales": 0,
			    "quantity": 2,
                "item_group_id": "{{item_group_id}}",
                "max_price": "1000",
                "store_id": "{{store_id}}",
                "min_price": "1000",
                "currency": "IDR",
                "title": "{{title}}",
                "gmv_max_ads_status": "OCCUPIED",
                "is_running_custom_shop_ads": true
            },
            {
                "status": "AVAILABLE",
                "product_image_url": "{{product_image_url}}",
                "catalog_id": null,
                "category": "{{category}}",
                "historical_sales": 0,
				"quantity": 2,
                "item_group_id": "{{item_group_id}}",
                "max_price": "100",
                "store_id": "{{store_id}}",
                "min_price": "100",
                "currency": "IDR",
                "title": "{{title}}",
                "gmv_max_ads_status": "OCCUPIED",
                "is_running_custom_shop_ads": false
            }
        ],
        "page_info": {
            "total_number": 2,
            "page_size": 100,
            "page": 1,
            "total_page": 1
        }
    }
}
(/code)
```

#### Get products available for Shopping Ads
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "page": 1,
            "total_number": 2,
            "page_size": 100,
            "total_page": 1
        },
        "store_products": [
            {
                "category": "{{category}}",
                "currency": "IDR",
                "max_price": "1000",
                "catalog_id": null,
                "gmv_max_ads_status": "UNOCCUPIED",
                "product_image_url": "{{product_image_url}}",
                "historical_sales": 0,
				"quantity": 2,
                "store_id": "{{store_id}}",
                "item_group_id": "{{item_group_id}}",
                "title": "{{title}}",
                "status": "AVAILABLE",
                "min_price": "1000"
            },
            {
                "category": "{{category}}",
                "currency": "IDR",
                "max_price": "100",
                "catalog_id": null,
                "gmv_max_ads_status": "UNOCCUPIED",
                "product_image_url": "{{product_image_url}}",
                "historical_sales": 0,
				"quantity": 2,
                "store_id": "{{store_id}}",
                "item_group_id": "{{item_group_id}}",
                "title": "{{title}}",
                "status": "AVAILABLE",
                "min_price": "100"
            }
        ]
    }
}
(/code)
```

#### Get products within a TikTok Shop without using the `ad_creation_eligible` filter
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "total_page": 1,
            "page": 1,
            "page_size": 100,
            "total_number": 2
        },
        "store_products": [
            {
                "currency": "GBP",
                "category": "Fashion Accessories > Eyewear > Glasses Cases & Accessories",
                "catalog_id": "{{catalog_id}}",
                "max_price": "0.07",
                "status": "AVAILABLE",
                "title": "{{title}}",
                "product_image_url": "{{product_image_url}}",
                "store_id": "{{store_id}}",
                "item_group_id": "{{item_group_id}}",
                "min_price": "0.07",
				"quantity": 2,
                "historical_sales": 18
            },
            {
                "currency": "GBP",
                "category": "Womenswear & Underwear > Women's Tops > Vests",
                "catalog_id": "{{catalog_id}}",
                "max_price": "0.7",
                "status": "NOT_AVAILABLE",
                "title": "{{title}}",
                "product_image_url": "{{product_image_url}}",
                "store_id": "{{store_id}}",
                "item_group_id": "{{item_group_id}}",
                "min_price": "0.7",
				"quantity": 2,
                "historical_sales": 0
            }
        ]
    }
}
(/code)
```

