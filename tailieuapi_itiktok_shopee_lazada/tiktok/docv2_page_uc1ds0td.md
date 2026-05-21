# For US Market: Add subscription order tag to Order APIs

> Source: https://partner.tiktokshop.com/docv2/page/uc1ds0td
> Section: Changelog
> Scraped: 2026-05-21T00:38:15.045Z

---

## Summary

To help developers and sellers better manage inventory and fulfillment for subscription-based orders, we are introducing a new field, `is_subscription_order`, to our Order APIs. This allows for easy identification of orders generated from a customer's subscription.  
This change brings our Open API capabilities to parity with the functionality available in the TikTok Shop Seller Center, where subscription orders are already tagged.  
**What's a subscription order?** Subscription orders are recurring orders that customers schedule for products they use regularly, often as part of a "Subscribe & Save" program. They require different planning for inventory and fulfillment compared to standard, one-time purchases.

## Impact

This is a backward-compatible change and will not require a new API version. Existing integrations will continue to work without any modifications.

| Impacted market(s) | \* United States (US) initially  
\* United Kingdom (UK), EMEA, and Japan (JP) will be supported in a future release. |
| --- | --- |
| 
Impacted API version(s)

 | 

\* `202309`  
  

 |

## Changes

We are adding a new boolean field, `is_subscription_order`, to the order object in the responses of the `Get Order List` and `Get Order Detail` endpoints.

| **Endpoint(s)** | **Change(s)** |
| --- | --- |
| 
\* `GET /order/{version}/orders` (Get Order Detail)  
\* `POST /order/{version}/orders/search` (Get Order List)

 | 

A new field, `is_subscription_order`, is added to the `orders` object in the response.  
\- If `true`, the order was generated from a subscription.  
  
\* If `false`, the order is a standard, one-time purchase.

 |

### Example response snippet

The following example shows the new `is_subscription_order` field within an order object.

JSON

Word Wrap

```
{
  "code": 0,
  "message": "Success",
  "request_id": "20240420080000_EXAMPLE_REQUEST_ID",
  "data": {
    "orders": [
      {
        "id": "576461413038785752",
        "status": "AWAITING_SHIPMENT",
        "is_subscription_order": true,
        "...": "..."
      },
      {
        "id": "576461413038785753",
        "status": "AWAITING_SHIPMENT",
        "is_subscription_order": false,
        "...": "..."
      }
    ]
  }
}
```

## Next steps

While this change is backward-compatible and requires no immediate action to prevent breakage, we recommend the following:

-   **Review your downstream logic:** If your application handles inventory, fulfillment, or analytics, consider updating your code to recognize and process the `is_subscription_order` tag.
-   **Inform your users:** If you provide services to sellers, let them know they can now differentiate subscription orders through your application.

This update will roll out to the US market first. We will provide further announcements as we expand support to other regions.
