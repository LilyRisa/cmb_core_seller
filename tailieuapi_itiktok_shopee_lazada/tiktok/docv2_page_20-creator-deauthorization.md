# (20) Creator deauthorization

> Source: https://partner.tiktokshop.com/docv2/page/20-creator-deauthorization
> Section: Webhooks
> Scraped: 2026-05-21T00:27:29.917Z

---

## Trigger scenario

When a creator completely removes the App's access to his/her data.

## Data business parameters

| Parameter name | Data Type | Sample | Description |
| --- | --- | --- | --- |
| 
type

 | 

int

 | 

`20`

 | 

Webhook type, fixed to `20` for this webhook.

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

creator\_open\_id

 | 

string

 | 

`"VIyStQAAAADCBQ40s4TzOSSOEIW-bM5O9cod3vK8OytW8m-bnBYlXw"`

 | 

The open\_id of the creator. To get the value, see [Authorization overview](authorization-overview-202407).

 |
| 

timestamp

 | 

int

 | 

`1644412885`

 | 

The UNIX timestamp when this webhook was triggered.

 |
| 

data

 | 

object

 | 

 | 

 |
| 

└ cancel\_time

 | 

string

 | 

`"1644412885"`

 | 

The UNIX timestamp when this event happened.

 |

## Event example

JSON

Word Wrap

```
{
  "type": 20,
  "creator_open_id": "VIyStQAAAADCBQ40s4TzOSSOEIW-bM5O9cod3vK8OytW8m-bnBYlXw",
  "tts_notification_id": "7327112393057371910",
  "timestamp": 1644412885,
  "data": {
    "cancel_time": 1644412885
  }
}
```
