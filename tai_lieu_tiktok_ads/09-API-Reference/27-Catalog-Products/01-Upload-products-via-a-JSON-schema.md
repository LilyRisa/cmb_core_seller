---
title: "Upload products via a JSON schema"
doc_id: 1740497429681153
path: "API Reference / Catalog Products / Upload products via a JSON schema"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload products via a JSON schema

Use this endpoint to upload products to the catalog in batch and you can upload up to 5000 products at a time. The system will enter the products specified in the request asynchronously, and at the same time, you will get a task processing ID, `feed_log_id`. With this ID you can view the processing status via the [/catalog/product/log/](https://ads.tiktok.com/marketing_api/docs?id=1740570027173889) endpoint.

Different from [/catalog/product/file/](https://ads.tiktok.com/marketing_api/docs?id=1740496787164161), this endpoint is mainly used for uploading a small number of products and updating some fields. For product catalogs with high update frequency (higher than once a day), it is recommended to upload products with this endpoint.

>  <span style="color:DodgerBlue">**Note**</span><br>
>  <ul><li>The SKU is unique under the same E-commerce catalog and only belongs to a certain feed. All operations on a SKU need to be performed under the feed to which it belongs.</li><li>You can make up to 1,000 requests per hour for each product catalog.</li><li>With this endpoint, products are updated incrementally. Data for existing products will not be replaced unless you upload the same product again.</li><li>Product images must be 500x500 px or larger in size. Otherwise, the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770).</li></ul>

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/product/upload/| /v1.3/catalog/product/upload/|
|Request parameter name |`dpa_products`<br>`image_link`<br>`video_link`<br> `additional_image_link`<br>`gtin`<br>`mpn`<br> `profession`<br> `product_type`<br>`price`<br>`landing_url`<br>`link`<br>`ext`|`products`<br>`image_url`<br>`video_url`<br>`additional_image_urls`<br>`global_trade_item_number`<br>`manufacturer_part_number`<br>`product_detail`<br>`product_category`<br>`price_info`<br>`landing_page`<br>`landing_page_url`<br>`extra_info`|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number|`bc_id`: string<br>`catalog_id`: string|
|Request parameter name and data type |`feed_ids`: number|`feed_id`: string|
|New request parameters |/|For hotel catalogs: <br>`hotel_id`<br>`name`<br>`hotel_retailer_id`<br> `address`<br>`postal_code`<br>`latitude`<br>`longitude` <br>`margin_level` <br>`loyalty_program`<br>`guest_ratings`<br>`star_rating`<br>`room_type`<br>`hotel_category`<br>`neighborhood`<br> `priority` <br> `internal_label_0`<br> `internal_label_1` <br> For flight catalogs:<br> `origin_airport`<br>`destination_airport` <br>`origin_city`<br>`destination_city`<br> `cabin_class`<br>`airline_company`<br>`priority`<br>`internal_label_0`<br>`internal_label_1`<br> For destination catalogs:<br>`destination_id` <br>`destination_name` <br>`address`<br> `postal_code`<br> `latitude`<br>`longitude`<br>`types`<br>`neighborhood` <br>For entertainment catalogs: <br>`media_title_id`<br>`genres`<br>`qid`<br>For Auto-Inventory catalogs<br>`vehicle_id`<br>`address`<br>`latitude` <br>`longitude`<br>`state_of_vehicle`<br>`vehicle_type` <br>`make`<br>`model`<br>`trim`<br>`year` <br>`vin`<br>`mileage`<br>`body_style`<br>`exterior_color`<br>`interior_color`<br>`transmission`<br>`drivetrain`<br>`fuel_type`<br>`dealer`<br>`date_first_on_lot`<br>`days_on_lot`<br>`offer_type`<br>`term_length`<br>`offer_term_qualifier`<br>`amount_price`<br>`amount_percentage`<br>`amount_qualifier`<br>`downpayment`<br>`downpayment_qualifier`<br>`offer_disclaimer`<br>`offer_disclaimer_url`<br>`emission_disclaimer`<br>`emission_disclaimer_url`<br>`emission_overlay_disclaimer`<br>`emission_image_link`<br>`video_url`<br>For Auto-Model catalogs:<br>`vehicle_id`<br>`make`<br> `model`<br>`trim`<br>`year`<br>`exterior_color` <br>`offer_type`<br>`term_length`<br>`offer_term_qualifier` <br>`amount_price`<br>`amount_percentage` <br>`amount_qualifier`<br>`downpayment`<br>`downpayment_qualifier`<br>`offer_disclaimer`<br>`offer_disclaimer_url`<br>`emission_disclaimer`<br>`emission_disclaimer_url`<br>`emission_overlay_disclaimer`<br>`emission_image_link`<br>`video_url`<br> For mini series catalogs: <br>`series_id`<br>`series_name` <br>`type`<br>`purchase_unit`<br>`cost` <br>`company_type`<br> `copyright_holder_name`<br>`app_id`<br>`minis_id`<br>`total_episodes`<br>`initial_paid_episodes`<br>`per_episode_duration`<br>`spoken_language`<br>`subtitle_language`<br>`production_type`<br>`target_audience`<br>`characters`<br>`genres`<br>`historical_context`<br>`actors`<br>`target_config`<br>`video_url`|
|Response parameter data type|`feed_log_id`: number|`feed_log_id`: string|
```
## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/product/upload/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request content type. <br>Allowed value: `"application/json"`.  |
```

**Parameters**

``` xtable
| Field {30%}| Data Type {15%}| Description {55%}|
|---|---|---|
| bc_id {Required} | string | Business Center ID. |
| catalog_id {Required} | string | Catalog ID. |
| feed_id | string | Catalog feeds ID. |
| products {Required} | object[] | List of products. <br>For the data fields in this array, see the <strong>"`products` object array parameters"</strong> section below.|
```

### `products` object array parameters 
The parameters in the `products` object array vary based on the type of catalog you want to upload products to. You can determine the type of a catalog according to `catalog_type` returned from [/catalog/get/](https://ads.tiktok.com/marketing_api/docs?id=1740315452868610). Currently, we support eight catalogs: E-commerce, Hotel, Flight, Destination, Entertainment, Auto-Inventory, Auto-Model, and Mini series. To find the parameters you can pass to the `products` object array, refer to the parameter lists below. 

To learn about the steps to upload products to different catalog types using JSON schema, see [here](https://ads.tiktok.com/marketing_api/docs?id=1766862002851842).
 
#### `products` object array parameters for E-commerce catalogs 
 
```xtable
| Field {30%}| Data Type {15%}| Description {55%}|
|---|---|---|
|products {Required} |object[] |List of products that you want to upload. <br><br>Max size: 5,000. |
#| sku_id {Required} | string | A unique ID for the product, such as a SKU. <br>If there are multiple instances of the same ID, these will be ignored. You need to use the same ID to replace the same product in the original data feed if you want to update it. <br>Use only valid Unicode characters, avoid invalid characters like control or function characters. <br>Length limit: 100 characters and cannot contain emojis. <br>Example: TikTok_item_1234. <br><p><span style="color:darkred"><b>Note</b></span>: Duplicate SKU IDs are not supported. |
#| title {Required} | string | The title of the product. <br>Use only valid Unicode characters, and avoid invalid characters like control, function characters or emoji. <br>Do not include promotional text like "free shipping, high quality, wholesale". <br>Length limit: 500 characters and cannot contain emojis. <br>Example: T-Shirt (Unisex). |
#| description {Required} | string | A short description of the product. <br>Length limit: 10,000 characters. <br>Example: A vibrant crewneck for all shapes and sizes. Made from 100% cotton. |
#| availability {Required} | string | The current availability of the product in your store. <br>Enum values: <ul><li>`IN_STOCK`: in stock.</li><li>`AVAILABLE_FOR_ORDER`: available for order. </li><li>`PREORDER`: available for pre-order. </li><li>`OUT_OF_STOCK`: out of stock.</li><li>`DISCONTINUED`: discontinued. </li></ul>Example: `IN_STOCK`. |
#| brand {Required} | string | Brand name for the product. <br>Length limit: 150 characters. <br>Example: TikTok . |
#| image_url {Required} | string | The URL for the product image. <br>The image must be 500x500 px or larger in size. Otherwise, the image will be filtered out, and the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770). <br>All images should be in JPG or PNG format. <br>Example: `https://www.tiktok.com/t_shirt_image_001.jpg`. |
#| additional_image_urls | string[] | Additional image URLs for the product. <br>Max size: 10. <br>The image must be 500x500 px or larger in size. Otherwise, the image will be filtered out, and the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770). <br>All images should be in JPG or PNG format. <br>Example: `["https://www.tiktok.com/t_shirt_image_002.jpg","https://www.tiktok.com/t_shirt_image_003.jpg"]`. <br><br><p><span style="color:darkred"><b>Note</b></span>: If your additional image URL contains commas (,), URL-encode them by replacing with %2C before adding them to this field.<br>For example, for URLs such as `https://img.example.com/product.jpg?width=800&height=600&quality=85&format=webp`and `https://img.example.com/product_800x600<b>,</b>q85<b>,</b>webp.jpg`, you need to set this field to `["https://img.example.com/product.jpg?width=800&height=600&quality=85&format=webp","https://img.example.com/product_800x600<b>%2C</b>q85<b>%2C</b>webp.jpg"]`.|
#| video_url | string | The URL for the video used in your ad. <br>Recommended video aspect ratio 9: 16 (Vertical); <br>Recommended resolution for TikTok placement: ≥720*1280; <br>Recommended bitrate for TikTok placement: ≥516kbps; <br>Sound: enabled with subtitles. <br>Example: `https://www.tiktok.com/tiktok_t_shirt`. |
#| item_group_id | string | Product SPU ID. <br>Items that are variants of a specific product. Provide the same `item_group_id` for all items that are variants. For example, a red Polo Shirt is a variant of Polo Shirt. <br>Example: TikTok_1234_sample. |
#| google_product_category | string | A preset value from Google's product taxonomy. <br>Only supports three levels. <br> Please refer to the Google website: https://support.google.com/merchants/answer/6324436?hl=en <br>Example: Apparel & Accessories> Clothing> Shirts. |
#| global_trade_item_number | string | The Global Trade Item Number (GTIN) for the product. <br>Example: 3234567890126. |
#| manufacturer_part_number | string | The Manufacturer Part Number (MPN) for the product. <br>Example: 12433. |
#| product_detail {Required} | object | Product other information. |
##| condition {Required} | string | The current condition of the product in your store. <br>Enum values: <ul><li>`NEW`: new.</li><li>`REFURBISHED`: refurbished. </li><li> `USED`: used.</li></ul>Example: `NEW`. |
##| age_group | string | The age group for the product. The accuracy of this field is important to ensure products are delivered to the intended age demographics. <br>Enum values: <ul><li>`NEW_BORN`: newborn (0-3 months old).</li><li>`INFANT`: infant (3-12 months old).</li><li>`TODDLER`: toddler (1-5 years old). </li><li>`KIDS`: kids (5-13 years old).</li><li>`ADULT`: adult (13 years old or older).</li></ul>Example: `ADULT`. |
##| color | string | The color of the product. <br>Length limit: 100 characters. <br>Example: blue. |
##| gender | string | The targeted gender for the product. The accuracy of this field is important to ensure products are delivered to the intended gender group. <br>Enum values: `MALE`, `FEMALE`, `UNISEX`. <br>Example: `UNISEX`. |
##| material | string | The material that the product is made from, such as cotton, denim, or leather. <br>Length limit: 200 characters. <br> Example: cotton. |
##| pattern | string | The pattern or graphic printing on the product. <br>Length limit: 100 characters. <br>Example: stripes1. |
##| product_category | string | The category that the product belongs to. <br>Only the first three levels are required. <br>Example: Apparel & Accessories> Clothing> Shirts. |
##| shipping | string | The type of shipping for the product. <br>Recommended format: `COUNTRY:STATE:SHIPPING_TYPE: PRICE`.<br>Example: `US:CA:Ground:9.99 USD`. |
##| shipping_weight | string | The shipping weight of the product. <br>The allowed units are lb, oz, g, kg. <br>Example: 1 oz, 1 kg. |
##| size | string | The size of the product. <br>Example: Small, Large，Extra Large. |
##| tax | string | The tax for the product. |
#| price_info {Required} | object | Price information. |
##| price {Required} | float | The base price of the product. <br>Example: 30. |
##| currency | string | Unit of currency. <br> If specified, this field must follow the catalog's target country. <br> If this field is not specified, the unit of currency for the catalog's target country will be used by default. <br>Example: USD. |
##| sale_price| float | The discounted price of the product if it's on sale. <br> Example: 20. |
##| sale_price_effective_date| string | The start and end date and time of sale, in the format of `YYYY-MM-DDT23:59+00:00/YYYY-MM-DDT23:59+00:00`. <br>Use a start date before the end date. <br>Do not pass this field if the sale price of a product is available endlessly. Example: `2023-12-11T23:59+00:00/2023-12-15T23:59+00:00`. |
#| landing_page {Required} | object | Landing page information. |
##| landing_page_url {Required} | string | URL of the landing page where you can view and purchase the product. <br> Example: `https://www.tiktok.com/tiktok_t_shirt`. |
##| ios_url | string | Custom scheme for iOS apps as URL. For iOS, provide both iPhone & iPad app information if they are different from the regular iOS app. <br>Example: iOS://electronic |
##| ios_app_store_id| string | App Store ID for the iOS app. <br> Example: 1234. |
##| ios_app_name | string | The iOS App name to display. <br>Example: Electronic iOS. |
##| iphone_app_store_id | string | App Store ID for the iPhone app. <br> Example: 1234. |
##| iphone_app_name | string | The iPhone App name to display. <br>Example: Electronic iOS. |
##| ipad_app_store_id | string | App Store ID for the iPad app. <br>Example: 1234. |
##| ipad_app_name| string | The iPad App name to display. <br>Example: Electronic iOS. |
##| android_url | string | Custom scheme for Android apps as URL. <br> Example: android://electronic |
##| android_package| string | The Android package name. <br> Example: com.electronic. |
##| android_app_name | string | The Android App name to display. <br>Example: Electronic Android. |
#| extra_info| object | Additional information. |
##| custom_label_0 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
##| custom_label_1 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
##| custom_label_2 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
##| custom_label_3 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
##| custom_label_4 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
```


#### `products` object array parameters for hotel catalogs 


```xtable
| Field {30%}| Data Type {15%}| Description {55%}|
|---|---|---|
|products {Required} |object[] |List of products that you want to upload. <br><br>Max size: 5,000. |
#| hotel_id {Required} | string | A unique ID for the hotel. <br>Length limit: 100 characters. |
#| name {Required} | string | The name of the hotel. <br>Length limit: 150 characters. |
#| description  | string | A short description of the hotel room. <br>Length limit: 10,000 characters.<br>Example: `Free Wifi, Free Breakfast, Free Cancellation` |
#| availability  | string | The current availability of the hotel room. <br> Enum values: <ul><li> `IN_STOCK`: Available for booking.</li><li>`OUT_OF_STOCK`: Unavailable for booking.</li></ul> Example: `IN_STOCK`. |
#| brand {Required} | string | The brand name of the hotel. <br>Length limit: 150 characters. |
#| image_url {Required} | string | The URL for the hotel room image. <br>The image must be 500x500 pixels or larger in size. Images smaller than this size will be filtered out, and the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770). <br>All images should be in JPG or PNG format. <br>Example: `https://www.tiktok.com/hotel_room_image_001.jpg`. |
#| additional_image_urls | string[] | Additional image URLs for the hotel room. <br>Max size: 20. <br> The image must be 500x500 pixels or larger in size. Images smaller than this size will be filtered out, and the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770). <br>All images should be in JPG or PNG format. <br> Example: `["https://www.tiktok.com/hotel_room_image_002.jpg","https://www.tiktok.com/hotel_room_image_003.jpg"]`. <br><br><p><span style="color:darkred"><b>Note</b></span>: If your additional image URL contains commas (,), URL-encode them by replacing with %2C before adding them to this field.<br>For example, for URLs such as `https://img.example.com/product.jpg?width=800&height=600&quality=85&format=webp`and `https://img.example.com/product_800x600<b>,</b>q85<b>,</b>webp.jpg`, you need to set this field to `["https://img.example.com/product.jpg?width=800&height=600&quality=85&format=webp","https://img.example.com/product_800x600<b>%2C</b>q85<b>%2C</b>webp.jpg"]`.|
#| video_url | string | The URL for the video used in your ad. <br>Recommended video aspect ratio 9: 16 (Vertical); <br>Recommended resolution for TikTok placement: ≥720*1280; <br>Recommended bitrate for TikTok placement: ≥516kbps; <br>Sound: enabled with subtitles. <br>Example: `https://www.tiktok.com/hotel.mp4`. |
#| hotel_category | string[] | The categories of the hotel. <br><br>Max size: 3.<br><br>For enum values, see <a href="#item-link-List of values for hotel_category">List of values for `hotel_category`</a>.|
#| hotel_retailer_id | string | A unique ID for the hotel retailer. <br><br>Length limit: 100 characters. |
#| address {Required} | object | Address information. |
##| address {Required} | string | Full name of the primary street address of hotel. <br><br>Length limit: 150 characters. |
##| secondary_address|string|The secondary street address of the hotel.<br><br>Length limit: 150 characters.|
##| tertiary_address|string|The tertiary street address of the hotel.<br><br>Length limit: 150 characters.|
##| city {Required} | string | The city where the hotel is located. <br> <br>Length limit: 150 characters. |
##| region | string | The region (state/province) where the hotel is located. <br><br>Length limit: 150 characters. |
##| country {Required} | string | The country where the hotel is located. <br><br>Length limit: 150 characters. |
#| neighborhood | string | The neighborhood where the hotel is located.<br> <br>Length limit: 100 characters.  <br> <br>Example: `Bangkok Old Town`. |
#| postal_code|string|The postal code or zip code of the hotel. <br>Length limit: 150 characters.|
#| latitude | float| The latitude of the hotel's location. <br>Value range: [-90, 90]. <br>Example: 37.484100 |
#| longitude | float | The longitude of the hotel's location. <br>Value range: [-180, 180]. <br>Example: -122.148252 |
#| margin_level | integer | The percentage of the base price (`price`) for a hotel room that the hotel charges as deposit. <br>Value range: [1, 10]. <br>For instance, a value of 10 means the hotel charges 10 percent of the base price for a hotel room as deposit. |
#| loyalty_program| string |The loyalty program offered by the hotel.<br>For enum values, see <a href="#item-link-List of values for loyalty_program">List of values for `loyalty_program`</a>.|
#| guest_ratings | object[] | Information about guest ratings for the hotel. <br>Max size: 1. |
##| rating_system | string | The rating system for the hotel. <br>Length limit: 150 characters. <br>Example: `Expedia`, `Tripadvisor`. |
##| max_score | integer | The highest score that the rating system offers. <br>Value range: [0,100]. |
##| score | float | The actual score of the hotel. <br>Value range: ≥0. |
##| number_of_reviewers | integer | The number of guests who have reviewed the hotel. <br>Value range: ≥0. |
#| star_rating| integer | Hotel star rating that is assigned by professional organizations or travel review agencies based on a set of criteria. The highest possible rating is usually 5 stars. Uploading it might help increase Page View Rate. |
#| room_type | string[] | The hotel room types.<br><br>For enum values, see <a href="#item-link-List of values for room_type">List of values for `room_type`</a>.<br><br>Max size: 3.|
#| priority | integer | The priority of the hotel. <br> <br>Value range: 0-5. <br>Default value: 0. <br>A higher value indicates a higher priority. |
#| price_info{Required} | object | Price information. |
##| price {Required} | float | The base price for one night in the hotel room. <br>Example: 30. |
##| currency | string | Unit of currency. <br> If specified, this field must follow the catalog's target country. <br> If this field is not specified, the unit of currency for the catalog's target country will be used by default. <br>Example: USD. |
##| sale_price| float | The discounted price for one night in the hotel room. <br>The `sale_price` should be less than `price`.<br><br>Example: 20. |
#| landing_page | object | Landing page information. |
##| landing_page_url  | string | URL of the landing page where you can view and book a room in the hotel. <br>Example: `https://www.tiktok.com/tiktok_hotel`. |
##| ios_url | string | Custom scheme for iOS apps as URL. For iOS, provide both iPhone & iPad app information if they are different from the regular iOS app. <br>Example: iOS://electronic |
##| ios_app_store_id| string | App Store ID for the iOS app. <br> Example: 1234. |
##| ios_app_name | string | The iOS App name to display. <br>Example: Electronic iOS. |
##| android_url | string | Custom scheme for Android apps as URL. <br> Example: android://electronic |
##| android_package| string | The Android package name. <br> Example: com.electronic. |
##| android_app_name | string | The Android App name to display. <br>Example: Electronic Android. |
#| extra_info| object | Additional information. |
##| custom_label_0 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
##| custom_label_1 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
##| custom_label_2 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
##| custom_label_3 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
##| custom_label_4 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
##| internal_label_0|string|An internal label assigned to the hotel. You can use internal labels to filter items while creating product sets. These labels are exclusively visible to you.<br><br>Length Limit: 100 characters.<br><br>If you are using custom labels (`custom_label_0` to `custom_label_4`) for filtering items during product set creation, consider switching to internal labels. Unlike custom labels, internal labels can be added or modified as needed without the need for submitting items for review each time, avoiding potential interruptions in ad delivery.|
##| internal_label_1|string|An internal label assigned to the hotel. You can use internal labels to filter items while creating product sets. These labels are exclusively visible to you.<br><br>Length Limit: 100 characters.<br><br>If you are using custom labels (`custom_label_0` to `custom_label_4`) for filtering items during product set creation, consider switching to internal labels. Unlike custom labels, internal labels can be added or modified as needed without the need for submitting items for review each time, avoiding potential interruptions in ad delivery.|
```

##### List of values for `hotel_category`
```xtable
|Value{40%}|Description{60%}|
|---|---|
|`ACCOMMODATION`|Accommodation|
|`APARTMENT`|Apartment|
|`BEACH_HOTEL`|Beach Hotel|
|`BED_BREAKFAST`|Bed & Breakfast|
|`BOAT`|Boats|
|`CABIN`|Cabin|
|`CAMPING`|Camping|
|`CAPSULE_HOTEL`|Capsule Hotel|
|`CASTLE`|Castle|
|`CHALET`|Chalet|
|`CONDO`|Condo|
|`CONDO_RESORT`|Condo Resort|
|`COTTAGE`|Cottage|
|`COUNTRY_HOUSE`|Country House|
|`CRUISE`|Cruise|
|`FAMILY_HOTEL`|Family Hotel|
|`FARM`|Farm|
|`FARM_STAYS`|Farm Stays|
|`GUEST_HOUSE`|Guest House|
|`HOLIDAY_PARK`|Holiday Park|
|`HOSTAL`|Hostal|
|`HOSTEL`|Hostel|
|`HOSTEL_BACKPACKER_ACCOMMODATION`|Hostel/Backpacker|
|`HOTEL`|Hotel|
|`HOUSEBOAT`|Houseboat|
|`INN`|Inn|
|`LODGE`|Lodge|
|`LOVE_HOTELS`|Love Hotels|
|`LUXURY_TENT`|Luxury tents|
|`MOTEL`|Motel|
|`PALACE`|Palace|
|`PENSION`|Pension|
|`POUSADA`|Pousada|
|`PRIVATE_VACATION_HOME`|Private Vacation Home|
|`RANCH`|Ranch|
|`RESIDENCE`|Residence|
|`RESORT`|Resort|
|`RESORT_VILLAGES`|Resort Villages|
|`RIAD`|Riad|
|`RYOKAN`|Ryokan|
|`SAFARI`|Safari|
|`SERVICED_APARTMENT`|Serviced Apartment|
|`TOWNHOUSE`|Townhouse|
|`TREE_HOUSE`|Tree House|
|`VILLA`|Villa|
```
##### List of values for `loyalty_program`
```xtable
|Value{40%}|Description{60%}|
|---|---|
|`ACCOR_LIVE_LIMITLESS_ALL`|Accor Live Limitless (ALL)|
|`AMAN_CLUB`|Aman Club|
|`BANYAN_TREE_DISCOVERY`|Banyan Tree DISCOVERY|
|`BEST_WESTERN_REWARDS`|Best Western Rewards|
|`CHOICE_PRIVILEGES`|Choice Privileges|
|`FOUR_SEASONS_LOYALTY_PROGRAM`|Four Seasons Loyalty Program|
|`HILTON_HONORS`|Hilton Honors|
|`IHG_REWARDS_CLUB`|IHG Rewards Club|
|`KIMPTON_KARMA_REWARDS`|Kimpton Karma Rewards|
|`LEADERS_CLUB_THE_LEADING_HOTELS_OF_THE_WORLD`|Leaders Club (The Leading Hotels of the World)|
|`LOEWS_LOVES_YOU`|Loews Loves You|
|`MARRIOTT_BONVOY`|Marriott Bonvoy|
|`MOTEL_6_MY6`|Motel 6 My6|
|`RADISSON_REWARDS`|Radisson Rewards|
|`SCANDIC_FRIENDS`|Scandic Friends|
|`SONESTA_TRAVEL_PASS`|Sonesta Travel Pass|
|`TRUMP_CARD`|Trump Card|
|`WORLD_OF_HYATT`|World of Hyatt|
|`WYNDHAM_REWARDS`|Wyndham Rewards|
```

##### List of values for `room_type`
```xtable
|Value{40%}|Description{60%}|
|---|---|
|`ACCESSIBLE_ROOM`|Accessible Room|
|`ALLERGY_FREE_ROOM`|Allergy-Free Room|
|`APARTMENT_ROOM`|Apartment Room|
|`APARTMENT_SUITE`|Apartment Suite|
|`ARTIST_ROOM`|Artist Room|
|`BEACHFRONT_ROOM`|Beachfront Room|
|`BUSINESS_ROOM`|Business Room|
|`CABIN_ROOM`|Cabin Room|
|`DELUXE_ROOM`|Deluxe Room|
|`DOUBLE_ROOM`|Double Room|
|`DUPLEX_SUITE`|Duplex Suite|
|`ECO_ROOM`|Eco Room|
|`EXECUTIVE_ROOM`|Executive Room|
|`EXECUTIVE_SUITE`|Executive Suite|
|`FAMILY_ROOM`|Family Room|
|`FITNESS_ROOM`|Fitness Room|
|`FLOATING_ROOM`|Floating Room|
|`GARDEN_SUITE`|Garden Suite|
|`GARDEN_VIEW_ROOM`|Garden View Room|
|`GLAMPING_TENT`|Glamping Tent|
|`GRAND_SUITE`|Grand Suite|
|`HISTORICAL_ROOM`|Historical Room|
|`HONEYMOON_SUITE`|Honeymoon Suite|
|`JACUZZI_ROOM`|Jacuzzi Room|
|`JUNIOR_SUITE`|Junior Suite|
|`LOFT_ROOM`|Loft Room|
|`LOFT_SUITE`|Loft Suite|
|`LUXURY_SUITE`|Luxury Suite|
|`MOUNTAIN_CHALET`|Mountain Chalet|
|`MOUNTAIN_LODGE`|Mountain Lodge|
|`MOUNTAIN_VIEW_ROOM`|Mountain View Room|
|`PENTHOUSE_SUITE`|Penthouse Suite|
|`PET_FRIENDLY_ROOM`|Pet-Friendly Room|
|`POOL_VILLA_ROOM`|Pool Villa Room|
|`POOLSIDE_ROOM`|Poolside Room|
|`PRESIDENTIAL_SUITE`|Presidential Suite|
|`PRIVATE_VILLA_ROOM`|Private Villa Room|
|`ROMANTIC_SUITE`|Romantic Suite|
|`RUSTIC_ROOM`|Rustic Room|
|`SEA_VIEW_ROOM`|Sea View Room|
|`SINGLE_ROOM`|Single Room|
|`SKI_IN_SKI_OUT_ROOM`|Ski-in/Ski-out Room|
|`SKI_ROOM`|Ski Room|
|`SOUNDPROOF_ROOM`|Soundproof Room|
|`STUDIO_APARTMENT`|Studio Apartment|
|`STUDIO_ROOM`|Studio Room|
|`SUITE`|Suite|
|`TERRACE_ROOM`|Terrace Room|
|`TREEHOUSE_ROOM`|Treehouse Room|
|`TWIN_ROOM`|Twin Room|
|`WATER_SUITE`|Water Suite|
|`WELLNESS_ROOM`|Wellness Room|
```

#### `products` object array parameters for flight catalogs 



```xtable
| Field {30%}| Data Type {15%}| Description {55%}|
|---|---|---|
|products {Required} |object[] |List of products that you want to upload. <br><br>Max size: 5,000. |
#| origin_airport {Required} | string | The origin airport of the flight, in the format of a three-letter IATA (International Air Transport Association) code. <br>Example: `AAR`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Within the same flight catalog, you cannot specify flights with the same `origin_airport` and `destination_airport`. |
#| destination_airport {Required} | string | The destination airport of the flight, in the format of a three-letter IATA code. <br>Example: `ABD`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Within the same flight catalog, you cannot specify flights with the same `origin_airport` and `destination_airport`. |
#| origin_city | string | The name of the origin city. <br>Length limit: 150 characters. |
#| destination_city | string | The name of the destination city. <br>Length limit: 150 characters. |
#| description | string | A short description of the flight. <br>Length limit: 10,000 characters. |
#| cabin_class| string | The cabin class of the flight. <br><br>Enum values:<ul><li>`FIRST_CLASS`: First Class. Premium service with luxurious amenities and private spaces.</li><li>`FIRST_CLASS_SUITE`: First Class Suite. Private suites offering luxury and exclusivity.</li><li>`BUSINESS_CLASS`: Business Class. Enhanced comfort and amenities for business and leisure travelers.</li><li>`BUSINESS_CLASS_SUITE`: Business Class Suite. Spacious and private cabins in business class.</li><li>`COMFORT_CLASS`: Comfort Class. Improved seating and service between premium and economy.</li><li>`ECONOMY_CLASS`: Economy Class. Standard travel option with basic amenities at an affordable price.</li><li>`BASIC_ECONOMY`: Basic Economy. Budget-friendly option with limited services and restrictions.</li><li>`STANDARD_ECONOMY`: Standard Economy. Typical economy service with basic features and pricing.</li><li>`PREMIUM_ECONOMY`: Premium Economy. Extra legroom and better in-flight services compared to standard economy.</li><li>`LIE_FLAT_SEAT`: Lie Flat Seat. Fully reclining seats for optimal comfort on long flights.</li><li>`CHARTER_CLASS`: Charter Class. Custom private aircraft hire for tailored travel experiences.</li><li>`ELITE_CLASS`: Elite Class. Top-tier service combining the best features of first and business classes.</li><li>`QUIET_CLASS`: Quiet Class. Noise-reduced area for a serene travel experience.</li></ul> |
#| airline_company | string | The name of the airline company operating the flight. <br><br>Enum values:<ul><li>`AEROFLOT`: Aeroflot.</li><li>`AIR_FRANCE_KLM_GROUP`: Air France-KLM Group.</li><li>`ALASKA_AIRLINES`: Alaska Airlines.</li><li>`ANA`: ANA.</li><li>`AMERICAN_AIRLINES`: American Airlines.</li><li>`BRITISH_AIRWAYS`: British Airways.</li><li>`CATHAY_PACIFIC`: Cathay Pacific.</li><li>`CHINA_SOUTHERN_AIRLINES`: China Southern Airlines.</li><li>`CHINA_EASTERN_AIRLINES`: China Eastern Airlines.</li><li>`DELTA_AIR_LINES`: Delta Air Lines.</li><li>`EMIRATES`: Emirates.</li><li>`JAPAN_AIRLINES`: Japan Airlines.</li><li>`JETBLUE_AIRWAYS`: JetBlue Airways.</li><li>`KLM_ROYAL_DUTCH_AIRLINES`: KLM Royal Dutch Airlines.</li><li>`LUFTHANSA_GROUP`: Lufthansa Group.</li><li>`QANTAS_AIRWAYS`: Qantas Airways.</li><li>`QATAR_AIRWAYS`: Qatar Airways.</li><li>`SINGAPORE_AIRLINES`: Singapore Airlines.</li><li>`SOUTHWEST_AIRLINES`: Southwest Airlines.</li><li>`TURKISH_AIRLINES`: Turkish Airlines.</li><li>`UNITED_AIRLINES`: United Airlines.</li><li>`VIRGIN_ATLANTIC`: Virgin Atlantic.</li></ul>|
#| image_url {Required} | string | The URL for the flight image. <br>The image must be 500x500 px or larger in size. Otherwise, the image will be filtered out, and the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770). <br>All images should be in JPG or PNG format. <br> Example: `https://www.tiktok.com/flight_image_001.jpg`. |
#| additional_image_urls | string | Additional image URLs for the flight. <br>Max size: 20. <br>The image must be 500x500 px or larger is size. Otherwise, the image will be filtered out, and the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770). <br>All images should be in JPG or PNG format. <br>Example: `["https://www.tiktok.com/flight_image_002.jpg","https://www.tiktok.com/flight_image_003.jpg"]`. <br><br><p><span style="color:darkred"><b>Note</b></span>: If your additional image URL contains commas (,), URL-encode them by replacing with %2C before adding them to this field.<br>For example, for URLs such as `https://img.example.com/product.jpg?width=800&height=600&quality=85&format=webp`and `https://img.example.com/product_800x600<b>,</b>q85<b>,</b>webp.jpg`, you need to set this field to `["https://img.example.com/product.jpg?width=800&height=600&quality=85&format=webp","https://img.example.com/product_800x600<b>%2C</b>q85<b>%2C</b>webp.jpg"]`.|
#| video_url | string | The URL for the video used in your ad. <br>Recommended video aspect ratio 9: 16 (Vertical); <br>Recommended resolution for TikTok placement: ≥720*1280; <br>Recommended bitrate for TikTok placement: ≥516kbps; <br>Sound: enabled with subtitles. <br>Example: `https://www.tiktok.com/flight.mp4`. |
#| priority | integer | The priority of the flight. <br> <br>Value range: 0-5. <br>Default value: 0. <br>A higher value indicates a higher priority. |
#| price_info | object | Price information. |
##| price | float | The base price for the flight. <br>Example: 3,000. |
##| currency | string | Unit of currency. <br>If specified, this field must follow the catalog's target country. <br>If this field is not specified, the unit of currency for the catalog's target country will be used by default. <br>Example: USD. |
##| sale_price | float | The discounted price for the flight. <br><br>The `sale_price` should be less than the `price`.<br>Example: 2,500. |
#| landing_page | object | Landing page information. |
##| landing_page_url | string | URL of the landing page where you can book the flight. <br> Example: `https://www.tiktok.com/tiktok_flight`. |
##| ios_url | string | Custom scheme for iOS apps as URL. For iOS, provide both iPhone & iPad app information if they are different from the regular iOS app. <br>Example: iOS://electronic |
##| ios_app_store_id| string | App Store ID for the iOS app. <br> Example: 1234. |
##| ios_app_name | string | The iOS App name to display. <br>Example: Electronic iOS. |
##| android_url | string | Custom scheme for Android apps as URL. <br> Example: android://electronic |
##| android_package| string | The Android package name. <br> Example: com.electronic. |
##| android_app_name | string | The Android App name to display. <br>Example: Electronic Android. |
#| extra_info| object | Additional information. |
##| custom_label_0 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
##| custom_label_1 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
##| custom_label_2 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
##| custom_label_3 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
##| custom_label_4 | string | Additional information about the products you want to include. <br>Length limit: 100 characters. |
##| internal_label_0 | string |An internal label assigned to the flight. You can use internal labels to filter items while creating product sets. These labels are exclusively visible to you.<br><br>Length Limit: 100 characters.<br><br>If you are using custom labels (`custom_label_0` to `custom_label_4`) for filtering items during product set creation, consider switching to internal labels. Unlike custom labels, internal labels can be added or modified as needed without the need for submitting items for review each time, avoiding potential interruptions in ad delivery.|
##| internal_label_1 | string | An internal label assigned to the flight. You can use internal labels to filter items while creating product sets. These labels are exclusively visible to you.<br><br>Length Limit: 100 characters.<br><br>If you are using custom labels (`custom_label_0` to `custom_label_4`) for filtering items during product set creation, consider switching to internal labels. Unlike custom labels, internal labels can be added or modified as needed without the need for submitting items for review each time, avoiding potential interruptions in ad delivery.|
```

#### `products` object array parameters for destination catalogs 


```xtable
| Field {30%}| Data Type {15%}| Description {55%}|
|---|---|---|
|products {Required} |object[] |List of products that you want to upload. <br><br>Max size: 5,000. |
#|destination_id {Required} |string |A unique ID for the destination. <br> Length limit: 100 characters. |
#|destination_name {Required} |string |The name of the destination. <br>Length limit: 150 characters. |
#|description {Required} |string |A short description of the destination. <br> Length limit: 10,000 characters.<br>Example: `A visit to one of the most famous sights in London.` |
#|image_url {Required} |string |The URL for the destination image. <br>The image must be 500x500 pixels or larger in size. Images smaller than this size will be filtered out, and the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770). <br>All images should be in JPG or PNG format. <br>Example: `https://www.tiktok.com/destination_image_001.jpg`. |
#|additional_image_urls |string[] |Additional image URLs for the destination. <br> Max size: 10. <br>The image must be 500x500 pixels or larger in size. Images smaller than this size will be filtered out, and the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770). <br> All images should be in JPG or PNG format. <br>Example: ` &#91;"https://www.tiktok.com/destination_image_002.jpg","https://www.tiktok.com/destination_image_003.jpg"&#93;`. <br><br><p><span style="color:darkred"><b>Note</b></span>: If your additional image URL contains commas (,), URL-encode them by replacing with %2C before adding them to this field.<br>For example, for URLs such as `https://img.example.com/product.jpg?width=800&height=600&quality=85&format=webp`and `https://img.example.com/product_800x600<b>,</b>q85<b>,</b>webp.jpg`, you need to set this field to `["https://img.example.com/product.jpg?width=800&height=600&quality=85&format=webp","https://img.example.com/product_800x600<b>%2C</b>q85<b>%2C</b>webp.jpg"]`.|
#| video_url | string | The URL for the video used in your ad. <br>Recommended video aspect ratio 9: 16 (Vertical); <br>Recommended resolution for TikTok placement: ≥720*1280; <br>Recommended bitrate for TikTok placement: ≥516kbps; <br>Sound: enabled with subtitles. <br>Example: `https://www.tiktok.com/destination.mp4`. |
#|address {Required} |object |Address information. |
##|address {Required} |string |The address of the destination. <br>Length limit: 150 characters. |
##|city {Required} |string |The city where the destination is located. <br>Length limit: 150 characters. |
##|region {Required} |string |The region (state/province) where the destination is located. <br> Length limit: 150 characters. |
##|country {Required} |string |The country where the destination is located. <br> Length limit: 150 characters. |
#| neighborhood | string | The neighborhood where the destination is located.<br> <br>Length limit: 100 characters.  <br> <br>Example: `Bangkok Old Town`. |
#|postal_code |string |Postal code or zip code for the destination. |
#|latitude |float |The latitude of the destination's location. <br>Value range: [-90, 90]. <br>Example: 37.484100 |
#|longitude |float |The longitude of the destination's location. <br>Value range: [-180, 180]. <br>Example: -122.148252 |
#|types {Required} |string[] |The type or types of destination. <br>You can specify one or more types to describe a destination. For instance, for New York city you can set this field to ` &#91;"city","sightseeing"&#93;`. <br>Max size:20. |
#|price_info |object |Price information. |
##|price |float |The average or lowest price for the destination. <br>You can clarify whether this price is the average or the lowest through description. <br>Example: 30. |
##|currency |string |Unit of currency. <br> If specified, this field must follow the catalog's target country. <br> If this field is not specified, the unit of currency for the catalog's target country will be used by default. <br>Example: USD. |
##|sale_price |float |The discounted price for the destination. <br>Example: 20. |
#|landing_page  |object |Landing page information. |
##|landing_page_url  |string |URL of the landing page where you can view the destination. <br>Example: `https://www.tiktok.com/tiktok_destination` |
##|ios_url |string |Custom scheme for iOS apps as URL. <br>Example: iOS://electronic |
##|ios_app_store_id |string |App Store ID for the iOS app. <br>Example: 1234. |
##|ios_app_name |string |The iOS App name to display.<br> Example: Electronic iOS. |
##|android_url |string |Custom scheme for Android apps as URL.<br> Example: android://electronic |
##|android_package |string |The Android package name. <br>Example: com.electronic. |
##|android_app_name |string |The Android App name to display.<br> Example: Electronic Android. |
#|extra_info |object |Additional information. |
##|custom_label_0 |string |Additional information about the products you want to include. <br>Length limit: 100 characters. |
##|custom_label_1 |string |Additional information about the products you want to include. <br>Length limit: 100 characters. |
##|custom_label_2 |string |Additional information about the products you want to include. <br>Length limit: 100 characters. |
##|custom_label_3 |string |Additional information about the products you want to include. <br>Length limit: 100 characters. |
##|custom_label_4 |string |Additional information about the products you want to include. <br>Length limit: 100 characters. |
```

#### `products` object array parameters for entertainment catalogs 
> <span style="color:DodgerBlue">**Note**</span><br>
> The entertainment catalog is currently an allowlist-only feature and is invitation-only because the catalog type is under Alpha Testing. If you would like to access it, please contact your TikTok representative. However, acceptance into the Alpha Test is not guaranteed.

```xtable
| Field {30%}| Data Type {15%}| Description {55%}|
|---|---|---|
|products {Required} |object[] |List of products that you want to upload. <br><br>Max size: 5,000. |
#| media_title_id {Required} | string | A unique ID for the media title item. <br><br>A media title item is a media product that is available for distribution or consumption, such as a movie, TV show, music, or sports game. <br><br>Length limit: 100 characters. <br><br>Example: `TIKTOKLIVE001`. |
#| title {Required} | string | The name of the media title item. <br><br>Use only valid Unicode characters, and avoid invalid characters like control, function characters or emoji. <br><br>Length limit: 150 characters. <br><br>Example: `TikTok LIVE Series`. |
#| description | string | A short description of the media title item. <br><br>Length limit: 10,000 characters. |
#| image_url {Required} | string |The URL for the image of the media title item. <br><br>The image must be 500 x 500 pixels or larger in size. Otherwise, the image will be filtered out, and the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770). <br><br>All images should be in JPG or PNG format. <br><br>Example: `https://www.tiktok.com/entertainment_image_001.jpg`. |
#| video_url | string | The URL for the video used in your ad. <br><br>Recommended video aspect ratio 9: 16 (Vertical). <br>Recommended resolution for TikTok placement: ≥720 x 1280 pixels. <br>Recommended bitrate for TikTok placement: ≥516 kbps. <br>Sound: enabled with subtitles. <br><br>Example: `https://www.tiktok.com/tiktok_live_series`. |
#| additional_image_urls | string[] | Additional image URLs for the media title item.<br><br>Max size: 20. <br><br>The image must be 500 x 500 pixels or larger is size. Otherwise, the image will be filtered out, and the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770). <br><br>All images should be in JPG or PNG format. <br><br>Example: `["https://www.tiktok.com/entertainment_image_002.jpg","https://www.tiktok.com/entertainment_image_003.jpg"]`. <br><br><p><span style="color:darkred"><b>Note</b></span>: If your additional image URL contains commas (,), URL-encode them by replacing with %2C before adding them to this field.<br>For example, for URLs such as `https://img.example.com/product.jpg?width=800&height=600&quality=85&format=webp`and `https://img.example.com/product_800x600<b>,</b>q85<b>,</b>webp.jpg`, you need to set this field to `["https://img.example.com/product.jpg?width=800&height=600&quality=85&format=webp","https://img.example.com/product_800x600<b>%2C</b>q85<b>%2C</b>webp.jpg"]`.|
#| genres | string[] | The genres of the media title item. <br><br>To find out the allowed values for this field, refer to the "<a href="#item-link-List of values for genres "> List of values for `genres` </a> " section below.<br><br> Max size: 2.<br><br>Example: `TEEN`. |
#| qid | string | QID (or Q number), the unique identifier of the media title item on Wikidata, which consists of the letter "Q" followed by one or more digits. <br><br>Length limit: 150 characters. <br><br>Example: `Q48938223`. |
#| price_info  | object | Price information. |
##| price | float | The price for the media title item.  <br><br>Example: 30. |
##| currency | string | Unit of currency. <br><br> If specified, this field must follow the catalog's target country. <br><br> If this field is not specified, the unit of currency for the catalog's target country will be used by default. <br><br>Example: `USD`. |
#| landing_page| object | Landing page information. |
##| landing_page_url | string | URL of the landing page where you can view and purchase the media title item. <br><br> Example: `https://www.tiktok.com/tiktok_entertainment`. |
##| ios_url | string | Custom scheme for iOS apps as URL. For iOS, provide both iPhone & iPad app information if they are different from the regular iOS app. <br><br>Example: iOS://electronic |
##| ios_app_store_id| string | App Store ID for the iOS app. <br><br> Example: 1234. |
##| ios_app_name | string | The iOS App name to display. <br><br>Example: Electronic iOS. |
##| android_url | string | Custom scheme for Android apps as URL. <br><br> Example: android://electronic |
##| android_package| string | The Android package name. <br><br> Example: com.electronic. |
##| android_app_name | string | The Android App name to display. <br><br>Example: Electronic Android. |
#| extra_info| object | Additional information. |
##| custom_label_0 | string | Additional information about the products you want to include. <br><br>Length limit: 100 characters. |
##| custom_label_1 | string | Additional information about the products you want to include. <br><br>Length limit: 100 characters. |
##| custom_label_2 | string | Additional information about the products you want to include. <br><br>Length limit: 100 characters. |
##| custom_label_3 | string | Additional information about the products you want to include. <br><br>Length limit: 100 characters. |
##| custom_label_4 | string | Additional information about the products you want to include. <br><br>Length limit: 100 characters. |
```
##### List of values for `genres`

```xtable
| Value {30%}| Description {60%}|
|---|---|
| `ACTION` | action |
| `ADULT_ANIMATION` | adult animation |
| `ADVENTURE` | adventure |
| `ANIMATION` | animation |
| `BIOGRAPHICAL` | biographical |
| `BLACK_COMEDY` | black comedy |
| `BUDDY` | buddy |
| `COMEDY` | comedy |
| `COMIC` | comic |
| `COOKING_SHOW` | cooking show |
| `CRIME` | crime |
| `DISASTER` | disaster |
| `DOCUMENTARY` | documentary |
| `DRAMA` | drama |
| `FAMILY` | family |
| `FANTASY` | fantasy |
| `GAME_SHOW` | game show |
| `HISTORY` | history |
| `HORROR` | horror |
| `LGBT` | LGBT |
| `MARTIAL_ARTS` | martial arts |
| `MILITARY` | military |
| `MUSIC` | music |
| `POLICE` | police |
| `POLICE_PROCEDURAL` | police procedural |
| `POLITIC` | politic |
| `REALITY_TV` | reality TV |
| `ROAD` | road |
| `ROMANCE` | romance |
| `SATIRE` | satire |
| `SCIENCE_FICTION` | science fiction |
| `SITCOM` | sitcom |
| `SOAP_OPERA` | soap opera |
| `SPORT` | sport |
| `SPY` | spy |
| `STEAMPUNK` | steampunk |
| `SUPERHERO` | superhero |
| `TALK_SHOW` | talk show |
| `TEEN` | teen |
| `THRILLER` | thriller |
| `TIME_TRAVEL` | time travel |
| `VAMPIRE` | vampire |
| `WAR` | war |
| `WESTERN` | western |
| `ZOMBIE` | zombie |
```

#### `products` object array parameters for Auto-Inventory catalogs 
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|products {Required}|object[]|List of vehicles that you want to upload.<br><br>Max size: 5,000.|
#|vehicle_id {Required}|string|A unique ID for the vehicle.<br><br>Length limit: 100 characters.<br><br>Example: `TIKTOKMOTORSSEDAN001`.|
#|title {Required}|string|The name of the vehicle.<br><br>Use only valid Unicode characters, and avoid invalid characters like control, function characters or emoji.<br><br>Length limit: 500 characters.<br><br>Example: `TikTok Motors Sedan`.|
#|description {Required}|string|A short description of the vehicle.<br><br>Length limit: 5,000 characters.<br><br>Example: `Experience the ultimate luxury and performance with the TikTok Motors Sedan.`|
#|availability|string|The current availability of the vehicle.<br><br>Enum values:<ul><li>`AVAILABLE`: available for order.</li><li>`NOT_AVAILABLE`: Not available for order.</li></ul>Example: `AVAILABLE`.|
#|image_url {Required}|string|The URL for the image of the vehicle, in the format of `http://xxx` or `https://xxx`.<br><br>The image must be 500 x 500 pixels or larger in size. Images smaller than this size will be filtered out, and the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770).<br><br>All images should be in JPG or PNG format.<br><br>Example: `https://www.tiktok.com/auto_vehicle_image_001.jpg`.|
#|additional_image_urls|string[]|Additional image URLs for the vehicle, in the format of `http://xxx` or `https://xxx`.<br><br>Max size: 20.<br><br>The image must be 500 x 500 pixels or larger in size. Images smaller than this size will be filtered out, and the product will not be approved. For details, see [Product Image Requirements](https://ads.tiktok.com/marketing_api/docs?id=1739578479392770).<br><br>All images should be in JPG or PNG format.<br><br>Example: `["https://www.tiktok.com/auto_vehicle_image_002.jpg","https://www.tiktok.com/auto_vehicle_image_003.jpg"]`.<br><br><p><span style="color:darkred"><b>Note</b></span>: If your additional image URL contains commas (,), URL-encode them by replacing with %2C before adding them to this field.<br>For example, for URLs such as `https://img.example.com/product.jpg?width=800&height=600&quality=85&format=webp`and `https://img.example.com/product_800x600<b>,</b>q85<b>,</b>webp.jpg`, you need to set this field to `["https://img.example.com/product.jpg?width=800&height=600&quality=85&format=webp","https://img.example.com/product_800x600<b>%2C</b>q85<b>%2C</b>webp.jpg"]`.|
#| video_url | string | The URL for the video used in your ad.<br><br>Recommended video aspect ratio 9: 16 (Vertical).<br>Recommended resolution for TikTok placement: ≥720 * 1280.<br>Recommended bitrate for TikTok placement: ≥516 kbps.<br>Sound: enabled with subtitles.<br>Supported formats: .mp4, .mov, .avi.<br>Max file size: 100 MB.<br>Recommended duration: 15-60 seconds.<br><br>Example: `https://www.tiktok.com/auto_inventory.mp4`. |
#|product_detail|object|Additional information about the vehicle.|
##|condition|string|The current condition of the vehicle.<br><br>Enum values:<ul><li>`EXCELLENT`: excellent.</li><li>`GOOD`: good.</li><li>`FAIR`: fair.</li><li>`POOR`: poor.</li><li>`OTHER`: other.</li></ul>Example: `GOOD`.|
##|offer_type | string | The type of offer.<br><br>Length limit: 100 characters.<br><br>Recommended values: <ul><li>`CASH`: A cash offer where the buyer pays the full price upfront and gains immediate ownership of the vehicle.</li><ul><li>You can specify the price of the cash offer using `amount_price`.</li><ul><li>For example, if the cash offer is 36,838 USD, you can set `amount_price` to `36838`.</li></ul></ul><li>`LEASE`: A lease offer where the buyer pays monthly to use the vehicle for a specified term. The buyer does not own the vehicle at the end of the lease automatically unless they choose to buy it out.</li><ul><li>You can specify the term, monthly pay, and downpayment for a lease offer using `term_length`, `offer_term_qualifier`, `amount_price`, `amount_qualifier`, `downpayment`, and `downpayment_qualifier` .</li><ul><li>For example, if the lease offer is 486 USD per month for three years, with 2,986 USD due at signing, you can set `term_length` to `3`, `offer_term_qualifier` to `years`, `amount_price` to `486`, `amount_qualifier` to `month`, `downpayment` to `2986`, and `downpayment_qualifier` to `due at signing`.</li></ul></ul><li>`FINANCE`: A finance offer where the buyer borrows money to purchase the vehicle, repaying it in installments over a fixed term. The buyer owns the vehicle after completing all loan payments.</li><ul><li>You can specify the term, price, and APR (Annual Percentage Rate charged for borrowing) for the finance offer using `term_length`, `offer_term_qualifier`, `amount_price`, `amount_percentage`, and `amount_qualifier` .</li><ul><li>For example, if the finance offer totals 37,170 USD, payable over 60 months with an APR of 5.25%, you can set `term_length` to `60`, `offer_term_qualifier` to `months`, `amount_price` to `37170`, `amount_percentage` to `5.25%`, and `amount_qualifier` to `APR`.</li></ul></ul></ul> |
##|term_length | string | The duration for which the offer applies.<br><br>The combination of `term_length` and `offer_term_qualifier` together defines the total applicable period of the offer.<br><br>Length limit: 100 characters.<br><br>Example: `3`. |
##|offer_term_qualifier | string | The unit for term of the offer.<br><br>Recommended values: `months`, `years`.<br><br>Length limit: 100 characters. |
##|amount_price | string | The amount of the offer.<br><br><ul><li>For cash and lease offers, you can specify the monthly payment using `amount_price` and set `amount_qualifier` to `month`.</li><li>For finance offers, you can specify the total price using `amount_price` the APR using `amount_percentage` . Set `amount_qualifier` to `APR`.</li></ul><br>Example: `100`.<br><br>The currency of the `amount_price` will be the `currency` of the catalog. To obtain the currency of a catalog, use [/catalog/get/](https://business-api.tiktok.com/portal/docs?id=1740315452868610). |
##|amount_percentage | string | The percentage for the APR in a finance offer.<br><br>Example: `1.1%`.<br><br>Length limit: 100 characters. |
##|amount_qualifier | string | The qualifier for the `amount_price` or `amount_percentage`.<br><br>Example: `month` (per month), `APR`  (Annual Percentage Rate).<br><br>Length limit: 100 characters.<br><br><ul><li>For cash and lease offers, you can set this field to `month` and specify the monthly pay using `amount_price`.</li><li>For finance offers,  you can set this field to `APR` and specify the APR percentage using `amount_percentage`. Specify the total price using `amount_price` .</li></ul> |
##|downpayment | string | The downpayment at the time of purchase or lease. <br><br>Example: `3000`.<br><br>The currency for the `downpayment` will be the `currency` of the catalog. To obtain the currency of a catalog, use [/catalog/get/](https://business-api.tiktok.com/portal/docs?id=1740315452868610). |
##|downpayment_qualifier | string | Qualifier for the downpayment.<br><br>Example: `due at signing`.<br><br>Length limit: 100 characters. |
##|offer_disclaimer | string | The full text of the offer disclaimer to appear in the in-app pop-up.<br><br>Length limit: 5,000 characters. |
##|offer_disclaimer_url | string | A URL that directs users to additional information related to the offer disclaimer, in the format of `http://xxx` or `https://xxx`. <br><br>Example: `https://www.tiktok.com/tiktok_auto_inventory_disclaimer`. |
##|emission_disclaimer | string | The text of the emission disclaimer to appear within the in-app pop-up.<br><br>Length limit: 5,000 characters. |
##|emission_disclaimer_url | string | A URL that directs users to additional information related to the emission disclaimer, in the format of `http://xxx` or `https://xxx`.     <br><br>Example: `https://www.tiktok.com/tiktok_auto_inventory_emission_disclaimer`. |
##|emission_overlay_disclaimer | string | The short text of the disclaimer to appear on-screen in the disclaimer banner.  <br><br>Length limit: 500 characters. |
##|emission_image_link | string | The link to an image showing the emission level, in the format of `http://xxx` or `https://xxx`. <br><br>The image must be 450 x 450 pixels or larger in size. <br><br>File size limit: within 4 MB.<br><br>Example: `https://www.tiktok.com/tiktok_auto_inventory_emission_image.jpg` |
#|state_of_vehicle {Required}|string|The state of the vehicle.<br><br>Enum values:<ul><li>`NEW`: new.</li><li>`USED`: used.</li><li>`CPO`: CPO (certified pre-owned). CPO vehicles are used vehicles that have undergone thorough inspection and are typically newer, have lower mileage, and come with additional warranty coverage compared to regular used cars.</li></ul>Example: `USED`.|
#|vehicle_type|string|The type of the vehicle.<br><br>Enum values:<ul><li>`BOAT`: boat.</li><li>`CAR_TRUCK`: car or truck.</li><li>`COMMERCIAL`: A vehicle used for commercial purposes, such as a delivery truck.</li><li>`MOTORCYCLE`: motorcycle.</li><li>`POWERSPORT`: powersport. Recreational vehicles that are designed for outdoor activities and adventure, such as a snowmobile.</li><li>`RV_CAMPER`: recreational vehicle or camper designed for travel and camping purposes.</li><li>`TRAILER`: trailer.</li><li>`OTHER`: Any other vehicle type not covered above.</li></ul>|
#|make {Required}|string|The make (brand) of the vehicle.<br><br>Length limit: 100 characters.<br><br>Example: `TikTok Motors`.|
#|model {Required}|string|The model of the vehicle.<br><br>Length limit: 100 characters.<br><br>Example: `X`.|
#|trim|string|The trim of the vehicle. The trim represents the distinctive specification package of the vehicle, tailored specifically to that model.<br><br>Length limit: 50 characters.<br><br>Example: `Long Range`.|
#|year {Required}|integer|The year when the vehicle was launched, in the format of `YYYY`.<br><br>Value range: 1801-2999.<br><br>Example: `2023`.|
#|vin|string|The VIN (Vehicle Identification Number) of the vehicle.<br><br>According to the ISO 3779 standard, the VIN for a vehicle manufactured since 1981 must be a 17-digit alphanumeric code.<br><br>Length limit: 17 characters.<br><br>Example: `1TIKT0K5TAR123456`.|
#|mileage {Required}|object|The mileage of the vehicle.|
##|value {Required}|integer|Mileage value.<br><br>Value range: ≥0.|
##|unit {Required}|string|Mileage unit.<br><br>Enum values:<ul><li>`MILE`: mile.</li><li>`KILOMETER`: kilometer.</li></ul>|
#|body_style {Required}|string|The body style of the vehicle.<br><br>Enum values:<ul><li>`CONVERTIBLE`: convertible.</li><li>`COUPE`: coupe.</li><li>`CROSSOVER`: crossover.</li><li>`HATCHBACK`: hatchback.</li><li>`MINIVAN`: minivan.</li><li>`SMALL_CAR`: small car.</li><li>`SEDAN`: sedan.</li><li>`SUV`: SUV.</li><li>`TRUCK`: truck.</li><li>`VAN`: van.</li><li>`WAGON`: wagon.</li><li>`OTHER`: other.</li></ul>|
#|exterior_color {Required}|string|The exterior color of the vehicle.<br><br>Length limit: 100 characters.|
#|interior_color|string|The interior color of the vehicle.<br><br>Length limit: 100 characters.|
#|transmission|string|The transmission type of the vehicle.<br><br>Enum values:<ul><li>`AUTOMATIC`: automatic.</li><li>`MANUAL`: manual.</li></ul>|
#|drivetrain|string|The drivetrain type of the vehicle.<br><br>Enum values:<ul><li>`4X2`: four-wheel drive vehicle with two wheel drive (2WD). The drivetrain delivers power to either the front wheels or the rear wheels.</li><li>`4X4`: four-wheel drive vehicle with four wheel drive. The drivetrain delivers power to all four wheels.</li><li>`AWD`: all-wheel drive. The drivetrain delivers power to all wheels continuously.</li><li>`FWD`: front-wheel drive. The drivetrain delivers power to the front wheels only.</li><li>`RWD`: rear-wheel drive. The drivetrain delivers power to the rear wheels only.</li><li>`OTHER`: any other drivetrain type not covered above, such as electric vehicles with unique drivetrain styles.</li></ul>|
#|fuel_type|string|The fuel type of the vehicle.<br><br>Enum values:<ul><li>`DIESEL`: diesel.</li><li>`ELECTRIC`: electric.</li><li>`FLEX`: flex fuel, a blend of gasoline and ethanol.</li><li>`GASOLINE`: gasoline.</li><li>`HYBRID`: hybrid.</li><li>`OTHER`: other.</li></ul>|
#|address {Required}|object|Address information about the dealership.<br><br>The catalog's targeting country will be used as the default country for the dealership.|
##|address {Required}|string|The address of the dealership.<br><br>Length limit: 150 characters.|
##|city {Required}|string|The city where the dealership is located.<br><br>Length limit: 150 characters.|
##|region {Required}|string|The region (state or province) where the dealership is located.<br><br>Length limit: 150 characters.|
##|postal_code {Required}|string|The postal code for the dealership's address.<br><br>Length limit: 150 characters.|
#|latitude {Required}|float|The latitude of the dealership's address.<br><br>Value range: [-90, 90].<br><br>Example: 37.484100|
#|longitude {Required}|float|The longitude of the dealership's address.<br><br>Value range: [-180, 180].<br><br>Example: -122.148252|
#|dealer|object|Detailed information about the dealership.|
##|dealer_id|string|A unique ID for the dealership.<br><br>Length limit: 100 characters.|
##|dealer_name|string|The name of the dealership.<br><br>Length limit: 100 characters.|
##|dealer_phone|string|The phone number of the dealership.<br><br>Ensure that the number includes the country calling code.<br><br>Length limit: 15 characters.|
##|stock_number|string|The stock number assigned to the vehicle by the dealership. The stock number is usually assigned when the vehicle physically arrives at the dealership's lot.<br><br>Length limit: 100 characters.<br><br>Example: `T12345`.|
#|date_first_on_lot|string|The date when the vehicle physically arrives at the dealership's lot, in the format of `YYYY-MM-DD`.<br><br>Example: `2023-12-01`.|
#|days_on_lot|integer|The number of days the vehicle has been on the dealership's lot.<br><br>You can calculate this value by taking the difference between the `date_first_on_lot` and the current date. Note that this field is not automatically incremented daily. If you specify this field, we recommend updating it daily to ensure accurate information.<br><br>Default value: 0.<br><br>Example: 13.|
#|price_info {Required}|object|Price information.|
##|price {Required}|float|The price of the vehicle.<br><br>Example: 9.9.|
##|currency|string|Unit of currency.<br><br>If specified, this field must follow the catalog's target country.<br><br>If this field is not specified, the unit of currency for the catalog's target country will be used by default.<br><br>Example: `USD`.|
##|sale_price|float|The discounted price of the vehicle if it's on sale.<br><br>The value of this field should be less than the value of price.<br><br>Example: 9.9.|
##|sale_price_effective_date|string|The start and end date and time of sale, in the format of `YYYY-MM-DDT23:59+00:00/YYYY-MM-DDT23:59+00:00`.<br><br>Use a start date before the end date.<br>Do not pass this field if the sale price of a product is available endlessly.<br><br>Example: `2023-12-11T23:59+00:00/2023-12-15T23:59+00:00`.|
#|landing_page {Required}|object|Landing page information.|
##|landing_page_url {Required}|string|URL of the landing page where you can view and purchase the vehicle.<br><br>Example: `https://www.tiktok.com/tiktok_auto_inventory`.|
##|ios_url|string|Custom scheme for iOS apps as URL.<br><br>Example: `iOS://electronic`|
##|android_url|string|Custom scheme for Android apps as URL.<br><br>Example: `android://electronic`|
#|extra_info|object|Additional information.|
##|custom_label_0|string|Additional information about the products you want to include.<br><br>Length limit: 100 characters.|
##|custom_label_1|string|Additional information about the products you want to include.<br><br>Length limit: 100 characters.|
##|custom_label_2|string|Additional information about the products you want to include.<br><br>Length limit: 100 characters.|
##|custom_label_3|string|Additional information about the products you want to include.<br><br>Length limit: 100 characters.|
##|custom_label_4|string|Additional information about the products you want to include.<br><br>Length limit: 100 characters.|
```
#### `products` object array parameters for Auto-Model catalogs

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|products {Required}|object[]|List of vehicles that you want to upload.<br><br>Max size: 5,000.|
#|vehicle_id {Required}|string|A unique ID for the vehicle.<br><br>Length limit: 100 characters.<br><br>Example: `TIKTOKMOTORSX001`.|
#|title {Required}|string|The name of the vehicle.<br><br>Include details about the make, model, trim, and year, if any.<br><br>Use only valid Unicode characters, and avoid invalid characters like control, function characters or emoji.<br><br>Recommended length: within 40 characters.<br>Length limit: 128 characters.<br><br>Example: `2025 TikTok Motors X Long Range`.|
#|image_url {Required}|string|The URL for the image of the vehicle, in the format of `http://xxx` or `https://xxx`.<br><br>The image must be 450 x 450 pixels or larger in size. Images smaller than this resolution will be filtered out, and the product will not be approved.<br>All images should be in JPG, PNG or JPEG format.<br>File size limit: within 4 MB.<br><br>Example: `https://www.tiktok.com/auto_model_image_001.jpg`.|
#| video_url | string | The URL for the video used in your ad.<br><br>Recommended video aspect ratio 9: 16 (Vertical).<br>Recommended resolution for TikTok placement: ≥ 720 * 1280.<br>Recommended bitrate for TikTok placement: ≥ 516 kbps.<br>Sound: enabled with subtitles.<br>Supported formats: .mp4, .mov, .avi.<br>Max file size: 100 MB.<br>Recommended duration: 15-60 seconds.<br><br>Example: `https://www.tiktok.com/auto_model.mp4`. |
#|make {Required}|string|The make (brand) of the vehicle.<br><br>Length limit: 100 characters.<br><br>Example: `TikTok Motors`.|
#|model {Required}|string|The model of the vehicle.<br><br>Length limit: 100 characters.<br><br>Example: `X`.|
#|trim|string|The trim of the vehicle. The trim represents the distinctive specification package of the vehicle, tailored specifically to that model.<br><br>Length limit: 50 characters.<br><br>Example: `Long Range`.|
#|year {Required}|integer|The year when the vehicle was launched, in the format of `YYYY`.<br><br>Example: 2025.|
#|exterior_color {Required}|string|The color of the vehicle.<br><br>Length limit: 100 characters.<br><br>Example: `Black`.|
#|product_detail|object|Additional information about the vehicle.|
##|offer_type|string|The type of offer.<br><br>Length limit: 100 characters.<br><br>Recommended values:<ul><li>`CASH`: A cash offer where the buyer pays the full price upfront and gains immediate ownership of the vehicle.<ul><li>You can specify the price of the cash offer using `amount_price`.<br>For example, if the cash offer is 36,838 USD, you can set `amount_price` to `36838`.</li></ul></li><li>`LEASE`: A lease offer where the buyer pays monthly to use the vehicle for a specified term. The buyer does not own the vehicle at the end of the lease automatically unless they choose to buy it out.<ul><li>You can specify the term, monthly pay, and downpayment for a lease offer using `term_length`, `offer_term_qualifier`, `amount_price`, `amount_qualifier`, `downpayment`, and `downpayment_qualifier`.<br>For example, if the lease offer is 486 USD per month for three years, with 2,986 USD due at signing, you can set `term_length` to `3`, `offer_term_qualifier` to `years`, `amount_price` to `486`, `amount_qualifier` to `month`, `downpayment` to `2986`, and `downpayment_qualifier` to `due at signing`.</li></ul></li><li>`FINANCE`: A finance offer where the buyer borrows money to purchase the vehicle, repaying it in installments over a fixed term. The buyer owns the vehicle after completing all loan payments.<ul><li>You can specify the term, price, and APR (Annual Percentage Rate charged for borrowing) for the finance offer using `term_length`, `offer_term_qualifier`, `amount_price`, `amount_percentage`, and `amount_qualifier`.<br>For example, if the finance offer totals 37,170 USD, payable over 60 months with an APR of 5.25%, you can set `term_length` to `60`, `offer_term_qualifier` to `months`, `amount_price` to `37170`, `amount_percentage` to `5.25%`, and `amount_qualifier` to `APR`.</li></ul></li></ul>|
##|term_length|string|The duration for which the offer applies.<br><br>The combination of `term_length` and `offer_term_qualifier` together defines the total applicable period of the offer.<br><br>Length limit: 100 characters.<br><br>Example: `3`.|
##|offer_term_qualifier|string|The unit for term of the offer.<br><br>Recommended values: `months`, `years`.<br><br>Length limit: 100 characters.|
##|amount_price|string|The amount of the offer.<br><br>Example: `100`.<br><br>The currency for the `amount_price` will be the currency of the catalog. To obtain the currency of a catalog, use [/catalog/get/](https://business-api.tiktok.com/portal/docs?id=1740315452868610).|
##|amount_percentage|string|The percentage for the APR in a finance offer.<br><br>Example: `1.1%`.<br><br>Length limit: 100 characters.|
##|amount_qualifier|string|The qualifier for the `amount_price` or `amount_percentage`.<br><br>Example: `month` (per month), `APR` (Annual Percentage Rate).<br><br>Length limit: 100 characters.<ul><li>For cash and lease offers, you can set this field to `month` and specify the monthly pay using `amount_price`.</li><li>For finance offers, you can set this field to `APR` and specify the APR percentage using `amount_percentage`. Specify the total price using `amount_price`.</li></ul>|
##|downpayment|string|The downpayment at the time of purchase or lease.<br><br>Example: `3000`.<br><br>The `currency` for the downpayment will be the `currency` of the catalog. To obtain the currency of a catalog, use [/catalog/get/](https://business-api.tiktok.com/portal/docs?id=1740315452868610).|
##|downpayment_qualifier|string|Qualifier for the downpayment.<br><br>Example: `due at signing`.<br><br>Length limit: 100 characters.|
##|offer_disclaimer|string|The full text of the offer disclaimer to appear in the in-app pop-up.<br><br>Length limit: 5,000 characters.|
##|offer_disclaimer_url|string|A URL that directs users to additional information related to the offer disclaimer, in the format of `http://xxx` or `https://xxx`.<br><br>Example: `https://www.tiktok.com/tiktok_auto_model_disclaimer`.|
##|emission_disclaimer|string|The text of the emission disclaimer to appear within the in-app pop-up.<br><br>Length limit: 5,000 characters.|
##|emission_disclaimer_url|string|A URL that directs users to additional information related to the emission disclaimer, in the format of `http://xxx` or `https://xxx`.<br><br>Example: `https://www.tiktok.com/tiktok_auto_model_emission_disclaimer`.|
##|emission_overlay_disclaimer|string|The short text of the disclaimer to appear on-screen in the disclaimer banner.<br><br>Length limit: 500 characters.|
##|emission_image_link|string|The link to an image showing the emission level, in the format of `http://xxx` or `https://xxx`.<br><br>The image must be 500 x 500 pixels or larger in size.<br><br>File size limit: within 4 MB.<br><br>Example: `https://www.tiktok.com/tiktok_auto_model_emission_image.jpg`.|
#|landing_page {Required}|string|Landing page information.|
##|landing_page_url {Required}|string|URL of the landing page where you can view the vehicle listing.<br><br>Example: `https://www.tiktok.com/tiktok_auto_model`.|
```

#### `products` object array parameters for mini series catalogs

> <span style="color:DodgerBlue">**Note**</span><br>
> The mini series catalog is currently an allowlist-only feature and is invitation-only because the catalog type is under testing. If you would like to access it, please contact your TikTok representative. However, acceptance into the test is not guaranteed. 
 
```xtable
| Field {30%}| Data Type {15%}| Description {55%}|
|---|---|---|
|products {Required} |object[] |List of short drama series that you want to upload. <br><br>Max size: 5,000. |
#|series_id {Required}|string|A unique self-defined ID for the short drama series.<br><br>Length limit: 100 characters.|
#|series_name {Required}|string|The name of the short drama series.<br><br>Length limit: 100 characters.|
#|target_config|object|Targeting details for the short drama series.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>To upload short drama series that target multiple regions to a short drama catalog, call [/catalog/product/upload/](https://business-api.tiktok.com/portal/docs?id=1740497429681153) multiple times with the same `series_id` and different `target_config` settings. After the uploads, access the consolidated targeting settings for the same short drama series through `target_config` and `additional_product_list` in [/catalog/product/get/](https://business-api.tiktok.com/portal/docs?id=1740564136678402).<ul><li>For example, separate uploads with `target_config` set to `&#123;"region_code": "US"&#125;` (same as the primary targeting region for the short drama catalog) and `&#123;"region_code": "VN"&#125;` (same as one of the additional targeting regions) results in `list.target_config.region_code` as `US` and `list.additional_product_list.region_code` as `VN` in `/catalog/product/get/`.</li></ul></li></ul></p>|
##|region_code|string|The code of the targeting region for the short drama series.<br><br>The region should be a subset of the targeting regions of the mini series catalog, either primary or additional.<br><br>To retrieve the targeting regions of a mini series catalog, use [/catalog/get/](https://business-api.tiktok.com/portal/docs?id=1740315452868610). The `region_code` within the object `catalog_conf` represents the primary targeting region, and `additional_config_list` includes the additional targeting regions.<br><br>Example: `US`.|
#|image_url {Required}|string|The URL for the short drama series poster image.<br><br>The image must be 500x500 pixels or larger in size. Images smaller than this size will be filtered out, and the product will not be approved. For details, see [Product Image Requirements](https://business-api.tiktok.com/portal/docs?id=1739578479392770).<br>All images should be in JPG or PNG format.<br><br>Example: `https://www.tiktok.com/short_drama_series_image_001.jpg`.|
#| video_url | string | The URL for the video used in your ad.<br><br>Recommended video aspect ratio 9: 16 (Vertical).<br>Recommended resolution for TikTok placement: ≥ 720 * 1280.<br>Recommended bitrate for TikTok placement: ≥ 516 kbps.<br>Sound: enabled with subtitles.<br>Supported formats: .mp4, .mov, .avi.<br>Max file size: 100 MB.<br>Recommended duration: 15-60 seconds.<br><br>Example: `https://www.tiktok.com/short_drama_series.mp4`. |
#|recharge {Required}|object[]|Information about the recharge for the short drama series.|
##|type {Required}|string|Recharge type for the short drama series.<br><br>Enum values:<ul><li>`BY_TIERS`: Recharge by tiers.</li><li>`SUBSCRIPTION`: Recharge by subscription fee for a time period.</li><li>`BY_EPISODES`: Recharge by episodes.</li></ul>|
##|purchase_unit {Required}|string[]|Units for the recharge.<br><br>When `recharge_type` is `BY_TIERS`, the enum values are:<ul><li>`TIER_1`: tier 1.</li><li>`TIER_2`: tier 2.</li><li>`TIER_3`: tier 3.</li><li>`TIER_4`: tier 4.</li><li>`TIER_5`: tier 5.</li></ul>When `recharge_type` is `SUBSCRIPTION`, the enum values are:<ul><li>`WEEKLY`: weekly.</li><li>`MONTHLY`: monthly.</li><li>`QUARTERLY`: quarterly.</li><li>`YEARLY`: yearly.</li></ul>When `recharge_type` is `BY_EPISODES`, specify one or more numeric strings that represent numbers no more than the total episode count.|
##|cost {Required}|float[]|The prices for the tiers, subscription periods, or episodes.|
#|product_detail {Required}|string|Additional information about the short drama series.|
##|company_type|string|The company type for the short drama series.<br><br>Enum values:<ul><li>`COPYRIGHT_HOLDER`: copyright holder.</li><li>`DISTRIBUTOR`: distributor.</li></ul>|
##|copyright_holder_name {+Conditional}|string|Required when `company_type` is specified.<br><br>The name of the copyright holder company.<br><br>Length limit: 100 characters.|
##|app_id{+Conditional}|string|Either `app_id` or `minis_id` or both must be specified.<br><br>The App ID of the app to promote in your ads.<br><br>To obtain a list of App IDs within your ad account, use [/app/list/](https://business-api.tiktok.com/portal/docs?id=1740859313270786).|
##|minis_id{+Conditional}|string|Either `app_id` or `minis_id` or both must be specified.<br><br>Mini program ID.<br><br>Specify the identifier for your mini program on the TikTok Open Platform.|
##|total_episodes {Required}|number|The number of episodes in the short drama series.<br><br>Value range: 1-9,999.|
##|initial_paid_episodes {Required}|number|The episode number at which viewers are required to pay to continue watching the short drama series. For instance, if payment starts from the 11th episode, set this field to 11.<br><br>Value range: 1-9,999.<br><br>The value of this field should be less than the value of `total_episodes`.|
##|per_episode_duration|number|The average duration of each episode in minutes.<br><br>Value range: 0-15.|
##|spoken_language {Required}|string|The language that actors in the original short drama series speak.<br><br>Enum values:<ul><li>`ar`: Arabic.</li><li>`de`: German.</li><li>`cs`: Czech.</li><li>`en`: English.</li><li>`es`: Spanish.</li><li>`fr`: French.</li><li>`he`: Hebrew.</li><li>`hi`: Hindi.</li><li>`id`: Indonesian.</li><li>`it`: Italian.</li><li>`ja`: Japanese.</li><li>`ko`: Korean.</li><li>`ms`: Malay.</li><li>`pl`: Polish.</li><li>`pt`: Portuguese.</li><li>`ro`: Romanian.</li><li>`ru`: Russian.</li><li>`ta`: Tamil.</li><li>`th`: Thai.</li><li>`tr`: Turkish.</li><li>`vi`: Vietnamese.</li><li>`zh`: Simplified Chinese.</li><li>`zh-hant`: Traditional Chinese.</li></ul>|
##|subtitle_language {Required}|string|The language used for the subtitles in the short drama series.<br><br>Enum values:<ul><li>`ar`: Arabic.</li><li>`de`: German.</li><li>`cs`: Czech.</li><li>`en`: English.</li><li>`es`: Spanish.</li><li>`fr`: French.</li><li>`he`: Hebrew.</li><li>`hi`: Hindi.</li><li>`id`: Indonesian.</li><li>`it`: Italian.</li><li>`ja`: Japanese.</li><li>`ko`: Korean.</li><li>`ms`: Malay.</li><li>`pl`: Polish.</li><li>`pt`: Portuguese.</li><li>`ro`: Romanian.</li><li>`ru`: Russian.</li><li>`ta`: Tamil.</li><li>`th`: Thai.</li><li>`tr`: Turkish.</li><li>`vi`: Vietnamese.</li><li>`zh`: Simplified Chinese.</li><li>`zh-hant`: Traditional Chinese.</li></ul>|
##|production_type {Required}|string|Production type, whether the short drama series is locally produced or translated for export.<br><br>Enum values:<ul><li>`LOCAL`: Local drama. The short drama features local actors, and the language for actors, narration, and subtitles is the local official language.</li><li>`TRANSLATION`: Translated drama. The short drama is packaged for international distribution with added subtitles, narration, and dubbing in another language.</li></ul>|
##|target_audience {Required}|string|The target audience for the short drama series.<br><br>Enum values:<ul><li>`MALE`: Male. The short drama is primarily targeted at male audiences, often centered around a male protagonist or narrated from a male perspective. Themes typically include the accumulation of power, wealth, and prestige, the enhancement of abilities, and strategic planning.</li><li>`FEMALE`: Female. The short drama is primarily targeted at female audiences, often centered around a female protagonist or narrated from a female perspective. These dramas usually contain elements of romance and love.</li><li>`NEUTRAL`: Neutral. The short drama doesn't have a specific gender target audience.</li></ul>|
##|characters {Required}|string[]|The list of characters in the short drama series.<br><br>Max size: 3.<br><br>For enum values, see <a href="#item-link-List of values for characters">List of values for `characters`</a>.|
##|genres {Required}|string[]|The genres of the short drama series as indicated by the main storyline or plot.<br><br>Max size: 3.<br><br>For enum values, see <a href="#item-link-List of values for mini series genres">List of values for mini series `genres`</a>.|
##|historical_context|string[]|The historical background or setting of the short drama series.<br><br>Max size: 3.<br><br>For enum values, see <a href="#item-link-List of values for historical_context">List of values for `historical_context`</a>.|
##|actors|string[]|The names of the actors in the short drama series.<br><br>Max size: 20.<br><br>Length limit for each name: 100 characters.|
```
##### List of values for `characters`
```xtable
| Value {30%}| Type {15%}| Description {55%}|
|---|---|---|
| `ALPHA_MALE` | alpha male | A protagonist characterized as a powerful and wealthy leader at the pinnacle of business. Commonly seen in corporate settings, this character combines dominance with charm. Traits often include strong control, possessiveness, and the ability to manipulate situations effortlessly. |
| `ATHLETE` | athlete | Individuals trained and competing in sports, highlighting themes of discipline, competition, and physical prowess. |
| `BABIES` | babies | Elements involving the birth, upbringing, or daily life of infants and young children, often adding warmth or comedic relief. |
| `BADBOY` | badboy | Rebellious male characters with a rough exterior, often misunderstood or with a hidden softer side. |
| `CHEF` | chef | Characters specializing in cooking, often involved in culinary arts or restaurant settings. |
| `DOCTOR` | doctor | Medical professionals who diagnose and treat patients, often involved in high-stakes or emotional storylines. |
| `DRAGON_KING` | dragon king | A figure from Eastern fantasy, often portrayed as a powerful dragon deity or war god, commonly referenced in short drama dialogues. |
| `ELITE_PROFESSION` | elite profession | Characters with prestigious or respected jobs, such as police officers, lawyers, athletes, doctors, pilots, or military personnel, often depicted with a professional aura. |
| `EMPEROR` | emperor | The supreme ruler of an empire, often depicted with authority, dignity, and political power. |
| `EXTRAORDINARY_TALENT` | extraordinary talent | Characters possessing extraordinary abilities beyond normal human limits, such as telekinesis, telepathy, or other supernatural powers. |
| `GANG_MAFIA` | gang/mafia | Characters involved in organized crime, often portrayed with a tough, ruthless edge and complex loyalties. |
| `HEIRESS` | heiress | A young woman born into wealth, nobility, or a family business inheritance, often facing expectations and pressures related to her status. |
| `HEROINE` | heroine | A dominant female protagonist characterized by independence, ambition, resilience, and a strong sense of agency. |
| `HIDDEN_IDENTITY` | hidden identity | Characters who hide their true identity behind a disguise or alias, creating mystery or conflict. |
| `HOUSE_WIFE` | house wife | A woman primarily focused on managing the household and family life. |
| `IDOL` | idol | Public figures or entertainers known for their fame, often in music, acting, or other performance arts. |
| `KUNG_FU_MASTER` | kung fu master | Highly skilled fighters with mastery of traditional combat techniques, often embodying discipline and honor. |
| `LIVE_IN_SON_IN_LAW` | live-in son in law | A man who marries into and lives with the wife's family, often facing social stigma or familial challenges in traditional societies. |
| `MODERN_MOMS` | modern moms | Characters centered around motherhood and parenting roles. |
| `NOBODY` | nobody | A regular individual without special status or power, often serving as a relatable or contrasting figure to the elite characters. |
| `PRINCESS_CONSORT` | princess consort | A noblewoman married into royalty, often involved in palace politics and court drama. |
| `PSYCHO_SWEETHEART` | psycho sweetheart | Characters who appear sweet but have obsessive, sometimes violent tendencies, especially in romantic contexts. |
| `QUEEN` | queen | The female counterpart to the emperor, often embodying grace, influence, and sometimes political intrigue. |
| `SPIRIT_CULTIVATOR` | spirit cultivator | Characters in fantasy settings who practice spiritual cultivation to gain immortality or supernatural powers. |
| `SUBSTITUTE` | substitute | Characters who impersonate or replace others, often leading to mistaken identities or dramatic tension. |
| `TEENS` | teens | Young characters navigating the challenges of adolescence, school life, friendships, and first loves. |
| `VAMPIRE` | vampire | Fictional beings that feed on the blood of humans or other creatures, often depicted with immortality and supernatural powers. |
| `WARLORD` | warlord | A divine or legendary warrior figure symbolizing ultimate combat prowess and leadership in battle. |
| `WEREWOLF` | werewolf | Legendary creatures who appear human but transform into wolves during the full moon. Werewolf packs typically have an Alpha leader, mirroring wolf pack dynamics. |
| `OTHERS` | others | Any additional character types not covered in the preceding types, adaptable to specific story needs. |
```

##### List of values for mini series `genres`
```xtable
| Value {30%}| Type {15%}| Description {55%}|
|---|---|---|
| `ACTION` | action | Fast-paced stories featuring physical conflict, stunts, and dynamic sequences. |
| `AFFAIR` | affair | Romantic or sexual relationships occurring outside of marriage, often involving secrecy and conflict. |
| `AGE_GAP_ROMANCE` | age gap romance | Love stories featuring significant age differences between partners. |
| `BITTER_LOVE` | bitter love | Intense love stories filled with emotional pain, misunderstandings, and heartbreak. |
| `BUILD_FROM_SCRATCH` | build from scratch | Stories about first-generation wealth accumulation through hard work and determination. |
| `CHILDHOOD_SWEETHEARTS` | childhood sweethearts | Romantic stories about protagonists who have known each other since childhood and gradually realize their love. |
| `CHINESE_FANTASY` | Chinese fantasy | Chinese fantasy dramas based on ancient mythology, blending immortals, gods, demons, and magical martial arts. |
| `COME_UPPANCE_RETRIBUTION` | comeuppance retribution | The protagonist exposes and defeats antagonists or unfaithful partners, often with satisfying revenge. |
| `COMEBACK` | comeback | Powerful characters return after a period of absence or hardship to reclaim their place or seek justice. |
| `COMEDY` | comedy | Light-hearted, humorous stories designed to entertain and amuse. |
| `CONSPIRACY` | conspiracy | Plots involving deception, conspiracies, and deliberate setups to trap or harm others. |
| `CONTRACT_LOVE` | contract love | Relationships or marriages based on agreements or contracts rather than initial romantic feelings, often evolving into true love or complicated emotional entanglements. |
| `COUGAR_CUB_DYNAMIC` | cougar-cub dynamic | Romance where the female partner is older than the male partner. |
| `CRIME` | crime | Stories revolving around criminal activities, investigations, and justice. |
| `DISASTER` | disaster | Dramas depicting natural or man-made catastrophes and their impact on people. |
| `DISGUISE` | disguise | Characters conceal their true identity to achieve goals, avoid detection, or execute plans. |
| `DRAGON_TIGER_PACT` | dragon-tiger pact | Two or more powerful characters join forces, often blending their strengths for a common goal. |
| `DRAMA` | drama | Emotionally intense stories focusing on character development and interpersonal conflicts. |
| `ENEMIES_TO_LOVERS` | enemies-to-lovers | Characters start as adversaries or rivals but gradually develop romantic feelings. |
| `FAMILY_BOUNDS` | family bonds | Stories focusing on family relationships, conflicts, and emotional bonds. |
| `FAMILY_ETHICS` | family ethics | Dramas that explore moral conflicts, societal taboos, and complicated interpersonal ethics. |
| `FANTASY_ROMANCE` | fantasy romance | Love stories set in fantastical worlds with magical or supernatural elements. |
| `FEMALE_GROWTH` | female growth | Narratives focusing on the personal development, empowerment, and self-discovery of female protagonists. |
| `FIGHT_BULLYING` | fight bullying | Narratives involving characters who are bullied and their journey to fight back or seek justice. |
| `FLASH_MARRIED` | flash married | Rapid, impulsive marriages often followed by the development of genuine feelings. |
| `GANG_FIGHTS` | gang fights | Stories revolving around conflicts, rivalries, and battles between gangs or factions. |
| `HARD_WIN_LOVE` | hard-win love | A narrative where one partner actively chases after the other to win or win back their love. |
| `HORRORS_AND_THRILLERS` | horrors and thrillers | Suspenseful and frightening stories designed to evoke fear and tension. |
| `INDULGENT_LOVE` | indulgent love | Heartwarming and indulgent love stories featuring romantic dates, surprises, proposals, and pure, healing affection between lovers. |
| `INVINCIBLE` | invincible | Stories featuring protagonists who are unbeatable or unmatched in their skills or power. |
| `LIFE_TRANSFORMATION` | life transformation | Protagonists rise from humble beginnings to the top of the social or economic ladder, conquering wealth, status, or admiration. |
| `LOVE_AFTER_MARRIAGE` | love after marriage | Protagonists marry without love initially, then grow closer through shared experiences, overcoming obstacles, and eventually falling genuinely in love. |
| `LOVE_AT_FIRST_SIGHT` | love at first sight | Instant romantic attraction leading to love after the first encounter. |
| `LOVE_IN_WEALTHY_FAMILIES` | love in wealthy families | Stories centered around ultra-rich, influential families. The protagonists enter marriages with partners who have significantly higher social status and wealth. They often face challenges adapting to family expectations and dynamics. Sometimes, two wealthy families unite through marriage to strengthen their power and influence, sparking romantic drama. |
| `LOVE_ON_CAMPUS` | love on campus | Love stories set in a school or university environment. |
| `LOVE_TRIANGLE` | love triangle | Complex romantic entanglements involving multiple people, often featuring love triangles or more intricate webs of affection. |
| `ONE_NIGHT_STAND` | one night stand | Stories revolving around brief, often accidental, romantic or sexual encounters. |
| `PALACE_FIGHT` | palace fight | Set in ancient Chinese imperial courts, focusing on the power struggles, romantic entanglements, and political scheming among concubines and female officials. |
| `POWER_CALCULUS` | power calculus | Dramas centered on strategic maneuvering, manipulation, and battles for power. |
| `PREGNANCY_ESCAPE` | pregnancy escape | A plot where a woman becomes pregnant and leaves or runs away, often involving themes of responsibility and reconciliation. |
| `RECONCILIATION_AFTER_BREAKUP` | reconciliation after breakup | Couples who have separated or broken up reconcile and rebuild their relationship. |
| `REDEMPTION` | redemption | Stories about characters seeking forgiveness, atonement, or personal salvation. |
| `RETURN_FOR_REVENGE` | return for revenge | Protagonists seek vengeance after betrayal or injustice, often returning stronger to reclaim what’s theirs. |
| `REVERSE_HAREM` | reverse harem | A female protagonist is surrounded by multiple male admirers or love interests, often competing for her affection. |
| `SECRET_CRUSH_COME_TRUE` | secret crush come true | A storyline where a long-held secret admiration or unspoken love eventually blossoms into a real relationship. |
| `SPY` | spy | Dramas centered on espionage, undercover agents, intelligence exchanges, and covert operations, often involving violence and interrogation. |
| `SUSPENSE` | suspense | Plots filled with suspense, puzzles, and unexpected twists that keep the audience guessing and anxious. |
| `SWAPPED_HEIRESSES` | swapped heiresses | Stories involving mistaken or hidden identities related to inheritance and family wealth. |
| `TABOO_RELATIONSHIP` | taboo relationship | Romantic or emotional relationships that cross societal or moral boundaries, such as cousins, teacher-student, blood relatives, or between different species. |
| `TEAR_JERKERS` | tear-jerkers | Emotionally moving dramas designed to evoke sadness and empathy. |
| `WORKPLACE_ROMANCE` | workplace romance | Love stories set in professional environments, involving colleagues or boss-subordinate relationships. |
```
##### List of values for `historical_context`

```xtable
| Value {30%}| Type {15%}| Description {55%}|
|---|---|---|
| `ANCIENT_SETTING` | ancient setting | The drama takes place in ancient settings, including both Eastern and Western backgrounds. |
| `BOOK_TRANSMIGRATION` | book transmigration | The plot involves characters traveling into the world of a book. |
| `FICTIONAL` | fictional | Stories set in fictional time and space backgrounds. |
| `MODERN` | modern | Stories set in modern times. |
| `PERIOD_IN_60_80S` | period in 60-80s | Stories set in the 1960s to 1980s. |
| `REPUBLIC_OF_CHINA` | Republic of China | Stories set in the Republic of China period. |
| `SECOND_CHANCE` | second chance | The plot revolves around reincarnation or characters surviving near-death experiences. Often, the story involves characters using knowledge from their past lives to seek revenge and change their destiny. |
| `TIME_SPACE_TRAVEL` | time/space travel | The plot involves elements of time and space travel. This genre typically describes events where a character travels from one time and space to another for various reasons and through different processes. |
```

### Example

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

## Response

``` xtable
| Field | Type | Description |
|--- |--- |--- |
| code | number  |Return code, see [Appendix - return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message, see [Appendix-Return message](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
| request_id | string | The log id of a request, which uniquely identifies the request. |
| data |object | Returned data. |
# | feed_log_id | string | Catalog handling log ID. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
"message": "OK",
"code": 0,
"data": {
    "feed_log_id": "73474"
},
"request_id": "2020091405212201023125321410665"
}
(/code);
```
