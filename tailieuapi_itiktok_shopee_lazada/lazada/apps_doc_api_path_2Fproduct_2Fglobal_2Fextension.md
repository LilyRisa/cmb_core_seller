# GETGetGlobalProductExtension

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fglobal%2Fextension
> API path: /product/global/extension
> Category: Cross Boarder Product API
> Scraped: 2026-05-20T23:12:35.404Z

---

Latest update2024-03-08 13:40:45

3701

GetGlobalProductExtension

GET

/product/global/extension

Authorization Required

Description:Use this API to query the extension info of the specified global product. (CrossBoarderSellersOnly)

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
| global\_item\_ids | Number\[\] | No | Batch size is limited to 50 |
| item\_ids | Number\[\] | No | Batch size is limited to 50, if global\_Item\_ids is present, this field will be ignored |
| country | String | No | country,if global\_Item\_ids is present, this field will be ignored |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | process result，If this is true, it doesn't mean that everything is processed successfully. It is necessary to judge that the item\_err\_code in packages is equal to 0 to determine that the processing is successful. |
| error\_code | String | exists when success is false |
| error\_msg | String | exists when success is false |
| data | Object\[\] | resp body |
| global\_item\_id | Number | globalItemId |
| item\_id | Number | itemId |
| products | Object\[\] | products |
| abs | String | average number of items included in an order |
| item\_id | Number | itemId |
| market | String | market |
| semi\_status | Number | 0 : false 1:true |
| skus | Object\[\] | skus |
| sku\_id | Number | sku\_id |
| seller\_sku | String | sellerSku |
| no\_postage\_fee | Object | no\_postage\_fee |
| special\_price | Object | special\_price |
| price | Object | freight\_insurance |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| E1000 | Internal Application Error | Endpoint exception, please use MY endpoint for GSP related requests. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/global/extension)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/product/global/extension

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/global/extension");
request.setHttpMethod("GET");
request.addApiParameter("global_item_ids", "[1234]");
request.addApiParameter("item_ids", "[1234]");
request.addApiParameter("country", "SG/VN/PH/TH/MY");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "Invalid Limit",
  "code": "0",
  "data": [
    {
      "global_item_id": "12312",
      "item_id": "123121",
      "products": [
        {
          "market": "LAZADA_SG",
          "semi_status": "1",
          "abs": "1",
          "skus": [
            {
              "special_price": {
                "amount": 3500,
                "currency": "VND"
              },
              "price": {
                "amount": 3500,
                "currency": "VND"
              },
              "seller_sku": "sellerSku",
              "no_postage_fee": {
                "amount": 3500,
                "currency": "VND"
              },
              "sku_id": "1231231"
            }
          ],
          "item_id": "12312"
        }
      ]
    }
  ],
  "success": "true",
  "error_code": "E0019",
  "request_id": "0ba2887315178178017221014"
}
```
