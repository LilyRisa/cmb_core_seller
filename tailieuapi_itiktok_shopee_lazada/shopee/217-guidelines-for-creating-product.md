# Guidelines for Creating Product

> Source: https://open.shopee.com/developer-guide/217
> Category: 
> Scraped: 2026-05-20T20:37:33.448Z

---

1) We recommend cross-border sellers who have upgraded CNSC/KRSC to read the following articles to create products:

-   [Product creation preparation](https://open.shopee.com/developer-guide/209)
-   [Creating global product](https://open.shopee.com/developer-guide/213)
-   [Publish global product](https://open.shopee.com/developer-guide/215)

2）For other types of sellers, we recommend reading the following articles:

-   [Product creation preparation](https://open.shopee.com/developer-guide/209)
-   [Create product](https://open.shopee.com/developer-guide/211)

# API call flow overview

\*Solid line is required process, dashed line is not required process

## 1\. Creating Product

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=h6oVTrljpWY5S0tzciqOnG9YqfQGBKY0kK2R7CZfSaYxi3MuWqsNzSN%2BPL50gXxhG1ImXimQ2aQtAhsB2uRKEA%3D%3D&image_type=png)

## 2\. Creating global product

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=Gu3z%2FttxxOY0eNPh7vBiitjykBI5B9m4U%2FJjIQu1hZGBw%2FSLdo2rBIjGjIgRnHUYaizhVXpQd7iTSbqmKX6dIw%3D%3D&image_type=png)

## 3\. Publishing global product

  

  

  

  

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=canaYd%2FLXy8qTVaTeWvYhivWE4ZMNJtKdBdYo0dtqMLMRjGozjA9Zb3KWiKAkWccuhYqnfzgxeLv1KWH2tPxHw%3D%3D&image_type=png)

# Data Definition

# Attribute value data type

(input\_validation\_type)

-   INT\_TYPE
-   STRING\_TYPE
-   ENUM\_TYPE
-   FLOAT\_TYPE
-   TIMESTAMP\_TYPE
-   DATE\_TYPE

## Attribute input type

(input\_type)

-   DROP\_DOWN
-   TEXT\_FILED
-   COMBO\_BOX
-   MULTIPLE\_SELECT
-   ﻿MULTIPLE\_SELECT\_COMBO\_BOX

## Logistics type

(fee\_type)

-   SIZE\_SELECTION
-   SIZE\_INPUT
-   FIXED\_DEFAULT\_PRICE
-   CUSTOM\_PRICE

## Item status type

(item\_status)

-   NORMAL
-   DELETED
-   BANNED
-   UNLIST

## Translation language

(language)

-   zh-hans：Simplified Chinese
-   zh-hant: Traditional Chinese
-   ms-my：Malay
-   en-my: English (Malaysia)
-   en: English
-   id: Indonesian
-   vi: Vietnamese
-   th: Thai
-   pt-br: Portuguese
-   es-mx: Spanish (Mexican)
-   pl: Polish
-   es-CO: Spanish (Colombia)
-   es-CL: Spanish (Chile)

## Stock type

(stock\_type)

-   1: Shopee Warehouse stock
-   2: Seller stock

## Product promotion type

(promotion\_type)

-   Campaign
-   Discount Promotions
-   Flash Sale
-   Whole Sale
-   Group Buy
-   Bundle Deal
-   Welcome Package
-   Add-on Discount
-   Brand Sale
-   In ShopFlash Sale
-   Gift with purchase
-   ﻿Exclusive Price

## Market Code

-   SG: Singapore
-   MY: Malaysia
-   TW: Taiwan
-   ID: Indonesia
-   VN: Vietnam
-   TH: Thailand
-   BR: Brazil
-   PH: Philippines
-   MX: Mexico
-   CO: Colombia
-   CL: Chile
-   PL: Poland
