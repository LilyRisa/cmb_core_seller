# POSTUpdateFlexiCombo

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpromotion%2Fflexicombo%2Fupdate
> API path: /promotion/flexicombo/update
> Category: Flexicombo API
> Scraped: 2026-05-20T23:17:25.501Z

---

Latest update2022-07-30 10:20:59

2552

UpdateFlexiCombo

POST

/promotion/flexicombo/update

Authorization Required

Description:update flexi combo

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
| apply | String | Yes | apply scope: ENTIRE\_SHOP | SPECIFIC\_PRODUCTS |
| sample\_skus | Object\[\] | No | sample list |
| productId | Number | No | sample product id |
| skuId | Number | No | sample sku id |
| criteria\_type | String | Yes | AMOUNT | QUANTITY |
| criteria\_value | String\[\] | Yes | criteria value list |
| order\_numbers | Number | Yes | orders numbers that can use flexi combo |
| name | String | Yes | flexi combo name |
| platform\_channel | String | No | platform channel |
| gift\_skus | Object\[\] | No | gift list |
| productId | Number | No | gift product id |
| skuId | Number | No | gift sku id |
| start\_time | Number | Yes | start time |
| discount\_type | String | Yes | money | discount | freeGift | freeSample | discountWithGift | moneyWithGift | discountWithSample | moneyWithSample |
| id | Number | Yes | flexi combo id |
| end\_time | Number | Yes | end time |
| discount\_value | String\[\] | Yes | discount value list |
| stackable | String | No | Stackable Discount，Ex. Buy 2SGD Save 1SGD, Buy 4SGD Save 2SGD, Buy 6SGD Save 3SGD, etc. |
| gift\_buy\_limit\_value | String\[\] | No | buyer can choose gift/sample quantity limit value list |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | true | false |
| error\_code | String | error code |
| error\_msg | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 21 | E021: Internal System Error | Internal System Error |
| 22 | E022: "%s" | invalid param |
| 23 | E023: "%s" | update failed |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/promotion/flexicombo/update)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/promotion/flexicombo/update

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/promotion/flexicombo/update");
request.addApiParameter("apply", "ENTIRE_STORE");
request.addApiParameter("sample_skus", "[{\"productId\":\"442156001\",\"skuId\":\"1174240001\"}]");
request.addApiParameter("criteria_type", "AMOUNT");
request.addApiParameter("criteria_value", "[\"100\"]");
request.addApiParameter("order_numbers", "100");
request.addApiParameter("name", "test");
request.addApiParameter("platform_channel", "1");
request.addApiParameter("gift_skus", "[{\"productId\":\"442156001\",\"skuId\":\"1174240001\"}]");
request.addApiParameter("start_time", "1591977600000");
request.addApiParameter("discount_type", "money");
request.addApiParameter("id", "9694800953530");
request.addApiParameter("end_time", "1592063999000");
request.addApiParameter("discount_value", "[\"100\"]");
request.addApiParameter("stackable", "false");
request.addApiParameter("gift_buy_limit_value", "[\"1\"]");
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
  "success": "true",
  "error_code": "null",
  "request_id": "0ba2887315178178017221014"
}
```
