---
title: "Send TikTok Cookie (_ttp)"
doc_id: 1771100936446977
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for Web / Send TikTok Cookie (_ttp)"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Send TikTok Cookie (_ttp)

# `_ttp` cookie
When you enable first-party cookies in your TikTok Pixel settings (as shown in the screenshot below), TikTok will automatically store a unique identifier in a first-party cookie named `_ttp` on your website domain. 

For Events API 2.0 users who have also installed the TikTok Pixel on their website, it's recommended to include the `ttp` cookie value in the Events API payload. This cookie value is used to enhance the matching of website visitor events with TikTok ads. For more details of Cookies, see [Using Cookies with TikTok Pixel](https://ads.tiktok.com/help/article/using-cookies-with-tiktok-pixel?redirected=2).

# Send `_ttp` Cookie via Events API
To capture the `_ttp` cookie value on your web server, you can parse it from the HTTP cookie request header.

When reporting user events via the Events API, include the `_ttp` cookie value in the user.ttp field of the API payload. For detailed instructions on reporting web events and related parameters, refer to [Events API 2.0 - Setup guide for Web](https://ads.tiktok.com/marketing_api/docs?id=1771100865818625).

Here's an example of how to include the `_ttp` cookie value in the Events API payload:

```xcodeblock
(code curl http)
"user": {
  "ttp": "UqBuLHl7TuWsDXUu-ViOGOLA5YP",
  // ... ...
},
(/code)
```



