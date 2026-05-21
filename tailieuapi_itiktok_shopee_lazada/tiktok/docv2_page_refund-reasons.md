# Refund reasons

> Source: https://partner.tiktokshop.com/docv2/page/refund-reasons
> Section: API Reference
> Scraped: 2026-05-20T23:45:52.999Z

---

📌 Different order statuses require different reasons. Check the status on the left in the table to find the applicable reasons, and include the reason name as a request parameter when making the API call. If the cancel reason is not applicable for the order status, the API will return a '25001021 Reason not match order status' error.

# US market

## Buyer Initiates Refund Reason

| **Order status** | **Buyer available refund reason** | **Reason name** |
| --- | --- | --- |
| 
`AWAITING_COLLECTION`  
`IN_TRANSIT`

 | 

Product wouldn't arrive on time

 | 

ecom\_order\_shipped\_refund\_reason\_not\_arrive\_on\_time

 |
| 

`DELIVERED`

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received

 |
| 

 | 

Package received but missing item

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product

 |
| 

 | 

Damaged item or packaging

 | 

ecom\_order\_delivered\_refund\_reason\_damaged

 |
| 

 | 

Defective item

 | 

ecom\_order\_delivered\_refund\_reason\_defective

 |
| 

 | 

Missing or broken parts

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_broken\_parts

 |
| 

 | 

Item doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match\_description

 |
| 

 | 

Wrong item was sent

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product\_1

 |

## Seller Reject Refund Request Reason

> ### **1st Review**

| **Seller available reject refund reason** | **Reason name** |
| --- | --- |
| 
Product delivery is on schedule

 | 

reverse\_reject\_request\_reason\_4

 |
| 

Item is correct

 | 

reverse\_reject\_refund\_request\_correct\_item

 |
| 

Products were shipped in multiple packages

 | 

reverse\_reject\_refund\_request\_package\_delivery\_multiple\_boxes

 |
| 

Reason is unclear / lack of evidence

 | 

reverse\_reject\_refund\_request\_unclear\_evidence

 |
| 

Product functions well/Incorrect usage by the customer

 | 

reverse\_reject\_refund\_request\_product\_function\_well

 |
| 

Product is in transit

 | 

reverse\_reject\_refund\_request\_package\_delivery\_timing

 |
| 

Replacement has been shipped

 | 

reverse\_reject\_refund\_request\_no\_reason\_shipped\_replacement

 |
| 

The delivery attempt failed

 | 

reverse\_reject\_refund\_request\_no\_reason\_failed\_delivery\_attempt

 |
| 

The package has been successfully delivered to the shipping address that was provided.

 | 

reverse\_reject\_refund\_request\_incorrect\_address

 |
| 

Not eligible for return (e.g. used or broken)

 | 

reverse\_reject\_request\_reason\_3

 |

## Seller Initiates Refund Reason

| **Order Status** | **Seller available initiate refund reason** | **Reason name** |
| --- | --- | --- |
| 
`AWAITING_COLLECTION`  
`IN_TRANSIT`

 | 

Package lost

 | 

seller\_shipped\_refund\_package\_lost

 |
| 

 | 

Package delivery failed

 | 

seller\_shipped\_refund\_package\_delivery\_failed

 |
| 

 | 

Missed estimated delivery date

 | 

seller\_shipped\_refund\_miss\_estimated\_delivery\_date

 |
| 

`DELIVERED`

 | 

Missing product or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product\_seller

 |
| 

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received\_seller

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match\_description\_seller

 |
| 

 | 

Package or product is damaged

 | 

ecom\_order\_delivered\_refund\_reason\_damaged\_seller

 |
| 

 | 

Wrong product was sent

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product\_seller

 |
| 

 | 

Missed estimated delivery date

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date\_seller

 |
| 

 | 

Package lost

 | 

seller\_shipped\_refund\_package\_lost

 |
| 

 | 

Package delivery failed

 | 

seller\_shipped\_refund\_package\_delivery\_failed

 |

* * *

# UK market

## Buyer Initiates Refund Reason

| **Order status** | **Buyer available refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT

 | 

Package didn't arrive on time

 | 

buyer\_refund\_package\_didn't\_arrive\_on\_time

 |
| 

DELIVERED

 | 

No longer needed

 | 

ecom\_order\_delivered\_refund\_reason\_no\_need\_uk

 |
| 

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received\_uk

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match\_uk

 |
| 

 | 

Missing product or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product\_uk

 |
| 

 | 

Package or product is damaged

 | 

ecom\_order\_delivered\_refund\_reason\_damaged\_uk

 |
| 

 | 

Product is defective or doesn't work

 | 

ecom\_order\_delivered\_refund\_reason\_defective\_uk

 |
| 

 | 

Wrong product was sent

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product\_uk

 |
| 

 | 

Missed estimated delivery date

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date\_uk

 |
| 

 | 

Other

 | 

ecom\_order\_delivered\_refund\_reason\_other\_uk

 |
| 

 | 

Suspected Counterfeit

 | 

buyer\_refund\_suspected\_counterfeit

 |
| 

 | 

Does not suit me

 | 

buyer\_refund\_does\_not\_suit\_me

 |
| 

 | 

Multiple sizes ordered

 | 

buyer\_refund\_multiple\_sizes\_ordered

 |
| 

 | 

Quality or style not as expected

 | 

buyer\_refund\_quality\_or\_style\_not\_as\_expected

 |
| 

 | 

Item is too big/long

 | 

buyer\_refund\_item\_is\_too\_big/long

 |
| 

 | 

Item is too small/short

 | 

buyer\_refund\_item\_is\_too\_small/short

 |
| 

 | 

Ordered incorrect size

 | 

buyer\_refund\_ordered\_incorrect\_size

 |
| 

 | 

Unauthorised purchase

 | 

buyer\_refund\_unauthorised\_purchase

 |
| 

 | 

Product is expired/spoiled

 | 

buyer\_refund\_product\_is\_expired/spoiled

 |
| 

 | 

Product not frozen on arrival

 | 

buyer\_refund\_product\_not\_frozen\_on\_arrival

 |

## Seller Reject Refund Request Reason

| **Seller available reject refund reason** | **Reason name** |
| --- | --- |
| 
Buyer return reason is not valid

 | 

reverse\_reject\_request\_reason\_1\_uk

 |
| 

Change of mind returns are not applicable

 | 

reverse\_reject\_request\_reason\_2\_uk

 |
| 

Not eligible for return (e.g. used or broken)

 | 

reverse\_reject\_request\_reason\_3\_uk

 |
| 

Product delivery is on schedule

 | 

reverse\_reject\_request\_reason\_4\_uk

 |
| 

You have reached an agreement with the buyer

 | 

reverse\_reject\_request\_reason\_5\_uk

 |
| 

Unable to change address

 | 

seller\_reject\_apply\_unable\_to\_change\_address

 |
| 

Buyer's responsibility for incorrect address

 | 

seller\_reject\_apply\_buyer's\_responsibility\_for\_incorrect\_address

 |
| 

Item is correct

 | 

seller\_reject\_apply\_item\_is\_correct

 |
| 

Products will be sent separately

 | 

seller\_reject\_apply\_products\_will\_be\_sent\_separately

 |
| 

Reason is unclear or lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_or\_lack\_of\_evidence

 |
| 

Product functions well/Buyer use in wrong way

 | 

seller\_reject\_apply\_product\_functions\_well/buyer\_use\_in\_wrong\_way

 |
| 

Reason is unclear / lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_/\_lack\_of\_evidence

 |
| 

Product is shipped and parcel cannot be recalled back from logistics.

 | 

seller\_reject\_apply\_package\_has\_not\_exceeded\_estimated\_delivery\_time

 |

## Seller Initiates Refund Reason

| **Order Status** | **Seller available initiate refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT

 | 

Package lost

 | 

seller\_package\_lost\_uk

 |
| 

 | 

Product wouldn't arrive on time

 | 

ecom\_order\_shipped\_refund\_reason\_not\_arrive\_on\_time\_seller\_uk

 |
| 

DELIVERED

 | 

Missing product or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product\_seller\_uk

 |
| 

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received\_seller\_uk

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match\_description\_seller\_uk

 |
| 

 | 

Package or product is damaged

 | 

ecom\_order\_delivered\_refund\_reason\_damaged\_seller\_uk

 |
| 

 | 

Wrong product was sent

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product\_seller\_uk

 |
| 

 | 

Missed estimated delivery date

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date\_seller\_uk

 |
| 

 | 

Product is defective or doesn't work

 | 

ecom\_order\_delivered\_refund\_reason\_defective\_seller\_uk

 |
| 

 | 

Suspected Counterfeit

 | 

buyer\_refund\_suspected\_counterfeit\_seller\_uk

 |

* * *

# ID market

## Buyer Initiates Refund Reason

| **Order status** | **Buyer available refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT

 | 

Product wouldn't arrive on time

 | 

ecom\_order\_shipped\_refund\_reason\_not\_arrive\_on\_time

 |
| 

DELIVERED

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match

 |
| 

 | 

Missing product or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product

 |
| 

 | 

Package or product is damaged

 | 

ecom\_order\_delivered\_refund\_reason\_damaged

 |
| 

 | 

Product is defective or doesn't work

 | 

ecom\_order\_delivered\_refund\_reason\_defective

 |
| 

 | 

Wrong product was sent

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product

 |
| 

 | 

Missed estimated delivery date

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date

 |
| 

 | 

Other

 | 

ecom\_order\_delivered\_refund\_reason\_other

 |
| 

 | 

Suspected Counterfeit

 | 

buyer\_refund\_suspected\_counterfeit

 |

## Seller Reject Refund Request Reason

| **Seller available reject refund reason** | **Reason name** |
| --- | --- |
| 
Buyer return reason is not valid

 | 

reverse\_reject\_request\_reason\_1

 |
| 

Change of mind returns are not applicable

 | 

reverse\_reject\_request\_reason\_2

 |
| 

Not eligible for return (e.g. used or broken)

 | 

reverse\_reject\_request\_reason\_3

 |
| 

Product delivery is on schedule

 | 

reverse\_reject\_request\_reason\_4

 |
| 

You have reached an agreement with the buyer

 | 

reverse\_reject\_request\_reason\_5

 |
| 

Unable to change address

 | 

seller\_reject\_apply\_unable\_to\_change\_address

 |
| 

Need to apply for refund&return

 | 

seller\_reject\_apply\_need\_to\_apply\_for\_refund&return

 |
| 

Buyer's responsibility for incorrect address

 | 

seller\_reject\_apply\_buyer's\_responsibility\_for\_incorrect\_address

 |
| 

Item is correct

 | 

seller\_reject\_apply\_item\_is\_correct

 |
| 

Products will be sent separately

 | 

seller\_reject\_apply\_products\_will\_be\_sent\_separately

 |
| 

Reason is unclear or lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_or\_lack\_of\_evidence

 |
| 

Product functions well/Buyer use in wrong way

 | 

seller\_reject\_apply\_product\_functions\_well/buyer\_use\_in\_wrong\_way

 |
| 

Reason is unclear / lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_/\_lack\_of\_evidence

 |
| 

Product is shipped and parcel cannot be recalled back from logistics.

 | 

seller\_reject\_apply\_package\_has\_not\_exceeded\_estimated\_delivery\_time

 |

## Seller Initiates Refund Reason

| **Order Status** | **Seller available initiate refund reason** | **Reason name** |
| --- | --- | --- |
| 
DELIVERED

 | 

Missing product or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product\_seller

 |

* * *

# MY market

## Buyer Initiates Refund Reason

| **Order status** | **Buyer available refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT

 | 

Product wouldn't arrive on time

 | 

ecom\_order\_shipped\_refund\_reason\_not\_arrive\_on\_time

 |
| 

DELIVERED

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match

 |
| 

 | 

Missing product or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product

 |
| 

 | 

Package or product is damaged

 | 

ecom\_order\_delivered\_refund\_reason\_damaged

 |
| 

 | 

Product is defective or doesn't work

 | 

ecom\_order\_delivered\_refund\_reason\_defective

 |
| 

 | 

Wrong product was sent

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product

 |
| 

 | 

Missed estimated delivery date

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date

 |
| 

 | 

Suspected Counterfeit

 | 

buyer\_refund\_suspected\_counterfeit

 |

## Seller Reject Refund Request Reason

| **Seller available reject refund reason** | **Reason name** |
| --- | --- |
| 
Buyer return reason is not valid

 | 

reverse\_reject\_request\_reason\_1

 |
| 

Change of mind returns are not applicable

 | 

reverse\_reject\_request\_reason\_2

 |
| 

Not eligible for return (e.g. used or broken)

 | 

reverse\_reject\_request\_reason\_3

 |
| 

Product delivery is on schedule

 | 

reverse\_reject\_request\_reason\_4

 |
| 

You have reached an agreement with the buyer

 | 

reverse\_reject\_request\_reason\_5

 |
| 

Unable to change address

 | 

seller\_reject\_apply\_unable\_to\_change\_address

 |
| 

Need to apply for refund&return

 | 

seller\_reject\_apply\_need\_to\_apply\_for\_refund&return

 |
| 

Buyer's responsibility for incorrect address

 | 

seller\_reject\_apply\_buyer's\_responsibility\_for\_incorrect\_address

 |
| 

Item is correct

 | 

seller\_reject\_apply\_item\_is\_correct

 |
| 

Products will be sent separately

 | 

seller\_reject\_apply\_products\_will\_be\_sent\_separately

 |
| 

Reason is unclear or lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_or\_lack\_of\_evidence

 |
| 

Product functions well/Buyer use in wrong way

 | 

seller\_reject\_apply\_product\_functions\_well/buyer\_use\_in\_wrong\_way

 |
| 

Reason is unclear / lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_/\_lack\_of\_evidence

 |
| 

Product is shipped and parcel cannot be recalled back from logistics.

 | 

seller\_reject\_apply\_package\_has\_not\_exceeded\_estimated\_delivery\_time

 |

## Seller Initiates Refund Reason

Unsupported

* * *

# PH market

## Buyer Initiates Refund Reason

| **Order status** | **Buyer available refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT

 | 

Product wouldn't arrive on time

 | 

ecom\_order\_shipped\_refund\_reason\_not\_arrive\_on\_time

 |
| 

DELIVERED

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match

 |
| 

 | 

Missing product or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product

 |
| 

 | 

Package or product is damaged

 | 

ecom\_order\_delivered\_refund\_reason\_damaged

 |
| 

 | 

Product is defective or doesn't work

 | 

ecom\_order\_delivered\_refund\_reason\_defective

 |
| 

 | 

Wrong product was sent

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product

 |
| 

 | 

Missed estimated delivery date

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date

 |
| 

 | 

Suspected Counterfeit

 | 

buyer\_refund\_suspected\_counterfeit

 |

## Seller Reject Refund Request Reason

| **Seller available reject refund reason** | **Reason name** |
| --- | --- |
| 
Buyer return reason is not valid

 | 

reverse\_reject\_request\_reason\_1

 |
| 

Change of mind returns are not applicable

 | 

reverse\_reject\_request\_reason\_2

 |
| 

Not eligible for return (e.g. used or broken)

 | 

reverse\_reject\_request\_reason\_3

 |
| 

Product delivery is on schedule

 | 

reverse\_reject\_request\_reason\_4

 |
| 

You have reached an agreement with the buyer

 | 

reverse\_reject\_request\_reason\_5

 |
| 

Unable to change address

 | 

seller\_reject\_apply\_unable\_to\_change\_address

 |
| 

Need to apply for refund&return

 | 

seller\_reject\_apply\_need\_to\_apply\_for\_refund&return

 |
| 

Buyer's responsibility for incorrect address

 | 

seller\_reject\_apply\_buyer's\_responsibility\_for\_incorrect\_address

 |
| 

Item is correct

 | 

seller\_reject\_apply\_item\_is\_correct

 |
| 

Products will be sent separately

 | 

seller\_reject\_apply\_products\_will\_be\_sent\_separately

 |
| 

Reason is unclear or lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_or\_lack\_of\_evidence

 |
| 

Product functions well/Buyer use in wrong way

 | 

seller\_reject\_apply\_product\_functions\_well/buyer\_use\_in\_wrong\_way

 |
| 

Reason is unclear / lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_/\_lack\_of\_evidence

 |
| 

Product is shipped and parcel cannot be recalled back from logistics.

 | 

seller\_reject\_apply\_package\_has\_not\_exceeded\_estimated\_delivery\_time

 |

## Seller Initiates Refund Reason

Unsupported

* * *

# SG market

## Buyer Initiates Refund Reason

| **Order status** | **Buyer available refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT

 | 

Product wouldn't arrive on time

 | 

ecom\_order\_shipped\_refund\_reason\_not\_arrive\_on\_time

 |
| 

DELIVERED

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match

 |
| 

 | 

Missing product or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product

 |
| 

 | 

Package or product is damaged

 | 

ecom\_order\_delivered\_refund\_reason\_damaged

 |
| 

 | 

Product is defective or doesn't work

 | 

ecom\_order\_delivered\_refund\_reason\_defective

 |
| 

 | 

Wrong product was sent

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product

 |
| 

 | 

Missed estimated delivery date

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date

 |
| 

 | 

Suspected Counterfeit

 | 

buyer\_refund\_suspected\_counterfeit

 |

## Seller Reject Refund Request Reason

| **Seller available reject refund reason** | **Reason name** |
| --- | --- |
| 
Buyer return reason is not valid

 | 

reverse\_reject\_request\_reason\_1

 |
| 

Change of mind returns are not applicable

 | 

reverse\_reject\_request\_reason\_2

 |
| 

Not eligible for return (e.g. used or broken)

 | 

reverse\_reject\_request\_reason\_3

 |
| 

Product delivery is on schedule

 | 

reverse\_reject\_request\_reason\_4

 |
| 

You have reached an agreement with the buyer

 | 

reverse\_reject\_request\_reason\_5

 |
| 

Unable to change address

 | 

seller\_reject\_apply\_unable\_to\_change\_address

 |
| 

Need to apply for refund&return

 | 

seller\_reject\_apply\_need\_to\_apply\_for\_refund&return

 |
| 

Buyer's responsibility for incorrect address

 | 

seller\_reject\_apply\_buyer's\_responsibility\_for\_incorrect\_address

 |
| 

Item is correct

 | 

seller\_reject\_apply\_item\_is\_correct

 |
| 

Products will be sent separately

 | 

seller\_reject\_apply\_products\_will\_be\_sent\_separately

 |
| 

Reason is unclear or lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_or\_lack\_of\_evidence

 |
| 

Product functions well/Buyer use in wrong way

 | 

seller\_reject\_apply\_product\_functions\_well/buyer\_use\_in\_wrong\_way

 |
| 

Reason is unclear / lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_/\_lack\_of\_evidence

 |
| 

Product is shipped and parcel cannot be recalled back from logistics.

 | 

seller\_reject\_apply\_package\_has\_not\_exceeded\_estimated\_delivery\_time

 |

## Seller Initiates Refund Reason

Unsupported

* * *

# TH market

## Buyer Initiates Refund Reason

| **Order status** | **Buyer available refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT

 | 

Product wouldn't arrive on time

 | 

ecom\_order\_shipped\_refund\_reason\_not\_arrive\_on\_time

 |
| 

 | 

Wrong delivery information

 | 

ecom\_order\_shipped\_refund\_wrong\_delivery\_info

 |
| 

 | 

Other

 | 

ecom\_order\_shipped\_refund\_other

 |
| 

DELIVERED

 | 

No longer needed

 | 

ecom\_order\_delivered\_refund\_reason\_no\_need

 |
| 

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match

 |
| 

 | 

Missing product or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product

 |
| 

 | 

Package or product is damaged

 | 

ecom\_order\_delivered\_refund\_reason\_damaged

 |
| 

 | 

Product is defective or doesn't work

 | 

ecom\_order\_delivered\_refund\_reason\_defective

 |
| 

 | 

Wrong product was sent

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product

 |
| 

 | 

Missed estimated delivery date

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date

 |
| 

 | 

Suspected Counterfeit

 | 

buyer\_refund\_suspected\_counterfeit

 |
| 

 | 

Other

 | 

ecom\_order\_delivered\_refund\_reason\_other

 |

## Seller Reject Refund Request Reason

| **Seller available reject refund reason** | **Reason name** |
| --- | --- |
| 
Buyer return reason is not valid

 | 

reverse\_reject\_request\_reason\_1

 |
| 

Not eligible for return (e.g. used or broken)

 | 

reverse\_reject\_request\_reason\_3

 |
| 

Product delivery is on schedule

 | 

reverse\_reject\_request\_reason\_4

 |
| 

You have reached an agreement with the buyer

 | 

reverse\_reject\_request\_reason\_5

 |
| 

Unable to change address

 | 

seller\_reject\_apply\_unable\_to\_change\_address

 |
| 

Need to apply for refund&return

 | 

seller\_reject\_apply\_need\_to\_apply\_for\_refund&return

 |
| 

Buyer's responsibility for incorrect address

 | 

seller\_reject\_apply\_buyer's\_responsibility\_for\_incorrect\_address

 |
| 

Item is correct

 | 

seller\_reject\_apply\_item\_is\_correct

 |
| 

Products will be sent separately

 | 

seller\_reject\_apply\_products\_will\_be\_sent\_separately

 |
| 

Reason is unclear or lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_or\_lack\_of\_evidence

 |
| 

Product functions well/Buyer use in wrong way

 | 

seller\_reject\_apply\_product\_functions\_well/buyer\_use\_in\_wrong\_way

 |
| 

Reason is unclear / lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_/\_lack\_of\_evidence

 |
| 

Product is shipped and parcel cannot be recalled back from logistics.

 | 

seller\_reject\_apply\_package\_has\_not\_exceeded\_estimated\_delivery\_time

 |

## Seller Initiates Refund Reason

Unsupported

* * *

# VN market

## Buyer Initiates Refund Reason

| **Order status** | **Buyer available refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT

 | 

Product wouldn't arrive on time

 | 

ecom\_order\_shipped\_refund\_reason\_not\_arrive\_on\_time

 |
| 

DELIVERED

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match

 |
| 

 | 

Missing product or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product

 |
| 

 | 

Package or product is damaged

 | 

ecom\_order\_delivered\_refund\_reason\_damaged

 |
| 

 | 

Product is defective or doesn't work

 | 

ecom\_order\_delivered\_refund\_reason\_defective

 |
| 

 | 

Wrong product was sent

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product

 |
| 

 | 

Missed estimated delivery date

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date

 |
| 

 | 

Suspected Counterfeit

 | 

buyer\_refund\_suspected\_counterfeit

 |

## Seller Reject Refund Request Reason

| **Seller available reject refund reason** | **Reason name** |
| --- | --- |
| 
Buyer return reason is not valid

 | 

reverse\_reject\_request\_reason\_1

 |
| 

Change of mind returns are not applicable

 | 

reverse\_reject\_request\_reason\_2

 |
| 

Not eligible for return (e.g. used or broken)

 | 

reverse\_reject\_request\_reason\_3

 |
| 

Product delivery is on schedule

 | 

reverse\_reject\_request\_reason\_4

 |
| 

You have reached an agreement with the buyer

 | 

reverse\_reject\_request\_reason\_5

 |
| 

Unable to change address

 | 

seller\_reject\_apply\_unable\_to\_change\_address

 |
| 

Need to apply for refund&return

 | 

seller\_reject\_apply\_need\_to\_apply\_for\_refund&return

 |
| 

Buyer's responsibility for incorrect address

 | 

seller\_reject\_apply\_buyer's\_responsibility\_for\_incorrect\_address

 |
| 

Item is correct

 | 

seller\_reject\_apply\_item\_is\_correct

 |
| 

Products will be sent separately

 | 

seller\_reject\_apply\_products\_will\_be\_sent\_separately

 |
| 

Reason is unclear or lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_or\_lack\_of\_evidence

 |
| 

Product functions well/Buyer use in wrong way

 | 

seller\_reject\_apply\_product\_functions\_well/buyer\_use\_in\_wrong\_way

 |
| 

Reason is unclear / lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_/\_lack\_of\_evidence

 |
| 

Product is shipped and parcel cannot be recalled back from logistics.

 | 

seller\_reject\_apply\_package\_has\_not\_exceeded\_estimated\_delivery\_time

 |

## Seller Initiates Refund Reason

| **Order Status** | **Seller available initiate refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT

 | 

Product wouldn't arrive on time

 | 

ecom\_order\_shipped\_refund\_reason\_not\_arrive\_on\_time\_seller

 |
| 

 | 

Package lost

 | 

seller\_package\_lost

 |
| 

DELIVERED

 | 

Missing product or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product\_seller

 |
| 

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received\_seller

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match\_description\_seller

 |
| 

 | 

Package or product is damaged

 | 

ecom\_order\_delivered\_refund\_reason\_damaged\_seller

 |
| 

 | 

Wrong product was sent

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product\_seller

 |
| 

 | 

Missed estimated delivery date

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date\_seller

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match\_seller

 |
| 

 | 

Product is defective or doesn't work

 | 

ecom\_order\_delivered\_refund\_reason\_defective\_seller

 |
| 

 | 

Suspected Counterfeit

 | 

buyer\_refund\_suspected\_counterfeit\_seller

 |

* * *

# MX market

## Buyer Initiates Refund Reason

| **Order status** | **Buyer available refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received

 |
| 

 | 

Defective item

 | 

ecom\_order\_delivered\_refund\_reason\_defective

 |
| 

 | 

Damaged item or packaging

 | 

ecom\_order\_delivered\_refund\_and\_return\_reason\_damaged

 |
| 

 | 

Product is defective or doesn't work

 | 

ecom\_order\_delivered\_refund\_reason\_defective

 |

## Seller Reject Refund Request Reason

| **Seller available reject refund reason** | **Reason name** |
| --- | --- |
| 
Reason is unclear / lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_or\_lack\_of\_evidence

 |
| 

You have reached an agreement with the customer

 | 

reverse\_reject\_request\_reason\_5

 |
| 

Reason is unclear / lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_or\_lack\_of\_evidence

 |
| 

Product is in transit

 | 

seller\_reject\_apply\_package\_has\_not\_exceeded\_estimated\_delivery\_time

 |
| 

The package has been successfully delivered to the shipping address that was provided.

 | 

seller\_reject\_apply\_buyers\_responsibility\_for\_incorrect\_address

 |
| 

Need to apply for refund&return

 | 

seller\_reject\_apply\_need\_to\_apply\_for\_refund&return

 |

## Seller Initiates Refund Reason

| **Order Status** | **Seller available initiate refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT

 | 

Package lost

 | 

seller\_package\_lost

 |
| 

 | 

Missed estimated delivery date

 | 

seller\_shipped\_refund\_miss\_estimated\_delivery\_date

 |
| 

 | 

Package lost

 | 

seller\_shipped\_refund\_package\_lost

 |
| 

 | 

Package delivery failed

 | 

seller\_shipped\_refund\_package\_delivery\_failed

 |
| 

 | 

Package lost

 | 

ecom\_seller\_order\_delivered\_refund\_reason\_return\_proxy\_app

 |
| 

DELIVERED

 | 

Missing products or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product\_seller

 |
| 

 | 

Package or product is damaged

 | 

ecom\_order\_delivered\_refund\_reason\_damaged\_seller

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match\_description\_seller

 |
| 

 | 

Wrong product was sent

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product\_seller

 |
| 

 | 

Missed estimated delivery date

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date\_seller

 |
| 

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received\_seller

 |
| 

 | 

Missed estimated delivery date

 | 

seller\_shipped\_refund\_miss\_estimated\_delivery\_date

 |
| 

 | 

Package lost

 | 

seller\_shipped\_refund\_package\_lost

 |
| 

 | 

Package delivery failed

 | 

seller\_shipped\_refund\_package\_delivery\_failed

 |
| 

 | 

Package lost

 | 

ecom\_seller\_order\_delivered\_refund\_reason\_return\_proxy\_app

 |

* * *

# BR market

## Buyer Initiates Refund Reason

| **Order status** | **Buyer available refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received

 |
| 

 | 

Defective item

 | 

ecom\_order\_delivered\_refund\_reason\_defective

 |
| 

 | 

Damaged item or packaging

 | 

ecom\_order\_delivered\_refund\_and\_return\_reason\_damaged

 |
| 

 | 

Product is defective or doesn't work

 | 

ecom\_order\_delivered\_refund\_reason\_defective

 |

## Seller Reject Refund Request Reason

| **Seller available reject refund reason** | **Reason name** |
| --- | --- |
| 
Reason is unclear / lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_or\_lack\_of\_evidence

 |
| 

You have reached an agreement with the customer

 | 

reverse\_reject\_request\_reason\_5

 |
| 

Reason is unclear / lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_or\_lack\_of\_evidence

 |
| 

Product is in transit

 | 

seller\_reject\_apply\_package\_has\_not\_exceeded\_estimated\_delivery\_time

 |
| 

The package has been successfully delivered to the shipping address that was provided.

 | 

seller\_reject\_apply\_buyers\_responsibility\_for\_incorrect\_address

 |
| 

Need to apply for refund&return

 | 

seller\_reject\_apply\_need\_to\_apply\_for\_refund&return

 |

## Seller Initiates Refund Reason

| **Order Status** | **Seller available initiate refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT

 | 

Package lost

 | 

seller\_package\_lost

 |
| 

 | 

Missed estimated delivery date

 | 

seller\_shipped\_refund\_miss\_estimated\_delivery\_date

 |
| 

 | 

Package lost

 | 

seller\_shipped\_refund\_package\_lost

 |
| 

 | 

Package delivery failed

 | 

seller\_shipped\_refund\_package\_delivery\_failed

 |
| 

 | 

Package lost

 | 

ecom\_seller\_order\_delivered\_refund\_reason\_return\_proxy\_app

 |
| 

DELIVERED

 | 

Missing products or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product\_seller

 |
| 

 | 

Package or product is damaged

 | 

ecom\_order\_delivered\_refund\_reason\_damaged\_seller

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match\_description\_seller

 |
| 

 | 

Wrong product was sent

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product\_seller

 |
| 

 | 

Missed estimated delivery date

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date\_seller

 |
| 

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received\_seller

 |
| 

 | 

Missed estimated delivery date

 | 

seller\_shipped\_refund\_miss\_estimated\_delivery\_date

 |
| 

 | 

Package lost

 | 

seller\_shipped\_refund\_package\_lost

 |
| 

 | 

Package delivery failed

 | 

seller\_shipped\_refund\_package\_delivery\_failed

 |
| 

 | 

Package lost

 | 

ecom\_seller\_order\_delivered\_refund\_reason\_return\_proxy\_app

 |

* * *

# EU market(s)

Currently, supported EU markets include:

-   Spain (ES)
-   Ireland (IE)
-   France (FR)
-   Germany (DE)
-   Italy (IT)

## Buyer Initiates Refund Reason

| **Order status** | **Buyer available refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT  
DELIVERED

 | 

No longer needed

 | 

ecom\_order\_delivered\_refund\_reason\_no\_need

 |
| 

 | 

Missed estimated delivery date

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date

 |
| 

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received

 |
| 

 | 

Other

 | 

ecom\_order\_delivered\_refund\_and\_return\_reason\_other

 |
| 

 | 

Wrong product was sent

 | 

ecom\_order\_delivered\_refund\_and\_return\_reason\_wrong\_product

 |
| 

 | 

Product is defective or doesn't work

 | 

ecom\_order\_delivered\_refund\_and\_return\_reason\_defective

 |
| 

 | 

Package or product is damaged

 | 

ecom\_order\_delivered\_refund\_and\_return\_reason\_damaged

 |
| 

 | 

Missing product or accessories

 | 

ecom\_order\_delivered\_refund\_and\_return\_reason\_missing\_product

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_and\_return\_reason\_not\_match\_description

 |
| 

 | 

Does not suit me

 | 

buyer\_return\_and\_refund\_does\_not\_suit\_me

 |
| 

 | 

Item is too big/long

 | 

buyer\_return\_and\_refund\_item\_is\_too\_big/long

 |
| 

 | 

Item is too small/short

 | 

buyer\_return\_and\_refund\_item\_is\_too\_small/short

 |
| 

 | 

Multiple sizes ordered

 | 

buyer\_return\_and\_refund\_multiple\_sizes\_ordered

 |
| 

 | 

Quality or style not as expected

 | 

buyer\_return\_and\_refund\_quality\_or\_style\_not\_as\_expected

 |
| 

 | 

Unauthorized purchase

 | 

buyer\_return\_and\_refund\_unauthorised\_purchase

 |
| 

 | 

Product is expired/spoiled

 | 

buyer\_return\_and\_refund\_product\_is\_expired/spoiled

 |
| 

 | 

Product not frozen on arrival

 | 

buyer\_return\_and\_refund\_product\_not\_frozen\_on\_arrival

 |
| 

 | 

Ordered incorrect size

 | 

buyer\_return\_and\_refund\_ordered\_incorrect\_size

 |

## Seller Reject Refund Request Reason

| **Seller available reject refund reason** | **Reason name** |
| --- | --- |
| 
Product delivery is on schedule

 | 

reverse\_reject\_request\_reason\_4\_uk

 |
| 

Change of mind returns are not applicable

 | 

reverse\_reject\_request\_reason\_2\_uk

 |
| 

Product delivery is on schedule

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_delivered\_uk

 |
| 

The package has been successfully delivered to the shipping address that was provided.

 | 

seller\_reject\_apply\_buyer's\_responsibility\_for\_incorrect\_address

 |
| 

Product functions well/Incorrect usage by the customer

 | 

seller\_reject\_apply\_product\_functions\_well/buyer\_use\_in\_wrong\_way

 |
| 

Parcel Delivered but Buyer did not receive, need more information

 | 

seller\_reject\_apply\_reason\_is\_unclear\_/\_lack\_of\_evidence

 |
| 

Reason is unclear / lack of evidence

 | 

seller\_reject\_apply\_reason\_is\_unclear\_or\_lack\_of\_evidence

 |
| 

Reached an agreement with the customer

 | 

order\_manage\_list\_action\_respond\_popup\_reject\_reason\_buyer\_agree

 |
| 

You have reached an agreement with the customer

 | 

reverse\_reject\_request\_reason\_5

 |
| 

Product is in transit

 | 

seller\_reject\_apply\_package\_has\_not\_exceeded\_estimated\_delivery\_time

 |
| 

Your products were shipped by multiple packages.

 | 

seller\_reject\_apply\_products\_will\_be\_sent\_separately

 |
| 

Product shipped. Tracking updates will be available soon.

 | 

seller\_reject\_apply\_product\_has\_been\_packed

 |

## Seller Initiates Refund Reason

| **Order Stauts** | **Seller available initiate refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT

 | 

Package lost

 | 

seller\_package\_lost

 |
| 

 | 

Product wouldn't arrive on time

 | 

ecom\_order\_shipped\_refund\_reason\_not\_arrive\_on\_time\_seller

 |
| 

DELIVERED  

 | 

Missing products or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product\_seller

 |
| 

 | 

Package wasn't received

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received\_seller

 |
| 

 | 

Product doesn't match description

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match\_description\_seller

 |
| 

 | 

Package or product is damaged

 | 

ecom\_order\_delivered\_refund\_reason\_damaged\_seller

 |
| 

 | 

Wrong product was sent

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product\_seller

 |
| 

 | 

Missed estimated delivery date

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date\_seller

 |
| 

 | 

Product is defective or doesn't work

 | 

ecom\_order\_delivered\_refund\_reason\_defective\_seller

 |
| 

 | 

Suspected Counterfeit

 | 

buyer\_refund\_suspected\_counterfeit\_seller

 |

* * *

# JP market

## Buyer Initiates Refund Reason

| **Order status** | **Buyer available refund reason** | **Reason name** |
| --- | --- | --- |
| 
AWAITING\_COLLECTION  
IN\_TRANSIT  
DELIVERED

 | 

No longer needed (Item must be returned in sealed/original condition).

 | 

ecom\_order\_delivered\_refund\_reason\_no\_need

 |
| 

 | 

Product didn't arrive on time.

 | 

ecom\_order\_shipped\_refund\_reason\_not\_arrive\_on\_time\_JP

 |
| 

 | 

Didn't receive package.

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received

 |
| 

 | 

Received package but some items are missing.

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product\_JP

 |
| 

 | 

Received package but missing parts or accessories.

 | 

ecom\_order\_delivered\_refund\_and\_return\_reason\_missing\_parts\_JP

 |
| 

 | 

Product doesn't match description.

 | 

ecom\_order\_delivered\_refund\_and\_return\_reason\_not\_match\_description\_JP

 |
| 

 | 

Package or product is damaged.

 | 

ecom\_order\_delivered\_refund\_reason\_damaged

 |
| 

 | 

Product is defective or doesn't work.

 | 

ecom\_order\_delivered\_refund\_reason\_defective

 |
| 

 | 

Wrong product sent.

 | 

ecom\_order\_delivered\_refund\_and\_return\_reason\_wrong\_product\_JP

 |
| 

 | 

Missed estimated delivery date.

 | 

ecom\_order\_delivered\_refund\_and\_return\_reason\_missed\_delivery\_date\_JP

 |

## Seller Reject Refund Request Reason

| **Seller available reject refund reason** | **Reason name** |
| --- | --- |
| 
Reason is unclear / lack of evidence.

 | 

reverse\_reject\_refund\_request\_unclear\_evidence\_Test

 |
| 

Product shipped. Tracking updates will be available soon.

 | 

ecom\_reverse\_reject\_cancel\_reason\_product\_packed

 |
| 

The delivery attempt failed.

 | 

ecom\_reverse\_reject\_refund\_request\_failed\_delivery

 |
| 

Products were shipped in multiple packages.

 | 

ecom\_reverse\_reject\_refund\_request\_package\_delivery\_multiple\_boxes

 |
| 

Replacement has been shipped.

 | 

ecom\_reverse\_reject\_refund\_request\_replacement\_shipped

 |
| 

Reached an agreement with the customer.

 | 

ecom\_reverse\_reject\_apply\_buyer\_agree\_jp

 |
| 

Product delivery is on schedule.

 | 

seller\_reject\_apply\_reason\_delivered\_jp

 |
| 

The package has been successfully delivered to the shipping address that was provided.

 | 

seller\_reject\_apply\_buyers\_responsibility\_for\_incorrect\_address

 |
| 

Item is correct.

 | 

seller\_reject\_apply\_item\_is\_correct

 |
| 

Product delivery is on schedule.

 | 

seller\_reject\_apply\_package\_has\_not\_exceeded\_estimated\_delivery\_time

 |
| 

Product functions well/Incorrect usage by the customer.

 | 

seller\_reject\_apply\_product\_functions\_well/buyer\_use\_in\_wrong\_way

 |
| 

Reason is unclear / lack of evidence.

 | 

seller\_reject\_apply\_reason\_is\_unclear\_or\_lack\_of\_evidence

 |
| 

Unable to change address.

 | 

seller\_reject\_apply\_unable\_to\_change\_address

 |

## Seller Initiates Refund Reason

| **Seller available initiate refund reason** | **Reason name** |
| --- | --- |
| 
Missing products or accessories

 | 

ecom\_order\_delivered\_refund\_reason\_missing\_product\_seller

 |
| 

General adjustment.

 | 

ecom\_order\_delivered\_refund\_reason\_general\_adjustment

 |
| 

Package wasn't received.

 | 

ecom\_order\_delivered\_refund\_reason\_not\_received\_seller

 |
| 

Product doesn't match description.

 | 

ecom\_order\_delivered\_refund\_reason\_not\_match\_description\_seller

 |
| 

Package or product is damaged.

 | 

ecom\_order\_delivered\_refund\_reason\_damaged\_seller

 |
| 

Wrong product was sent.

 | 

ecom\_order\_delivered\_refund\_reason\_wrong\_product\_seller

 |
| 

Missed estimated delivery date.

 | 

ecom\_order\_delivered\_refund\_reason\_missed\_delivery\_date\_seller

 |
| 

Package lost.

 | 

seller\_package\_lost

 |
| 

Package lost.

 | 

seller\_shipped\_refund\_package\_lost

 |
| 

Package delivery failed.

 | 

seller\_shipped\_refund\_package\_delivery\_failed

 |
| 

Missed estimated delivery date.

 | 

seller\_shipped\_refund\_miss\_estimated\_delivery\_date

 |
| 

Package lost.

 | 

ecom\_seller\_order\_delivered\_refund\_reason\_return\_proxy\_app

 |
