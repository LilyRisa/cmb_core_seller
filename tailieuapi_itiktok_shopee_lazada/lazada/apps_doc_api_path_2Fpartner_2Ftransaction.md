# GET/POSTPartnerTransaction

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpartner%2Ftransaction
> API path: /partner/transaction
> Category: Membership API
> Scraped: 2026-05-20T23:33:48.011Z

---

Latest update2022-10-09 16:23:51

3444

PartnerTransaction

GET/POST

/partner/transaction

Authorization Required

Description:Using this interface, you can obtain the seller's transaction order based on the conditions, and also contain the membership information

## Service Endpoints

| Region | Endpoint |
| --- | --- |
| Vietnam | https://api.lazada.vn/rest |
| Singapore | https://api.lazada.sg/rest |
| Philippines | https://api.lazada.com.ph/rest |
| Malaysia | https://api.lazada.com.my/rest |
| Thailand | https://api.lazada.co.th/rest |
| Indonesia | https://api.lazada.co.id/rest |
## Common Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| app\_key | String | Yes | Unique app ID issued by LAZADA Open Platform console when you apply for an app category |
| timestamp | String | Yes | The time stamp of the request e.g. 1517820392000 (which translates to 5 February 2018 08:46:32) with less than 7200s difference from UTC time |
| access\_token | String | Yes | API interface call credentials |
| sign\_method | String | Yes | The HMAC hash algorithm you are using to calculate your signature |
| sign | String | Yes | Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details) |
## Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| status | String | No | When set, limits the returned set of orders to loose orders, which return only entries which fit the status provided. Possible values are unpaid, pending, canceled, ready\_to\_ship, delivered, returned, shipped , failed, topack,toship,shipping and lost |
| update\_before | String | No | Limits the returned orders to those updated before or on the specified date, given in ISO 8601 date format. Optional. |
| sort\_direction | String | No | Specify the sorting type. Possible values are ASC and DESC. |
| offset | Number | No | Number of orders to skip at the beginning of the list. |
| limit | Number | No | The maximum number of orders that can be returned. The supported maximum number is 100. |
| update\_after | String | No | Limits the returned orders to those updated after or on the specified date, given in ISO 8601 date format. Either UpdatedAfter or CreatedAfter is mandatory. |
| sort\_by | String | No | Allows to choose the sorting column. Possible values are created\_at and updated\_at. |
| created\_before | String | No | Limits the returned orders to those updated before or on the specified date, given in ISO 8601 date format. Optional. |
| created\_after | String | No | Limits the returned orders to those updated after or on the specified date, given in ISO 8601 date format. Either UpdatedAfter or CreatedAfter is mandatory. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| model\_list | Object\[\] | data list |
| shipping\_fee\_original | String | the original shipping fee which are supposed to be charged to the customer, before any type of shipping fee promotion |
| shipping\_fee\_discount\_seller | String | shipping fee discount from seller |
| shipping\_fee\_discount\_platform | String | shipping fee discount from platform |
| address\_shipping | Object | Node that contains additional nodes, which makes up the shipping address: FirstName, LastName, Phone, Phone2, Address1, Address2, City, PostCode, and Country. |
| address1 | String | Detailed address |
| phone2 | String | Backup phone number |
| first\_name | String | Customer first name |
| phone | String | Phone number |
| address5 | String | Third-level address |
| post\_code | String | Post code; Note: This value will not be used in Lazada ID |
| address4 | String | City name |
| last\_name | String | Customer last name |
| country | String | country |
| address3 | String | State name |
| address2 | String | Not used for now |
| city | String | City name |
| customer\_last\_name | String | Empty for now. See cutomer\_first\_name. |
| gift\_option | String | 1 if item is a gift, and 0 if it is not. |
| voucher\_code | String | The returned value is Voucher id |
| updated\_at | String | Date and time of the last change to the order. |
| delivery\_info | String | Delivery information |
| gift\_message | String | Gift message as specified by the customer. |
| member\_sub\_order\_list | Object\[\] | the membership info and subOrder info |
| buyer\_id | Number | buyer id |
| partner\_user\_id | String | partnerUser id |
| seller\_id | String | seller id |
| pick\_up\_store\_info | Object | Pick-up Store infos |
| pick\_up\_store\_name | String | Pick-up Store's name |
| pick\_up\_store\_address | String | Pick-up Store's address |
| pick\_up\_store\_code | String | Pick-up Store's id |
| pick\_up\_store\_open\_hour | String\[\] | Pick-up Store's business hours |
| purchase\_order\_number | String | Returned when calling SetPackedByMarketPlace |
| name | String | Product name |
| product\_main\_image | String | Product main image URL |
| item\_price | String | Product price |
| tax\_amount | String | Tax amount |
| status | String | canceled |
| cancel\_return\_initiator | String | cancellation-customer |
| voucher\_platform | String | The voucher that is issued by Lazada |
| voucher\_seller | String | The voucher that is issued by the seller |
| order\_type | String | The type of order，maybe Normal, PreSale, Coupon, O2O or InStoreO2O |
| stage\_pay\_status | String | The payment status of Presale order at presale stage. The possible values are null, "unpaid" or "unpaid final payment". (unpaid: presale deposit has not been paid; unpaid final payment: presale deposit is paid but final payment / balance due is not paid) |
| warehouse\_code | String | Warehouse Code of multi-wh sellers |
| voucher\_seller\_lpi | String | The Lazada Bonus that is sponsored by the seller |
| voucher\_platform\_lpi | String | The Lazada Bonus that is sponsored by Lazada |
| shipping\_fee\_original | String | shipping fee original |
| shipping\_fee\_discount\_seller | String | shipping fee discount from seller |
| shipping\_fee\_discount\_platform | String | shipping fee discount from platform |
| voucher\_code\_seller | String | voucher code from seller |
| voucher\_code\_platform | String | voucher code from platform |
| is\_fbl | Number | The mark of whether is fulfilled by LAZADA, values included 1 and 0. |
| is\_reroute | Number | The mark of whether is secondary sale, values included 1 and 0. |
| reason | String | Cancel, Return or other reason, defined in the table sales\_order\_reason |
| digital\_delivery\_info | String | Digital deliery information |
| promised\_shipping\_time | String | Promised shipping time |
| order\_id | Number | order id |
| voucher\_amount | String | Voucher amount |
| return\_status | String | Return status |
| shipping\_type | String | Shipping type, Drop-shipping or Warehouse |
| shipment\_provider | String | 3PL shipment provider, such as LEX |
| variation | String | variation |
| created\_at | String | Time of the feed's creation in ISO 8601 format |
| invoice\_number | String | Invoice number |
| shipping\_amount | String | Shipping fee |
| currency | String | SGD |
| order\_flag | String | The type of order, Possible values are GUARANTEE, NORMAL and GLOBAL\_COLLECTION. Orders tagged with "GUARANTEE" or "GLOBAL\_COLLECTION" have shorter SLA requirement in order fulfillment. |
| shop\_id | String | dawen dp |
| sla\_time\_stamp | String | Time of the ship SLA in ISO 8601 format(yyyy-MM-dd'T'HH:mm:ssXXX) |
| sku | String | Product SKU |
| voucher\_code | String | Not used |
| wallet\_credits | String | Wallet credit |
| updated\_at | String | Time of the feed's last update in ISO 8601 format |
| is\_digital | Number | Is digital goods or not |
| tracking\_code\_pre | String | Not used |
| order\_item\_id | Number | Order item ID |
| package\_id | String | Package source ID |
| tracking\_code | String | Tracking code retrieved from 3PL shipment provider |
| shipping\_service\_cost | String | Shipping service cost |
| extra\_attributes | String | JSON encoded string with extra attributes |
| paid\_price | String | Paid price |
| shipping\_provider\_type | String | One of the following options: EXPRESS, STANDARD, ECONOMY, INSTANT, SELLER\_OWN\_FLEET, PICKUP\_IN\_STORE or DIGITAL |
| product\_detail\_url | String | Product detail URL |
| shop\_sku | String | Product outer ID |
| reason\_detail | String | Reason detail |
| purchase\_order\_id | String | Returned when calling SetPackedByMarketPlace |
| sku\_id | String | Sku Id |
| product\_id | String | Product ID |
| branch\_number | String | (For Thailand only) The tax branch code for corporate customers, provided by the customer when placing the order. |
| tax\_code | String | (For Thailand and Vietnam only) The customer's VAT tax code, provided by the customer when placing the order. |
| extra\_attributes | String | Extra attributes which were passed to the Seller Center on getMarketPlaceOrders call. |
| address\_updated\_at | String | Address updated at |
| shipping\_fee | String | Total shipping fee for this order. |
| customer\_first\_name | String | Customer first name |
| payment\_method | String | The method of the payment. |
| statuses | String\[\] | An array of unique status of the items in the order. You can find all of the different status codes in the response example. |
| remarks | String | Remarks |
| order\_number | Number | Represents the order ID |
| order\_id | Number | Represents the order ID |
| voucher | String | Total voucher for this order. |
| national\_registration\_number | String | National registration number. Required in some countries. |
| promised\_shipping\_times | String | Target shipping time for the soonest order item if they are available. |
| items\_count | Number | Number of items in order. |
| voucher\_platform | String | The voucher that is issued by Lazada |
| voucher\_seller | String | The voucher that is issued by the seller |
| created\_at | String | Date and time when the order was placed. |
| price | String | Total amount for this order.Not the final transaction price of the order, excluding voucher and shipping\_fee |
| address\_billing | Object | Node that contains additional nodes, which makes up the billing address: FirstName, LastName, Phone, Phone2, Address1, Address2, City, PostCode, and Country. |
| address1 | String | Detailed address |
| phone2 | String | Backup phone number |
| first\_name | String | Customer first name |
| phone | String | Phone number |
| address5 | String | Third-level address |
| post\_code | String | Post code; Note: This value will not be used in Lazada ID |
| address4 | String | City name |
| last\_name | String | Customer last name |
| country | String | country |
| address3 | String | State name |
| address2 | String | Not used for now |
| city | String | City name |
| warehouse\_code | String | Warehouse Code of multi-wh sellers |
| total\_count | Number | total count |
| page\_no | Number | page num |
| page\_size | Number | page size |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/partner/transaction)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/partner/transaction

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/partner/transaction");
request.addApiParameter("status", "delivered");
request.addApiParameter("update_before", "2022-10-10T16:00:00+08:00");
request.addApiParameter("sort_direction", "desc");
request.addApiParameter("offset", "0");
request.addApiParameter("limit", "100");
request.addApiParameter("update_after", "2022-10-10T16:00:00+08:00");
request.addApiParameter("sort_by", "updated_at");
request.addApiParameter("created_before", "2022-10-10T16:00:00+08:00");
request.addApiParameter("created_after", "2022-10-10T16:00:00+08:00");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "model_list": [
      {
        "voucher_platform": "0.00",
        "voucher": "0.00",
        "warehouse_code": "dropshipping",
        "order_number": "491253082180001",
        "voucher_seller": "0.00",
        "created_at": "2022-09-28 14:41:18",
        "voucher_code": "1234",
        "gift_option": "false",
        "shipping_fee_discount_platform": "0.00",
        "customer_last_name": "last_name",
        "updated_at": "2018-02-09T22:44:30+08:00",
        "promised_shipping_times": "shipping_time",
        "price": "106.00",
        "national_registration_number": "1",
        "shipping_fee_original": "0.00",
        "payment_method": "COD",
        "address_updated_at": "2022-09-28 22:48:28",
        "customer_first_name": "Ha Hung",
        "member_sub_order_list": [
          {
            "pick_up_store_info": {
              "pick_up_store_address": "Ali Center, Shenzhen",
              "pick_up_store_name": "Alibaba",
              "pick_up_store_open_hour": [
                "[\"Sunday 9:00-18:00\", \"Mondday,Tuesday,Wendnesday,Thursday,Friday 8:00-20:00\"]",
                "[\"Sunday 9:00-18:00\", \"Mondday,Tuesday,Wendnesday,Thursday,Friday 8:00-20:00\"]"
              ],
              "pick_up_store_code": "d4b04804-9192-4a8c-8ed1-5ebcd7d3c067"
            },
            "tax_amount": "6.48",
            "reason": "reason",
            "sla_time_stamp": "2019-06-24T23:59:59+08:00",
            "voucher_seller": "0.00",
            "purchase_order_id": "3454",
            "voucher_code_seller": "X234",
            "voucher_code": "X3453",
            "package_id": "345",
            "buyer_id": "1001",
            "variation": "1",
            "product_id": "12345",
            "voucher_code_platform": "Y123",
            "purchase_order_number": "345345",
            "sku": "BRSD#02",
            "order_type": "Normal",
            "invoice_number": "1342",
            "seller_id": "1001111",
            "cancel_return_initiator": "Indicates who initiated the canceled or returned order. Possible values are cancellation-internal, cancellation-customer, cancellation-failed Delivery, cancellation-seller, return-customer, and refund-internal.",
            "shop_sku": "BE494HLAAUE3SGAMZ-39898",
            "is_reroute": "0",
            "stage_pay_status": "unpaid",
            "sku_id": "666",
            "tracking_code_pre": "23534",
            "order_item_id": "98108",
            "shop_id": "Seller name",
            "order_flag": "GUATANTEE",
            "is_fbl": "0",
            "name": "Bean Rester Dooby Red",
            "order_id": "31202",
            "status": "status",
            "product_main_image": "Product main image URL",
            "voucher_platform": "0.00",
            "paid_price": "99.00",
            "product_detail_url": "http://www.lazada.co.th/535590.html",
            "warehouse_code": "WH-01",
            "promised_shipping_time": "2014-10-15 19:12:15 +0800",
            "shipping_type": "Dropshipping",
            "created_at": "2014-10-15 19:12:15 +0800",
            "voucher_seller_lpi": "0.00",
            "shipping_fee_discount_platform": "0.00",
            "wallet_credits": "0.00",
            "updated_at": "2014-10-15 19:12:15 +0800",
            "currency": "ISO 4217 compatible currency code",
            "shipping_provider_type": "standard",
            "voucher_platform_lpi": "0.00",
            "shipping_fee_original": "0.00",
            "item_price": "Product price",
            "is_digital": "0",
            "shipping_service_cost": "0",
            "tracking_code": "456",
            "shipping_fee_discount_seller": "0.00",
            "shipping_amount": "0.00",
            "reason_detail": "reason detail",
            "return_status": "1",
            "partner_user_id": "LorealLANSG-B",
            "shipment_provider": "LEL",
            "voucher_amount": "0.00",
            "digital_delivery_info": "delivery",
            "extra_attributes": "null"
          }
        ],
        "shipping_fee_discount_seller": "0.00",
        "shipping_fee": "0.54",
        "branch_number": "2222",
        "tax_code": "562562",
        "items_count": "1",
        "delivery_info": "delivery",
        "statuses": [
          "unpaid、pending、repacked、packed、ready_to_ship_pending、ready_to_ship、shipping、delivered、lost、failed、canceled、returned、damaged_by_3pl、lost_by_3pl",
          "unpaid、pending、repacked、packed、ready_to_ship_pending、ready_to_ship、shipping、delivered、lost、failed、canceled、returned、damaged_by_3pl、lost_by_3pl"
        ],
        "address_billing": {
          "country": "Singapore",
          "address3": "address3",
          "phone": "4***457",
          "address2": "address2",
          "city": "Singapore-Singapore-500001",
          "address1": "1 CHANGI VILLAGE ROAD, 11",
          "post_code": "500001",
          "phone2": "4***456",
          "last_name": "last_name",
          "address5": "address5",
          "address4": "address4",
          "first_name": "Ha Hung"
        },
        "extra_attributes": "null",
        "order_id": "491253082180001",
        "gift_message": "1",
        "remarks": "remarks",
        "address_shipping": {
          "country": "Singapore",
          "address3": "address3",
          "phone": "4***457",
          "address2": "address2",
          "city": "Singapore-Singapore-500001",
          "address1": "1 CHANGI VILLAGE ROAD, 11",
          "post_code": "500001",
          "phone2": "4***456",
          "last_name": "last_name",
          "address5": "address5",
          "address4": "address4",
          "first_name": "Ha Hung"
        }
      }
    ],
    "total_count": "2289",
    "page_no": "1",
    "page_size": "100"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
