# GETListFlexiCombo

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpromotion%2Fflexicombo%2Flist
> API path: /promotion/flexicombo/list
> Category: Flexicombo API
> Scraped: 2026-05-20T23:17:09.705Z

---

Latest update2022-07-29 14:20:57

3856

ListFlexiCombo

GET

/promotion/flexicombo/list

Authorization Required

Description:list flexi combo

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
| cur\_page | Number | Yes | current page |
| name | String | No | name |
| page\_size | Number | Yes | page size |
| status | String | No | NOT\_START | ONGOING | SUSPEND | FINISH |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | success |
| error\_code | String | error\_code |
| error\_msg | String | error\_msg |
| data | Object | data |
| page\_size | Number | page\_size |
| total | Number | total |
| current | Number | current |
| data\_list | Object\[\] | data\_list |
| order\_numbers | Number | order\_numbers |
| platform\_channel | String | platform\_channel |
| name | String | name |
| gift\_skus | Object\[\] | gift\_skus |
| product\_id | Number | product\_id |
| sku\_id | Number | sku\_id |
| start\_time | Number | start\_time |
| order\_used\_numbers | Number | orders numbers that has been used in flexi combo |
| discount\_type | String | discount\_type |
| end\_time | Number | end\_time |
| id | Number | id |
| discount\_value | String\[\] | discount\_value |
| status | String | status |
| apply | String | apply |
| sample\_skus | Object\[\] | sample\_skus |
| product\_id | Number | product\_id |
| sku\_id | Number | sku\_id |
| criteria\_type | String | criteria\_type |
| type | String | type |
| criteria\_value | String\[\] | criteria\_value |
| stackable | Boolean | Stackable Discount，Ex. Buy 2SGD Save 1SGD, Buy 4SGD Save 2SGD, Buy 6SGD Save 3SGD, etc. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 21 | E021: Internal System Error | Internal System Error |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/promotion/flexicombo/list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/promotion/flexicombo/list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/promotion/flexicombo/list");
request.setHttpMethod("GET");
request.addApiParameter("cur_page", "1");
request.addApiParameter("name", "test");
request.addApiParameter("page_size", "10");
request.addApiParameter("status", "NOT_START");
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
        "stackable": "false",
        "apply": "SPECIFIC_PRODUCTS",
        "gift_skus": [
          {
            "product_id": "600956022",
            "sku_id": "1742246015"
          }
        ],
        "end_time": "1593878399000",
        "discount_value": [],
        "sample_skus": [
          {
            "product_id": "640558086",
            "sku_id": "1926072105"
          }
        ],
        "discount_type": "freeGift",
        "type": "Flexi-combo",
        "start_time": "1593705600000",
        "order_used_numbers": "20",
        "name": "终极",
        "platform_channel": "null",
        "id": "9786600553530",
        "criteria_type": "QUANTITY",
        "criteria_value": [],
        "order_numbers": "10",
        "status": "ONGOING"
      }
    ],
    "total": "118",
    "current": "1",
    "page_size": "10"
  },
  "success": "true",
  "error_code": "null",
  "request_id": "0ba2887315178178017221014"
}
```
