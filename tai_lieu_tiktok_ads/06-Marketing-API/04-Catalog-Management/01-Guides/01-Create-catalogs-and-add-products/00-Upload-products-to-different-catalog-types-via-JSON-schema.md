---
title: "Upload products to different catalog types via JSON schema"
doc_id: 1766862002851842
path: "Marketing API / Catalog Management / Guides / Create catalogs and add products / Upload products to different catalog types via JSON schema"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload products to different catalog types via JSON schema

This article walks you through the steps to upload products to various types of catalog using a JSON schema.

# Introduction
A catalog is an asset that allows you to store information about the products you want to promote on TikTok. 

**By leveraging Catalog Management API and Catalog Products API, you can efficiently upload products, enhancing operational efficiency and scalability.**


# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To upload products to a catalog, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the <strong>"Steps"</strong> section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions. 
  
# Steps
## 1. Create a catalog
Create a catalog using [/catalog/create/](https://ads.tiktok.com/marketing_api/docs?id=1740306481704961). Specify the desired `catalog_type` for the catalog that you want to create.
> <span style="color:DodgerBlue">**Note**</span><br>
> - Entertainment catalogs are currently an allowlist-only feature and is invitation-only because the catalog type is under Alpha Testing. If you would like to access it, please contact your TikTok representative. However, acceptance into the Alpha Test is not guaranteed.
> - The mini series catalog is currently an allowlist-only feature and is invitation-only because the catalog type is under testing. If you would like to access it, please contact your TikTok representative. However, acceptance into the test is not guaranteed. 

### Example
#### Request
##### Create an E-commerce catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "name": "{{name}}",
    "catalog_type": "ECOM",
    "catalog_conf": {
        "region_code": "US",
        "currency": "USD"
        }
    }'
(/code)
```
##### Create a hotel catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "name": "{{name}}",
    "catalog_type": "HOTEL",
    "catalog_conf": {
        "region_code": "US",
        "currency": "USD"
        }
    }'
(/code)
```
##### Create a flight catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "name": "{{name}}",
    "catalog_type": "FLIGHT",
    "catalog_conf": {
        "region_code": "US",
        "currency": "USD"
        }
    }'
(/code)
```

##### Create a destination catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "name": "{{name}}",
    "catalog_type": "DESTINATION",
    "catalog_conf": {
        "region_code": "US",
        "currency": "USD"
        }
    }'

(/code)
```

##### Create an entertainment catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "name": "{{name}}",
    "catalog_type": "ENTERTAINMENT",
    "catalog_conf": {
        "region_code": "US",
        "currency": "USD"
    }
}'
(/code)
```

##### Create an Auto-Inventory catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "name": "{{name}}",
    "catalog_type": "AUTO_VEHICLE",
    "catalog_conf": {
        "region_code": "US",
        "currency": "USD"
    }
}'
(/code)
```
##### Create an Auto-Model catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "name": "{{name}}",
    "catalog_type": "AUTO_MODEL",
    "catalog_conf": {
        "region_code": "US",
        "currency": "USD"
    }
}'
(/code)
```

##### Create a mini series catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "name": "{{name}}",
    "catalog_type": "MINI_SERIES",
    "catalog_conf": {
        "region_code": "US",
        "currency": "USD"
    }
}'
(/code)
```

#### Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "catalog_id": "{{catalog_id}}"
    }
}
(/code)
```
## 2. Upload products
Upload products using [/catalog/product/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740497429681153). Pass in the `catalog_id` obtained from Step 1 and specify `products` object array parameters based on the catalog type that you want to upload products to. To learn about the parameters available for different catalog types, refer to the section [Upload products via a JSON schema-products object parameters](https://ads.tiktok.com/marketing_api/docs?id=1740497429681153#item-link-products%20object%20array%20parameters).
### Example
#### Request
##### Upload a product to an E-commerce catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/product/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "catalog_id": "{{catalog_id}}",
    "products": [
        {
            "sku_id": "{{sku_id}}",
            "title": "{{title}}",
            "description": "{{description}}",
            "availability": "IN_STOCK",
            "image_url": "{{image_url}}",            
            "brand": "{{brand}}",
            "additional_image_urls": ["{{additional_image_url}}", "{{additional_image_url}}"],
            "item_group_id": "{{item_group_id}}",
            "google_product_category": "{{google_product_category}}",
            "global_trade_item_number": "{{global_trade_item_number}}",
            "manufacturer_part_number": "{{manufacturer_part_number}}",
            "product_detail": {
                    "condition": "NEW",
                    "age_group": "ADULT",
                    "color": "{{color}}",
                    "gender": "UNISEX",
                    "material": "{{material}}",
                    "pattern": "{{pattern}}",
                    "product_category": "{{product_category}}",
                    "shipping": "US:CA:Ground:9.99 USD",
                    "size": "{{size}}",
                    "tax": "{{tax}}"
            },
            "price_info": {
                "price": {{price}},
                "currency":"USD",
                "sale_price": {{sale_price}},
                "sale_price_effective_date": "2023-12-11T23:59+00:00/2023-12-15T23:59+00:00"
            },
            "landing_page": {
                "landing_page_url": "{{landing_page_url}}"
            },
            "extra_info": {
                "custom_label_0": "{{custom_label_0}}",
                "custom_label_1": "{{custom_label_1}}",
                "custom_label_2": "{{custom_label_2}}",
                "custom_label_3": "{{custom_label_3}}",
                "custom_label_4": "{{custom_label_4}}"
            }
        }
    ]
}'
(/code)
```
##### Upload a product to a hotel catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/product/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "catalog_id": "{{catalog_id}}",
    "products": [
        {
            "hotel_id": "{{hotel_id}}",
            "name": "{{name}}",
            "description": "{{description}}",
            "availability": "IN_STOCK",
            "image_url": "{{image_url}}",            
            "brand": "{{brand}}",
            "additional_image_urls": ["{{additional_image_url}}", "{{additional_image_url}}"],
            "hotel_retailer_id": "{{hotel_retailer_id}}",
            "address": {
                "address": "{{address}}",
                "city": "{{city}}",
                "region": "{{region}}",
                "country": "{{country}}"
            },
            "latitude": {{latitude}},
            "longitude": {{longitude}},
            "phone": "{{phone}}",
            "margin_level": {{margin_level}},
            "loyalty_program": "{{loyalty_program}}",
            "cancellation_policy": "{{cancellation_policy}}",
            "guest_ratings": [
                {
                    "rating_system": "{{rating_system}}",
                    "max_score": {{max_score}},
                    "score": {{score}},
                    "number_of_reviewers": {{number_of_reviewers}}
                }
            ],
            "star_rating": {{star_rating}},
            "hotel_room_id": "{{hotel_room_id}}",
            "room_type": "{{room_type}}",
            "meal_policy": "{{meal_policy}}",
            "price_info": {
                "price": {{price}},
                "currency":"USD",
                "sale_price": {{sale_price}},
                "total_price": {{total_price}}
            },
            "landing_page": {
                "landing_page_url": "{{landing_page_url}}"
            },
            "extra_info": {
                "custom_label_0": "{{custom_label_0}}",
                "custom_label_1": "{{custom_label_1}}",
                "custom_label_2": "{{custom_label_2}}",
                "custom_label_3": "{{custom_label_3}}",
                "custom_label_4": "{{custom_label_4}}"
            }
        }
    ]
}'
(/code)
```
##### Upload a product to a flight catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/product/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "catalog_id": "{{catalog_id}}",
    "products": [
        {
            "flight_id": "{{flight_id}}",
            "origin_airport": "AAR",
            "destination_airport": "ABD",
            "origin_city": "{{origin_city}}",
            "destination_city": "{{destination_city}}",
            "description": "{{description}}",
            "availability": "IN_STOCK",
            "cabin_class": "{{cabin_class}}",
            "airline_company": "{{airline_company}}",
            "airline_id": "{{airline_id}}",           
            "image_url": "{{image_url}}",            
            "additional_image_urls": ["{{additional_image_url}}", "{{additional_image_url}}"],
            "price_info": {
                "price": {{price}},
                "currency":"USD",
                "sale_price": {{sale_price}},
                "total_price": {{total_price}}
            },
            "landing_page": {
                "landing_page_url": "{{landing_page_url}}"
            },
            "extra_info": {
                "custom_label_0": "{{custom_label_0}}",
                "custom_label_1": "{{custom_label_1}}",
                "custom_label_2": "{{custom_label_2}}",
                "custom_label_3": "{{custom_label_3}}",
                "custom_label_4": "{{custom_label_4}}"
            }
        }
    ]
}'
(/code)
```

##### Upload a product to a destination catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/product/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "bc_id": "{{bc_id}}",
    "catalog_id": "{{catalog_id}}",
    "products": [
        {
            "destination_id": "{{destination_id}}",
            "destination_name": "{{destination_name}}",
            "description": "{{description}}",
            "image_url": "{{image_url}}",
            "additional_image_urls": [
                "{{additional_image_url}}",
                "{{additional_image_url}}"
            ],    
            "address": {
                "address": "{{address}}",
                "city": "{{city}}",
                "region": "{{region}}",
                "country": "{{country}}"
            },
            "postal_code": "{{postal_code}}",          
            "latitude": {{latitude}},
            "longitude": {{longitude}},
            "types": [
                "{{type}}",
                "{{type}}"
            ],
            "price_info": {
                "price": {{price}},
                "sale_price": {{sale_price}}
            },
            "landing_page": {
                "landing_page_url": "{{landing_page_url}}"
            },
            "extra_info": {
                "custom_label_0": "{{custom_label_0}}",
                "custom_label_1": "{{custom_label_1}}",
                "custom_label_2": "{{custom_label_2}}",
                "custom_label_3": "{{custom_label_3}}",
                "custom_label_4": "{{custom_label_4}}"
           }
        }
    ]
}'
(/code)
```

##### Upload a product to an entertainment catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/product/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "catalog_id": "{{catalog_id}}",
    "products": [
        {  
            "media_title_id": "{{media_title_id}}",
            "title": "{{title}}",
            "description": "{{description}}",
            "availability": "AVAILABLE",
            "image_url": "{{image_url}}",
            "video_url": "{{video_url}}",
            "additional_image_urls": [
                "{{additional_image_url}}", "{{additional_image_url}}"
            ],
            "product_detail": {
                "condition": "USED"
            },
            "star_rating": 5.2,
            "timeline": "COMING_SOON",
            "category": "LIVE_EVENT",
            "genres": [
                "TEEN",
                "SITCOM"
            ],
            "qid": "{{qid}}",
            "price_info": {
                "price": {{price}},
                "currency": "USD"
            },
        "landing_page":{
                "landing_page_url": "{{landing_page_url}}"
            },
            "extra_info": {
                "custom_label_0": "{{custom_label_0}}",
                "custom_label_1": "{{custom_label_1}}",
                "custom_label_2": "{{custom_label_2}}",
                "custom_label_3": "{{custom_label_3}}",
                "custom_label_4": "{{custom_label_4}}"
            }
        }   
    ]
}'
(/code)
```

##### Upload a product to an Auto-Inventory catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/product/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "catalog_id": "{{catalog_id}}",
    "products": [
        {
            "vehicle_id": "{{vehicle_id}}",
            "title": "{{title}}",
            "description": "{{description}}",
            "availability": "AVAILABLE",     
            "image_url": "{{image_url}}",            
            "additional_image_urls": ["{{additional_image_url}}", "{{additional_image_url}}"],
            "product_detail":{"condition":"EXCELLENT"},
            "state_of_vehicle":"USED",
            "vehicle_type":"CAR_TRUCK",
            "make":"{{make}}",
            "model":"{{model}}",
            "trim":"{{trim}}",
            "year":"{{year}}",
            "vin":"{{vin}}",
            "mileage":{"value":"{{value}}","unit":"MILE"},
            "body_style":"CONVERTIBLE",
            "exterior_color":"{{exterior_color}}",
            "interior_color":"{{interior_color}}",
            "transmission":"AUTOMATIC",
            "drivetrain":"4X4",
            "fuel_type":"ELECTRIC",
            "address": {
                "address": "{{address}}",
                "city": "{{city}}",
                "region": "{{region}}",
                "postal_code": "{{postal_code}}"
            },
            "latitude":{{latitude}},
            "longitude":{{longitude}},
            "dealer":{"dealer_id":"{{dealer_id}}","dealer_name":"{{dealer_name}}","dealer_phone":"{{dealer_phone}}","stock_number":"{{stock_number}}"},
            "date_first_on_lot":"{{date_first_on_lot}}",         
            "price_info": {
                "price": {{price}},
                "sale_price":{{sale_price}},
                "sale_price_effective_date":"{{sale_price_effective_date}}"
            },
            "landing_page": {
                "landing_page_url": "{{landing_page_url}}",
                "ios_url": "{{ios_url}}",
                "android_url": "{{android_url}}"
            },
            "extra_info": {
                "custom_label_0": "{{custom_label_0}}",
                "custom_label_1": "{{custom_label_1}}",
                "custom_label_2": "{{custom_label_2}}",
                "custom_label_3": "{{custom_label_3}}",
                "custom_label_4": "{{custom_label_4}}"
            }
        }
    ]
}'
(/code)
```
##### Upload a product to an Auto-Model catalog
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/product/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "catalog_id": "{{catalog_id}}",
    "products": [
        {
            "vehicle_id": "{{vehicle_id}}",
            "title": "{{title}}",
            "image_url": "{{image_url}}",
            "make": "{{make}}",
            "model": "{{model}}",
            "trim": "{{trim}}",
            "year": 2025,
            "exterior_color": "{{exterior_color}}",
            "product_detail": {
                "offer_type": "LEASE",
                "term_length": "{{term_length}}",
                "offer_term_qualifier": "years",
                "amount_price": "{{amount_price}}",
                "amount_qualifier": "month",
                "downpayment": "{{downpayment}}",
                "downpayment_qualifier": "due at signing",
                "offer_disclaimer": "{{offer_disclaimer}}",
                "offer_disclaimer_url": "{{offer_disclaimer_url}}",
                "emission_disclaimer": "{{emission_disclaimer}}",
                "emission_disclaimer_url": "{{emission_disclaimer_url}}",
                "emission_overlay_disclaimer": "{{emission_overlay_disclaimer}}",
                "emission_image_link": "{{emission_image_link}}"
            },
            "landing_page": {
                "landing_page_url": "{{landing_page_url}}"
            }
        }
    ]
}'
(/code)
```

##### Upload a product to a mini series catalog
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/product/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "catalog_id": "{{catalog_id}}",
    "products": [
        {
            "series_id": "{{series_id}}",
            "series_name": "{{series_name}}",
            "image_url": "{{image_url}}",
            "recharge": [
                {
                    "type": "BY_TIERS",
                    "purchase_unit": ["TIER_1", "TIER_2", "TIER_3", "TIER_4", "TIER_5"],
                    "cost": ["{{cost}}", "{{cost}}", "{{cost}}"]
                },
                {
                    "type": "BY_EPISODES",
                    "purchase_unit": ["1", "10", "60"],
                    "cost": ["{{cost}}", "{{cost}}", "{{cost}}"]
                },
                {
                    "type": "SUBSCRIPTION",
                    "purchase_unit": ["WEEKLY", "MONTHLY", "QUARTERLY", "YEARLY"],
                    "cost": ["{{cost}}", "{{cost}}", "{{cost}}", "{{cost}}"]
                }
            ],
            "product_detail": {
                "company_type": "COPYRIGHT_HOLDER",
                "copyright_holder_name": "{{copyright_holder_name}}",
                "app_id": "{{app_id}}",
                "minis_id": "{{minis_id}}",
                "total_episodes": 60,
                "initial_paid_episodes": 10,
                "per_episode_duration": 3,
                "spoken_language": "zh",
                "subtitle_language": "en",
                "production_type": "TRANSLATION",
                "target_audience": "FEMALE",
                "characters": ["ALPHA_MALE", "EXTRAORDINARY_TALENT"],
                "genres": ["LOVE_IN_WEALTHY_FAMILIES", "INDULGENT_LOVE", "LOVE_AFTER_MARRIAGE"],
                "historical_context": ["ANCIENT_SETTING", "SECOND_CHANCE", "FICTIONAL"],
                "actors": ["{{actor}}", "{{actor}}", "{{actor}}"]
            }
        }
    ]
}'
(/code)
```

#### Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "feed_log_id": "{{feed_log_id}}"
    }
}
(/code)
```
## 3. Check the product handling results
Check the product handling results using [/catalog/product/log/](https://ads.tiktok.com/marketing_api/docs?id=1740570027173889). Pass in the `feed_log_id` obtained from Step 2. If the field `error_affected_products` in the response is not null, examine the issue details and return to Step 2 to reupload the product.

### Example
#### Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/catalog/product/log/?bc_id={{bc_id}}&catalog_id={{catalog_id}}&feed_log_id={{feed_log_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
#### Response

##### Unsuccessful product handling results
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "product_feed_log": {
            "warn_count": 1,
            "feed_id": "{{feed_id}}",
            "error_count": 0,
            "update_count": 0,
            "process_status": "SUCCESS",
            "delete_count": 0,
            "add_count": 1,
            "end_time": "2023-05-22 07:53:08",
            "start_time": "2023-05-22 07:53:07",
            "update_mode": "INCREASE",
            "feed_log_data": {
                "warn_affected_products": [
                    {
                        "field": "shipping",
                        "suggestion": "The \"shipping\" field is using an invalid value. Please follow the format: US:CA:Ground:9.99 USD",
                        "affected_product_count": 1,
                        "affected_product_item_list": [
                            {
                                "title": "{{title}}",
                                "product_url": "{{product_url}}",
                                "sku_id": "{{sku_id}}",
                                "value": "US: CA: Ground: 9.99 USD: Standard",
                                "index": 2,
                                "description": "{{description}}"
                            }
                        ],
                        "issue": "Invalid value: shipping"
                    },
                    {
                        "field": "sale_price_effective_date",
                        "suggestion": "The \"sales_price_effective_date\" field is using an invalid value. Please follow the format: YYYY-MM-DDT23:59+00:00/YYYY-MM-DDT23:59+00:00",
                        "affected_product_count": 1,
                        "affected_product_item_list": [
                            {
                                "title": "{{title}}",
                                "product_url": "{{product_url}}",
                                "sku_id": "{{sku_id}}",
                                "value": "2017-12-01T0: 00/2017-12-31T0: 00",
                                "index": 2,
                                "description": "{{description}}"
                            }
                        ],
                        "issue": "Invalid value: sale_price_effective_date"
                    }
                ],
                "error_affected_products": null,
                "download_path": {
                    "id": "{{download_path}}",
                    "zh-CN": "{{download_path}}",
                    "vi": "{{download_path}}",
                    "hi-IN": "{{download_path}}",
                    "it": "{{download_path}}",
                    "fr": "{{download_path}}",
                    "en": "{{download_path}}",
                    "ms": "{{download_path}}",
                    "tr": "{{download_path}}",
                    "es": "{{download_path}}",
                    "ja-JP": "{{download_path}}",
                    "ko": "{{download_path}}",
                    "th": "{{download_path}}",
                    "de": "{{download_path}}"
                }
            },
            "catalog_id": "{{catalog_id}}"
        }
    }
}
(/code)
```
##### Successful product handling results
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "product_feed_log": {
            "warn_count": 0,
            "delete_count": 0,
            "end_time": "2023-05-22 07:58:05",
            "catalog_id": "{{catalog_id}}",
            "feed_log_data": {
                "warn_affected_products": null,
                "error_affected_products": null,
                "download_path": {}
            },
            "feed_id": "{{feed_id}}",
            "add_count": 0,
            "update_count": 1,
            "update_mode": "INCREASE",
            "error_count": 0,
            "process_status": "SUCCESS",
            "start_time": "2023-05-22 07:58:05"
        }
    }
}
(/code)
```

## Related docs
- [Catalog Management API](https://ads.tiktok.com/marketing_api/docs?id=1740132866693122)
- [Catalog Products API](https://ads.tiktok.com/marketing_api/docs?id=1740132940622850)
- [Catalog Product Sets API](https://ads.tiktok.com/marketing_api/docs?id=1740132953884673)
- [Create Video Shopping Ads with products from catalogs](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249)
- [Create Automotive Ads for Inventory](https://business-api.tiktok.com/portal/docs?id=1827752507854850)
- [Create Automotive Ads for Models](https://business-api.tiktok.com/portal/docs?id=1829635758164994)

