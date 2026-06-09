---
title: "Product set  operators and fields"
doc_id: 1740673108747265
path: "Marketing API / Catalog Management / Guides / Create catalogs and add products / Product set  operators and fields"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Product set  operators and fields

This article lists the operators and the filter fields you can pass to `operation` and `field` respectively when calling [/catalog/set/create/](https://ads.tiktok.com/marketing_api/docs?id=1740572891104257) or [/catalog/set/update/](https://ads.tiktok.com/marketing_api/docs?id=1740572974725122).

### Operators
```xtable
| Operator {23%} | Definition{25%} | Details {52%} |
| -------- | ---------- | ------- |
| EQUAL | Equal to | Can only be used with a single value (string, int/long, float) |
| NOT_EQUAL | Not equal to | Can only be used with a single value (string, int/long, float) |
| GT | Greater than | Can only be used with a single value (string, int/long, float) |
| LT | Less than | Can only be used with a single value(string, int/long, float) |
| GTE | Greater than or equal | Can only be used with a single value (string, int/long, float) |
| LTE | Less than or equal | Can only be used with a single value (string, int/long, float) |
| CONTAIN | Contains a value in the list<br> (case-sensitive) | Can be used with multiple values in a list (list&#60;string&#62;, list&#60;int&#62;, list&#60;long&#62;, list&#60;float&#62;) |
| I_CONTAIN | Contains a value in the list<br> (case-insensitive) | Can be used with multiple values in a list (list&#60;string&#62;, list&#60;int&#62;, list&#60;long&#62;, list&#60;float&#62;) |
| EXCLUDE | Not equal to any value in the list <br>(case-sensitive) | Can be used with multiple values in a list (list&#60;string&#62;, list&#60;int&#62;, list&#60;long&#62;, list&#60;float&#62;) |
| I_EXCLUDE | Not equal to any value in the list<br> (case -insensitive) | Can be used with multiple values in a list (list&#60;string&#62;, list&#60;int&#62;, list&#60;long&#62;, list&#60;float&#62;) |
| NOT_EMPTY | Not empty | No values needed |
| WILDCARD | Contains a substring <br>(case-sensitive) | You can specify a single value or multiple values. When multiple values are specified, the filter looks for strings that contain any value in the list. (string, list&#60;string&#62;)|
| I_WILDCARD | Contains a substring<br> (case-insensitive) | You can specify a single value or multiple values. When multiple values are specified, the filter looks for strings that contain any value in the list. (string, list&#60;string&#62;) |
| NOT_WILDCARD | Not contains any substring<br> (case-sensitive) | You can specify a single value or multiple values. When multiple values are specified, the filter looks for strings that do not contain any value in the list. (string, list&#60;string&#62;) |
| I_NOT_WILDCARD | Not contains any substring<br> (case-insensitive) | You can specify a single value or multiple values. When multiple values are specified, the filter looks for strings that do not contain any value in the list. (string, list&#60;string&#62;) |
| PREFIX | Prefixed with a value<br> (case-sensitive)  | You can specify a value or multiple values. When multiple values are specified, the filter looks for strings that are prefixed with any value. (string, list&#60;string&#62;) |
| I_PREFIX | Prefixed with a value<br> (case-insensitive) | You can specify a value or multiple values. When multiple values are specified, the filter looks for strings that are prefixed with any value. (string, list&#60;string&#62;) |
```



### Filter fields

#### For E-commerce catalog product sets
```xtable
| Field{23%} | Description {25%}| Supported operators {22%}| Additional information{30%} |
| --- | --- | --- | --- |
| title | The title of the product. It can include words like "in sale". | EQUAL<br>NOT_EQUAL<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| description | The product description | EQUAL<br>NOT_EQUAL<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| availability | Product inventory status. | EQUAL<br>NOT_EQUAL | Supported values:<ul><li>`IN_STOCK`</li><li>`AVAILABLE_FOR_ORDER`</li><li>`PREORDER`</li><li>`OUT_OF_STOCK`</li><li>`DISCONTINUED`</li></ul> |
| brand | The product brand | CONTAIN<br> I_CONTAIN<br> EXCLUDE<br> I_EXCLUDE<br> WILDCARD<br> I_WILDCARD<br> NOT_WILDCARD<br> I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| item_group_id | The SPU ID of the product | CONTAIN<br>I_CONTAIN<br> EXCLUDE<br>I_EXCLUDE<br> WILDCARD<br>I_WILDCARD <br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX | Example: tiktok_1234_shirts |
| google_product_category | The product category. | CONTAIN<br>I_CONTAIN<br>EXCLUDE <br>I_EXCLUDE<br>WILDCARD<br> I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX | Example: Apparel & Accessories > Clothing > Shirts |
| condition | The condition of the product in the shop | EQUAL<br>NOT_EQUAL | Supported values:<ul><li>`NEW`</li><li>`REFURBISHED`</li><li>`USED`</li></ul> |
| age_group | The audience age group | EQUAL<br>NOT_EQUAL | Supported values:<ul><li>`NEW_BORN`</li><li>`INFANT`</li><li>`TODDLER`</li><li>`KIDS`</li><li>`ADULT` </li></ul>|
| color | The product color | EQUAL<br>NOT_EQUAL<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| gender | The target gender of the product | EQUAL<br>NOT_EQUAL | Supported values:<ul><li>`MALE`</li><li>`FEMALE`</li><li>`UNISEX` </li></ul>|
| material | The material that the product is made of or made from. | EQUAL<br>NOT_EQUAL <br>WILDCARD<br> I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| pattern | The pattern or image that is on the product | EQUAL<br>NOT_EQUAL<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| product_type | The product type. Only the first three levels are needed. | CONTAIN<br>I_CONTAIN <br>EXCLUDE<br>I_EXCLUDE<br> WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX | Example: Apparel & Accessories > Clothing > Shirts |
| size | The product size | EQUAL<br>NOT_EQUAL<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX | Example: small, L, XL |
| price | The cost price of the product. | GT<br>GTE<br>LT<br>LTE<br>EQUAL |  |
| sale_price | The sale price of the product. | GT<br>GTE<br>LT<br>LTE<br>EQUAL |  |
| sale_price_effective_start_date | The time when sales for this product start. | GT<br>GTE<br>LT<br>LTE<br>EQUAL | Format: 2017-12-31T0:00 |
| sale_price_effective_end_date | The time when the sales for this product end. | GT<br>GTE<br>LT<br>LTE<br>EQUAL | Format: 2017-12-31T0:00 |
| custom_label_(0\~4) | Additional information that you want to filter by. | CONTAIN<br>I_CONTAIN<br> EXCLUDE<br>I_EXCLUDE<br>WILDCARD <br>I_WILDCARD<br>NOT_WILDCARD <br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
```

#### For hotel catalog product sets
```xtable
| Field{24%} | Description {25%}| Supported operators {22%}| Additional information{29%} |
|---|---|---|---|
| hotel_id | A unique ID for the hotel |CONTAIN<br>I_CONTAIN<br>NOT_EQUAL<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX | |
| availability | The current availability of the hotel room. | EQUAL<br>NOT_EQUAL | Supported values: <ul><li>`IN_STOCK`: Available for booking.</li><li>`OUT_OF_STOCK`: Unavailable for booking.</li></ul>|
| hotel_category | The category of the hotel. | EQUAL<br>NOT_EQUAL | Supported values: <ul><li>`HOTEL`: Hotel. </li><li>`APARTMENT`: Apartment.</li><li>`BOAT`: Boat.</li><li>`CAMPSITE`: Campsite.</li><li>`CAPSULE_HOTEL`: Capsule hotel.</li><li>`GUESTHOUSE`: Guesthouse. </li><li>`HOMESTAY`: Homestay.</li><li>`HOSTEL`: Hostel. </li><li>`LUXURY_TENT`: Luxury tent.</li><li>`RESORT`: Resorts.</li><li>`VILLA`: Villa.</li></ul> |
| brand | The brand name of the hotel. | NOT_EQUAL<br>CONTAIN |  |
| city | The city where the hotel is located. | CONTAIN<br> I_CONTAIN<br> NOT_EQUAL<br> WILDCARD<br> I_WILDCARD<br> NOT_WILDCARD<br> I_NOT_WILDCARD <br>PREFIX<br>I_PREFIX  ||
| region | The region (state/province) where the hotel is located. | NOT_EQUAL <br>CONTAIN  |   |
| margin_level | The percentage of the base price (`price`) for a hotel room that the hotel charges as deposit. | NOT_EQUAL <br>CONTAIN  | Value range: 1-10. |
| guest_rating_score | The actual score of the hotel.<br><br> This filter field filters products based on the product attribute field `score` within the object array `guest_ratings`. | GT<br>GTE<br>LT<br>LTE<br>EQUAL | Value range: ≥0. |
|guest_rating_number_of_reviewers | The number of guests who have reviewed the hotel.<br><br>This filter field filters products based on the product attribute field `number_of_reviewers` within the object array `guest_ratings`. | GT<br>GTE<br>LT<br>LTE<br>EQUAL | Value range: ≥0. |
|star_rating | The star rating of the hotel. | NOT_EQUAL<br>CONTAIN   |  |
| price | The base price for one night in the hotel room. | GT<br>GTE<br>LT<br>LTE<br>EQUAL  | |
| custom_label_0<br>custom_label_1<br>custom_label_2<br>custom_label_3<br>custom_label_4 | Additional information. |CONTAIN <br>I_CONTAIN <br>NOT_EQUAL <br>EXCLUDE <br>I_EXCLUDE <br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX   | |
```

#### For flight catalog product sets
```xtable
| Field{24%} | Description {25%}| Supported operators {22%}| Additional information{29%} |
|---|---|---|---|
| availability | The current availability of the flight.|EQUAL<br>NOT_EQUAL | Supported values: <ul><li>`IN_STOCK`: Available for booking.</li><li>`OUT_OF_STOCK`: Unavailable for booking.</li></ul>|
| origin_airport | The origin airport of the flight, in the format of a three-letter IATA (International Air Transport Association) code. | CONTAIN<br> I_CONTAIN<br> NOT_EQUAL<br> WILDCARD<br> I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX | Example: `AAR`. |
| destination_airport | The destination airport of the flight, in the format of a three-letter IATA code. | CONTAIN <br>I_CONTAIN <br>NOT_EQUAL <br>WILDCARD <br>I_WILDCARD <br>NOT_WILDCARD <br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX | Example: `ABD`. |
| origin_city | The origin city of the flight. | CONTAIN <br>I_CONTAIN <br>NOT_EQUAL <br>WILDCARD <br>I_WILDCARD <br>NOT_WILDCARD <br>I_NOT_WILDCARD <br>PREFIX<br>I_PREFIX| |
|destination_city | The destination city of the flight. | CONTAIN<br>I_CONTAIN<br>NOT_EQUAL<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX| |
|airline_company | The name of the airline company operating the flight. | CONTAIN<br>I_CONTAIN<br>NOT_EQUAL<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX|  |
| price | The base price for the flight. | GT<br>GTE<br>LT<br>LTE<br>EQUAL   | |
| custom_label_0<br>custom_label_1<br>custom_label_2<br>custom_label_3<br>custom_label_4 | Additional information. |CONTAIN <br>I_CONTAIN <br>NOT_EQUAL <br>EXCLUDE <br>I_EXCLUDE <br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX   | |
```

#### For destination catalog product sets
```xtable
| Field{24%} | Description {25%}| Supported operators {22%}| Additional information{29%} |
|---|---|---|---|
| destination_id | A unique ID for the destination. | CONTAIN<br> I_CONTAIN<br> EQUAL<br> NOT_EQUAL<br> PREFIX<br>I_PREFIX  | |
| city | The city where the destination is located. | CONTAIN<br> I_CONTAIN<br> NOT_EQUAL<br> WILDCARD<br> I_WILDCARD <br>NOT_WILDCARD <br>I_NOT_WILDCARD <br>PREFIX <br>I_PREFIX  | |
| region | The region (state/province) where the destination is located. |NOT_EQUAL<br>CONTAIN   | |
| price | The average or lowest price for the destination. | GT<br>GTE<br>LT<br>LTE<br>EQUAL|  |
| custom_label_0<br>custom_label_1<br>custom_label_2<br>custom_label_3<br>custom_label_4 | Additional information. |CONTAIN <br>I_CONTAIN <br>NOT_EQUAL <br>EXCLUDE <br>I_EXCLUDE <br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX   | |
```

#### For entertainment catalog product sets
```xtable
| Field{23%} | Description {25%}| Supported operators {22%}| Additional information{30%} |
|---|---|---|---|
| title | The name of the media title item. | EQUAL<br>NOT_EQUAL<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX   |  |
| availability | The current availability of the media title item. | EQUAL<br>NOT_EQUAL | Supported values:<ul><li>`AVAILABLE`</li><li>`SUBSCRIBERS_ONLY`</li><li>`ON_DEMAND`</li><li>`NOT_AVAILABLE`</li></ul>  |
| condition | The current condition of the media title item. | EQUAL<br> NOT_EQUAL | Supported values:<ul><li>`NEW`: new. </li><li>`USED`: used.</li></ul>  |
| timeline | The timeline for the media title item. | EQUAL<br>NOT_EQUAL | Supported values: <ul><li>`COMING_SOON` : The media title item is not available now, but will be released soon. </li><li>`ONLINE`: The media title item is currently available for purchase. </li><li>`EXPIRE_SOON`: The media title item will no longer be available for purchase soon.</li></ul>|
| category | The category of the media title item. | EQUAL<br>NOT_EQUAL | Supported values:<ul><li>`MOVIE` : movie. </li><li>`MUSIC` : music. </li><li>`TV_SHOW` : TV show. </li><li>`TV_SERIES`: TV series. </li><li>`SPORTS_GAME` : sports game. </li><li>`LIVE_EVENT`: live event. </li></ul>|
| genres | The genres of the media title item. |CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br> I_PREFIX| Supported values: <br>To find out the allowed values for this field, refer to the "<a href="#item-link-List of values for genres "> List of values for `genres` </a> " section below.  |
| price | The price for the media title item. |GT<br>GTE<br>LT<br>LTE<br> EQUAL   |      |
| custom_label_0<br>  custom_label_1<br>  custom_label_2 <br> custom_label_3 <br> custom_label_4 | Additional information that you want to filter by. | CONTAIN<br> I_CONTAIN<br> EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX  | |
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
| `LGBT` | lgbt |
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

#### For Auto-Inventory catalog product sets
```xtable
| Field{23%} | Description {25%}| Supported operators {22%}| Additional information{30%} |
|---|---|---|---|
| vehicle_id | A unique ID for the vehicle. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| title | The name of the vehicle. | EQUAL<br>NOT_EQUAL<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| availability | The current availability of the vehicle. | EQUAL<br>NOT_EQUAL<br>CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE | Supported values:<ul><li>`AVAILABLE`: available for order.</li><li>`NOT_AVAILABLE`: Not available for order.</li></ul> |
| condition | The current condition of the vehicle. | EQUAL<br>NOT_EQUAL<br>CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE | Supported values:<ul><li>`EXCELLENT`: excellent.</li><li>`GOOD`: good.</li><li>`FAIR`: fair.</li><li>`POOR`: poor.</li><li>`OTHER`: other.</li></ul> |
| state_of_vehicle | The state of the vehicle. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE | Supported values:<ul><li>`NEW`: new.</li><li>`USED`: used.</li><li>`CPO`: CPO (certified pre-owned). CPO vehicles are used vehicles that have undergone thorough inspection and are typically newer, have lower mileage, and come with additional warranty coverage compared to regular used cars.</li></ul> |
| vehicle_type | The type of the vehicle. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE | Supported values:<ul><li>`BOAT`: boat.</li><li>`CAR_TRUCK`: car or truck.</li><li>`COMMERCIAL`: A vehicle used for commercial purposes, such as a delivery truck.</li><li>`MOTORCYCLE`: motorcycle.</li><li>`POWERSPORT`: powersport. Recreational vehicles that are designed for outdoor activities and adventure, such as a snowmobile.</li><li>`RV_CAMPER`: recreational vehicle or camper designed for travel and camping purposes.</li><li>`TRAILER`: trailer.</li><li>`OTHER`: Any other vehicle type not covered above.</li></ul> |
| make | The make (brand) of the vehicle. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| model | The model of the vehicle. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| trim | The trim of the vehicle. The trim represents the distinctive specification package of the vehicle, tailored specifically to that model. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| year | The year when the vehicle was launched, in the format of `YYYY`. | GT<br>GTE<br>LT<br>LTE<br>EQUAL | |
| vin | The VIN (Vehicle Identification Number) of the vehicle. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| value <br>(within the mileage object) | Mileage value. | GT<br>GTE<br>LT<br>LTE<br>EQUAL |  |
| body_style | The body style of the vehicle. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE | Supported values:<ul><li>`CONVERTIBLE`: convertible.</li><li>`COUPE`: coupe.</li><li>`CROSSOVER`: crossover.</li><li>`HATCHBACK`: hatchback.</li><li>`MINIVAN`: minivan.</li><li>`SMALL_CAR`: small car.</li><li>`SEDAN`: sedan.</li><li>`SUV`: SUV.</li><li>`TRUCK`: truck.</li><li>`VAN`: van.</li><li>`WAGON`: wagon.</li><li>`OTHER`: other.</li></ul> |
| exterior_color | The exterior color of the vehicle. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| interior_color | The interior color of the vehicle. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| transmission | The transmission type of the vehicle. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE | Supported values:<ul><li>`AUTOMATIC`: automatic.</li><li>`MANUAL`: manual.</li></ul> |
| drivetrain | The drivetrain type of the vehicle. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE | Supported values:<ul><li>`4X2`: four-wheel drive vehicle with two wheel drive (2WD). The drivetrain delivers power to either the front wheels or the rear wheels.</li><li>`4X4`: four-wheel drive vehicle with four wheel drive. The drivetrain delivers power to all four wheels.</li><li>`AWD`: all-wheel drive. The drivetrain delivers power to all wheels continuously.</li><li>`FWD`: front-wheel drive. The drivetrain delivers power to the front wheels only.</li><li>`RWD`: rear-wheel drive. The drivetrain delivers power to the rear wheels only.</li><li>`OTHER`: any other drivetrain type not covered above, such as electric vehicles with unique drivetrain styles.</li></ul> |
| fuel_type | The fuel type of the vehicle. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE | Supported values:<ul><li>`DIESEL`: diesel.</li><li>`ELECTRIC`: electric.</li><li>`FLEX`: flex fuel, a blend of gasoline and ethanol.</li><li>`GASOLINE`: gasoline.</li><li>`HYBRID`: hybrid.</li><li>`OTHER`: other.</li></ul> |
| city | The city where the dealership is located. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| region | The region (state or province) where the dealership is located. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| dealer_id | A unique ID for the dealership. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| dealer_name | The name of the dealership. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| stock_number | The stock number assigned to the vehicle by the dealership. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
| date_first_on_lot | The date when the vehicle physically arrives at the dealership's lot, in the format of `YYYY-MM-DD`. | GT<br>GTE<br>LT<br>LTE<br>EQUAL |  |
| days_on_lot | The number of days the vehicle has been on the dealership's lot. | GT<br>GTE<br>LT<br>LTE<br>EQUAL |  |
| price | The price of the vehicle. | GT<br>GTE<br>LT<br>LTE<br>EQUAL |  |
| sale_price | The discounted price of the vehicle. | GT<br>GTE<br>LT<br>LTE<br>EQUAL |  |
| custom_label_0<br>custom_label_1<br>custom_label_2<br>custom_label_3<br>custom_label_4 | Additional information that you want to filter by. | CONTAIN<br>I_CONTAIN<br>EXCLUDE<br>I_EXCLUDE<br>WILDCARD<br>I_WILDCARD<br>NOT_WILDCARD<br>I_NOT_WILDCARD<br>PREFIX<br>I_PREFIX |  |
```


#### For Auto-Model catalog product sets
```xtable
| Field{24%} | Description {25%}| Supported operators {22%}| Additional information{29%} |
|---|---|---|---|
| vehicle_id | A unique ID for the vehicle. | CONTAIN<br> I_CONTAIN<br> EXCLUDE<br> I_EXCLUDE<br> WILDCARD<br> I_WILDCARD<br> NOT_WILDCARD<br> I_NOT_WILDCARD<br> PREFIX<br> I_PREFIX | |
| title | The name of the vehicle. | EQUAL<br> NOT_EQUAL<br> WILDCARD<br> I_WILDCARD<br> NOT_WILDCARD<br> I_NOT_WILDCARD<br> PREFIX<br> I_PREFIX | |
| make | The make (brand) of the vehicle. | CONTAIN<br> I_CONTAIN<br> EXCLUDE<br> I_EXCLUDE<br> WILDCARD<br> I_WILDCARD<br> NOT_WILDCARD<br> I_NOT_WILDCARD<br> PREFIX<br> I_PREFIX | |
| model | The model of the vehicle. | CONTAIN<br> I_CONTAIN<br> EXCLUDE<br> I_EXCLUDE<br> WILDCARD<br> I_WILDCARD<br> NOT_WILDCARD<br> I_NOT_WILDCARD<br> PREFIX<br> I_PREFIX | |
| trim | The trim of the vehicle. The trim represents the distinctive specification package of the vehicle, tailored specifically to that model. | CONTAIN<br> I_CONTAIN<br> EXCLUDE<br> I_EXCLUDE<br> WILDCARD<br> I_WILDCARD<br> NOT_WILDCARD<br> I_NOT_WILDCARD<br> PREFIX<br> I_PREFIX | |
| year | The year when the vehicle was launched, in the format of `YYYY`. | GT<br>GTE<br>LT<br>LTE<br>EQUAL | |
| exterior_color | The color of the vehicle. | CONTAIN<br> I_CONTAIN<br> EXCLUDE<br> I_EXCLUDE<br> WILDCARD<br> I_WILDCARD<br> NOT_WILDCARD<br> I_NOT_WILDCARD<br> PREFIX<br> I_PREFIX | |
```
