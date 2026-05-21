# GET/POSTgetDiscoveryReportAudience

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Freport%2FgetDiscoveryReportAudience
> API path: /sponsor/solutions/report/getDiscoveryReportAudience
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:05:18.799Z

---

Latest update2026-05-21 08:05:10

500

getDiscoveryReportAudience

GET/POST

/sponsor/solutions/report/getDiscoveryReportAudience

Authorization Required

Description:Get sponsored discovery report audience level

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
| campaignName | String | No | Campaign Name |
| campaignId | Number | No | Campaign Id |
| audienceGroup | Number | No | Audienct type 1:15 days Visitors 2:Similar Product Visitors 3:Store Awareness Audience 4:Store Interest Audience 5:DMP Crow Audience 6:Gender 7:Age |
| sort | String | No | sort column,we have provide some index to sort |
| order | String | No | ASC or DESC, other String is invalid |
| pageNo | Number | Yes | Page No，default 1,max=100 |
| pageSize | Number | Yes | Page No, default 10, max=100 |
| startDate | String | Yes | start date, format like yyyy-MM-dd |
| endDate | String | Yes | end date , date, format like yyyy-MM-dd |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | Details |
| result | Object\[\] | Details of each record |
| productImageUrl | String | Product Image URL |
| ctr | String | CTR |
| campaignId | Number | Campaign Id |
| storeRevenue | String | Store Revenue |
| storeCvr | String | Store CVR |
| storeA2c | Number | Store A2C |
| storeOrders | Number | Store Orders |
| productUnitSold | Number | Product Unit Sold |
| impressions | Number | Impressions |
| productCvr | String | Product CVR |
| productOrders | Number | Product Orders |
| audienceFakeId | String | Audience Fake Id |
| storeRoi | String | Store ROI |
| adgroupId | Number | Adgroup Id |
| audienceGroup | Number | Audience Group |
| adgroupName | String | Adgroup Name,if query duration include current Date or yesterday, the adgroup name maybe is null because of real time data logic. The best way to get the adgroup name is to query adgroup info and cache it. |
| cpc | String | CPC |
| spend | String | Spend |
| clicks | Number | Clicks |
| productRevenue | String | Product Revenue |
| storeUnitSold | Number | Store Unit Sold |
| campaignName | String | Campaign Name,if query duration include current Date or yesterday, the campaign name maybe is null because of real time data logic. The best way to get the campaign name is to query campaign info and cache it. |
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
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/report/getDiscoveryReportAudience)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/report/getDiscoveryReportAudience

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/report/getDiscoveryReportAudience");
request.addApiParameter("campaignName", "test");
request.addApiParameter("campaignId", "101100033026398");
request.addApiParameter("audienceGroup", "1");
request.addApiParameter("sort", "impressions");
request.addApiParameter("order", "ASC");
request.addApiParameter("pageNo", "1");
request.addApiParameter("pageSize", "100");
request.addApiParameter("startDate", "2023-11-12");
request.addApiParameter("endDate", "2023-11-13");
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
        "productImageUrl": "null",
        "ctr": "2.58",
        "campaignId": "101100010112323",
        "storeRevenue": "1448.37",
        "storeCvr": "0.0654",
        "storeA2c": "67",
        "storeOrders": "17",
        "productUnitSold": "13",
        "impressions": "10067",
        "productCvr": "0.05",
        "productOrders": "13",
        "audienceFakeId": "1995526319_5_18813978",
        "storeRoi": "26.28",
        "adgroupId": "1995526319",
        "audienceGroup": "5",
        "adgroupName": "Caltrate 600 Calcium Dietary Supplement For Bone Health Value Pack (60\u0027s x 4)",
        "cpc": "0.21",
        "spend": "55.12",
        "clicks": "260",
        "productRevenue": "1298.45",
        "storeUnitSold": "32",
        "campaignName": "Caltrate SP (Manual)",
        "productA2c": "32"
      }
    ],
    "errorKey": "null",
    "errorDTOList": [],
    "success": "true",
    "analyseTraceId": "null",
    "errorCode": "null",
    "totalCount": "24",
    "errorMsg": "null"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
