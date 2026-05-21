# POSTFreeShippingDeleteSelectedProductSKU

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpromotion%2Ffreeshipping%2Fproduct%2Fsku%2Fremove
> API path: /promotion/freeshipping/product/sku/remove
> Category: Free Shipping API
> Scraped: 2026-05-20T23:20:05.399Z

---

Latest update2022-08-03 14:15:33

2367

FreeShippingDeleteSelectedProductSKU

POST

/promotion/freeshipping/product/sku/remove

Authorization Required

Description:delete sku for free shipping promotion

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
| id | Number | Yes | promotion id |
| sku\_ids | Number\[\] | Yes | sku id list |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | true | false |
| error\_code | Number | error code |
| error\_msg | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/promotion/freeshipping/product/sku/remove)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/promotion/freeshipping/product/sku/remove

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/promotion/freeshipping/product/sku/remove");
request.addApiParameter("id", "91471120815070");
request.addApiParameter("sku_ids", "[10165704002,10164988653]");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "error message",
  "code": "0",
  "success": "true",
  "error_code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
