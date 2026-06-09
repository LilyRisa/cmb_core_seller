---
title: "Set up TikTok Click ID and Cookies"
doc_id: 1739584860883969
path: "Marketing API / Ad Measurement / Guides / Events API 1.0 / Events API : Web / Set up TikTok Click ID and Cookies"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Set up TikTok Click ID and Cookies

# Cookies or `_ttp`
When using the TikTok Pixel with first party cookies enabled in your Pixel settings in TikTok Events Manager, TikTok will automatically save a unique identifier in the `_ttp` first party cookie. 

We strongly encourage Events API users to pass back this value. You can extract the value of `_ttp` and attach it to the `ttp` field in Events API payload. The value of this cookie is used to enhance the matching of website visitor events with TikTok ads. For more details of Cookies, see [Using Cookies with TikTok Pixel](https://ads.tiktok.com/help/article?aid=10007540&redirected=1).

Sample payload with `ttp`:
```xcodeblock
(code curl http)
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
         "contents": [
          {
             "price": 8,
             "quantity": 2,
             "content_type": "product_group",
             "content_id": "1077218"
           },
           {
             "price": 30,
             "quantity": 1,
             "content_type": "product_group",
             "content_id": "1197218"
           }
         ],
         "currency": "USD",
         "value": 46.00
     }
 }'
(/code)
```


# TikTok Click ID or `ttclid` 
TikTok Click IDs or `ttclid` are data connection parameters that are auto-attached to your ad's landing page URLs. The request parameter name is `ttclid`. When a TikTok user clicks on an ad and land on your website, a unique ttclid is created and appended to the URL. TikTok Click IDs helps you to match users and attribute event to your TikTok ads accurately. It is strongly encouraged to set up `ttclid` to improve your ads performance. For details of TikTok Click ID, see [TikTok Click ID](https://ads.tiktok.com/help/article?aid=10014664&redirected=1). 


If your website has [TikTok Pixel](https://ads.tiktok.com/help/article?aid=6669727593823993861&redirected=1) installed, the Pixel will automatically capture the `ttclid` parameter and save it to a `ttclid` first-party cookie for your domain. You can access the `ttclid` value from the cookie via the `ttclid` key and attach it to the `context.ad.callback` field in Events API payload.

```xcodeblock
(code curl http)
function getCookie(name) {
  let cookie = {};
  document.cookie.split(';').forEach(function(el) {
    let [k,v] = el.split('=');
    cookie[k.trim()] = v;
  })
  return cookie[name];
}
getCookie('ttclid') // TikTok Click ID
getCookie('_ttp') //Cookie id
(/code)
```

## Single-Session Support

1. Capture the `ttclid` from landing page website.<br>
The click through URL is loaded in TikTok webview and should have `ttclid` appended at the end. You need to programatically capture the `ttclid` in the URL. Follow the steps in [How to Preview Ads](https://ads.tiktok.com/help/article?aid=6716825908595916805) to test your implementation. 
2. Make sure you hold on to the `ttclid` query parameter as the website redirects to different pages. 
3. Report the user's events with `ttclid` inserted in Events API `context.ad.callback` field via [/pixel/track/](https://ads.tiktok.com/marketing_api/docs?id=1740858531237890) or [/pixel/batch/](https://ads.tiktok.com/marketing_api/docs?id=1740858565852225). For the complete instruction on how to report web events and related parameters, see [Report Web Events and Parameters](https://ads.tiktok.com/marketing_api/docs?id=1741601162187777).

```xcodeblock
(code curl http)
  "context": {
    "ad": {
      "callback": "E.C.P.v3fQ2RHacdksKfofPmlyuStIIHJ4Af1tKYxF9zz2c2PLx1Oaw15oHpcfl5AH"
    },
(/code)
```

## Multi-session Support
1. Capture and hold on to the `ttclid` value until you can identify the user:
	1. To improve your ad performance, hold on to the `ttclid` until you can identify the user internally through their account, subscription, loyalty, or through identifiers such as cookie id, email, phone number, anonymous id, etc. 
2. Maintain user and `ttclid` association in your database:
	1. Create a table in your backend system that maintains the relationship between the user and the `ttclid` value. Such as: `ttclid<>email`, `ttclid<>external_id`, etc.
	2. If the same user clicks on your ads at a different time, there will be a different `ttclid` generated. Please make sure to override the old `ttclid` value with the new one.
	3. NOTE: Please be aware that currently, the **`ttclid` can be as long as 1,000 characters.** We recommend choosing a data type that can hold 1,000 chars to ensure the `ttclid` does not get truncated.
3. Send web events with `ttclid` via Events API:
	1. When web events are triggered, you will be required to lookup the user-`ttclid` association and pass the `ttclid` value in the `context.ad.callback` field of [/pixel/track/](https://ads.tiktok.com/marketing_api/docs?id=1740858531237890) or [/pixel/batch/](https://ads.tiktok.com/marketing_api/docs?id=1740858565852225). For the complete instruction on how to report web events and related parameters, see [Report Web Events and Parameters](https://ads.tiktok.com/marketing_api/docs?id=1741601162187777).
  
# Removing `ttclid`
If you wish to remove the `ttclid` from your landing page URLs, you are able to do so programatically or by editing your website code, similarly to the way you manage other query parameters. Please see below a javascript example:
```xcodeblock
(code curl http)
// Add this before pixel tag
<script>
  const myurl = new URL(location.href);  // build a url object from current url
  myurl.searchParams.delete('ttclid');  // remove all ttclid url parameters
  history.replaceState({}, '', myurl);  // replace current url with updated one
</script>
(/code)
```
  
