# For US market: Replacement orders now available

> Source: https://partner.tiktokshop.com/docv2/page/for-us-market-replacement-orders-now-available
> Section: Changelog
> Scraped: 2026-05-21T00:36:15.055Z

---

# Summary

Prior to releasing this new feature, the only choice available to buyers who received missing, damaged or otherwise compromised items in an order was to request a return or refund. To improve the buyer experience and help sellers provide better customer satisfaction, TikTok Shop has released a new feature where a buyer can ask for a replacement for the item they paid for. Sellers can approve the request for replacement and send them a new item by creating a new "Replacement" order. If the seller does not want to provide replacements, the request can be canceled and issue a refund instead.  
**This change is only for the US market and affects local sellers. Developers who build return apps, connector apps, or multi-channel apps are** **recommended** **to adopt this change.**

# Upcoming Changes

## Seller Operation Flow

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/b0f79e23e4184e7fb250abb15e0562e9~tplv-k9wyc2ijk0-image.image)

## API Changes

The following APIs will be impacted by this change:

-   [GET Order List](get-order-list)
-   [GET Order Details](get-order-detail)
-   [Search Returns](search-returns)
-   [Approve Return](approve-return)
-   [Reject Return](reject-return)

For details about these API changes, please go to the API reference page by clicking on the links above.

## Webhook Changes

The webhook relevant for this change is the [Return Status Change](12-return-status-change) webhook.
