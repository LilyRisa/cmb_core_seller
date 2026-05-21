# POSTEditChoiceSkuStock

> Source: https://open.lazada.com/apps/doc/api?path=%2Fchoice%2Fstock%2Fedit
> API path: /choice/stock/edit
> Category: Choice Customized API
> Scraped: 2026-05-21T00:09:38.267Z

---

Latest update2026-05-21 08:09:25

500

EditChoiceSkuStock

POST

/choice/stock/edit

Authorization Required

Description:batch update choice jit product stock by skuId

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
| item\_id | Number | Yes | item id |
| site | String | Yes | The country site of the queried Product |
| sku\_edit\_stock | String | Yes | Key：sku\_id Value: sellable stock |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | update result json |
| success\_sku | Number\[\] | success sku |
| failed\_sku | Object\[\] | failed sku |
| success | Boolean | success flag |
| error\_code | String | error code |
| error\_msg | String | error msg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| E0208 | Product not exist | The item id in the request does not exist in the current store or the CHOICE item has not yet been reviewed by Lazada, updating inventory is not supported. |
| E1002 | not jit product | Non-JIT items do not support inventory modification, please call GetChoiceProducts or GetChoiceProductItem API to query the bizSupplement field, only if the field value is 3 or 4 can you call this API to modify inventory. |
| E1001 | not jit seller | Seller authorization is not a choice authorization, please ask the seller to re-authorize and select the 'country - choice' option to complete the choice store authorization. |
| E0208 | Product not exist | The item id in the request does not exist in the current store or the CHOICE item has not yet been reviewed by Lazada, updating inventory is not supported. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/choice/stock/edit)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/choice/stock/edit

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/choice/stock/edit");
request.addApiParameter("item_id", "2616344300");
request.addApiParameter("site", "SG");
request.addApiParameter("sku_edit_stock", "{ 314525867:10, 314525868:11 }");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "Parameter Invalid",
  "code": "0",
  "data": {
    "success_sku": [
      314525868
    ],
    "failed_sku": []
  },
  "success": "true",
  "error_code": "E305",
  "request_id": "0ba2887315178178017221014"
}
```
