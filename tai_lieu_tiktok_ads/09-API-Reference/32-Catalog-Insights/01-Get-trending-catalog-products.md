---
title: "Get trending catalog products"
doc_id: 1805640886872066
path: "API Reference / Catalog Insights / Get trending catalog products"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get trending catalog products

Use this endpoint to retrieve up to top 50 trending products based on user engagement on TikTok within an E-commerce catalog.  

><span style="color:DodgerBlue">**Note**</span><br> 
> <ul><li>Catalog Product Insights become available 24 hours after you have uploaded a minimum of 20 products to your E-commerce catalog.</li><li> The insights are refreshed daily at around 4:00 AM UTC time.</li></ul>


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/insight/product/get/

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
| bc_id {Required} | string | Business Center ID.<br><br>To obtain the list of Business Centers that you have access to, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826). |
| catalog_id {Required} | string | Catalog ID. <br>The catalog needs to be an E-commerce catalog that contains at least 20 products. <br><br>To obtain the list of E-commerce catalogs within a Business Center, use [/catalog/get/](https://business-api.tiktok.com/portal/docs?id=1740315452868610) and retrieve `catalog_id` values with `catalog_type` as `ECOM`. <br>To verify that the catalog contains at least 20 products, use [/catalog/overview/](https://business-api.tiktok.com/portal/docs?id=1740492470201345) and check whether the sum of the values of the returned `approved`, `rejected`, and `processing` fields is equal to or greater than 20. |
| filtering | object | Filtering conditions. |
#| category_ids {+Conditional} | string[] | When `filtering` is specified, you need to provide at least one of `category_ids`, `brands`, and `availabilities`. <br><br> A list of TikTok product category IDs to filter the results by.<br><br> Max size: 50. <br><br>Provide one or more category IDs obtained from the `category_id` field in the response of [/catalog/insight/filter/get/](https://business-api.tiktok.com/portal/docs?id=1805640864553985). |
#| brands {+Conditional} | string[] | When `filtering` is specified, you need to provide at least one of `category_ids`, `brands`, and `availabilities`. <br><br>A list of product brand names to filter the results by. <br><br>Max size: 50. <br><br>Provide one or more brand names obtained from the `brands` field in the response of [/catalog/insight/filter/get/](https://business-api.tiktok.com/portal/docs?id=1805640864553985). |
#| availabilities {+Conditional} | string[] |When `filtering` is specified, you need to provide at least one of `category_ids`, `brands`, and `availabilities`. <br><br>Product availability statuses to filter the results by. <br><br>Enum values: <ul><li>`IN_STOCK`: in stock. </li><li>`AVAILABLE_FOR_ORDER`: available for order.</li><li>`PREORDER`: available for pre-order.</li><li>`OUT_OF_STOCK`: out of stock.</li><li>`DISCONTINUED`: discontinued. </li></ul><br>Provide one or more availability statuses obtained from the `availabilities` field in the response of [/catalog/insight/filter/get/](https://business-api.tiktok.com/portal/docs?id=1805640864553985). |
```

### Example

```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/catalog/insight/product/get/?bc_id={{bc_id}}&catalog_id={{catalog_id}}' \
--header 'Access-Token: {{Access-Token}}' \
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
#| product_insights | object[] | The list of up to 50 trending products within the E-commerce catalog, sorted in descending order by popularity.  <br><br>For instance, the first item in the list is ranked 1st, indicating the most popular product, and the second item is ranked 2nd, indicating the second most popular product. |
##| product_id | string | The system-generated product ID. |
##| image_url | string | The URL for the product image. |
##| title | string | The title of the product. |
##| description | string | A short description of the product. |
##| sku_id | string | Advertiser-defined unique SKU ID for the Product. |
##| category_info | object | Information about the TikTok product category assigned to the product. |
###| category_id | string | The TikTok product category ID assigned to the product, consisting of three levels separated by the number sign (#), in the format of `"level_id_1#level_id_2#level_id_3"`.<br><br> Example: `601152#842248#601302`. |
###| level_info | object | Details of the levels that the category belongs to. |
####| level_id_1 | string | The ID of the level-1 TikTok product category. |
####| level_name_1 | string | The name of the level-1 TikTok product category. |
####| level_id_2 | string | The ID of the level-2 TikTok product category. |
####| level_name_2 | string | The name of the level-2 TikTok product category. |
####| level_id_3 | string | The ID of the level-3 TikTok product category. |
####| level_name_3 | string | The name of the level-3 TikTok product category. |
##| brand | string | Brand name for the product. |
##| price | object | Price information. |
###| price | float | The base price of the product. |
###| currency | string | Unit of currency. <br><br>  If this field is not specified, the unit of currency for the catalog's target country will be used by default. |
###| sale_price | float | The discounted price of the product if it's on sale. |
###| sale_price_effective_date| string[] | The start and end date and time of sale. |
##| availability | string | The current availability of the product in your store.<br><br>Enum values: <ul><li>`IN_STOCK`: in stock. </li><li>`AVAILABLE_FOR_ORDER`: available for order.</li><li>`PREORDER`: available for pre-order.</li><li>`OUT_OF_STOCK`: out of stock.</li><li>`DISCONTINUED`: discontinued. </li></ul> |
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
        "product_insights": [
            {
                "availability": "OUT_OF_STOCK",
                "brand": "{{brand}}",
                "category_info": {
                    "category_id": "605248#905224#906248",
                    "level_info": {
                        "level_id_1": "605248",
                        "level_id_2": "905224",
                        "level_id_3": "906248",
                        "level_name_1": "Fashion Accessories",
                        "level_name_2": "Clothes Accessories",
                        "level_name_3": "Headwear"
                    }
                },
                "description": "{{description}}",
                "image_url": "{{image_url}}",
                "price": {
                    "currency": "USD",
                    "price": 22.99,
                    "sale_price": 17.99,
                    "sale_price_effective_date": null
                },
                "product_id": "{{product_id}}",
                "sku_id": "{{sku_id}}",
                "title": "{{title}}"
            },
            {
                "availability": "OUT_OF_STOCK",
                "brand": "{{brand}}",
                "category_info": {
                    "category_id": "601152#842376#601276",
                    "level_info": {
                        "level_id_1": "601152",
                        "level_id_2": "842376",
                        "level_id_3": "601276",
                        "level_name_1": "Womenswear & Underwear",
                        "level_name_2": "Women's Bottoms",
                        "level_name_3": "Jeans"
                    }
                },
                "description": "{{description}}",
                "image_url": "{{image_url}}",
                "price": {
                    "currency": "USD",
                    "price": 57.99,
                    "sale_price": 49.99,
                    "sale_price_effective_date": null
                },
                "product_id": "{{product_id}}",
                "sku_id": "{{sku_id}}",
                "title": "{{title}}"
            }
        ]
    }
}
(/code)
```
