# (13) New conversation

> Source: https://partner.tiktokshop.com/docv2/page/13-new-conversation
> Section: Webhooks
> Scraped: 2026-05-21T00:28:07.502Z

---

# 1\. Trigger scenario

This **new conversation** webhook is triggered when a customer service agent joins or leaves a conversation.  
We recommend refreshing the conversation list when receiving this event.

# 2\. Data business parameters

| **Parameter name** | **Data type** | **Sample** | **Description** |
| --- | --- | --- | --- |
| 
conversation\_id

 | 

string

 | 

576486316948490001

 | 

The identification of the conversation

 |
| 

create\_time

 | 

int

 | 

1627587506

 | 

Conversation creation time, represented as a Unix timestamp (in seconds).

 |

## Event example

JSON

Word Wrap

```
{
  "type": 13,
  "tts_notification_id": "7327112393057371910",
  "shop_id": "7494049642642441621",
  "timestamp": 1644412885,
  "data": {
    "conversation_id": "576486316948490001",
    "create_time": 1627587506
  }
}
```
