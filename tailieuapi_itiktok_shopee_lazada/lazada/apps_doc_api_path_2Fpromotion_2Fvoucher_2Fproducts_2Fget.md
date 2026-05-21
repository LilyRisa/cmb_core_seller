# GETSellerVoucherSelectedProductList

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpromotion%2Fvoucher%2Fproducts%2Fget
> API path: /promotion/voucher/products/get
> Category: Seller Voucher API
> Scraped: 2026-05-20T23:18:58.060Z

---

Latest update2022-08-03 21:16:29

4494

SellerVoucherSelectedProductList

GET

/promotion/voucher/products/get

Authorization Required

Description:query seller voucher selected products list

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
| voucher\_type | String | Yes | voucher type COLLECTIBLE\_VOUCHER | CODE\_VOUCHER |
| id | Number | Yes | Promotion ID |
| cur\_page | Number | No | cur page |
| page\_size | Number | No | page size |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | response body |
| total | Number | total |
| current | Number | current page |
| data\_list | Object\[\] | product list |
| product\_id | Number | product item id |
| sku\_ids | Number\[\] | item sku id list |
| page\_size | Number | page size |
| success | Boolean | true | false |
| error\_code | String | error code |
| error\_msg | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/promotion/voucher/products/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/promotion/voucher/products/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/promotion/voucher/products/get");
request.setHttpMethod("GET");
request.addApiParameter("voucher_type", "COLLECTIBLE_VOUCHER");
request.addApiParameter("id", "91471121134707");
request.addApiParameter("cur_page", "1");
request.addApiParameter("page_size", "20");
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
  "data": {
    "data_list": [
      {
        "sku_ids": [],
        "product_id": "1899544073"
      }
    ],
    "total": "2",
    "current": "1",
    "page_size": "10"
  },
  "success": "true",
  "error_code": "null",
  "request_id": "0ba2887315178178017221014"
}
```
