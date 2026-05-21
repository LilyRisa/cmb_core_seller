# For Indonesian market: Changes in Order and Finance APIs for Horizon+ Orders

> Source: https://partner.tiktokshop.com/docv2/page/u62dh98s
> Section: Changelog
> Scraped: 2026-05-21T00:48:02.510Z

---

The orders with tag "Horizon+" will hide shipping fees at sku prices, so the sku prices will be increased and the shipping fee will be reduced. There are changes in order and finance APIs for this type of order which are estimated to be effective on January 22, 2026. By using these API, the sellers can get the original sku prices and shipping fees. These original sku prices and shipping fees can also be checked at the seller center.  
Here is the list of changes:

1.  [Get Order Detail](https://partner.tiktokshop.com/docv2/page/get-order-detail-202507) ([https://partner.tiktokshop.com/docv2/page/get-order-detail-202507](https://partner.tiktokshop.com/docv2/page/get-order-detail-202507)) and [Get Order List](https://partner.tiktokshop.com/docv2/page/get-order-list-202309) ([https://partner.tiktokshop.com/docv2/page/get-order-list-202309](https://partner.tiktokshop.com/docv2/page/get-order-list-202309)) API

Response Parameters (Newly added fields in bond lines, existing fields with new logic in italic lines):

| Properties |  | Type | Properties description | New logic |
| --- | --- | --- | --- | --- |
| 
code

 | 

 | 

int

 | 

The success or failure status code returned in API response.

 | 

 |
| 

message

 | 

 | 

string

 | 

The success or failure messages returned in API response. Reasons of failure will be described in the message.

 | 

 |
| 

request\_id

 | 

 | 

string

 | 

Request log

 | 

 |
| 

data

 | 

 | 

object

 | 

Specific return information

 | 

 |
| 

\>

 | 

orders

 | 

\[\]object

 | 

Order information.

 | 

 |
| 

**\>>**

 | 

**order\_rights**

 | 

**int**

 | 

**Order tag identifier if has certain rights within the order based on the program subscribed by sellers.**  
**1 = Shipping Fee Reimbursement Program**  
**2 = Horizon+ Program**  
**Applicable for SEA market only**

 | 

 |
| 

\>>

 | 

Payment

 | 

\[\]object

 | 

Payment info about a TikTok Shop order.

 | 

 |
| 

\>>>

 | 

*sub\_total*

 | 

*string*

 | 

*Buyer paid subtotal of all the SKUs in the order. For the US market, this is pre-tax total amount.sub\_total = original\_total\_product\_price - seller\_discount - platform\_discount.*

 | 

*SKUPriceRemainingAmount + SKUPriceOverchargeAmount*

 |
| 

\>>>

 | 

shipping\_fee

 | 

string

 | 

Buyer paid shipping fee. shipping\_fee = original\_shipping\_fee - shipping\_fee\_seller\_discount - shipping\_fee\_platform\_discountFor the US market, this is pre-tax total amount.

 | 

ShippingSalePrice (No change)

 |
| 

\>>>

 | 

original\_shipping\_fee

 | 

string

 | 

Shipping fee before discount

 | 

ShippingListPrice (No Change)

 |
| 

\>>>

 | 

*original\_total\_product\_price*

 | 

*string*

 | 

*Total original price of products (VAT included for crossborder shop).For the US market, this is pre-tax total amount.*

 | 

*Total of SKUlistPrice, if the order is distance fee, then total of SKUOriginListPrice*

 |
| 

**\>>>**

 | 

**distance\_shipping\_fee**

 | 

**string**

 | 

**Distance shipping fee is fee that is charged by our logistics partner and covers the separate distance-based cost for deliveries outside Java island as a part of Horizon+ Program.**  
**Only applicable in ID Market.**

 | 

**SKUPriceLogisticsAmount**

 |
| 

**\>>>**

 | 

**distance\_fee\_amount**

 | 

**string**

 | 

**Total distance fee for Horizon+ Program. Only applicable for ID market**

 | 

**SKUFreightEmbedAmount**

 |
| 

\>>

 | 

line\_items

 | 

\[\]object

 | 

Line item info list.

 | 

 |
| 

**\>>>**

 | 

*original\_price*

 | 

*string*

 | 

*Item original price, please refer to the currency of payment\_info.*

 | 

*if hidden fee, then it shows SKUOriginListPrice, but if normal order then it shows SKUlistprice value*

 |
| 

**\>>>**

 | 

*salePrice*

 | 

*string*

 | 

*Item sale price, please refer to the currency of payment\_info.*  
*For ID market, if order included in the horizon+ program, the saleprice is equal to final sale product without the distance fee*

 | 

*Item subtotal after discount*

 |
| 

**\>>>**

 | 

**distance\_shipping\_fee**

 | 

**string**

 | 

**Distance shipping fee is fee that is charged by our logistics partner and covers the separate distance-based cost for deliveries outside Java island as a part of Horizon+ Program.**  
**Only applicable in ID Market.**

 | 

**SKUPriceLogisticsAmount**

 |
| 

**\>>>**

 | 

**distance\_fee\_amount**

 | 

**string**

 | 

**Total distance fee for Horizon+ Program. Only applicable for ID market**

 | 

**SKUFreightEmbedAmount**

 |

1.  [Get Price Detail](https://partner.tiktokshop.com/docv2/page/get-price-detail-202407) API ([https://partner.tiktokshop.com/docv2/page/get-price-detail-202407](https://partner.tiktokshop.com/docv2/page/get-price-detail-202407))

Response Parameters (Newly added fields in bond lines, existing fields with new logic in italic lines):

| Properties |  | Type | Properties description | New Calculation |
| --- | --- | --- | --- | --- |
| 
code

 | 

 | 

int

 | 

The success or failure status code returned in API response.

 | 

 |
| 

message

 | 

 | 

string

 | 

The success or failure messages returned in API response. Reasons of failure will be described in the message.

 | 

 |
| 

request\_id

 | 

 | 

string

 | 

Request log

 | 

 |
| 

data

 | 

 | 

object

 | 

Specific return information

 | 

 |
| 

\>

 | 

*sku\_list\_price*

 | 

*string*

 | 

*Total MSRP price of the products.*

 | 

*if hidden fee, then it shows SKUOriginListPrice, but if normal order then it shows SKUlistprice value*

 |
| 

\>

 | 

*subtotal*

 | 

*string*

 | 

*Total promotional sale price of the products including tax. Calculation: sku\_sale\_price + subtotal\_tax\_amount*

 | 

*SKUPriceRemainingAmount + SKUPriceOverchargeAmount*

 |
| 

\>

 | 

*sku\_sale\_price*

 | 

*string*

 | 

*Total promotional sale price of the products. Calculation: sku\_list\_price - subtotal\_deduction\_seller - subtotal\_deduction\_platform*  
  
*For ID market, if order included in the horizon+ program, the saleprice is equal to final sale product without the distance fee*

 | 

*New field from pricedetail = SkuListPrice - Platform discount on items - Seller discount on items - Distant Shipping fee from Horizon Program - SkuPriceOverChargePlatformAmount = SkuPriceRemainingAmount + SkuPriceOverChargeAmount*

 |
| 

\>

 | 

shipping\_list\_price

 | 

string

 | 

Original shipping price

 | 

ShippingListPrice (No change)

 |
| 

\>

 | 

shipping\_sale\_price

 | 

string

 | 

Promotional shipping price Calculation: shipping\_list\_price - shipping\_fee\_deduction -shipping\_fee\_deduction\_platform

 | 

ShippingSalePrice (No Change)

 |
| 

\>

 | 

distance\_shipping\_fee

 | 

string

 | 

Distance shipping fee is fee that is charged by our logistics partner and covers the separate distance-based cost for deliveries outside Java island as a part of Horizon+ Program.  
Only applicable in ID Market.

 | 

take from SkuPriceLogisticsAmount

 |
| 

**\>**

 | 

**distance\_fee**

 | 

**string**

 | 

**Total distance fee for Horizon+ Program. Only applicable for ID market**

 | 

**SKUFreightEmbedAmount**

 |
| 

line\_items

 | 

 | 

\[\]object

 | 

Each object is the same as the "data" field (line 5) without "line\_items".

 | 

 |
| 

\>

 | 

*subtotal*

 | 

*string*

 | 

*Total promotional sale price of the products including tax. Calculation: sku\_sale\_price + subtotal\_tax\_amount*

 | 

*Confirm to RD*

 |
| 

\>

 | 

sku\_list\_price

 | 

string

 | 

Total MSRP price of the products.

 | 

if hidden fee, then it shows SKUOriginListPrice, but if normal order then it shows SKUlistprice value

 |
| 

\>

 | 

*sku\_sale\_price*

 | 

*string*

 | 

*Total promotional sale price of the products. Calculation: sku\_list\_price - subtotal\_deduction\_seller - subtotal\_deduction\_platform*  
  
*For ID market, if order included in the horizon+ program, the saleprice is equal to final sale product without the distance fee*

 | 

*Item subtotal after discount*

 |
| 

\>

 | 

shipping\_list\_price

 | 

string

 | 

Original shipping price

 | 

ShippingListPrice (No change)

 |
| 

\>

 | 

shipping\_sale\_price

 | 

string

 | 

Promotional shipping price Calculation: shipping\_list\_price - shipping\_fee\_deduction -shipping\_fee\_deduction\_platform

 | 

ShippingSalePrice (No Change)

 |
| 

\>

 | 

**distance\_shipping\_fee**

 | 

**string**

 | 

**Distance shipping fee is fee that is charged by our logistics partner and covers the separate distance-based cost for deliveries outside Java island as a part of Horizon+ Program.**  
**Only applicable in ID Market.**

 | 

**take from SkuPriceLogisticsAmount**

 |
| 

\>

 | 

**distance\_fee**

 | 

**string**

 | 

**Total distance fee for Horizon+ Program. Only applicable for ID market**

 | 

**SKUFreightEmbedAmount**

 |

1.  Statement APIs changes

| **API name** | **changes** | **Screenshot** |
| --- | --- | --- |
| 
[Get Statements](https://partner.tiktokshop.com/docv2/page/get-statements-202309) *([https://patner.tiktokshop.com/docv2/page/get-statements-202309](https://patner.tiktokshop.com/docv2/page/get-statements-202309))*

 | 

\* fee\_amount will add distant\_shipping\_fee value  
\* revenue\_amount will add distant\_item\_fee value  
  

 | 

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/ee95b9dac697473cb27ff43e6bdb97d7~tplv-k9wyc2ijk0-image.image)  

 |
| 

[Get Transactions by Order](https://partner.tiktokshop.com/docv2/page/get-transactions-by-order-202501) *([https://partner.tiktokshop.com/docv2/page/get-transactions-by-order-202501](https://partner.tiktokshop.com/docv2/page/get-transactions-by-order-202501))*

 | 

\* shipping\_cost\_amount will add distant\_shipping\_fee value  
\* revenue\_amount will add distant\_item\_fee value  
\* A new field “distant\_shipping\_fee” will be shown under shipping\_cost\_breakdown object  
\* A new field “distant\_item\_fee” will be shown under revenue\_breakdown object  
  

 | 

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/a807a66cbdd7488b9e2d5d808121278d~tplv-k9wyc2ijk0-image.image)  

 |
| 

[Get Transactions by Statement](https://partner.tiktokshop.com/docv2/page/get-transactions-by-statement-202501) *([https://patner.tiktokshop.com/docv2/page/get-transactions-by-statement-202501](https://patner.tiktokshop.com/docv2/page/get-transactions-by-statement-202501))*

 | 

\* total\_settlement\_amount will add distant\_shipping\_fee value  
\* total\_shipping\_cost\_amount will add distant\_shipping\_fee value  
\* shipping\_cost\_amount will add distant\_shipping\_fee value  
\* revenue\_amount will add distant\_item\_fee value  
\* A new field “distant\_shipping\_fee” will be shown under shipping\_cost\_breakdown object  
\* A new field “distant\_item\_fee” will be shown under revenue\_breakdown object  
  

 | 

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/638190c200b4468c8351504a099b1625~tplv-k9wyc2ijk0-image.image)  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/e8479a5e9c4e4705aa62a5de59a70730~tplv-k9wyc2ijk0-image.image)  
  
  

 |
| 

[Get Unsettled Transactions](https://partner.tiktokshop.com/docv2/page/get-unsettled-transactions-202507) *([https://partner.tiktokshop.com/docv2/page/get-unsettled-transactions-202507](https://partner.tiktokshop.com/docv2/page/get-unsettled-transactions-202507))*

 | 

\* sum\_est\_fee\_amount will add distant\_shipping\_fee value  
\* sum\_est\_revenue\_amount will add distant\_item\_fee value  
\* est\_revenue\_amount will add distant\_item\_fee value  
\* est\_shipping\_cost\_amount will add distant\_shipping\_fee value  
\* A new field “distant\_shipping\_fee” will be shown under shipping\_cost\_breakdown object  
\* A new field “distant\_item\_fee” will be shown under revenue\_breakdown object

 | 

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/1c8497e8257f40458ad4262aabe7f773~tplv-k9wyc2ijk0-image.image)  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/69d7780075664941b4ef87a0d85e9df0~tplv-k9wyc2ijk0-image.image)  
  
  

 |

# Which markets are affected?

The updates of the requirements apply to the Local to Local sellers for the Indonesian market.

# Who is affected?

Developers with applications that use APIs shown above

# Which version is applicable?

The updates of the requirements apply to the versions shown above.

# What action is required?

Developers need to check whether to use APIs shown above and handle the related error messages.
