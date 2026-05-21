# For ID market: One Change for Privacy Improvement of Image Message

> Source: https://partner.tiktokshop.com/docv2/page/hrilusvh
> Section: Changelog
> Scraped: 2026-05-21T00:51:11.323Z

---

There is one change for privacy improvement of image message in Indonesian market which will be effective on September 30, 2025.  
When sellers check image messages (from [Customer Service API](https://partner.tiktokshop.com/docv2/page/customer-service-api-overview)) and download images through HTTP to access the image link, they need to include two parameters

-   Header：x-tts-access-token
-   Query：shop\_cipher

# Which markets are affected?

The updates of the requirements apply to the Local to Local sellers for the Indonesian market.

# Who is affected?

Developers with applications that use HTTP to access image links to download images in messages.

# Which version is applicable?

The updates of the requirements apply to all versions of the related API.

# What action is required?

Developers need to check whether to use this HTTP and handle the related error messages.
