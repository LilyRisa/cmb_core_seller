# GET/POSTgetReportCampaignOnFIrstSlot

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Freport%2FgetReportCampaignOnPrePlacement
> API path: /sponsor/solutions/report/getReportCampaignOnPrePlacement
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:06:02.800Z

---

Latest update2026-05-21 08:05:57

500

getReportCampaignOnFIrstSlot

GET/POST

/sponsor/solutions/report/getReportCampaignOnPrePlacement

Authorization Required

Description:Get sponsored discovery report campaign first slot

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
| sort | String | No | sort column,we have provide some index to sort |
| order | String | No | ASC or DESC, other String is invalid |
| pageNo | Number | Yes | Page No，default 1,max=100 |
| pageSize | Number | Yes | Page Size, default 10, max=100 |
| startDate | String | Yes | start date, format like yyyy-MM-dd |
| endDate | String | Yes | end date , date, format like yyyy-MM-dd |
| campaignName | String | No | Campaign Name |
| campaignId | Number | No | campagnId |
| productType | String | No | Product Type, N:Sponsored Search(All) F:Firsh Search Slot |
| useRtTable | Boolean | No | It means that if endDate have selected today, and you need realtime data,then set useRtTable=true If useRtTable=false,it will not search realtime data |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | Details |
| result | Object\[\] | Details of each record |
| ctr | String | CTR |
| campaignObjective | String | Unused |
| campaignType | Number | Campaign Type 1 Manual 2 Automated |
| firstImpShare | String | First Imp Share |
| campaignId | Number | Campaign Id |
| storeRevenue | String | Store Revenue |
| storeCvr | String | Store CVR |
| storeA2c | Number | Store A2C |
| storeOrders | Number | Store Orders |
| productUnitSold | Number | Product Unit Sold |
| impressions | Number | Impressions |
| productCvr | String | Product CVR |
| productOrders | Number | Product Orders |
| storeRoi | String | Store ROI |
| cpc | String | CPC |
| spend | String | Spend |
| clicks | Number | Clicks |
| productRevenue | String | Product Revenue |
| storeUnitSold | Number | Store Unit Sold |
| campaignName | String | Campagin Name,if query duration include current Date or yesterday, the campaign name maybe is null because of real time data logic. The best way to get the campaign name is to query campaign info and cache it. |
| productType | String | Product Type |
| dayBudget | Number | Day Budget |
| productA2c | Number | Product A2C |
| errorKey | String | Unused |
| errorDTOList | Object\[\] | Unused |
| success | Boolean | true:success false:fail |
| analyseTraceId | String | Analyse Trace Id |
| errorCode | String | Error Code |
| totalCount | Number | Total Count |
| errorMsg | String | Error Message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/report/getReportCampaignOnPrePlacement)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/report/getReportCampaignOnPrePlacement

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/report/getReportCampaignOnPrePlacement");
request.addApiParameter("sort", "impressions");
request.addApiParameter("order", "ASC");
request.addApiParameter("pageNo", "1");
request.addApiParameter("pageSize", "100");
request.addApiParameter("startDate", "2023-11-12");
request.addApiParameter("endDate", "2023-11-13");
request.addApiParameter("campaignName", "test");
request.addApiParameter("campaignId", "101100033026398");
request.addApiParameter("productType", "F");
request.addApiParameter("useRtTable", "true");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "result": [
      {
        "ctr": "3.55",
        "campaignObjective": "null",
        "campaignType": "2",
        "firstImpShare": "null",
        "campaignId": "101100030626143",
        "storeRevenue": "45348.59",
        "storeCvr": "0.1398",
        "storeA2c": "3851",
        "storeOrders": "915",
        "productUnitSold": "1160",
        "impressions": "184528",
        "productCvr": "0.0958",
        "productOrders": "627",
        "storeRoi": "15.49",
        "cpc": "0.45",
        "spend": "2928.51",
        "clicks": "6543",
        "productRevenue": "32151.02",
        "storeUnitSold": "2272",
        "campaignName": "12.12 2023",
        "productType": "ALL",
        "dayBudget": "314",
        "productA2c": "2280"
      }
    ],
    "errorKey": "null",
    "errorDTOList": [],
    "success": "true",
    "analyseTraceId": "null",
    "errorCode": "null",
    "totalCount": "17",
    "errorMsg": "null"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
