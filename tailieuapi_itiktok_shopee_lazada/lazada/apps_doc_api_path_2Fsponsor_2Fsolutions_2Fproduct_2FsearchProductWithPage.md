# GET/POSTsearchProductWithPage

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Fproduct%2FsearchProductWithPage
> API path: /sponsor/solutions/product/searchProductWithPage
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:08:11.132Z

---

Latest update2026-05-21 08:07:58

500

searchProductWithPage

GET/POST

/sponsor/solutions/product/searchProductWithPage

Authorization Required

Description:Search product.

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
| brandName | String | No | Prodct brand name. |
| campaignType | Number | Yes | Unlock different ways to bids, select products, and keywords with campaign types. |
| pageSize | Number | Yes | Page size. |
| bizCode | String | Yes | Decided to choose which advertisement solution.SD:sponsoredSearch. |
| placementList | Number\[\] | Yes | Placements determine where shoppers will see your promoted products.3:Search Result Page;4:Just For You Page |
| productName | String | No | Product name to fuzzy search. |
| campaignObjectLive | Number | Yes | Your campaign objective helps determine your bidding strategy - Traffic objective helps you to increase the number of clicks to your store, while sales objective helps to increase your store’s sales.1:Traffic;2:Sales. |
| eligible | Number | Yes | Only search product which is eligible|ineligible.1:eligible;0:ineligible. |
| pageNo | Number | Yes | Page number. |
| sellerSku | String | No | Product sellerSku. |
| maxCpc | String | Yes | Max bid determines the highest amount that you're willing to pay for a click on your promoted product.-1 means no limit. |
| categoryId | Number | No | Input category id to exact search. |
| itemIdBlackList | Number\[\] | No | Input item id which you do not want put into result. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object\[\] | The detail result, for this api is product detail info. |
| itemId | Number | Product id. |
| productName | String | Product name. |
| imageUrl | String | Product image url. |
| pdpLink | String | Product datail page. |
| categoryId | Number | Category id. |
| bidPrice | String | This is the maximum bid price that you have set for your campaign. |
| competitionIndex | Number | From 10 to 1, derived from algorithm, bigger number means the product is better. |
| avgSalesVolume | Number | Unit sold. |
| retailPrice | String | Retail price. |
| inventory | Number | Quantity reflects the number of products left in stock. |
| ipv | String | Average number of page views for your product over the past 7 days. |
| cvr | String | Overall conversion rate for the product level for the past 7 days |
| contentScore | Number | From 0 to 100, the bigger the better. |
| isBan | Boolean | If this is false, means you can not select this product. |
| isDigitalUtilities | Boolean | Is this product digital utilities. |
| tags | String\[\] | This shows the product platform.SS:sponsored search;SP:sponsored products. |
| success | Boolean | System result for this api call. |
| analyseTraceId | String | If the api call failed, you could find us with this. |
| totalCount | Number | Total count of product. |
| errorMsg | String | If the api call failed, this field will show the detail reason. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/product/searchProductWithPage)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/product/searchProductWithPage

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/product/searchProductWithPage");
request.addApiParameter("brandName", "adidas");
request.addApiParameter("campaignType", "1");
request.addApiParameter("pageSize", "20");
request.addApiParameter("bizCode", "sponsoredSearch");
request.addApiParameter("placementList", "[3,4]");
request.addApiParameter("productName", "star");
request.addApiParameter("campaignObjectLive", "2");
request.addApiParameter("eligible", "1");
request.addApiParameter("pageNo", "1");
request.addApiParameter("sellerSku", "sku1");
request.addApiParameter("maxCpc", "-1");
request.addApiParameter("categoryId", "7939");
request.addApiParameter("itemIdBlackList", "[321]");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": [
    {
      "avgSalesVolume": "0",
      "isDigitalUtilities": "true",
      "inventory": "99",
      "productName": "xiaotian Test PH",
      "bidPrice": "2.34",
      "ipv": "1",
      "tags": [
        "SS",
        "SP"
      ],
      "itemId": "200155812",
      "competitionIndex": "10",
      "imageUrl": "https://ph-live.slatic.net/original/8c82428287b375c4ce3cf6bd00f736aa.jpg",
      "isBan": "true",
      "pdpLink": "https://www.lazada.com.ph/products/xiaotian-test-ph-i200155812-s250760605.html",
      "contentScore": "100",
      "retailPrice": "10",
      "categoryId": "62188201",
      "cvr": "0"
    }
  ],
  "code": "0",
  "success": "true",
  "analyseTraceId": "...",
  "totalCount": "100",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "-"
}
```
