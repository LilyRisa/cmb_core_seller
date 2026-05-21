# (66)Appeal Completed Webhook

> Source: https://partner.tiktokshop.com/docv2/page/ohw7toic
> Section: Webhooks
> Scraped: 2026-05-21T00:31:28.537Z

---

### **Webhook Name:** Appeal Completed Webhook

**Trigger Scenario:** This webhook is triggered when a size chart verification appeal is approved or rejected.  
**Scenario:** SHOP  
**Category:** Products

## Params

| Properties |  |  |  | Type | Must Return |
| --- | --- | --- | --- | --- | --- |
| 
shop\_id

 | 

 | 

 | 

 | 

string

 | 

true

 |
| 

type

 | 

 | 

 | 

 | 

int64

 | 

true

 |
| 

tts\_notification\_id

 | 

 | 

 | 

 | 

string

 | 

true

 |
| 

timestamp

 | 

 | 

 | 

 | 

int64

 | 

true

 |
| 

data

 | 

 | 

 | 

 | 

object

 | 

true

 |
| 

└

 | 

appeal\_task

 | 

 | 

 | 

object

 | 

true

 |
| 

└

 | 

└

 | 

size\_chart\_id

 | 

 | 

int64

 | 

false

 |
| 

└

 | 

└

 | 

size\_chart\_image\_uri

 | 

 | 

string

 | 

false

 |
| 

└

 | 

└

 | 

appeal\_audit\_result

 | 

 | 

\[\]object

 | 

true

 |
| 

└

 | 

└

 | 

└

 | 

indicator\_type

 | 

string

 | 

true

 |
| 

└

 | 

└

 | 

└

 | 

status

 | 

string

 | 

true

 |
| 

└

 | 

└

 | 

└

 | 

reject\_reason

 | 

string

 | 

false

 |
| 

└

 | 

product\_id

 | 

 | 

 | 

int64

 | 

true

 |
