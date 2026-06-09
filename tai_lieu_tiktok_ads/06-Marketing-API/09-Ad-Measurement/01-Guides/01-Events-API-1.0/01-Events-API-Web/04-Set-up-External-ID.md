---
title: "Set up External ID"
doc_id: 1765942670613506
path: "Marketing API / Ad Measurement / Guides / Events API 1.0 / Events API : Web / Set up External ID"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Set up External ID

External ID is an identifier that represents a user on an advertiser’s platform, such as a customer ID, loyalty card number or order ID. When shared, TikTok will try to match the ID with a user on TikTok. This increases match rates, ultimately leading to downstream benefits in measurement and optimization.  

External ID can be sent to TikTok using one of the following TikTok for Business tools: Pixel SDK or Events API. 

# Benefits
If you assign an ID to users visiting your site, External ID allows you to: 
- **Improve signal performance**: Improve match rates across channels and capture more events.
- **Boost campaign optimization**: Power campaign bidding models to amplify conversion events.
- **Measure full-funnel events**: Capture more events with complete cross-device and cross-session matching. 

> <span style="color:DodgerBlue">**Note**</span><br>
> In order to maximize the benefits of External ID, it is recommended to also have Manual and Automatic Matching set up. For Advanced Matching instructions for Pixel SDK, see [here](https://ads.tiktok.com/marketing_api/docs?id=1739585700402178). 

# How Does External ID Work 
1. A user views a specific product on your website 
2. You send us a ViewContent event via browser Pixel containing:
  - Hashed `external id` for that user
  - Either or both of the below:
    - Hashed customer PII (email/phone)
    - Cookie information which is automatically included with all pixels
3. Within TikTok, we use the hashed PII associated with the external ID to find a match. If we find a match, we will associate the provided external ID with the TikTok user
4. A couple of days later, that same user completes a purchase on your website.
5. You send us a new event. This time, you use Events API to share the event. In the event, you include `external_id`.
6. We get your `external_id`. We're able to match this signal with the customer's prior browsing activity from the previous PII to TikTok user match.
7. The signals can then be used downstream to improve your campaign performance.

# Getting Started with External ID
## Set up External ID and Advanced Matching for Pixel SDK
### Before you begin
- Technical resource is required and you must have access to the website's code.
- You should have events set up in the website's code at this point. If not, refer to [the developer documentation for setting up events](https://ads.tiktok.com/marketing_api/docs?id=1739585702922241).
- It is also recommended to have Manual and Automatic Advanced Matching setup to benefit from External ID

### Steps
1. Navigate to the event (or event code) you want to implement External ID with. Here's an example of an 'Add to Cart' event which includes parameters:
```xcodeblock
(code curl http)
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
(/code)
```

2. Use `ttq.identify` as the start of External ID code, similar to Advanced Matching where you can

a. Pass the raw value and we will hash it on the client side
```xcodeblock
(code curl http)
ttq.identify({
	  email: 'email@example.com',
	  phone_number: '+15551234567',
	  external_id: '123456',
})
(/code)
```
b. Pass the hashed value. Be sure to trim any leading/trailing spaces before hashing.
 
```xcodeblock
(code curl http)
ttq.identify({
	  email: '0c7e6a405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b', 
	  phone_number: '9f7ec22d72092cd3c0b58726ed9c2d91b92e51a3f29837508fb2948bb22dd2fd',
	  external_id: '936a185caaa266bb9cbe981e9e05cb78cd732b0b3280eb944412bb6f8f8f07af',
})
(/code)
```
**Important notes for passing hashed values**:
Advanced Matching and External ID 
- Leading/Trailing spaces need to be trimmed before hashing
- `ttq.identify` code should be placed right above the event code(s). This ensures that when the event is triggered, the event can pick up Advanced Matching and External ID data.

See below for a sample event.

```xcodeblock
(code curl http)
// Always Advanced Matching code before event code, see below for a sample event. 
// Repeat for each page where event(s)take place. 

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
4. Continue this process (steps 1-3) for **each page where you have event code** on your website
5. Once you've added Advanced Matching and External ID code to your events, use the [Pixel Helper chrome extension](https://chrome.google.com/webstore/detail/tiktok-pixel-helper/aelgobmabdmlfmiblddjfnjodalhidnn) to check if the installation was successful. Here's how to check:
  	1. Install Pixel Helper and then go to your website
  	2. Test each event as if you are a customer as each event detected will populate in Pixel Helper
  	3. Open event details for each event and look for a 'green check' which means no issues were detected with this event
  	4. Go to the **Parameter Details** section under event details
  	5. Look for **Advanced Matching** and check that it displays  **email**, **phone**, and **external_id**. This means our system has detected valid Advanced Matching code for email, phone number, and external_id.
 
Learn more about [Pixel Helper](https://ads.tiktok.com/help/article/tiktok-pixel-helper-2.0?redirected=2).
 
### Pixel identity information supported

``` xtable
| user_properties{22%} | Description {25%}| Formatting Requirements {28%} | Contents example {25%} |
|---|---|---|---|
| email | The email of the customer if available | Unhashed or lowercase and hashed SHA-256.<br><br>For unhashed value:<ul><li>TikTok will automatically hash value with sha256 before value enters our system</li></ul><br>For hashed SHA-256 format:<ul><li>Trim any leading and trailing spaces, and lowercase all characters before hashing.</li><li>Do not perform any other types of normalization on emails prior to hashing.</li><li>Only SHA-256 is accepted. Here is the recommended package: [GitHub - emn178/js-sha256: A simple SHA-256 / SHA-224 hash function for JavaScript supports UTF-8 encoding](https://github.com/emn178/js-sha256).</li></ul> | `email@example.com` |
| phone_number | The phone number of the customer if available|Unhashed or hashed SHA-256.<br><br>Normalize your phone numbers to E.164 format, a combination of + &#91;country code&#93; &#91;phone number&#93;. An example of a US number in the E.164 format: `+12133734253`<br>Recommend using [https://github.com/catamphetamine/libphonenumber-js](https://github.com/catamphetamine/libphonenumber-js) for E.164 parsing.<br><br>For unhashed value:<ul><li>TikTok will automatically hash value with sha256 before value enters our system.</li></ul>For hashed SHA-256 format: <ul><li>Only SHA-256 is accepted. Here is the recommended package: [GitHub - emn178/js-sha256: A simple SHA-256 / SHA-224 hash function for JavaScript supports UTF-8 encoding](https://github.com/emn178/js-sha256).</li></ul>| `+12133734253` |
| external_id | Unique ID from the advertiser, such as loyalty membership ID, user ID, and external cookie ID. |Unhashed or hashed SHA-256.<br><br>Trim any leading and trailing spaces before hashing.<br><br>For hashed SHA-256 format: <ul><li>Only SHA-256 is accepted. Here is the recommended package: [GitHub - emn178/js-sha256: A simple SHA-256 / SHA-224 hash function for JavaScript supports UTF-8 encoding](https://github.com/emn178/js-sha256).</li></ul>||
```

## Set up External ID and Advanced Matching for Events API
- Visit [Events API developer documentation](https://ads.tiktok.com/marketing_api/docs?id=1741601162187777) on how to set up External ID with Events API. 

# Managing External ID
If you are sharing `external_id` via TikTok Pixel SDK or Events API and would not like TikTok to use the parameter for matching or measurement purposes, please update your setup to remove the `external_id` field. 
