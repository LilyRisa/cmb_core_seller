---
title: "Set up events manually"
doc_id: 1799004110681154
path: "Marketing API / Ad Measurement / Guides / Google Tag Manager (GTM) Integration / Set up events manually"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Set up events manually

To set up TikTok events manually without using the data layer, you can use the template parameter override. The instructions provided in this document are applicable to both the TikTok Pixel template and Events API template.

> <span style="color:DodgerBlue">**Note**</span><br>
> However, it is recommended that you switch to using the GTM (Google Tag Manager) data layer because it offers a more reliable and scalable approach for event setup, especially if your website already uses GTM data layer for sending events to other advertising platforms such as Google Analytics. For more information, see [Set up events with data layer](https://business-api.tiktok.com/portal/docs?id=1799004097478658).

## Event name configuration
Select a TikTok **Event Name** that you want the tag to send. The event name should depend on the trigger that you decide to associate with the tag. You can either use variables as the event or choose from a list of TikTok standard web event names (for example, `ViewContent` and `AddToCart`). Learn more about TikTok [supported Pixel events](https://ads.tiktok.com/marketing_api/docs?id=1739585696931842).

 <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/eec69eb6518d4caf0ac57bf2c29610bd" width="60%" style="color:#808080" />

## User data
To manually configure user information for [Advanced Matching](https://business-api.tiktok.com/portal/docs?id=1739585700402178), go to **Parameter Override** > **Advanced Matching Configurations** in TikTok Pixel and Events API template tags. You can choose to send user data as either SHA256 strings or plain strings (the template will SHA256 hash plain strings before sending them to TikTok).
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c1e25f7c111c800bd390c097e2d15e71" width="60%" style="color:#808080" />

### SHA256 AM parameters format

```xtable
| Field {20%}| Format requirement {60%}|
|---|---|
| SHA256 Hashed Email | <ul><li><b>Trim</b> any leading and trailing spaces of each email before hashing</li><li><b>Lowercase</b> all characters before hashing.</li><li><b>Hash</b> the normalized email values using SHA-256.</li></ul>|
| SHA256 Hashed Phone | <ul><li><b>Normalize</b> the phone number to [E.164 format](https://www.twilio.com/docs/glossary/what-e164), `+&#123;country code&#125;&#123;phone number&#125;`. For example, "+12133734253". <ul><li><b>Country code</b> must be included and prefixed with the `+` sign, without any parentheses or leading `0`s. For example, a US phone number should be prefixed with the `+1` country code.</li><li><b>Exclude spaces, letters or symbols</b> except the `+` for the country code. </li></ul></li><li><b>Hash</b> the normalized phone number values using SHA-256.</li></ul>|
| SHA256 Hashed External ID | <b>Trim</b> any leading and trailing spaces before hashing using SHA-256. |
```

### Plain AM parameters format
```xtable
| Field {20%}| Format requirement {60%}|
|---|---|
| Email | <ul><li><b>Trim</b> any leading and trailing spaces of each email. </li><li><b>Lowercase</b> all characters.</li></ul>|
| Phone | <ul><li><b>Normalize</b> the phone number to [E.164 format](https://www.twilio.com/docs/glossary/what-e164), `+&#123;country code&#125;&#123;phone number&#125;`. For example, "+12133734253". <ul><li><b>Country code</b> must be included and prefixed with the `+` sign, without any parentheses or leading `0`s. For example, a US phone number should be prefixed with the `+1` country code.</li><li><b>Exclude spaces, letters or symbols</b> except the `+` for the country code. </li></ul></li></ul>
| External ID | <b>Trim</b> any leading and trailing spaces. |
```

## Product data
The following section contains product information, which is crucial if you are advertising a product sold on a website. Sending complete product information enables measurement and optimization through solutions such as TikTok's Video Shopping Ads (VSA) for commerce, Value-based Optimization (VBO), or ROAS measurement.

```xtable
| Field {15%}|  Description{45%} |Required for reporting Return on Ad Spend (ROAS) or for Value-based Optimization (VBO){20%} |Required for Video Shopping Ads (VSA){20%} |
|---|---|---|---|
| currency | <strong>Recommended for revenue related events. </strong> <br><br>     The ISO 4217 currency code.   <br>Examples: `EUR`, `USD`, `JPY`.  <br><strong>List of currencies currently supported</strong>: `AED`, `ARS`, `AUD`, `BDT`, `BHD`, `BIF`, `BOB`, `BRL`, `CAD`, `CHF`, `CLP`, `CNY`, `COP`, `CRC`, `CZK`, `DKK`, `DZD`, `EGP`, `EUR`, `GBP`, `GTQ`, `HKD`, `HNL`, `HUF`, `IDR`, `ILS`, `INR`, `ISK`, `JPY`, `KES`, `KHR`, `KRW`, `KWD`, `KZT`, `MAD`, `MOP`, `MXN`, `MYR`, `NGN`, `NIO`, `NOK`, `NZD`, `OMR`, `PEN`, `PHP`, `PHP`, `PKR`, `PLN`, `PYG`, `QAR`, `RON`, `RUB`, `SAR`, `SEK`, `SGD`, `THB`, `TRY`, `TWD`, `UAH`, `USD`, `VES`, `VND`, `ZAR` .  | ✅ |  |
| value |  <strong>Recommended for revenue related events. </strong> <br> <br> Value of the order or items sold. <br> The value should always be formatted as an integer or decimal (for instance, 10.00) regardless of the location, currency, or other factors. It should not contain any currency symbols, special characters, letters, or commas.   <br><p><span style="color:darkred"><b>Note</b></span>: `price` specifies the price for a single item, and `value` specifies the total price of the order. For example, if you have two items each sold for ten dollars, the `price` parameter would be `10.00` and the `value` parameter would be `20.00`.   |  ✅|  |
| query | The text string that was input by a user.   <br>For instance, if a user searches for a product on your website, you can forward the keyword being searched. If a user enters a coupon code at checkout, you can forward the code.   |  |  |
| description |  Description of the item or page.   |  |  |
| contents |  Array of content objects. <br> To view the list of fields included in the `contents` object, refer to the "<a href="#item-link-contents parameters">`contents` parameters</a>" section below.    |  |  |
```

### `contents` parameters
```xtable
| Field {15%}| Description{45%} |Required for reporting ROAS or for VBO{20%} |Required for VSA{20%} |
|---|---|---|---|
| price | The price of the item.  <br><p><span style="color:darkred"><b>Note</b></span>: `price` specifies the price for a single item, and `value` specifies the total price of the order. For example, if you have two items each sold for ten dollars, the `price` parameter would be `10.00` and the `value` parameter would be `20.00`.   <br> Example: `10`, `10.5`.   |  |  |
| quantity |  The number of items.   |  |  |
| content_id |  Unique ID of the product or content.  <br>We recommend using `sku_id` or `item_group_id` if you have one.  <br> If you have set up `sku_id` or `item_group_id` in the catalog, the ID should match the `sku_id` or `item_group_id`.   |  | ✅ |
| content_type| The type of content in the event.   <br>Enum values: `product`, `product_group`.   <ul><li> When the `content_id` in the `contents` parameter is specified as `sku_id`, set this field to `product`.  </li><li> When the `content_id` in the `contents` parameter is specified as `item_group_id`, set this field to `product_group`.  </li></ul> |  |  ✅|
| content_category|  Category of the page or product.   |  |  |
| content_name | Name of the page or product.   |  |  |
| brand | Brand name of the product item.   |  |  |
```


To manually configure product data to send to TikTok, select **Use Custom Data** under the **Parameter Override** > **Object Properties** section in your configuration, and use your Variables to fill out the rest of the form.

 <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/56db99256bd37be45093e0a85300eedd" width="60%" style="color:#808080" />

Custom Data Settings:
- **Single Content**: Associate the event with one product.
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d54e7b6de3793a6cf8c4f0f10a395201" width="60%" style="color:#808080" />
- **Multiple Contents**: Associate the event with multiple products. The contents field must be filled with a JSON-encoded string of products.
Example: `[{ "price": 100.0, "quantity": 2, "content_id": "12345", "content_type": "product", "content_name": "Fancy-MaxSpeed2.0 Black", "content_category": "Shoes - Running Shoes", "brand": "Fancy Sneakers" }, { "price": 40.0, "quantity": 1, "content_id": "45678", "content_type": "product", "content_name": "Fancy-MaxSpeed2.0 White", "content_category": "Shoes - Running Shoes", "brand": "Fancy Sneakers" }]`. <br>[Learn more about the valid schema of the JSON object](https://ads.tiktok.com/marketing_api/docs?id=1740858531237890).
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4cb995fe82a3ee66be6365e796523052" width="60%" style="color:#808080" />

- **Order Info Only**: Only post back `value` and `currency` information to TikTok.
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/41271f1a42f69a80793102386d88de94" width="60%" style="color:#808080" />

## Verify events setup
Once you have configured TikTok events and parameters in the Pixel or Events API templates, you can verify the setup by following [Verify GTM setup](https://business-api.tiktok.com/portal/docs?id=1799004129683458).

## Support
If you encounter any issues that require support, file an issue in the respective GitHub projects:
- [TikTok Pixel template GitHub](https://github.com/tiktok/gtm-template-pixel/issues)
- [TikTok Events API template GitHub](https://github.com/tiktok/gtm-template-eapi/issues)
