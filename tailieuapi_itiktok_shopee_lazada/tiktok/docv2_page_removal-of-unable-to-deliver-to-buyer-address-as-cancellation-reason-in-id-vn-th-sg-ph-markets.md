# Removal of "unable to deliver to buyer address" as a cancellation reason in ID, VN, TH, SG, PH marke

> Source: https://partner.tiktokshop.com/docv2/page/removal-of-unable-to-deliver-to-buyer-address-as-cancellation-reason-in-id-vn-th-sg-ph-markets
> Section: Changelog
> Scraped: 2026-05-21T00:38:26.162Z

---

# What is changing?

Starting March 1, 2024, sellers will no longer be able to use "unable to deliver to buyer address" as a reason for canceling orders. This change is intended to encourage sellers to provide more solid reasons for cancellations.

# Which markets are affected?

This update affects local sellers in ID, SG, TH, VN, and PH markets

# Who is affected?

Developers who have integrated the [Cancel Order](cancel-order) endpoint into their apps are impacted by this change.

# What action is required?

If your app integration includes **"unable to deliver to buyer address"** as a [cancellation reason](cancel-reasons) for seller-initiated cancellations, you need to remove this option from your app before March 1, 2024.
