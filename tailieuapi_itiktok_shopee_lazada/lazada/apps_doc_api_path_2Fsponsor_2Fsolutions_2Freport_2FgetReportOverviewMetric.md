# GET/POSTgetReportOverviewMetric

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Freport%2FgetReportOverviewMetric
> API path: /sponsor/solutions/report/getReportOverviewMetric
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:06:25.615Z

---

Latest update2026-05-21 08:06:20

500

getReportOverviewMetric

GET/POST

/sponsor/solutions/report/getReportOverviewMetric

Authorization Required

Description:get report overview metric

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
| metricType | Number | Yes | The type pf metric.1:spend;2:impressions;3:clicks;4:ctr;5:units sold;6:revenue;7:cpc;8:roi;9:store order;10:store a2c;11:product order. |
| endDate | String | Yes | End date. |
| useRtTable | Boolean | Yes | If you need to search data for today, then use true, otherwise false. |
| bizCode | String | Yes | Decided to choose which advertisement solution.SD:sponsoredSearch. |
| startDate | String | Yes | Start date. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | The detail result, for this api is metric data. |
| dateList | Number\[\] | Timelime for horizontal axis. |
| hourList | Number\[\] | Timelime for horizontal axis.Only when search date is today. |
| metricList | String\[\] | The detail metric data for longitudinal axis. |
| success | String | System result for this api call. |
| analyseTraceId | String | If the api call failed, you could find us with this. |
| errorMsg | String | If the api call failed, this field will show the detail reason. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/report/getReportOverviewMetric)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/report/getReportOverviewMetric

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/report/getReportOverviewMetric");
request.addApiParameter("metricType", "1");
request.addApiParameter("endDate", "2023-03-08");
request.addApiParameter("useRtTable", "false");
request.addApiParameter("bizCode", "sponsoredSearch");
request.addApiParameter("startDate", "2023-03-01");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "metricList": [
      0,
      0,
      0,
      0,
      0,
      0,
      0
    ],
    "dateList": [
      1680451200000,
      1680537600000,
      1680624000000,
      1680710400000,
      1680796800000,
      1680883200000,
      1680969600000
    ],
    "hourList": []
  },
  "code": "0",
  "success": "true",
  "analyseTraceId": "...",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "INTERNAL_ERROR"
}
```
