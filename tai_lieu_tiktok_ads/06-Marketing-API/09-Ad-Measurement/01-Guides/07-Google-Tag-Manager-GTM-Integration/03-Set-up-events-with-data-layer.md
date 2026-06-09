---
title: "Set up events with data layer"
doc_id: 1799004097478658
path: "Marketing API / Ad Measurement / Guides / Google Tag Manager (GTM) Integration / Set up events with data layer"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Set up events with data layer

The data layer is an object used by Google Tag Manager (GTM) and `gtag.js` to pass information to tags. Your website code can interact with the data layer to store or send data by using the Google Tag or Tag Manager JavaScript APIs, that is, `gtag()` or `dataLayer.push()`.

We have developed two templates, the [TikTok Pixel template](https://tagmanager.google.com/gallery/#/owners/tiktok/templates/gtm-template-pixel) and the [TikTok Events API template](https://tagmanager.google.com/gallery/#/owners/tiktok/templates/gtm-template-eapi), to parse data layer events and send the events to TikTok using the Pixel and Events API.

> <span style="color:DodgerBlue">**Note**</span><br>
> If you plan to use the data layer to pass information to tags, it's highly recommended to use the interactive flow in TikTok Events Manager to set up the integration of Pixel or Events API with GTM. This flow automatically installs the templates and all the necessary tags, variables, and triggers for processing and sending data layer events to TikTok. For more information, see:<ul><li>[TikTok Pixel only setup](https://business-api.tiktok.com/portal/docs?id=1799004034003969)</li><li>[Events API and Pixel setup](https://business-api.tiktok.com/portal/docs?id=1799004052020225)</li></ul>

The events and parameters recognized by the TikTok templates align with the recommendations provided by Google. For more information on the recommended event types and parameters, see [Recommended events](https://developers.google.com/analytics/devguides/collection/ga4/reference/events?client_type=gtm) and [Common event data](https://developers.google.com/tag-platform/tag-manager/server-side/common-event-data).

> <span style="color:DodgerBlue">**Note**</span><br>
> If you have already set up events following the guides from Google, that is, [Recommended events](https://developers.google.com/analytics/devguides/collection/ga4/reference/events?client_type=gtm) and [Common event data](https://developers.google.com/tag-platform/tag-manager/server-side/common-event-data), to measure your website with Google Analytics 4 (GA4), the TikTok templates will automatically detect and convert the same events or parameters into TikTok's standard events or parameters.

This document covers the following content:
- Prerequisites for setting up data layer events
- An example of a purchase event
- Overview of the data layer event schema, including recommended event types and parameters


## Prerequisites
- You need to have the ability to modify the source code of your website. 
- Before proceeding with setting up TikTok events, we recommend familiarizing yourself with the following Google developer guides:
  - [GTM data layer](https://developers.google.com/tag-platform/tag-manager/datalayer)
  - [Setting up data-layer variables](https://support.google.com/tagmanager/answer/6164391?hl=en)
  - [Measure ecommerce](https://developers.google.com/analytics/devguides/collection/ga4/ecommerce?client_type=gtm) and [Recommended events](https://developers.google.com/analytics/devguides/collection/ga4/reference/events?client_type=gtm)
  
## A simple `purchase` event
Let's take a look at an example to understand how this works. Suppose you want to send a `Purchase` event to TikTok. When a user makes a purchase on your website, the JavaScript code of your website should trigger a `purchase` event using the Google Tag Manager data layer. The TikTok templates will then parse this event and send it as a `Purchase` event to TikTok.

```xcodeblock
(code Javascript javascript)
// Google Tag example
gtag('event', 'purchase', {
  transaction_id: 'T_12345',
  value: 200.0,
  currency: 'USD',
  user_data: {
    sha256_email_address: '0c7e6a405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b',
    sha256_phone_number: '123456405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b',
    address: {
      sha256_first_name: '4f23798d92708359b734a18172c9c864f1d48044a754115a0d4b843bca3a5332',
      sha256_last_name: 'fd53ef835b15485572a6e82cf470dcb41fd218ae5751ab7531c956a2a6bcd3c7',
      city: 'City Town',
      region: 'CA',
      postal_code: '54321',
      country: 'US'
    }
  },
  items: [
    {
      item_id: 'SKU_12345',
      item_name: 'Fancy-MaxSpeed2.0 Black',
      item_brand: 'Fancy Sneakers',
      item_category: 'Shoes - Running Shoes',
      price: 100.0,
      quantity: 2
    }
  ],
  tt_external_id: 'b9c5fdaea74ee1c31705b764389895371b8d416c5cae8740e7f89082a9fcdc0a',
  tt_content_type: 'product'
});
(/code)
```

```xcodeblock
(code Javascript javascript)
// Tag Manager example
dataLayer.push({ ecommerce: null });
dataLayer.push({
  event: 'purchase',
  user_data: {
    sha256_email_address: '0c7e6a405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b',
    sha256_phone_number: '123456405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b',
    address: {
      sha256_first_name: '4f23798d92708359b734a18172c9c864f1d48044a754115a0d4b843bca3a5332',
      sha256_last_name: 'fd53ef835b15485572a6e82cf470dcb41fd218ae5751ab7531c956a2a6bcd3c7',
      city: 'City Town',
      region: 'CA',
      postal_code: '54321',
      country: 'US'
    }
  },
  ecommerce: {
    transaction_id: 'T_12345',
    value: 200.0,
    currency: 'USD',
    items: [
      {
        item_id: 'SKU_12345',
        item_name: 'Fancy-MaxSpeed2.0 Black',
        item_brand: 'Fancy Sneakers',
        item_category: 'Shoes - Running Shoes',
        price: 100.0,
        quantity: 2
      }
    ]
  },
  tt_external_id: 'b9c5fdaea74ee1c31705b764389895371b8d416c5cae8740e7f89082a9fcdc0a',
  tt_content_type: 'product'
});
(/code)
```

Using the Events API integration as an example, here is a sample event payload that is sent to TikTok Events API. For more details on the specific parameters, see [Events API parameters](https://business-api.tiktok.com/portal/docs?id=1771101151059969).

```xcodeblock
(code Javascript javascript)
{
  "event_source": "web",
  "event_source_id": "{{PIXEL-CODE}}",
  "data": [
    {
      "event": "Purchase",
      "event_time": 1714176177,
      "user": {
        "phone": "0c7e6a405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b",
        "email": "123456405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b",
        "external_id": "b9c5fdaea74ee1c31705b764389895371b8d416c5cae8740e7f89082a9fcdc0a",
        "first_name": "4f23798d92708359b734a18172c9c864f1d48044a754115a0d4b843bca3a5332",
        "last_name": "fd53ef835b15485572a6e82cf470dcb41fd218ae5751ab7531c956a2a6bcd3c7",
        "city": "citytown",
        "state": "ca",
        "country": "us",
        "zip_code": "20f3765880a5c269b747e1e906054a4b4a3a991259f1e16b5dde4742cec2319a"
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
            "content_name": "Fancy-MaxSpeed2.0 Black",
            "content_category": "Shoes - Running Shoes",
            "brand": "Fancy Sneakers"
          }
        ]
      }
    }
  ]
}
(/code)
```

## Event name mapping
When setting up TikTok events using the GTM data layer, you need to directly send the Google Analytics recommended event names. The TikTok Pixel and Events API templates will automatically map them to the corresponding TikTok standard event names.

If a Google Analytics event name is not listed in the mapping, it will be treated as a TikTok custom event. Learn more about [TikTok supported events](https://business-api.tiktok.com/portal/docs?id=1771101186666498) and [GA4 recommended events](https://developers.google.com/analytics/devguides/collection/ga4/reference/events?client_type=gtag).

```xtable
| TikTok Standard Event Name {30%}| Google Analytics Event Name{60%} |
|---|---|
| `AddPaymentInfo` | `add_payment_info` |
| `AddToCart` | `add_to_cart` |
| `AddToWishlist` | `add_to_wishlist` |
| `Purchase` | `purchase` |
| `CompleteRegistration` | `sign_up` |
| `InitiateCheckout` | `begin_checkout` |
| `Pageview` | `page_view` |
| `Search` | `search` |
| `ViewContent` | `view_item` |
```

For the following TikTok standard event names that don't have a corresponding GA4 recommended event name, you can simply send the TikTok event name instead when setting up your events.
- `Contact`
- `Download`
- `Lead`
- `Subscribe`

To allow TikTok Pixel and Events API templates to automatically convert GA4 recommended event names, you need to use the GTM built-in `{{Event}}` variable as the **Event Name** in your TikTok Pixel tag and the built-in `{{Event Name}}` variable as the **Event Name** in your TikTok Events API tag.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/cadb3d40761bdaeaabb6552d3fdd1ee9" width="60%" style="color:#808080" />
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/936ec492542f73d3653eb315cb55e056" width="60%" style="color:#808080" />

## Website event parameters 
When configuring event parameters using GTM data layer, you need to follow [GA4 recommended events](https://developers.google.com/analytics/devguides/collection/ga4/reference/events?client_type=gtag) and [GTM common event data](https://developers.google.com/tag-platform/tag-manager/server-side/common-event-data). The TikTok Pixel template and Events API template will automatically map the GTM and GA4 parameters to the corresponding TikTok parameters. Learn more about TikTok [Pixel parameters](https://business-api.tiktok.com/portal/docs?id=1739585702922241) and [Events API parameters](https://business-api.tiktok.com/portal/docs?id=1771101151059969).

### Product data
Product information is crucial if you are advertising a product sold on a website. Sending complete product information enables measurement and optimization through solutions such as TikTok Video Shopping Ads (VSA) for commerce, Value-based Optimization (VBO), or ROAS measurement.

For more information on the recommended Google Analytics parameters to include, see [GA4 recommended events](https://developers.google.com/analytics/devguides/collection/ga4/reference/events?client_type=gtag).

<table>
<thead>
  <tr>
    <th rowspan="2" style="width:20%">TikTok parameters</th>
    <th colspan="2" style="width:44%">Google Analytics parameters</th>
    <th style="width:18%">Required for reporting Return on Ad Spend (ROAS) or for Value-based Optimization (VBO)</th>
    <th style="width:18%">Required for Video Shopping Ads (VSA)</th>
  </tr>
  <tr>
    <th style="width:22%">Google tag <br>- <code>gtag()</code> API</th>
    <th style="width:22%">Tag Manager <br>- <code>dataLayer.push()</code> API</th>
    <th></th>
    <th></th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>value</td>
    <td>value</td>
    <td>ecommerce.value</td>
    <td>✅</td>
    <td></td>
  </tr>
  <tr>
    <td>currency</td>
    <td>currency</td>
    <td>ecommerce.currency</td>
    <td>✅</td>
    <td></td>
  </tr>
  <tr>
    <td>content_type</td>
    <td>tt_content_type</td>
    <td>tt_content_type</td>
    <td></td>
    <td>✅</td>
  </tr>
  <tr>
    <td>contents</td>
    <td>items</td>
    <td>ecommerce.items</td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td>contents[].price</td>
    <td>items[].price</td>
    <td>ecommerce.items[].price</td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td>contents[].content_id</td>
    <td>items[].item_id</td>
    <td>ecommerce.items[].item_id</td>
    <td></td>
    <td>✅</td>
  </tr>
  <tr>
    <td>contents[].content_category</td>
    <td>items[].item_category</td>
    <td>ecommerce.items[].item_category</td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td>contents[].content_name</td>
    <td>items[].item_name</td>
    <td>ecommerce.items[].item_name</td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td>contents[].quantity</td>
    <td>items[].quantity</td>
    <td>ecommerce.items[].quantity</td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td>contents[].brand</td>
    <td>items[].brand</td>
    <td>ecommerce.items[].brand</td>
    <td></td>
    <td></td>
  </tr>
</tbody>
</table>



### User data
To leverage [Advanced Matching](https://business-api.tiktok.com/portal/docs?id=1739585700402178), which helps you optimize your TikTok ads and drive performance by matching customer information with people on TikTok, you can send the following user information.

For more information on GTM data layer parameters, see [GTM common event data](https://developers.google.com/tag-platform/tag-manager/server-side/common-event-data).


| TikTok Events parameters | GTM data layer parameters  |
|---|---|
| `email` |<ul><li>`user_data.email_address` <ul><li>The value will be SHA256 hashed before being sent to TikTok.</li></ul></li><li>`user_data.sha256_email_address`</li></ul> |
| <ul><li>`phone_number` (for Pixel)</li><li>`phone` (for Events API)</li></ul>  |  <ul><li>`user_data.phone_number` <ul><li>The value will be SHA256 hashed before being sent to TikTok.</li></ul></li><li>`user_data.sha256_phone_number`</li></ul>|
| `external_id`| `tt_external_id`<ul><li>The value will be SHA256 hashed before being sent to TikTok.</li><li>See [Set up External ID](https://business-api.tiktok.com/portal/docs?id=1771100952291330) for more details.</li></ul> |
| `first_name` | <ul><li> `user_data.address.first_name` <ul><li>The value will be SHA256 hashed before being sent to TikTok.</li></ul>  </li><li> `user_data.address.sha256_first_name`</li></ul>|
| `last_name` | <ul><li> `user_data.address.last_name`  <ul><li>The value will be SHA256 hashed before being sent to TikTok.</li></ul></li><li> `user_data.address.sha256_last_name`</li></ul>|
| `city` | `user_data.address.city`<br><br> The city for the address of the user. |
|`state` | `user_data.address.region` <br><br>The state or territory for the address of the user. |
| `country`| `user_data.address.country` <br><br>The country or region code for the address of the user. |
| `zip_code` |  <ul><li> `user_data.address.postal_code`<ul><li>The value will be SHA256 hashed before being sent to TikTok.</li><li>For US zip codes, only the first five digits will be sent (hashed) to TikTok.</li></ul> |


## Send data layer events to server tags
  
> <span style="color:DodgerBlue">**Note**</span><br>
> If you use the Events Manager interactive flow (<b>recommended</b>) to set up Events API integration, you can skip the following setup steps. This is because the interactive flow will automatically install the [TikTok Events API template](https://tagmanager.google.com/gallery/#/owners/tiktok/templates/gtm-template-eapi) and all the additional tags, variables and triggers necessary for sending events to your server container.

  For Events API integration, it is essential to forward your data layer events (triggers using `gtag()` or `dataLayer.push()` APIs) to your server container, where the [TikTok Events API template](https://tagmanager.google.com/gallery/#/owners/tiktok/templates/gtm-template-eapi) tag is installed. To achieve this, you need to set up a Google Tag, a Google Analytics GA4 Event tag, and some additional variables.
  
### Set up a Google Tag
In your **Web Container**, add a **Google Tag**. If you already set up Server-side Tagging and have an existing Google Tag with `server_container_url` configured, you can reuse the same Google Tag.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/307df4a481f62638c7fff8c809d8724d" width="60%" style="color:#808080" />
  
In your Google Tag, set the `server_container_url` to point to your tagging server and set the trigger to be fired on **All Pages**. [Learn more](https://developers.google.com/tag-platform/tag-manager/server-side/send-data?option=GTM).
  
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/1b7673350d97d13cd972c0d929f3b9bc" width="60%" style="color:#808080" />
  
### Set up a GA4 Event tag
In your **Web Container**, add a **Google Analytrics: GA4 Event** tag.
  
  <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/728af7ad31678a6b38a162fe8017bd8e" width="60%" style="color:#808080" />
  
In your **Google Analytrics: GA4 Event** tag, set the built-in `{{Event}}` variable as the **Event Name**.
  
  <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d12eeab4366a7dc666c2bd52786f09a8" width="60%" style="color:#808080" />
  
Then you need to configure <a href="#item-link-GA4 Event Parameters">GA4 Event Parameters</a>.

#### GA4 Event Parameters
- If your website implements the recommended GA4 data layer event schema (see instructions in <a href="#item-link-Website event parameters">Website event parameters</a>), configure the following mapping in the **Google Analytrics: GA4 Event** tag based on the exact API that you have integrated with (`gtag()` or `dataLayer.push()`). The **GA4 Event parameters** specify data parameters and the corresponding values that will be forwarded from the Web Container to the Server Container on your tagging server.
- If your website implements customized schemas, you need to use the **Parameter Override** in TikTok templates to [manually configure TikTok event parameters](https://business-api.tiktok.com/portal/docs?id=1799004110681154).

```xtable
| GA4 Event Parameter{20%} | Note{20%}| Value for <code>gtag()</code> API {30%}| Value for <code>dataLayer.push()</code> API {30%}|
|---|---|---|---|
| `first_party_collection` | If you want to send `user_data` to TikTok through Events API, set `first_party_collection` to `true`. | `true` |`true`|
| `user_data`  | | Variable Type: Data Layer Variable <br>Variable Name: `eventModel.user_data` | Variable Type: Data Layer Variable <br> Variable Name: `user_data` |
| `tt_external_id` |  | Variable Type: Data Layer Variable <br>Variable Name: `eventModel.tt_external_id`| Variable Type: Data Layer Variable<br> Variable Name: `tt_external_id`|
| `value` |  | Variable Type: Data Layer Variable <br>Variable Name: `eventModel.value`| Variable Type: Data Layer Variable<br> Variable Name: `ecommerce.value`|
| `currency`|   | Variable Type: Data Layer Variable<br>Variable Name: `eventModel.currency` | Variable Type: Data Layer Variable<br>Variable Name: `ecommerce.currency` |
| `tt_content_type` |  | Variable Type: Data Layer Variable<br>Variable Name: `eventModel.tt_content_type`  | Variable Type: Data Layer Variable<br> Variable Name: `tt_content_type` |
| `tt_contents` | If you want to send the item data of an event that is not a GA4 recommended event or an event that does not support the `items` parameter, specify `tt_contents`. [Learn more about GA4 recommended events specification](https://developers.google.com/analytics/devguides/collection/ga4/reference/events?client_type=gtag). | Variable Type: Custom Javascript <br>`function() &#123; <br> &nbsp;&nbsp;&nbsp;&nbsp;var tt_contents = window.google_tag_manager[&#123;&#123;Container ID&#125;&#125;].dataLayer.get('eventModel.items');<br>&nbsp;&nbsp;&nbsp;&nbsp;return JSON.stringify(tt_contents); <br> } `<br> JSON.stringify `tt_contents` as it is a TikTok defined GA4 JSON parameter. |Variable Type: Custom Javascript <br>`function() &#123; <br> &nbsp;&nbsp;&nbsp;&nbsp;var tt_contents = window.google_tag_manager[&#123;&#123;Container ID&#125;&#125;].dataLayer.get('ecommerce.items');<br>&nbsp;&nbsp;&nbsp;&nbsp;return JSON.stringify(tt_contents); <br> } `<br> JSON.stringify `tt_contents` as it is a TikTok defined GA4 JSON parameter. |
| `event_id`  |  | Variable Type: Custom JavaScript <br> See <a href="#item-link-How to set up deduplication">How to set up deduplication</a>.  | Variable Type: Custom JavaScript <br> See <a href="#item-link-How to set up deduplication">How to set up deduplication</a>.  |
```

You need to select **Send Ecommerce data** with **Data Layer** as the **Data source**.
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c602423b293257a2abc0fa3c09a2d93e" width="60%" style="color:#808080" />
  
Finally, assign a trigger to your GA4 Event tag. In most cases, you can set the trigger to be fired on All Custom Events, with the `.+` regex expression as the event name matching rule.
  
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/6de411961a49ce404500f69c4c425595" width="60%" style="color:#808080" />
  
### Example configuration 
Here's an example Google Analytics: GA4 Event tag configuration after completing the preceding setups:
 <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/58033e4d832174ba9808428471658ca8" width="60%" style="color:#808080" />
  
### How to set up deduplication
  
> <span style="color:DodgerBlue">**Note**</span><br>
> If you use the recommended Events Manager interactive flow to set up both Pixel and Events API integration, the Event ID deduplication will be automatically configured for you.

To set up deduplication, you can configure a GTM `Custom Javascript` variable as your `event_id`. This variable helps deduplicate events between TikTok Pixel and Events API events. [Learn more about event deduplication](https://ads.tiktok.com/help/article/event-deduplication). 
  
An example of generating an `event_id` for the same event is to use the GTM data layer default values, that is, the tag loading time (`start`) and the unique event sequence id (`uniqueEventId`).
  
 <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/01c8b4fce47711019cb6d5e1b9a2066d" width="60%" style="color:#808080" />
  
To set up the Event ID deduplication, follow these steps:
- In the **TikTok Pixel** Tag of your Web container, set the variable to the `Event ID` parameter (**Parameter Override** > **Deduplication Configuration** > **Event ID**).
- In the **Google Analytics: GA4 Event** Tag of your Web container, set the same variable to the `event_id` Event parameter.

## Verify events setup
Once you have configured TikTok events and parameters in the Pixel or Events API templates, you can verify the setup by following [Verify GTM setup](https://business-api.tiktok.com/portal/docs?id=1799004129683458).

## Support
If you encounter any issues that require support, file an issue in the respective GitHub projects:
- [TikTok Pixel template GitHub](https://github.com/tiktok/gtm-template-pixel/issues)
- [TikTok Events API template GitHub](https://github.com/tiktok/gtm-template-eapi/issues)
