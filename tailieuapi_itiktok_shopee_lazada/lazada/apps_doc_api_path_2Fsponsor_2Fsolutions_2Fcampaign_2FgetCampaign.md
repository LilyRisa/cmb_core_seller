# GET/POSTgetCampaign

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Fcampaign%2FgetCampaign
> API path: /sponsor/solutions/campaign/getCampaign
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:04:40.482Z

---

Latest update2026-05-21 08:04:27

500

getCampaign

GET/POST

/sponsor/solutions/campaign/getCampaign

Authorization Required

Description:Get campaign list with bizCode by seller.

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
| bizCode | String | Yes | Discovery:sponsoredSearch |
| campaignId | Number | Yes | 123 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | The detail result, for this api is campaign detail info. |
| endDate | String | Campaign end date. |
| onlineStatus | Number | The campaign online status.1:Online;0:Offline;9:deleted. |
| campaignObjective | Number | Your campaign objective helps determine your bidding strategy - Traffic objective helps you to increase the number of clicks to your store, while sales objective helps to increase your store’s sales. |
| campaignType | Number | Unlock different ways to bids, select products, and keywords with campaign types. |
| campaignId | Number | Campaign id. |
| budgetUsedAmount | Number | Today used amount of daily budget. |
| autoItemSelect | Number | The way the product be selected.1:manual(I want to select products manually from my store.);2:auto(Let Lazada optimize the products within the campaigns in real-time to maximize the campaigns' performance) |
| haveAdCount | Number | The count of adgroup of this campaign. |
| startDate | String | Campaign start date. |
| switchStatus | Number | Is the campaign on rightnow.1:ON:0:OFF. |
| platform | Number\[\] | Placements determine where shoppers will see your promoted products. |
| sceneId | Number | Fine granularity to discriminate solutions.0:SD; |
| autoCreative | Number | Let Lazada automatically set creatives for your products.1:ON;0:OFF. |
| campaignModel | Number | To discriminate solutions.99:SD+NPL. |
| maxBid | String | Max bid determines the highest amount that you're willing to pay for a click on your promoted product. |
| dayBudget | String | Budget indicates the maximum amount you’re willing to pay each day. |
| campaignName | String | campaignName |
| success | String | System result for this api call. |
| errorMsg | String | If the api call failed, this field will show the detail reason. |
| analyseTraceId | String | If the api call failed, you could find us with this. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/campaign/getCampaign)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/campaign/getCampaign

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/campaign/getCampaign");
request.addApiParameter("bizCode", "sponsoredSearch");
request.addApiParameter("campaignId", "123");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "campaignObjective": "1",
    "campaignType": "1",
    "endDate": "3020-12-30",
    "campaignId": "101100024772415",
    "onlineStatus": "1",
    "switchStatus": "1",
    "platform": [],
    "budgetUsedAmount": "0",
    "autoItemSelect": "1",
    "campaignModel": "99",
    "maxBid": "-1",
    "haveAdCount": "1",
    "sceneId": "0",
    "autoCreative": "1",
    "campaignName": "Campaign_2023_04_07_15:17",
    "startDate": "2023-04-07",
    "dayBudget": "25.00"
  },
  "code": "0",
  "success": "true",
  "analyseTraceId": "...",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "Invalid param."
}
```
