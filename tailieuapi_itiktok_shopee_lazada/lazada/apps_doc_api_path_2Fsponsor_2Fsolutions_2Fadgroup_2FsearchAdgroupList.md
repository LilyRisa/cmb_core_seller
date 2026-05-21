# GET/POSTsearchAdgroupList

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Fadgroup%2FsearchAdgroupList
> API path: /sponsor/solutions/adgroup/searchAdgroupList
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:07:24.014Z

---

Latest update2026-05-21 08:07:11

500

searchAdgroupList

GET/POST

/sponsor/solutions/adgroup/searchAdgroupList

Authorization Required

Description:Search adgroup with bizCode by seller.

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
| pageSize | Number | Yes | Page size. |
| endDate | String | Yes | Campaign end date. |
| campaignId | Number | Yes | Campaign id. |
| pageNo | Number | Yes | Page number. |
| bizCode | String | Yes | Decided to choose which advertisement solution.SD:sponsoredSearch. |
| adgroupName | String | No | Adgroup name for fuzzy search. |
| startDate | String | Yes | Campaign start date. |
| onlineStatus | Number | No | The campaign online status.1:Online;0:Offline;9:deleted. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | The detail result, for this api is boolean. |
| errorMsg | String | If the api call failed, this field will show the detail reason. |
| analyseTraceId | String | If the api call failed, you could find us with this.If the api call failed, you could find us with this. |
| totalCount | Number | The count of adgorup. |
| result | Object\[\] | The detail result, for this api is adgroup detail list. |
| adgroupId | Number | Adgroup id. |
| adgroupName | String | The adgroup name, normanlly is the product name. |
| imageUrl | String | Normally is the product first pic. |
| bidPrice | String | This is the maximum bid price that you have set for your campaign.Local currency. |
| autoCreative | Number | Automated Creatives selection allows Lazada to automatically optimise towards the best performing creative from your existing product image set.1:ON;0:OFF. |
| audienceViewDTOList | Object\[\] | This setting allows you to bid higher on premium audiences that are more likely to convert in your store. |
| adCrowdTag | Number | 1:on store visitors in the past 15 days;2:on in-market audiences for similar products;3:Store Awareness Audience;4:Store Interest Audience |
| discount | Number | The discount you want to give.eg:10 means 10% discount. |
| spend | String | Spend is the total amount of your spend. |
| impressions | String | An impression is counted each time your promoted product is shown. Impressions help you understand how often your product is being seen. |
| clicks | String | A click is counted each time someone clicks on your promoted product. |
| ctr | String | Clickthrough rate (CTR) is the ratio showing how often people who see your promoted product end up clicking on it. It’s calculated as Clicks divided by Impressions. |
| cpc | String | The cost-per-click (CPC) is the average amount you pay each time someone clicks your promoted product. It’s calculated as Spend divided by Clicks. |
| storeUnitsSold | String | The total number of units sold after the shoppers click on your promoted product during the selected date range. |
| storeRevenue | String | Total store revenue is generated from the units sold in your store after buyer(s) click your promoted product(s). It is the total amount paid by the buyer plus all discounts applied, store credit, shipping fees, and surcharges. |
| storeRoi | String | The store's return on investment (ROI) shows how efficient your Sponsored Discovery's spend is in driving revenue for your store. |
| storeOrders | String | The total number of orders from your store during the selected time period, after someone clicks on your promoted product. |
| productOrders | String | The total number of direct orders from your store during the selected time period, after someone clicks on your promoted product. |
| unitsSold | String | The number of promoted product units sold after shoppers click on them and purchase them. |
| revenue | String | Revenue generated from your promoted product after someone clicks on it and purchases it. It is the total buyer paid amount inclusive of all discounts applied, store credit, shipping fees and surcharges. |
| status | Number | The combine of balance,budget,schedule... |
| adAccountBalanceStatus | Number | Is the balance enough.1:ON;0:OFF. |
| adApproveStatus | Number | Is the adgroup be approved.1:ON;0:OFF. |
| adSwitchStatus | Number | Is the adgroup on right now.1:ON;0:OFF. |
| campaignDailyBudgetStatus | Number | Is the campaign budget of today enougn now.1:ON;0:OFF. |
| campaignScheduleStatus | Number | Is the campaign running now.1:ON;0:OFF. |
| campaignSwitchStatus | Number | Is the campaign switch on now.1:ON;0:OFF. |
| productEligibleStatus | Number | Is the product eligible.1:ON;0:OFF. |
| productStockStatus | Number | Is the product have enougn stock.1:ON;0:OFF. |
| sellerEligibleStatus | Number | Is the seller eligible.1:ON;0:OFF. |
| itemId | Number | Product id. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/adgroup/searchAdgroupList)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/adgroup/searchAdgroupList

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/adgroup/searchAdgroupList");
request.addApiParameter("pageSize", "10");
request.addApiParameter("endDate", "2023-03-08");
request.addApiParameter("campaignId", "101100023522308");
request.addApiParameter("pageNo", "1");
request.addApiParameter("bizCode", "sponsoredSearch");
request.addApiParameter("adgroupName", "-");
request.addApiParameter("startDate", "2023-03-01");
request.addApiParameter("onlineStatus", "1");
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
      "unitsSold": "9",
      "productOrders": "1",
      "campaignSwitchStatus": "1",
      "adAccountBalanceStatus": "1",
      "revenue": "00",
      "adgroupId": "1385156242",
      "adgroupName": "TEST Lazmall item 1",
      "imageUrl": "https://sg-live-02.slatic.net/original/9a3a9c9defb21cb818142b1f0a188ab7.jpg",
      "spend": "14",
      "cpc": "-",
      "campaignScheduleStatus": "1",
      "adSwitchStatus": "1",
      "autoCreative": "1",
      "ctr": "-",
      "campaignDailyBudgetStatus": "1",
      "productEligibleStatus": "1",
      "sellerEligibleStatus": "1",
      "storeRevenue": "1400",
      "storeOrders": "2",
      "impressions": "10",
      "storeUnitsSold": "-",
      "bidPrice": "14.12",
      "audienceViewDTOList": [
        {
          "adCrowdTag": "1",
          "discount": "10"
        }
      ],
      "itemId": "30048182376",
      "storeRoi": "1",
      "productStockStatus": "1",
      "adApproveStatus": "1",
      "clicks": "140",
      "status": "1"
    }
  ],
  "code": "0",
  "success": "true",
  "analyseTraceId": "...",
  "totalCount": "100",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "Internal_error."
}
```
