# Product base info management

> Source: https://open.shopee.com/developer-guide/221
> Category: 
> Scraped: 2026-05-20T20:37:51.928Z

---

# 1\. Getting product list

## 1.1 Getting all shop products

API: [v2.product.get\_item\_list](https://open.shopee.com/documents/v2/v2.product.get_item_list?module=89&type=1)

  

This API allows you to get a list of all the products in the shop or filter by update\_time range and item status

  

## 1.2 Searching for item\_id

API: [v2.product.search\_item](https://open.shopee.com/documents/v2/v2.product.search_item?module=89&type=1)

  

This API allows you to search for a list of item\_id based on some specific conditions, including

-   A list of item\_id containing the product name keyword
-   A list of item\_id containing the sku keyword
-   A list of item\_id lacking required attributes
-   A list of item\_id lacking optional attributes

  

# 2\. Getting product information

API: [v2.product.get\_item\_base\_info](https://open.shopee.com/documents/v2/v2.product.get_item_base_info?module=89&type=1) + [v2.product.get\_model\_list](https://open.shopee.com/documents/v2/v2.product.get_model_list?module=89&type=1)

  

If the product has no variants, you only need to call [v2.product.get\_item\_base\_info](https://open.shopee.com/documents/v2/v2.product.get_item_base_info?module=89&type=1) to get the product base information, otherwise you also need to call [v2.product.get\_model\_list](https://open.shopee.com/documents/v2/v2.product.get_model_list?module=89&type=1) API to get the variants price and stock.

  

The field “has\_model” in [v2.product.get\_item\_base\_info](https://open.shopee.com/documents/v2/v2.product.get_item_base_info?module=89&type=1) API indicates whether the product has variants or not.

  

# 3\. Getting the data of product

API: [v2.product.get\_item\_extra\_info](https://open.shopee.com/documents/v2/v2.product.get_item_extra_info?module=89&type=1)

  

This API can get the data of views, likes, sales, ratings, and star rating from a product.

The data of views is from the last 30 days' statistics, the sales data is the cumulative value.

  

# 4\. Getting product promotion information

API: [v2.product.get\_item\_promotion](https://open.shopee.com/documents/v2/v2.product.get_item_promotion?module=89&type=1)

  

This API allows you to get information about all ongoing or upcoming promotions that the product is added in. If the product is added into multiple promotions, the promotion\_id field of [v2.product.get\_item\_base\_info](https://open.shopee.com/documents/v2/v2.product.get_item_base_info?module=89&type=1) will return one of the promotion\_id, and we suggest you continue to call API [v2.product.get\_item\_promotion](https://open.shopee.com/documents/v2/v2.product.get_item_promotion?module=89&type=1) to get all the promotions information.

  

# 5\. Updating product information

API: [v2.product.update\_item](https://open.shopee.com/documents/v2/v2.product.update_item?module=89&type=1)

  

1\. This API supports updating product information except for the size\_chart/price/stock/model information. Fields that are uploaded will be updated, and fields that are not uploaded will not be updated.

  

2\. For item\_sku/wholesale/video\_upload\_id, we support the delete operation, you can upload the null string then we will delete it.

Example of deleting item\_sku:

{

   "item\_id": 800182459,

   "item\_sku": ""

}

  

3\. Please refer to the [FAQ](https://open.shopee.com/faq?top=162&sub=166&page=1&faq=218) about updating extended descriptions.

  

4\. If you did not update some fields but encountered a prompt that these fields are filled in incorrectly, this situation is normal because every time you update, we will verify the legitimacy of all the product information, so if it does not meet the requirements, please modify it.

# 6\. Unlisting or deleting product

API: [v2.product.unlist\_item](https://open.shopee.com/documents/v2/v2.product.unlist_item?module=89&type=1)

  

“unlist” : true means the product will be unlist,“unlist” : false, means the product will be re-listed.

  

API: [v2.product.delete\_item](https://open.shopee.com/documents/v2/v2.product.delete_item?module=89&type=1)

This API can change item\_status to be “deleted”, please note that after the deletion, you will not be able to update the product, and the seller can not view this item through the Seller Center.

  

For Shopee deleted and Seller deleted products, within 90 days, you can still get the product information through API, after 90 days, the product data will be permanently deleted in Shopee database, you can not query any information about this product, if you need, please save the product information in time.

  

# 7\. Updating size chart image

API: [v2.product.update\_size\_chart](https://open.shopee.com/documents/v2/v2.product.update_size_chart?module=89&type=1)

This API can be used to add or update the image size chart of the product, if you encounter the error "Your shop can not edit image size chart", please check the [announcement](https://open.shopee.com/announcements/548).

  

# 8\. Registering Brand

API: [v2.product.register\_brand](https://open.shopee.com/documents/v2/v2.product.register_brand?module=89&type=1)

  

Sellers can register their own brands through this API. If Shopee audits this brand successfully, you will get a valid brand\_id for adding or updating products.

For the specific audit process, please check the [FAQ](https://open.shopee.com/faq?top=162&sub=166&page=1&faq=211)

  

  

\*The following content is only applicable to CNSC/KRSC sellers.

  

# 9\. Getting global product list

API: [v2.global\_product.get\_global\_item\_list](https://open.shopee.com/documents/v2/v2.global_product.get_global_item_list?module=90&type=1)

  

This API allows you to get a list of all global\_item\_id or filter by update\_time range under the merchant. This API will not return a list of deleted global\_item\_id.

  

# 10\. Getting a global product ID

API: [v2.global\_product.get\_global\_item\_id](https://open.shopee.com/documents/v2/v2.global_product.get_global_item_id?module=90&type=1)

  

By calling this API, you can quickly find the global\_item\_id of a shop product.

# 11\. Getting global product information

API: [v2.global\_product.get\_global\_item\_info](https://open.shopee.com/documents/v2/v2.global_product.get_global_item_info?module=90&type=1) + [v2.global\_product.get\_global\_model\_list](https://open.shopee.com/documents/v2/v2.global_product.get_global_model_list?module=90&type=1)

  

1\. If the global product does not contain variants, you only need to call [v2.global\_product.get\_global\_item\_info](https://open.shopee.com/documents/v2/v2.global_product.get_global_item_info?module=90&type=1) to get the global product information, otherwise you also need to call [v2.global\_product.get\_global\_model\_list](https://open.shopee.com/documents/v2/v2.global_product.get_global_model_list?module=90&type=1) to get the variants stock and price information.

  

2\. Product data and promotion information are not saved on global products.

You can call [v2.product.get\_item\_extra\_info](https://open.shopee.com/documents/v2/v2.product.get_item_extra_info?module=89&type=1) to get the shop product data and [v2.product.get\_item\_promotion](https://open.shopee.com/documents/v2/v2.product.get_item_promotion?module=89&type=1) to get the promotion data of shop products.

  

# 12\. Updating global products

API: [v2.global\_product.update\_global\_item](https://open.shopee.com/documents/v2/v2.global_product.update_global_item?module=90&type=1)

  

1\. Since some fields can be managed by global products and shop products together, you can check the article "[Creating global product](https://open.shopee.com/developer-guide/213)" for details. You can set the fields synchronization through [v2.global\_product.set\_sync\_field](https://open.shopee.com/documents/v2/v2.global_product.set_sync_field?module=90&type=1) API, then Shopee will automatically synchronize to shop products after you update the global product.

# 13\. Deleting global product

API: [v2.global\_product.delete\_global\_item](https://open.shopee.com/documents/v2/v2.global_product.delete_global_item?module=90&type=1)

Global products do not support unlist, but can only be deleted. After the global products are deleted, all published shop products will also be deleted.

# 14\. Updating the size chart image

API: [v2.global\_product.update\_size\_chart](https://open.shopee.com/documents/v2/v2.global_product.update_size_chart?module=90&type=1)

  

This API allows you to add or update the size chart of global products. If you want to update the size chart of a shop product individually, you can call [v2.product.update\_size\_chart](https://open.shopee.com/documents/v2/v2.product.update_size_chart?module=89&type=1) API.

  

You can check this [FAQ](https://open.shopee.com/faq/162) to learn more about which product module interface permissions CNSC/KRSC sellers have.
