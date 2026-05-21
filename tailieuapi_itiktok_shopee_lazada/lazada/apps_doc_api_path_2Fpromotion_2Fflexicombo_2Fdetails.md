# GETGetFlexiComboDetails

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpromotion%2Fflexicombo%2Fdetails
> API path: /promotion/flexicombo/details
> Category: Flexicombo API
> Scraped: 2026-05-20T23:16:57.218Z

---

Latest update2022-07-29 14:20:57

4016

GetFlexiComboDetails

GET

/promotion/flexicombo/details

Authorization Required

Description:get promotion flexi combo detail by id

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
| id | Number | Yes | id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | response body |
| order\_used\_numbers | Number | orders numbers that has been used in flexi combo |
| apply | String | apply scope: ENTIRE\_SHOP | SPECIFIC\_PRODUCTS |
| sample\_skus | Object\[\] | sample list |
| product\_id | Number | sample product id |
| sku\_id | Number | sample sku id |
| tier | Number | tier of the sample |
| criteria\_type | String | criteria type: AMOUNT | QUANTITY |
| type | String | fixed value: Flexi-combo |
| criteria\_value | String\[\] | criteria value |
| order\_numbers | Number | orders numbers that can use flexi combo |
| platform\_channel | String | platform channel |
| name | String | name |
| gift\_skus | Object\[\] | gift list |
| product\_id | Number | gift product id |
| sku\_id | Number | gift sku id |
| tier | Number | tier of the gift |
| discount\_type | String | money | discount | freeGift | freeSample | discountWithGift | moneyWithGift | discountWithSample | moneyWithSample |
| start\_time | Number | flexi combo start time |
| end\_time | Number | flexi combo end time |
| id | Number | id |
| discount\_value | String\[\] | discount value |
| status | String | NOT\_START | ONGOING | SUSPEND | FINISH |
| stackable | Boolean | Stackable Discount，Ex. Buy 2SGD Save 1SGD, Buy 4SGD Save 2SGD, Buy 6SGD Save 3SGD, etc. |
| gift\_buy\_limit\_value | String\[\] | buyer can choose gift/sample quantity limit value list |
| success | Boolean | true | false |
| error\_code | String | error code |
| error\_msg | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 21 | E021: Internal System Error | Internal System Error |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/promotion/flexicombo/details)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/promotion/flexicombo/details

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/promotion/flexicombo/details");
request.setHttpMethod("GET");
request.addApiParameter("id", "9694800953530");
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
    "stackable": "false",
    "gift_buy_limit_value": [
      "1",
      "2"
    ],
    "apply": "ENTIRE_SHOP",
    "gift_skus": [
      {
        "tier": "1",
        "product_id": "442156001",
        "sku_id": "1174240001"
      }
    ],
    "end_time": "1592063999000",
    "sample_skus": [
      {
        "tier": "1",
        "product_id": "442156001",
        "sku_id": "1174240001"
      }
    ],
    "discount_value": [],
    "type": "Flexi-combo",
    "discount_type": "money",
    "order_used_numbers": "20",
    "start_time": "1591977600000",
    "name": "test_openapi",
    "platform_channel": "null",
    "id": "9694800953530",
    "criteria_type": "AMOUNT",
    "criteria_value": [
      "100",
      "200"
    ],
    "order_numbers": "39",
    "status": "SUSPEND"
  },
  "success": "true",
  "error_code": "null",
  "request_id": "0ba2887315178178017221014"
}
```
