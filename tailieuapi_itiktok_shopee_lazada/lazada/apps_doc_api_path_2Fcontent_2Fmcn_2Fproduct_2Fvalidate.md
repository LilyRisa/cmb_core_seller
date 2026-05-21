# GETMcnProductValidator

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcontent%2Fmcn%2Fproduct%2Fvalidate
> API path: /content/mcn/product/validate
> Category: LazLike API
> Scraped: 2026-05-21T00:13:52.272Z

---

Latest update2026-05-21 08:13:39

500

McnProductValidator

GET

/content/mcn/product/validate

No Authorization Required

Description:Identify high risk products

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
| access\_token | String | No | API interface call credentials |
| sign\_method | String | Yes | The HMAC hash algorithm you are using to calculate your signature |
| sign | String | Yes | Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details) |
## Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| lazOpAppKey | String | No | appKey |
| itemIdList | String | Yes | 商品id，多个用英文逗号隔开 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result of api |
| normalItemList | Number\[\] | 正常商品id列表 |
| highRiskItemList | Number\[\] | 危险商品id列表 |
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code provided when the operation fails |
| result\_message | String | error message provided when the operation fails |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/content/mcn/product/validate)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/content/mcn/product/validate

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/content/mcn/product/validate");
request.setHttpMethod("GET");
request.addApiParameter("lazOpAppKey", "123456");
request.addApiParameter("itemIdList", "4078878428,4234217729");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "result_message": "success",
    "success": "true",
    "highRiskItemList": [
      4078878428,
      4234217729
    ],
    "normalItemList": [
      4078878428,
      4234217729
    ],
    "result_code": "OK"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
