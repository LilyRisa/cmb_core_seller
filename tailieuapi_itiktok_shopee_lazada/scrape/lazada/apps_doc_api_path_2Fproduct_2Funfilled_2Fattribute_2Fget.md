# GET/POSTGetUnfilledAttributeItem

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Funfilled%2Fattribute%2Fget
> Scraped: 2026-05-20T22:51:24.319Z

---

Latest update2022-07-28 17:05:45

4784

GetUnfilledAttributeItem

GET/POST

/product/unfilled/attribute/get

Authorization Required

Description:Get products without key attributes. (For cross boarder sellers Only)

## Service Endpoints

| 
Region

 | 

Endpoint

 |
| --- | --- |
| 

Vietnam

 | 

https://api.lazada.vn/rest

 |
| 

Singapore

 | 

https://api.lazada.sg/rest

 |
| 

Philippines

 | 

https://api.lazada.com.ph/rest

 |
| 

Malaysia

 | 

https://api.lazada.com.my/rest

 |
| 

Thailand

 | 

https://api.lazada.co.th/rest

 |
| 

Indonesia

 | 

https://api.lazada.co.id/rest

 |

Did this chapter help you?

YesNo

## Common Parameters

| 
Name

 | 

Type

 | 

Required or not

 | 

Description

 |
| --- | --- | --- | --- |
| 

app\_key

 | 

String

 | 

Yes

 | 

Unique app ID issued by LAZADA Open Platform console when you apply for an app category

 |
| 

timestamp

 | 

String

 | 

Yes

 | 

The time stamp of the request e.g. 1517820392000 (which translates to 5 February 2018 08:46:32) with less than 7200s difference from UTC time

 |
| 

access\_token

 | 

String

 | 

Yes

 | 

API interface call credentials

 |
| 

sign\_method

 | 

String

 | 

Yes

 | 

The HMAC hash algorithm you are using to calculate your signature

 |
| 

sign

 | 

String

 | 

Yes

 | 

Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details)

 |

Did this chapter help you?

YesNo

## Parameters

| 
Name

 | 

Type

 | 

Required or not

 | 

Description

 |
| --- | --- | --- | --- |
| 

page\_index

 | 

Number

 | 

Yes

 | 

page\_index

 |
| 

attribute\_tag

 | 

String

 | 

Yes

 | 

The tag of attributes. Currently only has one value "key\_prop" 属性标示。当前只支持key\_prop

 |
| 

page\_size

 | 

Number

 | 

Yes

 | 

The number of Products you would like to fetch from every response. The max number is 50. 返回的最大商品量。最大值50。商品级别

 |
| 

language\_code

 | 

String

 | 

Yes

 | 

Multi-language of category attributes that need to be returned

 |

Did this chapter help you?

YesNo

## Response Parameters

| 
Name

 | 

Type

 | 

Description

 |
| --- | --- | --- |
| 

success

 | 

Boolean

 | 

api

 |
| 

total\_products

 | 

Number

 | 

The current product volume returned. Commodity level

 |
| 

products

 | 

Object\[\]

 | 

products

 |
| 

item\_id

 | 

Number

 | 

The ID of this product

 |
| 

primary\_category

 | 

Number

 | 

The ID of the primary category for his product.

 |
| 

attributes

 | 

Object\[\]

 | 

Contains unfilled product attributes. 只返回符合查询条件的未填写的属性

 |
| 

advanced

 | 

Object

 | 

When the attribute is key attribute, is\_key\_prop = 1. When the attribute is not key attribute, is\_key\_prop = 0.

 |
| 

name

 | 

String

 | 

Human-readable display name of the attribute

 |
| 

input\_type

 | 

String

 | 

Attribute input type (text, date,numeric,img,rich text, singleSelect，multiSelect,enumInput,multiEnumInput) multiEnumInput/multiEnumInput supports custom value

 |
| 

options

 | 

Object\[\]

 | 

List of all option nodes

 |
| 

name

 | 

String

 | 

Option name

 |
| 

is\_mandatory

 | 

Number

 | 

Whether the attribute is mandatory

 |
| 

attribute\_type

 | 

String

 | 

Attribute type

 |
| 

label

 | 

String

 | 

Name of the attribute

 |
| 

seller\_sku\_id

 | 

String

 | 

One of seller SKU ID under this product. status and sub\_status of it is 1 (active)

 |
| 

error\_msg

 | 

String

 | 

error\_msg

 |

Did this chapter help you?

YesNo

## Error Code

| 
Error Code

 | 

Error Message

 | 

Solution

 |
| --- | --- | --- |
| 

No Data

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/unfilled/attribute/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/product/unfilled/attribute/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/unfilled/attribute/get");
request.addApiParameter("page_index", "1");
request.addApiParameter("attribute_tag", "key_prop");
request.addApiParameter("page_size", "50");
request.addApiParameter("language_code", "en_us");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "systemerror",
  "code": "0",
  "success": "true",
  "total_products": "100",
  "request_id": "0ba2887315178178017221014",
  "products": [
    {
      "item_id": "123",
      "primary_category": "123",
      "attributes": [
        {
          "advanced": {
            "is_key_prop": 1
          },
          "name": "Size",
          "input_type": "singleSelect",
          "options": [
            {
              "name": "Twin"
            }
          ],
          "is_mandatory": "1",
          "attribute_type": "normal",
          "label": "mattress_size"
        }
      ],
      "seller_sku_id": "Apple 6S Black"
    }
  ]
}
```

Please rate this article

Popular Articles
