---
title: "Setup guide for CRM"
doc_id: 1835052338764993
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for CRM / Setup guide for CRM"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Setup guide for CRM

# Get Started
This guide walks you through the process of setting up Events API 2.0 for CRM (Customer relationship management).
- If you want to create a new CRM Event Set, please start from the first step. 
- If you want to use an existing CRM Event Set, you can start from the second step.


## 1. Create a CRM Event Set
Create a CRM Event Set using [/crm/create/](https://business-api.tiktok.com/portal/docs?id=1858830704718658).

After creating the CRM Event Set, record the CRM Event Set ID (this will be used as the `event_source_id` for sending CRM events via Events API).

## 2. Generate an access token
To authenticate Events API calls, you need an access token. <br>To learn about how to obtain an access token, refer to [Events API 2.0 > Authentication](https://ads.tiktok.com/marketing_api/docs?id=1771101130925058).


# Share CRM Events with Events API
1. Upload CRM Events to your CRM Event Set

After you create an CRM Event Set, you need to upload CRM Events to your CRM Event Set. Events are defined as actions a visitor takes on your CRM system to achieve a business goal. 

For details of supported CRM events, see [Supported Events - CRM Standard Events](https://business-api.tiktok.com/portal/docs?id=1771101186666498#item-link-CRM%20Standard%20Events).

2. Use the recommended event payload format.

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/event/track/

**Method** POST

**Header**

```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
|Access-Token {Required}|string|Authorized access token. <br>To obtain an access token, refer to [Events API 2.0 - Authentication](https://ads.tiktok.com/marketing_api/docs?id=1771101130925058).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```
## Parameters for reporting CRM Events
For each parameter value format, refer to the lists below.


### Top-level parameters
```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
| event_source {Required} | string | This field is used to specify the type of events you are uploading through Events API.  <br>The allowed enum value:  <ul><li> `crm`: The lead events took place on a CRM system and are tracked by a CRM Event Set ID.</li></ul>   |
| event_source_id {Required} | string | An Event Source ID that is used to measure events.  <br>When `event_source` is set to `crm`, specify an CRM Event Set ID through this field. <br>To obtain a CRM Event Set ID, refer to <a href="#item-link-1. Create a CRM Event Set">Create a CRM Event Set</a> or the instruction below this table.    |
| data {Required} | object[] | An object array of the events you want to report.  <ul><li> If you are sending a single event, pass one object in the array. </li><li> If you are batching multiple events in a single payload, you can report up to 1000 objects in one request. If a request contains more than 1,000 events, the </strong>entire request will be rejected</strong>.  </li><li> To optimize campaign performance, it's <strong>highly recommended to send the event in real-time (without batching)</strong> as soon as it is seen on the advertiser's server. </li></ul>    |
#| event {Required} | string | Conversion event name. It can be either a Standard Event or a Custom Event.   <br>  <br> To find out the supported values for Standard Events, refer to [Events API 2.0-Supported events](https://ads.tiktok.com/marketing_api/docs?id=1771101186666498).    <br> To report a Custom Event, pass a customized name in this field.|
#| event_time {Required} | integer | The time when the event occurred, indicated as a Unix timestamp measured in seconds, and in UTC+0 time zone.   |
#| event_id {+Conditional} | string | A unique value for each event. This ID can be used to match data between partner and TikTok. <br> We suggest a String of 32 characters, including numeric digits (0-9), uppercase letters (A-Z), and lowercase letters (a-z). |
#| user | object | Information about the customer.   <br>  To view the list of fields included in the `user` object, refer to the "<a href="#item-link-user parameters">`user` parameters</a>" section below.   |
#| properties {+Conditional} | object | Information about the product, order and additional information to help improve ad performance.  <br>To view the list of fields included in the `properties` object, refer to the "<a href="#item-link-properties parameters">`properties` parameters</a>" section below.   |
#| lead | object | Information about the lead. <br> To view the list of fields included in the `lead` object, refer to the "<a href="#item-link-lead parameters">`lead` parameters</a>" section below.   |
```
- `event_source_id` for CRM will be the ID of the CRM Event Set you want to send the events to. You can identify the ID of the CRM event set by navigating to **Tools** > **Events** > **Data sources** button. The ID of the CRM Event Set is 7446580941998374913 in the example below.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/042070948f43645036b2ba364d9fac49" alt="CRM Event Set ID" width="60%" style="color:#808080" />


#### `user` parameters

```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
| email | string <br> or string[]| <strong>SHA-256 hashing is required.</strong> <br>The email address, or email addresses of the customer. <br> Follow the instructions below to obtain a valid value: <ul><li><strong>Trim</strong> any leading and trailing spaces of each email before hashing. </li><li><strong>Lowercase</strong> all characters before hashing.</li><li><strong>Hash</strong> the normalized email values using SHA-256.</li></ul><strong>Example</strong>: <ul><li> Original: " ALICE_abc@gmail.com " </li><li> Normalized: "alice_abc@gmail.com" (leading and trailing spaces trimmed and lowercased) </li><li> SHA-256 hashed: "848a771458438fc2ec420560d769fb9b9b86851ee338ec56517baabd79d3bb4f"</li></ul>|
| phone | string<br> or string[] | <strong>SHA-256 hashing is required. </strong> <br>The phone number or phone numbers of the customer. <br>Follow the instructions below to obtain a valid value: <ul><li><strong>Normalize</strong> the phone number to [E.164 format](https://www.twilio.com/docs/glossary/what-e164) (for example, "+12133734253"). We recommend using [https://github.com/catamphetamine/libphonenumber-js](https://github.com/catamphetamine/libphonenumber-js) for E.164 parsing. <ul><li><strong>Country code</strong> must be included and prefixed with the `+` sign, without any parentheses or leading `0`s. For example, a US phone number should be prefixed with the `+1` country code.</li><li> <strong>No spaces, letters or symbols</strong> except the `+` for the country code are included. </li></ul></li><li><strong>SHA-256 hash</strong> the phone number after normalizing. </li></ul><strong>Example</strong>: <ul><li> Original: "(+1)2133734253"</li><li> Normalized to E.164 format: "+12133734253"</li><li> SHA256 hashed: "9f7ec22d72092cd3c0b58726ed9c2d91b92e51a3f29837508fb2948bb22dd2fd"</li></ul>|
```
#### `properties` parameters
```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
|content_ids| string[] | Unique ID or array of multiple IDs of the products or content. <br>Example: `["987", "654"]`. <br>We recommend using `sku_id` or `item_group_id` that matches the `sku_id` or `item_group_id` you set up in the catalog, if available.<br>This field is required for Video Shopping Ads (VSA). |
| contents | object[] | Relevant products in an event with product information.   <br> To view the list of fields included in the contents object, refer to the "<a href="#item-link-contents parameters">`contents` parameters</a>" section below.   |
| content_type| string | The type of content in the event.   <br>Enum values: `product`, `product_group`.   <ul><li> When the `content_id` in the `contents` parameter is specified as `sku_id`, set this field to `product`.  </li><li> When the `content_id` in the `contents` parameter is specified as `item_group_id`, set this field to `product_group`.  </li></ul> |
| currency | string | <strong>Recommended for revenue related events. </strong> <br><br>     The ISO 4217 currency code.   <br>Examples: `EUR`, `USD`, `JPY`.  <br><strong>List of currencies currently supported</strong>: `AED`, `ARS`, `AUD`, `BDT`, `BHD`, `BIF`, `BOB`, `BRL`, `CAD`, `CHF`, `CLP`, `CNY`, `COP`, `CRC`, `CZK`, `DKK`, `DZD`, `EGP`, `EUR`, `GBP`, `GTQ`, `HKD`, `HNL`, `HUF`, `IDR`, `ILS`, `INR`, `ISK`, `JPY`, `KES`, `KHR`, `KRW`, `KWD`, `KZT`, `MAD`, `MOP`, `MXN`, `MYR`, `NGN`, `NIO`, `NOK`, `NZD`, `OMR`, `PEN`, `PHP`, `PHP`, `PKR`, `PLN`, `PYG`, `QAR`, `RON`, `RUB`, `SAR`, `SEK`, `SGD`, `THB`, `TRY`, `TWD`, `UAH`, `USD`, `VES`, `VND`, `ZAR` .  |
| value | float | <strong>Recommended for revenue related events. </strong> <br> <br> Value of the order or items sold. <br> The value should always be formatted as an integer or decimal (for instance, 10.00) regardless of the location, currency, or other factors. It should not contain any currency symbols, special characters, letters, or commas.   <br><p><span style="color:darkred"><b>Note</b></span>: `price` specifies the price for a single item, and `value` specifies the total price of the order. For example, if you have two items each sold for ten dollars, the `price` parameter would be `10.00` and the `value` parameter would be `20.00`.   |
|num_items|integer| The quantity of items.|
|search_string|string|The user-entered string for search.|
| description | string | Description of the item or page.   |
| order_id | string | Order ID.   |
| shop_id | string | Shop ID.   |
```

##### `contents` parameters
```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
| price | float | The price of the item.  <br><p><span style="color:darkred"><b>Note</b></span>: `price` specifies the price for a single item, and `value` specifies the total price of the order. For example, if you have two items each sold for ten dollars, the `price` parameter would be `10.00` and the `value` parameter would be `20.00`.   <br> Example: `10`, `10.5`.   |
| content_id | string | Unique ID of the product or content.  <br>We recommend using `sku_id` or `item_group_id` if you have one.  <br> If you have set up `sku_id` or `item_group_id` in the catalog, the ID should match the `sku_id` or `item_group_id`.   |
| content_category| string | Category of the page or product.   |
| content_name | string | Name of the page or product.   |
| brand | string | Brand name of the product item.   |
```

#### `lead` parameters
Use the `lead` parameters to share information about the leads in a CRM system with Events API 2.0. The `lead` parameters should only be used when reporting CRM Events (`event_source` = `crm`).
```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
|lead_id|string|<b>Required for CRM events</b>.<br> ID of TikTok leads.<br>Every lead will have its `lead_id` when exported from TikTok.<br>To obtain the ID of a lead, download the lead by using the endpoints [/page/lead/task/](https://business-api.tiktok.com/portal/docs?id=1739052936772610) and [/page/lead/task/download/](https://business-api.tiktok.com/portal/docs?id=1739053563877378), or export the lead from TikTok Leads Center, the first-party CRM platform located within TikTok Ads Manager.|
|lead_event_source| string |Lead event source.<br> You can set this field to the name of your CRM system, such as HubSpot or Salesforce.|
```

Note that another alternative for uploading CRM Events to a CRM Event set is via **file** upload on Events Manager.

## Sample payload

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/event/track/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "event_source": "crm",
  "event_source_id": "{{CRM-Event-Set-ID}}",
  "data": [
    {
      "event_id":"1616318632825_357",
      "event": "Purchase",
      "event_time": 1684574395,
      "user": {
        "email": "848a771458438fc2ec420560d769fb9b9b86851ee338ec56517baabd79d3bb4f",
        "phone": "2f9d2b4df907e5c9a7b3434351b55700167b998a83dc479b825096486ffcf4ea"
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
      },
      "lead": {
        "lead_id": "71247185198338",
        "lead_event_source": "YourCRM"
      }
    }
  ]
}'
(/code)
```

# Verify CRM Events setup
For best practices of verifying Events API setup, see [Verify Events API setup](https://business-api.tiktok.com/portal/docs?id=1835052369704961).

# (Optional) Manage CRM Event sets 
You can manage your CRM Event sets in the following ways:
- Get the CRM Event sets under an advertiser account via [/crm/list/](https://business-api.tiktok.com/portal/docs?id=1780896521680898).
