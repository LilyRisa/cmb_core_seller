---
title: "Send TikTok Click ID (ttclid)"
doc_id: 1771100879787009
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for Web / Send TikTok Click ID (ttclid)"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Send TikTok Click ID (ttclid)

# TikTok Click ID (`ttclid`)
TikTok Click ID (`ttclid`) is a data connetion parameter that gets appended to your landing page URL when a user clicks on your TikTok ad. Here's an example of a URL with a `ttclid` parameter: `https://www.example.com/landing?ttclid=E.C.P.v3fQ2RHacdksKfofPmlyuStIIHJ4Af1tKYxF9zz2c2PLx1Oaw15oHpcfl5AH`.

It is **strongly recommended** to send back the `ttclid` via Events API 2.0 for all events from your website. Doing so helps TikTok accurately associate events on your website with the ad click and allows TikTok to better report and optimize your campaign performance.

For details of TikTok Click ID, see [TikTok Click ID](https://ads.tiktok.com/help/article/tiktok-click-id?redirected=2).

# Store the `ttclid`
TikTok only appends the `ttclid` parameter to the landing page URL of an ad. It is recommended to hold on to the `ttclid` for the entire session of a user's visit to your website, so you can send it via Events API for all events during the session. There are three options for storing the `ttclid`:
- Keep the `ttclid` parameter in the URL as the website redirects to different pages (for instance, from landing page to a product page), and send the full URL (with URL parameters) in the page.url field of the API payload. The API backend will parse the Click ID if it detects the presence of a `ttclid` parameter in the page.url.
- If you have already installed a TikTok Pixel on your website, the Pixel will parse the `ttclid` from the landing page URL and store its value in a cookie. You can read the cookie value when you send each Events API request. For more details, refer to the "<a href="#item-link-Get Click ID with TikTok Pixel">Get Click ID with TikTok Pixel</a>" section below.
- You can also parse the `ttclid` from the landing page URL on your own, and store it using browser cookie storage or session storage. For more details, refer to the "<a href="#item-link-Store the Click ID on your own">Store the Click ID on your own</a>" section below.

> <span style="color: DodgerBlue">**Note**</span><br>It's highly recommended to implement at least one of the above three options. 

If you have the extra engineering resources, you can also store the <code>ttclid</code> on your backend for cross-browser or device association. For more details on how to implement this, refer to the "<a href="#item-link-Cross-browser or device storage (optional)">Cross-browser or device storage</a>" section below.

## Get Click ID with TikTok Pixel 
If you've installed the TikTok Pixel on your website, it will automatically parse the `ttclid` from the landing page URL and store its value as a first-party cookie under your website domain. The cookie's name is also `ttclid`.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/da4fce5d7eb446bf49a3d832cb5ae63f" width="90%" style="color:#808080"/>

To retrieve the <code>ttclid</code> on your web server, you can parse the HTTP cookie request header and report it via Events API. For more details, refer to the section "<a href="#item-link-Send the ttclid via Events API">Send the ttclid via Events API</a>" below.

Alternatively, you can retrieve the Click ID by reading the <code>ttclid</code> cookie from the browser's cookie jar on your web client, and send it to your server to report via the Events API. Here's some sample JavaScript code to read the cookie:

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
getCookie('ttclid')// TikTok Click ID
(/code)
```

## Store the Click ID on your own
If you haven't installed the TikTok Pixel on your website, you can parse and store the `ttclid` on your own.  It is recommended to use browser cookie storage or HTML5 local storage.
- The recommended storage TTL (time-to-live) is 28 days or more (TikTok will handle the expiration of Click ID based on the advertiser's attribution window settings). 
- If a new `ttclid` is detected on your landing page URL, you should refresh the stored value to the latest seen value. 

Here's some sample code using cookie storage (**recommended**):

```xcodeblock
(code curl http)
// Get the ttclid parameter from the URL
const urlSearchParams = new URLSearchParams(window.location.search);
const ttclidValue = urlSearchParams.get('ttclid');
if (ttclidValue) {
  // Set the cookie with the ttclid value and a 28-day expiration date
  expirationDate.setDate(new Date().getDate() + 28);
  // save the value in a cookie named ttclid
  document.cookie = `ttclid=${ttclidParam}; expires=${expirationDate.toUTCString()}; path=/`;
}
(/code)
```
Here's some sample code using HTML5 local storage:
```xcodeblock
(code curl http)
// Get the ttclid parameter value from the URL
const urlParams = new URLSearchParams(window.location.search);
const ttclidValue = urlParams.get('ttclid');

// Save ttclid to local storage with expiration of 28 days
if (ttclidValue) {
  let expirationDate = new Date(new Date().getDate() + 28)
    let data = {
      value: ttclid,
      expirationDate: expirationDate.toISOString()
  }
  // save the value in local storage with key='ttclid'
  localStorage.setItem('ttclid', data);
}
(/code)
```
For each event fired on your website, include the event data and `ttclid` in your server request to report it via the Events API. For more details, refer to the section "<a href="#item-link-Send the ttclid via Events API">Send the ttclid via Events API</a>" below.

## Cross-browser or device storage (optional)
If you want to associate cross-browser or device customer actions with an ad click (for instance, a user clicks on an ad on their laptop and later makes a purchase on their mobile browser), you can store the `ttclid` in your database. Here are the general guidelines:
1. Capture and hold onto the `ttclid` value until you can identify the user:
- To improve your ad performance, hold onto the `ttclid` until you can identify the user internally through their account, subscription, loyalty, or through identifiers such as cookie ID, email, phone number, and anonymous ID.
2. Maintain a user and `ttclid` association in your database:
- Create a table in your backend system that maintains the relationship between the user and the `ttclid` value, such as `ttclid<>email`, `ttclid<>external_id`.
- If the same user clicks on your ads at a different time, a different `ttclid` will be generated. Make sure to override the old `ttclid` value with the new one each time the same user clicks on your ad.
3. Send web events with `ttclid` via the Events API:
- When web events are triggered, you will be required to look up the user-`ttclid` association and pass the `ttclid` value in the API payload. For more details, refer to the section "<a href="#item-link-Send the ttclid via Events API">Send the ttclid via Events API</a>" below.

# Send the `ttclid` via Events API
Report the user's events with `ttclid` inserted into Events API `user.ttclid` field via [/event/track/](https://ads.tiktok.com/marketing_api/docs?id=1771101303285761). For the complete instruction on how to report web events and related parameters, refer to [Events API 2.0 -  Setup guide for Web](https://ads.tiktok.com/marketing_api/docs?id=1771100865818625).
> <span style="color:DodgerBlue">**Note**</span><br>
> `ttclid` can be up to 1,000 characters long, so you need to ensure that you don't truncate it on your server.
```xcodeblock
(code curl http)
  "user": {
  "ttclid": "E.C.P.v3fQ2RHacdksKfofPmlyuStIIHJ4Af1tKYxF9zz2c2PLx1Oaw15oHpcfl5AH",
  // ... ...
},
(/code)
```

 
# Remove `ttclid` from landing page URLs
If you want to remove the `ttclid` from your landing page URLs, you can do so programatically or by editing your website code, similar to the way you manage other query parameters. Here's some JavaScript code to remove all `ttclid` URL parameters:



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
  
