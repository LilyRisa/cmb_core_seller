# POSTCreateEarlyBirdActivity

> Source: https://open.lazada.com/apps/doc/api?path=%2Factivity%2Fearly%2Fbird%2Fcreate
> API path: /activity/early/bird/create
> Category: Early Bird Price API
> Scraped: 2026-05-20T23:21:25.998Z

---

Latest update2026-02-27 15:05:37

837

CreateEarlyBirdActivity

POST

/activity/early/bird/create

Authorization Required

Description:early bird price activity create

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
| order\_total\_budget | Number | Yes | activity inventory |
| discount\_price | Object | Yes | discount price |
| sku\_id | Number | Yes | sku id |
| page\_no | Number | No | page no |
| name | String | No | activity name |
| page\_size | Number | No | page\_size |
| id | Number | No | activity id |
| source | String | No | source |
| buyer\_code | String | No | buyer\_code |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| success | Boolean | success |
| module | Object | activity id |
| error\_code | Object | errorCode |
| error\_code\_params | Object\[\] | error\_code\_params |
| display\_message | String | display\_message |
| log\_message | String | log\_message |
| key | String | error code |
| repeated | Boolean | repeated |
| retry | Boolean | retry |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/activity/early/bird/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/activity/early/bird/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/activity/early/bird/create");
request.addApiParameter("sku_list", "[{\"discount_price\":{},\"product_id\":\"111111\",\"sku_id\":\"222222\",\"order_total_budget\":\"100\"}]");
request.addApiParameter("page_no", "0");
request.addApiParameter("name", "xx activity");
request.addApiParameter("page_size", "0");
request.addApiParameter("id", "0");
request.addApiParameter("source", "OPENAPI");
request.addApiParameter("buyer_code", "0");
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
    "module": {},
    "error_code": {
      "display_message": " illegal seller",
      "log_message": " null",
      "key": "EARLY_BIRD_BIZ_ERROR"
    },
    "repeated": "false",
    "retry": "false"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
