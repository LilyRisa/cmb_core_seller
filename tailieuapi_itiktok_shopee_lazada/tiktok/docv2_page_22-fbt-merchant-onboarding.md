# (22) FBT merchant onboarding

> Source: https://partner.tiktokshop.com/docv2/page/22-fbt-merchant-onboarding
> Section: Webhooks
> Scraped: 2026-05-21T00:28:36.547Z

---

## Trigger scenario

This webhook is triggered when the TikTok Shop seller onboards FBT platform to enable FBT service.

> Prerequisite: The **Fulfilled by TikTok(FBT) Info** API scope is enabled in Partner Center. For more information, refer to [Access Scope](access-scope).

## Data business parameters

| **Parameter Name** | **Data Type** | **Sample** | **Description** |
| --- | --- | --- | --- |
| 
type

 | 

int

 | 

`22`

 | 

The ID of this webhook topic, which is 22.

 |
| 

tts\_notification\_id

 | 

string

 | 

`"7327112393057371910"`

 | 

The ID of this webhook notification.

 |
| 

seller\_open\_id

 | 

string

 | 

`"VIyStQAAAADCBQ40s4TzOSSOEIW-bM5O9cod3vK8OytW8m-bnBYlXw"`

 | 

The open\_id of the seller. For more information, refer to [Authorization overview](authorization-overview-202407).

 |
| 

timestamp

 | 

int

 | 

`1644412885`

 | 

The UNIX timestamp when the FBT onboarding status is updated.

 |
| 

data

 | 

object

 | 

 | 

 |
| 

└ onboarded\_regions

 | 

\[\]object

 | 

 | 

All onboarded FBT regions.

 |
| 

└└ region\_code

 | 

string

 | 

`"GB"`

 | 

Onboarded FBT region code.

 |
| 

└ update\_time

 | 

int

 | 

`1644412845`

 | 

The latest update time. Unix timestamp.

 |

## Event example

JSON

Word Wrap

```
{
  "type": 22,
  "seller_open_id": "VIyStQAAAADCBQ40s4TzOSSOEIW-bM5O9cod3vK8OytW8m-bnBYlXw",
  "tts_notification_id": "7327112393057371910",
  "timestamp": 1644412885,
  "data": {
    "onboarded_regions": [
      {
        "region_code": "GB"
      }
    ],
    "update_time": 1644412845
  }
}
```
