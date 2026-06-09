---
title: "Parameters"
doc_id: 1771101151059969
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Parameters"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Parameters

# Top-level Parameters
All top-level parameters that are labeled as "Required" are required for all event sources (web, app, offline, or CRM (Customer relationship management) systems).

```xtable
| Field {20%}| Data Type {15%}| Description{65%} |
|---|---|---|
| event_source {Required} | string | This field is used to specify the type of events you are uploading through Events API.  <br>Enum values:  <ul><li> `web`: The events took place on your website and are measured by a Pixel Code.  </li><li>`app`: The events took place on your app and are measured by a TikTok App ID.</li><li>`offline` : The conversions took place in a physical store and are measured by an Offline Event Set ID.</li><li>`crm`: The lead events took place in a CRM system and are measured by a CRM Event Set ID.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: Reporting App Events using Events API 2.0 is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.   |
| event_source_id {Required} | string | An Event Source ID that is used to measure events.  <ul><li>When `event_source` is set to `web`, specify a Pixel Code through this field. <br>To obtain a Pixel Code, refer to [FAQs - Where can I find the pixel_code?](https://ads.tiktok.com/marketing_api/docs?id=1771101001228290#item-link-Q2%3A%20Where%20can%20I%20find%20the%20pixel_code%3F). </li><li>When `event_source` is set to `offline`, specify an Offline Event Set ID through this field. <br>To obtain an Offline Event Set ID, refer to [Setup Guide for Offline](https://ads.tiktok.com/marketing_api/docs?id=1771101027431425).</li><li>When `event_source` is set to `app`, specify a TikTok App ID through this field. <br>To obtain a TikTok App ID, refer to [Setup Guide for App](https://ads.tiktok.com/marketing_api/docs?id=1771101111730178).</li><li>When `event_source` is set to `crm`, specify a CRM Event Set ID through this field.<br>To obtain a CRM Event Set ID, use [/crm/list/](https://business-api.tiktok.com/portal/docs?id=1780896521680898).</li></ul>  |
| data {Required} | object[] | An object array of the events you want to report.  <ul><li> If you are sending a single event, pass one object in the array. </li><li> If you are batching multiple events in a single payload, you can report up to 1000 objects in one request. If a request contains more than 1,000 events, the </strong>entire request will be rejected</strong>.  </li><li> To optimize campaign performance, it's <strong>highly recommended to send the event in real-time (without batching)</strong> as soon as it is seen on the advertiser's server. </li></ul>    |
#| event {Required} | string | <strong>For web, app, offline, and CRM events.</strong><br>Conversion event name.<br><ul><li>For web and app events, it can be either a Standard Event or Custom Event.</li><li>For offline events, it can only be a Standard Event.</li><li>For CRM events, it can only be a Custom Event.</li></ul> To find out the supported values for Standard Events, refer to [Events API 2.0-Supported events](https://ads.tiktok.com/marketing_api/docs?id=1771101186666498). <br>  To report a Custom Web Event or Custom App Event, pass a customized name in this field.<br>Do not use sensitive words in the name of Custom Events. Otherwise, the responsibility for any consequences that may arise will be borne by you. |
#| event_time {Required} | integer | <strong>For web, app, offline, and CRM events.</strong><br>The time when the event occurred, indicated as a Unix timestamp measured in seconds, and in UTC+0 time zone.   |
#| event_id {+Conditional} | string | <strong>For web, offline, and CRM events.</strong><br>Required in any of the following scenarios:<ul><li>You are sending web events (`event_source` = `web`) from both TikTok browser pixel and Events API.</li><li>You are sending CRM events (`event_source` = `crm`) from both Events API and by uploading CSV files on Events Manager.</li></ul>  The ID that is used to identify a unique event. It can be hashed or unhashed.   <br> TikTok uses the `event_source_id`, `event_id` and `event` to deduplicate the same event sent multiple times from a single channel or across multiple channels (for instance browser pixel and Events API).  <br>For more information on event deduplication and how to complete the setup, refer to [Events API 2.0 - Event Deduplication](https://ads.tiktok.com/marketing_api/docs?id=1771100965992450).   |
#| user | object |<strong>For web, app, offline, and CRM events.</strong><br> Information about the customer.   <br>  To view the list of fields included in the `user` object, refer to the "<a href="#item-link-user parameters">`user` parameters</a>" section below.   |
#| properties | object |<strong>For web, app, offline, and CRM events.</strong><br> Information about the product, order and additional information to help improve ad performance.  <br>To view the list of fields included in the `properties` object, refer to the "<a href="#item-link-properties parameters">`properties` parameters</a>" section below.   |
#| page{+Conditional} | object | <strong>Required for web events.</strong><br>Information about the web page.  <br> To view the list of fields included in the `page` object, refer to the "<a href="#item-link-page parameters">`page` parameters</a>" section below.   |
#| app{+Conditional} | object |<strong>Required for app events.</strong><br>Information about the app.  <br> To view the list of fields included in the `app` object, refer to the "<a href="#item-link-app parameters">`app` parameters</a>" section below.<br><p><span style="color:darkred"><b>Note</b></span>: Reporting App Events using Events API 2.0 is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. |
#| ad |  object |<strong>Only for app and web events.</strong><br>Information about the ad. <br>To view the list of fields included in the `ad` object, refer to the "<a href="#item-link-ad parameters">`ad` parameters</a>" section below.|
#| limited_data_use | boolean | <strong>For web and app events.</strong><br>To learn more about the Limited Data Use feature, refer to [Events API 2.0 - Limited Data Use](https://ads.tiktok.com/marketing_api/docs?id=1771101204435970).   |
#| lead {+Conditional} | object | <strong>Required for CRM events. </strong><br>Information about the lead. <br>To view the list of fields included in the lead object, refer to the "<a href="#item-link-lead parameters">`lead` parameters</a>" section below. |
```

## `user` parameters
Events API 2.0 uses customer information, such as TikTok Click IDs, Advanced Matching, and additional signals like IP address and User Agent, to match web visitor events with TikTok users. Once matched, information shared via the Events API can be used to build targeting audiences, inform the optimization algorithm, and better measure campaign results.

To increase the accuracy of targeting and optimization models, it is highly recommended to include multiple types of matching data. You can use Advanced Matching, TikTok Click IDs (`ttclid`), and Cookies to attribute conversions.

```xtable
| Field {15%}| Data Type {13%}|Description{40%} |Examples{32%}|
|---|---|---|
| ttclid | string | <strong>For web events only.</strong><br><br>TikTok Click ID, a data connection parameter appended to a landing page URL whenever a user clicks on an ad on TikTok. <br>The `ttclid` valid period is the same as your CTA window setting in [Attribution Manager](https://ads.tiktok.com/help/article/attribution-manager?redirected=1). <br>For more information on sending TikTok Click IDs, refer to [Events API 2.0 - Send TikTok Click ID (`ttclid`)](https://ads.tiktok.com/marketing_api/docs?id=1771100879787009). ||
| email | string <br> or string[]|<strong> For web, app, offline, and CRM events.</strong><br><br><strong>SHA-256 hashing is required.</strong> <br>The email address, or email addresses of the customer. <br> Follow the instructions below to obtain a valid value: <ul><li><strong>Trim</strong> any leading and trailing spaces of each email before hashing. </li><li><strong>Lowercase</strong> all characters before hashing.</li><li><strong>Hash</strong> the normalized email values using SHA-256.</li></ul><strong>Example</strong>: <ul><li> Original: " ALICE_abc@gmail.com " </li><li> Normalized: "alice_abc@gmail.com" (leading and trailing spaces trimmed and lowercased) </li><li> SHA-256 hashed: "848a771458438fc2ec420560d769fb9b9b86851ee338ec56517baabd79d3bb4f"</li></ul>| `"848a771458438fc2ec420560d769fb9b9b86851ee338ec56517baabd79d3bb4f"`|
| phone | string<br> or string[] |  <strong>For web, app, offline, and CRM events.</strong><br><br><strong>SHA-256 hashing is required. </strong> <br>The phone number or phone numbers of the customer. <br>Follow the instructions below to obtain a valid value: <ul><li><strong>Normalize</strong> the phone number to [E.164 format](https://www.twilio.com/docs/glossary/what-e164) (for example, "+12133734253"). We recommend using [https://github.com/catamphetamine/libphonenumber-js](https://github.com/catamphetamine/libphonenumber-js) for E.164 parsing. <ul><li><strong>Country code</strong> must be included and prefixed with the `+` sign, without any parentheses or leading `0`s. For example, a US phone number should be prefixed with the `+1` country code.</li><li> <strong>No spaces, letters or symbols</strong> except the `+` for the country code are included. </li></ul></li><li><strong>SHA-256 hash</strong> the phone number after normalizing. </li></ul><strong>Example</strong>: <ul><li> Original: "(+1)2133734253"</li><li> Normalized to E.164 format: "+12133734253"</li><li> SHA256 hashed: "9f7ec22d72092cd3c0b58726ed9c2d91b92e51a3f29837508fb2948bb22dd2fd"</li></ul>|`"9f7ec22d72092cd3c0b58726ed9c2d91b92e51a3f29837508fb2948bb22dd2fd"`|
| external_id | string <br> or string[]|  <strong>For web and CRM events only.</strong><br><br><strong>SHA-256 hashing is required. </strong> <br>External ID, a unique identifier on the advertiser's side, such as loyalty membership IDs, user IDs, and external cookie IDs. To learn more about the `external_id` parameter, refer to [Events API 2.0 - Set up External ID](https://ads.tiktok.com/marketing_api/docs?id=1771100952291330). <br><strong>Example</strong>: <ul><li> Original: "user_123"</li><li> SHA-256 hashed: "80fba0ae1c48e3978e43e4efc365e14e12ea0c830ba8ba5b9a2dafc7e3f2ab8b"</li></ul>| `"80fba0ae1c48e3978e43e4efc365e14e12ea0c830ba8ba5b9a2dafc7e3f2ab8b"` |
| ttp | string | <strong>For web events only.</strong><br><br>Cookie ID. <br>If you also use Pixel SDK and have enabled cookies, Pixel SDK automatically saves a unique identifier in the `_ttp` cookie. The value of `_ttp` is used to match website visitor events with TikTok ads. You can extract the value of `_ttp` and attach the value here. <br>  To learn more about the `ttp` parameter, refer to [Events API 2.0 - Send TikTok Cookie (`_ttp`)](https://ads.tiktok.com/marketing_api/docs?id= 1771100936446977). ||
| ip | string | <strong>For web and app events only.</strong><br><br>Non-hashed <strong> public IP</strong> address of the user's device. <br>To increase the probability of matching website visitor events with TikTok ads, we recommend sending both `ip` and `user_agent`. <br><br>Both IPv4 and IPv6 addresses are supported. For IPv6 addresses, both full and compressed formats are acceptable. <br>For example:<ul><li>Full format: <b>2001:0db8:1111:000a:00b0:0000:0000:0200</b></li><li>Compressed format: <b>2001:db8:1111:a:b0::200</b></li></ul>|`"203.0.113.1"`<br><br>`"2001:0db8:85a3:0000:0000:8a2e:0370:7334"`|
| user_agent | string |  <strong>For web and app events only.</strong><br><br>Non-hashed user agent from the user’s device. <br>To increase the probability of matching website visitor events with TikTok ads, we recommend sending both `ip` and `user_agent`. | `"Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604"`|
| first_name | string | <b>For web events only. </b><br><br><b>SHA-256 hashing is required.</b><br><br>The first name of the customer.  <br>The name should be in lowercase without any punctuation. Special characters are allowed.<br><br><b>Example 1: </b>  <ul><li>Original: " Jane "</li><li>Normalized: "jane" (leading and trailing spaces trimmed and lowercased)</li><li>SHA-256 hashed: "81f8f6dde88365f3928796ec7aa53f72820b06db8664f5fe76a7eb13e24546a2"</li></ul> <br><b>Example 2: </b> <ul><li>Original: "민준" </li><li>SHA-256 hashed:   "905d70b1f90f9cbfa6dc23ce36d81c8a385849941c15490f743822b842fee426"</li></ul>  |`"81f8f6dde88365f3928796ec7aa53f72820b06db8664f5fe76a7eb13e24546a2"` |
| last_name| string | <b>For web events only. </b><br><br><b>SHA-256 hashing is required.</b><br><br> The last name of the customer.  <br>The name should be in lowercase without any punctuation. Special characters are allowed.  <br><br><b>Example: </b> See `first_name` examples.| `"81f8f6dde88365f3928796ec7aa53f72820b06db8664f5fe76a7eb13e24546a2"` |
| city | string | <b>For web events only. </b><br><br> City of the customer's address.<br> The city should be in lowercase without any punctuation, special characters, or spaces. | `"seattle"`, `"newyork"` |
| state | string | <b>For web events only. </b><br><br>State or province of the customer's address. <br><br><ul><li>US: Use the [two-letter ANSI (American National Standards Institute) code](https://en.wikipedia.org/wiki/Federal_Information_Processing_Standard_state_code) in lowercase.</li><li>Non-US: Normalize states outside the US in lowercase without any punctuation, special characters, or spaces. </li></ul>  | <ul><li>US: `"ca"` (California)</li><li>Non-US: `"britishcolumbia"` (British Columbia (Canada))</li></ul> |
| country | string | <b>For web events only. </b><br><br> Country of the customer's address.<br><br>Use the lowercase, two-letter [ISO 3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2) codes.  |<ul><li>`"us"`: the United States</li><li>`"sg"`: Singapore</li></ul>  |
| zip_code | string |<b>For web events only. </b><br><br><b>SHA-256 hashing is required.</b><br><br>Zip code or postal code of customer’s address.<br> The code should be in lowercase without any spaces or dashes.<br><br><ul><li>For US zip codes, use only the first 5 digits. </li><li> For non-US zip codes or postal codes, normalize the codes in lowercase and trim the leading and trailing spaces. </li></ul><b>Example 1: </b> <ul><li>Original: "95110" (US zip code) </li><li>SHA-256 hashed:   "383f7fffd99f3b1c3066086bcf991eae7ad543589d12bd792ecb6dc8b5acebc1" </li></ul> <br><b>Example 2: </b><ul><li>Original: "M5V 3L9" (Canadian postal code)</li><li>Normalized: "m5v3l9" </li><li>SHA-256 hashed:   "5c850751d57c5d887fe7807d3da801efe7e8faa1f105ea36cdcd913dd32a41c7" </li></ul> | `"383f7fffd99f3b1c3066086bcf991eae7ad543589d12bd792ecb6dc8b5acebc1"` |
| idfa | string |<strong> The iOS IDFA for app events only. </strong><br> For iOS 14 and above, ensure that IDFA data collection follows [Apple’s policies](https://developer.apple.com/app-store/user-privacy-and-data-use/). Both raw and SHA-256 IDFAs are accepted:<ul><li>For raw IDFAs, all characters must be <strong>UPPERCASE</strong>. </li><li>For SHA-256 IDFAs, all characters must be <strong>lowercase</strong>, and any <strong>leading/trailing spaces</strong> must be <strong>trimmed</strong> before hashing.</li></ul>| <ul><li>Raw IDFA: `"EA7583CD-A667-48BC-B806-42ECB2B48606"`</li><li>SHA-256 IDFA: `"70574fa9c8f498a7b2e5c8712b1126de7b1406fd02fdc591821c5bd33092fd1c"` </li></ul> |
| idfv | string | The iOS IDFV <strong> for app events only</strong>. | |
| gaid | string | The GAID (Google Advertising ID)<strong> for app events only</strong>.<br> Both raw and SHA-256 GAIDs are accepted.<br>For both raw and SHA-256 GAIDs:<ul><li>All characters must be <strong>lowercase</strong>.</li><li><strong>Leading/trailing spaces</strong> must be <strong>trimmed</strong> before hashing</li></ul> | <ul><li>Raw GAID: `"aaaaaa-2222-1111-8787-809e92233ba0"`</li><li> SHA-256 GAID: `"0ebad966881e1eebffe69b3abf624631cf83ec9bf8cb3ce134c7c75c6d8ee7fc"`  </li></ul>   |
| locale | string  |<strong>For web and app events only.</strong><br><br> The BCP 47 language identifier. <br> For reference, refer to [the IETF BCP 47 standardized code](https://www.rfc-editor.org/rfc/bcp/bcp47.txt). |`"en-US"`<br><br>`"zh-CN"`|
| att_status | string | <strong>For app events only.</strong><br><br>Whether the user has authorized your mobile app to access app-related data for measuring the user or the device. This is an iOS only field. <br>Enum values: <ul><li>`AUTHORIZED`: The user has authorized access to app-related data that can be used for measuring the user or the device. </li><li> `DENIED`: The user has not agreed to authorize your mobile app to access app-related data for measuring the user or the device via [Apple's AppTrackingTransparency framework](https://developer.apple.com/documentation/apptrackingtransparency). <br>If this field is set to `DENIED`, TikTok will not use the PII data (for instance, user email address or phone number) in this App Event for user-level matching.</li><li>`NOT_DETERMINED`: The user has not yet received an authorization request to authorize access to app-related data that can be used for measuring the user or the device.</li><li>`RESTRICTED`: The authorization to access app-related data is restricted.</li><li> `NOT_APPLICABLE`: The iOS version is below 14 or the device is running Android. </li></ul>| `"AUTHORIZED"`   |
```

## `properties` parameters
Information about the order and product associated with the event. Providing this information allows Tiktok to help optimize your ads performance.
```xtable
| Field {20%}| Data Type {10%}| Description{35%} |Required for reporting Return on Ad Spend (ROAS) or for Value-based Optimization (VBO){18%} |Required for Video Shopping Ads (VSA){17%} |
|---|---|---|---|---|
|content_ids|string[] |Unique ID or array of multiple IDs of the products or content. <br>Example: `["987", "654"]`. <br>We recommend using `sku_id` or `item_group_id` that matches the `sku_id` or `item_group_id` you set up in the catalog, if available.<br>This field is required for Video Shopping Ads (VSA). | |✅|
| contents {+Conditional} | object[] | Relevant products in an event with product information.   <br> To view the list of fields included in the contents object, refer to the "<a href="#item-link-contents parameters">`contents` parameters</a>" section below.   |    | ✅ |
| content_type{+Conditional} | string | The type of content in the event.   <br>Enum values: `product`, `product_group`.   <ul><li> When the `content_id` in the `contents` parameter is specified as `sku_id`, set this field to `product`.  </li><li> When the `content_id` in the `contents` parameter is specified as `item_group_id`, set this field to `product_group`.  </li></ul> |  |  ✅|
| currency{+Conditional}  | string | <strong>Recommended for revenue related events. </strong> <br><br>     The ISO 4217 currency code.   <br>Examples: `EUR`, `USD`, `JPY`.  <br><strong>List of currencies currently supported</strong>: `AED`, `ARS`, `AUD`, `BDT`, `BHD`, `BIF`, `BOB`, `BRL`, `CAD`, `CHF`, `CLP`, `CNY`, `COP`, `CRC`, `CZK`, `DKK`, `DZD`, `EGP`, `EUR`, `GBP`, `GTQ`, `HKD`, `HNL`, `HUF`, `IDR`, `ILS`, `INR`, `ISK`, `JPY`, `KES`, `KHR`, `KRW`, `KWD`, `KZT`, `MAD`, `MOP`, `MXN`, `MYR`, `NGN`, `NIO`, `NOK`, `NZD`, `OMR`, `PEN`, `PHP`, `PHP`, `PKR`, `PLN`, `PYG`, `QAR`, `RON`, `RUB`, `SAR`, `SEK`, `SGD`, `THB`, `TRY`, `TWD`, `UAH`, `USD`, `VES`, `VND`, `ZAR` .  | ✅ |  |
| value {+Conditional} | float | <strong>Recommended for revenue related events. </strong> <br> <br> Value of the order or items sold. <br> The value should always be formatted as an integer or decimal (for instance, 10.00) regardless of the location, currency, or other factors. It should not contain any currency symbols, special characters, letters, or commas.   <br><p><span style="color:darkred"><b>Note</b></span>: `price` specifies the price for a single item, and `value` specifies the total price of the order. For example, if you have two items each sold for ten dollars, the `price` parameter would be `10.00` and the `value` parameter would be `20.00`.   |  ✅|  |
|num_items|integer| The quantity of items.| | |
|search_string|string|The user-entered string for search.| | |
| description | string | Description of the item or page.   |  |  |
| order_id | string | Order ID.   |  |  |
| shop_id | string | Shop ID.   |  |  |
|customer_type|string|<strong>Only for app and web events.</strong><br><br>A categorical label indicating the customer segment associated with the conversion event.<br><br>Enum values: <ul><li>`new`: A new customer.</li><li>`returning`: A returning customer.</li></ul>| | |
```

### `contents` parameters
```xtable
| Field {20%}| Data Type {10%}| Description{35%} |Required for reporting ROAS or for VBO{18%} |Required for VSA{17%} |
|---|---|---|---|---|
| price | float | The price of the item.  <br><p><span style="color:darkred"><b>Note</b></span>: `price` specifies the price for a single item, and `value` specifies the total price of the order. For example, if you have two items each sold for ten dollars, the `price` parameter would be `10.00` and the `value` parameter would be `20.00`.   <br> Example: `10`, `10.5`.   |  |  |
| content_id {+Conditional} | string | Unique ID of the product or content.  <br>We recommend using `sku_id` or `item_group_id` if you have one.  <br> If you have set up `sku_id` or `item_group_id` in the catalog, the ID should match the `sku_id` or `item_group_id`.   |  | ✅ |
| content_category| string | Category of the page or product.   |  |  | 
| content_name | string | Name of the page or product.   |  |  |
| brand | string | Brand name of the product item.   |  |  |
```
## `page` parameters
```xtable
| Field {20%}| Data Type {15%}| Description{45%} |Example{20%} |
|---|---|---|---|
| url {Required} | string | The browser URL where the event happened, for example, the value of `location.href` in the client side Javascript. <br>It is recommended to use the full URL, including all URLparameters.   | `"http://demo.mywebsite.com/purchase?v=helloworld"` |
| referrer | string | The referrer URL.   <br>For example, [document.referrer ](https://developer.mozilla.org/en-US/docs/Web/API/Document/referrer) in the client side Javascript, or the server side [Referer http header](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referer). <br> It is recommended to use the full URL, including all URL parameters. |`"http://demo.mywebsite.com/purchase"` |
```

## `app` parameters
Use the `app` parameters to share information about the advertiser's app with Events API 2.0. The `app` parameters should only be used when reporting App Events ( `event_source` = `app`).
> <span style="color:DodgerBlue">**Note**</span><br>
> Reporting App Events using Events API 2.0 is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.
```xtable
| Field {20%}| Data Type {15%}| Description{65%} |
|---|---|---|
| app_id {+Conditional} | string | <strong>Required for app events. </strong><br>Mobile App ID. <ul><li> For iOS Apps, use the app ID found in the App Store URL <ul><li> Example: Apple Store URL: `https://apps.apple.com/us/app/angry-birds/id343200656`<ul><li>`id` = `"343200656"`</li></ul></li></ul></li><li> For Android Apps in the Google Play store, use the app ID found in the Google Play store URL. <ul><li> Example: Google Play store URL: `https://play.google.com/store/apps/details?id=com.rovio.angrybird`<ul><li> `id` = `"com.rovio.angrybirds"` </li></ul></li></ul></li><li> For Android Apps in the Google Play store, use the package name.</li></ul>|
| app_name | string | Application name. |
| app_version | string | App version number. <br>Example: `"3.0.2"`. |
```
## `ad` parameters
Use the `ad` parameters to share information about the advertiser's app or website with Events API 2.0. The `ad` parameters should only be used when reporting App Events or Web Events ( `event_source` = `app`  or `web`).
> <span style="color:DodgerBlue">**Note**</span><br>
> Reporting App Events using Events API 2.0 is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.
```xtable
| Field {20%}| Data Type {15%}| Description{65%} |
|---|---|---|
| callback | string | <strong>For app events only.</strong><br><br>Callback information to help attribute events |
| campaign_id | string | <strong>For app events only.</strong><br><br>Campaign ID. |
| ad_id | string | <strong>For app events only.</strong><br><br>Ad group ID. |
| creative_id | string |<strong>For app events only.</strong><br><br> Ad ID. |
| is_retargeting | boolean | <strong>For app  events only.</strong><br><br>Whether the user is a retargeting user. |
| attributed | boolean | <strong>For app events only.</strong><br><br>Whether the event is attributed. |
| attribution_type | string | <strong>For appevents only.</strong><br><br>Attribution type. |
| attribution_provider | string | <strong>For app events only.</strong><br><br>Attribution provider. |
|attribution_share|float| <strong>For app and web events only.</strong><br><br>Attribution share score.<br>A score provided by you per conversion, representing the portion of credit TikTok should receive for the conversion. A greater value indicates a larger credit share for TikTok. <br><br>Examples:<ul><li>0.0: No credit.</li><li>0.5: Partial credit.</li><li>1.0: Full credit.</li></ul>|
```

## `lead` parameters
Use the `lead` parameters to share information about the leads in a CRM system with Events API 2.0. The `lead` parameters should only be used when reporting CRM Events (`event_source` = `crm`).

```xtable
| Field {20%}| Data Type {10%}| Description{70%} |
|---|---|
| lead_id {+Conditional} | string |<strong> Required for CRM events. </strong><br>ID of TikTok leads. <br> Every lead will have its `lead_id` when exported from TikTok. <br>To obtain the ID of a lead, download the lead by using the endpoints [/page/lead/task/](https://business-api.tiktok.com/portal/docs?id=1739052936772610) and [/page/lead/task/download/](https://business-api.tiktok.com/portal/docs?id=1739053563877378), or export the lead from TikTok Leads Center, the first-party CRM platform located within TikTok Ads Manager. |
| lead_event_source | string | Lead event source. <br>You can set this field to the name of your CRM system, such as HubSpot or Salesforce. |
```



