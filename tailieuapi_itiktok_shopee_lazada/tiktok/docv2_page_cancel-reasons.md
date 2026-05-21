# Cancel reasons

> Source: https://partner.tiktokshop.com/docv2/page/cancel-reasons
> Section: API Reference
> Scraped: 2026-05-20T23:46:01.350Z

---

📌 Different order statuses require different reasons. Check the status on the left in the table to find the applicable reasons, and include the reason name as a request parameter when making the API call. If the cancel reason is not applicable for the order status, the API will return a '25001021 Reason not match order status' error.

# US market

## Buyer Initiates Cancel Reason

The following reason is available for buyers when they initiate cancel request.

| **Order Status** | **Buyer available cancel reason** | **Reason name** |
| --- | --- | --- |
| 
`UNPAID`

 | 

Better price available

 | 

ecom\_order\_unpaid\_canceled\_reason\_better\_price

 |
| 

 | 

No longer needed

 | 

ecom\_order\_unpaid\_canceled\_reason\_no\_longer\_needed

 |
| 

 | 

Need to change payment method

 | 

ecom\_order\_to\_ship\_canceled\_reason\_change\_payment\_method

 |
| 

 | 

Bought by mistake

 | 

ecom\_order\_to\_ship\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

Better price available

 | 

ecom\_order\_to\_ship\_canceled\_reason\_better\_price

 |
| 

 | 

Incorrect shipping address

 | 

ecom\_order\_to\_ship\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Forgot to apply coupons

 | 

ecom\_order\_to\_ship\_canceled\_reason\_forgot\_to\_apply\_coupons

 |
| 

`ON_HOLD`  
`AWAITING_SHIPMENT`

 | 

Need to change shipping address

 | 

ecom\_order\_unpaid\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Better price available

 | 

ecom\_order\_unpaid\_canceled\_reason\_better\_price

 |
| 

 | 

No longer needed

 | 

ecom\_order\_unpaid\_canceled\_reason\_no\_longer\_needed

 |
| 

 | 

Need to change payment method

 | 

ecom\_order\_to\_ship\_canceled\_reason\_change\_payment\_method

 |
| 

 | 

Discount not as expected

 | 

ecom\_order\_to\_ship\_canceled\_reason\_discount\_not\_expected

 |
| 

 | 

Bought by mistake

 | 

ecom\_order\_to\_ship\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

High shipping fee

 | 

ecom\_order\_to\_ship\_canceled\_reason\_high\_delivery\_costs

 |
| 

 | 

Better price available

 | 

ecom\_order\_to\_ship\_canceled\_reason\_better\_price

 |
| 

 | 

Incorrect shipping address

 | 

ecom\_order\_to\_ship\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Item wasn't shipped on time

 | 

ecom\_order\_to\_ship\_canceled\_reason\_not\_shipped\_on\_time

 |
| 

 | 

No longer needed

 | 

ecom\_order\_to\_ship\_canceled\_reason\_no\_longer\_needed

 |
| 

 | 

Forgot to apply coupons

 | 

ecom\_order\_to\_ship\_canceled\_reason\_forgot\_to\_apply\_coupons

 |
| 

 | 

Don't want to wait

 | 

US\_order\_to\_ship\_canceled\_reason\_not\_shipped\_on\_time\_before\_RTS\_prod

 |

## Seller Reject Cancel Request Reason

The reason the seller can use to reject the buyer's cancel request

| **Seller available Reject Cancel reason** | **Reason name** |
| --- | --- |
| 
Products were shipped in multiple packages.

 | 

ecom\_reverse\_reject\_refund\_request\_package\_delivery\_multiple\_boxes

 |
| 

Unable to change address

 | 

seller\_reject\_apply\_unable\_to\_change\_address

 |
| 

Product shipped. Tracking updates will be available soon.

 | 

ecom\_reverse\_reject\_cancel\_reason\_product\_packed

 |

## Seller Initiates Cancel Reason

The available reason of seller initiates cancel

| **Order Status** | **Seller available initiate cancel reason** | **Reason name** |
| --- | --- | --- |
| 
`UNPAID`

 | 

Out of stock

 | 

seller\_cancel\_unpaid\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_unpaid\_reason\_wrong\_price

 |
| 

 | 

Buyer did not pay on time

 | 

seller\_cancel\_unpaid\_reason\_buyer\_hasnt\_paid\_within\_time\_allowed

 |
| 

 | 

Buyer requested cancellation

 | 

seller\_cancel\_unpaid\_reason\_buyer\_requested\_cancellation

 |
| 

`ON_HOLD` and `AWAITING_SHIPMENT`

 | 

Out of stock

 | 

seller\_cancel\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_reason\_wrong\_price

 |
| 

 | 

Unable to deliver to buyer address

 | 

seller\_cancel\_paid\_reason\_address\_not\_deliver

 |
| 

 | 

Buyer requested cancellation

 | 

seller\_cancel\_paid\_reason\_buyer\_requested\_cancellation

 |

## SYSTEM Cancel Reason

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
Buyer payment overdue

 | 

system\_cancel\_unpaid\_time\_out

 |
| 

Failed to pass risk review

 | 

system\_cancel\_anti\_span\_block

 |
| 

Inbound failed

 | 

system\_cancel\_cross\_border\_entry\_warehouse\_failed\_reason2

 |
| 

Order cancelled by system

 | 

package\_cancel

 |
| 

Order cancelled by system

 | 

platform\_cancel

 |
| 

Package rejected

 | 

returned\_to\_shipper\_refused

 |
| 

Package delivery failed

 | 

returned\_to\_shipper\_other

 |
| 

Package damaged

 | 

package\_damaged

 |
| 

Package lost

 | 

package\_lost

 |
| 

Late delivery

 | 

threepl\_breach

 |
| 

Package scrapped

 | 

package\_scrap

 |
| 

Automatically cancelled due to collection time out

 | 

system\_cancel\_order\_reason\_shipping\_timeout

 |
| 

This order has been automatically canceled as you didn't provide your delivery address.

 | 

system\_cancel\_add\_address\_overtime

 |
| 

Automatically cancelled due to untraceable tracking number

 | 

system\_cancel\_order\_untracked

 |
| 

Order canceled by the system

 | 

ecom\_cb\_sc\_out\_return

 |
| 

Automatically canceled as not delivered within 30 days of estimated time of delivery

 | 

system\_cancel\_order\_exceed\_edt\_not\_arrive

 |
| 

Buyer hasn't paid

 | 

ecom\_order\_unpaid\_canceled\_reason\_not\_want\_pay

 |

## Operator Cancel

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
We have identified a risk involved with your order and cancelled your order for safety reasons to protect your transaction.

 | 

system\_cancel\_order\_reason\_potential\_fraud

 |
| 

Order canceled by system.

 | 

system\_cancel\_order\_reason\_experience\_related

 |
| 

Failed to pass risk review

 | 

operator\_cancel\_risk\_control\_block

 |
| 

As your product violates the platform's negative review policy, we canceled this transaction. We recommend you process the buyer's after-sales request.

 | 

system\_cancel\_order\_reason\_nrr\_related

 |

* * *

# UK market

## Buyer Initiates Cancel Reason

The following reason is available for buyers when they initiate cancel request.

| **Order Status** | **Buyer available cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Desired payment method not available

 | 

ecom\_order\_unpaid\_canceled\_reason\_payment\_method\_not\_available\_uk

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_unpaid\_canceled\_reason\_wrong\_delivery\_info\_uk

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_unpaid\_canceled\_reason\_created\_by\_mistakes\_uk

 |
| 

 | 

Better price available

 | 

ecom\_order\_unpaid\_canceled\_reason\_better\_price\_uk

 |
| 

 | 

No longer needed

 | 

ecom\_order\_unpaid\_canceled\_reason\_no\_longer\_needed\_uk

 |
| 

 | 

Other

 | 

ecom\_order\_unpaid\_canceled\_reason\_other\_uk

 |
| 

 | 

Discount not as expected

 | 

ecom\_order\_to\_ship\_canceled\_reason\_discount\_not\_expected\_uk

 |
| 

 | 

High delivery costs

 | 

ecom\_order\_to\_ship\_canceled\_reason\_high\_delivery\_costs\_uk

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT

 | 

No longer needed

 | 

ecom\_order\_to\_ship\_canceled\_reason\_no\_longer\_needed\_uk

 |
| 

 | 

Need to change payment method

 | 

ecom\_order\_to\_ship\_canceled\_reason\_change\_payment\_method\_uk

 |
| 

 | 

Discount not as expected

 | 

ecom\_order\_to\_ship\_canceled\_reason\_discount\_not\_expected\_uk

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_to\_ship\_canceled\_reason\_created\_by\_mistakes\_uk

 |
| 

 | 

High delivery costs

 | 

ecom\_order\_to\_ship\_canceled\_reason\_high\_delivery\_costs\_uk

 |
| 

 | 

Better price available

 | 

ecom\_order\_to\_ship\_canceled\_reason\_better\_price\_uk

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_to\_ship\_canceled\_reason\_wrong\_delivery\_info\_uk

 |
| 

 | 

Late shipment

 | 

buyer\_cancel\_late\_shipment

 |
| 

 | 

Other

 | 

ecom\_order\_unpaid\_canceled\_reason\_other\_uk

 |

## Seller Reject Cancel Request Reason

The reason the seller can use to reject the buyer's cancel request

| **Seller available Reject Cancel reason** | **Reason name** |
| --- | --- |
| 
Invalid reason for cancellation

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_invalid\_cancellation\_reason\_uk

 |
| 

Product delivery is on schedule

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_delivered\_uk

 |
| 

Reached an agreement with the buyer

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_buyer\_agree\_uk

 |
| 

Product has been packed

 | 

seller\_reject\_apply\_product\_has\_been\_packed

 |

## Seller Initiates Cancel Reason

The available reason of seller initiates cancel

| **Order Status** | **Seller available initiate cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Out of stock

 | 

seller\_cancel\_unpaid\_reason\_out\_of\_stock\_uk

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_unpaid\_reason\_wrong\_price\_uk

 |
| 

 | 

Buyer did not pay on time

 | 

seller\_cancel\_unpaid\_reason\_buyer\_hasnt\_paid\_within\_time\_allowed\_uk

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT

 | 

Pricing error

 | 

seller\_cancel\_reason\_wrong\_price\_uk

 |
| 

 | 

Unable to deliver to buyer address

 | 

seller\_cancel\_paid\_reason\_address\_not\_deliver\_uk

 |
| 

 | 

Out of stock

 | 

seller\_cancel\_reason\_out\_of\_stock\_uk

 |

## SYSTEM Cancel Reason

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
Buyer payment overdue

 | 

system\_cancel\_unpaid\_time\_out\_uk

 |
| 

Failed to pass risk review

 | 

system\_cancel\_anti\_span\_block\_uk

 |
| 

Inbound failed

 | 

system\_cancel\_cross\_border\_entry\_warehouse\_failed\_reason2\_uk

 |
| 

Order cancelled by system

 | 

package\_cancel\_uk

 |
| 

Order cancelled by system

 | 

platform\_cancel\_uk

 |
| 

Package rejected

 | 

returned\_to\_shipper\_refused\_uk

 |
| 

Package delivery failed

 | 

returned\_to\_shipper\_other\_uk

 |
| 

Package damaged

 | 

package\_damaged\_uk

 |
| 

Package lost

 | 

package\_lost\_uk

 |
| 

Late delivery

 | 

threepl\_breach\_uk

 |
| 

Package scrapped

 | 

package\_scrap\_uk

 |
| 

Automatically cancelled due to collection time out

 | 

system\_cancel\_order\_reason\_shipping\_timeout\_uk

 |
| 

This order has been automatically canceled as you didn't provide your delivery address.

 | 

system\_cancel\_add\_address\_overtime

 |
| 

Automatically cancelled due to untraceable tracking number

 | 

system\_cancel\_order\_untracked\_uk

 |
| 

Order canceled by the system

 | 

ecom\_cb\_sc\_out\_return

 |
| 

Buyer hasn't paid

 | 

ecom\_order\_unpaid\_canceled\_reason\_not\_want\_pay

 |

## Operator Cancel

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
We have identified a risk involved with your order and cancelled your order for safety reasons to protect your transaction.

 | 

system\_cancel\_order\_reason\_potential\_fraud

 |
| 

Order canceled by system.

 | 

system\_cancel\_order\_reason\_experience\_related

 |
| 

Failed to pass risk review

 | 

operator\_cancel\_risk\_control\_block

 |
| 

As your product violates the platform's negative review policy, we canceled this transaction. We recommend you process the buyer's after-sales request.

 | 

system\_cancel\_order\_reason\_nrr\_related

 |

* * *

# ID market

## Buyer Initiates Cancel Reason

The following reason is available for buyers when they initiate cancel request.

| **Order Status** | **Buyer available cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Desired payment method not available

 | 

ecom\_order\_unpaid\_canceled\_reason\_payment\_method\_not\_available

 |
| 

 | 

Need to input/change coupon code

 | 

buyer\_cancel\_need\_to\_input/change\_coupon\_code

 |
| 

 | 

Wrong item variation (color, size, etc.)

 | 

buyer\_cancel\_wrong\_item\_variation\_(colour,\_size,\_etc.)

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_unpaid\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_unpaid\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

Better price available

 | 

ecom\_order\_unpaid\_canceled\_reason\_better\_price

 |
| 

 | 

No longer needed

 | 

ecom\_order\_unpaid\_canceled\_reason\_no\_longer\_needed

 |
| 

 | 

Other

 | 

ecom\_order\_unpaid\_canceled\_reason\_other

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT

 | 

Need to change payment method

 | 

ecom\_order\_to\_ship\_canceled\_reason\_change\_payment\_method

 |
| 

 | 

Discount not as expected

 | 

ecom\_order\_to\_ship\_canceled\_reason\_discount\_not\_expected

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_to\_ship\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

High delivery costs

 | 

ecom\_order\_to\_ship\_canceled\_reason\_high\_delivery\_costs

 |
| 

 | 

Better price available

 | 

ecom\_order\_to\_ship\_canceled\_reason\_better\_price

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_to\_ship\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Product wasn't shipped on time

 | 

ecom\_order\_to\_ship\_canceled\_reason\_not\_shipped\_on\_time

 |

## Seller Reject Cancel Request Reason

The reason the seller can use to reject the buyer's cancel request

| **Seller available Reject Cancel reason** | **Reason name** |
| --- | --- |
| 
Invalid reason for cancellation

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_invalid\_cancellation\_reason

 |
| 

Product delivery is on schedule

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_delivered

 |
| 

Reached an agreement with the buyer

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_buyer\_agree

 |
| 

Product has been packed

 | 

seller\_reject\_apply\_product\_has\_been\_packed

 |
| 

Order canceled by system

 | 

system\_cancel\_failed\_due\_to\_tts

 |

## Seller Initiates Cancel Reason

The available reason of seller initiates cancel

| **Order Status** | **Seller available initiate cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Out of stock

 | 

seller\_cancel\_unpaid\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_unpaid\_reason\_wrong\_price

 |
| 

 | 

Buyer did not pay on time

 | 

seller\_cancel\_unpaid\_reason\_buyer\_hasnt\_paid\_within\_time\_allowed

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT

 | 

Out of stock

 | 

seller\_cancel\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_reason\_wrong\_price

 |
| 

 | 

Unable to deliver to buyer address

 | 

seller\_cancel\_paid\_reason\_address\_not\_deliver

 |

## SYSTEM Cancel Reason

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
Buyer payment overdue

 | 

system\_cancel\_unpaid\_time\_out

 |
| 

Failed to pass risk review

 | 

system\_cancel\_anti\_span\_block

 |
| 

Order cancelled by system

 | 

package\_cancel

 |
| 

Order cancelled by system

 | 

platform\_cancel

 |
| 

Package rejected

 | 

returned\_to\_shipper\_refused

 |
| 

Package delivery failed

 | 

returned\_to\_shipper\_other

 |
| 

Package damaged

 | 

package\_damaged

 |
| 

Package lost

 | 

package\_lost

 |
| 

Late delivery

 | 

threepl\_breach

 |
| 

Package scrapped

 | 

package\_scrap

 |
| 

Automatically cancelled due to collection time out

 | 

system\_cancel\_order\_reason\_shipping\_timeout

 |
| 

Automatically cancelled due to recharge time out

 | 

system\_cancel\_virtual\_timeout

 |
| 

Top up failed

 | 

system\_cancel\_virtual\_top\_up\_failed

 |
| 

Invalid phone number

 | 

system\_cancel\_virtual\_phone\_number\_invalid

 |
| 

This order has been automatically canceled as you didn't provide your delivery address.

 | 

system\_cancel\_add\_address\_overtime

 |
| 

Order canceled by the system

 | 

ecom\_cb\_sc\_out\_return

 |
| 

Automatically cancelled due to untraceable tracking number

 | 

system\_cancel\_order\_untracked

 |
| 

Buyer hasn't paid

 | 

ecom\_order\_unpaid\_canceled\_reason\_not\_want\_pay

 |

## Operator Cancel

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
We have identified a risk involved with your order and cancelled your order for safety reasons to protect your transaction.

 | 

system\_cancel\_order\_reason\_potential\_fraud

 |
| 

Order canceled by system.

 | 

system\_cancel\_order\_reason\_experience\_related

 |
| 

Failed to pass risk review

 | 

operator\_cancel\_risk\_control\_block

 |
| 

As your product violates the platform's negative review policy, we canceled this transaction. We recommend you process the buyer's after-sales request.

 | 

system\_cancel\_order\_reason\_nrr\_related

 |

* * *

# MY market

## Buyer Initiates Cancel Reason

The following reason is available for buyers when they initiate cancel request.

| **Order Status** | **Buyer available cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Need to input/change coupon code

 | 

buyer\_cancel\_need\_to\_input/change\_coupon\_code

 |
| 

 | 

Wrong item variation (color, size, etc.)

 | 

buyer\_cancel\_wrong\_item\_variation\_(colour,\_size,\_etc.)

 |
| 

 | 

Desired payment method not available

 | 

ecom\_order\_unpaid\_canceled\_reason\_payment\_method\_not\_available

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_unpaid\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_unpaid\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

Better price available

 | 

ecom\_order\_unpaid\_canceled\_reason\_better\_price

 |
| 

 | 

Other

 | 

ecom\_order\_unpaid\_canceled\_reason\_other

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT

 | 

Need to change payment method

 | 

ecom\_order\_to\_ship\_canceled\_reason\_change\_payment\_method

 |
| 

 | 

Discount not as expected

 | 

ecom\_order\_to\_ship\_canceled\_reason\_discount\_not\_expected

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_to\_ship\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

High delivery costs

 | 

ecom\_order\_to\_ship\_canceled\_reason\_high\_delivery\_costs

 |
| 

 | 

Better price available

 | 

ecom\_order\_to\_ship\_canceled\_reason\_better\_price

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_to\_ship\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Product wasn't shipped on time

 | 

ecom\_order\_to\_ship\_canceled\_reason\_not\_shipped\_on\_time

 |

## Seller Reject Cancel Request Reason

| **Seller available Reject Cancel reason** | **Reason name** |
| --- | --- |
| 
Invalid reason for cancellation

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_invalid\_cancellation\_reason

 |
| 

Product delivery is on schedule

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_delivered

 |
| 

Reached an agreement with the buyer

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_buyer\_agree

 |
| 

Product has been packed

 | 

seller\_reject\_apply\_product\_has\_been\_packed

 |

## Seller Initiates Cancel Reason

The available reason of seller initiates cancel

| **Order Status** | **Seller available initiate cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Out of stock

 | 

seller\_cancel\_unpaid\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_unpaid\_reason\_wrong\_price

 |
| 

 | 

Buyer did not pay on time

 | 

seller\_cancel\_unpaid\_reason\_buyer\_hasnt\_paid\_within\_time\_allowed

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT

 | 

Out of stock

 | 

seller\_cancel\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_reason\_wrong\_price

 |
| 

 | 

Unable to deliver to buyer address

 | 

seller\_cancel\_paid\_reason\_address\_not\_deliver

 |

## SYSTEM Cancel Reason

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
Buyer payment overdue

 | 

system\_cancel\_unpaid\_time\_out

 |
| 

Failed to pass risk review

 | 

system\_cancel\_anti\_span\_block

 |
| 

Order cancelled by system

 | 

package\_cancel

 |
| 

Order cancelled by system

 | 

platform\_cancel

 |
| 

Package rejected

 | 

returned\_to\_shipper\_refused

 |
| 

Package delivery failed

 | 

returned\_to\_shipper\_other

 |
| 

Package damaged

 | 

package\_damaged

 |
| 

Package lost

 | 

package\_lost

 |
| 

Late delivery

 | 

threepl\_breach

 |
| 

Package scrapped

 | 

package\_scrap

 |
| 

Automatically cancelled due to collection time out

 | 

system\_cancel\_order\_reason\_shipping\_timeout

 |
| 

Inbound failed

 | 

system\_cancel\_cross\_border\_entry\_warehouse\_failed\_reason2

 |
| 

This order has been automatically canceled as you didn't provide your delivery address.

 | 

system\_cancel\_add\_address\_overtime

 |
| 

Order canceled by the system

 | 

ecom\_cb\_sc\_out\_return

 |
| 

Automatically cancelled due to untraceable tracking number

 | 

system\_cancel\_order\_untracked

 |
| 

Buyer hasn't paid

 | 

ecom\_order\_unpaid\_canceled\_reason\_not\_want\_pay

 |

## Operator Cancel

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
We have identified a risk involved with your order and cancelled your order for safety reasons to protect your transaction.

 | 

system\_cancel\_order\_reason\_potential\_fraud

 |
| 

Order canceled by system.

 | 

system\_cancel\_order\_reason\_experience\_related

 |
| 

Failed to pass risk review

 | 

operator\_cancel\_risk\_control\_block

 |
| 

As your product violates the platform's negative review policy, we canceled this transaction. We recommend you process the buyer's after-sales request.

 | 

system\_cancel\_order\_reason\_nrr\_related

 |

* * *

# PH market

## Buyer Initiates Cancel Reason

The following reason is available for buyers when they initiate cancel request.

| **Order Status** | **Buyer available cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Need to input/change coupon code

 | 

buyer\_cancel\_need\_to\_input/change\_coupon\_code

 |
| 

 | 

Wrong item variation (color, size, etc.)

 | 

buyer\_cancel\_wrong\_item\_variation\_(colour,\_size,\_etc.)

 |
| 

 | 

Desired payment method not available

 | 

ecom\_order\_unpaid\_canceled\_reason\_payment\_method\_not\_available

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_unpaid\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_unpaid\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

Better price available

 | 

ecom\_order\_unpaid\_canceled\_reason\_better\_price

 |
| 

 | 

Other

 | 

ecom\_order\_unpaid\_canceled\_reason\_other

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT

 | 

Need to change payment method

 | 

ecom\_order\_to\_ship\_canceled\_reason\_change\_payment\_method

 |
| 

 | 

Discount not as expected

 | 

ecom\_order\_to\_ship\_canceled\_reason\_discount\_not\_expected

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_to\_ship\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

High delivery costs

 | 

ecom\_order\_to\_ship\_canceled\_reason\_high\_delivery\_costs

 |
| 

 | 

Better price available

 | 

ecom\_order\_to\_ship\_canceled\_reason\_better\_price

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_to\_ship\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Product wasn't shipped on time

 | 

ecom\_order\_to\_ship\_canceled\_reason\_not\_shipped\_on\_time

 |

## Seller Reject Cancel Request Reason

The reason the seller can use to reject the buyer's cancel request

| **Seller available Reject Cancel reason** | **Reason name** |
| --- | --- |
| 
Invalid reason for cancellation

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_invalid\_cancellation\_reason

 |
| 

Product delivery is on schedule

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_delivered

 |
| 

Reached an agreement with the buyer

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_buyer\_agree

 |
| 

Product has been packed

 | 

seller\_reject\_apply\_product\_has\_been\_packed

 |

## Seller Initiates Cancel Reason

The available reason of seller initiates cancel

| **Order Status** | **Seller available initiate cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Out of stock

 | 

seller\_cancel\_unpaid\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_unpaid\_reason\_wrong\_price

 |
| 

 | 

Buyer did not pay on time

 | 

seller\_cancel\_unpaid\_reason\_buyer\_hasnt\_paid\_within\_time\_allowed

 |
| 

 | 

Buyer requested cancellation

 | 

seller\_cancel\_unpaid\_reason\_buyer\_requested\_cancellation

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT

 | 

Out of stock

 | 

seller\_cancel\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_reason\_wrong\_price

 |
| 

 | 

Unable to deliver to buyer address

 | 

seller\_cancel\_paid\_reason\_address\_not\_deliver

 |
| 

 | 

Buyer requested cancellation

 | 

seller\_cancel\_paid\_reason\_buyer\_requested\_cancellation

 |

## SYSTEM Cancel Reason

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
Buyer payment overdue

 | 

system\_cancel\_unpaid\_time\_out

 |
| 

Failed to pass risk review

 | 

system\_cancel\_anti\_span\_block

 |
| 

Order cancelled by system

 | 

package\_cancel

 |
| 

Order cancelled by system

 | 

platform\_cancel

 |
| 

Package rejected

 | 

returned\_to\_shipper\_refused

 |
| 

Package delivery failed

 | 

returned\_to\_shipper\_other

 |
| 

Package damaged

 | 

package\_damaged

 |
| 

Package lost

 | 

package\_lost

 |
| 

Late delivery

 | 

threepl\_breach

 |
| 

Package scrapped

 | 

package\_scrap

 |
| 

Automatically cancelled due to collection time out

 | 

system\_cancel\_order\_reason\_shipping\_timeout

 |
| 

Inbound failed

 | 

system\_cancel\_cross\_border\_entry\_warehouse\_failed\_reason2

 |
| 

This order has been automatically canceled as you didn't provide your delivery address.

 | 

system\_cancel\_add\_address\_overtime

 |
| 

Order canceled by the system

 | 

ecom\_cb\_sc\_out\_return

 |
| 

Automatically cancelled due to untraceable tracking number

 | 

system\_cancel\_order\_untracked

 |
| 

Buyer hasn't paid

 | 

ecom\_order\_unpaid\_canceled\_reason\_not\_want\_pay

 |

## Operator Cancel

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
We have identified a risk involved with your order and cancelled your order for safety reasons to protect your transaction.

 | 

system\_cancel\_order\_reason\_potential\_fraud

 |
| 

Order canceled by system.

 | 

system\_cancel\_order\_reason\_experience\_related

 |
| 

Failed to pass risk review

 | 

operator\_cancel\_risk\_control\_block

 |
| 

As your product violates the platform's negative review policy, we canceled this transaction. We recommend you process the buyer's after-sales request.

 | 

system\_cancel\_order\_reason\_nrr\_related

 |

* * *

# SG market

## Buyer Initiates Cancel Reason

The following reason is available for buyers when they initiate cancel request.

| **Order Status** | **Buyer available cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Need to input/change coupon code

 | 

buyer\_cancel\_need\_to\_input/change\_coupon\_code

 |
| 

 | 

Wrong item variation (color, size, etc.)

 | 

buyer\_cancel\_wrong\_item\_variation\_(colour,\_size,\_etc.)

 |
| 

 | 

Desired payment method not available

 | 

ecom\_order\_unpaid\_canceled\_reason\_payment\_method\_not\_available

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_unpaid\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_unpaid\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

Better price available

 | 

ecom\_order\_unpaid\_canceled\_reason\_better\_price

 |
| 

 | 

Other

 | 

ecom\_order\_unpaid\_canceled\_reason\_other

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT

 | 

Need to change payment method

 | 

ecom\_order\_to\_ship\_canceled\_reason\_change\_payment\_method

 |
| 

 | 

Discount not as expected

 | 

ecom\_order\_to\_ship\_canceled\_reason\_discount\_not\_expected

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_to\_ship\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

High delivery costs

 | 

ecom\_order\_to\_ship\_canceled\_reason\_high\_delivery\_costs

 |
| 

 | 

Better price available

 | 

ecom\_order\_to\_ship\_canceled\_reason\_better\_price

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_to\_ship\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Product wasn't shipped on time

 | 

ecom\_order\_to\_ship\_canceled\_reason\_not\_shipped\_on\_time

 |

## Seller Reject Cancel Request Reason

The reason the seller can use to reject the buyer's cancel request

| **Seller available Reject Cancel reason** | **Reason name** |
| --- | --- |
| 
Invalid reason for cancellation

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_invalid\_cancellation\_reason

 |
| 

Product delivery is on schedule

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_delivered

 |
| 

Reached an agreement with the buyer

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_buyer\_agree

 |
| 

Product has been packed

 | 

seller\_reject\_apply\_product\_has\_been\_packed

 |

## Seller Initiates Cancel Reason

The available reason of seller initiates cancel

| **Order Status** | **Seller available initiate cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Out of stock

 | 

seller\_cancel\_unpaid\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_unpaid\_reason\_wrong\_price

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT

 | 

Out of stock

 | 

seller\_cancel\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_reason\_wrong\_price

 |
| 

 | 

Unable to deliver to buyer address

 | 

seller\_cancel\_unable\_to\_deliver\_to\_buyer\_address

 |

## SYSTEM Cancel Reason

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
Buyer payment overdue

 | 

system\_cancel\_unpaid\_time\_out

 |
| 

Failed to pass risk review

 | 

system\_cancel\_anti\_span\_block

 |
| 

Order cancelled by system

 | 

package\_cancel

 |
| 

Order cancelled by system

 | 

platform\_cancel

 |
| 

Package rejected

 | 

returned\_to\_shipper\_refused

 |
| 

Package delivery failed

 | 

returned\_to\_shipper\_other

 |
| 

Package damaged

 | 

package\_damaged

 |
| 

Package lost

 | 

package\_lost

 |
| 

Late delivery

 | 

threepl\_breach

 |
| 

Package scrapped

 | 

package\_scrap

 |
| 

Automatically cancelled due to collection time out

 | 

system\_cancel\_order\_reason\_shipping\_timeout

 |
| 

Inbound failed

 | 

system\_cancel\_cross\_border\_entry\_warehouse\_failed\_reason2

 |
| 

This order has been automatically canceled as you didn't provide your delivery address.

 | 

system\_cancel\_add\_address\_overtime

 |
| 

Order canceled by the system

 | 

ecom\_cb\_sc\_out\_return

 |
| 

Automatically cancelled due to untraceable tracking number

 | 

system\_cancel\_order\_untracked

 |
| 

Buyer hasn't paid

 | 

ecom\_order\_unpaid\_canceled\_reason\_not\_want\_pay

 |

## Operator Cancel

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
We have identified a risk involved with your order and cancelled your order for safety reasons to protect your transaction.

 | 

system\_cancel\_order\_reason\_potential\_fraud

 |
| 

Order canceled by system.

 | 

system\_cancel\_order\_reason\_experience\_related

 |
| 

Failed to pass risk review

 | 

operator\_cancel\_risk\_control\_block

 |
| 

As your product violates the platform's negative review policy, we canceled this transaction. We recommend you process the buyer's after-sales request.

 | 

system\_cancel\_order\_reason\_nrr\_related

 |

* * *

# TH market

## Buyer Initiates Cancel Reason

The following reason is available for buyers when they initiate cancel request.

| **Order Status** | **Buyer available cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Wrong item variation (color, size, etc.)

 | 

buyer\_cancel\_wrong\_item\_variation\_(colour,\_size,\_etc.)

 |
| 

 | 

Need to input/change coupon code

 | 

buyer\_cancel\_need\_to\_input/change\_coupon\_code

 |
| 

 | 

Desired payment method not available

 | 

ecom\_order\_unpaid\_canceled\_reason\_payment\_method\_not\_available

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_unpaid\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_unpaid\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

Better price available

 | 

ecom\_order\_unpaid\_canceled\_reason\_better\_price

 |
| 

 | 

No longer needed

 | 

ecom\_order\_unpaid\_canceled\_reason\_no\_longer\_needed

 |
| 

 | 

Other

 | 

ecom\_order\_unpaid\_canceled\_reason\_other

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT

 | 

Need to change payment method

 | 

ecom\_order\_to\_ship\_canceled\_reason\_change\_payment\_method

 |
| 

 | 

Discount not as expected

 | 

ecom\_order\_to\_ship\_canceled\_reason\_discount\_not\_expected

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_to\_ship\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

High delivery costs

 | 

ecom\_order\_to\_ship\_canceled\_reason\_high\_delivery\_costs

 |
| 

 | 

Better price available

 | 

ecom\_order\_to\_ship\_canceled\_reason\_better\_price

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_to\_ship\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Product wasn't shipped on time

 | 

ecom\_order\_to\_ship\_canceled\_reason\_not\_shipped\_on\_time

 |
| 

 | 

No longer needed

 | 

ecom\_order\_to\_ship\_canceled\_reason\_no\_longer\_needed

 |

## Seller Reject Cancel Request Reason

The reason the seller can use to reject the buyer's cancel request

| **Seller available Reject Cancel reason** | **Reason name** |
| --- | --- |
| 
Invalid reason for cancellation

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_invalid\_cancellation\_reason

 |
| 

Product delivery is on schedule

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_delivered

 |
| 

Reached an agreement with the buyer

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_buyer\_agree

 |
| 

Product has been packed

 | 

seller\_reject\_apply\_product\_has\_been\_packed

 |

## Seller Initiates Cancel Reason

The available reason of seller initiates cancel

| **Order Status** | **Seller available initiate cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Out of stock

 | 

seller\_cancel\_unpaid\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_unpaid\_reason\_wrong\_price

 |
| 

 | 

Buyer did not pay on time

 | 

seller\_cancel\_unpaid\_reason\_buyer\_hasnt\_paid\_within\_time\_allowed

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT

 | 

Out of stock

 | 

seller\_cancel\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_reason\_wrong\_price

 |
| 

 | 

Unable to deliver to buyer address

 | 

seller\_cancel\_paid\_reason\_address\_not\_deliver

 |

## SYSTEM Cancel Reason

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
Buyer payment overdue

 | 

system\_cancel\_unpaid\_time\_out

 |
| 

Failed to pass risk review

 | 

system\_cancel\_anti\_span\_block

 |
| 

Order cancelled by system

 | 

package\_cancel

 |
| 

Order cancelled by system

 | 

platform\_cancel

 |
| 

Package rejected

 | 

returned\_to\_shipper\_refused

 |
| 

Package delivery failed

 | 

returned\_to\_shipper\_other

 |
| 

Package damaged

 | 

package\_damaged

 |
| 

Package lost

 | 

package\_lost

 |
| 

Late delivery

 | 

threepl\_breach

 |
| 

Package scrapped

 | 

package\_scrap

 |
| 

Automatically cancelled due to collection time out

 | 

system\_cancel\_order\_reason\_shipping\_timeout

 |
| 

Inbound failed

 | 

system\_cancel\_cross\_border\_entry\_warehouse\_failed\_reason2

 |
| 

This order has been automatically canceled as you didn't provide your delivery address.

 | 

system\_cancel\_add\_address\_overtime

 |
| 

Order canceled by the system

 | 

ecom\_cb\_sc\_out\_return

 |
| 

Automatically cancelled due to untraceable tracking number

 | 

system\_cancel\_order\_untracked

 |
| 

Buyer hasn't paid

 | 

ecom\_order\_unpaid\_canceled\_reason\_not\_want\_pay

 |

## Operator Cancel

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
We have identified a risk involved with your order and cancelled your order for safety reasons to protect your transaction.

 | 

system\_cancel\_order\_reason\_potential\_fraud

 |
| 

Order canceled by system.

 | 

system\_cancel\_order\_reason\_experience\_related

 |
| 

Failed to pass risk review

 | 

operator\_cancel\_risk\_control\_block

 |
| 

As your product violates the platform's negative review policy, we canceled this transaction. We recommend you process the buyer's after-sales request.

 | 

system\_cancel\_order\_reason\_nrr\_related

 |

* * *

# VN market

## Buyer Initiates Cancel Reason

The following reason is available for buyers when they initiate cancel request.

| **Order Status** | **Buyer available cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Need to input/change coupon code

 | 

buyer\_cancel\_need\_to\_input/change\_coupon\_code

 |
| 

 | 

Wrong item variation (color, size, etc.)

 | 

buyer\_cancel\_wrong\_item\_variation\_(colour,\_size,\_etc.)

 |
| 

 | 

Desired payment method not available

 | 

ecom\_order\_unpaid\_canceled\_reason\_payment\_method\_not\_available

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_unpaid\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_unpaid\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

Better price available

 | 

ecom\_order\_unpaid\_canceled\_reason\_better\_price

 |
| 

 | 

Other

 | 

ecom\_order\_unpaid\_canceled\_reason\_other

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT

 | 

Need to change payment method

 | 

ecom\_order\_to\_ship\_canceled\_reason\_change\_payment\_method

 |
| 

 | 

Discount not as expected

 | 

ecom\_order\_to\_ship\_canceled\_reason\_discount\_not\_expected

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_to\_ship\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

High delivery costs

 | 

ecom\_order\_to\_ship\_canceled\_reason\_high\_delivery\_costs

 |
| 

 | 

Better price available

 | 

ecom\_order\_to\_ship\_canceled\_reason\_better\_price

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_to\_ship\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Product wasn't shipped on time

 | 

ecom\_order\_to\_ship\_canceled\_reason\_not\_shipped\_on\_time

 |

## Seller Reject Cancel Request Reason

The reason the seller can use to reject the buyer's cancel request

| **Seller available Reject Cancel reason** | **Reason name** |
| --- | --- |
| 
Invalid reason for cancellation

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_invalid\_cancellation\_reason

 |
| 

Product delivery is on schedule

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_delivered

 |
| 

Reached an agreement with the buyer

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_buyer\_agree

 |
| 

Product has been packed

 | 

seller\_reject\_apply\_product\_has\_been\_packed

 |

## Seller Initiates Cancel Reason

The available reason of seller initiates cancel

| **Order Status** | **Seller available initiate cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Out of stock

 | 

seller\_cancel\_unpaid\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_unpaid\_reason\_wrong\_price

 |
| 

 | 

Buyer did not pay on time

 | 

seller\_cancel\_unpaid\_reason\_buyer\_hasnt\_paid\_within\_time\_allowed

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT

 | 

Out of stock

 | 

seller\_cancel\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_reason\_wrong\_price

 |
| 

 | 

Unable to deliver to buyer address

 | 

seller\_cancel\_paid\_reason\_address\_not\_deliver

 |

## SYSTEM Cancel Reason

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
Buyer payment overdue

 | 

system\_cancel\_unpaid\_time\_out

 |
| 

Failed to pass risk review

 | 

system\_cancel\_anti\_span\_block

 |
| 

Order cancelled by system

 | 

package\_cancel

 |
| 

Order cancelled by system

 | 

platform\_cancel

 |
| 

Package rejected

 | 

returned\_to\_shipper\_refused

 |
| 

Package delivery failed

 | 

returned\_to\_shipper\_other

 |
| 

Package damaged

 | 

package\_damaged

 |
| 

Package lost

 | 

package\_lost

 |
| 

Late delivery

 | 

threepl\_breach

 |
| 

Package scrapped

 | 

package\_scrap

 |
| 

Automatically cancelled due to collection time out

 | 

system\_cancel\_order\_reason\_shipping\_timeout

 |
| 

Inbound failed

 | 

system\_cancel\_cross\_border\_entry\_warehouse\_failed\_reason2

 |
| 

This order has been automatically canceled as you didn't provide your delivery address.

 | 

system\_cancel\_add\_address\_overtime

 |
| 

Order canceled by the system

 | 

ecom\_cb\_sc\_out\_return

 |
| 

Automatically cancelled due to untraceable tracking number

 | 

system\_cancel\_order\_untracked

 |
| 

Buyer hasn't paid

 | 

ecom\_order\_unpaid\_canceled\_reason\_not\_want\_pay

 |

## Operator Cancel

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
We have identified a risk involved with your order and cancelled your order for safety reasons to protect your transaction.

 | 

system\_cancel\_order\_reason\_potential\_fraud

 |
| 

Order canceled by system.

 | 

system\_cancel\_order\_reason\_experience\_related

 |
| 

Failed to pass risk review

 | 

operator\_cancel\_risk\_control\_block

 |
| 

As your product violates the platform's negative review policy, we canceled this transaction. We recommend you process the buyer's after-sales request.

 | 

system\_cancel\_order\_reason\_nrr\_related

 |

* * *

# MX market

## Buyer Initiates Cancel Reason

The following reason is available for buyers when they initiate cancel request.

| **Order Status** | **Buyer available cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

No longer needed

 | 

ecom\_order\_to\_ship\_canceled\_reason\_no\_longer\_needed

 |
| 

 | 

Bought by mistake

 | 

ecom\_order\_to\_ship\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

Forgot to apply coupons

 | 

ecom\_order\_to\_ship\_canceled\_reason\_forgot\_to\_apply\_coupons

 |
| 

 | 

Incorrect shipping address

 | 

ecom\_order\_to\_ship\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Better price available

 | 

ecom\_order\_to\_ship\_canceled\_reason\_better\_price

 |
| 

ON\_HOLD  
AWAITING\_SHIPMENT

 | 

No longer needed

 | 

ecom\_order\_to\_ship\_canceled\_reason\_no\_longer\_needed

 |
| 

 | 

Bought by mistake

 | 

ecom\_order\_to\_ship\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

Forgot to apply coupons

 | 

ecom\_order\_to\_ship\_canceled\_reason\_forgot\_to\_apply\_coupons

 |
| 

 | 

Incorrect shipping address

 | 

ecom\_order\_to\_ship\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Item wasn't shipped on time

 | 

ecom\_order\_to\_ship\_canceled\_reason\_not\_shipped\_on\_time

 |
| 

 | 

Better price available

 | 

ecom\_order\_to\_ship\_canceled\_reason\_better\_price

 |

## Seller Reject Cancel Request Reason

The reason the seller can use to reject the buyer's cancel request

| **Seller available Reject Cancel reason** | **Reason name** |
| --- | --- |
| 
Product shipped. Tracking updates will be available soon.

 | 

seller\_reject\_apply\_product\_has\_been\_packed

 |
| 

You have reached an agreement with the customer

 | 

reverse\_reject\_request\_reason\_5

 |
| 

Unable to change address

 | 

seller\_reject\_apply\_unable\_to\_change\_address

 |

## Seller Initiates Cancel Reason

The available reason of seller initiates cancel

| **Order Status** | **Seller available initiate cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Out of stock

 | 

seller\_cancel\_unpaid\_reason\_out\_of\_stock

 |
| 

 | 

Customer did not pay on time

 | 

seller\_cancel\_unpaid\_reason\_buyer\_hasnt\_paid\_within\_time\_allowed

 |
| 

 | 

Customer requested cancellation

 | 

seller\_cancel\_unpaid\_reason\_buyer\_requested\_cancellation

 |
| 

ON\_HOLD  
AWAITING\_SHIPMENT

 | 

Out of stock

 | 

seller\_cancel\_reason\_out\_of\_stock

 |
| 

 | 

Customer requested cancellation

 | 

seller\_cancel\_paid\_reason\_buyer\_requested\_cancellation

 |
| 

 | 

Unable to deliver to buyer address

 | 

seller\_cancel\_paid\_reason\_address\_not\_deliver

 |

## SYSTEM Cancel Reason

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
Buyer payment overdue

 | 

system\_cancel\_unpaid\_time\_out

 |
| 

Failed to pass risk review

 | 

system\_cancel\_anti\_span\_block

 |
| 

Order cancelled by system

 | 

package\_cancel

 |
| 

Order cancelled by system

 | 

platform\_cancel

 |
| 

Package rejected

 | 

returned\_to\_shipper\_refused

 |
| 

Package delivery failed

 | 

returned\_to\_shipper\_other

 |
| 

Package damaged

 | 

package\_damaged

 |
| 

Package lost

 | 

package\_lost

 |
| 

Late delivery

 | 

threepl\_breach

 |
| 

Package scrapped

 | 

package\_scrap

 |
| 

Automatically cancelled due to collection time out

 | 

system\_cancel\_order\_reason\_shipping\_timeout

 |
| 

Inbound failed

 | 

system\_cancel\_cross\_border\_entry\_warehouse\_failed\_reason2

 |
| 

This order has been automatically canceled as you didn't provide your delivery address.

 | 

system\_cancel\_add\_address\_overtime

 |
| 

Order canceled by the system

 | 

ecom\_cb\_sc\_out\_return

 |
| 

Automatically cancelled due to untraceable tracking number

 | 

system\_cancel\_order\_untracked

 |
| 

Buyer hasn't paid

 | 

ecom\_order\_unpaid\_canceled\_reason\_not\_want\_pay

 |
| 

Automatically canceled as not delivered within 30 days of estimated time of delivery

 | 

system\_cancel\_order\_exceed\_edt\_not\_arrive

 |
| 

Customer hasn't paid

 | 

ecom\_order\_unpaid\_canceled\_reason\_not\_want\_pay

 |
| 

This order was canceled automatically as the customer's payment method could not be charged.

 | 

system\_cancel\_payment\_capture\_failed

 |
| 

This order was canceled automatically due to payment risk control interception.

 | 

system\_cancel\_payment\_risk\_review\_failed

 |
| 

This order was canceled automatically due to inventory shortage.

 | 

system\_cancel\_create\_valid\_order\_failed

 |

## Operator Cancel

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
We have identified a risk involved with your order and cancelled your order for safety reasons to protect your transaction.

 | 

system\_cancel\_order\_reason\_potential\_fraud

 |
| 

During our regular review, the order you placed was found to have potential issues that may result in an experience that doesn't meet our standards. To ensure you have a great shopping experience, we have proactively issued a full refund.

 | 

system\_cancel\_order\_reason\_experience\_related

 |
| 

Failed to pass risk review

 | 

operator\_cancel\_risk\_control\_block

 |
| 

As your product violates the platform's negative review policy, we canceled this transaction. We recommend you process the buyer's after-sales request.

 | 

system\_cancel\_order\_reason\_nrr\_related

 |

* * *

# BR market

## Buyer Initiates Cancel Reason

The following reason is available for buyers when they initiate cancel request.

| **Order Status** | **Buyer available cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

No longer needed

 | 

ecom\_order\_to\_ship\_canceled\_reason\_no\_longer\_needed

 |
| 

 | 

Bought by mistake

 | 

ecom\_order\_to\_ship\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

Forgot to apply coupons

 | 

ecom\_order\_to\_ship\_canceled\_reason\_forgot\_to\_apply\_coupons

 |
| 

 | 

Incorrect shipping address

 | 

ecom\_order\_to\_ship\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Better price available

 | 

ecom\_order\_to\_ship\_canceled\_reason\_better\_price

 |
| 

ON\_HOLD  
AWAITING\_SHIPMENT

 | 

No longer needed

 | 

ecom\_order\_to\_ship\_canceled\_reason\_no\_longer\_needed

 |
| 

 | 

Bought by mistake

 | 

ecom\_order\_to\_ship\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

Forgot to apply coupons

 | 

ecom\_order\_to\_ship\_canceled\_reason\_forgot\_to\_apply\_coupons

 |
| 

 | 

Incorrect shipping address

 | 

ecom\_order\_to\_ship\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Item wasn't shipped on time

 | 

ecom\_order\_to\_ship\_canceled\_reason\_not\_shipped\_on\_time

 |
| 

 | 

Better price available

 | 

ecom\_order\_to\_ship\_canceled\_reason\_better\_price

 |

## Seller Reject Cancel Request Reason

The reason the seller can use to reject the buyer's cancel request

| **Seller available Reject Cancel reason** | **Reason name** |
| --- | --- |
| 
Product shipped. Tracking updates will be available soon.

 | 

seller\_reject\_apply\_product\_has\_been\_packed

 |
| 

You have reached an agreement with the customer

 | 

reverse\_reject\_request\_reason\_5

 |
| 

Unable to change address

 | 

seller\_reject\_apply\_unable\_to\_change\_address

 |

## Seller Initiates Cancel Reason

The available reason of seller initiates cancel

| **Order Status** | **Seller available initiate cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Out of stock

 | 

seller\_cancel\_unpaid\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_unpaid\_reason\_wrong\_price

 |
| 

 | 

Customer did not pay on time

 | 

seller\_cancel\_unpaid\_reason\_buyer\_hasnt\_paid\_within\_time\_allowed

 |
| 

 | 

Customer requested cancellation

 | 

seller\_cancel\_unpaid\_reason\_buyer\_requested\_cancellation

 |
| 

ON\_HOLD  
AWAITING\_SHIPMENT

 | 

Out of stock

 | 

seller\_cancel\_reason\_out\_of\_stock

 |
| 

 | 

Customer requested cancellation

 | 

seller\_cancel\_paid\_reason\_buyer\_requested\_cancellation

 |
| 

 | 

Unable to deliver to buyer address

 | 

seller\_cancel\_paid\_reason\_address\_not\_deliver

 |

## SYSTEM Cancel Reason

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
Buyer payment overdue

 | 

system\_cancel\_unpaid\_time\_out

 |
| 

Failed to pass risk review

 | 

system\_cancel\_anti\_span\_block

 |
| 

Order cancelled by system

 | 

package\_cancel

 |
| 

Order cancelled by system

 | 

platform\_cancel

 |
| 

Package rejected

 | 

returned\_to\_shipper\_refused

 |
| 

Package delivery failed

 | 

returned\_to\_shipper\_other

 |
| 

Package damaged

 | 

package\_damaged

 |
| 

Package lost

 | 

package\_lost

 |
| 

Late delivery

 | 

threepl\_breach

 |
| 

Package scrapped

 | 

package\_scrap

 |
| 

Automatically cancelled due to collection time out

 | 

system\_cancel\_order\_reason\_shipping\_timeout

 |
| 

Inbound failed

 | 

system\_cancel\_cross\_border\_entry\_warehouse\_failed\_reason2

 |
| 

This order has been automatically canceled as you didn't provide your delivery address.

 | 

system\_cancel\_add\_address\_overtime

 |
| 

Order canceled by the system

 | 

ecom\_cb\_sc\_out\_return

 |
| 

Automatically cancelled due to untraceable tracking number

 | 

system\_cancel\_order\_untracked

 |
| 

Buyer hasn't paid

 | 

ecom\_order\_unpaid\_canceled\_reason\_not\_want\_pay

 |
| 

Automatically canceled as not delivered within 30 days of estimated time of delivery

 | 

system\_cancel\_order\_exceed\_edt\_not\_arrive

 |
| 

Customer hasn't paid

 | 

ecom\_order\_unpaid\_canceled\_reason\_not\_want\_pay

 |
| 

This order was canceled automatically as the customer's payment method could not be charged.

 | 

system\_cancel\_payment\_capture\_failed

 |
| 

This order was canceled automatically due to payment risk control interception.

 | 

system\_cancel\_payment\_risk\_review\_failed

 |
| 

This order was canceled automatically due to inventory shortage.

 | 

system\_cancel\_create\_valid\_order\_failed

 |

## Operator Cancel

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
We have identified a risk involved with your order and cancelled your order for safety reasons to protect your transaction.

 | 

system\_cancel\_order\_reason\_potential\_fraud

 |
| 

During our regular review, the order you placed was found to have potential issues that may result in an experience that doesn't meet our standards. To ensure you have a great shopping experience, we have proactively issued a full refund.

 | 

system\_cancel\_order\_reason\_experience\_related

 |
| 

Failed to pass risk review

 | 

operator\_cancel\_risk\_control\_block

 |
| 

As your product violates the platform's negative review policy, we canceled this transaction. We recommend you process the buyer's after-sales request.

 | 

system\_cancel\_order\_reason\_nrr\_related

 |

* * *

# EU market(s)

Currently, supported EU markets include:

-   Spain (ES)
    
-   Ireland (IE)
    
-   France (FR)
    
-   Germany (DE)
    
-   Italy (IT)
    

## Buyer Initiates Cancel Reason

The following reason is available for buyers when they initiate cancel request.

| **Order Stauts** | **Buyer available cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID  

 | 

High delivery costs

 | 

ecom\_order\_to\_ship\_canceled\_reason\_high\_delivery\_costs

 |
| 

 | 

Discount not as expected

 | 

ecom\_order\_to\_ship\_canceled\_reason\_discount\_not\_expected

 |
| 

 | 

Seller is not responsive to my inquiries / seller requests to cancel

 | 

ecom\_order\_unpaid\_canceled\_reason\_other

 |
| 

 | 

No longer needed

 | 

ecom\_order\_unpaid\_canceled\_reason\_no\_longer\_needed

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT  

 | 

No reason

 | 

ecom\_order\_delivered\_refund\_reason\_none

 |
| 

 | 

Late shipment

 | 

buyer\_cancel\_late\_shipment

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_to\_ship\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Better price available

 | 

ecom\_order\_to\_ship\_canceled\_reason\_better\_price

 |
| 

 | 

High delivery costs

 | 

ecom\_order\_to\_ship\_canceled\_reason\_high\_delivery\_costs

 |
| 

 | 

Discount not as expected

 | 

ecom\_order\_to\_ship\_canceled\_reason\_discount\_not\_expected

 |
| 

 | 

Order created by mistake

 | 

ecom\_order\_to\_ship\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

Seller is not responsive to my inquiries / seller requests to cancel

 | 

ecom\_order\_unpaid\_canceled\_reason\_other

 |
| 

 | 

Need to change payment method

 | 

ecom\_order\_to\_ship\_canceled\_reason\_change\_payment\_method

 |
| 

 | 

No longer needed

 | 

ecom\_order\_unpaid\_canceled\_reason\_no\_longer\_needed

 |

## Seller Reject Cancel Request Reason

The reason the seller can use to reject the buyer's cancel request

| **Seller available Reject Cancel reason** | **Reason name** |
| --- | --- |
| 
The seller didn't accept your cancellation request.

 | 

ecom\_refund\_reject\_reason\_cancel\_unacceptable\_uk

 |
| 

Your product has been shipped. Please wait for tracking updates.

 | 

ecom\_reverse\_reject\_cancel\_reason\_product\_packed

 |

## Seller Initiates Cancel Reason

The available reason of seller initiates cancel

| **Order Stauts** | **Seller available initiate cancel reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Out of stock

 | 

seller\_cancel\_unpaid\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_unpaid\_reason\_wrong\_price

 |
| 

 | 

Customer did not pay on time

 | 

seller\_cancel\_unpaid\_reason\_buyer\_hasnt\_paid\_within\_time\_allowed

 |
| 

 | 

Customer requested cancellation

 | 

seller\_cancel\_unpaid\_reason\_buyer\_requested\_cancellation

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT  

 | 

Out of stock

 | 

seller\_cancel\_reason\_out\_of\_stock

 |
| 

 | 

Pricing error

 | 

seller\_cancel\_reason\_wrong\_price

 |
| 

 | 

Unable to deliver to customer address

 | 

seller\_cancel\_paid\_reason\_address\_not\_deliver

 |
| 

 | 

Customer requested cancellation

 | 

seller\_cancel\_paid\_reason\_buyer\_requested\_cancellation

 |

## SYSTEM Cancel Reason

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
Customer overdue to pay

 | 

system\_cancel\_unpaid\_time\_out

 |
| 

Failed to pass risk review

 | 

system\_cancel\_anti\_span\_block

 |
| 

Inbound failed

 | 

system\_cancel\_cross\_border\_entry\_warehouse\_failed\_reason2

 |
| 

Order cancelled by system

 | 

package\_cancel

 |
| 

Order cancelled by system

 | 

platform\_cancel

 |
| 

Package rejected

 | 

returned\_to\_shipper\_refused

 |
| 

Package delivery failed

 | 

returned\_to\_shipper\_other

 |
| 

Package damaged

 | 

package\_damagaed

 |
| 

Package lost

 | 

package\_lost

 |
| 

Late delivery

 | 

threepl\_breach

 |
| 

Package scrapped

 | 

package\_scrap

 |
| 

Automatically canceled due to collection time out

 | 

system\_cancel\_order\_reason\_shipping\_timeout

 |
| 

This order has been automatically canceled as you didn't provide your delivery address.

 | 

system\_cancel\_add\_address\_overtime

 |
| 

Automatically canceled due to untraceable tracking number

 | 

system\_cancel\_order\_untracked

 |
| 

Order canceled by the system

 | 

ecom\_cb\_sc\_out\_return

 |
| 

Customer hasn't paid

 | 

ecom\_order\_unpaid\_canceled\_reason\_not\_want\_pay

 |
| 

Automatically canceled as not delivered within 30 days of estimated time of delivery

 | 

system\_cancel\_order\_exceed\_edt\_not\_arrive

 |

## Operator Cancel

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
Failed to pass risk review

 | 

operator\_cancel\_risk\_control\_block

 |
| 

We have identified a risk involved with your order and canceled your order for safety reasons to protect your transaction.

 | 

system\_cancel\_order\_reason\_potential\_fraud

 |
| 

During our regular review, the order you placed was found to have potential issues that may result in an experience that doesn't meet our standards. To ensure you have a great shopping experience, we have proactively issued a full refund.

 | 

system\_cancel\_order\_reason\_experience\_related

 |
| 

As your product violates the platform's negative review policy, we canceled this transaction. We recommend you process the customer's after-sales request.

 | 

system\_cancel\_order\_reason\_nrr\_related

 |

* * *

# JP market

## Buyer Initiates Cancel Reason

| **Order status** | **Buyer available Return reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT  
DELIVERED  

 | 

Order created by mistake.

 | 

ecom\_order\_unpaid\_canceled\_reason\_created\_by\_mistakes

 |
| 

 | 

Need to change payment method.

 | 

ecom\_order\_unpaid\_canceled\_reason\_payment\_method\_not\_available\_S

 |
| 

 | 

Need to use coupon.

 | 

buyer\_cancel\_need\_to\_input/change\_coupon\_code

 |
| 

 | 

Need to change color or size.

 | 

buyer\_cancel\_wrong\_item\_variation\_(colour,\_size,\_etc.)

 |
| 

 | 

Need to change shipping address.

 | 

ecom\_order\_unpaid\_canceled\_reason\_wrong\_delivery\_info

 |
| 

 | 

Found better price.

 | 

ecom\_order\_unpaid\_canceled\_reason\_better\_price

 |
| 

 | 

No longer need item.

 | 

ecom\_order\_unpaid\_canceled\_reason\_no\_longer\_needed

 |
| 

 | 

Seller not responsive

 | 

ecom\_order\_to\_ship\_canceled\_reason\_seller\_no\_response

 |
| 

 | 

Seller requested to cancel order

 | 

ecom\_order\_unpaid\_canceled\_reason\_seller\_ask\_cancel

 |

## Seller Initiates Cancel Reason

| **Order Stauts** | **Seller available initiate Return reason** | **Reason name** |
| --- | --- | --- |
| 
UNPAID

 | 

Pricing error

 | 

seller\_cancel\_unpaid\_reason\_wrong\_price

 |
| 

 | 

Out of stock

 | 

seller\_cancel\_unpaid\_reason\_out\_of\_stock

 |
| 

 | 

Customer requested cancellation

 | 

seller\_cancel\_unpaid\_reason\_buyer\_requested\_cancellation

 |
| 

 | 

Customer did not pay on time

 | 

seller\_cancel\_unpaid\_reason\_buyer\_hasnt\_paid\_within\_time\_allowed

 |
| 

ON\_HOLD and AWAITING\_SHIPMENT  

 | 

Pricing error

 | 

seller\_cancel\_reason\_wrong\_price

 |
| 

 | 

Out of stock

 | 

seller\_cancel\_reason\_out\_of\_stock

 |
| 

 | 

Customer requested cancellation

 | 

seller\_cancel\_paid\_reason\_buyer\_requested\_cancellation

 |
| 

 | 

Unable to deliver to customer address

 | 

seller\_cancel\_paid\_reason\_address\_not\_deliver

 |
| 

 | 

Order with high chance of cancellation / refund

 | 

seller\_cancel\_order\_reason\_potential\_fraud

 |

## SYSTEM Cancel Reason

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
Customer overdue to pay

 | 

system\_cancel\_unpaid\_time\_out

 |
| 

Failed to pass risk review

 | 

system\_cancel\_anti\_span\_block

 |
| 

Inbound failed

 | 

system\_cancel\_cross\_border\_entry\_warehouse\_failed\_reason2

 |
| 

Order cancelled by system

 | 

package\_cancel

 |
| 

Order cancelled by system

 | 

platform\_cancel

 |
| 

Package rejected

 | 

returned\_to\_shipper\_refused

 |
| 

Package delivery failed

 | 

returned\_to\_shipper\_other

 |
| 

Package damaged

 | 

package\_damagaed

 |
| 

Package lost

 | 

package\_lost

 |
| 

Late delivery

 | 

threepl\_breach

 |
| 

Package scrapped

 | 

package\_scrap

 |
| 

Automatically canceled due to collection time out

 | 

system\_cancel\_order\_reason\_shipping\_timeout

 |
| 

This order has been automatically canceled as you didn't provide your delivery address.

 | 

system\_cancel\_add\_address\_overtime

 |
| 

Automatically canceled due to untraceable tracking number

 | 

system\_cancel\_order\_untracked

 |
| 

Order canceled by the system

 | 

ecom\_cb\_sc\_out\_return

 |
| 

This order was automatically canceled because it wasn't delivered within the estimated delivery time.

 | 

system\_cancel\_order\_exceed\_edt\_not\_arrive

 |
| 

Customer hasn't paid

 | 

ecom\_order\_unpaid\_canceled\_reason\_not\_want\_pay

 |
| 

This order was canceled automatically as the customer's payment method could not be charged.

 | 

system\_cancel\_payment\_capture\_failed

 |
| 

This order was canceled automatically due to payment risk control interception.

 | 

system\_cancel\_payment\_risk\_review\_failed

 |
| 

This order was canceled automatically due to inventory shortage.

 | 

system\_cancel\_create\_valid\_order\_failed

 |
| 

Certificate not uploaded

 | 

system\_cancel\_order\_reason\_coa\_upload\_more\_than\_24hrs

 |
| 

Unable to verify certificate

 | 

system\_cancel\_order\_reason\_coa\_2nd\_upload\_rejected

 |
| 

Unable to authenticate

 | 

system\_cancel\_order\_reason\_coa\_rejected\_fraud

 |
| 

Verification timed out

 | 

system\_cancel\_order\_reason\_coa\_moderation\_more\_than\_12hrs

 |
| 

The packaging or shipping failed to meet standards due to incorrect labeling, postage issues, third-party fulfillment problems, or weight discrepancies.

 | 

system\_cancel\_unpaid\_postage

 |
| 

Package delivery failed

 | 

early\_refund\_returned\_to\_shipper

 |

## Operator Cancel

| **Cancel reason** | **Reason name** |
| --- | --- |
| 
We have identified a risk involved with your order and canceled your order for safety reasons to protect your transaction.

 | 

system\_cancel\_order\_reason\_potential\_fraud

 |
| 

During our regular review, the order you placed was found to have potential issues that may result in an experience that doesn't meet our standards. To ensure you have a great shopping experience, we have proactively issued a full refund.

 | 

system\_cancel\_order\_reason\_experience\_related

 |
| 

Failed to pass risk review

 | 

operator\_cancel\_risk\_control\_block

 |
| 

As your product violates the platform's negative review policy, we canceled this transaction. We recommend you process the customer's after-sales request.

 | 

system\_cancel\_order\_reason\_nrr\_related

 |
