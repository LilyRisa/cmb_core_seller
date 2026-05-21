# GETGetUnfilledAttribute

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fglobal%2Funfilled%2Fattribute%2Fget
> API path: /product/global/unfilled/attribute/get
> Category: Cross Boarder Product API
> Scraped: 2026-05-20T23:13:17.133Z

---

Latest update2022-07-29 14:54:09

3507

GetUnfilledAttribute

GET

/product/global/unfilled/attribute/get

Authorization Required

Description:get the product which have attribute not filled （for cross boarder sellers Only）

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
| offset | Number | Yes | offset |
| limit | Number | Yes | pageSize |
| attributeTag | String | Yes | only support key\_prop |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | response body |
| total\_products | Number | total numbers |
| products | Object\[\] | products |
| item\_id | Number | product id |
| primary\_category | Number | category id |
| seller\_sku | String | seller sku |
| attributes | Object\[\] | all attributes |
| advanced | Object | 1: key attribute |
| is\_key\_prop | Number | 1: key attribute |
| input\_type | String | text |
| options | String\[\] | all values |
| name | String | key name |
| is\_mandatory | Number | 1: mandatory |
| attribute\_type | String | normal |
| label | String | attritebu label |
| success | Boolean | success or false |
| error\_detail | String | error detail |
| error\_code | String | error code |
| errors | String | errors |
| error\_msg | String | error msg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 19 | E019: Invalid Limit | The maximum value of limit is 50 |
| 306 | E306: attribute tag not allowed | attributeTag only enter "key\_ prop" |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/global/unfilled/attribute/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/product/global/unfilled/attribute/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/global/unfilled/attribute/get");
request.setHttpMethod("GET");
request.addApiParameter("offset", "0");
request.addApiParameter("limit", "50");
request.addApiParameter("attributeTag", "key_prop");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "E019: Invalid Limit",
  "code": "0",
  "data": {
    "total_products": "2542",
    "products": [
      {
        "item_id": "623904419103525",
        "primary_category": "10000388",
        "seller_sku": "sssss",
        "attributes": [
          {
            "advanced": {
              "is_key_prop": "0"
            },
            "input_type": "text",
            "options": [],
            "name": "video",
            "is_mandatory": "0",
            "attribute_type": "normal",
            "label": "Video URL"
          }
        ]
      }
    ]
  },
  "success": "true",
  "error_detail": "null",
  "error_code": "19",
  "request_id": "0ba2887315178178017221014",
  "errors": "[]"
}
```
