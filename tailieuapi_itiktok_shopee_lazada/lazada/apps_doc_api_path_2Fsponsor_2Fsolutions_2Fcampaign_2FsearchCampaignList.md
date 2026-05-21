# GET/POSTsearchCampaignList

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Fcampaign%2FsearchCampaignList
> API path: /sponsor/solutions/campaign/searchCampaignList
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:07:39.972Z

---

Latest update2026-05-21 08:07:26

500

searchCampaignList

GET/POST

/sponsor/solutions/campaign/searchCampaignList

Authorization Required

Description:Search campaign list with bizCode for sellers.

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
| onlineStatus | Number | No | The campaign online status.1:Online;0:Offline;9:deleted. |
| startDate | String | Yes | Campaign start date. |
| endDate | String | Yes | Campaign end date. |
| pageNo | String | Yes | Page number. |
| pageSize | String | Yes | Page size. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | System result for this api call. |
| totalCount | Number | Campaign total count. |
| errorMsg | String | If the api call failed, this field will show the detail reason. |
| analyseTraceId | String | If the api call failed, you could find us with this. |
| result | Object\[\] | The detail campaign list. |
| impressions | String | An impression is counted each time your promoted product is shown. Impressions help you understand how often your product is being seen.The impression of the campaign during startDate-endDate.String type..If the value is '-', it means no data. |
| clicks | String | Clickthrough rate (CTR) is the ratio showing how often people who see your promoted product end up clicking on it. It’s calculated as Clicks divided by Impressions.The click of the campaign during startDate-endDate.String type..If the value is '-', it means no data. |
| ctr | String | Clickthrough rate (CTR) is the ratio showing how often people who see your promoted product end up clicking on it. It’s calculated as Clicks divided by Impressions..If the value is '-', it means no data. |
| cpc | String | The cost-per-click (CPC) is the average amount you pay each time someone clicks your promoted product. It’s calculated as Spend divided by Clicks..If the value is '-', it means no data. |
| storeUnitsSold | String | The total number of units sold after the shoppers click on your promoted product during the selected date range..If the value is '-', it means no data. |
| storeOrders | String | The total number of orders from your store during the selected time period, after someone clicks on your promoted product..If the value is '-', it means no data. |
| storeRevenue | String | Total store revenue is generated from the units sold in your store after buyer(s) click your promoted product(s). It is the total amount paid by the buyer plus all discounts applied, store credit, shipping fees, and surcharges. |
| storeRoi | String | The store's return on investment (ROI) shows how efficient your Sponsored Discovery's spend is in driving revenue for your store. |
| campaignId | Number | The campaign id. |
| campaignName | String | The campaign name. |
| dailyBudget | String | The budget shows your campaign's daily budget. |
| startDate | String | Campaign start date. |
| endDate | String | Campaign end date. |
| status | String | The campaign status,this is a combination of 5 status include balance,budget,schedule,swtich,products.1:ON:0:OFF. |
| adAccountBalanceStatus | String | Is the seller have enough balance in wallet.1:ON:0:OFF. |
| campaignDailyBudgetStatus | String | Is the campaign hava enough realtime budget today.1:ON:0:OFF. |
| campaignScheduleStatus | String | Is the campaign in right schedule.1:ON:0:OFF. |
| campaignSwitchStatus | String | Is the campaign on rightnow.1:ON:0:OFF. |
| haveActiveAdStatus | String | Is the campaign hava at least 1 active product.1:ON:0:OFF. |
| spend | String | Spend is the total amount of your spend.The spend of the campaign during startDate-endDate.String type.If the value is '-', it means no data. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/campaign/searchCampaignList)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/campaign/searchCampaignList

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/campaign/searchCampaignList");
request.addApiParameter("bizCode", "sponsoredSearch");
request.addApiParameter("onlineStatus", "1");
request.addApiParameter("startDate", "2023-03-01");
request.addApiParameter("endDate", "2023-05-01");
request.addApiParameter("pageNo", "1");
request.addApiParameter("pageSize", "10");
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
      "ctr": "4",
      "campaignDailyBudgetStatus": "1",
      "endDate": "2023-05-01",
      "storeRevenue": "199",
      "campaignId": "101100024476086",
      "storeOrders": "19",
      "impressions": "200",
      "storeUnitsSold": "9",
      "campaignSwitchStatus": "1",
      "adAccountBalanceStatus": "1",
      "storeRoi": "-",
      "dailyBudget": "25",
      "cpc": "2",
      "spend": "100",
      "campaignScheduleStatus": "1",
      "clicks": "400",
      "campaignName": "myCampaign_20230301",
      "haveActiveAdStatus": "1",
      "startDate": "2023-03-01",
      "status": "1"
    }
  ],
  "code": "0",
  "success": "true",
  "analyseTraceId": "...",
  "totalCount": "100",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "invalid param"
}
```
