# New Capability for Affiliate Sample Applications

> Source: https://partner.tiktokshop.com/docv2/page/x0ebukvz
> Section: Changelog
> Scraped: 2026-05-21T00:33:00.410Z

---

# Overview

This release introduces several new affiliate sample-related capabilities to improve the compliance and automation of the sample application workflow. Additionally, the platform now supports sub-account authorization for affiliate-category apps, allowing merchants to grant permissions to eligible sub-accounts for increased operational flexibility.

## What’s New

-   **New API:** [Seller Get Sample Request Deeplink](<https://partner.tiktokshop.com/docv2/page/seller-get-sample-request-deeplink-202512#Back To Top>)
    
    This API returns a TikTok deeplink that directs a user to the sample request page within the TikTok app. Sellers can send this deeplink to creators via Instant Messaging (IM) or by encoding it into a QR code for email distribution.
    
-   **New Webhook:** [Sample Application Status Change](https://partner.tiktokshop.com/docv2/page/urzvzank)
    
    This webhook sends notifications when a sample application is submitted or its status changes. This allows sellers and developers to receive real-time updates and review applications without relying on high-frequency polling.
    
-   **New Capability:** Sub-account Authorization for Affiliate Apps \*\*\*\* Merchants can now authorize affiliate-category applications using eligible sub-accounts that have the required permissions. This feature enhances flexibility and operational efficiency for managing multiple accounts and roles.
    

## Important Notes

-   The `Sample Application Status Change` webhook helps reduce reliance on polling the `Seller Search Sample Applications` API, leading to better performance and fewer rate-limiting issues.
-   Sub-account authorization is only available for applications in the "Affiliate" service category.

## Affected APIs and Webhooks

| **Name** | **Type** | **Version** |
| --- | --- | --- |
| 
Seller Get Sample Request Deeplink

 | 

API

 | 

202512

 |
| 

Sample Application Status Change

 | 

Webhook

 | 

 |
