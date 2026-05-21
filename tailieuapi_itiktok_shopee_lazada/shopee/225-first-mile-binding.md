# First Mile Binding

> Source: https://open.shopee.com/developer-guide/225
> Category: 
> Scraped: 2026-05-20T20:38:01.258Z

---

\*This article only applies to cross-border sellers.

  

Currently, for cross-border sellers in Mainland China and South Korea, Shopee provides the first-mile tracking bind function, you can check the following API call flow.

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=KTJ%2FmBFBiTMjkkqHqBALjaL2k0dzE3iLf3m2CZKWK9DzjjsQoVfd18bqMWhNezkUh9SN7R6ERsglhTS1knun2Q%3D%3D&image_type=png)

# Terminology

  

Pick up: Channel door-to-door collection, only "logistics\_channel\_name": "shopee" ("logistics\_channel\_id": 813) support pick up.

  

Drop off: Delivered to channel outlets, expect "logistics\_channel\_name": "shopee"（"logistics\_channel\_id": 813）和"logistics\_channel\_name": "Self Deliver"（"logistics\_channel\_id": 0), other channels you get are drop off.

  

Self deliver: Self-delivered, not using third-party logistics, "logistics\_channel\_name": "Self Deliver".

  

first\_mile\_tracking\_number: first-mile tracking number

# Best Practise

For different types of first-mile shipping methods, they can be classified as

-   Pick up
-   Drop off
-   Self deliver

  

Developers can get the list of channels for first-mile and the shipping methods supported by the corresponding channels through the first\_mile.get\_channel\_list API.

## For pick up mode:

Step 1: Generate the first mile tracking number via [v2.first\_mile.generate\_first\_mile\_tracking\_number](https://open.shopee.com/documents/v2/v2.first_mile.generate_first_mile_tracking_number?module=96&type=1) API and [v2.first\_mile.get\_tracking\_number\_list](https://open.shopee.com/documents/v2/v2.first_mile.get_tracking_number_list?module=96&type=1) API can be used to query the list of generated first mile tracking numbers.

Step 2: [v2.first\_mile.get\_unbind\_order\_list](https://open.shopee.com/documents/v2/v2.first_mile.get_unbind_order_list?module=96&type=1) API provides the list of orders to be bound with the first-mile tracking number, and then please bind them through [v2.first\_mile.bind\_first\_mile\_tracking\_number](https://open.shopee.com/documents/v2/v2.first_mile.bind_first_mile_tracking_number?module=96&type=1) API; v2.first\_mile.get \_detail API can be used to query the first mile tracking number details.

Step 3: Call [v2.first\_mile.get\_waybill](https://open.shopee.com/documents/v2/v2.first_mile.get_waybill?module=96&type=1) API to print the first mile package label.

## For drop off mode:

Step 1: The seller obtains the channel tracking number offline.

Step 2: Get the list of orders to be bound with the first-mile tracking number through [v2.first\_mile.get\_unbind\_order\_list](https://open.shopee.com/documents/v2/v2.first_mile.get_unbind_order_list?module=96&type=1) API and binding them through [v2.first\_mile.bind\_first\_mile\_tracking\_number](https://open.shopee.com/documents/v2/v2.first_mile.bind_first_mile_tracking_number?module=96&type=1) api. v2.first\_mile. get\_detail API can be used to query the first-mile tracking number details.

## For self deliver mode:

Step 1: The seller gets the list of orders to be bound with the first-mile tracking number through the [v2.first\_mile.get\_unbind\_order\_list](https://open.shopee.com/documents/v2/v2.first_mile.get_unbind_order_list?module=96&type=1) API.

Step 2: Generate the first mile tracking number via [v2.first\_mile.generate\_first\_mile\_tracking\_number](https://open.shopee.com/documents/v2/v2.first_mile.generate_first_mile_tracking_number?module=96&type=1) API and [v2.first\_mile.get\_tracking\_number\_list](https://open.shopee.com/documents/v2/v2.first_mile.get_tracking_number_list?module=96&type=1) API can be used to query the list of generated first mile tracking numbers. (shipping\_method need to be uploaded self-deliver,logistics\_channel\_id need to be uploaded null).

# FAQ

Q: Is it allowed to bind orders across shops for a first-mile tracking number?A: Yes, but make sure that orders across shops use the same transshipment warehouse. Because the API has authentication, when calling the [v2.first\_mile.bind\_first\_mile\_tracking\_number](https://open.shopee.com/documents/v2/v2.first_mile.bind_first_mile_tracking_number?module=96&type=1) API, only one shop's order can be called for one call, so please make sure that order\_sn and shop\_id match for each time you call.

  

Q: Will the order status change after successful binding?

A: Yes, after binding FM successfully and being scanned, the order status of all orders being bound will be updated from PROCESSED to SHIPPED.

  

Q: Should I ship the order and print the airway bill first or first mile bind first?

A: The correct order is to ship the order first, then print the airway bill, then bind the first mile tracking number for order.

  

Q: How can I check the first-mile tracking number of an order?

A: You can use v2.logistics.get\_tracking\_number API and upload the request parameter response\_optional\_fields: first\_mile\_tracking\_number then you can get the first-mile tracking number of an order.

  

Q: What can first\_mile\_tracking\_number status be bound?

A: You can query the first-mile tracking number status through v2.first\_mile.get\_detail API.

1\. If ship\_method is a pickup:

-   If the first\_mile\_tracking\_number is just generated, the status is NOT\_AVAILABLE, at that time, the order can be bound.
-   If the first\_mile\_tracking\_number tracking status is ORDER\_RECEIVED, it means that there are orders that have already been bound, and you can continue to bind other orders.
-   If the first\_mile\_tracking\_number status is PICKED\_UP, indicating that the parcels have been collected by the channel, and can no longer bind the order.
-   If the first\_mile\_tracking\_number status is DELIVERED, which means that the parcel has arrived at the warehouse, and orders can no longer be bound.

  

2\. If ship\_method is drop off or self-deliver: there is no status restriction.

  

Q: Does self-deliver still need to call the API for first mile binding?A: Yes, even self-delivery is the offline ship action for sellers, but you still need to call [v2.first\_mile.bind\_first\_mile\_tracking\_number](https://open.shopee.com/documents/v2/v2.first_mile.bind_first_mile_tracking_number?module=96&type=1) API for the first-mile binding.

  

Q: In what state can I unbind the first mile tracking number for an order?

A: 1\. If ship\_method is a pick up, only if the first\_mile\_tracking\_number status is ORDER\_RECEIVED can be unbind.

2\. If ship\_method is drop off or self-deliver, there is no first\_mile\_tracking\_number status restriction.

# Data definition

First Mile tracking number status

-   ORDER\_RECEIVED
-   PICKED\_UP
-   DELIVERED
