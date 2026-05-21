# GET/POSTgetReportOverview

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Freport%2FgetReportOverview
> API path: /sponsor/solutions/report/getReportOverview
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:06:17.836Z

---

Latest update2026-05-21 08:06:05

500

getReportOverview

GET/POST

/sponsor/solutions/report/getReportOverview

Authorization Required

Description:Get report overview.

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
| lastStartDate | String | Yes | \- |
| endDate | String | Yes | \- |
| useRtTable | Boolean | Yes | \- |
| bizCode | String | Yes | \- |
| lastEndDate | String | Yes | \- |
| startDate | String | Yes | \- |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | The detail data. |
| reportOverviewDetailDTO | Object | Today's data. |
| spend | String | Spend is the total amount spent. |
| impressions | Number | An impression is counted when your promoted product is shown. Impressions help you understand how often your promoted product is being seen. |
| clicks | Number | A click is counted when someone clicks on your promoted product. |
| ctr | String | Clickthrough rate (CTR) measures how often people click on your promoted product after it's shown to them, which can help you understand the effectiveness of your promoted product. |
| unitsSold | Number | The total number of units sold after someone clicks your promoted product. |
| revenue | String | The revenue generated from units sold after someone clicks your promoted product. It is the total buyer paid amount inclusive of all discounts applied, store credit, shipping fees and surcharges. |
| cpc | String | The cost-per-click (CPC) is the average amount you pay when someone clicks your promoted product. |
| roi | String | The store's return on investment (ROI) shows how efficient your Sponsored Discovery's spend is in driving revenue for your store. |
| lastReportOverviewDetailDTO | Object | Yestoday's data. |
| spend | String | Spend is the total amount spent. |
| impressions | Number | An impression is counted when your promoted product is shown. Impressions help you understand how often your promoted product is being seen. |
| clicks | Number | A click is counted when someone clicks on your promoted product. |
| ctr | String | Clickthrough rate (CTR) measures how often people click on your promoted product after it's shown to them, which can help you understand the effectiveness of your promoted product. |
| unitsSold | Number | The total number of units sold after someone clicks your promoted product. |
| revenue | String | The revenue generated from units sold after someone clicks your promoted product. It is the total buyer paid amount inclusive of all discounts applied, store credit, shipping fees and surcharges. |
| cpc | String | The cost-per-click (CPC) is the average amount you pay when someone clicks your promoted product. |
| roi | String | The store's return on investment (ROI) shows how efficient your Sponsored Discovery's spend is in driving revenue for your store. |
| success | String | System result for this api call. |
| analyseTraceId | String | If the api call failed, you could find us with this. |
| errorMsg | String | If the api call failed, this field will show the detail reason. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/report/getReportOverview)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/report/getReportOverview

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/report/getReportOverview");
request.addApiParameter("lastStartDate", "2023-03-01");
request.addApiParameter("endDate", "2023-03-08");
request.addApiParameter("useRtTable", "false");
request.addApiParameter("bizCode", "sponsoredSearch");
request.addApiParameter("lastEndDate", "2023-03-07");
request.addApiParameter("startDate", "2023-03-02");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "lastReportOverviewDetailDTO": {
      "ctr": "0",
      "revenue": "0",
      "spend": "0",
      "unitsSold": "0",
      "cpc": "0",
      "clicks": "0",
      "impressions": "0",
      "roi": "0"
    },
    "reportOverviewDetailDTO": {
      "ctr": "0",
      "revenue": "99",
      "spend": "0",
      "unitsSold": "0",
      "cpc": "0",
      "clicks": "9",
      "impressions": "100",
      "roi": "0"
    }
  },
  "code": "0",
  "success": "-",
  "analyseTraceId": "-",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "-"
}
```
