# GETGetOrders

> Source: https://open.lazada.com/apps/doc/api?path=%2Forders%2Fget
> API path: /orders/get
> Category: Order API
> Scraped: 2026-05-20T23:24:15.689Z

---

Latest update2022-07-25 14:53:01

94485

GetOrders

GET

/orders/get

Authorization Required

Description:Use this API to get the list of items for a range of orders1..

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
| update\_before | String | No | Limits the returned orders to those updated before or on the specified date, given in ISO 8601 date format. Optional. |
| sort\_direction | String | No | Specify the sorting type. Possible values are ASC and DESC. |
| offset | Number | No | Number of orders to skip at the beginning of the list. |
| limit | Number | No | The maximum number of orders that can be returned. The supported maximum number is 100. |
| update\_after | String | No | Limits the returned orders to those updated after or on the specified date, given in ISO 8601 date format. Either UpdatedAfter or CreatedAfter is mandatory. |
| sort\_by | String | No | Allows to choose the sorting column. Possible values are created\_at and updated\_at. |
| created\_before | String | No | Limits the returned orders to those updated before or on the specified date, given in ISO 8601 date format. Optional. |
| created\_after | String | No | Limits the returned orders to those updated after or on the specified date, given in ISO 8601 date format. Either UpdatedAfter or CreatedAfter is mandatory. |
| status | String | No | When set, limits the returned set of orders to loose orders, which return only entries which fit the status provided. Possible values are unpaid, pending, canceled, ready\_to\_ship, delivered, returned, shipped , failed, topack,toship,shipping and lost |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Response body |
| countTotal | Number | Displayed in the Head section, this number tells the complete number of all orders for the current filter set in the database. |
| count | Number | Displayed in the Head section, this number tells the complete number of all orders for the current filter set in the database(included offset and limit). |
| orders | Object\[\] | Order details |
| branch\_number | String | (For Thailand only) The tax branch code for corporate customers, provided by the customer when placing the order. |
| tax\_code | String | (For Thailand and Vietnam only) The customer's VAT tax code, provided by the customer when placing the order. |
| extra\_attributes | String | Extra attributes which were passed to the Seller Center on getMarketPlaceOrders call. |
| address\_updated\_at | String | Address updated at |
| shipping\_fee | String | Total shipping fee for this order. |
| customer\_first\_name | String | Customer first name |
| payment\_method | String | The method of the payment. For details, see [Payment method options](https://open.lazada.com/apps/doc/doc?nodeId=29616&docId=121753). |
| statuses | String\[\] | An array of unique status of the items in the order. You can find all of the different status codes in the response example. |
| remarks | String | Remarks |
| order\_number | String | Represents the order ID |
| order\_id | String | Represents the order ID |
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
| country | String | Country |
| address3 | String | State name |
| address2 | String | Not used for now |
| city | String | City name |
| addressDsitrict | String | Dsitrict |
| warehouse\_code | String | Warehouse Code of multi-wh sellers |
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
| country | String | Country |
| address3 | String | State name |
| address2 | String | Not used for now |
| city | String | City name |
| addressDsitrict | String | Dsitrict |
| customer\_last\_name | String | Empty for now. See cutomer\_first\_name. |
| gift\_option | String | 1 if item is a gift, and 0 if it is not. |
| voucher\_code | String | The returned value is Voucher id |
| updated\_at | String | Date and time of the last change to the order. |
| delivery\_info | String | Delivery information |
| gift\_message | String | Gift message as specified by the customer. |
| buyer\_note | String | buyer note |
| recipient\_info | Object | Information filled in by the buyer when placing an order |
| passport\_no | String | passport number |
| identify\_no | String | identify card number |
| detail\_address | String | recipient address |
| need\_cancel\_confirm | Boolean | true: seller needs to respond to the cancellation request from buyer |
| is\_cancel\_pending | String | true: seller agrees the cancellation request, waiting for logistic system |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 14 | E014: "%s" Invalid Offset | The value for the offset parameter is not valid. |
| 17 | E017: "%s" Invalid Date Format | The date format is not valid. |
| 19 | E019: "%s" Invalid Limit | The value for the limit parameter is not valid. |
| 36 | E036: Invalid status filter | The specified status filter is not valid. |
| 74 | E074: Invalid sort direction. | The specified sort direction is not valid. |
| 75 | E075: Invalid sort filter. | The specified sort filter is not valid. |
| SellerNotVerified | Seller not verified,please check seller status | The seller's store opening process has not been completed, please log in to the Seller Center, check the store information that needs to be improved on the home page and submit it for review. |
| SellerNotVerified | Seller not verified,please check seller status | The seller's store opening process has not been completed, please log in to the Seller Center, check the store information that needs to be improved on the home page and submit it for review. |
| 6 | Invalid status filter | The status field value is incorrect and only these enumerations are currently supported:unpaid, pending, packed, canceled, ready\_to\_ship, delivered, returned, shipped , failed, topack,toship , lost, lost\_by\_3pl, damaged\_by\_3pl, failed\_delivery, shipped\_back, shipped\_back\_success, shipped\_back\_failed, package\_scrapped. |
| 17 | Invalid Date Format | The time format used in the request is incorrect, please make sure your time format meets this format requirement: YYYY-MM-DDTHH:mm:ss±HH:MM. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/orders/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/orders/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/orders/get");
request.setHttpMethod("GET");
request.addApiParameter("update_before", "2018-02-10T16:00:00+08:00");
request.addApiParameter("sort_direction", "DESC");
request.addApiParameter("offset", "0");
request.addApiParameter("limit", "10");
request.addApiParameter("update_after", "2017-02-10T09:00:00+08:00");
request.addApiParameter("sort_by", "updated_at");
request.addApiParameter("created_before", "2018-02-10T16:00:00+08:00");
request.addApiParameter("created_after", "2017-02-10T09:00:00+08:00");
request.addApiParameter("status", "shipped");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {
    "count": "10",
    "countTotal": "500",
    "orders": [
      {
        "voucher_platform": "0.00",
        "voucher": "0.00",
        "warehouse_code": "dropshipping",
        "order_number": "491253082180001",
        "voucher_seller": "0.00",
        "created_at": "2018-02-09T22:44:30+08:00",
        "voucher_code": "1234",
        "gift_option": "false",
        "is_cancel_pending": "true",
        "shipping_fee_discount_platform": "0.00",
        "customer_last_name": "last_name",
        "promised_shipping_times": "shipping_time",
        "updated_at": "2018-02-09T22:44:30+08:00",
        "price": "106.00",
        "national_registration_number": "1",
        "shipping_fee_original": "0.00",
        "payment_method": "COD",
        "address_updated_at": "null",
        "recipient_info": {
          "identify_no": "012345679",
          "detail_address": "318 tanglin road, phoenix park, #01-59",
          "passport_no": "012345678"
        },
        "buyer_note": "red color",
        "customer_first_name": "Ha Hung",
        "shipping_fee_discount_seller": "0.00",
        "shipping_fee": "0.54",
        "branch_number": "2222",
        "tax_code": "562562",
        "items_count": "2",
        "delivery_info": "delivery",
        "statuses": [],
        "address_billing": {
          "country": "Singapore",
          "address3": "address3",
          "address2": "address2",
          "city": "Singapore-Singapore-500001",
          "address1": "1 CHANGI VILLAGE ROAD, 11",
          "phone2": "61****7",
          "last_name": "last_name",
          "addressDsitrict": "addressDsitrict",
          "phone": "61****7",
          "post_code": "500001",
          "address5": "address5",
          "address4": "address4",
          "first_name": "Ha Hung"
        },
        "extra_attributes": "null",
        "order_id": "491253082180001",
        "need_cancel_confirm": "true",
        "remarks": "remarks",
        "gift_message": "1",
        "address_shipping": {
          "country": "Singapore",
          "address3": "address3",
          "address2": "address2",
          "city": "Singapore-Singapore-500001",
          "address1": "1 CHANGI VILLAGE ROAD, 11",
          "phone2": "4***456",
          "last_name": "last_name",
          "addressDsitrict": "addressDsitrict",
          "phone": "6****67",
          "post_code": "500001",
          "address5": "address5",
          "address4": "address4",
          "first_name": "Ha Hung"
        }
      }
    ]
  },
  "request_id": "0ba2887315178178017221014"
}
```
