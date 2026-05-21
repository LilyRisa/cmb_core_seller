# GET/POSTgetDiscoveryReportKeyword

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Freport%2FgetDiscoveryReportKeyword
> API path: /sponsor/solutions/report/getDiscoveryReportKeyword
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:05:45.652Z

---

Latest update2026-05-21 08:05:32

500

getDiscoveryReportKeyword

GET/POST

/sponsor/solutions/report/getDiscoveryReportKeyword

Authorization Required

Description:Get sponsored discovery report keyword level

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
| adgroupName | String | No | Adgroup Name |
| adgroupId | String | No | Adgroup Id |
| keyword | String | No | Keyword |
| useRtTable | Boolean | No | It means that if endDate have selected today, and you need realtime data,then set useRtTable=true If useRtTable=false,it will not search realtime data |
| sort | String | No | sort column,we have provide some index to sort |
| order | String | No | ASC or DESC, other String is invalid |
| pageNo | String | Yes | Page No，default 1,max=100 |
| pageSize | String | Yes | Page No, default 10, max=100 |
| startDate | String | Yes | start date, format like yyyy-MM-dd |
| endDate | String | Yes | end date , date, format like yyyy-MM-dd |
| campaignName | String | No | Campaign Name |
| campaignId | String | No | Campaign Id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | Result Details |
| result | Object\[\] | Details of each Record |
| productImageUrl | String | Product Image URL |
| ctr | String | CTR |
| keywordId | Number | Keyword ID |
| campaignId | Number | Campaign Id |
| storeRevenue | String | Store Revenue |
| storeCvr | String | Store CVR |
| storeA2c | Number | Store A2C |
| storeOrders | Number | Store Orders |
| productUnitSold | Number | Product Unit Sold |
| impressions | Number | Impressions |
| productCvr | String | Product CVR |
| productOrders | Number | Product Orders |
| storeRoi | String | Store Roi |
| adgroupId | Number | Adgroup Id |
| adgroupName | String | Adgroup Name,if query duration include current Date or yesterday, the adgroup name maybe is null because of real time data logic. The best way to get the adgroup name is to query adgroup info and cache it. |
| cpc | String | CPC |
| spend | String | Spend |
| maxBid | String | Max Bid |
| storeUnitSold | Number | Store Unit Sold |
| clicks | Number | Clicks |
| productRevenue | String | Product Revenue |
| keyword | String | Keyword |
| campaignName | String | Campaign Name,if query duration include current Date or yesterday, the campaign name maybe is null because of real time data logic. The best way to get the campaign name is to query campaign info and cache it. |
| productA2c | Number | Product A2C |
| errorKey | String | Unused |
| errorDTOList | Object\[\] | Unused |
| success | Boolean | true:success false:fail |
| analyseTraceId | String | Unused |
| errorCode | String | Error Code |
| totalCount | Number | Total count of search rescord |
| errorMsg | String | Error Msg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/report/getDiscoveryReportKeyword)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/report/getDiscoveryReportKeyword

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/report/getDiscoveryReportKeyword");
request.addApiParameter("adgroupName", "test");
request.addApiParameter("adgroupId", "2281484260");
request.addApiParameter("keyword", "test");
request.addApiParameter("useRtTable", "true");
request.addApiParameter("sort", "impressions");
request.addApiParameter("order", "ASC");
request.addApiParameter("pageNo", "1");
request.addApiParameter("pageSize", "100");
request.addApiParameter("startDate", "2023-11-12");
request.addApiParameter("endDate", "2023-11-13");
request.addApiParameter("campaignName", "test");
request.addApiParameter("campaignId", "101100033026398");
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
        "productImageUrl": "https://my-live.slatic.net/p/c4c943d4b80e153aa65867defe74f04d.jpg",
        "ctr": "0.48",
        "keywordId": "46295804448",
        "campaignId": "101100009688305",
        "storeRevenue": "17.5",
        "storeCvr": "0.0161",
        "storeA2c": "8",
        "storeOrders": "1",
        "productUnitSold": "1",
        "impressions": "13045",
        "productCvr": "0.0161",
        "productOrders": "1",
        "storeRoi": "0.23",
        "adgroupId": "1994950098",
        "adgroupName": "Scott\u0027s Multivitamin Gummies - Mango (15’s)",
        "cpc": "1.25",
        "spend": "77.79",
        "maxBid": "1.14",
        "storeUnitSold": "1",
        "clicks": "62",
        "productRevenue": "17.5",
        "keyword": "vitamin c kanak kanak",
        "campaignName": " Scotts Search (Manual)",
        "productA2c": "2"
      }
    ],
    "errorKey": "null",
    "errorDTOList": [],
    "success": "true",
    "analyseTraceId": "null",
    "errorCode": "null",
    "totalCount": "1",
    "errorMsg": "null"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
