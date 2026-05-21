# POSTSetInvoiceNumber

> Source: https://open.lazada.com/apps/doc/api?path=%2Forder%2Finvoice_number%2Fset
> API path: /order/invoice_number/set
> Category: Order API
> Scraped: 2026-05-20T23:24:38.717Z

---

Latest update2022-07-18 20:00:40

9844

SetInvoiceNumber

POST

/order/invoice\_number/set

Authorization Required

Description:Use this API to set the invoice number for the specified order.

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
| order\_item\_id | Number | Yes | Identifier of the order item. |
| invoice\_number | String | Yes | The invoice number. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Response body |
| order\_item\_id | Number | Identifier of the order item. |
| invoice\_number | String | The invoice number. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 20 | "E020: 59871357123 Invalid Order Item ID" | Order Item ID is incorrect, please verify |
| 34 | "E034: Order Item must be packed. Please call setStatusToReadyToShip before" | Canceled or pending orders are not allowed to call this API |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/order/invoice_number/set)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/order/invoice\_number/set

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/order/invoice_number/set");
request.addApiParameter("order_item_id", "123");
request.addApiParameter("invoice_number", "INV-20");
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
    "order_item_id": "1",
    "invoice_number": "INV-20"
  },
  "request_id": "0ba2887315178178017221014"
}
```
