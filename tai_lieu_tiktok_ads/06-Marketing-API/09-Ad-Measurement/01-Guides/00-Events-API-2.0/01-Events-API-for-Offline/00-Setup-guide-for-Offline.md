---
title: "Setup guide for Offline"
doc_id: 1771101027431425
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for Offline / Setup guide for Offline"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Setup guide for Offline

# Get Started
This guide walks you through the process of setting up Events API 2.0 for Offline.

- If you want to create a new Offline Event Set, please start from the first step. 
- If you want to use an existing Offline Event Set, you can start from the second step.

## 1. Create an Offline Event Set

### Option 1: Through Events Manager UI

a. In TikTok Ads Manager, navigate to **Tools** > **Events**.

b. Click **Connect data source** > **Offline**. 

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7693acc4769aeb8e015a83519d1b959a" alt="Connect data source" width="60%" style="color:#808080"/>

c. Enter the **Offline event set name** and select **Create**.

d. Turn on **Auto-tracking**: All future campaigns from this ad account will use this Offline Event Set for ad data connection and attribution.

e. After creating the Offline Event Set, record the Offline Event Set ID (this will be used as the `event_source_id` for sending offline events via Events API).

### Option 2: Through API
You can also create a new Offline Event Set using `/offline/create/`.  


#### Request
**Address** https://business-api.tiktok.com/open_api/v1.3/offline/create/

**Method** POST

**Request Header**

```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
|Access-Token {Required}|string|Authorized access token. <br>To obtain an access token, refer to [Events API 2.0 - Authentication](https://ads.tiktok.com/marketing_api/docs?id=1771101130925058).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.|
```

**Parameters**
```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|name{Required}|string|Offline Event Set name. <br>Example: in_store_purchases.|
|description|string|Offline Event Setdescription. <br>Example: in_store_purchases_blackfriday.|
|auto_tracking|boolean|Whether to enable Auto Tracking.<br>Enum values: `true`, `false`.<br> If this field is set to `true`, new campaigns created afterwards under this advertiser account will use this event set for ad data connection and attribution. |
```
##### Example
> <span style="color: DodgerBlue">**Note**</span><br>You need to replace the dummy data in the example with your own data.

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/offline/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "advertiser_id": "123456", 
  "name": "EVENT_SET_NAME", 
  "description": "In_store_purchases_blackfriday_2021",
  "auto_tracking": true
}'
(/code)
```

#### Response
```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.<br>For a successful request,  this field will be an empty object (`{}`). <br>For a failed request, this field will not be returned.|
#|event_set_id |string|ID of the Offline Event Set that you just created.|
```

##### Example
```xcodeblock
(code)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "event_set_id": "{{event_set_id}}"
    }
}
(/code)
```

## 2. Generate an access token
To authenticate Events API 2.0 calls, you need an access token. To learn about how to obtain an access token, refer to [Events API 2.0 > Authentication](https://ads.tiktok.com/marketing_api/docs?id=1771101130925058).

# Send Offline Events with Events API 2.0
1. Upload Offline Events to your Offline Event Set

After you create an Offline Event Set, you need to upload Offline Events to your Offline Event Set. Events are defined as actions an offline visitor takes to achieve a business goal. For details of supported offline events, see [Supported Events-Offline Standard Events](https://ads.tiktok.com/marketing_api/docs?id=1771101186666498#item-link-Offline%20Standard%20Events).

2. Use the recommended event payload format. 

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/event/track/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. <br>To obtain an access token, refer to [Events API 2.0 - Authentication](https://ads.tiktok.com/marketing_api/docs?id=1771101130925058).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

## Parameters for reporting Offline Events

For each parameter value format, refer to the lists below.

### Top-level parameters
```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
| event_source {Required} | string | This field is used to specify the type of events you are uploading through Events API.  <br>The allowed enum value:  <ul><li> `offline`: The conversions took place in a physical store and are measured by an Offline Event Set ID. </li></ul>   |
| event_source_id {Required} | string | An Event Source ID that is used to measure events.  <br>When `event_source` is set to `offline`, specify an Offline Event Set ID through this field. To obtain an Offline Event Set ID, refer to [Setup Guide for Offline-1.Create an Offline Event Set](https://ads.tiktok.com/marketing_api/docs?id=1771101027431425#item-link-1.Create%20an%20Offline%20Event%20Set) or the instruction below this table.  |
| data {Required} | object[] | An object array of the events you want to report.  <ul><li> If you are sending a single event, pass one object in the array. </li><li> If you are batching multiple events in a single payload, you can report up to 1000 objects in one request. If a request contains more than 1,000 events, the </strong>entire request will be rejected</strong>.  </li><li> To optimize campaign performance, it's <strong>highly recommended to send the event in real-time (without batching)</strong> as soon as it is seen on the advertiser's server. </li></ul>    |
#| event {Required} | string | Conversion event name. <br> To find out the supported values for this field, refer to [Events API 2.0-Supported events](https://ads.tiktok.com/marketing_api/docs?id=1771101186666498).   |
#| event_time {Required} | integer | The time when the event occurred, indicated as a Unix timestamp measured in seconds, and in UTC+0 time zone.   |
#| event_id {+Conditional} | string | A unique value for each event.<br>  This ID can be used to match data between partner and TikTok. We suggest a String of 32 characters, including numeric digits (0-9), uppercase letters (A-Z), and lowercase letters (a-z). |
#| user {Required} | object | Information about the customer.   <br>  To view the list of fields included in the `user` object, refer to the "<a href="#item-link-user parameters">`user` parameters</a>" section below.   |
#| properties | object | Information about the product, order and additional information to help improve ad performance.  <br>To view the list of fields included in the `properties` object, refer to the "<a href="#item-link-properties parameters">`properties` parameters</a>" section below.   |
```
- `event_source_id` for Offline will be the ID of the Offline Event Set you want to send the events to. You can identify the ID of the offline event set by navigating to  **Tools** > **Events** > **Data sources** button. The ID of the Offline Event Set is `"7481671978261495824"` in the example below.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/365860f0b2a51ec85c23f77f1553bc7a" alt="Offline Event Set ID" width="60%" style="color:#808080"/>


#### `user` parameters

```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
| email | string <br> or string[]| <strong>SHA-256 hashing is required.</strong> <br>The email address, or email addresses of the customer. <br> Follow the instructions below to obtain a valid value: <ul><li><strong>Trim</strong> any leading and trailing spaces of each email before hashing. </li><li><strong>Lowercase</strong> all characters before hashing.</li><li><strong>Hash</strong> the normalized email values using SHA-256.</li></ul><strong>Example</strong>: <ul><li> Original: " ALICE_abc@gmail.com " </li><li> Normalized: "alice_abc@gmail.com" (leading and trailing spaces trimmed and lowercased) </li><li> SHA-256 hashed: "848a771458438fc2ec420560d769fb9b9b86851ee338ec56517baabd79d3bb4f"</li></ul>|
| phone | string<br> or string[] |  <strong>SHA-256 hashing is required. </strong> <br>The phone number or phone numbers of the customer. <br>Follow the instructions below to obtain a valid value: <ul><li><strong>Normalize</strong> the phone number to [E.164 format](https://www.twilio.com/docs/glossary/what-e164) (for example, "+12133734253"). We recommend using [https://github.com/catamphetamine/libphonenumber-js](https://github.com/catamphetamine/libphonenumber-js) for E.164 parsing. <ul><li><strong>Country code</strong> must be included and prefixed with the `+` sign, without any parentheses or leading `0`s. For example, a US phone number should be prefixed with the `+1` country code.</li><li> <strong>No spaces, letters or symbols</strong> except the `+` for the country code are included. </li></ul></li><li><strong>SHA-256 hash</strong> the phone number after normalizing. </li></ul><strong>Example</strong>: <ul><li> Original: "(+1)2133734253"</li><li> Normalized to E.164 format: "+12133734253"</li><li> SHA256 hashed: "9f7ec22d72092cd3c0b58726ed9c2d91b92e51a3f29837508fb2948bb22dd2fd"</li></ul>|
```

#### `properties` parameters
```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
|content_ids| string[]  |Unique ID or array of multiple IDs of the products or content. <br>Example: `["987", "654"]`. <br>We recommend using `sku_id` or `item_group_id` that matches the `sku_id` or `item_group_id` you set up in the catalog, if available.<br>This field is required for Video Shopping Ads (VSA). |
| contents | object[] | Relevant products in an event with product information.   <br> To view the list of fields included in the contents object, refer to the "<a href="#item-link-contents parameters">`contents` parameters</a>" section below.   |
| content_type| string | The type of content in the event.   <br>Enum values: `product`, `product_group`.   <ul><li> When the `content_id` in the `contents` parameter is specified as `sku_id`, set this field to `product`.  </li><li> When the `content_id` in the `contents` parameter is specified as `item_group_id`, set this field to `product_group`.  </li></ul> |
| currency | string | <strong>Recommended for revenue related events. </strong> <br><br>     The ISO 4217 currency code.   <br>Examples: `EUR`, `USD`, `JPY`.  <br><strong>List of currencies currently supported</strong>: `AED`, `ARS`, `AUD`, `BDT`, `BHD`, `BIF`, `BOB`, `BRL`, `CAD`, `CHF`, `CLP`, `CNY`, `COP`, `CRC`, `CZK`, `DKK`, `DZD`, `EGP`, `EUR`, `GBP`, `GTQ`, `HKD`, `HNL`, `HUF`, `IDR`, `ILS`, `INR`, `ISK`, `JPY`, `KES`, `KHR`, `KRW`, `KWD`, `KZT`, `MAD`, `MOP`, `MXN`, `MYR`, `NGN`, `NIO`, `NOK`, `NZD`, `OMR`, `PEN`, `PHP`, `PHP`, `PKR`, `PLN`, `PYG`, `QAR`, `RON`, `RUB`, `SAR`, `SEK`, `SGD`, `THB`, `TRY`, `TWD`, `UAH`, `USD`, `VES`, `VND`, `ZAR` .  |
| value | float | <strong>Recommended for revenue related events. </strong> <br> <br> Value of the order or items sold. <br> The value should always be formatted as an integer or decimal (for instance, 10.00) regardless of the location, currency, or other factors. It should not contain any currency symbols, special characters, letters, or commas.   <br><p><span style="color:darkred"><b>Note</b></span>: `price` specifies the price for a single item, and `value` specifies the total price of the order. For example, if you have two items each sold for ten dollars, the `price` parameter would be `10.00` and the `value` parameter would be `20.00`.   |
|num_items|integer|The quantity of items.|
|search_string|string|The user-entered string for search.|
| description | string | Description of the item or page.   |
| order_id | string | Order ID.   |
| shop_id | string | Shop ID.   |
```

##### `contents` parameters
```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|---|---|
| price | float | The price of the item.  <br><p><span style="color:darkred"><b>Note</b></span>: `price` specifies the price for a single item, and `value` specifies the total price of the order. For example, if you have two items each sold for ten dollars, the `price` parameter would be `10.00` and the `value` parameter would be `20.00`.   <br> Example: `10`, `10.5`.   |
| content_id | string | Unique ID of the product or content.  <br>We recommend using `sku_id` or `item_group_id` if you have one.  <br> If you have set up `sku_id` or `item_group_id` in the catalog, the ID should match the `sku_id` or `item_group_id`.   |
| content_category| string | Category of the page or product.   |
| content_name | string | Name of the page or product.   |
| brand | string | Brand name of the product item.   |
```
Note that another alternative for uploading Offline Events to an Offline Event set is via **file** upload on Events Manager.

## Sample payload

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/event/track/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "event_source": "offline",
  "event_source_id": "{{Offline-Event-Set-ID}}",
  "data": [
    {
      "event_id":"1616318632825_357",
      "event": "Purchase",
      "event_time": 1684574395,
      "user": {
        "email": ["848a771458438fc2ec420560d769fb9b9b86851ee338ec56517baabd79d3bb4f",
        "dd6ff77f54e2106661089bae4d40cdb600979bf7edc9eb65c0942ba55c7c2d7f"],
        "phone": ["2f9d2b4df907e5c9a7b3434351b55700167b998a83dc479b825096486ffcf4ea"]
       },
      "properties": {
        "order_id": "abc_xyz",
        "shop_id": "123abc",
        "contents": [
          {
            "price": 8,
            "quantity": 2,
            "content_id": "1077218",
            "content_name": "running shoes",
            "content_category": "Shoes > Sneakers > running shoes",
            "brand": "your_brand_name"
          },
          {
            "price": 30,
            "quantity": 1,
            "content_id": "1197218",
            "content_name": "running shoes",
            "content_category": "Shoes > Sneakers > running shoes",
            "brand": "your_brand_name"
          }
        ],
        "content_type": "product",
        "currency": "USD",
        "value": 46.00
      }
    }
  ]
}'
(/code)
```
# Verify Offline Events setup
For best practices of verifying events API setup, see [Verify Events API setup](https://ads.tiktok.com/marketing_api/docs?id=1771101049613313).

# Manage Offline Event Sets (Optional)
You can manage your Offline Event Sets in the following ways:
- Update the name or the Auto Tracking setting of an Offline Event Set via [/offline/update/](https://ads.tiktok.com/marketing_api/docs?id=1765596741157889).
- Delete an Offline Event Set via [/offline/delete/](https://ads.tiktok.com/marketing_api/docs?id=1765596790860802).
- Get the Offline Event Sets under an advertiser account via [/offline/get/](https://ads.tiktok.com/marketing_api/docs?id=1765596808589313).

# Measure Offline Event sets in your regular ads or aco ads
Specify the Offline Event Sets that you want to measure in your ads via `tracking_offline_event_set_ids` in [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) or [/ad/aco/create/](https://ads.tiktok.com/marketing_api/docs?id=1739473063234626). Note that you can measure at most 50 Offline Event Sets, including a maximum of 10 auto-tracking ones, in an ad. 
