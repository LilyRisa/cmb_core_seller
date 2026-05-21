# POSTEarlyBirdActivityDeactivateSkusV2

> Source: https://open.lazada.com/apps/doc/api?path=%2Factivity%2Fearly%2Fbird%2FdeactivateSkus%2Fv2
> API path: /activity/early/bird/deactivateSkus/v2
> Category: Early Bird Price API
> Scraped: 2026-05-20T23:22:33.652Z

---

Latest update2026-05-11 14:35:47

553

EarlyBirdActivityDeactivateSkusV2

POST

/activity/early/bird/deactivateSkus/v2

Authorization Required

Description:deactivate Skus for early bird acivity

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
| sku\_list | Object\[\] | Yes | sku list |
| product\_id | Number | Yes | item id |
| order\_total\_budget | Number | Yes | order total budget inventory |
| discount\_price | String | Yes | discount price |
| sku\_id | Number | Yes | sku id |
| page\_no | Number | No | page no |
| name | String | No | activity name |
| page\_size | Number | No | page size |
| id | Number | Yes | activity id |
| source | String | No | source |
| buyer\_code | String | No | buyer code |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| repeated | Boolean | repeated |
| retry | Boolean | retry |
| success | Boolean | interface success |
| module | Object | null |
| error\_code | Object | error message |
| key | String | error key |
| error\_code\_params | Object\[\] | message |
| display\_message | String | message |
| log\_message | String | message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/activity/early/bird/deactivateSkus/v2)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/activity/early/bird/deactivateSkus/v2

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/activity/early/bird/deactivateSkus/v2");
request.addApiParameter("sku_list", "[{\"discount_price\":\"20.95\",\"product_id\":\"111111\",\"sku_id\":\"22222\",\"order_total_budget\":\"100\"}]");
request.addApiParameter("page_no", "0");
request.addApiParameter("name", "activity name");
request.addApiParameter("page_size", "0");
request.addApiParameter("id", "123");
request.addApiParameter("source", "OPENAPI");
request.addApiParameter("buyer_code", "buyer");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "success": "true",
    "error_code": {
      "display_message": "Parameter illegal: discountPrice is null or 0",
      "log_message": "null",
      "key": "EARLY_BIRD_BIZ_ERROR"
    },
    "repeated": "false",
    "retry": "false"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
