# (37) Product audit status change

> Source: https://partner.tiktokshop.com/docv2/page/37-product-audit-status-change
> Section: Webhooks
> Scraped: 2026-05-21T00:30:22.727Z

---

## Trigger scenario

When the product audit status changes, you'll receive this webhook.  
With this webhook, you can easily get the audit status information of a product instead of calling [Get Product](get-product) API.  
To receive this webhook, you must ensure the `Product Basic` API scope is enabled in Partner Center.

## Data business parameters

| **Parameter name** | **Data Type** | **Sample** | **Description** |
| --- | --- | --- | --- |
| 
type

 | 

int

 | 

`37`

 | 

The ID of the webhook type.

 |
| 

tts\_notification\_id

 | 

string

 | 

`"7327112393057371910"`

 | 

The ID of the notification.

 |
| 

shop\_id

 | 

string

 | 

`"7494049642642441621"`

 | 

The ID of the shop.

 |
| 

timestamp

 | 

int

 | 

`1644412885`

 | 

The time when this webhook was triggered, represented by Unix timestamp.

 |
| 

data

 | 

object

 | 

 | 

 |
| 

└ product\_id

 | 

int

 | 

`789078671231`

 | 

The ID of the product.

 |
| 

└ audit

 | 

object

 | 

 | 

The product audit information.

 |
| 

└└ status

 | 

string

 | 

`PRE_APPROVED`

 | 

The product audit status.  
Possible values:  
  
\* NONE: The product is not applicable for audit because it has not been submitted for listing on this platform, or it is in a draft, frozen, or deactivated state.  
\* AUDITING: The product is currently being audited.  
\* FAILED: The product failed the audit, or the audit was cancelled.  
\* PRE\_APPROVED: The product has passed the audit but is not yet listed due to pending prerequisites. Refer to `pre_approved_reasons` for the prerequisites.  
\* APPROVED: The product passed the audit and has been listed on the platform.

 |
| 

└└ pre\_approved\_reasons

 | 

\[\]string

 | 

\["KYC\_PENDING"\]

 | 

The reason why the audit status of the product is `PRE_APPROVED`.  
Possible values:  
  
\* KYC\_PENDING: The seller's onboarding (KYC - Know Your Customer information) is incomplete or awaiting processing.  
\* RESTRICTED\_CATEGORY\_PENDING: Applicable only for the US market. The product is in a restricted category, and category approval is still pending. To request access, submit an application through the Qualification Center on TikTok Shop Seller Center.

 |
| 

└ integrated\_platform\_statuses

 | 

object

 | 

 | 

The current audit status of the product on platforms that are natively integrated with TikTok Shop (e.g. TOKOPEDIA).

 |
| 

└└ platform

 | 

string

 | 

`TOKOPEDIA`

 | 

The integrated platform name.  
Possible values:  
  
\* TOKOPEDIA.

 |
| 

└└ status

 | 

string

 | 

`AUDITING`

 | 

The product audit status.  
Possible values:  
  
\* NONE: The product is not applicable for audit because it has not been submitted for listing on this platform, or it is in a draft, frozen, or deactivated state.  
\* AUDITING: The product is currently being audited.  
\* FAILED: The product failed the audit or the audit was cancelled.  
\* APPROVED: The product passed the audit and has been listed on the platform.

 |
| 

└ update\_time

 | 

int

 | 

`1644412885`

 | 

The time when the status changed, represented as a Unix timestamp (seconds).

 |

## Event example

JSON

Word Wrap

```
{
  "type": 37,
  "shop_id": "7494049642642441621",
  "tts_notification_id": "7327112393057371910",
  "timestamp": 1644412885,
  "data": {
    "product_id": 789078671231,
    "audit": {
      "status": "PRE_APPROVED",
      "pre_approved_reason": "KYC_PENDING"
    },
    "integrated_platform_statuses": {
      "platform": "TOKOPEDIA",
      "status": "AUDITING"
    },
    "update_time": 1644412885
  }
}
```
