---
title: "Setup guide for Web"
doc_id: 1771100865818625
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for Web / Setup guide for Web"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Setup guide for Web

> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.

# Get Started
This guide walks you through the process of setting up Events API 2.0 for Web.

## Before you begin
- You should have access to TikTok Ads Manager that contains test event functionality and diagnostic tools that would help you during development testing and post integration validation.
- You must have created a pixel in the TikTok Ads Manager under **Tools** > **Events**.
- Once you create a pixel, you can send events via web site through [installing the pixel base code](https://ads.tiktok.com/marketing_api/docs?id=1739585702922241) or send events via Events API. The recommended best practice is to send both that would maximize your event coverage and campaign performance. Furthermore, with the dual channels setup, your campaign performance is protected against event disruption as the event signals will still come in from one channel when the other fails. If you are sending events from both Pixel SDK and Events API, you will need to set up [event deduplication](https://ads.tiktok.com/marketing_api/docs?id=1771100965992450).

If you don't already have a pixel, start with the first step to create a pixel. 
<br>If you already have an existing pixel, skip the first step and start with the second step.

### 1. Create a Pixel
a. In [TikTok Ads Manager](https://ads.tiktok.com/i18n/home), navigate to  **Tools** > **Events** > **Connect data source** > **Web**.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7693acc4769aeb8e015a83519d1b959a" alt="Connect data source" width="60%" style="color:#808080" />

b. Add your website.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4a84b2667f1dde26205c1468bda39706" alt="Add your website" width="60%" style="color:#808080" />

c. Choose data connection method
- Option 1: **Partner integrations**
- Option 2: **Manual setup**

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8f14d1e9ea2ed4dd3f4b87f3ee4a4b1f" alt="Choose data connection method" width="60%" style="color:#808080" />

- If you select "**Partner Integrations**" , follow the on-screen steps to complete the setup process through one of the integration partners.
- If you choose "**Manual Setup**", you'll have the following setup options:
	- **TikTok Pixel** only
	- **Events API** only
	- **TikTok Pixel + Events** API

For optimal performance, we recommend setting up both TikTok Pixel and Events API. You'll need to name and create the Pixel, then follow the on-screen instructions to complete the setup and Pixel creation process.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/738e717b85429f5087cb11a42070cf56" alt="Choose a manual connection method" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5511d5683fad4d0abcef824eefbd31aa" alt="Create pixel" width="60%" style="color:#808080" />



### 2. Generate an access token
To authenticate Events API calls, you need an access token. <br>To learn about how to obtain an access token, refer to [Events API 2.0 > Authentication](https://ads.tiktok.com/marketing_api/docs?id=1771101130925058).

Alternatively, you can **Generate access token** from pixel > **Settings**.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2393b44725766427936ad259da0289f4" alt="Generate access token" width="60%" style="color:#808080" />

## Share Events with Events API
Events are actions a website visitor takes to achieve a business goal, like adding an item to a cart, filling out a form or making a purchase. Prior to integrating with the Events API, we recommend predefining which Events you are interested in sharing with TikTok to achieve your marketing objectives. You can add events and parameters to be sent with your payload.

For details of supported web events and parameters, see [Supported events - Web Standard Events](https://ads.tiktok.com/marketing_api/docs?id=1771101186666498#item-link-Web%20Standard%20Events).

## Share matching data within the events 
Events API for Web uses customer information such as TikTok Click IDs (`ttclid`), Advanced Matching, and additional signals like IP address and User Agent to match web visitor events with TikTok users. Once matched, information shared via the Events API can be used to build targeting audiences, inform the optimization algorithm and better measure campaign results.

# Verify Events setup
After creating the event, you can verify web data connection setup and monitor activity. If you are sending events from both Pixel SDK and Events API, you will need to set up [event deduplication](https://ads.tiktok.com/marketing_api/docs?id=1771100965992450).

For best practices of verifying Events API setup, see [Verify Events API setup](https://ads.tiktok.com/marketing_api/docs?id=1771100984456193).

# Best Practices for Optimal Performance

To achieve optimal performance with the Events API 2.0, we recommend the following settings:
1. Toggle on both first-party cookies and Automatic Advanced Matching from the Pixel settings in [TikTok Ads Manager](https://ads.tiktok.com/i18n/home) by navigating to **Tools** > **Events** > **Data sources**  > select a pixel > **Settings** tab. Refer to the screenshot below:

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/1fcea52ee71deded14d8201ddf12c921" alt="Settings tab" width="60%" style="color:#808080" />

2. Set up [TikTok Click ID](https://ads.tiktok.com/marketing_api/docs?id=1771100879787009) and [cookies](https://ads.tiktok.com/marketing_api/docs?id=1771100936446977).
3. Send Advanced Matching Data. To learn more about Advanced Matching, refer to [About Advanced Matching for Web](https://ads.tiktok.com/help/article/advanced-matching-web?redirected=2).
As a best practice, we encourage you to send both email and phone numbers for all visitor events. Additionally, we recommend capturing visitors' emails and phone numbers early in the funnel for best results. This increases the probability of matching customers with people on TikTok. Finally, you can pass an External ID, such as a membership ID, along with the email and phone numbers for the best matching results. To learn more about External ID, refer to [Set up External ID](https://ads.tiktok.com/marketing_api/docs?id=1771100952291330).
4. Use the recommended event payload format. 

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/event/track/

**Method** POST

**Header**

```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
|Access-Token {Required}|string|Authorized access token. <br>To obtain an access token, refer to [Events API 2.0 - Authentication](https://ads.tiktok.com/marketing_api/docs?id=1771101130925058).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```
## Parameters for reporting Web Events
For each parameter value format, refer to the lists below.

> <span style="color:DodgerBlue">**Note**</span><br>
> To generate sample code in cURL, Python, or Node.js format for reporting Web Events via the Events API 2.0, use [the Payload Helper for Web](https://business-api.tiktok.com/portal/docs?id=1807346079965186).


### Top-level parameters
```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
| event_source {Required} | string | This field is used to specify the type of events you are uploading through Events API.  <br>The allowed enum value:  <ul><li> `web`: The events took place on your website and are measured by a Pixel Code.  </li></ul>   |
| event_source_id {Required} | string | An Event Source ID that is used to measure events.  <br>When `event_source` is set to `web`, specify a Pixel Code through this field. <br>To obtain a Pixel Code, refer to [FAQs - Where can I find the Pixel Code](https://business-api.tiktok.com/portal/docs?id=1771101001228290#item-link-Q2:%20Where%20can%20I%20find%20the%20Pixel%20Code?).   |
| data {Required} | object[] | An object array of the events you want to report.  <ul><li> If you are sending a single event, pass one object in the array. </li><li> If you are batching multiple events in a single payload, you can report up to 1000 objects in one request. If a request contains more than 1,000 events, the </strong>entire request will be rejected</strong>.  </li><li> To optimize campaign performance, it's <strong>highly recommended to send the event in real-time (without batching)</strong> as soon as it is seen on the advertiser's server. </li></ul>    |
#| event {Required} | string | Conversion event name. It can be either a Standard Event or a Custom Event.   <br>  <br> To find out the supported values for Standard Events, refer to [Events API 2.0-Supported events](https://ads.tiktok.com/marketing_api/docs?id=1771101186666498).    <br> To report a Custom Event, pass a customized name in this field.|
#| event_time {Required} | integer | The time when the event occurred, indicated as a Unix timestamp measured in seconds, and in UTC+0 time zone.   |
#| event_id {+Conditional} | string | Required if you are sending web events (`event_source` = `web`) from both TikTok browser pixel and Events API.  <br> The ID that is used to identify a unique event. It can be hashed or unhashed.   <br> TikTok uses the `event_source_id`, `event_id` and `event` to deduplicate the same event sent multiple times from a single channel or across multiple channels (for instance browser pixel and Events API).  <br>For more information on event deduplication and how to complete the setup, refer to [Events API 2.0 - Event Deduplication](https://ads.tiktok.com/marketing_api/docs?id=1771100965992450).   |
#| user {Required} | object | Information about the customer.   <br>  To view the list of fields included in the `user` object, refer to the "<a href="#item-link-user parameters">`user` parameters</a>" section below.   |
#| properties | object | Information about the product, order and additional information to help improve ad performance.  <br>To view the list of fields included in the `properties` object, refer to the "<a href="#item-link-properties parameters">`properties` parameters</a>" section below.   |
#| page {Required} | object | Information about the web page.  <br> To view the list of fields included in the `page` object, refer to the "<a href="#item-link-page parameters">`page` parameters</a>" section below.   |
#| ad |  object |Information about the ad. <br>To view the list of fields included in the `ad` object, refer to the "<a href="#item-link-ad parameters">`ad` parameters</a>" section below.|
#| limited_data_use | boolean | To learn more about the Limited Data Use feature, refer to [Events API 2.0 - Limited Data Use](https://ads.tiktok.com/marketing_api/docs?id=1771101204435970).   |
```

#### `user` parameters

```xtable
| Field {25%}| Data Type {10%}| Recommended ?{13%} |Description{52%} |
|---|---|---|
| ttclid | string | ✅ | TikTok Click ID, a data connection parameter appended to a landing page URL whenever a user clicks on an ad on TikTok. <br>The `ttclid` valid period is the same as your CTA window setting in [Attribution Manager](https://ads.tiktok.com/help/article/attribution-manager?redirected=1). <br>For more information on sending TikTok Click IDs, refer to [Events API 2.0 - Send TikTok Click ID (`ttclid`)](https://ads.tiktok.com/marketing_api/docs?id=1771100879787009). |
| email | string <br> or string[]| ✅ | <strong>SHA-256 hashing is required.</strong> <br>The email address, or email addresses of the customer. <br> Follow the instructions below to obtain a valid value: <ul><li><strong>Trim</strong> any leading and trailing spaces of each email before hashing. </li><li><strong>Lowercase</strong> all characters before hashing.</li><li><strong>Hash</strong> the normalized email values using SHA-256.</li></ul><strong>Example</strong>: <ul><li> Original: " ALICE_abc@gmail.com " </li><li> Normalized: "alice_abc@gmail.com" (leading and trailing spaces trimmed and lowercased) </li><li> SHA-256 hashed: "848a771458438fc2ec420560d769fb9b9b86851ee338ec56517baabd79d3bb4f"</li></ul>|
| phone | string<br> or string[] | ✅ | <strong>SHA-256 hashing is required. </strong> <br>The phone number or phone numbers of the customer. <br>Follow the instructions below to obtain a valid value: <ul><li><strong>Normalize</strong> the phone number to [E.164 format](https://www.twilio.com/docs/glossary/what-e164) (for example, "+12133734253"). We recommend using [https://github.com/catamphetamine/libphonenumber-js](https://github.com/catamphetamine/libphonenumber-js) for E.164 parsing. <ul><li><strong>Country code</strong> must be included and prefixed with the `+` sign, without any parentheses or leading `0`s. For example, a US phone number should be prefixed with the `+1` country code.</li><li> <strong>No spaces, letters or symbols</strong> except the `+` for the country code are included. </li></ul></li><li><strong>SHA-256 hash</strong> the phone number after normalizing. </li></ul><strong>Example</strong>: <ul><li> Original: "(+1)2133734253"</li><li> Normalized to E.164 format: "+12133734253"</li><li> SHA256 hashed: "9f7ec22d72092cd3c0b58726ed9c2d91b92e51a3f29837508fb2948bb22dd2fd"</li></ul>|
| external_id | string <br> or string[]| ✅ | <strong>SHA-256 hashing is required. </strong> <br>External ID, a unique identifier on the advertiser's side, such as loyalty membership IDs, user IDs, and external cookie IDs. To learn more about the `external_id` parameter, refer to [Events API 2.0 - Set up External ID](https://ads.tiktok.com/marketing_api/docs?id=1771100952291330). <br><strong>Example</strong>: <ul><li> Original: "user_123"</li><li> SHA-256 hashed: "80fba0ae1c48e3978e43e4efc365e14e12ea0c830ba8ba5b9a2dafc7e3f2ab8b"</li></ul>|
| ttp | string | ✅ | Cookie ID. <br>If you also use Pixel SDK and have enabled cookies, Pixel SDK automatically saves a unique identifier in the `_ttp` cookie. The value of `_ttp` is used to match website visitor events with TikTok ads. You can extract the value of `_ttp` and attach the value here. <br>  To learn more about the `ttp` parameter, refer to [Events API 2.0 - Send TikTok Cookie (`_ttp`)](https://ads.tiktok.com/marketing_api/docs?id= 1771100936446977). |
| ip | string | ✅ | Non-hashed <strong> public IP</strong> address of the user's device. <br>To increase the probability of matching website visitor events with TikTok ads, we recommend sending both `ip` and `user_agent`. <br><br>Both IPv4 and IPv6 addresses are supported. For IPv6 addresses, both full and compressed formats are acceptable. <br>For example:<ul><li>Full format: <b>2001:0db8:1111:000a:00b0:0000:0000:0200</b></li><li>Compressed format: <b>2001:db8:1111:a:b0::200</b></li></ul> |
| user_agent | string | ✅ | Non-hashed user agent from the user’s device. <br>To increase the probability of matching website visitor events with TikTok ads, we recommend sending both `ip` and `user_agent`. |
| locale | string  | |The BCP 47 language identifier. <br> For reference, refer to [the IETF BCP 47 standardized code](https://www.rfc-editor.org/rfc/bcp/bcp47.txt). |
```
#### `properties` parameters
```xtable
| Field {25%}| Data Type {10%}| Description{35%} |Required for reporting Return on Ad Spend (ROAS) or for Value-based Optimization (VBO){15%} |Required for Video Shopping Ads (VSA){15%} |
|---|---|---|---|---|
|content_ids| string[] | Unique ID or array of multiple IDs of the products or content. <br>Example: `["987", "654"]`. <br>We recommend using `sku_id` or `item_group_id` that matches the `sku_id` or `item_group_id` you set up in the catalog, if available.<br>This field is required for Video Shopping Ads (VSA). | |✅|
| contents | object[] | Relevant products in an event with product information.   <br> To view the list of fields included in the contents object, refer to the "<a href="#item-link-contents parameters">`contents` parameters</a>" section below.   |    | ✅ |
| content_type| string | The type of content in the event.   <br>Enum values: `product`, `product_group`.   <ul><li> When the `content_id` in the `contents` parameter is specified as `sku_id`, set this field to `product`.  </li><li> When the `content_id` in the `contents` parameter is specified as `item_group_id`, set this field to `product_group`.  </li></ul> |  |  ✅|
| currency | string | <strong>Recommended for revenue related events. </strong> <br><br>     The ISO 4217 currency code.   <br>Examples: `EUR`, `USD`, `JPY`.  <br><strong>List of currencies currently supported</strong>: `AED`, `ARS`, `AUD`, `BDT`, `BHD`, `BIF`, `BOB`, `BRL`, `CAD`, `CHF`, `CLP`, `CNY`, `COP`, `CRC`, `CZK`, `DKK`, `DZD`, `EGP`, `EUR`, `GBP`, `GTQ`, `HKD`, `HNL`, `HUF`, `IDR`, `ILS`, `INR`, `ISK`, `JPY`, `KES`, `KHR`, `KRW`, `KWD`, `KZT`, `MAD`, `MOP`, `MXN`, `MYR`, `NGN`, `NIO`, `NOK`, `NZD`, `OMR`, `PEN`, `PHP`, `PHP`, `PKR`, `PLN`, `PYG`, `QAR`, `RON`, `RUB`, `SAR`, `SEK`, `SGD`, `THB`, `TRY`, `TWD`, `UAH`, `USD`, `VES`, `VND`, `ZAR` .  | ✅ |  |
| value | float | <strong>Recommended for revenue related events. </strong> <br> <br> Value of the order or items sold. <br> The value should always be formatted as an integer or decimal (for instance, 10.00) regardless of the location, currency, or other factors. It should not contain any currency symbols, special characters, letters, or commas.   <br><p><span style="color:darkred"><b>Note</b></span>: `price` specifies the price for a single item, and `value` specifies the total price of the order. For example, if you have two items each sold for ten dollars, the `price` parameter would be `10.00` and the `value` parameter would be `20.00`.   |  ✅|  |
|num_items|integer| The quantity of items.| | |
|search_string|string|The user-entered string for search.| | |
| description | string | Description of the item or page.   |  |  |
| order_id | string | Order ID.   |  |  |
| shop_id | string | Shop ID.   |  |  |
| customer_type |string|A categorical label indicating the customer segment associated with the conversion event.<br><br>Enum values: <ul><li>`new`: A new customer.</li><li>`returning`: A returning customer.</li></ul>| | |
```

##### `contents` parameters
```xtable
| Field {25%}| Data Type {10%}| Description{35%} |Required for reporting Return on Ad Spend (ROAS) or for Value-based Optimization (VBO){15%} |Required for Video Shopping Ads (VSA){15%} |
|---|---|---|---|---|
| price | float | The price of the item.  <br><p><span style="color:darkred"><b>Note</b></span>: `price` specifies the price for a single item, and `value` specifies the total price of the order. For example, if you have two items each sold for ten dollars, the `price` parameter would be `10.00` and the `value` parameter would be `20.00`.   <br> Example: `10`, `10.5`.   |  |  |
| content_id | string | Unique ID of the product or content.  <br>We recommend using `sku_id` or `item_group_id` if you have one.  <br> If you have set up `sku_id` or `item_group_id` in the catalog, the ID should match the `sku_id` or `item_group_id`.   |  | ✅ |
| content_category| string | Category of the page or product.   |  |  |
| content_name | string | Name of the page or product.   |  |  |
| brand | string | Brand name of the product item.   |  |  |
```

#### `page` parameters
```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
| url {Required} | string | The browser URL where the event happened, for example, the value of `location.href` in the client side Javascript. <br>It is recommended to use the full URL, including all URLparameters.   |
| referrer | string | The referrer URL.   <br>For example, [document.referrer ](https://developer.mozilla.org/en-US/docs/Web/API/Document/referrer) in the client side Javascript, or the server side [Referer http header](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referer). <br> It is recommended to use the full URL, including all URL parameters. |
```

#### `ad` parameters
Use the `ad` parameters to share information about the advertiser's website with Events API 2.0. 

```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
|attribution_share|float| Attribution share score.<br>A score provided by you per conversion, representing the portion of credit TikTok should receive for the conversion. A greater value indicates a larger credit share for TikTok. <br><br>Examples:<ul><li>0.0: No credit.</li><li>0.5: Partial credit.</li><li>1.0: Full credit.</li></ul>|
```

## Sample payload

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/event/track/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "event_source": "web",
  "event_source_id": "{{PIXEL-CODE}}",
  "data": [
    {
      "event": "Purchase",
      "event_time": 1687758765,
      "event_id": "12345",
      "user": {
        "external_id": "user_12345678",
        "phone": "0c7e6a405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b",
        "email": "123456405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b",
        "ip": "123.456.789.1",
        "user_agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.1 Safari/605.1.15",
        "locale": "en_US"
      },
      "properties": {
        "currency": "USD",
        "value": 200.0,
        "content_type": "product",
        "description": "some product decription",
        "contents": [
          {
            "price": 100.0,
            "quantity": 2,
            "content_id": "12345",
            "content_name": "Fancy-AirMax2.0 Black",
            "content_category": "Shoes - Running Shoes",
            "brand": "Fancy Sneakers"
          }
        ]
      }
    }
  ]
}'
(/code)
```


