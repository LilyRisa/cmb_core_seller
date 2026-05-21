# Enhanced Get Tracking API for CBT First-Mile Pickup Visibility

> Source: https://partner.tiktokshop.com/docv2/page/8syhoy40
> Section: Changelog
> Scraped: 2026-05-21T00:35:04.092Z

---

**Effective date:** June 30, 2026 (target — final date confirmed at general availability)  
**API version:** `v202604` or later  
**Audience:** Apps that integrate with TikTok Shop logistics APIs for CBT orders

## Overview

TikTok Shop is releasing an enhanced **Get Tracking API** that exposes the full end-to-end tracking history of CBT parcels — including the **first-mile pickup leg** that is currently missing from most App systems. Starting in late June 2026, your app can call a single endpoint to retrieve every tracking event a seller would otherwise see only in Seller Center, including pickup scans, line-haul transfers, last-mile delivery, and reshipment updates.

## What is changing

### New CBT first-mile tracking events

The Get Tracking API now returns first-mile carrier scan events that were previously available only in Seller Center. Your App will receive the same event stream that `seller.tiktokshop.com` displays — including the pickup scan, station inbound/outbound, and line-haul handover events.

### Updated tracking event flow

| Step | Event (action\_code\_name) | Visibility before | Visibility after |
| --- | --- | --- | --- |
| 
1

 | 

pkg\_shipped

 | 

Seller Center only

 | 

Seller Center + your App

 |
| 

2

 | 

pickup\_start

 | 

Seller Center only

 | 

Seller Center + your App

 |
| 

3

 | 

pickup\_success

 | 

Seller Center only

 | 

Seller Center + your App

 |
| 

4

 | 

pickup\_station\_in (first-mile carrier)

 | 

Seller Center only

 | 

Seller Center + your App

 |
| 

5

 | 

sc\_inbound (last-mile carrier)

 | 

Your App (via 17Track / 51Track)

 | 

Your App (direct from TikTok Shop)

 |
| 

6

 | 

signed\_personally

 | 

Your App

 | 

Your App

 |

### New and updated response fields

The response now returns multiple parcels per order, the latest tracking number after a reship, and a structured action code on every event.

| Field | Type | Description |
| --- | --- | --- |
| 
data.logistics\_details\[\]

 | 

array

 | 

One entry per parcel. A single order can contain multiple parcels (split fulfillment).

 |
| 

data.logistics\_details\[\].newest\_tracking\_no

 | 

string

 | 

The latest tracking number for the parcel. After a reshipment this differs from the original tracking number.

 |
| 

data.logistics\_details\[\].carrier\_name

 | 

string

 | 

The carrier display name (for example, J&T Express, USPS).

 |
| 

data.logistics\_details\[\].track\_list\[\].tracking\_no

 | 

string

 | 

The tracking number associated with the specific event (may differ from newest\_tracking\_no when first-mile and last-mile carriers issue separate tracking numbers).

 |
| 

data.logistics\_details\[\].track\_list\[\].action\_code

 | 

int

 | 

Numeric enum identifying the event.

 |
| 

data.logistics\_details\[\].track\_list\[\].action\_code\_name

 | 

string

 | 

Machine-readable name of the event (for example, pickup\_success, pickup\_station\_in).

 |
| 

data.logistics\_details\[\].track\_list\[\].description

 | 

string

 | 

Human-readable event description.

 |
| 

data.logistics\_details\[\].track\_list\[\].update\_time\_millis

 | 

int

 | 

Event timestamp in milliseconds, UTC.

 |

### Example

JSON

Word Wrap

```
{
  "code": 0,
  "message": "Success",
  "request_id": "string",
  "data": {
    "order_id": "577071607420326620",
    "logistics_details": [
      {
        "newest_tracking_no": "861651122474",
        "carrier_name": "J&T Express",
        "track_list": [
          {
            "description": "Package has been delivered!\n",
            "tracking_no": "861651122474",
            "update_time_millis": 1694686949000,
            "action_code": 50101,
            "action_code_name": "signed_personally"
          },
          {
            "description": "The shipping carrier is on the way to pick up your package.\n",
            "tracking_no": "861651122474",
            "update_time_millis": 1694686939000,
            "action_code": 20201,
            "action_code_name": "pickup_start"
          },
          {
            "description": "The shipping carrier is on the way to pick up your package.\n",
            "tracking_no": "VTPVN9037994516",
            "update_time_millis": 1694685548000,
            "action_code": 20201,
            "action_code_name": "pickup_start"
          },
          {
            "description": "The seller is preparing your package, and will hand it over to our carrier for shipping.\n",
            "tracking_no": "VTPVN9037994516",
            "update_time_millis": 1694685518000,
            "action_code": 20101,
            "action_code_name": "pkg_shipped"
          }
        ]
      },
      {
        "newest_tracking_no": "USPS1223456789",
        "carrier_name": "USPS",
        "track_list": [
          {
            "description": "Arrived at the carrier's facility.\n",
            "tracking_no": "USPS1223456789",
            "update_time_millis": 1694684527000,
            "action_code": 31301,
            "action_code_name": "sc_inbound"
          },
          {
            "description": "Package picked up.\n",
            "tracking_no": "USPS1223456789",
            "update_time_millis": 1694673516000,
            "action_code": 30901,
            "action_code_name": "pickup_success"
          }
        ]
      }
    ]
  }
}
```

### Reshipment behavior

When a parcel is reshipped with a new tracking number, the API returns the new value in `newest_tracking_no` while keeping the original number in the historical `track_list[].tracking_no` entries. Your app must reconcile both values so that the seller's App record stays aligned with Seller Center.

## How to integrate

### Step 1: Upgrade to API version `v202604`

Call the enhanced endpoint:

-   Method: `GET`
-   Path: `/logistics/202604/orders/{order_id}/tracking`
-   Required scope: `seller.logistics`
-   Required headers: `content-type: application/json`, `x-tts-access-token`
-   Required query parameters: `app_key`, `sign`, `timestamp`, `shop_cipher`

Pass the cross-border `shop_cipher` retrieved from the **Get Authorization Shop** API. Omitting it for a CBT shop returns an incorrect response.

### Step 2: Handle multi-parcel orders

Iterate over `data.logistics_details[]` instead of assuming one parcel per order. Display each parcel's `track_list` independently and key it by `newest_tracking_no`.

### Step 3: Reconcile reshipment tracking numbers

1.  On every poll, read `newest_tracking_no` for each parcel.
2.  Compare it against the tracking number stored in your App at order creation.
3.  If the values differ, update your internal record with `newest_tracking_no` and surface the new number to the seller. Do not overwrite the historical `track_list` entries — the original numbers remain valid for prior events.

### Step 4: Render events using `action_code_name`

Map each event to your App's tracking timeline using `action_code_name` (stable identifier) rather than `description` (localized free text). Treat unknown codes as informational and pass them through to the seller view.

### Step 5: Respect the rate limit

| Limit | Value |
| --- | --- |
| 
Per-order query frequency

 | 

Once every 8 hours

 |
| 

Average QPS

 | 

5 QPS

 |
| 

Cache responses for at least 8 hours per `order_id`. If your business case requires a higher QPS, contact TikTok Shop during the integration phase.

 | 

 |

### Step 6: Handle errors

| Code | Meaning | Recommended action |
| --- | --- | --- |
| 
36009003

 | 

Internal error

 | 

Retry with exponential backoff. If the failure persists, contact platform support.

 |

## FAQ

**Q: My app already integrates with 17Track or 51Track. Do I still need to upgrade?​**A: Yes, if you want the fastest and most complete data. Direct integration removes the third-party hop and surfaces events as soon as TikTok Shop receives them. Aggregatorintegrations will reach parity by mid-June 2026 but with added latency.  
**Q: How do I distinguish a first-mile event from a last-mile event in the response?​**A: Compare `track_list[].tracking_no` against `newest_tracking_no`. Events from the first-mile carrier carry the original tracking number; events from the last-mile carrier carry the carrier-issued number that becomes `newest_tracking_no` after handover.  
**Q: What if** `newest_tracking_no` \*\*is empty?\*\*A: The parcel has not yet been handed over to a downstream carrier or reshipped. Continue to use the order's original tracking number.  
**Q: Can I subscribe to tracking events instead of polling?​**A: A Tracking webhook is on the roadmap (target Q3 2026). Until it ships, poll the Get Tracking API within the 8-hour-per-order frequency limit.
