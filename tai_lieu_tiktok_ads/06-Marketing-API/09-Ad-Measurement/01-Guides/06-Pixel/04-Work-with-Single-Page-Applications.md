---
title: "Work with Single Page Applications"
doc_id: 1801524003169281
path: "Marketing API / Ad Measurement / Guides / Pixel / Work with Single Page Applications"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Work with Single Page Applications

A Single Page Application (SPA) is a type of web application that loads a single HTML page and dynamically updates the content as you interact with it, making it faster and more responsive. 

After Pixel base code has been installed on your webpage, you can configure how the TikTok Pixel behaves with historical state changes on your SPA pages.

## Default setting
By default, the TikTok Pixel will report a pageview event when users land on your SPA website. Whenever the browser state is changed and the web URL is updated, the TikTok Pixel will measure an additional pageview representing a view on the new page URL.

## Alternative setting
If you don't want the TikTok Pixel to tell when users visit a specific URL under an SPA website, you have the option to disable the TikTok Pixel from measuring additional pageview upon webpage content changes, though it is not recommended. 

To do so, you can set `historyObserver` as `false` when loading the TikTok Pixel.

```xcodeblock
(code Javascript javascript)
<script>
!function (w, d, t) {
  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};

  ttq.load("<pixel_code>", {
    historyObserver: false, // stop measuring pageview events upon URL changes, but not recommended.
  });
  
  ttq.page();
}(window, document, 'ttq');
</script>
(/code)
```

> <span style="color:DodgerBlue">**Note**</span><br>
> If you install the TikTok Pixel using Google Tag Manager on TikTok's Events Manager, you can find and modify the Pixel base code under tag `TT-<pixel_code>-Web-Tag-Pixel_Setup`. 

**Learn More**
- Visit [Google's Tag Manager documentation](https://marketingplatform.google.com/about/tag-manager/?fbclid=IwZXh0bgNhZW0CMTAAAR0XN1HDBKem46UEcy7xGW63k6gN3eAdMahRdqJVLmzsF16_aHesrv3qSVk_aem_AQT9UxcZSp_qB5sEEfMNkymHJkay46Ie1rTzsQXS2WIwFkXUakLgBHz33z3zmD5PJHMq_-6SWjW1XNfonadNjvq4) to measure events using a Tag Manager
- Debug using the [TikTok Pixel Helper](https://chromewebstore.google.com/detail/tiktok-pixel-helper/aelgobmabdmlfmiblddjfnjodalhidnn) to see event data connection
