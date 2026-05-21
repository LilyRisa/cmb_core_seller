# (17) Shoppable content posting

> Source: https://partner.tiktokshop.com/docv2/page/17-shoppable-content-posting
> Section: Webhooks
> Scraped: 2026-05-21T00:27:20.341Z

---

## Trigger scenario

The shoppable content posting webhook is triggered when the creator adds, updates, or removes a product link in a video or livestream.

> This webhook will only be triggered after the creator has authorized the `Read Creator Affiliate Collaborations` scope.

## Data business parameters

| **Parameter name** | **Data Type** | **Sample** | **Description** |
| --- | --- | --- | --- |
| 
type

 | 

int

 | 

`17`

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

└product\_id

 | 

string

 | 

`"789078671231"`

 | 

ID of the product promoted in the creator's video.

 |
| 

└event

 | 

object

 | 

 | 

 |
| 

└└type

 | 

string

 | 

`"ADD"`

 | 

The action which the creator takes to the product promoted in the video or live stream. The possible enumerations are:  
  
\* `ADD`: Add the product.  
\* `UPDATE`: Update the product.  
\* `REMOVE`: Remove the product.

 |
| 

└└scene

 | 

string

 | 

`"VIDEO"`

 | 

The scene where the creator promotes the product. The possible enumerations are:  
  
\* `VIDEO`: short video.

 |
| 

└└content\_id

 | 

string

 | 

`"789078671231"`

 | 

The ID of the short video.

 |

## Event example

JSON

Word Wrap

```
{
  "type": 17,
  "creator_open_id": "VIyStQAAAADCBQ40s4TzOSSOEIW-bM5O9cod3vK8OytW8m-bnBYlXw",
  "tts_notification_id": "7327112393057371910",
  "timestamp": 1644412885,
  "data": {
    "product_id": "789078671231",
    "event": {
      "type": "ADD",
      "scene": "VIDEO",
      "content_id": "789078671231"
    }
  }
}
```
