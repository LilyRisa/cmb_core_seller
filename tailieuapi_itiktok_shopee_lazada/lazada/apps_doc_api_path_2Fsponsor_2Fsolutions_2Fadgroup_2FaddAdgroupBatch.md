# GET/POSTaddAdgroupBatch

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Fadgroup%2FaddAdgroupBatch
> API path: /sponsor/solutions/adgroup/addAdgroupBatch
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:02:55.399Z

---

Latest update2026-05-21 08:02:45

500

addAdgroupBatch

GET/POST

/sponsor/solutions/adgroup/addAdgroupBatch

Authorization Required

Description:Do add adgroup for one campaign.

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
| campaignId | Number | Yes | Campaign id which you want to add into. |
| bizCode | String | Yes | Decided to choose which advertisement solution.SD:sponsoredSearch. |
| adgroupViewDTOList | Object\[\] | Yes | Adgroup list |
| adgroupName | String | Yes | The adgroup name, normanlly is the product name. |
| autoItemSelect | String | Yes | The way the product be selected.1:manual(I want to select products manually from my store.);2:auto(Let Lazada optimize the products within the campaigns in real-time to maximize the campaigns' performance).This must be the same as the campaign. |
| bidPrice | String | Yes | Let Lazada automatically set cost-effective bid prices for your products. |
| itemId | Number | Yes | Product id. |
| autoCreative | Number | Yes | Let Lazada automatically set creatives for your products.1:ON;0:OFF.This must be the same as the campaign. |
| autoKeyword | Number | Yes | Let Lazada automatically set keyword for your products.1:manual(I want to select keywords manually for my product selection.);2:auto(Let Lazada optimize the keywords relating to your products in real time to maximize the campaigns' performance).This must be the same as the campaign. |
| bidwordViewDTOList | Object\[\] | No | Bid word list |
| keyword | String | No | The specific keyword.eg:shoe. |
| bidPrice | String | No | Let Lazada automatically set cost-effective bid prices for your products. |
| audienceViewDTOList | Object\[\] | No | This setting allows you to bid higher on premium audiences that are more likely to convert in your store. |
| adCrowdTag | Number | No | 1:on store visitors in the past 15 days;2:on in-market audiences for similar products;3:Store Awareness Audience;4:Store Interest Audience |
| discount | Number | No | The discount you want to give.eg:10 means 10% discount. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Boolean | The detail result, for this api is boolean. |
| success | Boolean | System result for this api call. |
| errorMsg | String | If the api call failed, this field will show the detail reason. |
| analyseTraceId | String | If the api call failed, you could find us with this. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/adgroup/addAdgroupBatch)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/adgroup/addAdgroupBatch

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/adgroup/addAdgroupBatch");
request.addApiParameter("campaignId", "101100023522312");
request.addApiParameter("bizCode", "sponsoredSearch");
request.addApiParameter("adgroupViewDTOList", "[{\"autoKeyword\":\"2\",\"audienceViewDTOList\":[{\"adCrowdTag\":\"1\",\"discount\":\"40\"},{\"adCrowdTag\":\"1\",\"discount\":\"40\"}],\"itemId\":\"3598680999\",\"bidwordViewDTOList\":[{\"keyword\":\"starbuck\",\"bidPrice\":\"40\"},{\"keyword\":\"starbuck\",\"bidPrice\":\"40\"}],\"adgroupName\":\"testcomlazPicks\",\"autoItemSelect\":\"1\",\"autoCreative\":\"1\",\"bidPrice\":\"2.57\"}]");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": "true",
  "code": "0",
  "success": "true",
  "analyseTraceId": "-",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "invalid param"
}
```
