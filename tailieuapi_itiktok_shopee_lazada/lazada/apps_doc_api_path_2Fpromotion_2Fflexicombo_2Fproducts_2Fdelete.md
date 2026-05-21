# POSTDeleteFlexiComboProducts

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpromotion%2Fflexicombo%2Fproducts%2Fdelete
> API path: /promotion/flexicombo/products/delete
> Category: Flexicombo API
> Scraped: 2026-05-20T23:16:50.056Z

---

Latest update2022-07-29 14:20:56

2419

DeleteFlexiComboProducts

POST

/promotion/flexicombo/products/delete

Authorization Required

Description:delete flexi combo products

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
| sku\_ids | Number\[\] | Yes | sku list that will remove from flexi combo |
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
| 23 | E023: remove sku from flexi combo failed | remove sku from flexi combo failed |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/promotion/flexicombo/products/delete)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/promotion/flexicombo/products/delete

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/promotion/flexicombo/products/delete");
request.addApiParameter("id", "9616200353530");
request.addApiParameter("sku_ids", "[2865522584, 2865522584]");
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
