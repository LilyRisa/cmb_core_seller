# GETGetReverseOrderDetail

> Source: https://open.lazada.com/apps/doc/api?path=%2Forder%2Freverse%2Freturn%2Fdetail%2Flist
> API path: /order/reverse/return/detail/list
> Category: Return and Refund API
> Scraped: 2026-05-20T23:24:46.012Z

---

Latest update2022-07-28 17:13:18

20198

GetReverseOrderDetail

GET

/order/reverse/return/detail/list

Authorization Required

Description:Get the detailed information for a specific reverse order

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
| reverse\_order\_id | Number | Yes | 0 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | data |
| reverse\_order\_id | Number | reverse order id |
| trade\_order\_id | Number | trade order id |
| request\_type | String | CANCEL;RETURN;ONLY\_REFUND |
| shipping\_type | String | PICK\_UP;DROP\_OFF |
| is\_rtm | Boolean | is Return to Merchant or not |
| reverseOrderLineDTOList | Object\[\] | reverseOrderLineDTOList |
| reverse\_order\_line\_id | Number | reverse order line id |
| trade\_order\_line\_id | Number | trade order line id |
| buyer | Object | buyer |
| user\_id | Number | buyer user id |
| reverse\_status | String | REQUEST\_INITIATE;REQUEST\_REJECT;REQUEST\_CANCEL;CANCEL\_SUCCESS |
| productDTO | Object | productDTO |
| product\_id | Number | product id |
| sku | String | sku id |
| is\_need\_refund | Boolean | need refund or not |
| ofc\_status | String | fulfillment status |
| trade\_order\_gmt\_create | Number | trade order create time |
| refund\_amount | Number | refund amount, currency in cent, except VN (for example for SG, 100 equals SGD $1; for VN, 10000 equals VND 10000) |
| reason\_text | String | reason text |
| reason\_code | Number | reason code |
| refund\_payment\_method | String | refund payment Method |
| whqc\_decision | String | warehouse decision |
| return\_order\_line\_gmt\_create | Number | reverse order line create time |
| return\_order\_line\_gmt\_modified | Number | reverse order line modified time |
| is\_dispute | Boolean | is in dispute or not |
| seller\_sku\_id | String | seller sku id |
| item\_unit\_price | Number | price, currency in cent, except VN (for example for SG, 100 equals SGD $1; for VN, 10000 equals VND 10000) |
| platform\_sku\_id | String | platform sku id |
| tracking\_number | String | tracking number |
| sla | Number | seller operation sla in milliseconds |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 105 | E0105: reverse order id is empty or invalid | E0105: reverse order id is empty or invalid |
| 106 | E0106: ROC internal error | E0106: ROC internal error |
| 116 | E0116: no seller id | E0116: no seller id |
| 117 | E0117: no user id | E0117: no user id |
| 118 | E0118: no user email | E0118: no user email |
| Mp3SellerApiLimit | Mp3 Seller not support the api -apipath | MP3 sellers cannot call the current API, please readthis document for a list of APIs that can be called by MP3 sellers, and you can call the GetSeller API and check the marketplaceEaseMode field to confirm that the current seller is of type MP3. |
| 106 | ROC internal error | The reverse ID entered in reverse\_order\_id does not exist in the current store or is incorrect, call the GetReverseOrdersForSeller API to resynchronize or query for the correct reverse order ID. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/order/reverse/return/detail/list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/order/reverse/return/detail/list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/order/reverse/return/detail/list");
request.setHttpMethod("GET");
request.addApiParameter("reverse_order_id", "reverse order id");
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
    "reverse_order_id": "0",
    "request_type": "CANCEL",
    "reverseOrderLineDTOList": [
      {
        "return_order_line_gmt_create": "0",
        "platform_sku_id": "th-000",
        "is_need_refund": "true",
        "trade_order_gmt_create": "0",
        "reason_text": "Out of stock",
        "item_unit_price": "0",
        "sla": "1741672336776",
        "trade_order_line_id": "0",
        "return_order_line_gmt_modified": "0",
        "ofc_status": "INITIAL",
        "seller_sku_id": "th-123",
        "productDTO": {
          "product_id": "0",
          "sku": "0"
        },
        "refund_payment_method": "Alipay",
        "buyer": {
          "user_id": "0"
        },
        "reason_code": "123",
        "whqc_decision": "scrap",
        "reverse_status": "REQUEST_INITIATE",
        "refund_amount": "0",
        "tracking_number": "NLRSGZ10444515",
        "is_dispute": "true",
        "reverse_order_line_id": "0"
      }
    ],
    "shipping_type": "PICK_UP",
    "is_rtm": "true",
    "trade_order_id": "0"
  },
  "request_id": "0ba2887315178178017221014"
}
```
