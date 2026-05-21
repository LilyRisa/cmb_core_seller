# GET/POSTQueryWarehouseDetailInfoBySellerId

> Source: https://open.lazada.com/apps/doc/api?path=%2Frc%2Fwarehouse%2Fdetail%2Fget
> API path: /rc/warehouse/detail/get
> Category: Seller API
> Scraped: 2026-05-20T23:03:52.297Z

---

Latest update2022-07-28 17:14:55

6913

QueryWarehouseDetailInfoBySellerId

GET/POST

/rc/warehouse/detail/get

Authorization Required

Description:query warehouse detail info by seller id

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
| No Data |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | xxx |
| not\_success | Boolean | xxx |
| success | Boolean | xxx |
| module | Object | xxx |
| country | String | country |
| province | String | province |
| city | String | city |
| district | String | district |
| name | String | name |
| detail\_address | String | detail\_address |
| post\_code | String | post\_code |
| warehouse\_code | String | warehouse\_code |
| default\_address | Boolean | default\_address |
| status | String | status |
| error\_code | String | xxx |
| repeated | Boolean | xx |
| retry | Boolean | xx |
| class\_name | String | class name |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| IllegalAccessToken | The specified access token is invalid or expired | access token is invalid or expired |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/rc/warehouse/detail/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/rc/warehouse/detail/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/rc/warehouse/detail/get");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "not_success": "false",
    "success": "true",
    "module": {
      "country": "Singapore",
      "default_address": "true",
      "province": "Singapore",
      "city": "65  Hillview  Dairy  Farm  Bukit  Panjang  Choa  Chu  Kang",
      "detail_address": "福田区2号",
      "warehouse_code": "dropshipping",
      "district": "650387",
      "post_code": "61544",
      "name": "james shop sg test153",
      "status": "ACTIVE"
    },
    "error_code": "null",
    "repeated": "false",
    "class_name": "com.alibaba.ecommerce.module.Response",
    "retry": "false"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
