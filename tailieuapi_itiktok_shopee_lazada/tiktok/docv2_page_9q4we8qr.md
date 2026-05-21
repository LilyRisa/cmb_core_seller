# New Feature: Inventory Update Webhook

> Source: https://partner.tiktokshop.com/docv2/page/9q4we8qr
> Section: Changelog
> Scraped: 2026-05-21T00:46:44.751Z

---

# New Feature: Inventory Update Webhook

### What is changing?

TikTok Shop is launching a new `inventory_update` webhook to push real-time events for SKU inventory quantity changes. Previously, developers had to indirectly infer inventory changes by subscribing to multiple webhook messages (e.g., order creation, marketing campaigns), which was complex and error-prone.  
The new `inventory_update` webhook standardizes inventory change notifications. Whether caused by new order creation, order cancellation, manual seller adjustments, API calls, or inventory locking/releasing from marketing campaigns or creator collaborations, the system will push real-time messages with a unified structure via this webhook.  
This will help developers and service providers synchronize product inventory more accurately and efficiently, avoiding overselling or stockout issues caused by inventory information delays or inconsistencies, and simplifying inventory management logic.

### Which markets are affected?

This change applies to all markets.

### Who is affected?

This change applies to all developers integrated with TikTok Shop APIs.

### Which version is applicable?

This is a new webhook push event and does not involve API version changes. Messages will be pushed to the webhook URL configured in the developer's application.

### What action is required?

It is highly recommended to integrate this webhook into your application so that your merchants can benefit from more real-time two-way inventory synchronization. Once implemented, you can more reliably track inventory changes on the TikTok Shop platform.  
Detailed protocol and field descriptions for this webhook can be found in the technical details below.

### Technical Details

#### Trigger Conditions

The `inventory_update` webhook will be triggered when any of the following events cause SKU inventory quantity changes:

-   `order_created`: Order successfully placed, inventory is committed.
-   `order_canceled`: Order canceled by user or seller, committed inventory is released back to available stock.
-   `order_shipped`: Order shipped, committed inventory is deducted.
-   `manual_adjustment`: Seller manually modifies inventory in Seller Center.
-   `api_sync`: Developer updates inventory via inventory-related OpenAPIs.
-   `campaign_lock`: Seller signs up for platform marketing campaigns, part of the inventory is locked.
-   `campaign_unlock`: Marketing campaign ends or seller withdraws, locked inventory is released.
-   `creator_lock`: Seller establishes collaboration with a creator, allocating exclusive inventory.
-   `creator_unlock`: Collaboration with creator ends, locked inventory is released.
-   `system_auto_replenish`: System automatically replenishes inventory (e.g., order failure due to exceptions).

#### Payload Structure

The payload for each inventory update push includes common event information, the post-change inventory snapshot, and specific change details.

| **Field Name** | **Type** | **Required** | **Description** |
| --- | --- | --- | --- |
| 
`event_id`

 | 

String

 | 

Yes

 | 

Unique identifier for the event, can be used for event-level idempotency.

 |
| 

`occurred_at`

 | 

String

 | 

Yes

 | 

Time when the event occurred (UTC+0), in ISO 8601 format.

 |
| 

`seller_id`

 | 

int64

 | 

Yes

 | 

Seller ID.

 |
| 

`product_id`

 | 

int64

 | 

Yes

 | 

Product ID.

 |
| 

`sku_id`

 | 

int64

 | 

Yes

 | 

SKU ID where the inventory change occurred.

 |
| 

**quantity\_snapshot\_after\_change (Object)**: Post-change SKU inventory snapshot

 | 

 | 

 | 

 |
| 

`total_quantity`

 | 

Integer

 | 

Yes

 | 

Total warehouse inventory. Equals `total_available_quantity` + `total_committed_quantity`.

 |
| 

`total_available_quantity`

 | 

Integer

 | 

Yes

 | 

Available warehouse inventory. Total inventory physically present in the warehouse and available for sale.

 |
| 

`total_committed_quantity`

 | 

Integer

 | 

Yes

 | 

Committed order inventory. Inventory that has been ordered by users but not yet shipped.

 |
| 

`in_shop_quantity`

 | 

Integer

 | 

Yes

 | 

In-shop available inventory. Available stock currently not locked by any orders, campaigns, or creators. Equals `total_available_quantity` - (`campaign_locked_quantity` + `creator_locked_quantity`).

 |
| 

`campaign_locked_quantity`

 | 

Integer

 | 

Yes

 | 

Inventory locked by marketing campaigns.

 |
| 

`creator_locked_quantity`

 | 

Integer

 | 

Yes

 | 

Inventory locked by creator collaborations.

 |
| 

**change\_detail (List): List of inventory change details. In most cases, this list contains only one object.**

 | 

 | 

 | 

 |
| 

`idempotency_key`

 | 

String

 | 

Yes

 | 

Unique identifier for a single change operation, can be used for detail-level idempotency.

 |
| 

`trigger_source`

 | 

String

 | 

Yes

 | 

Reason for the inventory change, corresponding to the Trigger Condition Code above.

 |
| 

`occurred_at`

 | 

String

 | 

Yes

 | 

Time when the specific change event occurred (UTC+0). Can be used to ensure sequential processing.

 |
| 

`total_quantity_delta`

 | 

Integer

 | 

Yes

 | 

Change amount for total warehouse inventory.

 |
| 

`available_quantity_delta`

 | 

Integer

 | 

Yes

 | 

Change amount for available warehouse inventory.

 |
| 

`committed_quantity_delta`

 | 

Integer

 | 

Yes

 | 

Change amount for committed order inventory.

 |
| 

`in_shop_quantity_delta`

 | 

Integer

 | 

Yes

 | 

Change amount for in-shop available inventory.

 |
| 

`campaign_locked_quantity_delta`

 | 

Integer

 | 

Yes

 | 

Change amount for marketing campaign locked inventory.

 |
| 

`creator_locked_quantity_delta`

 | 

Integer

 | 

Yes

 | 

Change amount for creator collaboration locked inventory.

 |
| 

**Note: For all delta fields, positive numbers represent increases, and negative numbers represent decreases.**

 | 

 | 

 | 

 |

**

#### Example Payload

The following is an example of a Webhook payload when a seller manually increases the inventory of an SKU by 4 units through the Seller Center:

JSON

Word Wrap

```
{
  "event_id": "d7813cae-9997-4d24-a583-7d85801250f1",
  "occurred_at": "2026-04-02T09:28:34.979101552Z",
  "seller_id": "7498123456789012345",
  "product_id": "1891234567890123456",
  "sku_id": "1729507467923261408",
  "quantity_snapshot_after_change": {
    "total_quantity": 7,
    "total_available_quantity": 7,
    "total_committed_quantity": 0,
    "in_shop_quantity": 7,
    "campaign_locked_quantity": 0,
    "creator_locked_quantity": 0
  },
  "change_detail": [
    {
      "idempotency_key": "d7813cae-9997-4d24-a583-7d85801250f1",
      "trigger_source": "manual_adjustment",
      "occurred_at": "2026-04-02T09:28:34.979101552Z",
      "total_quantity_delta": 4,
      "available_quantity_delta": 4,
      "committed_quantity_delta": 0,
      "in_shop_quantity_delta": 4,
      "campaign_locked_quantity_delta": 0,
      "creator_locked_quantity_delta": 0
    }
  ]
}
```

#### Idempotency Explanation

To ensure the accuracy and reliability of message processing, we provide two levels of idempotency protection:

-   **Event-level Idempotency**: The top-level `event_id` is a unique identifier for each Webhook push event. You can prevent duplicate processing of the same push event by recording and checking the `event_id`.
-   **Change Detail-level Idempotency**: Each object in the `change_detail` list contains an `idempotency_key`, which uniquely identifies the specific operation that caused the inventory change. Since one push might aggregate multiple changes (though rare), it is recommended to also use this `idempotency_key` for deduplication when processing the `change_detail` list.

Additionally, you can use the `occurred_at` field to compare the sequence of events and ensure inventory status is updated in the correct chronological order.

**
