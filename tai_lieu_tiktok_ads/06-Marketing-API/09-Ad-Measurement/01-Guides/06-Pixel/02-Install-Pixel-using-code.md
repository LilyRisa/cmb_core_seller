---
title: "Install Pixel using code"
doc_id: 1739585702922241
path: "Marketing API / Ad Measurement / Guides / Pixel / Install Pixel using code"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Install Pixel using code

# Before you begin
* You must have created a pixel in Events Manager and have access to the pixel base code. Learn how to create a pixel in [Help Center](https://ads.tiktok.com/help/article?aid=10021).
* You must have a technical resource.
* You must have access to your website's source code.

# Install the pixel
If you created a pixel previously but did not install it on your website, you must complete installation before proceeding to set up events.
1. Retrieve your pixel base code directly from the setup screen or you can use our base code template. Remember to replace the placeholder with your pixel ID before using the template.

```xcodeblock
(code Javascript javascript)
// BASE CODE TEMPLATE:

<script>
//Part1
!function (w, d, t) {
  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};

//Part 2
  ttq.load('YOUR PIXEL ID WILL BE LOCATED HERE');
  ttq.page();
}(window, document, 'ttq');
</script>
(/code)
```
The base code has 2 parts:
* Part 1 is for general functions.
* Part 2 contains ttq.load('{pixel ID placeholder}'), which helps to load the context relevant to the specific pixel based on its configuration. ttq.page() will report a page view event, which can be used to verify the code installation.

2. Complete installation by pasting the base code at the top of the header section on your website. Website headers start with the <head> tag and close with the </head> tag. Here is an example:

```xcodeblock
(code Javascript javascript)
// Header example:
<!DOCTYPE html >
    <head>
          "INSERT TIKTOK PIXEL CODE HERE"
    </head>
</html>
(/code)
```


# Event codes
Prior to installing event code, make sure you have properly installed your base code in the previous step. Event code will not work by itself without the base code.
Event code enables you to report on the specific actions or events visitors take for conversion purposes. For example, if you are an e-commerce business, events such as "add to cart" and "complete payment" would be important for you to measure. Below is a list of Standard Events currently supported by the TikTok Pixel and Events API. 
With any of the standard events, you can send parameters, or additional information about the visitor's actions, like total price of an order or product name and category. If you're unsure of what parameters to use, refer to our 'recommended parameters' in the table.
```xtable
|Event{20%}|Description{35%}|Event Code{25%}|Recommended parameters{20%}
|---|---|---|
|Add Payment Info|When a visitor adds their payment information at checkout.|ttq.track('AddPaymentInfo')|  |
|Add to Cart|When a visitor adds an item to the cart.|ttq.track('AddToCart')|content_type, quantity, description, content_id, currency, value |
|Add to Wishlist|When an item is added to the wishlist.|ttq.track('AddToWishlist')|  |
| Application Approval|When an application is approved.    | ttq.track('ApplicationApproval') |  |
|Purchase|When a visitor makes a payment. TikTok recommends using this event when placing an order and making a payment are the same.|ttq.track('Purchase')|content_type, quantity, description, content_id, currency, value |
|Complete Registration|When a visitor signs up for something such as account registration.|ttq.track('CompleteRegistration')| |
|Contact|When a visitor contacts you.|ttq.track('Contact')| |
| Customize Product | When a visitor customizes a product. | ttq.track('CustomizeProduct') | |
|Download|When a visitor downloads something from your website.|ttq.track('Download')| |
| Find Location | When a visitor tries to find your location. | ttq.track('FindLocation') | |
|Initiate Checkout|When a visitor proceeds to checkout.|ttq.track('InitiateCheckout')| |
|Lead|When a visitor submits a form.|ttq.track('Lead')| |
| Schedule | When a visitor makes an appointment to speak with your business or visit your location. | ttq.track('Schedule') | |
|Search|When a visitor searches.|ttq.track('Search')|query |
| Start Trial |When someone begins a free trial of a product or service. | ttq.track('StartTrial') | currency, value  |
| Submit Application| When someone submits an application.| ttq.track('SubmitApplication') |  |
|Subscribe|When a visitor subscribes on your website including follows, content, or paid subscriptions.|ttq.track('Subscribe')| |
|View Content|When a visitor views a specific page. TikTok recommends measuring pages important to your business such as product comparison, announcement, or release pages.|ttq.track('ViewContent')|content_type, quantity, description, content_id, currency, value |
```
> <span style="color:DodgerBlue">**Note**</span><br>By default, the pixel base code will always include "Page View" events, which measure when a visitor lands on your website. Therefore, no additional event code is needed to measure "Page View."

## Install event code
You can call the `ttq.track('') `function anywhere between your web page's opening and closing `<body>` tags, either when the page loads, or when a visitor completes an action such as clicking a button.

**Important note:** The examples below uses "pixel_id_1"," pixel_id_2" and "pixel_id_3" as placeholder values in the sample code snippets to help illustrate.  Make sure you use your actual pixel ID when implementing.


### Measure an event based on page load
If you want to measure a purchase event after a visitor has completed the purchase, you could call the `ttq.track('')` function on your purchase confirmation page (The premise is the purchase confirmation page loaded this basic code):

```xcodeblock
(code Javascript html)
<body>
  ...
  <script>
    ttq.instance('pixel_id_1').track('Purchase')
  </script>
  ...
</body>
(/code)
```

### Measure an event based on a button click
If you want to measure a purchase event when the visitor clicks a purchase button, you could tie the `ttq.track('')` function call to the purchase button on your check out page:

```xcodeblock
(code Javascript html)
<button id="addToCartButton">Purchase</button>
<script type="text/javascript">
document.getElementById("addToCartButton").onclick = function(){
    ttq.track('AddToCart',{  
	contents: [
    {
      content_id: '301',
      content_name: 'dress',
	  quantity: 1,
      price: 8,
    }],
    content_type: 'product',
    value: 8.0,
    currency: 'USD',  })
}
</script>
(/code)
```


# Parameters
Below is a list of parameters supported by the TikTok Pixel and Events API. Parameters can be paired with any event. Refer to the description to see examples of how parameters can be used. 

```xtable
|Parameter {17%}|Description{28%} | Value type{19%}|Required for Return on Ad Spend (ROAS){12%}|Required for Video Shopping Ads{12%}|Required for Value-Based Optimization (VBO){12%}|
|---|---|---|
|content_ids| Unique ID or array of multiple IDs of the products or content. <br>Example: `['987','654']`. <br>We recommend using `sku_id` or `item_group_id` that matches the `sku_id` or `item_group_id` you set up in the catalog, if available.<br>This field is required for Video Shopping Ads (VSA). |Must be an array of strings | |✅| |
|content_type| The `content_type` object property's value must be set to either `product`, or `product_group`. <br>If you use `sku_id` as `content_id`, use `product`. <br>If you use `item_group_id` as `content_id`, use `product_group`.<br>This field is required for VSA.|Must be either `product` or `product_group`. | |✅||
|contents|A list of content objects that represent relevant products in a web event with product information.|Must be an array of objects | | ✅||
#|price|The price of the item. <br><p><span style="color:darkred"><b>Note</b></span>: <strong>Price</strong> is the price for a single item, and <strong>value</strong> is the total price of the order. For example, if you have two items each sold for $10, the <code>price</code> parameter would pass <code>10</code> and the <code>value</code> parameter would pass <code>20</code>.  |number| | ||
#|content_id|Unique ID of the product or content. <br>Recommend using `sku id` or `item_group_id`. This should match the `sku_id` or `item_group_id` you set up in the catalog (if available).<br> This field is required for VSA|string| |✅ ||
#|content_category|Category of the page/product|string| | ||
#|content_name|Name of the page/product|string| | ||
#|brand|Brand name of the product item.| string|| ||
|currency|ISO 4217 code. Examples: `EUR`, `USD`, `JPY`.<br><strong> List of currencies currently supported:</strong> AED, ARS, AUD, BDT, BHD, BIF, BOB, BRL, CAD, CHF, CLP, CNY, COP, CRC, CZK, DKK, DZD, EGP, EUR, GBP, GTQ, HKD, HNL, HUF, IDR, ILS, INR, ISK, JPY, KES, KHR, KRW, KWD, KZT, MAD, MOP, MXN, MYR, NGN, NIO, NOK, NZD, OMR, PEN, PHP, PHP, PKR, PLN, PYG, QAR, RON, RUB, SAR, SEK, SGD, THB, TRY, TWD, UAH, USD, VES, VND, ZAR|enum(string)|✅|||
|value|Value of the order or items sold. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li><strong>Price</strong> is the price for a single item, and <strong>value</strong> is the total price of the order. For example, if you have two items each sold for ten dollars, the `price` parameter would pass `10.00` and the `value` parameter would pass `20.00`.</li><li>Values should always be formatted as an integer or decimal (ex: 10.00) regardless of the location, currency, etc. It cannot contain any currency signs, special characters, letters, or commas.</li></ul>|number|✅||✅|
|num_items|The quantity of items.|integer| | ||
|search_string|The user-entered string for search.|string| | ||
|description|Description of the item or page.|string| | ||
|status|Status of an order, item, or service. <br><p><span style="color:darkred"><b>Note</b></span>: Depending on your use of `status`, Events API may be required in order to share status changes to TikTok.|string| | ||
```

## Adding parameters to event code
You can use `ttq.track(event, [parameters]); `for parameters. Below are the code examples for Add to Cart event for **single** product and **multiple** products.

```xcodeblock
(code Javascript javascript)
//taking Add to Cart event as an example
// For single product 
ttq.track('AddToCart', {
   contents: [
    {
      content_id: '301',
      content_name: 'dress',
	  quantity: 1,
      price: 8,
    }],
    content_type: 'product',
    value: 8.0,
    currency: 'USD',
});

// For multiple products  
ttq.track('AddToCart', {
   contents: [
    {
      content_id: '301',
      content_name: 'dress',
	  quantity: 1,
      price: 8,
    },
    {
      content_id: '401',
      content_name: 'dress',
	  quantity: 1,
      price: 1.2,
    }],
	content_type: 'product',
    value: 9.2,
    currency: 'USD',
});
(/code)
```

## Using dynamic values in your parameters
You can set up dynamic values which pass dynamic event-related values to TikTok. By doing so, this dynamic code will automatically determine the correct values of your event, such as the price of purchase, instead of using a set value.

For example, if a visitor lands on your website and adds a book to their cart. This is what static or set value will look like:

```xcodeblock
(code Javascript html)
ttq.track('AddToCart', {
   contents: [
    {
      content_id: '301',
      content_name: 'book',
	  quantity: 1,
      price: 8,
    }],
    content_type:'product',
    value: 8.0,
    currency: 'USD',
});
(/code)
```
To make these values dynamic, replace set values with code which will pass correct values like 'book' or '10.0' automatically.
```xcodeblock
(code Javascript html)
ttq.track('AddToCart', {
   contents: [
    {
      content_id: DYNAMIC_CONTENT_ID_COMES_HERE,
      content_name: DYNAMIC_PRODUCT_NAME_COMES_HERE,
	  quantity: DYNAMIC_QUANTITY_COMES_HERE,
      price: DYNAMIC_PRICE_COMES_HERE,
    }],
    content_type:'product',
    value: DYNAMIC_ORDER_VALUE_COMES_HERE,
    currency: 'USD',
});
(/code)
```

# Disable the pixel when a user opts out

If a user declines or opts out of being measured, ensure your website has a protocol or code in place to disable the pixel from firing. Once the pixel is disabled, any information such as cookies, timestamps, and events will not be measured. If you're not sure how to code this on your website, here are some suggestions we recommend:
* If you're using Google Tag Manager, you can use [Google's Consent Mode](https://blog.google/products/marketingplatform/360/measure-conversions-while-respecting-user-consent-choices/) to help you customize how Google tags behave before and after users make their consent decisions.
* If you don't have technical resources or a tag manager, TikTok Pixel integrates with many third-party consent management platforms. These platforms may also offer technical resources and implementation options, including banner design and more.
* If you have technical resources and prefer not to rely on third-party consent management platforms, TikTok offers [Pixel Cookie Consent Mode](https://business-api.tiktok.com/portal/docs?id=1795929012554754) to control the cookie usage of pixels.


# Verify your pixel and event setup
After you finish the set up, there are multiple ways to check whether pixel is working properly:
1. Install the Pixel Helper chrome extension
2. Use our Test Events & Diagnostics tools in Events Manager
3. Preview an ad
4. Use debug mode


## 1. Pixel Helper
Pixel Helper is a Chrome extension that can help you verify and troubleshoot pixel installation by checking for errors and providing implementation recommendations for your website.
With Pixel Helper, you can troubleshoot pixel installation with detailed diagnostics at all levels, including pixel, event and in some cases, parameter implementation. [Learn how to use Pixel Helper](https://ads.tiktok.com/help/article?aid=9661).
 Pixel Helper provides you with many core benefits including the following:
1. **Pixel status**: Verify pixel implementation with quick access to installation details at the pixel, event and in some cases, parameter level. 
2. **Detailed diagnostics**: Troubleshoot common setup issues and improve pixel performance by reviewing error messages and implementation recommendations for your pixel. 
Pixel Helper is available now. [Download Pixel Helper here](https://chrome.google.com/webstore/detail/tiktok-pixel-helper/aelgobmabdmlfmiblddjfnjodalhidnn).


## 2. Test Events and Diagnostics
In TikTok Ads Manager, navigate to Events Manager by clicking **Tools** > **Events** > **Data sources**. Select a Pixel or Events API connection to reach the details page which you will see five tabs:
- **Overview**: Verify web data connection setup or monitor activity with quick access to web data connection details. Event Status indicates whether a pixel event is 'Active' or if there is 'No recent activity' while Last Received shows when an event was last recorded. 
- **Test Events**: Test web events in real-time using a live test environment that mimics how your website will show up in the TikTok app.  After scanning the QR code (pixel) or sending the test_event payload (Events API), you can verify events are configured and triggered as expected on your website. 
- **Diagnostics**: Troubleshoot common issues and improve pixel performance by fixing errors and implementing the recommended solutions. Diagnostics can be accessed through the Web Events details page under the 'Diagnostics' tab. 
- **Change Log**: View any previous changes made to the web data connection or configured events. The change log helps with troubleshooting by pinpointing exactly what changes were made, when they were made, and by whom.
- **Settings**: View and manage your TikTok Pixel's settings, including Automatic Advanced Matching, cookie usage, data postback enhancements, event configuration, access token generation for the Events API, and integration with partners.

Learn how to use our Diagnostics tools by referencing our [Help Center](https://ads.tiktok.com/help/article?aid=10000360).



## 3. Debug Mode
If you require more details as you debug your pixel setting, debug mode is here to help you. 

- There are two ways to open debug mode:
	1. Open the [Sdk Link](https://analytics.tiktok.com/i18n/pixel/events.js), and run `document.cookie="pixel_debug=true; expires=Thu, 18 Dec 2043 12:00:00 GMT; path=/; SameSite=None; Secure";`
	2. Refresh your website	
- Check debug messages
Please open the console of your browser first. Then our Tiktok Pixel will report debug logs in console. The logs information will start with `[TT]`. For example：


**Logs of some key nodes**
- When you call `ttq.load()`

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7c24f65c7b8fcaa14b035b5917dc9ae5.png" width="800pt" />

- When you call `ttq.identify()` or `ttq.instance().identify()`

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8f13e009a36f1d69d9004afa7270e0ae.png" width="800pt" />

- When you call `ttq.page()` or `ttq.track()` or `ttq.instance().track()`. Before report one event, we will console `adInfo` and `appInfo`

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8f518029f6e2c516dcf42d201fa80635.png" width="800pt" />


- When we get info from cache or url or bridge

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/31e456ff3a3b49254997581db0807cce.png" width="800pt" />

# FAQs

**How do I set up multiple pixels on my website?**

You can follow the sample code below for an example of 3 pixel base codes. Remember to replace the placeholders with your pixel ID before using the sample code.

```xcodeblock
(code Javascript html)
<script>
!function (w, d, t) {
  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
}(window, document, 'ttq');

ttq.load('BRU69P41M9ALI4R4B2QG');
ttq.page();
</script>

<script>
!function (w, d, t) {
  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
}(window, document, 'ttq');

ttq.load('pixel_id_2');
ttq.page();
</script>

<script>
!function (w, d, t) {
  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
}(window, document, 'ttq');

ttq.load('pixel_id_3');
ttq.page();
</script>
(/code)
```

**How do I set up events if I have multiple pixels on my website?**
There are 2 event setup scenarios to consider if you have multiple pixels:

1. Each pixel reports a different event
2. All pixels report the same events
Refer to the sample codes below to see how each scenario is setup.

**Scenario 1: Each pixel reports different events**

```xcodeblock
(code Javascript html)
<script>
function () {
/* xxxxx */

ttq.load('pixel_id_1')
ttq.page()
}()
</script>

<script>
function () {
/* xxxxx */ 

ttq.load('pixel_id_2')
ttq.page()
}()
</script>
<script>
function () {
/* xxxxx */

ttq.load('pixel_id_3')
ttq.page()
}()
</script>
ttq.instance('pixel_id_1').track('Purchase')
ttq.instance('pixel_id_2').track('Event 2')
ttq.instance('pixel_id_3').track('Event 3')
(/code)
```

**Scenario 2: All pixels report the same events**

```xcodeblock
(code Javascript html)
<script>
function () {
/* xxxxx */

ttq.load('pixel_id_1')
ttq.page()
}()
</script>

<script>
function () {
/* xxxxx */

ttq.load('pixel_id_2')
ttq.page()
}()
</script>

<script>
function () {
/* xxxxx */

ttq.load('pixel_id_3')
ttq.page()
}()
</script>

ttq.track('Purchase') // it will report three Purchase events for pixel_1 and pixel_2 and pixel_3
(/code)
```

In this situation, `ttq.track('Purchase')`is same as the following lines:

```xcodeblock
(code Javascript javascript)
ttq.instance('pixel_id_1').track('Purchase')
ttq.instance('pixel_id_2').track('Purchase')
ttq.instance('pixel_id_3').track('Purchase')
(/code)
```

Because `ttq.track() `will report all the installed Pixels' events.
