# US market: Mandatory compliance attributes added for Paper Money and Coins Categories

> Source: https://partner.tiktokshop.com/docv2/page/l8xklei9
> Section: Changelog
> Scraped: 2026-05-21T00:46:31.541Z

---

# Summary

### What are we launching

In the event that non-circulating currencies are sold as commodities, to ensure full regulatory compliance, we will introduce new mandatory attributes to the Get Attributes API. Sellers are required to fully complete all such attributes as a precondition for listing products in the affected product categories.

### When are we launching it

API enforcement for product listings (**Create and Edit Product APIs**) with these compliance attributes will begin after **April 7th, 2026**.

### Impact

All existing and new listings in the affected categories must comply and provide the required information after **April 7th, 2026** to avoid being taken down or receiving listing errors.

### Which markets are impacted?

Only the **US market** will be impacted at this time.

# Affected Categories and Attributes

### Affected Categories

The following leaf categories are impacted by this update:

| Category Name | Leaf Category ID |
| --- | --- |
| 
US Paper Money

 | 

1393552

 |
| 

US Coins

 | 

1931792

 |

### Mandatory Compliance Attributes

The following attributes will become mandatory for the categories listed above:

| **attribute.name** | **attribute.id** | **Supported input type** |
| --- | --- | --- |
| 
Circulation

 | 

102992

 | 

Drop down only

 |
| 

Authenticated/Graded

 | 

102216

 | 

Drop down only

 |
| 

Paper money grader

 | 

102995

 | 

Drop down only

 |
| 

Paper money grade

 | 

102998

 | 

Drop down only

 |
| 

Certification number

 | 

102218

 | 

Free form only

 |
| 

Paper money condition

 | 

103351

 | 

Drop down only

 |
| 

Coin grader

 | 

102993

 | 

Drop down only

 |
| 

Coin grade

 | 

102996

 | 

Drop down only

 |
| 

Coin condition

 | 

103353

 | 

Drop down only

 |
| 

Year

 | 

100530

 | 

Free form only

 |
| 

Fineness

 | 

102893

 | 

Free form& Drop down

 |
| 

Coin composition

 | 

103355

 | 

Free form& Drop down

 |

# API Changes

### 1\. Get Attributes

The response will include the mandatory status for the listed attributes when querying for the affected categories.

### 2\. Get Category Rules

The `requirement_conditions` in the response will indicate that these attributes are now mandatory for the US market.

### 3\. Create Product & Edit Product

Developers must ensure that these attributes are provided in the request body. Failure to provide mandatory attributes will result in an error with a message indicating the missing fields.

**For more information, please refer to the TikTok Shop Academy.**
