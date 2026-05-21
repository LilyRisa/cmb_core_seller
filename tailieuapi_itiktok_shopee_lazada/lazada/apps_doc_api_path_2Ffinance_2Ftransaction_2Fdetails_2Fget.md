# GETQueryTransactionDetails

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffinance%2Ftransaction%2Fdetails%2Fget
> API path: /finance/transaction/details/get
> Category: Finance API
> Scraped: 2026-05-20T23:32:09.574Z

---

Latest update2022-07-28 17:04:38

34842

QueryTransactionDetails

GET

/finance/transaction/details/get

Authorization Required

Description:API to query seller transaction details within specific date range.

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
| offset | String | No | Number of transaction lines to skip at the beginning of the list. |
| trans\_type | String | No | Transaction type ID. |
| trade\_order\_id | String | No | Order ID. |
| limit | String | No | Number of lines of transactions to be extracted. The supported maximum number is 500. |
| start\_time | String | Yes | Starting date when transactions need to be extracted. |
| end\_time | String | Yes | Ending date when transactions need to be extracted. |
| trade\_order\_line\_id | String | No | Order Item ID. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object\[\] | Response body |
| fee\_type | String | Transaction type ID. |
| details | String | Transaction details |
| seller\_sku | String | The seller SKU |
| lazada\_sku | String | The Lazada SKU |
| amount | String | Total transaction value |
| VAT\_in\_amount | String | The VAT in amount |
| WHT\_amount | String | The WHT amount |
| WHT\_included\_in\_amount | String | The WHT included in amount or not |
| statement | String | Statement ID |
| paid\_status | String | Yes / No |
| order\_no | String | Order ID |
| orderItem\_no | String | Order item number |
| orderItem\_status | String | The order item status |
| shipping\_provider | String | The shipping provider |
| shipping\_speed | String | The shipping speed |
| shipment\_type | String | The shipment type |
| reference | String | The Order Item ID (the Sub-order ID of "Order ID" parameter) |
| comment | String | Comments by regional finance team |
| payment\_ref\_id | String | Payment reference ID from bank or other payment provider |
| fee\_name | String | feeName |
| transaction\_date | String | Date of the transaction |
| transaction\_type | String | Transaction type or fee name |
| transaction\_number | String | Unique ID of the transaction in the format "Seller code- xxxxxxx" |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 1000012 | endTime - startTime must should be less than 180 days | endTime - startTime must should be less than 180 days |
| 1000014 | Can not find that transactionType | transaction type invalid |
| 1000012 | endTime - startTime must should be less than 180 days | Please make sure that the timeframe of your inquiry is within 180 days. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/finance/transaction/details/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/finance/transaction/details/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/finance/transaction/details/get");
request.setHttpMethod("GET");
request.addApiParameter("offset", "0");
request.addApiParameter("trans_type", "-1");
request.addApiParameter("trade_order_id", "123123213213");
request.addApiParameter("limit", "100");
request.addApiParameter("start_time", "2021-01-01");
request.addApiParameter("end_time", "2021-01-05");
request.addApiParameter("trade_order_line_id", "45645674566");
LazopResponse response = client.execute(request, accessToken);
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
      "order_no": "123445666666",
      "transaction_date": "17 May 2016",
      "amount": "-0.62",
      "paid_status": "Not paid",
      "shipping_provider": "LEX",
      "WHT_included_in_amount": "Yes",
      "payment_ref_id": "paymentRefId",
      "lazada_sku": "Item test -123",
      "fee_type": "13",
      "transaction_type": "Payment Fee",
      "orderItem_no": "1666666",
      "orderItem_status": "orderItemStatus",
      "reference": "1340",
      "fee_name": "feeName",
      "shipping_speed": "shippingSpeed",
      "WHT_amount": "0.0112",
      "transaction_number": "SG103EF-1P9VK1A",
      "seller_sku": "sellerSKU",
      "statement": "11 May 2016 - 17 May 2016",
      "details": "details",
      "comment": "comment",
      "VAT_in_amount": "0.0672",
      "shipment_type": "Dropshipping"
    }
  ],
  "request_id": "0ba2887315178178017221014"
}
```
