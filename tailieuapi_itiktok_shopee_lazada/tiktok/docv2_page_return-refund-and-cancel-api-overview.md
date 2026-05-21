# Return, refund, and cancel API overview

> Source: https://partner.tiktokshop.com/docv2/page/return-refund-and-cancel-api-overview
> Section: API Reference
> Scraped: 2026-05-20T23:45:36.840Z

---

# Context

Cancel/Return/Refund, also referred to as 'post-transaction' or 'after-sales' can be initiated by a buyer or directly processed by a seller. TikTok Shop provides two APIs for sellers process these after-sales situations. The cancel API allows sellers to process a buyer order cancellation request as well as a direct seller order cancellation. The return API allows sellers to process a buyer order refund/ return & refund request. For situations where TikTok Shop cancels/ refunds an order, please refer to TikTok Shop Seller Academy for details.  
  
  
Cancel order: Order cancellations can be initiated by a buyer after the buyer remorse window or directly canceled by the seller. For each canceled order, there is a buyer/seller cancel reason, cancel status, cancel order creation time, and the seller's process time for the buyer cancellation request. In the US and UK markets, TikTok Shop allows sellers to process partial order cancellations on item out of stock scenario.  
  
  
Return order: Return orders refers to when a buyer requests to return or refund one or more items from an order. Returns can be initiated by a buyer after they have received the items. Return orders contain the buyer/seller return or refund reason, return/refund status, return or refund request time, the seller's process time for the buyer return/refund request, and the refund amount.  
  
  
There are 2 ways to implement Search Cancel API and Search Return API:

-   Subscribe to the [Cancellation Status Change](https://partner.tiktokshop.com/docv2/page/65030150746462028285f657) and [Return Status Change](https://partner.tiktokshop.com/docv2/page/65030162bb2a4d028d50cc51) webhooks: you must subscribe to reverse order Webhook. For more details, [click here](<https://partner.tiktokshop.com/docv2/page/650512b42f024f02be19755f#Back To Top>). Poll Order List periodically
-   Poll Cancel order/Return order periodically

# Important Concepts

**The following table illustrates when "Cancellation," "Return," and "Refund" can be used based on the order status.**

|  | **Initiator** | **UNPAID** | **ON\_HOLD** | **AWAITING\_SHIPMENT** | **AWAITING\_COLLECTION** | **IN\_TRANSIT** | **DELIVERED** | **COMPLETED** |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 
**Cancel**

 | 

Buyer

 | 

Y

 | 

Y(Cancel request auto approve by platform. Only available in the UK)

 | 

Y  

 | 

N  

 | 

N

 | 

N

 | 

N

 |
| 

 | 

Seller

 | 

N

 | 

Y

 | 

Depends on market policy. Please see details in appendix Seller Academy link

 | 

Y  

 | 

N

 | 

N

 | 

N

 |
| 

**Refund**

 | 

Buyer

 | 

N

 | 

N

 | 

N

 | 

Y

 | 

Depends on market policy. Please see details in Seller Academy in appendix

 | 

Y

 | 

N

 |
| 

 | 

Seller

 | 

N

 | 

N

 | 

N

 | 

Y

 | 

 | 

Depends on market policy. Please see details in appendix Seller Academy link

 | 

N

 |
| 

**Return**

 | 

Buyer

 | 

N

 | 

N

 | 

N

 | 

N

 | 

 | 

Y

 | 

N

 |
| 

 | 

Seller

 | 

N

 | 

N

 | 

N

 | 

N

 | 

 | 

Depends on market policy. Please see details in appendix Seller Academy link

 | 

N

 |

## Cancel/Return/Refund initiator explanation

-   BUYER: The buyer that placed the order.
    
-   SELLER: TikTok Shop seller.
    
-   SYSTEM: Orders may be cancelled by the TikTok Shop system automatically based on TikTok Shop's policies. For example, this can happen if a package is lost in transit, and TikTok Shop detects that the tracking number has not changed for over 7 days.
    
-   OPERATOR: Orders may also be cancelled by TikTok Shop's customer service for a variety of reasons.
    

## Cancel

**Order Cancellations:** This API allows sellers to cancel paid orders as well as manage order cancellation requests. Please note, only orders that have not yet been shipped can be canceled.  
  
  
Note: Buyers cannot cancel individual line items within multiple line item orders. Currently, only US and UK sellers are allowed to do partial cancel on item out of stock scenario.  
  
  
**Cancel Types:** On the TikTok Shop platform, there are two types of order cancellations on TikTok Shop: buyer initiated cancellation, i.e. BUYER\_CANCEL and direct cancellation, i.e. CANCEL. Seller/System/Operator can direct cancel an order. BUYER\_CANCEL requires the seller to review the cancellation request. If an order's status is 'ON\_HOLD', and a buyer initiates a cancellation, TikTok Shop will accept the cancellation request on behalf of the seller automatically. The seller does not need to review the cancellation request. In this case, this order will have a 'CANCEL' cancellation type.  
  
  
**Cancellation Reasons:** Please refer to [our list of cancel reasons](<https://partner.tiktokshop.com/docv2/page/67e61eee427345048595487d#Back To Top>).  
  
  
**Cancellation Order Status：**

**Business Case:**

-   Buyer initiates a cancellation request
    

> **Note**: If the buyer cancellation request is made **before** the 2-business day Standard Shipping SLA from the order date, the seller must act within 24 hours. If no action is taken, TikTok Shop will auto-approve the cancellation and issue a refund. Sellers must resolve the request by either:

> -   Uploading the tracking number to the cancellation request
> -   Approving the cancellation

> Shipping the item or taking no other action will result in the cancellation being approved.
> 
> **Note**: If the cancellation request is made **after** the 2-business-day Standard Shipping SLA from the order date, but before the order is marked as **"In transit"**, TikTok Shop will automatically approve the cancellation and issue a refund.

> -   For more information on SLAs, refer to the [Fulfillment Policy](https://seller-us.tiktok.com/university/essay?identity=1&role=1&knowledge_id=3995852763301633&from=policy).

-   Seller initiates a cancellation
    

## Refund

**Refund:** Buyers/Seller are able to initiate a refund for a line item. This API allows sellers to process buyer refund requests and issue either partial or full refunds.  
  
  
Note: Currently, different markets have different refund policies. Please refer to Seller Academy for more details.  
  
  
**Refund Reasons:** Please refer to [our list of refund reasons](https://partner.tiktokshop.com/docv2/page/67e61e7b8ceb0d04a320061e).  
  
  
**Refund Order Status：**

Refund Order Status Explanation

| **Status** | **Description** |
| --- | --- |
| 
RETURN\_OR\_REFUND\_REQUEST\_PENDING

 | 

Buyer initiates a refund request, needs to be approved by seller or platform.

 |
| 

REQUEST\_SUCCESS

 | 

The refund request is successful, the buyer will be refunded.

 |
| 

REQUEST\_REJECTED

 | 

The seller rejected the refund request.

 |
| 

RETURN\_OR\_REFUND\_CANCEL

 | 

The refund request has been cancelled by buyer or system.

 |
| 

RETURN\_OR\_REFUND\_REQUEST\_COMPLETE

 | 

The refund request is successful, and the amount has been refunded.

 |

Step 1: status from RETURN\_OR\_REFUND\_REQUEST\_PENDING to REQUEST\_SUCCESS  
Trigger: refund request has been approved.  
Trigger initiator: Seller/System  
  
  
Step 2: status from REQUEST\_SUCCESS to RETURN\_OR\_REFUND\_REQUEST\_COMPLETE  
Trigger: the refund amount has been refunded.  
Trigger initiator: System  
  
  
Step 3: status from RETURN\_OR\_REFUND\_REQUEST\_PENDING to REQUEST\_REJECTED  
Trigger: refund request has been rejected.  
Trigger initiator: Seller  
  
  
Step 4: status from RETURN\_OR\_REFUND\_REQUEST\_PENDING to RETURN\_OR\_REFUND\_CANCEL  
Trigger: buyer cancels the refund request.  
Trigger initiator: Buyer.  
  
  
Step 5: status from REQUEST\_REJECTED to RETURN\_OR\_REFUND\_CANCEL  
Trigger: After the buyer's refund request is rejected and there is no arbitration raised within the given time, or if the arbitration result favors the seller.  
Trigger initiator: System  
  
  
Step 6: status from REQUEST\_REJECTED to REQUEST\_SUCCESS  
Trigger: The buyer submits an arbitration request to the platform, and the platform approves the buyer's refund request.  
Trigger initiator: System  
  
  
**Business Case:**

-   Buyer initiates a refund request
    

-   Seller initiates a refund
    

## Return

**Return:** Buyers are able to initiate a return and get a refund for a returned order line item. This API allows sellers to accept return requests from buyers as well as initiate returns on behalf of buyers.  
  
  
**Return Reasons:** [Click here for a list of our return reasons](https://partner.tiktokshop.com/docv2/page/67e61d87fcf2dd04c982ef1d).  
  
  
**Return-less Refunds:** TikTok Shop allows the sellers to change a buyer's return request and issue a refund directly. When sellers accept returns via the Approve Return API, they can choose to allow the buyer to keep the items and complete the return as a return-less refund. When seller accepts return via Approve Return API, can choose buyer keep item option to achieve returnless refund.  
  
  
**Return Order Status**

  

Refund Order Status Explanation

| **Status** | **Description** |
| --- | --- |
| 
RETURN\_OR\_REFUND\_REQUEST\_PENDING

 | 

Buyer initiates a return request, pending seller review.

 |
| 

AWAITING\_BUYER\_SHIP

 | 

Waiting for buyer to ship return items to the seller. If the return exceeds the return deadline, the request will be closed by TikTok Shop.

 |
| 

BUYER\_SHIPPED\_ITEM

 | 

The buyer has shipped items to the seller.

 |
| 

REQUEST\_REJECTED

 | 

The seller rejected the return request.

 |
| 

RECEIVE\_REJECTED

 | 

The seller rejected the buyer's return package.

 |
| 

REQUEST\_SUCCESS

 | 

The return request is successful, the buyer will be refunded.

 |
| 

RETURN\_OR\_REFUND\_REQUEST\_COMPLETE

 | 

The return request is successful~~,~~ and the amount has been refunded.

 |
| 

RETURN\_OR\_REFUND\_CANCEL

 | 

The return request has been cancelled by the buyer.

 |

Step 1: status from RETURN\_OR\_REFUND\_REQUEST\_PENDING to AWAITING\_BUYER\_SHIP  
Trigger: buyer return request has been approved.  
Trigger initiator: Seller/System  
  
  
Step 2: status from AWAITING\_BUYER\_SHIP to BUYER\_SHIPPED\_ITEM  
Scenario A(buyer uses platform shipping to return)  
Trigger: buyer shipped return package and package picked up by carrier.  
Trigger initiator: System  
  
  
Scenario B(buyer uses self-arrange shipping to return)  
Trigger: buyer uploads the tracking number  
Trigger initiator: Buyer  
  
  
Step 3: status from BUYER\_SHIPPED\_ITEM to REQUEST\_SUCCESS  
Trigger: seller finished the return item(s) quality check and confirmed refund.  
Trigger initiator: Seller/System  
  
  
Step 4: status from REQUEST\_SUCCESS to RETURN\_OR\_REFUND\_REQUEST\_COMPLETE  
Trigger: the refunding for return is successful  
Trigger initiator: System  
  
  
Step 5: status from BUYER\_SHIPPED\_ITEM to RECEIVE\_REJECTED  
Trigger: After seller checks the return item(s), seller refuses to refund for the return.  
Trigger initiator: Seller  
  
  
Step 6: status from RECEIVE\_REJECTED to REQUEST\_SUCCESS  
Trigger: The buyer submits an arbitration request to the platform, and the platform approves the buyer's return request.  
Trigger initiator: System  
  
  
Step 7: status from RETURN\_OR\_REFUND\_REQUEST\_PENDING to REQUEST\_REJECTED  
Trigger: Seller rejects the buyer's return request.  
Trigger initiator: Seller  
  
  
Step 8: status from RETURN\_OR\_REFUND\_REQUEST\_PENDING to RETURN\_OR\_REFUND\_CANCEL  
Trigger: The request has been cancelled by the buyer.  
Trigger initiator: Buyer  
  
  
Step 9: status from REQUEST\_REJECTED to RETURN\_OR\_REFUND\_CANCEL  
Trigger: After the buyer's return request is rejected and there is no arbitration raised within the given time, or if the arbitration result favors the seller.  
Trigger initiator: System  
  
  
Step 10: status from REQUEST\_REJECTED to AWAITING\_BUYER\_SHIP  
Trigger: The buyer submits an arbitration request to the platform, and the platform approves the buyer's return request.  
Trigger initiator: System  
  
  
Step 11: status from RECEIVE\_REJECTED to RETURN\_OR\_REFUND\_CANCEL  
Trigger: After the buyer's return request is rejected and there is no arbitration raised within the given time, or if the arbitration result favors the seller.  
Trigger initiator: System  
  
  
**Business Case:**

-   Buyer initiates a return request
    

-   Seller initiates a return
    

## Replacement

**Replacement order status**

| **Status** | **Description** |
| --- | --- |
| 
REPLACEMENT\_REQUEST\_PENDING

 | 

The buyer has initiated a replacement request. The request is pending review by seller. Seller has 24 hours to respond to the request.

 |
| 

REPLACEMENT\_REQUEST\_REJECT

 | 

The seller rejects the buyer's replacement request.

 |
| 

REPLACEMENT\_REQUEST\_REFUND\_SUCCESS

 | 

Buyer's replacement request was resolved by refund due to insufficient inventory.

 |
| 

REPLACEMENT\_REQUEST\_CANCEL

 | 

The buyer canceled the replacement request.

 |
| 

REPLACEMENT\_REQUEST\_COMPLETE

 | 

The seller has approved the buyer's replacement request. The platform will generate a new order for the seller to fulfill.

 |

Feature brief: Replacement is initiated by buyer. The seller can reject/accept/refund the buyer request. If the seller/system accepts the buyer request, Tiktok Shop will generate a new order for the seller to reship. If sellers do not have enough inventory for the Replacement or can not send the item for Replacement, they can refund buyer directly.

Learn more about Replacement policy from [Item Replacement for Orders](https://seller-us.tiktok.com/university/essay?identity=1&role=1&knowledge_id=3253210454181634&from=policy&anchor_link=EB7800D0).

Case 1: status changing from REPLACEMENT\_REQUEST\_PENDING to REPLACEMENT\_REQUEST\_REJECT  
Trigger: Seller received buyer replacement request and rejected the buyer replacement request. Please be aware if seller rejected buyer replacement request, buyer can initiate the dispute.  
Trigger initiator: Seller

Case 2: status changing from REPLACEMENT\_REQUEST\_PENDING to REPLACEMENT\_REQUEST\_COMPLETE  
Trigger: Seller or system automatically accepts replacement requests. Please be aware if the seller/system accepts the buyer's replacement request TikTok Shop platform will automatically generate a new order for seller to fulfill. For the new orders generated for replacement, sellers should follow the same fulfillment policy as for the normal orders.  
Trigger initiator: Seller/System

Case 3: status changing from REPLACEMENT\_REQUEST\_PENDING to REPLACEMENT\_REQUEST\_REFUND\_SUCCESS  
Trigger: Seller directly refunds the buyer.  
Trigger initiator: Seller

Case 4: status changing changing from REPLACEMENT\_REQUEST\_REJECT to REPLACEMENT\_REQUEST\_COMPLETE  
Trigger: When buyer initiates dispute and TikTok Shop make a resolution that seller should execute the replacement request for the buyer.. TikTok Shop will initiate a new order for sellers to fulfill. Please be aware, for the new orders generated for replacement, sellers should follow the same fulfillment policy as for the normal orders.

**Business case**

# TikTok Shop Marketplace Policies

## Policy for Responding to Buyer Requests

-   The following approval nodes for cancellation/return/refund requests require seller's action, and the seller must respond to the request within 48 hours. If there is no response within 48 hours, TikTok Shop will automatically approve the corresponding request.
    -   Cancel
        -   When buyer initiates cancel request success, seller should respond to the request within 48 hours.
    -   Refund
        -   When buyer initiates refund request success, seller should respond to the request within 48 hours.
    -   Return
        -   When buyer initiates return request success, seller should respond to the request within 48 hours.
            
        -   If the seller accepts the buyer's return request, the buyer will ship the item(s) back. Once the return package is delivered, the seller must respond to the return request within 48 hours.
            

## Cancel/Return/Refund Initiative Policy

**Policy for Initiating Cancellations**  
Buyers

-   Buyers can freely cancel unpaid orders.

  
For the UK, TikTok Shop uses the ON\_HOLD status to define an order that's within the buyer remorse window. If a buyer requests an order cancellation while the order has an ON\_HOLD status, TikTok Shop will automatically accept the buyer cancellation request on behalf of the seller. For regions outside of the UK, the buyer remorse window starts when the order changes to AWAITING\_SHIPMENT status +1 hour. The buyer will no longer be able to request for cancellations if the seller has fulfilled the order.  

-   Orders that are outside of the buyer remorse window

Once an order is longer within the buyer remorse window, the cancellation must be reviewed by the seller. If an order status changes to AWAITING\_COLLECTION, the buyer will no longer be able to request for a cancellation.  
  
  
Sellers

-   Seller cannot cancel unpaid orders.
-   Seller can cancel orders with the following statuses: 'ON\_HOLD', 'AWAITING\_SHIPMENT' and 'AWAITING\_COLLECTION'.

  
For more details, please refer to Seller Academy.

**Policy for Initiating Refunds**

-   Buyers are allowed to initiate refund requests when the order status is 'AWAITING\_COLLECTION', 'IN\_TRANSIT' and 'DELIVERED'
-   Sellers are allowed to initiate refund requests when the order status is 'DELIVERED'

  
For more details, please refer to Seller Academy.

**Policy for Initiating Returns**

-   Buyers are allowed to initiate return requests when the order status is 'DELIVERED'
-   In the US, sellers are allowed to initiate refund requests when the order status is 'IN\_TRANSIT' and 'DELIVERED'. In other markets, sellers are not allowed to initiate returns on behalf of buyers.

  
For more details, please refer to Seller Academy.

# Frequently Asked Questions

-   Can a seller issue a partial refund to the buyer?
    -   Different markets have different policies. Please refer to Seller Academy for details.

  

-   What is the difference between cancel and refund?
    -   Cancel request can be initiated by the buyer BEFORE the seller ships an order. Once the order has been shipped, the buyer will no longer be able to cancel the order. If the package is still being delivered and the buyer insists on canceling the order, the seller should ask the buyer to initiate a refund request.

  

-   If a seller initiates a refund, are there any additional approvals needed?
    -   No. TikTok Shop will automatically refund the buyer.
        

# Appendix

Seller Academy Cancel/Refund/Return TikTok Shop Policy  
US:

-   [https://seller-us.tiktok.com/university/essay?identity=1&role=1&knowledge\_id=3253210454181634&from=policy](https://seller-us.tiktok.com/university/essay?identity=1&role=1&knowledge_id=3253210454181634&from=policy)

GB:

-   Cancellation: [https://seller-uk.tiktok.com/university/essay?identity=1&role=1&knowledge\_id=7753822474782466&from=policy](https://seller-uk.tiktok.com/university/essay?identity=1&role=1&knowledge_id=7753822474782466&from=policy)
-   Refund and Return: [https://seller-uk.tiktok.com/university/essay?identity=1&role=1&knowledge\_id=7753847099623170&from=policy](https://seller-uk.tiktok.com/university/essay?identity=1&role=1&knowledge_id=7753847099623170&from=policy)

ID:

-   [https://seller-id.tiktok.com/university/essay?identity=1&role=1&knowledge\_id=6837727601690370&from=policy](https://seller-id.tiktok.com/university/essay?identity=1&role=1&knowledge_id=6837727601690370&from=policy)

TH:

-   [https://seller-th.tiktok.com/university/essay?identity=1&role=1&knowledge\_id=6837799362217730&from=policy](https://seller-th.tiktok.com/university/essay?identity=1&role=1&knowledge_id=6837799362217730&from=policy)

MY:

-   [https://seller-my.tiktok.com/university/essay?identity=1&role=1&knowledge\_id=7753775054259970&from=policy](https://seller-my.tiktok.com/university/essay?identity=1&role=1&knowledge_id=7753775054259970&from=policy)

VN:

-   [https://seller-vn.tiktok.com/university/essay?identity=1&role=1&knowledge\_id=6837773789234946&from=policy](https://seller-vn.tiktok.com/university/essay?identity=1&role=1&knowledge_id=6837773789234946&from=policy)

PH:

-   [https://seller-ph.tiktok.com/university/essay?identity=1&role=1&knowledge\_id=7654203686340353&from=policy](https://seller-ph.tiktok.com/university/essay?identity=1&role=1&knowledge_id=7654203686340353&from=policy)

SG：

-   [https://seller-sg.tiktok.com/university/essay?identity=1&role=1&knowledge\_id=7654182014830338&from=policy](https://seller-sg.tiktok.com/university/essay?identity=1&role=1&knowledge_id=7654182014830338&from=policy)
