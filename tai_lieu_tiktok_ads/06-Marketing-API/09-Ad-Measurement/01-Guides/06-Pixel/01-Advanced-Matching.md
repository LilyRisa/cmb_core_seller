---
title: "Advanced Matching"
doc_id: 1739585700402178
path: "Marketing API / Ad Measurement / Guides / Pixel / Advanced Matching"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Advanced Matching


## About Advanced Matching
Advanced Matching helps you optimize your TikTok ads and drive performance by matching customer information with people on TikTok. Hashed customer information can be shared with any TikTok event to attribute more conversions, build bigger audiences and improve campaign optimization. 
 
There are two ways to enable Advanced Matching: manual or automatic.
 
- <strong>Manual Advanced Matching</strong> is the passing of customer information to TikTok from your website. This can be implemented using code for each event on your website or if you're using a partner integration, you can enable Manual Advanced Matching in the data sharing settings of the partner platform. With this option, you have the flexibility to configure what information and for which event you want to pass to TikTok.
- <strong>Automatic Advanced Matching</strong> automatically identifies customer information such as email, phone number, and other identifiers from form inputs, static text displayed on your site, and key website variables (e.g., window.dataLayer, window.utag). The information is hashed in the browser using an industry-standard algorithm (SHA-256) before being sent securely to TikTok for ad measurement and attribution purposes.
 
<strong>We recommend advertisers use both Manual and Automatic Advanced Matching at the same time, as this setting maximizes Advanced Matching's full performance. </strong>

<strong>This guide is intended for implementing Manual Advanced Matching with the TikTok Pixel. To turn on Automatic Advanced Matching, you can do so in Events Manager settings. Learn more about Automatic Advanced Matching and how to turn it on in our [help center](https://ads.tiktok.com/help/article/advanced-matching-web?lang=en). </strong>
  
### Privacy
As per standard industry practice, customer emails and phone numbers will be hashed with SHA256 before reaching TikTok servers for matching. TikTok is ISO 27001 certified in the US and UK. Learn more about how this information is used in our [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms).



## Get started with Manual Advanced Matching
### Before you begin
- Technical resource is required for Manual Advanced Matching implementation.
- You must have access to the website's code.
- You must have ability to add privacy-safe audience data to parameters.
- You should have events set up in the website's code at this point. If not, refer to [Install Pixel using code](https://ads.tiktok.com/marketing_api/docs?id=1739585702922241) to learn about how to set up events.

1. Navigate to the event (or event code) you want to implement Advanced Matching with. Here's an example of an 'Add to Cart' event which includes parameters:

```xcodeblock
(code JavaScript JavaScript)
ttq.track('AddToCart', {
    contents: [
    {
      content_id: '301',
      content_name: 'running shoes',
      content_category: 'Shoes > Sneakers > running shoes',
      brand: 'your brand name',
      quantity: 1,
      price: 8,
    }],
    content_type: 'product',
    value: 9.2,
    currency: 'USD',  
});
(/code)
```

2. Use `ttq.identify` as the start of the Advanced Matching code before the event code e.g. `ttq.track`.
3. Under the `ttq.identify` function, there are two ways to pass email and phone number using the parameters `email` and `phone_number`:

- a. Pass the raw values and we will hash them on the client side

```xcodeblock
(code JavaScript JavaScript)
ttq.identify({
    email: 'email@example.com',
    phone_number: '+15551234567',
})
(/code)
```
- **Important notes for passing original non-hashed values**
	- Phone Numbers
		- Normalize your phone numbers to E164 format, a combination of +{country code}{phone number}.
		- Recommend using [https://github.com/catamphetamine/libphonenumber-js](https://github.com/catamphetamine/libphonenumber-js) for E164 parsing.<br>

- b. Pass the hashed values
  
```xcodeblock
 (code JavaScript JavaScript)
ttq.identify({
    email: '0c7e6a405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b', 
    phone_number: '9f7ec22d72092cd3c0b58726ed9c2d91b92e51a3f29837508fb2948bb22dd2fd',
    external_id: '936a185caaa266bb9cbe981e9e05cb78cd732b0b3280eb944412bb6f8f8f07af',
})
(/code)
```

- **Important notes for passing hashed values:**
	- Emails
		- Must reduce all letters into lowercase prior to hashing.
	    - Leading/Trailing spaces need to be trimmed before hashing.
		- Do not perform any other types of normalization on emails prior to hashing.
		- Only sha-256 is accepted. Here is the recommended package [https://github.com/emn178/js-sha256](https://github.com/emn178/js-sha256).
	- Phone Numbers
		- Normalize your phone numbers to E164 format, a combination of+{country code}{phone number}.
		- Recommend using [https://github.com/catamphetamine/libphonenumber-js](https://github.com/catamphetamine/libphonenumber-js) for E164 parsing.
		- Only sha-256 is accepted. Here is the recommended package [https://github.com/emn178/js-sha256](https://github.com/emn178/js-sha256).
	- External IDs
		- Leading/Trailing spaces need to be trimmed before hashing.
4. Place the Advanced Matching code right above the event code(s). This ensures that when the event is triggered, the event can pick up the Advanced Matching data.

```xcodeblock
(code JavaScript JavaScript)
//Always Advanced Matching code before event code, see below for a sample event.
//Repeat for each page where event(s)take place.

ttq.identify({
    email: '0c7e6a405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b',
    phone_number: '9f7ec22d72092cd3c0b58726ed9c2d91b92e51a3f29837508fb2948bb22dd2fd',
    external_id: '936a185caaa266bb9cbe981e9e05cb78cd732b0b3280eb944412bb6f8f8f07af',
})

ttq.track('ViewContent', {
    contents: [
    {
      content_id: '301',
      content_name: 'running shoes',
      content_category: 'Shoes > Sneakers > running shoes',
      brand: 'your brand name',
      quantity: 1,
      price: 8,
    }],
    content_type: 'product',
    value: 9.2,
    currency: 'USD',  
});

ttq.track('AddToCart', {
    contents: [
    {
      content_id: '301',
      content_name: 'running shoes',
      content_category: 'Shoes > Sneakers > running shoes',
      brand: 'your brand name',
      quantity: 1,
      price: 8,
    }],
    content_type: 'product',
    value: 9.2,
    currency: 'USD', 
});
(/code)
```

5. Continue this process (steps 1-4) for <strong>each page where you have event code</strong> on your website.
6. Once you've added Advanced Matching code to all your events, use the [Pixel Helper chrome extension](https://chrome.google.com/webstore/detail/tiktok-pixel-helper/aelgobmabdmlfmiblddjfnjodalhidnn) to check if the installation was successful. Here's how to check:
  	1. Install Pixel Helper and then go to your website
  	2. Test each event as if you are a customer as each event detected will populate in Pixel Helper
  	3. Open event details for each event and look for a 'green check' which means no issues were detected with this event
  	4. Go to the **Parameter Details** section under event details
  	5. Look for **Advanced Matching** and check that it displays both **email** and **phone**. This means our system has detected valid Advanced Matching code for both email and phone number.

To learn more about Pixel Helper, see [TikTok Pixel Helper](https://ads.tiktok.com/help/article?aid=9661).

### Identity information supported

``` xtable
| user_properties{22%} | Formatting Requirements {38%} | Contents example {15%} |
|---|---|---|---|
| email |  Unhashed or lowercase and hashed SHA-256.<br><br>For unhashed value:<ul><li>TikTok will automatically hash value with sha256 before value enters our system.</li></ul><br>For hashed SHA-256 format:<ul><li>Trim any leading and trailing spaces, and lowercase all characters before hashing.</li><li>Do not perform any other types of normalization on emails prior to hashing.</li><li>Only SHA-256 is accepted. Here is the recommended package: [GitHub - emn178/js-sha256: A simple SHA-256 / SHA-224 hash function for JavaScript supports UTF-8 encoding](https://github.com/emn178/js-sha256)</li></ul> | `email@example.com` |
| phone_number | Unhashed or hashed SHA-256.<br><br><ul><li>Normalize your phone numbers to E.164 format, a combination of + ［country code]［phone number]. An example of a US number in the E.164 format: +12133734253</li><li>Recommend using [https://github.com/catamphetamine/libphonenumber-js](https://github.com/catamphetamine/libphonenumber-js) for E.164 parsing.</li></ul><br>For unhashed value:<ul><li>TikTok will automatically hash value with sha256 before value enters our system.</li></ul><br>For hashed SHA-256 format:<ul><li>Only SHA-256 is accepted. Here is the recommended package: [GitHub - emn178/js-sha256: A simple SHA-256 / SHA-224 hash function for JavaScript supports UTF-8 encoding](https://github.com/emn178/js-sha256)</li></ul> | `+15551234567` |
| external_id <br><br>Unique ID from the advertiser, such as loyalty membership ID, user ID, and external cookie ID.|Unhashed or hashed SHA-256.<br><br>Trim any leading and trailing spaces before hashing and ensure you are consistent with the External ID used.<br><br>For hashed SHA-256 format: <ul><li>Only SHA-256 is accepted. Here is the recommended package: [GitHub - emn178/js-sha256: A simple SHA-256 / SHA-224 hash function for JavaScript supports UTF-8 encoding](https://github.com/emn178/js-sha256)</li></ul>||
```
  
### Best practices

There are a few best practices to keep in mind when implementing Advanced Matching:
- <strong>Use both Manual and Automatic Advanced Matching if possible. </strong> Using both Manual and Automatic Advanced Matching ensures you're receiving maximum benefits from the solution. The manual version involves coding, so you may need developer resources. Learn how to set up Manual Advanced Matching [here](https://ads.tiktok.com/marketing_api/docs?id=1739585700402178).
- <strong>Maximize the benefits of Advanced Matching with Cookies. </strong>We recommend that both Auto and Manual AM solutions are paired with cookies for optimal performance. Ensure first-party cookies are enabled via Events Manager settings. Read [Using Cookies with TikTok Pixel](https://ads.tiktok.com/help/article/using-cookies-with-tiktok-pixel?redirected=1) to learn more.
- For Manual Advanced Matching, we encourage you to send both email and phone numbers for all visitor events. We also recommend capturing visitors’ email and phone number early in the funnel for best results. This increases the probability of matching customers with people on TikTok. Here are some additional best practices to keep in mind when implementing Advanced Matching:<br>

<table>
<thead>
  <tr>
    <th>Manual Advanced Matching (MAM)</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td style="vertical-align:top"><ol><li>Send email and phone number for <strong>every event</strong> on your website or <strong>as frequently as possible</strong>.</li><li>Ensure you use dynamic code in order to pass dynamic event-related values or information to TikTok. Avoid invalid value or a static (hardcoded) value in your Advanced Matching code.</li><li>Pass empty string when customer info is not available rather than using blank spaces, "undefined", or other hardcoded values.</li><li>Double check you are using the right Advanced Matching parameters when passing email and phone numbers.</li><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ed8795d2c1944a20807e86cba5214fbf" width="60%" style="color:#808080"/><br>Note TikTok will do the hashing should you choose to share the raw customer info. TikTok only accepts SHA256 should you choose to hash your email and/or phone number prior to sharing with us.<br><br></td>
  </tr>
</tbody>
</table>

