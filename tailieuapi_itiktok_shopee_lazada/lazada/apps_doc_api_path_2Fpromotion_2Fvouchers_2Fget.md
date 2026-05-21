# GETSellerVoucherList

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpromotion%2Fvouchers%2Fget
> API path: /promotion/vouchers/get
> Category: Seller Voucher API
> Scraped: 2026-05-20T23:18:50.649Z

---

Latest update2022-07-29 14:43:23

9542

SellerVoucherList

GET

/promotion/vouchers/get

Authorization Required

Description:query seller voucher promotion list

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
| cur\_page | Number | No | current page |
| voucher\_type | String | Yes | voucher type COLLECTIBLE\_VOUCHER | CODE\_VOUCHER |
| name | String | No | promotion name |
| page\_size | Number | No | page size |
| status | String | No | NOT\_START | ONGOING | SUSPEND | FINISH |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | response body |
| total | Number | total |
| current | Number | current page |
| data\_list | Object\[\] | data list |
| criteria\_over\_money | String | Discount details, if order value reaches set value, will money discount or percentage discount |
| apply | String | apply scope: ENTIRE\_SHOP | SPECIFIC\_PRODUCTS |
| voucher\_type | String | Voucher type, COLLECTIBLE\_VOUCHER | CODE\_VOUCHER |
| collect\_start | Number | The time that customers can collect the voucher |
| display\_area | String | The area that customers can see the voucher. REGULAR\_CHANNEL|STORE\_FOLLOWER|OFFLINE|LIVE\_STREAM|SHARE\_VOUCHER|CEM\_SELLER |
| period\_end\_time | Number | The period end time that customers can use the voucher |
| voucher\_name | String | Voucher name |
| voucher\_discount\_type | String | Discount type |
| offering\_money\_value\_off | String | Discount details, if order value reaches criteria\_over\_money value, will discount money value |
| period\_start\_time | Number | The period start time that customers can use the voucher |
| limit | Number | Voucher limit per customer |
| order\_used\_budget | Number | Already used total |
| currency | String | Currency |
| id | Number | Promotion ID |
| issued | Number | Revision should be greater than the current setting |
| max\_discount\_offering\_money\_value | String | Discount details, if order value reaches criteria\_over\_money value, allow maximum discount per order, just support percentage discount off type |
| voucher\_code | String | Voucher code |
| offering\_percentage\_discount\_off | String | Discount details, if order value reaches criteria\_over\_money value, will percentage discount off value |
| status | String | Promotin status, NOT\_START | ONGOING | SUSPEND | FINISH |
| page\_size | Number | current page size |
| success | Boolean | true | false |
| error\_code | String | error code |
| error\_msg | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| Mp3SellerApiLimit | Mp3 Seller not support the api -apipath | MP3 sellers cannot call the current API, please readthis document for a list of APIs that can be called by MP3 sellers, and you can call the GetSeller API and check the marketplaceEaseMode field to confirm that the current seller is of type MP3. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/promotion/vouchers/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/promotion/vouchers/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/promotion/vouchers/get");
request.setHttpMethod("GET");
request.addApiParameter("cur_page", "1");
request.addApiParameter("voucher_type", "COLLECTIBLE_VOUCHER");
request.addApiParameter("name", "null");
request.addApiParameter("page_size", "10");
request.addApiParameter("status", "null");
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
  "data": {
    "data_list": [
      {
        "period_end_time": "1630339199000",
        "max_discount_offering_money_value": "null",
        "criteria_over_money": "100",
        "apply": "SPECIFIC_PRODUCTS",
        "voucher_name": "test voucher",
        "voucher_code": "null",
        "offering_money_value_off": "1",
        "order_used_budget": "0",
        "offering_percentage_discount_off": "null",
        "period_start_time": "1626969600000",
        "display_area": "REGULAR_CHANNEL",
        "voucher_type": "COLLECTIBLE_VOUCHER",
        "limit": "1",
        "collect_start": "1626969600000",
        "voucher_discount_type": "MONEY_VALUE_OFF",
        "currency": "SGD",
        "id": "91471121126083",
        "issued": "5",
        "status": "SUSPEND"
      }
    ],
    "total": "243",
    "current": "1",
    "page_size": "10"
  },
  "success": "true",
  "error_code": "null",
  "request_id": "0ba2887315178178017221014"
}
```
