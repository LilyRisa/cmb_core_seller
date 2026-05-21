# Shortened string length for buyer_email in Get Order Detail API

> Source: https://partner.tiktokshop.com/docv2/page/shortened-string-length-for-buyer-email-in-get-order-detail-api
> Section: Changelog
> Scraped: 2026-05-21T00:35:19.172Z

---

# What is changing?

In August, we added a new property called `buyer_email` to the [Get Order Detail](get-order-detail) API.

As of today, we've shortened the string value of the buyer\_email property to less than 45 characters. This change improves compatibility with external systems like shipping software.

# Which markets are affected?

The shortened buyer\_email string is available in these markets and for these seller types:

-   US and ID markets for local sellers
-   MY, PH, VN, TH, SG, UK markets for local and cross-border sellers

# Who is affected?

Developers with applications that use the Get Order Detail API to manage orders for sellers are affected.

# What action is required?

The length of the existing property buyer\_email in the Get Order Detail API response has been shortened.

If your application syncs TikTok Shop orders to external systems like shipping software, you can use the shortened buyer\_email value and make sure relevant customer information is still correctly synced back to those external systems.
