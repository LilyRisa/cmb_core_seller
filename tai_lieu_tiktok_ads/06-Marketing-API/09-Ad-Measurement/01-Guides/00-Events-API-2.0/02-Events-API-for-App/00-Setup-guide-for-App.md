---
title: "Setup guide for App"
doc_id: 1771101111730178
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for App / Setup guide for App"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Setup guide for App

> <span style="color:DodgerBlue">**Note**</span><br>
> Reporting App Events using Events API 2.0 is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.

This guide walks you through the process of setting up Events API 2.0 for App.


# Create an app and generate your TikTok App ID
A user in an Admin or Operator role needs to perform these steps in your Ads Manager account.

## Access Events Manager
To get started with app creation, navigate to **Tools ➤ Events** in your TikTok Ads Manager account.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2a1ff1af7589e56f2ab2dddcb98fa030" alt="Status Center page" width="60%" style="color:#808080" />

## Create your app

- If you do not already have a data connection set up, select <b>"Get started"</b>. 
- If you already have other data connections set up, select <b>"Connect data source"</b>.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/f5a206c7f8e849f95d757e4b36221279" alt="select Get started" width="60%" style="color:#808080" />

Select <b>"App"</b> and proceed.


## Provide your app details

Fill in your app download URL and proceed. The app icon, app name, and app ID will be populated automatically.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/990b6ae01e01cb32780795515c032fc2" alt="Fill in your app download URL and proceed" width="80%" style="color:#808080" />

Select <b>"Use TikTok Events API"</b> and proceed.</td>

The app is now created, and you will see some recommended events for your app. Recommendations are provided for specific verticals. Once you have noted these down, click **Finish** to be taken to your app detail page.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8b668541888bc98c6cb5229eb8e6030a" alt="The app is now created, and you will see some recommended events for your app." width="80%" style="color:#808080" />


## Generate your TikTok App ID

Note down your **App ID**, and copy your **TikTok App ID**. Both are required for integration.

Your **App ID** will be in either reverse DNS format (Android), or short numerical string (iOS) typically present in app store or play store URLs. Your **TikTok App ID** is a longer numerical string, and specific to your ad account. This will be used as the `event_source_id` for sending app events via Events API 2.0.


## Generate your access token
To authenticate Events API calls, you need an access token. You can generate one in the "**Settings**" tab. To learn more about how to generate an access token, refer to [Events API 2.0 - Authentication](https://business-api.tiktok.com/portal/docs?id=1771101130925058).

# Report an App Event
To report an event  (such as `LaunchAPP` or `Purchase`) from a mobile app (Android or iOS) to TikTok, call the Events API 2.0 endpoint [/event/track/](https://ads.tiktok.com/marketing_api/docs?id=1771101303285761).

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/event/track/

**Method** POST

**Header**

```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
|Access-Token {Required}|string|Authorized access token. <br>To obtain an access token, refer to [Events API 2.0 - Authentication](https://ads.tiktok.com/marketing_api/docs?id=1771101130925058).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

## Parameters for reporting App Events

> <span style="color:DodgerBlue">**Note**</span><br>
> To generate sample code in cURL, Python, or Node.js format for reporting App Events via the Events API 2.0, use [the Payload Helper for App](https://business-api.tiktok.com/portal/docs?id=1819581869993025).

### Top-level parameters
```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
| event_source {Required} | string | This field is used to specify the type of events you are uploading through Events API.  <br>The allowed enum value:  <ul><li> `app`: The events took place on your app and are measured by a TikTok App ID.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Reporting App Events using Events API 2.0 is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.    |
| event_source_id {Required} | string | An Event Source ID that is used to measure events.  <br>When `event_source` is set to `app`, specify a TikTok App ID through this field. <br>To obtain a TikTok App ID, refer to the <a href="#item-link-Create an App in Events Manager and obtain an access token">Create an App in Events Manager and obtain an access token</a> section above.</li>   |
| data {Required} | object[] | An object array of the events you want to report.  <ul><li> If you are sending a single event, pass one object in the array. </li><li> If you are batching multiple events in a single payload, you can report up to 1000 objects in one request. If a request contains more than 1,000 events, the </strong>entire request will be rejected</strong>.  </li><li> To optimize campaign performance, it's <strong>highly recommended to send the event in real-time (without batching)</strong> as soon as it is seen on the advertiser's server. </li></ul>    |
#| event {Required} | string | Conversion event name. It can be either a Standard Event or a Custom Event.   <br>  <br> To find out the supported values for Standard Events, refer to [Events API 2.0-Supported events](https://ads.tiktok.com/marketing_api/docs?id=1771101186666498).    <br> To report a Custom Event, pass a customized name in this field.|
#| event_time {Required} | integer | The time when the event occurred, indicated as a Unix timestamp measured in seconds, and in UTC+0 time zone.   |
#| user | object | Information about the customer.   <br>  To view the list of fields included in the `user` object, refer to the "<a href="#item-link-user parameters">`user` parameters</a>" section below.   |
#| properties | object | Information about the product, order and additional information to help improve ad performance.  <br>To view the list of fields included in the `properties` object, refer to the "<a href="#item-link-properties parameters">`properties` parameters</a>" section below.   |
#| app{Required}  | object |Information about the app.  <br> To view the list of fields included in the `app` object, refer to the "<a href="#item-link-app parameters">`app` parameters</a>" section below.<br><p><span style="color:darkred"><b>Note</b></span>: Reporting App Events using Events API 2.0 is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. |
#| ad |  object |Information about the ad. <br>To view the list of fields included in the `ad` object, refer to the "<a href="#item-link-ad parameters">`ad` parameters</a>" section below.|
#| limited_data_use | boolean | To learn more about the Limited Data Use feature, refer to [Events API 2.0 - Limited Data Use](https://ads.tiktok.com/marketing_api/docs?id=1771101204435970).   |
```

#### `user` parameters

```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
| email | string <br> or string[]| <strong>SHA-256 hashing is required.</strong> <br>The email address, or email addresses of the customer. <br> Follow the instructions below to obtain a valid value: <ul><li><strong>Trim</strong> any leading and trailing spaces of each email before hashing. </li><li><strong>Lowercase</strong> all characters before hashing.</li><li><strong>Hash</strong> the normalized email values using SHA-256.</li></ul><strong>Example</strong>: <ul><li> Original: " ALICE_abc@gmail.com " </li><li> Normalized: "alice_abc@gmail.com" (leading and trailing spaces trimmed and lowercased) </li><li> SHA-256 hashed: "848a771458438fc2ec420560d769fb9b9b86851ee338ec56517baabd79d3bb4f"</li></ul>|
| phone | string<br> or string[] |  <strong>SHA-256 hashing is required. </strong> <br>The phone number or phone numbers of the customer. <br>Follow the instructions below to obtain a valid value: <ul><li><strong>Normalize</strong> the phone number to [E.164 format](https://www.twilio.com/docs/glossary/what-e164) (for example, "+12133734253"). We recommend using [https://github.com/catamphetamine/libphonenumber-js](https://github.com/catamphetamine/libphonenumber-js) for E.164 parsing. <ul><li><strong>Country code</strong> must be included and prefixed with the `+` sign, without any parentheses or leading `0`s. For example, a US phone number should be prefixed with the `+1` country code.</li><li> <strong>No spaces, letters or symbols</strong> except the `+` for the country code are included. </li></ul></li><li><strong>SHA-256 hash</strong> the phone number after normalizing. </li></ul><strong>Example</strong>: <ul><li> Original: "(+1)2133734253"</li><li> Normalized to E.164 format: "+12133734253"</li><li> SHA256 hashed: "9f7ec22d72092cd3c0b58726ed9c2d91b92e51a3f29837508fb2948bb22dd2fd"</li></ul>|
| idfa | string | The iOS IDFA.<br> For iOS 14 and above, ensure that IDFA data collection follows [Apple’s policies](https://developer.apple.com/app-store/user-privacy-and-data-use/). Both raw and SHA-256 IDFAs are accepted:<ul><li>For raw IDFAs, all characters must be <strong>UPPERCASE</strong>. </li><li>For SHA-256 IDFAs, all characters must be <strong>lowercase</strong>, and any <strong>leading/trailing spaces</strong> must be <strong>trimmed</strong> before hashing.</li></ul>|
| idfv | string | The iOS IDFV. |
| gaid | string | The GAID (Google Advertising ID).<br> Both raw and SHA-256 GAIDs are accepted.<br>For both raw and SHA-256 GAIDs:<ul><li>All characters must be <strong>lowercase</strong>.</li><li><strong>Leading/trailing spaces</strong> must be <strong>trimmed</strong> before hashing</li></ul> |
| ip | string | Non-hashed <strong> public IP</strong> address of the user's device. <br>To increase the probability of matching website visitor events with TikTok ads, we recommend sending both `ip` and `user_agent`. |
| user_agent | string |  Non-hashed user agent from the user’s device. <br>To increase the probability of matching website visitor events with TikTok ads, we recommend sending both `ip` and `user_agent`. |
| locale | string  | The BCP 47 language identifier. <br> For reference, refer to [the IETF BCP 47 standardized code](https://www.rfc-editor.org/rfc/bcp/bcp47.txt). |
| att_status | string | Whether the user has authorized your mobile app to access app-related data for measuring the user or the device. This is an iOS only field. <br>Enum values: <ul><li>`AUTHORIZED`: The user has authorized access to app-related data that can be used for measuring the user or the device. </li><li> `DENIED`: The user has not agreed to authorize your mobile app to access app-related data for measuring the user or the device via [Apple's AppTrackingTransparency framework](https://developer.apple.com/documentation/apptrackingtransparency). <br>If this field is set to `DENIED`, TikTok will not use the PII data (for instance, user email address or phone number) in this App Event for user-level matching.</li><li>`NOT_DETERMINED`: The user has not yet received an authorization request to authorize access to app-related data that can be used for measuring the user or the device.</li><li>`RESTRICTED`: The authorization to access app-related data is restricted.</li><li> `NOT_APPLICABLE`: The iOS version is below 14 or the device is running Android. </li></ul>|
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
| customer_type | string |A categorical label indicating the customer segment associated with the conversion event.<br><br>Enum values: <ul><li>`new`: A new customer.</li><li>`returning`: A returning customer.</li></ul>|
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

#### `app` parameters
```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
| app_id {Required} | string | </strong><br>Mobile App ID. <ul><li> For iOS Apps, use the app ID found in the App Store URL <ul><li> Example: Apple Store URL: `https://apps.apple.com/us/app/angry-birds/id343200656`<ul><li>`id` = `"343200656"`</li></ul></li></ul></li><li> For Android Apps in the Google Play store, use the app ID found in the Google Play store URL. <ul><li> Example: Google Play store URL: `https://play.google.com/store/apps/details?id=com.rovio.angrybird`<ul><li> `id` = `"com.rovio.angrybirds"` </li></ul></li></ul></li><li> For Android Apps not in the Google Play store, use the package name.</li></ul>|
| app_name | string | Application name. |
| app_version | string | App version number. <br>Example: `"3.0.2"`. |
```
#### `ad` parameters

```xtable
| Field {25%}| Data Type {10%}| Description{65%} |
|---|---|---|
| callback | string | Callback information to help attribute events |
| campaign_id | string | Campaign ID. |
| ad_id | string | Ad group ID. |
| creative_id | string | Ad ID. |
| is_retargeting | boolean | Whether the user is a retargeting user. |
| attributed | boolean | Whether the event is attributed. |
| attribution_type | string | Attribution type. |
| attribution_provider | string | Attribution provider. |
| attribution_share |float| Attribution share score.<br>A score provided by you per conversion, representing the portion of credit TikTok should receive for the conversion. A greater value indicates a larger credit share for TikTok. <br><br>Examples:<ul><li>0.0: No credit.</li><li>0.5: Partial credit.</li><li>1.0: Full credit.</li></ul>|
```


## Sample payload
The following is an example of a request for reporting a single `Purchase` App Event.
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/event/track/' \
--header 'Access-Token: {{ACCESS_TOKEN}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "event_source": "app",
  "event_source_id": "{{TIKTOK_APP_ID}}",
  "data": [
    {
      "event": "InstallApp",
      "event_time": 1687758765,
      "user": {
        "phone": "{{HASHED_PHONE}}",
        "email": "{{HASHED_EMAIL}}",
        "idfa": "{{IDFA}}",
        "idfv": "{{IDFV}}",
        "gaid": "{{GAID}}",
        "att_status": "AUTHORIZED",
        "locale": "en-US"
      },
      "app": {
        "app_id": "com.test.flyingsneakers",
        "app_name": "Flying Sneakers",
        "app_version": "3.0.2"
      },
      "ad": {
        "attributed": true,
        "callback": "{{CALLBACK}}",
        "campaign_id": "1700817380525057",
        "ad_id": "1700817380806706",
        "creative_id": "1700817378256945",
        "is_retargeting": false,        
        "attribution_type": "click_through"
      },
      "properties": {
        "currency": "USD",
        "value": 200.0,
        "content_type": "product",
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


