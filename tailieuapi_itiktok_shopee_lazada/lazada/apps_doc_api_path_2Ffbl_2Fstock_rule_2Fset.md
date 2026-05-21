# POSTSetStockRule

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Fstock_rule%2Fset
> API path: /fbl/stock_rule/set
> Category: FBL API
> Scraped: 2026-05-20T23:44:12.808Z

---

Latest update2022-08-03 18:25:10

2345

SetStockRule

POST

/fbl/stock\_rule/set

Authorization Required

Description:set channel ratio by sku and warehouse

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
| skus | Object\[\] | Yes | skus |
| fulfillment\_sku\_id | String | Yes | fulfillment sku id |
| store\_code | String | Yes | warehouse code |
| ratio | Number | Yes | ratio |
| auto\_balancing | Boolean | Yes | enable auto-balancing between channels |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | success |
| error\_code | String | error code |
| error\_message | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/stock_rule/set)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/fbl/stock\_rule/set

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/stock_rule/set");
request.addApiParameter("skus", "[{\"store_code\":\"OMS-LAZADA-SG-W-1\",\"fulfillment_sku_id\":\"1234\",\"auto_balancing\":\"true\",\"ratio\":\"100\"}]");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "shipper_error",
  "code": "0",
  "success": "true",
  "error_code": "SHIPPER_ERROR",
  "request_id": "0ba2887315178178017221014"
}
```
