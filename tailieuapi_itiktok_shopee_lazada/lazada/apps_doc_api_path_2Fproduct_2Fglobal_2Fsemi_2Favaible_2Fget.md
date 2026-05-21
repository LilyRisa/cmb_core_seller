# GET/POSTGetUpgradableGlobalPlusProductList

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fglobal%2Fsemi%2Favaible%2Fget
> API path: /product/global/semi/avaible/get
> Category: Cross Boarder Product API
> Scraped: 2026-05-20T23:13:32.029Z

---

Latest update2024-02-28 20:54:16

3503

GetUpgradableGlobalPlusProductList

GET/POST

/product/global/semi/avaible/get

Authorization Required

Description:get an upgradeable global plus product list

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
| type | String | Yes | global |
| country | String | No | country |
| pageNo | String | Yes | page no |
| pageSize | String | Yes | page size |
| currentIndex | String | Yes | current index |
| itemIds | Number\[\] | No | itemId or productId |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | data |
| total\_products | Number | 1 |
| page\_size | Number | 0 |
| type | String | global |
| current\_index | Number | 0 |
| products | Object\[\] | data |
| item\_id | Number | 3025196234185548 |
| skus | Object\[\] | sku |
| item\_id | Number | 3025196234185548 |
| package\_height | String | 10 |
| package\_weight | String | 0.5 |
| package\_length | String | 10 |
| package\_width | String | 10 |
| seller\_sku | String | sku |
| country\_info | Object\[\] | country info |
| market | String | LAZADA\_SG |
| quantity | Number | 1 |
| price | String | 1 |
| currency | String | 1 |
| special\_price | String | 1 |
| item\_id | Number | country item id |
| sku\_id | Number | country sku id |
| abs | String | abs |
| sku\_id | Number | 3025196234185548 |
| global\_item\_id | Number | 3025196234185548 |
| current\_page | String | 0 |
| success | Boolean | true |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/global/semi/avaible/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/product/global/semi/avaible/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/global/semi/avaible/get");
request.addApiParameter("type", "global");
request.addApiParameter("country", "SG");
request.addApiParameter("pageNo", "0");
request.addApiParameter("pageSize", "10");
request.addApiParameter("currentIndex", "0");
request.addApiParameter("itemIds", "[3135539721]");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {
    "type": "global",
    "total_products": "1",
    "current_page": "0",
    "page_size": "0",
    "current_index": "0",
    "products": [
      {
        "global_item_id": "3025196234185548",
        "skus": [
          {
            "package_width": "10",
            "package_height": "10",
            "item_id": "3025196234185548",
            "package_length": "10",
            "seller_sku": "wangyi-test-sku-0308-001-1",
            "package_weight": "0.5",
            "sku_id": "3025196234185548",
            "country_info": [
              {
                "market": "LAZADA_SG",
                "quantity": "1",
                "abs": "1.0",
                "special_price": "700 CNY",
                "item_id": "2289233261",
                "price": "200.60",
                "currency": "SGD",
                "sku_id": "13235769889"
              }
            ]
          }
        ],
        "item_id": "3025196234185548"
      }
    ]
  },
  "success": "true",
  "request_id": "0ba2887315178178017221014"
}
```
