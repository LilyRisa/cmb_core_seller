# SLA information now available in Orders API

> Source: https://partner.tiktokshop.com/docv2/page/sla-information-now-available-in-orders-api
> Section: Changelog
> Scraped: 2026-05-21T00:35:37.654Z

---

# What is changing?

When sellers choose the express shipping option to facilitate faster delivery to customers, TikTok Shop will evaluate the shipping and collection time of the orders to provide a satisfactory user experience. In Seller Center, there are notifications to tell sellers about the shipping and collection time window and this change reflects these time windows in the API as `*_due_time` - `shipping_due_time`, `collection_due_time`, and `delivery_due_time`.  
There are also three other new properties: `collection_time`, `delivery_time`, `cancel_time` that reflect the actual collection, delivery and cancel time to help sellers have a better view of their order performance.  
These fields are available in the response from both the [Get Order List](get-order-list) and [Get Order Details](get-order-detail) APIs.  
Additionally, **cancel\_order\_sla\_time** **is** **deprecated**.

# Which markets are affected?

This change is applicable to **all markets**.

# Who is affected?

**All seller types** are affected by this change.

# Which version is applicable?

These changes are applicable to API **v202309**.

# What action is required?

We recommend adopting these changes so sellers can handle orders effectively in a timely manner. It also allows sellers to see their current order performance. Please note that **cancel\_order\_sla\_time** **is** **deprecated**.  
Below is the list of the newly added properties:

| **Property** | **Type** | **Description** |
| --- | --- | --- |
| 
delivery\_option\_required\_delivery\_time

 | 

int

 | 

Order should be delivered before this time.

 |
| 

shipping\_due\_time

 | 

int

 | 

If the order status has not been updated to "AWAITING\_COLLECTION" before this time, the order will be canceled by TikTok Shop.

 |
| 

collection\_due\_time

 | 

int

 | 

If the order status has not been updated to "IN\_TRANSIT" before this time, the order will be canceled by TikTok Shop.

 |
| 

delivery\_due\_time

 | 

int

 | 

If the order has not been updated to "DELIVERED" before this time, the order will be canceled by TikTok Shop.

 |
| 

collection\_time

 | 

int

 | 

The time of order status update to "IN\_TRANSIT"

 |
| 

delivery\_time

 | 

int

 | 

The time of order status update to "DELIVERED"

 |
| 

cancel\_time

 | 

int

 | 

The time of order status update to "CANCELLED"

 |
