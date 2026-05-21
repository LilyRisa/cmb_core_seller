# API calls

> Source: https://open.shopee.com/developer-guide/16
> Category: 
> Scraped: 2026-05-20T20:37:03.418Z

---

⚠️ Note: This guide only applies to making API calls for Shopee Open API v2.0.

# API domains

  

There are 3 domains available:

-   Production environment

-   [https://openplatform.shopee.cn/](https://openplatform.shopee.cn/) — for developers who deployed their services near Chinese Mainland.
-   [https://openplatform.shopee.com.br/](https://openplatform.shopee.com.br/) — for developer who deployed their services near US.
-   [https://partner.shopeemobile.com/](https://partner.shopeemobile.com/)  — for developer who deployed their services near SG.

-   Sandbox environment

-   [https://openplatform.sandbox.test-stable.shopee.sg/](https://openplatform.sandbox.test-stable.shopee.sg) — for All developers
-   [https://openplatform.sandbox.test-stable.shopee.cn/](https://openplatform.sandbox.test-stable.shopee.cn/) —for Chinese Mainland developers

  

Select the correct domain name, based on the server location you’re accessing the Open API from.

# API request methods

  

Currently, Open API only provides two request methods: GET and POST.

  

# API Protocol

HTTP/JSON for most APIs. HTTP/FORM for some certain APIs, for example, the API for uploading files.

  

# API request parameters

  

In the API document, you will see two types of request parameters:

1.  Common parameter
2.  Request parameter

  

For GET\-type APIs, these two parameters may exist at the same time, or only the common parameter will exist.

  

For POST\-type APIs, these two parameters will exist at the same time.

  

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=sPZ86F%2FKQhIBn25dCIy5b3nU7xjEI9wxHYIbtVKfV5i2hclNI7gNZVnVE1%2FBfzEmL0ZPkUaVxRni%2BDzi%2FKEfdQ%3D%3D&image_type=png)

  

The table below consists of descriptions for common parameters:

| Parameters | Description |
| --- | --- |
| partner\_id | All API calls require a partner ID. You can obtain a partner ID by creating an App on the [Shopee Open Platform Console App list page](https://open.shopee.com/myconsole/management/app?from=header). Test partner ID can only be used in the test environment, and Live partner ID can only be used in the production environment. |
| timestamp | All API calls require [timestamps](https://www.computerhope.com/jargon/t/timestam.htm). Example of a timestamp: 1610000000. Each API request needs to be requested within 5 minutes of a timestamp. |
| sign | All API calls require signatures that are generated using the SHA256 algorithm. Different API types have different signature generation methods. For more details, please refer to the Signature Calculation section in this article. |
| access\_token | Access tokens are required to obtain and modify seller data-related APIs. Each access token is valid for 4 hours and can be reused within the validity period. The access token needs to be refreshed regularly. Refer to the [Authorization and Authentication](https://open.shopee.com/developer-guide/20) article to learn more about obtaining and refreshing access tokens. |
| shop\_id | The unique identification ID of a Shopee shop can be obtained after the shop has granted authorization. Refer to the [Authorization and Authentication](https://open.shopee.com/developer-guide/20) article on how to obtain your shop ID. |
| merchant\_id | The unique identification ID of a Shopee merchant, which can be obtained after a shop has granted authorization. Open API only supports cross-border sellers using a merchant ID. Refer to the [Authorization and Authentication](https://open.shopee.com/developer-guide/20) article on how to acquire your merchant ID. |

# Types of Open API

  

In the API document, there are 3 API types due to the different common parameters. The public parameters contained in these 3 types are as follows:

-   Shop API: partner\_id, timestamp, sign, access\_token, shop\_id
-   Merchant API: partner\_id, timestamp, sign, access\_token, merchant\_id
-   Public API: partner\_id, timestamp, sign

  

⚠️ Note: Red items are highlighted to indicate that they are different from Public API.

  

The Public API does not require an access token, while both the Shop and Merchant APIs do.

This means that Shop and Merchant APIs can be called only after the shop has granted authorization.

  

Currently, only Shopee cross-border merchants need to use Merchant API. Local sellers do not need to use it.

# Signature calculation

  

Step 1: Create a base string:

Different types of APIs contain different elements in its base string. Concatenate the API path (without host) and the following common parameters into a single string. You should strictly follow the sequence (base string) as shown below:

  

\*API path example：/api/v2/auth/token/get

  

Shop API: partner\_id, api path, timestamp, access\_token, shop\_id

\*Example：

partner\_id: 2001887

API path: /api/v2/shop/get\_shop\_info

timestamp: 1655714431

access\_token: 59777174636562737266615546704c6d

shop id: 14701711

  

Base string=2001887/api/v2/shop/get\_shop\_info165571443159777174636562737266615546704c6d14701711

  

Merchant API: partner\_id, api path, timestamp, access\_token, merchant\_id

\*Example:

partner\_id: 2001887

API path: /api/v2/global\_product/get\_category

timestamp: 1655714431

access\_token: 09777174636962737266615546704c6d

merchant\_id: 1000000

  

Base string=2001887/api/v2/global\_product/get\_category165571443109777174636962737266615546704c6d1000000

  

Public API: partner\_id, api path, timestamp

\*Example:

partner\_id:2001887

API path: /api/v2/public/get\_shops\_by\_partner

timestamp:1655714431

  

Base string=2001887/api/v2/public/get\_shops\_by\_partner1655714431

  

Step 2: Calculate the signature using the HMAC-SHA256 algorithm

Calculate the signature by using the HMAC-SHA256 hashing algorithm on the base string and partner Key (obtained through the [Shopee Open Platform Console App detail page](https://open.shopee.com/myconsole/management/app?from=header)). The output of the hash function is a hex-encoded string.

  

\*Example：

sign=56f31d01aeda9d08bf456b37f6f6640ef8614b4d6ad49baafe30b39a061f0e26

  

Find out why you may encounter the [“Wrong sign” error](https://open.shopee.com/faq?top=162&sub=166&page=1&faq=188) and how you can resolve the issue.

  

Sample of Code:

Python

```
#!/usr/bin/envpython

# encoding:utf-8

import hmac 

import time

import requests

import hashlib

timest=int(time.time())

host="https://partner.shopeemobile.com 

access_token = "random string"

partner id =80001

partner key = "test....."

#### call shop level api 

shop id =209920

base string ="%s%s%s%s%s"%(partner id, path timest access token, shop id) 

sign = hmac.new( partner key,base string,hashlib.sha256)hexdigest() 

path ="/api/v2/example/shop level/get"

url = host + path + "?partner_id=%s&shop_id=%s&timestamp=%s&access_token=%s&sign=%s"%(partner_id, shop_id, timest, access_token, sign) 

headers={"Content-Type":"application/ison"? 

resp=requests.post(urlheaders=headers)

#### call merchant level api 

merchant id =1234567

base string ="%s%s%s%s%s"%(partner id, path timest, access token, merchant id) 

sign =hmac.new( partner key,base string,hashlib.sha256).hexdigest() 

path ="/api/v2/example/merchant_level/get"

url = host+ path +"?partner id=%s&merchant id=%s&timestamp=%s&access token=%s&sign=%s"%(partnerid, merchant id, timest, access token, sign) 

headers ={"Content-Type":"application/ison"? resp =requests.eet(urlheaders=headers)

#### call public api

base string ="%s%s%5%s"%(partner idpathtimestaccess token)

sign= hmac.new( partner keybase stringhashlib.sha256)hexdigest() 

path ="/api/v2/auth merchant/access token/get"

url = host+path+"?partner id=%s&timestamp=%s&sign=%s%(partner idtimest, sign)

body ={"partner id":partner id, "merchant id": merchant id,"refresh token":"testingtoken") 

headers =["Content-Type":"application/ison"?

resp =requests.post(url,json=bodyheaders=headers)
```

# API request sample

  

For GET\-type API requests, you need to put both the common parameters and request parameters in the URL.

  

For example: [V2.product.get\_category](https://open.shopee.com/documents/v2/v2.product.get_category?module=89&type=1)

  

API request URL:

https://partner.shopeemobile.com/api/v2/product/get\_category?partner\_id=851249&timestamp=1654673582&shop\_id=1001094&access\_token=367a0a8eb9d1837cbf7c43b587a0faa4&sign=a40fc50a08c382eeee08e2eb00deb8464c6fdcbe4f1c271e033cdbca3ded4d5b&language=zh-hans

  

\*For this API endpoint，partner\_id、timestamp、access\_token、shop\_id and sign are common parameters. Meanwhile, language is a request parameter.

  

  

For POST\-type API requests, you need to insert common parameters in the request URL and request parameters in the request body.

  

For example:[v2.shop.update\_profile](https://open.shopee.com/documents/v2/v2.shop.update_profile?module=92&type=1)

  

API request URL:

https://partner.shopeemobile.com/api/v2/shop/update\_profile?partner\_id=851249&timestamp=1654673582&shop\_id=1001094&access\_token=367a0a8eb9d1837cbf7c43b587a0faa4&sign=80cbce8da907d5a1237711409920fc16908a9f9e01b1254ff9cc44aaf0836122

  

Request body:

{

"shop\_logo": "https://cf.shopee.sg/file/8424390be4677b0b3c37ce6499ce261a",

"description": "TTest",

"shop\_name": "123"

}

  

\*For this API endpoint，partner\_id、timestamp、access\_token、shop\_id、and sign are common parameters. Meanwhile, shop\_logo、description and shop\_name are request parameters.

# API response parameters

| Parameters | Must return？ | Description |
| --- | --- | --- |
| request id | Yes | Each API request has a unique request ID. When you encounter an API issue, please provide this ID and the corresponding API information to get a faster response. |
| error | Yes | Error code. When the request is successful, the error parameter will return empty. If the request fails, the corresponding error code will be reflected in this field. |
| message | No | Error message. When the request is successful, the message will return empty. If the request fails, this field will contain detailed information about the error. |
| warning | No | If the API call is successful, but some data is not returned or some batch requests fail, the information will be reflected in this field. |
| response | No | When the request is successful, the corresponding data will be reflected in this field. |

# API functions

| API module | Function Description |
| --- | --- |
| [Product](https://open.shopee.com/documents/v2/v2.product.get_category?module=89&type=1) | You can obtain the following information:
-   Product-related category tree
-   Attribute and brand information
-   Shop product data
-   Product promotion information
-   Boost item and boost item list
-   Product reviews and review list
-   Product recommended categories and recommended attributes
-   Registered product brands

  
Perform the following actions:

-   Create, delete and update product information.

 |
| [Shop](https://open.shopee.com/documents/v2/v2.shop.get_shop_info?module=92&type=1) | You can obtain the following information:

-   Shop name
-   Market shop is based in
-   Shop type

  
Perform the following actions:

-   Update shop information

 |
| [Merchant](https://open.shopee.com/documents/v2/v2.merchant.get_merchant_info?module=93&type=1) | \*This module is only required by cross-border sellersGet merchant information (merchant name/market/currency) and a list of all shops under the merchant that have granted authorization. |
| [GlobalProduct](https://open.shopee.com/documents/v2/v2.global_product.get_category?module=90&type=1) | \*This module is only required by cross-border sellersObtain the following information:

-   Related category tree
-   Attribute and brand information
-   Global product data
-   List of products that can be listed in certain markets only
-   List of products with global product already published
-   List of products already listed in specific markets
-   Global product ID corresponding to the product in a specific market
-   Recommended category and attribute of a global product

  
Perform the following actions:

-   Create, delete and update global products.
-   Enable/Disable syncing of global product information.
-   Publish global product

 |
| [MediaSpace](https://open.shopee.com/documents/v2/v2.media_space.init_video_upload?module=91&type=1) | Upload videos and images |
| [Order](https://open.shopee.com/documents/v2/v2.order.get_order_list?module=94&type=1) | Obtain the following information:

-   Shop order list
-   Order list and order details
-   List of invoice orders to be uploaded
-   Invoice information

  
Perform the following actions:

-   Manage orders by splitting and canceling them
-   Undo split orders
-   Process sellers’ order cancellation application
-   Add order remarks
-   Upload and download invoices to retrieve invoice information

 |
| [Logistics](https://open.shopee.com/documents/v2/v2.logistics.get_shipping_parameter?module=95&type=1) | Obtain the following information:

-   List of shop channel
-   Shipping parameters
-   Order tracking number and tracking information
-   Shipping document formats and shipping documents
-   List of shop addresses

  
Perform the following actions:

-   Ship orders/Ship orders in bulk
-   Update shop address flag
-   Enable/Disable shop channel
-   Delete shop addresses

 |
| [FirstMile](https://open.shopee.com/documents/v2/v2.first_mile.get_unbind_order_list?module=96&type=1) | \*This module is only required by cross-border sellersObtain the following information:

-   Unbound First Mile orders
-   First Mile tracking numbers and details
-   First Mile order details
-   First Mile shipping documents
-   FirstMile channel details

 |
| [Returns](https://open.shopee.com/documents/v2/v2.returns.get_return_detail?module=102&type=1) | Obtain the following information:

-   List of return and refund requests
-   Return and refund request details
-   Get a return and refund plan

  
Perform the following actions:

-   Confirm refunds
-   Submit disputes
-   Negotiate refunds
-   Upload image evidence for disputes

 |
| [Payment](https://open.shopee.com/documents/v2/v2.payment.get_escrow_detail?module=97&type=1) | Obtain the following information:

-   Order income
-   Payout data
-   Wallet data
-   List of completed orders
-   Installment shop settings
-   List of products that have been set up for installment payment

 |
| [Discount](https://open.shopee.com/documents/v2/v2.discount.add_discount?module=99&type=1) | Create, view, update, and delete Discount Promotions |
| [Bundle Deal](https://open.shopee.com/documents/v2/v2.bundle_deal.add_bundle_deal?module=110&type=1) | Create, view, update, and delete Bundle Deals |
| [Add-On Deal](https://open.shopee.com/documents/v2/v2.add_on_deal.add_add_on_deal?module=111&type=1) | Create, view, update, and delete Add-on Deals |
| [Voucher](https://open.shopee.com/documents/v2/v2.voucher.add_voucher?module=112&type=1) | Create, view, update, and delete Vouchers |
| [Follow Prize](https://open.shopee.com/documents/v2/v2.follow_prize.add_follow_prize?module=113&type=1) | Create, view, update, and delete Follow Prizes |
| [TopPicks](https://open.shopee.com/documents/v2/v2.top_picks.get_top_picks_list?module=100&type=1) | Create, view, update, and delete Top Picks |
| [ShopCategory](https://open.shopee.com/documents/v2/v2.shop_category.add_shop_category?module=101&type=1) | Create, view, update, and delete Shop Categories |
| [AccountHealth](https://open.shopee.com/documents/v2/v2.account_health.get_shop_performance?module=103&type=1) | Obtain shop performance and any shop penalty points data |
| [Public](https://open.shopee.com/documents/v2/v2.public.get_shops_by_partner?module=104&type=1) | Perform the following actions:

-   Obtain list of shops that have granted authorization
-   Obtain list of merchants that have granted authorization
-   Resend code and upgrade codes to retrieve tokens
-   Retrieve and refresh tokens

 |
| [Push](https://open.shopee.com/documents/v2/v2.push.get_push_config?module=105&type=1) | Retrieve and update Push Mechanism settings |
| [Chat](https://open.shopee.com/documents/v2/v2.sellerchat.get_message?module=109&type=1) | \*This module is only open to whitelisted users. To apply to join the whitelist, you may refer to this [FAQ](https://open.shopee.com/faq?top=162&sub=166&page=1&faq=243).  
Obtain the following information:

-   Chat list
-   Chat details
-   Chat information
-   Obtain Make Offer settings

  
Perform the following actions:

-   Delete chats
-   Mark chats as unread
-   Pin and unpin chats
-   Upload images to chats
-   Send manual and automatic chat messages
-   Enable/disable Make Offer settings

 |

# Problems with API

If you encounter problems during the API integration process, you may refer to the [FAQ](https://open.shopee.com/faq?top=162&sub=169&page=1&faq=) page for help. If your problem persists, you can [raise a ticket](https://open.shopee.com/myconsole/ticket-system/raise-ticket).
