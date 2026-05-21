# POSTSellerVoucherUpdate

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpromotion%2Fvoucher%2Fupdate
> API path: /promotion/voucher/update
> Category: Seller Voucher API
> Scraped: 2026-05-20T23:19:12.910Z

---

Latest update2022-07-29 14:45:36

3148

SellerVoucherUpdate

POST

/promotion/voucher/update

Authorization Required

Description:update a existing seller voucher promotion

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
| max\_discount\_offering\_money\_value | String | No | Discount details, if order value reaches criteria\_over\_money value, allow maximum discount per order, just support percentage discount off type |
| offering\_percentage\_discount\_off | Number | No | Discount details, if order value reaches criteria\_over\_money value, will percentage discount off value |
| id | String | Yes | Promotion ID |
| criteria\_over\_money | String | Yes | Discount details, if order value reaches set value, will money discount or percentage discount |
| voucher\_type | String | Yes | Voucher type, just set COLLECTIBLE\_VOUCHER |
| apply | String | Yes | apply scope: ENTIRE\_SHOP | SPECIFIC\_PRODUCTS |
| collect\_start | Number | No | The time that customers can collect the voucher |
| display\_area | String | Yes | The area that customers can see the voucher. |
| period\_end\_time | Number | Yes | The period end time that customers can use the voucher |
| voucher\_name | String | Yes | Voucher name |
| voucher\_discount\_type | String | Yes | Discount type |
| offering\_money\_value\_off | String | Yes | Discount details, if order value reaches criteria\_over\_money value, will discount money value |
| period\_start\_time | Number | Yes | The period start time that customers can use the voucher |
| limit | Number | Yes | Voucher limit per customer |
| issued | Number | Yes | Revision should be greater than the current setting |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Number | promotion ID |
| success | Boolean | true | false |
| error\_code | Number | error code |
| error\_msg | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/promotion/voucher/update)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/promotion/voucher/update

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/promotion/voucher/update");
request.addApiParameter("max_discount_offering_money_value", "50");
request.addApiParameter("offering_percentage_discount_off", "1");
request.addApiParameter("id", "91471121134707");
request.addApiParameter("criteria_over_money", "100");
request.addApiParameter("voucher_type", "COLLECTIBLE_VOUCHER");
request.addApiParameter("apply", "SPECIFIC_PRODUCTS");
request.addApiParameter("collect_start", "1625649720000");
request.addApiParameter("display_area", "REGULAR_CHANNEL");
request.addApiParameter("period_end_time", "1630339199000");
request.addApiParameter("voucher_name", "test voucher");
request.addApiParameter("voucher_discount_type", "MONEY_VALUE_OFF");
request.addApiParameter("offering_money_value_off", "1");
request.addApiParameter("period_start_time", "1626969600000");
request.addApiParameter("limit", "1");
request.addApiParameter("issued", "5");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "null",
  "code": "0",
  "data": "9616200353530",
  "success": "true",
  "error_code": "null",
  "request_id": "0ba2887315178178017221014"
}
```
