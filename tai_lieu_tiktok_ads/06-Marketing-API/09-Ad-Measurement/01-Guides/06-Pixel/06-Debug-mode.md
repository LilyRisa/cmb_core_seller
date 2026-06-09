---
title: "Debug mode"
doc_id: 1739585706138625
path: "Marketing API / Ad Measurement / Guides / Pixel / Debug mode"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Debug mode

If you require more details as you debug your pixel setting, debug mode is here to help you. 

# How to open debug mode
There are two ways to open the debug mode.

  1. Open the [Sdk Link](https://analytics.tiktok.com/i18n/pixel/events.js), and run `document.cookie="pixel_debug=true; expires=Thu, 18 Dec 2043 12:00:00 GMT; path=/; SameSite=None; Secure";`
  
  2. Refresh your website
- Check debug messages
Please open the console of your browser first. Then our Tiktok Pixel will report debug logs in console. The logs information will start with `[TT]`. For example：

  <img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ed6f1ddf7782f13bfe333806e4d9ef8b.png" width="800pt" />

# Logs of some key nodes
- When you call `ttq.load()`

  <img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7c24f65c7b8fcaa14b035b5917dc9ae5.png" width="800pt" />

- When you call `ttq.identify()` or `ttq.instance().identify()`

    <img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8f13e009a36f1d69d9004afa7270e0ae.png" width="800pt" />

- When you call `ttq.page()` or `ttq.track()` or `ttq.instance().track()`. Before report one event, we will console `adInfo` and `appInfo`

 <img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8f518029f6e2c516dcf42d201fa80635.png" width="800pt" />

- When we get info from cache or url or bridge
  
 <img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/31e456ff3a3b49254997581db0807cce.png" width="800pt" />
