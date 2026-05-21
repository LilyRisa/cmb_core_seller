# GET/POSTgetDiscoveryReportCampaign

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Freport%2FgetDiscoveryReportCampaign
> API path: /sponsor/solutions/report/getDiscoveryReportCampaign
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:05:29.490Z

---

Latest update2026-05-21 08:05:20

500

getDiscoveryReportCampaign

GET/POST

/sponsor/solutions/report/getDiscoveryReportCampaign

Authorization Required

Description:Get sponsored discovery report campaign level

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
| campaignId | Number | No | Campaign Id |
| useRtTable | Boolean | No | It means that if endDate have selected today, and you need realtime data,then set useRtTable=true If useRtTable=false,it will not search realtime data |
| sort | String | No | sort column,we have provide some index to sort |
| order | String | No | ASC or DESC, other String is invalid |
| startDate | String | Yes | start date, format like yyyy-MM-dd |
| endDate | String | Yes | end date , date, format like yyyy-MM-dd |
| pageNo | String | Yes | Page No，default 1,max=100 |
| pageSize | String | Yes | Page No, default 10, max=100 |
| campaignType | Number | No | Campaign type, 1 Manual 2 Automated |
| productType | String | No | Placement , N Sponsored Search, J Sponsored Product |
| campaignName | String | No | campaign name，fuzzy search |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | The Details |
| result | Object\[\] | The details of each campaign |
| ctr | String | CTR |
| campaignObjective | String | Unused |
| campaignType | Number | Campaign Type, 1 Manual 2 Automated |
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
| campaignName | String | Campaign Name, if query duration include today or yesterday, the campaign name maybe is null because of real time data logic. The best way to get the campaign name is to query campaign info and cache it. |
| productType | String | Product Type, N Sponsored Search;J Sponsored Product |
| dayBudget | Number | Campaign Daily Budget |
| productA2c | Number | Product A2C |
| errorKey | String | Unused |
| errorDTOList | Object\[\] | Unused |
| success | Boolean | true means query success; false means fail |
| analyseTraceId | String | Unused |
| errorCode | String | Error Code |
| totalCount | Number | Total count by search param |
| errorMsg | String | Error Message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/report/getDiscoveryReportCampaign)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/report/getDiscoveryReportCampaign

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/report/getDiscoveryReportCampaign");
request.addApiParameter("campaignId", "101100033026398");
request.addApiParameter("useRtTable", "true");
request.addApiParameter("sort", "impressions");
request.addApiParameter("order", "ASC");
request.addApiParameter("startDate", "2023-11-12");
request.addApiParameter("endDate", "2023-11-13");
request.addApiParameter("pageNo", "1");
request.addApiParameter("pageSize", "10");
request.addApiParameter("campaignType", "1");
request.addApiParameter("productType", "N");
request.addApiParameter("campaignName", "test");
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
        "ctr": "6.86",
        "campaignObjective": "null",
        "campaignType": "1",
        "campaignId": "101100021686346",
        "storeRevenue": "451.48",
        "storeCvr": "0.1028",
        "storeA2c": "61",
        "storeOrders": "11",
        "productUnitSold": "18",
        "impressions": "1560",
        "productCvr": "0.0654",
        "productOrders": "7",
        "storeRoi": "6.77",
        "cpc": "0.62",
        "spend": "66.68",
        "clicks": "107",
        "productRevenue": "243.66",
        "storeUnitSold": "32",
        "campaignName": "Parodontax Search (Manual)",
        "productType": "N",
        "dayBudget": "10",
        "productA2c": "35"
      }
    ],
    "errorKey": "null",
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
