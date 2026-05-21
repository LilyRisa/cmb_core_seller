# Stock & Price Management

> Source: https://open.shopee.com/developer-guide/223
> Category: 
> Scraped: 2026-05-20T20:37:54.791Z

---

# 1\. Getting product price

-   If a product has no variants, please use [v2.product.get\_item\_base\_info](https://open.shopee.com/documents/v2/v2.product.get_item_base_info?module=89&type=1) API to get price information.
-   If a product has variants, please use [v2.product.get\_model\_list](https://open.shopee.com/documents/v2/v2.product.get_model_list?module=89&type=1) API to get price information.

  

  

API response:

  

"price\_info": \[

                   {

                       "current\_price": 7678,

                       "original\_price": 13960,

                       "inflated\_price\_of\_current\_price": 9137,

                       "inflated\_price\_of\_original\_price": 16612,

                       "currency": "COP"

                   }

  

  

Please note that:

1\. If your product has ongoing promotion, current\_price will show the promotion price during the promotion period. If not, current\_price=original\_price. original\_price indicates the original price of the product.

  

2.If your product has multiple promotions, you can get each promotion price through [v2.product.get\_item\_promotion](https://open.shopee.com/documents/v2/v2.product.get_item_promotion?module=89&type=1) API.

  

3\. If you are an ID / CO / PL seller, inflated\_price\_of\_current\_price/inflated\_price\_of\_original\_price means the price with tax; if you are a seller from other regions, inflated\_price\_of\_current\_price=current\_price, inflated\_price\_of\_original\_price=original\_price.

  

# 2\. Updating product price

API: [v2.product.update\_price](https://open.shopee.com/documents/v2/v2.product.update_price?module=89&type=1)

-   If a product has variants, you can upload multiple variants of this product to update the price in one call.
-   This API only supports updating one item\_id in one call. if you need to update more than one item\_id, you can request them multiple times.
-   Please check that the range of price can be updated by price\_limit in the [v2.product.get\_item\_limit](https://open.shopee.com/documents/v2/v2.product.get_item_limit?module=89&type=1) API.

  

  

2.1 Example of updating the price of a product without variants.

{

"item\_id": 1000,

"price\_list": \[{"original\_price": 11.11}\]

}

  

2.2 Example of updating the price of a product with variants.

{

"item\_id": 2000,

"price\_list": \[{"model\_id": 3456, "original\_price": 11.11}, {"model\_id": 1234, "original\_price": 22.22}\]

}

  

Note that if you are an ID / CO / PL seller, the original\_price is updated to be the untaxed price.

  

# 3\. Updating global product price

\*The following is only applicable to cross-border sellers who have upgraded CNSC/KRSC.

  

API: [v2.global\_product.update\_price](https://open.shopee.com/documents/v2/v2.global_product.update_price?module=90&type=1)

-   If a global product has variants, you can update the price of multiple variants of this global product in one call.
-   This API only supports updating one global\_item\_id in one call, if you need to update more than one global\_item\_id, you can request it multiple times.
-   For the price of the global product, please check the price currency first through the [v2.merchant.get\_merchant\_info](https://open.shopee.com/documents/v2/v2.merchant.get_merchant_info?module=93&type=1) API.
-   If you want the price of global products automatically synchronized to shop products, please set the price synchronization toggle open through the [v2.global\_product.set\_sync\_field](https://open.shopee.com/documents/v2/v2.global_product.set_sync_field?module=90&type=1) API . Shopee will automatically update based on the formula. If not, you can update the price through [v2.product.update\_price](https://open.shopee.com/documents/v2/v2.product.update_price?module=89&type=1) API.

  

# 4\. Getting product stock

  

-   If a product has no variants, please use [v2.product.get\_item\_base\_info](https://open.shopee.com/documents/v2/v2.product.get_item_base_info?module=89&type=1) API to get the stock information.
-   If a product has variants, please use [v2.product.get\_model\_list](https://open.shopee.com/documents/v2/v2.product.get_model_list?module=89&type=1) API to get the stock information.

  

API response:

Json

```
{      

                "stock_info_v2": {

                    "summary_info": {

                        "total_reserved_stock": 0,

                        "total_available_stock": 389

                    },

                    "seller_stock": [

                        {

                            "location_id": "IDZ",

                            "stock": 90

                        }

                    ],

                    "shopee_stock": [

                        {

                            "location_id": "IDG",

                            "stock": 99

                        },

                        {

                            "location_id": "IDM",

                            "stock": 200

                        }

                    ]

                }

            }
```

  

Please note:

-   Product may have both seller\_stock and shopee\_stock, or it may have stock from multiple locations.
-   For more stock calculation logic, please refer to the [FAQ](https://open.shopee.com/faq?top=162&sub=166&page=1&faq=230)

  

# 5\. Updating product stock

API: [v2.product.update\_stock](https://open.shopee.com/documents/v2/v2.product.update_stock?module=89&type=1)

-   If a product has variants, you can upload multiple variants of this product to update the stock in one call.
-   This API only supports updating one item\_id in one call, if you need to update more than one item\_id, you can request it multiple times.
-   Sellers can only update seller\_stock, cannot update shopee\_stock.
-   Please check the stock\_limit in the [v2.product.get\_item\_limit](https://open.shopee.com/documents/v2/v2.product.get_item_limit?module=89&type=1) API for the range of stock that can be updated.

  

5.1 Example of updating the stock of a product with no variants.

{

"item\_id": 1000,

"stock\_list": \[{"seller\_stock": \[{"stock": 100}\]}\]

}

  

5.2 Example of updating the stock of a product with variants.

{

"item\_id": 2000,

"stock\_list": \[{"model\_id": 3456, "seller\_stock": \[{"stock": 100}\]}, {"model\_id": 1234, "seller\_stock": \[{"stock": 100}\]}\]

}

  

Please note：

-   If a product has variants, the price difference between the variations cannot exceed a certain multiple. For example, BR product, the price of the most expensive variations divided by the price of the cheapest variations cannot exceed 4.

<table><tbody><tr><td colspan="" rowspan=""><span><span>Region</span></span></td><td colspan="" rowspan=""><span><span>multiple</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>BR</span></span></td><td colspan="" rowspan=""><span><span>4</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>SG/VN/TW/TH/PH/MX</span></span></td><td colspan="" rowspan=""><span><span>5</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>ID/MY</span></span></td><td colspan="" rowspan=""><span><span>7</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>CL/CO</span></span></td><td colspan="" rowspan=""><span><span>9</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>CNSC</span></span></td><td colspan="" rowspan=""><span><span>7</span></span></td></tr></tbody></table>

-   The product participates in certain promotion, sellers are not allow to modify the original price of the product. More detail please check FAQ: [https://open.shopee.com/faq/140](https://open.shopee.com/faq/140)

  

# 6\. Updating global product stock

\*The following is only applicable to cross-border sellers who have upgraded CNSC/KRSC.

  

API: [v2.global\_product.update\_stock](https://open.shopee.com/documents/v2/v2.global_product.update_stock?module=90&type=1)

-   If a global product has variants, you can update the stock of multiple variants of the global product in one call.
-   Since cross-border sellers who have upgraded CNSC/KRSC can only manage shop product stock through global product, it means you can only call the [v2.global\_product.update\_stock](https://open.shopee.com/documents/v2/v2.global_product.update_stock?module=90&type=1) API to update stock. After updating global product stock, it will be automatically synchronized to shop products. Using the [v2.product.update\_stock](https://open.shopee.com/documents/v2/v2.product.update_stock?module=89&type=1) API to update the stock will result in an error.
-   This API only supports updating one global\_item\_id at a time, if you need to update more than one global\_item\_id, you can request it multiple times.
-   Please check the stock\_limit in the [v2.global\_product.get\_global\_item\_limit](https://open.shopee.com/documents/v2/v2.global_product.get_global_item_limit?module=90&type=1) API for the range of stock that can be updated.
