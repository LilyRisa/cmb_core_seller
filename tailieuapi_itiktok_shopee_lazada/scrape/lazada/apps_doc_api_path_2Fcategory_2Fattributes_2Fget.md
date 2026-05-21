# GETGetCategoryAttributes

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcategory%2Fattributes%2Fget
> Scraped: 2026-05-20T22:49:16.632Z

---

Latest update2022-07-28 16:51:54

25241

GetCategoryAttributes

GET

/category/attributes/get

No Authorization Required

Description:Use this API to get a list of attributes for a specified product category.

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

No

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

primary\_category\_id

 | 

String

 | 

Yes

 | 

identifiers of category code

 |
| 

language\_code

 | 

String

 | 

No

 | 

Language code indicates the type of language you would like to translate. Please note not all languages are available in every region. For example, in Indonesia, only English and Indonesia are available. If you are passing a language code which does not belong to your area, null value might receive. Please do make sure your language code is correct. Supported language codes are listed as below: English:"en\_US" - available in every area Singapore:"en\_SG" - available in Singapore Thailand"th\_TH" - available in Thailand Indonesia:"id\_ID" - available in Indonesia Vietnam:"vi\_VN" - available in Vietnam Philippines: "fil\_PH" - available in Philippines Malaysia : "ms\_MY" - available in Malaysia Default(if null is passed): "en\_US"

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

data

 | 

Object\[\]

 | 

Response body

 |
| 

advanced

 | 

Object

 | 

When the attribute is key attribute, is\_key\_prop = 1. When the attribute is not key attribute,  is\_key\_prop = 0.

 |
| 

label

 | 

String

 | 

Human-readable display name of the attribute

 |
| 

name

 | 

String

 | 

Name of the attribute

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

en\_name

 | 

String

 | 

Option name in English

 |
| 

id

 | 

Number

 | 

options id

 |
| 

is\_sale\_prop

 | 

Number

 | 

Whether the attribute is sale property

 |
| 

id

 | 

Number

 | 

attribute id

 |
| 

unit

 | 

Object

 | 

{ "precision": 1, "type": \[ "inch", "cm" \], "numericMin": "1", "numericMax": "999" }

 |
| 

type

 | 

String\[\]

 | 

\[ "inch", "cm" \]

 |
| 

numericMin

 | 

String

 | 

1

 |
| 

numericMax

 | 

String

 | 

999

 |
| 

precision

 | 

Number

 | 

0

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

57

 | 

E057: No attribute sets linked to that category.

 | 

No attributes are linked to the specified category.

 |
| 

4228

 | 

Query category is not active

 | 

The category ID in the request is in Inactive state and cannot be used. Please call GetCategoryTree to query the latest category.

 |
| 

4227

 | 

Query category is null

 | 

The category ID in the request does not exist in the current country, call the GetCategoryTree API to query the latest category list.

 |
| 

4227

 | 

Query category is null

 | 

The category ID in the request does not exist in the current country, call the GetCategoryTree API to query the latest category list.

 |
| 

4228

 | 

Query category is not active

 | 

The category ID in the request is in Inactive state and cannot be used. Please call GetCategoryTree to query the latest category.

 |
| 

4228

 | 

Query category is not active

 | 

The category ID in the request is in Inactive state and cannot be used. Please call GetCategoryTree to query the latest category.

 |
| 

4227

 | 

Query category is null

 | 

The category ID in the request does not exist in the current country, call the GetCategoryTree API to query the latest category list.

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/category/attributes/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/category/attributes/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/category/attributes/get");
request.setHttpMethod("GET");
request.addApiParameter("primary_category_id", "8704");
request.addApiParameter("language_code", "en_US");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": [
    {
      "unit": {
        "precision": "precision",
        "type": [],
        "numericMin": "numericMin",
        "numericMax": "numericMax"
      },
      "advanced": {
        "is_key_prop": 1
      },
      "is_sale_prop": "0",
      "name": "mattress_size",
      "input_type": "singleSelect",
      "options": [
        {
          "name": "Twin",
          "en_name": "Twin ",
          "id": "0"
        }
      ],
      "is_mandatory": "1",
      "attribute_type": "normal",
      "label": "Size",
      "id": "0"
    }
  ],
  "request_id": "0ba2887315178178017221014"
}
```

Please rate this article

Popular Articles
