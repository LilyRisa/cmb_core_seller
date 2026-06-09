---
title: "Report a Web Event"
doc_id: 1740858531237890
path: "API Reference / Events 1.0 / Pixel Events / Report a Web Event"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Report a Web Event

Use this endpoint to report a Web Event.
> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.
## Supported web events

Events are defined as actions a website visitor takes to achieve a business goal, like adding an item to a cart, filling out a form or making a purchase.

There are two categories of events on TikTok: 
- **Standard Events**: these are actions with predefined names created by TikTok. Standard events are supported across all TikTok integration tools today and can be used for reporting, optimizing for conversions and building audiences. For a list of supported standard events, continue reading below.  
- **Custom Events**: these are actions that TikTok partners can define themselves beyond the predefined standard events list. Custom events can be set up with Event Builder in Events Manager or reported directly via the Pixel and Web Events API. **Custom events are available for reporting and audience creation purposes. Optimization is NOT supported at this time.**

The table below lists 18 standard web events, as well as recommended values in `properties` object. Please make sure to use the exact name as shown in **Event** as it is case sensitive. 


```xtable
| Event{25%} |Event Type in Events Manager{25%}|Description{25%}|Recommended fields in <code>properties</code> object{25%}|
|---|---|---|---|
|ViewContent|View Content|When a page is viewed.|`content_type`, `quantity, description`, `content_id`, `currency`, `value`|
|Search|Search|When a search is made.| |
|AddToWishlist|Add to Wishlist|When an item is added to a wishlist.||
|AddToCart|Add to Cart|When an item is added to the shopping cart.|`content_type`, `quantity`, `description`, `content_id`,` currency`, `value`|
|ApplicationApproval |Application Approval| When an application is approved.||
|InitiateCheckout|Initiate Checkout|When the checkout process is started.||
|AddPaymentInfo|Add Payment Info|When payment information is added in the checkout flow.||
|Purchase|Purchase|When a payment is completed.|`content_type`, `quantity`, `description`, `content_id`, `currency`, `value`|
|Contact|Contact|When contact or consultation occurs.| |
|Download|Download|When a button to open an external browser download page is clicked.||
|Lead|Submit Form|When a form is submitted.||
|CompleteRegistration|Complete Registration|When a registration is completed.||
|StartTrial|Start Trial|When someone begins a free trial of a product or service.|`currency`, `value`|
|SubmitApplication|Submit Application|When someone submits an application.||
|Subscribe|Subscribe|When a subscription is made.||
| CustomizeProduct | Customize Product | When someone customizes a product. | |
| FindLocation | Find Location | When someone tries to find your location. | |
| Schedule | Schedule | When someone makes an appointment to speak with your business or visit your location. | |
```

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/pixel/track/| /v1.3/pixel/track/|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/pixel/track/

**Method** POST

**Header**

```xtable
|Field|Data Type|Desciption|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
|Content-Type {Required}|string|Request message type<br>Allowed format: `"application/json"`.|
```

**Parameters**

```xtable
|Field|Data Type|Description|
| --- | --- | --- |
|pixel_code{Required}|string  |Pixel ID that can be found in events manager of the TikTok ads platform. For details, see the "[Where to Find pixel_code](https://ads.tiktok.com/marketing_api/docs?id=1739584855420929)" section. |
|event {Required}|string |Conversion event name. <br><br>It can be either a Standard Event or a Custom Event. <br><br>To find out the supported values for Standard Events, see the the "<b>Event</b>" column in the "[Supported web events](https://business-api.tiktok.com/portal/docs?id=1740858531237890#item-link-Supported%20web%20events)" section.<br>To report a Custom Event, pass a customized name in this field.|
|event_id  |string |Any string or hashed ID that can identify a unique event. This is required if you are sending overlapping events from both TikTok Pixel and  Events API. Please check the [deduplication](https://ads.tiktok.com/marketing_api/docs?id=1739584864945154) subsection for details and complete the setup. <p>Some examples are:</p> <ol><li>RandomNumber</li><li>OrderID (for Purchase event)</li><li>SessionID_RandomNumber</li><li>ExternalID_RandomNumber</li><li>BrowserID_RandomNumber</li></ol>|
|timestamp |string  | Timestamp that the event took place. Timestamp with ISO 8601 format. See [ISO_8601](https://en.wikipedia.org/wiki/ISO_8601). Please note, if timestamp is not provided, the time when TikTok receives the event via the server will be used. |
|context{Required} |object  | Required to attribute events to TikTok campaigns. The context object can be removed for testing event fires. For the data fields in this object, see the "`context` object parameters" section below.  |
|properties |object  |Properties associated with the event. For the data fields in this object, see the "`properties` object parameters" section below.|
```

### `context` object parameters
 > <span style="color:DodgerBlue">**Note**</span><br>To increase the probability of matching website visitor events with TikTok ads and improve your ads performance, we recommend as much information as possible. When there's no pii information available, you can send us both ip and user_agent.

```xtable
|Field{15%}|Description{25%}|Data Type{10%}|Formatting{20%}|Example{20%}|
|---|---|---|---|---|
|context|	Context contains all customer information parameters.|object| | 
#|ad|Ad information. |object| | 
##|callback|TikTok Click ID. TikTok Click ID (`ttclid`) is a data connection parameter appended to a landing page URL whenever a user clicks on an ad on TikTok.<br>The `ttclid` valid period is the same as your CTA window setting in [Attribution Manager](https://ads.tiktok.com/help/article/attribution-manager?redirected=1). <br>For details, see [Set up TikTok Click ID and Cookies](https://ads.tiktok.com/marketing_api/docs?id=1739584860883969) section.|string | | |
#|page|Page information. |object| | 
##|url|Page url when event happened. |string| | `"http://demo.mywebsite.com/purchase"`
##|referrer| Page referrer.|string| | `"http://demo.mywebsite.com"`
#|user| The personal data of a user.|object| | 
##|external_id |Any unique identifier on the advertiser's side, such as loyalty membership IDs, user IDs, and external cookie IDs.<br>It must be hashed with SHA256 on the client side. |string| Leading/Trailing spaces need to be trimmed before hashing.| `9f7ec22d72092cd3c0b58726ed9c2d91b92e51a3f29837508fb2948bb22dd2fd`
##|email|The email of the customer if available. <br>It must be hashed with SHA256 on the client side. |string|You should lowercase all characters before hashing.|`0c7e6a405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b`
##|phone_number|The phone number of the customer if available. <br>It must be hashed with SHA256 on the client side. |string|Prior to hashing, format the phone number:<ul><li>If the country code is 86, then do not include country code (example: 13800000000). </li><li>Otherwise, include country code with + and remove any other characters (spaces, '-') between numbers (example for US: +12133734253)</li><li>To learn more, refer to: [https://github.com/google/libphonenumber](https://github.com/google/libphonenumber)|`9f7ec22d72092cd3c0b58726ed9c2d91b92e51a3f29837508fb2948bb22dd2fd`
##|ttp|Cookie ID. If you also use Pixel SDK and enabled cookie, Pixel SDK automatically saves a unique identifier in the `_ttp` cookie. The value of `_ttp` is used to match website visitor events with TikTok ads. You can extract the value of `_ttp` and attach the value here. <br>For details, see [Set up TikTok Click ID and Cookies](https://ads.tiktok.com/marketing_api/docs?id=1739584860883969) section.|string| | 
#|ip|Non-hashed public IP address of the browser.<br>To increase the probability of matching website visitor events with TikTok ads, we recommend sending both `ip` and `user_agent`.|string|Both IPv4 and IPv6 addresses are supported. For IPv6 addresses, both full and compressed formats are acceptable. <br>For example:<ul><li>Full format: <b>2001:0db8:1111:000a:00b0:0000:0000:0200</b></li><li>Compressed format: <b>2001:db8:1111:a:b0::200</b></li></ul> ||
#|user_agent|Non-hashed user agent from the user’s device.<br>To increase the probability of matching website visitor events with TikTok ads, we recommend sending both `ip` and `user_agent`.|string| |`Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.`|
```

### `properties` object parameters
```xtable
|Parameter {20%}|Description{27%} | Value type{17%}|Required for Return on Ad Spend (ROAS){12%}|Required for Video Shopping Ads{12%}|Required for Value-Based Optimization (VBO){12%}|
|---|---|---|
|properties|Properties associated with the event in JSON format.|object| | | |
#|content_type| The `content_type` object property's value must be set to either `product`, or `product_group`. <br>If you use `sku_id` as `content_id`, use `product`. <br>If you use `item_group_id` as `content_id`, use `product_group`.<br>This field is required for VSA.|Must be either `product` or `product_group`. | |✅||
#|contents|A list of content objects that represent relevant products in a web event with product information.|Must be an array of objects | | ✅||
##|price|The price of the item. <br><p><span style="color:darkred"><b>Note</b></span>: <strong>Price</strong> is the price for a single item, and <strong>value</strong> is the total price of the order. For example, if you have two items each sold for $10, the <code>price</code> parameter would pass <code>10</code> and the <code>value</code> parameter would pass <code>20</code>.  |number| | ||
##|quantity|The number of the item.|number| | ||
##|content_id|Unique ID of the product or content. <br>Recommend using `sku id` or `item_group_id`. This should match the `sku_id` or `item_group_id` you set up in the catalog (if available).<br> This field is required for VSA|string| |✅ ||
##|content_category|Category of the page/product|string| | ||
##|content_name|Name of the page/product|string| | ||
##|brand|Brand name of the product item.| string|| ||
#|currency|ISO 4217 code. Examples: `EUR`, `USD`, `JPY`.<br><strong> List of currencies currently supported:</strong> AED, ARS, AUD, BDT, BHD, BIF, BOB, BRL, CAD, CHF, CLP, CNY, COP, CRC, CZK, DKK, DZD, EGP, EUR, GBP, GTQ, HKD, HNL, HUF, IDR, ILS, INR, ISK, JPY, KES, KHR, KRW, KWD, KZT, MAD, MOP, MXN, MYR, NGN, NIO, NOK, NZD, OMR, PEN, PHP, PHP, PKR, PLN, PYG, QAR, RON, RUB, SAR, SEK, SGD, THB, TRY, TWD, UAH, USD, VES, VND, ZAR|enum(string)|✅|||
#|value|Value of the order or items sold. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li><strong>Price</strong> is the price for a single item, and <strong>value</strong> is the total price of the order. For example, if you have two items each sold for ten dollars, the `price` parameter would pass `10.00` and the `value` parameter would pass `20.00`.</li><li>Values should always be formatted as an integer or decimal (ex: 10.00) regardless of the location, currency, etc. It cannot contain any currency signs, special characters, letters, or commas.</li></ul>|number|✅||✅|
#|query|The text string that was input by a user. For instance, if a person searches for a product on your website, you can forward the keyword being searched. If a person enters a coupon code at check out, you can forward the code.|string| | ||
#|description|Description of the item or page.|string| | ||
#|status|Status of an order, item, or service. <br><p><span style="color:darkred"><b>Note</b></span>: Depending on your use of `status`, Events API may be required in order to share status changes to TikTok.|string| | ||
```

###  Example

> <span style="color: dodgerblue">**Note**</span><br>You need to replace the dummy data in the example with your own data.

```xcodeblock

(code cURL http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/pixel/track/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "pixel_code": "BU35TSQHT2A1QT375GMG",
  "event": "InitiateCheckout",
  "event_id":"1616318632825_357",
  "timestamp": "2020-09-17T19:49:27Z",
  "context": {
    "ad": {
      "callback": "123ATXSfe"
    },
    "page": {
      "url": "http://demo.mywebsite.com/purchase",
      "referrer": "http://demo.mywebsite.com"
    },
    "user": {
      "external_id": "f0e388f53921a51f0bb0fc8a2944109ec188b59172935d8f23020b1614cc44bc",
      "phone_number": "2f9d2b4df907e5c9a7b3434351b55700167b998a83dc479b825096486ffcf4ea",
      "email": "dd6ff77f54e2106661089bae4d40cdb600979bf7edc9eb65c0942ba55c7c2d7f",
	  "ttp":"94e2a4j9-h3j5-k2h5-98cc-c84a745mk098"
    },
    "user_agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.109 Safari/537.36",
    "ip": "13.57.97.131"
  },
  "properties": {
    "content_type": "product_group",
    "contents": [
      {
        "price": 8,
        "quantity": 2,
        "content_id": "1077218"
      },
      {
        "price": 30,
        "quantity": 1,
        "content_id": "1197218"
      }
    ],
    "currency": "USD",
    "value": 46.00
  }
}'
(/code);
```


## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|request_id |string|The log id of a request, which uniquely identifies the request. |
|data|object| Error details. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "request_id": "2019091810032301011023224213558",
    "data":{}
}
(/code);
```
