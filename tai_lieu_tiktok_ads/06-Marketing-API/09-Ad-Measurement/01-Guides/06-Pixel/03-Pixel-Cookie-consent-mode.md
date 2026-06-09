---
title: "Pixel Cookie consent mode"
doc_id: 1795929012554754
path: "Marketing API / Ad Measurement / Guides / Pixel / Pixel Cookie consent mode"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Pixel Cookie consent mode

TikTok's pixel consent mode offers two solutions:
 - Default opt-out solution: This solution will prevent pixels from sharing data with TikTok until user consent is explicitly granted.
 - Default opt-in solution: This solution will allow pixels to share data with TikTok until user consent is explicitly revoked.

# Default opt-out solution
 1. In pixel base code, add "holdConsent", "revokeConsent" and "grantConsent" in the `ttq.methods` array.
 2. Call `ttq.holdConsent()` before loading the pixel via `ttq.load()`. This will queue events but not share them until the user consent status changes.
 3. Based on the user's preference:
- If users grant consent, call `ttq.grantConsent()`. This will share events to the TikTok server, including events that were previously queued in the session.  
- If users reject consent, call `ttq.revokeConsent()`. This will prevent events from being shared to the TikTok server, including previously queued events in that session.
 4. If users change their preference, call `ttq.grantConsent()` or `ttq.revokeConsent()` as applicable to either enable or disable data sharing via the pixel. Calling `ttq.revokeConsent()` does not affect data shared with the TikTok server.      

> <span style="color:DodgerBlue">**Note**</span><br>
> Where applicable, advertisers should implement this solution on every webpage where they've installed the TikTok pixel.

**Use case 1: Websites default to opt-out before users make an explicit selection**

```xcodeblock
(code Javascript javascript)
<script>
 // Add the new methods "holdConsent", "revokeConsent", and "grantConsent" to the ttq.methods array
!function (w, d, t) {
  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};

  // Hold consent before 'ttq.load' is called
  ttq.holdConsent();
  ttq.load('YOUR PIXEL ID WILL BE LOCATED HERE');
  ttq.page();
}(window, document, 'ttq');
</script>

<script>
  function accept
<script>    
(/code)
```

``` xcodeblock
(code)
// Once affirmative consent has been denied.
<span style="background-color: green;">ttq.revokeConsent(); </span>
(/code)
```

``` xcodeblock
(code)
// Once affirmative consent has been granted
<span style="background-color: green;">ttq.grantConsent(); </span>
(/code)
```
# Default opt-in solution
 1. In pixel base code, add "revokeConsent" and "grantConsent" in the `ttq.methods` array.
 2. Call `ttq.load()` to initialize the pixel. All events will be sent to the TikTok server by default.
 3. Based on the user's preference:
- If users grant consent, call `ttq.grantConsent()`. This allows events triggered by users to continuously be sent to the TikTok server.
- If users reject consent, call `ttq.revokeConsent()`. This prevents future events from being sent to the TikTok server.
 4. If users change their preference, call `ttq.grantConsent()` or `ttq.revokeConsent()` as applicable to either enable or disable data sharing via the pixel. Calling `ttq.revokeConsent()` does not affect data shared with the TikTok server.       

> <span style="color:DodgerBlue">**Note**</span><br>
> Where applicable, advertisers should implement this solution on every webpage where they've installed the TikTok pixel.

**Use case 2: Websites default to opt-in and users explicitly reject cookies, then accept them later**


``` xcodeblock
(code Javascript javascript)
<script>
 // Add the new methods "revokeConsent" and "grantConsent" to the ttq.methods array
!function (w, d, t) {
  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};

  ttq.load('YOUR PIXEL ID WILL BE LOCATED HERE');
  ttq.page();
}(window, document, 'ttq');
</script>
(/code)
```

``` xcodeblock
(code)
// Users explicitly reject cookies
<span style="background-color: green;">ttq.revokeConsent() </span>

// After a certain period
// ...

// Users accept them later
<span style="background-color: green;">ttq.grantConsent() </span>
(/code)
```
