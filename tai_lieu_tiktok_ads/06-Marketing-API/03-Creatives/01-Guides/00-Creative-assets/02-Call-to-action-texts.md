---
title: "Call-to-action texts"
doc_id: 1749018853745665
path: "Marketing API / Creatives / Guides / Creative assets / Call-to-action texts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Call-to-action texts

Call-to-action (CTA) button refers to the text in the ad that guides the user to take some kind of action after seeing the ad.  See [Enumeration - Call-to-action](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) to find out the enum values of the `call_to_action` field. 

# CTA portfolio
A CTA portfolio is used to contain auto-optimized CTAs. To create a CTA portfolio, you need to get a group of auto-optimized CTAs using the [/creative/cta/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1739362202742785) endpoint. Then fetch the data in the response and include it in the request to the [/creative/portfolio/create/](https://ads.tiktok.com/marketing_api/docs?id=1739091950439426) endpoint.

# Recommended CTAs
You can use [/creative/cta/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1739362202742785) to get the recommended CTAs for your ads. There are two types of recommended CTAs, the standard recommended CTAs and the Dynamic CTAs. See [CTA recommendations](https://ads.tiktok.com/marketing_api/docs?id=1740307296329730&rid=8io5ucomqr7) for details. 
