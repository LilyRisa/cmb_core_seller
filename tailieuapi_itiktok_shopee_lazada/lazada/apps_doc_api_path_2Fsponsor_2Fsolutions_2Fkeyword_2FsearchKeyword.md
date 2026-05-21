# GET/POSTsearchKeyword

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Fkeyword%2FsearchKeyword
> API path: /sponsor/solutions/keyword/searchKeyword
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:07:55.457Z

---

Latest update2026-05-21 08:07:42

500

searchKeyword

GET/POST

/sponsor/solutions/keyword/searchKeyword

Authorization Required

Description:Search keyword with specific word.

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
| campaignObjective | Number | Yes | Your campaign objective helps determine your bidding strategy - Traffic objective helps you to increase the number of clicks to your store, while sales objective helps to increase your store’s sales.1:Traffic;2:Sales. |
| campaignType | Number | Yes | Unlock different ways to bids, select products, and keywords with campaign types. |
| bizCode | String | Yes | Decided to choose which advertisement solution.SD:sponsoredSearch. |
| itemQuery | String | Yes | The word you do not want to put in the result. |
| itemId | Number | Yes | Product id. |
| searchWord | String | Yes | The specific word. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object\[\] | The keyword detail. |
| keyword | String | Keyword. |
| relevance | Number | Based on our prediction of the likelihood of shoppers clicking on the promoted product(s). Higher relevance drives better campaign performance. |
| historicalPV | Number | The number of searches a keyword gets in the last 30 days on average. |
| suggestedPrice | String | The suggested bid is calculated based on the average market winning bid for the keyword and the level of competition of the keyword in the market |
| currency | String | Local currency. |
| reservePrice | String | The hard limit of lower price. |
| softLowerLimit | String | The soft limit of lower price. |
| softUpperLimit | String | The soft limit of upper price. |
| softUpperLimitType | Number | 1:Bid is far higher than market price;2:Bid is too high due to current ads account credit. |
| success | Boolean | System result for this api call. |
| analyseTraceId | String | If the api call failed, you could find us with this. |
| totalCount | Number | Total count of keyword. |
| errorMsg | String | If the api call failed, this field will show the detail reason. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/keyword/searchKeyword)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/keyword/searchKeyword

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/keyword/searchKeyword");
request.addApiParameter("campaignObjective", "2");
request.addApiParameter("campaignType", "1");
request.addApiParameter("bizCode", "sponsoredSearch");
request.addApiParameter("itemQuery", "-");
request.addApiParameter("itemId", "123");
request.addApiParameter("searchWord", "shirt");
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
      "suggestedPrice": "2",
      "reservePrice": "0.48",
      "currency": "PHP",
      "softLowerLimit": "1.02",
      "keyword": "Nike",
      "softUpperLimit": "2",
      "relevance": "1",
      "softUpperLimitType": "1",
      "historicalPV": "1062"
    }
  ],
  "code": "0",
  "success": "true",
  "analyseTraceId": "...",
  "totalCount": "19",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "-"
}
```
