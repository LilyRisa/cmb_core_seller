# POSTaddSolution

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2FaddSolution
> API path: /sponsor/solutions/addSolution
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:03:10.500Z

---

Latest update2026-05-21 08:02:57

500

addSolution

POST

/sponsor/solutions/addSolution

Authorization Required

Description:Add sponsor solution

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
| bizCode | String | Yes | Decided to choose which advertisement solution.SD:sponsoredSearch. |
| autoKeyword | Number | No | Let Lazada automatically set keyword for your products.1:manual(I want to select keywords manually for my product selection.);2:auto(Let Lazada optimize the keywords relating to your products in real time to maximize the campaigns' performance). |
| endDate | String | Yes | Campaign end date. |
| platform | Number\[\] | Yes | Placements determine where shoppers will see your promoted products.3:Search Result Page;4:Just For You Page |
| autoCreative | Number | Yes | Lazada automatically set creatives for your products.1:ON;0:OFF. |
| campaignObjective | Number | Yes | Your campaign objective helps determine your bidding strategy - Traffic objective helps you to increase the number of clicks to your store, while sales objective helps to increase your store’s sales.1:Traffic;2:Sales. |
| campaignType | Number | Yes | Unlock different ways to bids, select products, and keywords with campaign types.1:Standard;2:Smart. |
| campaignModel | Number | Yes | Fine granularity to distinguish solutions. |
| maxBid | String | Yes | Max bid determines the highest amount that you're willing to pay for a click on your promoted product.String type, -1 means no limit. |
| autoItemSelect | Number | Yes | The way the product be selected.1:manual(I want to select products manually from my store.);2:auto(Let Lazada optimize the products within the campaigns in real-time to maximize the campaigns' performance) |
| dayBudget | String | Yes | Budget indicates the maximum amount you’re willing to pay each day. |
| campaignName | String | Yes | Campaign name. |
| startDate | String | Yes | Campaign start date. |
| adgroupViewDTOlistWithFeed | Object\[\] | Yes | Adgroup list. |
| adgroupName | String | Yes | Adgroup name, normally is product name, |
| bidPrice | String | No | This is the maximum bid price that you have set for your campaign.When campaignType is 1, this field must be filled. |
| autoKeyword | Number | Yes | Let Lazada automatically set keyword for your products.1:manual(I want to select keywords manually for my product selection.);2:auto(Let Lazada optimize the keywords relating to your products in real time to maximize the campaigns' performance). This must be the same as the campaign. |
| audienceViewDTOList | Object\[\] | No | This setting allows you to bid higher on premium audiences that are more likely to convert in your store. |
| adCrowdTag | Number | No | 1:on store visitors in the past 15 days;2:on in-market audiences for similar products;3:Store Awareness Audience;4:Store Interest Audience |
| discount | Number | No | The discount you want to give.eg:10 means 10% discount. |
| itemId | Number | Yes | Product id. |
| bidwordViewDTOList | Object\[\] | No | Bid word list |
| keyword | String | No | The specific keyword.eg:shoe. |
| bidPrice | String | No | This is the maximum bid price that you have set for your campaign. |
| autoItemSelect | Number | Yes | The way the product be selected.1:manual(I want to select products manually from my store.);2:auto(Let Lazada optimize the products within the campaigns in real-time to maximize the campaigns' performance) |
| autoCreative | Number | Yes | Let Lazada automatically set creatives for your products.1:ON;0:OFF. This must be the same as the campaign. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | System result for this api call. |
| result | Object | The detail result, for this api is boolean. |
| errorMsg | String | If the api call failed, this field will show the detail reason. |
| analyseTraceId | String | If the api call failed, you could find us with this. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/addSolution)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/sponsor/solutions/addSolution

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/addSolution");
request.addApiParameter("bizCode", "sponsoredSearch");
request.addApiParameter("autoKeyword", "2");
request.addApiParameter("endDate", "2023-03-08");
request.addApiParameter("platform", "[3,4]");
request.addApiParameter("autoCreative", "1");
request.addApiParameter("campaignObjective", "2");
request.addApiParameter("campaignType", "1");
request.addApiParameter("campaignModel", "99");
request.addApiParameter("maxBid", "100");
request.addApiParameter("autoItemSelect", "1");
request.addApiParameter("dayBudget", "10");
request.addApiParameter("campaignName", "Campaign_2023_03_08_11:11");
request.addApiParameter("startDate", "2023-03-01");
request.addApiParameter("adgroupViewDTOlistWithFeed", "[{\"autoKeyword\":\"2\",\"audienceViewDTOList\":[{\"adCrowdTag\":\"1\",\"discount\":\"44\"},{\"adCrowdTag\":\"1\",\"discount\":\"44\"}],\"itemId\":\"123\",\"bidwordViewDTOList\":[{\"keyword\":\"Nike\",\"bidPrice\":\"4\"},{\"keyword\":\"Nike\",\"bidPrice\":\"4\"}],\"adgroupName\":\"starbuck\",\"autoItemSelect\":\"1\",\"autoCreative\":\"1\",\"bidPrice\":\"3\"}]");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {},
  "code": "0",
  "success": "true",
  "analyseTraceId": "...",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "invalid"
}
```
