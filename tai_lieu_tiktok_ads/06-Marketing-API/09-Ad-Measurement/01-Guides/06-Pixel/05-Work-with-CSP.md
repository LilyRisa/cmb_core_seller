---
title: "Work with CSP"
doc_id: 1739585704427522
path: "Marketing API / Ad Measurement / Guides / Pixel / Work with CSP"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Work with CSP

If Content Security Policy (CSP) is enabled on your website, for TikTok Pixel to work properly, you need to add configurations and domains to various CSP directives.

- For `frame-src`, add the configurations below.
    - `bytedance:`
    - `sslocal:`
	
> <span style="color:DodgerBlue">**Note**</span><br>
> `frame-src` is a Content Security Policy (CSP) directive that controls which sources \<iframe\> or \<frame\> elements can load. Browsers block any frames from domains or schemes not listed. For TikTok Pixel, you must include `bytedance:` and  `sslocal:` so its tracking and app/webview frames load correctly; otherwise, the Pixel may fail. 

- For `script-src`, `img-src` and `connect-src` directives, please add the `analytics.tiktok.com`, `analytics-ipv6.tiktokw.us`, and `ads.tiktok.com` domains.

> <span style="color:DodgerBlue">**Note**</span><br>
> `analytics-ipv6.tiktokw.us` is a domain associated with TikTok's analytics services, specifically for tracking and analytics that use IPv6 addresses. 

**Example**

```xcodeblock
(code http)
Content-Security-Policy: frame-src 'self' <b>bytedance: sslocal: </b>; script-src 'self' analytics.tiktok.com analytics-ipv6.tiktokw.us ads.tiktok.com; img-src 'self' analytics.tiktok.com analytics-ipv6.tiktokw.us ads.tiktok.com; connect-src 'self' analytics.tiktok.com analytics-ipv6.tiktokw.us ads.tiktok.com;
(/code)
```

