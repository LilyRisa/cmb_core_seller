# GET/POSTGetOrderItemsFromBarCode

> Source: https://open.lazada.com/apps/doc/api?path=%2Feticket%2Fcode%2Fquery
> API path: /eticket/code/query
> Category: E-Tickets API
> Scraped: 2026-05-20T23:52:09.627Z

---

Latest update2022-07-22 14:48:03

3543

GetOrderItemsFromBarCode

GET/POST

/eticket/code/query

Authorization Required

Description:E-Ticcket certificate query Open API

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
| code | String | Yes | certificate code |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | response body |
| biz\_type | Number | biz type |
| certificate\_code | String | coupon code |
| code\_status | String | coupon code status. 1: can use, -1: consumed, -5: expired |
| outer\_id | String | outer id |
| strart\_time | Number | start time |
| end\_time | Number | end time |
| trade\_order\_id | Number | trade\_order\_id |
| serial\_num | String | consume serial number (if it has been consumed) |
| item\_list | Object\[\] | item list |
| item\_id | String | item id |
| item\_name | String | item name |
| item\_img | String | item image link |
| unit\_fee | String | item price (the smallest unit of the currency) |
| unit\_fee\_currency | String | item price currency |
| actual\_fee | String | the actual amount paid by the buyer (the smallest unit of the currency) |
| actual\_fee\_currency | String | the actual currency paid by the buyer |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 100 | E100: Param Invalid, "%s" | Param invalid |
| 200 | E200: Certificate Not Exist | Certificate not exist |
| 201 | E201: Certificate Not Unique | More that one certificate matched |
| 202 | E202: Certificate Can Not Distinguish | Can't distinguish the business type of this code |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/eticket/code/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/eticket/code/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/eticket/code/query");
request.addApiParameter("code", "abcdedf");
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
    "strart_time": "1640969999000",
    "certificate_code": "abcd123sa",
    "item_list": [
      {
        "item_img": "https://sg-live.slatic.net/p/test.jpg",
        "item_id": "123",
        "actual_fee": "1",
        "actual_fee_currency": "SGD",
        "unit_fee": "1",
        "item_name": "Meal",
        "unit_fee_currency": "SGD"
      }
    ],
    "biz_type": "5107",
    "end_time": "1640969999000",
    "trade_order_id": "50011002200334",
    "code_status": "1",
    "outer_id": "F01312342312",
    "serial_num": "3451c641-a7da-4264-92cb-78a1f79392c3"
  },
  "request_id": "0ba2887315178178017221014"
}
```
