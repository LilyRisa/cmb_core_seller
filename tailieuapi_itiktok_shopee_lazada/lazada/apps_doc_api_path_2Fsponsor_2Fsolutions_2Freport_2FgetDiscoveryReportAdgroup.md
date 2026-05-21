# GET/POSTgetDiscoveryReportAdgroup

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Freport%2FgetDiscoveryReportAdgroup
> API path: /sponsor/solutions/report/getDiscoveryReportAdgroup
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:05:07.705Z

---

Latest update2026-05-21 08:04:55

500

getDiscoveryReportAdgroup

GET/POST

/sponsor/solutions/report/getDiscoveryReportAdgroup

Authorization Required

Description:Get sponsored discovery report adgroup level

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
| campaignType | String | No | Campaign Type,1 standard 2 automated |
| campaignName | String | No | Campaign Name, frazzy search |
| campaignId | String | No | Campaign Id |
| adgroupName | String | No | Adgroup Name |
| adgroupId | String | No | Adgroup Id |
| itemId | String | No | Item Id |
| useRtTable | Boolean | No | It means that if endDate have selected today, and you need realtime data,then set useRtTable=true If useRtTable=false,it will not search realtime data |
| sort | String | No | sort column,we have provide some index to sort |
| pageNo | String | Yes | Page No，default 1,max=100 |
| pageSize | String | Yes | Page No, default 10, max=100 |
| order | String | No | ASC or DESC, other String is invalid |
| startDate | String | Yes | start date, format like yyyy-MM-dd |
| endDate | String | Yes | end date , date, format like yyyy-MM-dd |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | Result Details |
| result | Object\[\] | Details of each adgroup |
| dateRange | String | Unused |
| productUnitSold | Number | Product Unit Sold |
| productCvr | String | Product CVR |
| productOrders | Number | Product Orders |
| adgroupId | Number | Adgroup Id |
| adgroupName | String | Adgroup Name,if query duration include current Date or yesterday, the adgroup name maybe is null because of real time data logic. The best way to get the adgroup name is to query adgroup info and cache it. |
| cpc | String | CPC |
| spend | String | Spend |
| storeUnitSold | Number | Store Unit Sold |
| productA2c | Number | Product A2C |
| productImageUrl | String | Unused |
| ctr | String | CTR |
| campaignId | Number | Campaign Id |
| storeRevenue | String | Store Revenue |
| storeCvr | String | Store CVR |
| storeA2c | Number | Store A2C |
| storeOrders | Number | Store Orders |
| impressions | Number | Impressions |
| bidPrice | String | Unused |
| itemId | Number | Item Id |
| storeRoi | String | Store ROI |
| maxBid | Number | Max Bid |
| clicks | Number | Clicks |
| productRevenue | String | Product Revenue |
| campaignName | String | Campagin Name,if query duration include current Date or yesterday, the campaign name maybe is null because of real time data logic. The best way to get the campaign name is to query campaign info and cache it. |
| errorKey | String | Unused |
| errorDTOList | Object\[\] | Unused |
| success | Boolean | true:success false:fail |
| analyseTraceId | String | Unused |
| errorCode | String | Error Code |
| totalCount | Number | Total count of the search record |
| errorMsg | String | Error Message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/report/getDiscoveryReportAdgroup)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/report/getDiscoveryReportAdgroup

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/report/getDiscoveryReportAdgroup");
request.addApiParameter("campaignType", "1");
request.addApiParameter("campaignName", "test");
request.addApiParameter("campaignId", "101100033026398");
request.addApiParameter("adgroupName", "test");
request.addApiParameter("adgroupId", "2281484260");
request.addApiParameter("itemId", "2281484260");
request.addApiParameter("useRtTable", "true");
request.addApiParameter("sort", "impressions");
request.addApiParameter("pageNo", "1");
request.addApiParameter("pageSize", "10");
request.addApiParameter("order", "ASC");
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
        "dateRange": "2023-12-01",
        "productUnitSold": "33",
        "productCvr": "0.0497",
        "productOrders": "33",
        "adgroupId": "1995566028",
        "adgroupName": "[Value Pack] Caltrate 600 Plus Calcium Dietary Supplement For Bone Health With Vitamin D \u0026 Minerals (2 x 100\u0027s)",
        "cpc": "0.2",
        "spend": "130.01",
        "storeUnitSold": "83",
        "productA2c": "105",
        "productImageUrl": "null",
        "ctr": "2.21",
        "campaignId": "101100010112323",
        "storeRevenue": "3842.65",
        "storeCvr": "0.0587",
        "storeA2c": "147",
        "storeOrders": "39",
        "impressions": "29985",
        "bidPrice": "null",
        "itemId": "2342883060",
        "storeRoi": "29.56",
        "maxBid": "18",
        "clicks": "664",
        "productRevenue": "3392.42",
        "campaignName": "Caltrate SP (Manual)"
      }
    ],
    "errorKey": "null",
    "errorDTOList": [],
    "success": "true",
    "analyseTraceId": "null",
    "errorCode": "null",
    "totalCount": "436",
    "errorMsg": "null"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
