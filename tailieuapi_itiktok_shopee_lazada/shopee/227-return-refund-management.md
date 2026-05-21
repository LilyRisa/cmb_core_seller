# Return Refund Management

> Source: https://open.shopee.com/developer-guide/227
> Category: 
> Scraped: 2026-05-20T20:38:04.157Z

---

# 1.Return Status Flow

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=YmhOvjLs5FOCWWDJSCddP7ynOYe6yQV1hqPMdC2%2B462NkEaBIFFh9I69IvUDnDZSUgr5Us13F8FVMb0%2FjL%2BTOA%3D%3D&image_type=jpg)

# 2.Return API Call Flow

## 2.1 Getting the list of return orders and details

[v2.returns.get\_return\_list](https://open.shopee.com/documents/v2/v2.returns.get_return_list?module=102&type=1) API: You can get the list of returns and refund applications for a shop. Each application will return a return\_sn as a unique ID. Buyers may submit multiple return\_sn for the same order. The return parameter contains order\_sn, which is the order number associated with this return refund application. In addition, the API supports filtering different types of returns and refund applications, including return status, negotiation status, evidence upload status, and seller compensation status.

  

[v2.returns.get\_return\_detail](https://open.shopee.com/documents/v2/v2.returns.get_return_detail?module=102&type=1) API: Use return\_sn to get order return details.

[v2.returns.get\_available\_solutions](https://open.shopee.com/documents/v2/v2.returns.get_available_solutions?module=102&type=1) API: Get the available solutions offered to buyers.

## 2.2 Refund Only

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=XB4b2lXvaPdGNtUrjwUNAwZOVjMzX2VN8STWXXzcf4tJzRbPCObX79x9wXU%2FOEk3f%2FaitYMF66kCFMrleMBubQ%3D%3D&image_type=png)

[v2.returns.confirm](https://open.shopee.com/documents/v2/v2.returns.confirm?module=102&type=1) API：Agree to the buyer's return application, only for the Full Refund type, the buyer does not need to return the product. Once agreed, the status will be updated to Accepted.

## 2.3 Return & Refund (No Dispute)

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=mN3CE0oCV6vNYQ8poIcMWCYd6O0npeEkosblpXfx%2FPTZmTP2szoefNDsLSJ91lNO7GmsCMxxcoqYa%2Fi5%2BC%2BbZQ%3D%3D&image_type=png)

[v2.returns.offer](https://open.shopee.com/documents/v2/v2.returns.offer?module=102&type=1) [](https://open.shopee.com/documents/v2/v2.returns.offer?module=102&type=1): The seller provides a return plan for the buyer to choose.

[v2.returns.accept\_offer](https://open.shopee.com/documents/v2/v2.returns.accept_offer?module=102&type=1)：The seller accepts the return plan provided by the buyer.

## 2.4 Return & Refund (Dispute)

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=6JyASJfiigN%2Fn6QtT5KbHGO7BxeYXkz50WCNQo9MFJ7BLvRzsQG%2BR%2FGCxM4a5Tlk%2FujSLw0GQyJRwj1w94GCOA%3D%3D&image_type=png)

The seller can call [v2.returns.get\_available\_solution](https://open.shopee.com/documents/v2/v2.returns.get_available_solutions?module=102&type=1) API first, to determine the options or the return order, and then call [v2.returns.offer](https://open.shopee.com/documents/v2/v2.returns.offer?module=102&type=1) API for the buyer to choose.

  

Buyers will also provide solutions to sellers. If the seller accepts, they can call [v2.returns.accept\_offer](https://open.shopee.com/documents/v2/v2.returns.accept_offer?module=102&type=1) to accept the offer. If you cannot accept it, then you can call [v2.returns.dispute](https://open.shopee.com/documents/v2/v2.returns.dispute?module=102&type=1) to file a dispute with the Dispute Center.

  

At present, Open API only supports two statuses of REQUESTED and PROCESSING to dispute.

### 2.4.1 Dispute

If the seller has a dispute with the buyer’s return request, he can go to the Dispute Center to handle the request.

[v2.returns.dispute](https://open.shopee.com/documents/v2/v2.returns.dispute?module=102&type=1): Used by sellers to escalate return orders to the dispute center.

  

After a dispute is raised, the seller can upload evidence images through API, but uploading videos is not currently supported.

[v2.returns.convert\_image](https://open.shopee.com/documents/v2/v2.returns.convert_image?module=102&type=1)：Convert image.

[v2.returns.upload\_proof](https://open.shopee.com/documents/v2/v2.returns.upload_proof?module=102&type=1): Upload pictures.

[v2.returns.query\_proof](https://open.shopee.com/documents/v2/v2.returns.query_proof?module=102&type=1): Query uploaded images.

# 3.Data Definition

## ReturnStatus

-   REQUESTED
-   ACCEPTED
-   CANCELLED
-   JUDGING
-   CLOSED
-   PROCESSING
-   SELLER\_DISPUTE

## ReturnReason and Reassessed Request Reason

-   NONE
-   NOT\_RECEIPT
-   WRONG\_ITEM
-   ITEM\_DAMAGED
-   DIFFERENT\_DESCRIPTION
-   MUTUAL\_AGREE
-   OTHER
-   ITEM\_WRONGDAMAGED(only for Vietnam)
-   CHANGE\_MIND
-   ITEM\_MISSING
-   EXPECTATION\_FAILED
-   ITEM\_FAKE
-   PHYSICAL\_DMG
-   FUNCTIONAL\_DMG

## ReturnDisputeReason

Reason

NON\_RECEIPT: I would like to reject the non-receipt claim

OTHER: I would like to reject the request

NOT\_RECEIVED:I agree with the return request, but I did not receive product(s)        

UNKNOWN

## ReturnSolution

-   RETURN\_REFUND
-   REFUND

## NegotiationStatus

-   PENDING\_RESPOND
-   PENDING\_BUYER\_RESPOND
-   TERMINATED

## SellerProofStatus

-   PENDING
-   UPLOADED
-   OVERDUE

## SellerCompensationStatus

-   COMPENSATION\_NOT\_APPLICABLE
-   COMPENSATION\_INITIAL\_STAGE
-   COMPENSATION\_PENDING\_REQUEST
-   COMPENSATION\_NOT\_REQUIRED
-   COMPENSATION\_REQUESTED
-   COMPENSATION\_APPROVED
-   COMPENSATION\_REJECTED
-   COMPENSATION\_CANCELLED
-   COMPENSATION\_NOT\_ELIGIBLE

## Return Refund Request Type

-   0:  Normal RR（RR is raised by the buyer after they have received the parcel, based on estimated delivery date /delivery done ）
-   1: In-Transit RR (RR is raised by the buyer while item is still in-transit to buyer)
-   2: Return-on-the-Spot (RR is raised by the driver after buyer rejected parcel at delivery)

## Validation Type

-   seller\_validation: For Return & Refund requests with return parcel that will be delivered to the seller for validation and decision whether to refund buyer or to raise dispute
-   warehouse\_validation: For Return & Refund requests with return parcel that will be delivered to warehouse for validation and decision whether to refund buyer or to raise dispute

## Reverse Logistics Status

### \[Normal Return\]

-   LOGISTICS\_PENDING\_ARRANGE: Return is now pending user to select shipping option. Same for both integrated logistics and non-integrated logistics.
-   LOGISTICS\_READY: User has selected shipping option, and pending system to create logistics request. Tracking number is not yet available. Same for both integrated logistics and non-integrated logistics.
-   LOGISTICS\_REQUEST\_CREATED: Means that the logistics request has been created successfully. Tracking number should be available
-   LOGISTICS\_PICKUP\_RETRY: Third party logistics provider will make another attempt to pick up parcel from buyer. Only available for integrated logistics since this is updated by third party logistics provider back to Shopee.
-   LOGISTICS\_PICKUP\_FAILED: Third party logistics provider has failed to pickup parcel from buyer. Only available for integrated logistics since this is updated by third party logistics provider back to Shopee.
-   LOGISTICS\_PICKUP\_DONE: For integrated logistics, this means the parcel has been picked up by a third party logistics provider. For non-integrated logistics, this means the user has entered shipping proof.
-   LOGISTICS\_DELIVERY\_FAILED: Parcel delivery to seller has failed. Only available for integrated logistics since this is updated by third party logistics provider back to Shopee.
-   LOGISTICS\_LOST: Parcel has been marked as lost. Only available for integrated logistics since this is updated by third party logistics provider back to Shopee.
-   LOGISTICS\_DELIVERY\_DONE: Parcel has been successfully delivered to seller. Only available for integrated logistics since this is updated by third party logistics provider back to Shopee.

### \[In-transit RR\]

-   Preparing
-   Delivered 
-   Delivery Failed 
-   Lost

### \[Return-on-the-Spot\]

-   Preparing
-   Delivered 
-   Delivery Failed 
-   Lost

## Post Return Logistics Status

Note this is only applicable to return parcels sent from warehouse back to seller

-   POST\_RETURN\_LOGISTICS\_REQUEST\_CREATED: Logistics request generated successfully with tracking number.
-   POST\_RETURN\_LOGISTICS\_REQUEST\_CANCELED: ​​Logistics request cancelled by warehouse team
-   POST\_RETURN\_LOGISTICS\_PICKUP\_FAILED: Failed to pickup parcel
-   POST\_RETURN\_LOGISTICS\_PICKUP\_RETRY: Subsequent attempt to pickup parcel.
-   POST\_RETURN\_LOGISTICS\_PICKUP\_DONE: Successful pickup; on the way to destination.
-   POST\_RETURN\_LOGISTICS\_DELIVERY\_FAILED: Failed delivery of parcel. Driver will return parcel back to warehouse.
-   POST\_RETURN\_LOGISTICS\_DELIVERY\_DONE: Successful delivery of parcel
-   POST\_RETURN\_LOGISTICS\_LOST: Parcel marked as Lost
