# (4) Package update

> Source: https://partner.tiktokshop.com/docv2/page/4-package-update
> Section: Webhooks
> Scraped: 2026-05-21T00:29:12.238Z

---

# 1\. Trigger scenario

The **package update** webhook is triggered when package updates such as combine, split, cancel the combine package operation, etc. occur.

# 2\. Data business parameters

| **Parameter name** | **Sample** | **Description** |
| --- | --- | --- |
| 
sc\_type

 | 

COMBINE

 | 

Type of the event trigger, with possible values:  
  
\* COMBINE  
\* CANCEL\_COMBINE  
\* SPLIT  
\* CANCEL\_SPLIT  
\* ADDRESS\_UPDATE\_SPLIT  
\* CANCEL\_FULFILL\_SPLIT  
\* FULFILL\_UNCOMBINE  
\* PARTLY\_CANCEL\_SPLIT  
\* SPLIT\_BY\_SKU\_CANCEL

 |
| 

role\_type

 | 

ROLE\_USER

 | 

Operator of the event trigger, with possible values:  
  
\* ROLE\_USER  
\* ROLE\_SELLER  
\* ROLE\_OPERATOR  
\* ROLE\_SYSTEM

 |
| 

package\_list

 | 

\[\]object

 | 

Package list updated by the event trigger

 |
| 

└ package\_id

 | 

"123456"

 | 

The identification of a package

 |
| 

└ order\_id\_list

 | 

\["152523", "532123"\]

 | 

List of order IDs in a given package

 |
| 

update\_time

 | 

1627587600

 | 

The time when the package was updated, represented as a Unix timestamp (seconds).

 |

## Event example

JSON

Word Wrap

```
{
  "type": 4,
  "tts_notification_id": "7327112393057371910",
  "shop_id": "7494049642642441621",
  "timestamp": 1644412885,
  "data": {
    "sc_type": "COMBINE",
    "role_type": "ROLE_USER",
    "package_list": [
      {
        "package_id": "123456",
        "order_id_list": [
          "152523",
          "532123"
        ]
      }
    ],
    "update_time": 1644412885
  }
}
```
