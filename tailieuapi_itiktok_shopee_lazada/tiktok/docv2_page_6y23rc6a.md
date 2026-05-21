# Message Types

> Source: https://partner.tiktokshop.com/docv2/page/6y23rc6a
> Section: API Reference
> Scraped: 2026-05-20T23:54:15.217Z

---

## Context

This API enables you to retrieve standardized card information for displaying conversational data. You can choose to use it based on your specific requirements.  
Our overall data is divided into three parts, and the content contained within these three sections may vary depending on the scenario, user, or purpose.  
**Content**  
The field `content` contains metadata of the message content, including only a limited set of key fields.  
**Custom UI? Build from Raw Data**  
The field `data` encapsulates the business data related to the message, such as order, product, or logistics information.  
Developers can use this field to build custom UI representations based on their own requirements. This field is returned only when `request.query.need_data=true`. Note that business data may be time-sensitive.  
**Need Speed? Use Plaintext.**  
The field `plaintext` is a plain text representation of the message intended for UI display.  
It already includes relevant business information, and developers may directly use this field for UI rendering without additional processing. This field is returned only when `request.query.need_plaintext=true`. Note that business data may be time-sensitive.

## TEXT

### content

#### **Properties**

| Properties | Type | Description |
| --- | --- | --- |
| 
content

 | 

string

 | 

Text content

 |

#### **Example**

JSON

Word Wrap

```
{
  "content": "simple text"
}
```

## IMAGE / EMOTICONS

### content

JSON

Word Wrap

```
{
  "url": "https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290",
  "width": 300,
  "height": 300
}
```

#### **Properties**

| Properties | Type | Description |
| --- | --- | --- |
| 
url

 | 

string

 | 

URL of the image

 |
| 

width

 | 

int

 | 

Width of the image

 |
| 

height

 | 

int

 | 

Height of the image

 |

## VIDEO

### content

JSON

Word Wrap

```
{
  "url": "https://video-boei18n.byted.org/storage/v1/tos-boei18n-v-c72c01/e8240f35244646428df9c3244d1a7408?x-tos-algorithm=v2&x-tos-authkey=5bf25627da095a5cba28ace592de46cc&x-tos-expires=1681980481&x-tos-signature=r_bRxtrvGhXAuZgMmNhlZ_Upqzg",
  "cover": "https://p-boei18n.byted.org/tos-boei18n-v-c72c01/o8keEOhzTcNCcJyAbkWZwpLIyTfkJxcGbRBvLP~tplv-jvtte31kaf-origin-jpeg.jpeg?",
  "width": 640,
  "height": 360,
  "duration": "20.504",
  "vid": "v0e30cg700f7cgcmu8jc77u9e2bdp95g",
  "expire_time": "1681980481",
  "format": "mp4",
  "size": 400000,
  "bit_rate": 156067,
  "quality": "original",
  "codec_type": "h264"
}
```

#### **Properties**

| Properties | Type | Description |
| --- | --- | --- |
| 
url

 | 

string

 | 

URL of the video

 |
| 

width

 | 

int

 | 

Width of the video

 |
| 

height

 | 

int

 | 

Height of the video

 |
| 

duration

 | 

string

 | 

Duration of the video

 |
| 

vid

 | 

string

 | 

Identifier of the video

 |
| 

expire\_time

 | 

int

 | 

Expiration of the video, in Unix timestamp

 |
| 

format

 | 

string

 | 

Video file format

 |
| 

size

 | 

int

 | 

File size in bytes

 |
| 

bit\_rate

 | 

int

 | 

Video bit rate in kbps

 |
| 

quality

 | 

string

 | 

Video quality level

 |
| 

codec\_type

 | 

string

 | 

Video codec

 |

## ORDER\_CARD

### content

#### **Properties**

| Properties | Type | Description |
| --- | --- | --- |
| 
order\_id

 | 

string

 | 

Order identifier

 |

#### **Example**

JSON

Word Wrap

```
{
  "order_id": "123"
}
```

### data

#### **Properties**

| Properties | Type | Description |
| --- | --- | --- |
| 
order\_id

 | 

string

 | 

Order identifier

 |
| 

order\_status

 | 

string

 | 

Current order status

 |
| 

product\_name

 | 

string

 | 

Product name

 |
| 

product\_image

 | 

string

 | 

Product image URL

 |
| 

paid\_price

 | 

string

 | 

Total amount paid

 |
| 

quantity

 | 

int

 | 

Number of items in the order

 |
| 

order\_link

 | 

string

 | 

URL to the order details page

 |

#### **Example**

JSON

Word Wrap

```
{
  "order_id": "123",
  "order_status": "Delivered",
  "product_id": "1234",
  "product_name": "abc",
  "product_image": "https://p16-oec-sg.ibyteimg.com/tos-alisg-i-aphluv4xwc-sg/1a80a431f63f447dbcb7f19b666757ea~tplv-aphluv4xwc-resize-webp:800:800.webp?dr=15582&t=555f072d&ps=933b5bde&shp=f359665f&shcp=5566cfe3&idc=my2&from=4082722399",
  "paid_price": "Rp16.000",
  "quantity": 3,
  "order_link": "https://seller-us.tiktok.com/order/detail?order_no=123"
}
```

### plaintext

#### **Example**

YAML

Word Wrap

```
Order card shared:  
- Order ID: 582362672348497014;   
- Current order status: Delivered;   
- Number of items: 3;   
- Order payment amount: Rp16.000.   
  
View full details at: https://abc.com/
```

## PRODUCT\_CARD

### content

#### **Properties**

| Properties | Type | Description |
| --- | --- | --- |
| 
product\_id

 | 

string

 | 

Product identifier

 |

#### **Example**

JSON

Word Wrap

```
{
  "product_id": "1234"
}
```

### data

#### **Properties**

| Properties | Type | Description |
| --- | --- | --- |
| 
product\_id

 | 

string

 | 

Product identifier

 |
| 

product\_name

 | 

string

 | 

Product display name

 |
| 

product\_image

 | 

string

 | 

Product image URL

 |
| 

product\_highest\_price

 | 

string

 | 

The highest price in the product's price range

 |
| 

product\_lowest\_price

 | 

string

 | 

The lowest price in the product's price range

 |
| 

sold\_quantity

 | 

int

 | 

Total number of units sold

 |
| 

product\_link

 | 

string

 | 

URL to the product details page

 |

#### **Example**

JSON

Word Wrap

```
{
  "product_id": "1234",
  "product_name": "abc",
  "product_image": "https://p16-oec-sg.ibyteimg.com/tos-alisg-i-aphluv4xwc-sg/1a80a431f63f447dbcb7f19b666757ea~tplv-aphluv4xwc-resize-webp:800:800.webp?dr=15582&t=555f072d&ps=933b5bde&shp=f359665f&shcp=5566cfe3&idc=my2&from=4082722399",
  "product_highest_price": "139.000₫",
  "product_lowest_price": "119.000₫",
  "sold_quantity": 40013,
  "product_link": "https://seller-us.tiktok.com/product/manage?search_content=1234"
}
```

### plaintext

#### **Example**

YAML

Word Wrap

```
Product card shared:  
- Product ID: 1732529206731441911;   
- Product name: abc;   
- Product lowest price: 119.000₫;   
- Product highest price: 139.000₫;  
- Sold: 40642.   
  
View full details at: https://abc.com/
```

## RETURN\_REFUND\_CARD

### content

#### **Properties**

| Properties | Type | Description |
| --- | --- | --- |
| 
order\_id

 | 

string

 | 

The associated order identifier

 |
| 

sku\_id

 | 

string

 | 

The specific product SKU identifier for the return/refund

 |

#### **Example**

JSON

Word Wrap

```
{
  "order_id": "123",
  "sku_id": "1234"
}
```

### data

#### **Properties**

| Properties | Type | Description |
| --- | --- | --- |
| 
sku\_id

 | 

string

 | 

The specific product SKU identifier

 |
| 

order\_id

 | 

string

 | 

The associated order identifier

 |
| 

order\_status

 | 

string

 | 

Current status of the order

 |
| 

product\_name

 | 

string

 | 

Name of the product being returned/refunded

 |
| 

product\_image

 | 

string

 | 

URL of the product image

 |
| 

paid\_price

 | 

string

 | 

The amount paid for the SKU

 |
| 

quantity

 | 

int

 | 

Number of items for the SKU

 |
| 

order\_link

 | 

string

 | 

URL to the original order details page

 |

#### **Example**

JSON

Word Wrap

```
{
  "sku_id": "1",
  "order_id": "123",
  "order_status": "Delivered",
  "product_id": "1234",
  "product_name": "abc",
  "product_image": "https://p16-oec-sg.ibyteimg.com/tos-alisg-i-aphluv4xwc-sg/1a80a431f63f447dbcb7f19b666757ea~tplv-aphluv4xwc-resize-webp:800:800.webp?dr=15582&t=555f072d&ps=933b5bde&shp=f359665f&shcp=5566cfe3&idc=my2&from=4082722399",
  "paid_price": "Rp10.667",
  "quantity": 2,
  "order_link": "https://seller-us.tiktok/com/order/detail?order_no=123"
}
```

### plaintext

#### **Example**

YAML

Word Wrap

```
Return/refund form shared:  
- SKU ID: 1;   
- Order ID: 123;  
- Order status: Delivered;   
- Product ID: 1234;   
- Product name: abc;   
- Number of items: 2;   
- Reverse amount: Rp10.667.
```

## COUPON\_CARD

### content

#### **Properties**

| Properties | Type | Description |
| --- | --- | --- |
| 
coupon\_id

 | 

string

 | 

Coupon identifier

 |

#### **Example**

JSON

Word Wrap

```
{
  "coupon_id": "12"
}
```

### data

#### **Properties**

| Properties | Type | Description |
| --- | --- | --- |
| 
coupon\_id

 | 

string

 | 

Coupon identifier

 |
| 

coupon\_title

 | 

string

 | 

Display title of the coupon

 |
| 

coupon\_type

 | 

string

 | 

Type of coupon (e.g., "Regular")

 |
| 

discount

 | 

string

 | 

The discount amount or percentage

 |
| 

threshold

 | 

string

 | 

The condition to be met to use the coupon

 |
| 

scope

 | 

string

 | 

The scope of products the coupon applies to

 |
| 

start\_time

 | 

int

 | 

The coupon validity start time (Unix timestamp)

 |
| 

end\_time

 | 

int

 | 

The coupon validity end time (Unix timestamp)

 |
| 

coupon\_link

 | 

string

 | 

URL to the coupon details page

 |

#### **Example**

JSON

Word Wrap

```
{
  "coupon_id": "12",
  "coupon_title": "cheap",
  "coupon_type": "Regular",
  "discount": "₱26.00 off",
  "threshold": "on order over ₱196.00",
  "scope": "Selected products",
  "start_time": 1768634640,
  "end_time": 1773829080,
  "coupon_link": "https://seller-us.toktok.com/promotion/marketing-tools/voucher/view/12"
}
```

### plaintext

#### **Example**

YAML

Word Wrap

```
Coupon shared:  
- Coupon ID: 12;   
- Coupon title: cheap;   
- Coupon discount: ₱26.00 off;  
- Coupon scope: Selected products;  
- Coupon condition: on order over ₱196.00;   
- Coupon validity: Jan 17, 2026 3:24 PM to Mar 18, 2026 6:18 PM GMT+08:00.   
  
View full details at: https://abc.com/
```

## LOGISTICS\_CARD

### content

#### **Properties**

| Properties | Type | Description |
| --- | --- | --- |
| 
order\_id

 | 

string

 | 

The associated order identifier

 |
| 

package\_id

 | 

string

 | 

The specific package identifier for tracking

 |

#### **Example**

JSON

Word Wrap

```
{
  "order_id": "123",
  "package_id": "12345"
}
```

### data

#### **Properties**

| Properties | Type | Description |
| --- | --- | --- |
| 
packages

 | 

list

 | 

An array of package objects, each containing logistics and product details.

 |

-   **Package**

| Properties | Type | Description |
| --- | --- | --- |
| 
package\_id

 | 

string

 | 

Package identifier

 |
| 

product\_name

 | 

string

 | 

Product display name

 |
| 

product\_image

 | 

string

 | 

Product image URL

 |
| 

paid\_price

 | 

string

 | 

Total amount paid

 |
| 

quantity

 | 

int

 | 

Number of items in the package

 |
| 

predict\_delivery\_time\_min

 | 

int

 | 

The minimum estimated delivery time

 |
| 

predict\_delivery\_time\_max

 | 

int

 | 

The maximum estimated delivery time

 |
| 

delivery\_option

 | 

string

 | 

The selected delivery option for the order

 |
| 

tracking\_number

 | 

string

 | 

The tracking number provided by the shipping provider

 |
| 

shipping\_provider\_name

 | 

string

 | 

The name of the shipping provider or carrier

 |
| 

tracking

 | 

list

 | 

The list of tracking records that describe the shipment status history

 |

-   **Tracking**

| Properties | Type | Description |
| --- | --- | --- |
| 
description

 | 

string

 | 

The description of the shipment status update

 |
| 

update\_time\_millis

 | 

int

 | 

The timestamp when the shipment status was updated

 |

#### **Example**

JSON

Word Wrap

```
{
  "packages": [
    {
      "package_id": "12345",
      "product_name": "abc",
      "product_image": "https://p16-oec-sg.ibyteimg.com/tos-alisg-i-aphluv4xwc-sg/1a80a431f63f447dbcb7f19b666757ea~tplv-aphluv4xwc-resize-webp:800:800.webp?dr=15582&t=555f072d&ps=933b5bde&shp=f359665f&shcp=5566cfe3&idc=my2&from=4082722399",
      "paid_price": "$0.01",
      "quantity": 1,
      "predict_delivery_time_min": 1763198750000,
      "predict_delivery_time_max": 1763457950000,
      "delivery_option": "Standard shipping",
      "tracking_number": "1ZCIETST0422222228",
      "shipping_provider_name": "UPS",
      "tracking": [
        {
          "description": "Package has been delivered!",
          "update_time_millis": 1763954669267
        },
        {
          "description": "Arrived at the carrier's facility.",
          "update_time_millis": 1763954598311
        }
      ]
    }
  ]
}
```

### plaintext

#### **Example**

YAML

Word Wrap

```
Logistics card shared:  
- Order ID: 123;  
- Package ID: 12345;  
- Number of items: 1;   
- Order payment amount: Rp63.852;   
- Delivery option: Economy shipping  
- J&T Express: JX7053814071;   
- Estimated delivery date: Jan 17, 2026 3:24 PM - Mar 18, 2026 6:18 PM GMT+08:00;   
- Logistics latest update:   
  Jan 30, 2026 2:35 AM  
  Your package has left the sorting center in Tangerang City.  
  Jan 30, 2026 1:48 AM  
  Your package has arrived at the sorting center in Tangerang City.  
  Jan 29, 2026 9:03 PM  
  Your package has been collected by our carrier in Tangerang Regency.  
  Jan 29, 2026 1:34 PM  
  The seller is preparing your package, and will hand it over to our carrier for shipping..  
  
View full details at: https://abc.com/
```

## \[Others\]

OTHER / NOTIFICATION / ALLOCATED\_SERVICE / BUYER\_ENTER\_FROM\_TRANSFER

### content

#### **Properties**

| Properties | Type | Description |
| --- | --- | --- |
| 
content

 | 

string

 | 

Text content

 |

#### **Example**

JSON

Word Wrap

```
{
  "content": "...."
}
```
