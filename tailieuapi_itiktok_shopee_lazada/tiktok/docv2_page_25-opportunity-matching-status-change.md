# (25) Opportunity matching status change

> Source: https://partner.tiktokshop.com/docv2/page/25-opportunity-matching-status-change
> Section: Webhooks
> Scraped: 2026-05-21T00:30:08.830Z

---

# 1\. Trigger scenario

This webhook is triggered when the status of opportunity matching for a candidate product changes.  
**Prerequisite**: The "Product Opportunities" API scope is enabled in Partner Center. For more information, refer to [Access Scope](access-scope).  
📌 This webhook is currently available only to a limited set of developers.

# 2\. Data business parameters

| **Parameter name** | **Data type** | **Sample** | **Description** |
| --- | --- | --- | --- |
| 
type

 | 

int

 | 

25

 | 

The ID of this webhook topic, which is 25.

 |
| 

tts\_notification\_id

 | 

string

 | 

"7327112393057371910"

 | 

The ID of this webhook notification.

 |
| 

shop\_id

 | 

string

 | 

"7494049642642441621"

 | 

The shop ID.

 |
| 

timestamp

 | 

int

 | 

1730458800

 | 

The time when this webhook is triggered. Unix timestamp.

 |
| 

data

 | 

object

 | 

 | 

 |
| 

└ external\_product\_id

 | 

string

 | 

"576486316948490000"

 | 

An external product identifier used in the external ecommerce platform.

 |
| 

└ opportunity\_matching\_status

 | 

string

 | 

"PENDING"

 | 

The status of opportunity matching.  
Possible values:  
\- `PENDING`: Waiting for opportunity matching.  
\- `MATCHED`: There are one or more matched opportunities.  
\- `NOT_MATCHED`: There are no matches.

 |
| 

└ opportunity\_ids

 | 

\[\]string

 | 

\["213231", "213233"\]

 | 

A list of IDs for the matched opportunities.

 |
| 

└ opportunity\_matching\_end\_time

 | 

int

 | 

1735603200

 | 

The time by which the system will stop matching opportunities for this candidate product. Unix timestamp.  
\- Default: 60 days from the upload time.  
\- Latest allowable time: 60 days from the upload time.

 |
| 

└ update\_time

 | 

int

 | 

1730458800

 | 

The time when the opportunity matching status is last updated. Unix timestamp.

 |

## Event example

JSON

Word Wrap

```
{
  "type": 25,
  "tts_notification_id": "7327112393057371910",
  "shop_id": "7494049642642441621",
  "timestamp": 1730458800,
  "data": {
    "external_product_id": "576486316948490000",
    "opportunity_matching_status": "PENDING",
    "opportunity_ids": [
      "213231",
      "213233"
    ],
    "opportunity_matching_end_time": 1735603200,
    "update_time": 1730458800
  }
}
```
