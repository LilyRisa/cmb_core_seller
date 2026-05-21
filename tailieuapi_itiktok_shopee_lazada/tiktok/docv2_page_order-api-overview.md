# Order API overview

> Source: https://partner.tiktokshop.com/docv2/page/order-api-overview
> Section: API Reference
> Scraped: 2026-05-20T23:35:48.936Z

---

# Context

The Orders API helps you obtain information about orders.  
You may programmatically GET an [Order List](https://partner.tiktokshop.com/docv2/page/650aa8094a0bb702c06df242?external_id=650aa8094a0bb702c06df242), and GET the [Order Details](https://partner.tiktokshop.com/docv2/page/650aa8ccc16ffe02b8f167a0?external_id=650aa8ccc16ffe02b8f167a0) from a specific Order.  
Additionally, you may also subscribe to the [Order Status Webhook](https://partner.tiktokshop.com/docv2/page/650300b8a57708028b430b4a) to be notified regarding order status changes.

# Important Concepts

## Order

An order is created when the buyer clicks on the Place Order button. Please note that when the order is created, the buyer has yet to make the payment, therefore the status of the order is UNPAID. Once an order is created, the seller should deduct or hold inventory from their inventory management system accordingly.

## **Order Structure**

1.  Order ID: An Order ID is a unique identifier for each Order that is created by a Buyer.
    
2.  SKU ID: An order line contains 1 or more items of products of the same Stock Keeping Unit. Each SKU is identified by its unique SKU ID. A SKU can be thought of as a "variant".
    
3.  Order Line Item id: Each single item in a line is identified by its unique order line item id.
    

  

**Example-** A buyer places an order for 5 products total: 2 Red Large T-shirts, 2 Red Medium T-shirts, and 1 size 10 black shoes:

| **Order** | **SKU** | **Line item** |
| --- | --- | --- |
| 
Order ID: 12345678  

 | 

Red Large T Shirt

 | 

1x Red Large T Shirt (item 1)

 |
| 

 | 

 | 

1x Red Large T Shirt (item 2)

 |
| 

 | 

Red Medium t shirt  

 | 

1x Red Medium t shirt (item 3)

 |
| 

 | 

 | 

1x Red Medium t shirt (item 4)

 |
| 

 | 

Size 10 Black Shoes

 | 

1 size 10 black shoes (item 5)

 |

## Order Status

### **Order Status Definition**

| **Status** | **Description** |
| --- | --- |
| 
UNPAID

 | 

The order has been placed but payment has been authorized.

 |
| 

ON\_HOLD

 | 

After payment is completed for the order, the order transitions into the ON\_HOLD status during the remorse period. The remorse period allows the buyer to cancel the order without seller approval. ON\_HOLD orders are not allowed to be fulfilled.

 |
| 

AWAITING\_SHIPMENT

 | 

Awaiting the seller to place a logistic order.

 |
| 

PARTIALLY\_SHIPPING

 | 

One or more (but not all) items in the order have been shipped.

 |
| 

AWAITING\_COLLECTION

 | 

The logistics order was placed. At least one item in the order is still waiting to be collected by the carrier.

 |
| 

IN\_TRANSIT

 | 

All items have been collected by the carrier. At least one package is has yet to be delivered to the buyer.

 |
| 

DELIVERED

 | 

All items have been delivered to the buyer.

 |
| 

COMPLETED

 | 

The order has been completed. Completed orders can no longer be returned or refunded.

 |
| 

CANCELLED  

 | 

The order has been canceled. The order can be canceled by the buyer, the seller, the TikTok SYSTEM, or a TikTok OPERATOR.  
  
\* Buyer: Buyers can change their mind and cancel within 1 hour post purchase, known as 1-hour remorse. Past this period, Buyer can still request a cancellation, but it is subject to Seller approval.  
\* Seller: Sellers can cancel an order, for example if they are out of stock of the product.  
\* SYSTEM: Orders may be cancelled by the TikTok system automatically based on TikTok policies. For example, this can happen if a package is lost in transit, and TikTok detects that the tracking ID has not progressed for over 7 days.  
\* OPERATOR: Orders may also be cancelled by TikTok customer service for a variety of reasons.  
  
For a comprehensive list of supported cancellation reasons, [please visit this page.](https://partner.tiktokshop.com/docv2/page/67e61eee427345048595487d)

 |

### **Order Status Transitions**

1.  From UNPAID to ON\_HOLD
    1.  Trigger: When the order is paid, the order status updates to ON\_HOLD.
    2.  Trigger initiator: Buyer
    3.  Note:
        1.  For ON\_HOLD orders, the recipient address and buyer information are not available via the Order API.
2.  From ON\_HOLD to AWAITING\_SHIPMENT
    1.  Trigger: After the remorse window(1 hour after payment), the order status changes to AWAITING\_SHIPMENT.
    2.  Trigger initiator: TiKTok
    3.  Note: This step occurs automatically.
3.  From AWAITING\_SHIPMENT to PARTIALLY\_SHIPPING
    1.  Trigger: When some item(s) in the order but not all have been shipped.
    2.  Trigger initiator: Seller
    3.  Note: only split shipments have this status. If the seller ships all item(s) in the order within one package, this status is skipped.
4.  From AWAITING\_SHIPMENT or PARTIALLY\_SHIPPING to AWAITING\_COLLECTION
    1.  Scenario A(seller ships all items in one package) : AWAITING\_SHIPMENT to AWAITING\_COLLECTION
        1.  Trigger: Seller calls API to ship all the item(s) in the order.
        2.  Trigger initiator: Seller
    2.  Scenario B(Seller split order to ship): PARTIALLY\_SHIPPING to AWAITING\_COLLECTION
        1.  Trigger: Seller calls API to ship the unshipped item in the order. Only all the items have been arranged shipment, then the order status will be updated to AWAITING\_COLLECTION.
        2.  Trigger initiator: Seller
    3.  Note: Once the seller arranges shipment, the buyer can not cancel request without seller approval.
5.  From AWAITING\_COLLECTION to IN\_TRANSIT
    1.  Scenario A(Seller ships all items in one package)
        1.  Trigger: Once TikTok obtains the shipment tracking information of packages from the carrier system, the order status will be updated to IN\_TRANSIT from AWAITING\_COLLECTION.
        2.  Trigger initiator: TikTok
    2.  Scenario B(Seller split order to ship)
        1.  Trigger: Once Tiktok obtains the shipment tracking information of all packages from the carrier system, the order status will be updated to IN\_TRANSIT from AWAITING\_COLLECTION.
    3.  Note: TikTok obtains shipment tracking information from various tracking data providers. Depending on the performance of the provider, the tracking information may be delayed. If the shipment tracking information delay is over 24 hours, please contact TikTok.
6.  From IN\_TRANSIT to DELIVERED
    1.  Trigger: The package has been successfully delivered.
    2.  Trigger initiator: TikTok
7.  From DELIVERED to COMPLETED.
    1.  Scenario A(Buyer requests refund or return)
        1.  Trigger: Buyers can initiate a multi-time return or refund request. Once the order amount is a full refund to the buyer, the order status will be updated to COMPLETED.
        2.  Trigger initiator: Seller/TikTok
    2.  Scenario B(Seller initiate refund or return)
        1.  Trigger: Seller can initiate a multi-time return or refund. Once the order amount is a full refund to the buyer, the order status will be updated to COMPLETED.
        2.  Trigger initiator: Seller
    3.  Scenario C(TikTok)
        1.  Trigger: Order available refund amount fully be refunded before the after-sales period is over.
        2.  Trigger initiator: TikTok
    4.  Note: Different region and business mode (Local to Local or cross border modes) after-sales period is different. Please refer to the seller academy for applicable after-sales policies.
8.  From UNPAID to CANCELLED
    1.  Scenario A(Buyer cancel the order)：
        1.  Trigger: Buyer cancel UNPAID status order.
        2.  Trigger initiator: Buyer
    2.  Scenario B(TikTok cancel the order)：
        1.  Trigger: The buyer has not paid for the order within the specified time(different region has different setting).
        2.  Trigger initiator: TikTok
9.  From ON\_HOLD to CANCELLED
    1.  Scenario A(Buyer cancel the order)
        1.  Trigger: Buyer cancel order in remorse window.
        2.  Trigger initiator: Buyer
    2.  Scenario B(Seller cancel the order)
        1.  Trigger: The seller cancels the order due to being out of stock.
        2.  Trigger initiator: Seller
    3.  Note: The order status will only updated to 'CANCELLED' when all items in the order have been cancelled.
10.  From AWAITING\_SHIPMENT to CANCELLED
     1.  Scenario A(Buyer cancel the order)
         1.  Trigger: Buyer initiates cancel request and seller accepts.
         2.  Trigger initiator: Buyer
     2.  Scenario B(Seller cancel the order)
         1.  Trigger: Seller cancels the order due to being out of stock.
         2.  Trigger initiator: Seller
     3.  Scenario C(TikTok cancel the order)
         1.  Trigger: TikTok cancels the order, because the seller doesn't arrange shipment before TikTok requires time.
         2.  Trigger initiator: TikTok
     4.  Note: The order status will only updated to 'CANCELLED' when all items in the order have been cancelled.
11.  From PARTIALLY\_SHIPPING to CANCELLED
     1.  Trigger: Buyer cancels the unshipped and Tiktok cancels the shipped item
     2.  Trigger initiator: Buyer and Tiktok
     3.  Note: The order status will only updated to 'CANCELLED' when all items in the order have been cancelled.
12.  From AWAITING\_COLLECTION to CANCELLED
     1.  Scenario A(Seller cancels the order)
         1.  Trigger: Seller cancels the order. If order has been split, order status will update to CANCELLED when all the split order been canceled.
         2.  Trigger initiator: seller
     2.  Scenario B(Tiktok cancels the order)
         1.  Trigger: Tiktok cancels the order because TikTok can not obtain shipment tracking information from various tracking data providers before TikTok requires time. If order has been split, order status will update to CANCELLED when all the split order been canceled.
         2.  Trigger initiator: Tiktok
     3.  Note: Only the US market allows sellers to cancel order under AWAITING\_COLLECTION status. The order status will only updated to 'CANCELLED' when all items in the order have been cancelled.
13.  From IN\_TRANSIT to CANCELLED
     1.  Trigger: Tiktok cancels the order. If order has been split, order status will update to CANCELLED when all the split order been canceled.
     2.  Trigger initiator: Tiktok
     3.  Note: this situation commonly occurs when the package is lost during transit or the buyer refuses to receive package. The order status will only updated to 'CANCELLED' when all items in the order have been cancelled.
14.  From AWAITING\_COLLECTION to COMPLETED
     1.  Trigger: Buyer initiates refund request and seller accept
     2.  Trigger initiator: Buyer
     3.  Note: Once the order amount is fully refunded, order status will change to COMPLETED. The order status will only updated to 'CANCELLED' when all items in the order have been cancelled.
15.  From IN\_TRANSIT to COMPLETED
     1.  Scenario A(Buyer initiate refund request)
         
         1.  Trigger: Buyer initiates refund request and seller accepts.
         2.  Trigger initiator: Buyer
     2.  Scenario B (Tiktok auto approve buyer refund request)
         
         1.  Trigger: Buyer initiates refund request. When the time exceeds the latest estimated delivery time, Tiktok will auto approve the buyer refund request.
         2.  Trigger initiator: Tiktok
     3.  Note: Once the order amount is fully refunded, order status will change to COMPLETED. The order status will only updated to 'CANCELLED' when all items in the order have been cancelled.
         

## Fulfillment Types and Delivery Options

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/2a0ec536ec0e4d1e93aa66566915c3ce~tplv-k9wyc2ijk0-image.image)

### Fulfillment Types

TikTok Shop offers two fulfillment types:

1.  FULFILLMENT\_BY\_SELLER: The seller fulfills orders directly from their own warehouses. In this model, the seller is responsible for storing, packaging, and shipping the products to customers.
2.  FULFILLMENT\_BY\_TIKTOK: The seller stocks their products in Tiktok's fulfillment centers. Tiktok is responsible for storing, picking, packing, and shipping the products to customers.

### Shipping type

For FULFILLMENT\_BY\_SELLER, there are two shipping types:

1.  TikTok Shipping: Tiktok provides shipping services. The seller obtains shipping labels from Tiktok.
    
2.  Seller Shipping: The seller arranges shipping.
    

## Recipient Address

### Recipient Information Redaction

In the following scenarios, the recipient's address and personal information will be redacted.

1.  Order fulfillment\_type = FULFILLMENT\_BY\_TIKTOK
2.  Order fulfillment\_type = FULFILLMENT\_BY\_SELLER and shipping type = TikTok

### Localized Recipient Address

Different countries may have different address hierarchy and naming conventions. To accommodate such differences, use 'district\_info\_list' to obtain address information expressed in the local address hierarchy and naming convention.

## Order SLA (Service Level Agreement) Information

Currently, Tiktok shop has the following Service Level Agreement concept.  
rts\_sla: "RTS" is the abbreviation for Ready To Ship. RTS marks the time when the order status transitions to AWAITING\_COLLECTION. rts\_sla indicates the time period within which TikTok requires the seller to ship the order. If the order status has not transitioned to AWAITING\_COLLECTION before rts\_sla has passed, this constitutes a late dispatch, which will increase the seller's late dispatch rate.  
tts\_sla: "TTS" stands for Transfer To Ship. TTS marks the time when the order status transitions to IN\_TRANSIT. tts\_sla specifies the time period within which TikTok requires the packages to be collected by the carrier. If the order status has not transitioned to IN\_TRANSIT before tts\_sla has passed, this constitutes a late dispatch, which will increase the seller's late dispatch rate.  
delivery\_sla: The time period within which TikTok requires the packages to be delivered to the buyer.  
cancel\_order\_sla: If the seller fails to complete the shipment by this time point, the order will be automatically canceled by the platform. TikTok might cancel the order, if the order doesn't arrange the shipment before rts\_sla or the order can not obtain the tracking information before tts\_sla. Policy details, please see the Seller Academy.

# TikTok Shop Marketplace Policies

As a Marketplace, TikTok Shop imposes certain policies on sellers for the benefit of both buyers and sellers.

## Cancel Policy

Buyer cancel order within 1-hour:

1.  Buyers are able to cancel their order within one hour for no charge: this is known as the 1-hour remorse period. This is required for all shops. We simply recommend that sellers wait one hour before beginning fulfillment of the Order, and for all apps and services to implement this 1-hour remorse period. In markets where the ON\_HOLD status is available, this 1-hour remorse window is the ON\_HOLD status.

Buyer request cancel after 1-hour:

1.  After 1-hour, buyer cancel request required to review by seller. The seller can accept or decline the cancel request.
    

## Order Fulfillment SLA(Service Level Agreement) Policy

1.  The seller should arrange shipment before rts\_sla time.
2.  The shipping service provider must update the tracking information of package collection before the tts\_sla time.
3.  If the Seller is not able to fulfill an Order with a valid shipping ID before cancel\_order\_sla, the Order will be cancelled and the Buyer refunded.

# Frequently Asked Questions

1.  How do I get order ID from?
    1.  To utilize the Orders API, you must subscribe to the Orders Webhook. Click here for more details
2.  My item is partially out of stock. Can I ship only part of the Order?
    1.  Currently, we don't provide this functionality. If this is a must have requirement, reach out to our CST team and we will see if we can get you access to a beta API which may be able to address this.
3.  Can I completely cancel an order?
    1.  Yes, you can call Cancel Order API to cancel order.
4.  How do I ship a new order which is a replacement for an existing one (ie package lost)?
    1.  Currently, we don't have replacement functionality. If this is a must have requirement, please ask buyer to cancel the order and replace an order.
5.  How can I check the order line item information?
    1.  You can call Get Order Detail API to obtain it from line\_items.
6.  How can I know is the order fulfilled by Tiktok?
    1.  You can call Get Order Detail API to obtain it from fulfillment\_type.
7.  How can I know the order shipping service offered by platform or seller?
    1.  You can call Get Order Detail API to obtain it from delivery\_option\_id.
8.  How can I know whether the order line item is gift?
    1.  You can call Get Order Detail API to obtain it from is\_gift.
9.  Can a Buyer place an order from multiple shops?
    1.  Yes! Buyers can place an order from multiple shops at once. For example, if I buy some products from Shop A, and some products from Shop B, TTS will actually create 2 orders.
