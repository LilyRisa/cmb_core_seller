# GET/POSTServiceMarketAppKeySubQuery

> Source: https://open.lazada.com/apps/doc/api?path=%2Fservice%2Fmarket%2Fsubs%2Fquery
> API path: /service/market/subs/query
> Category: Service Market API
> Scraped: 2026-05-21T00:09:16.164Z

---

Latest update2026-05-21 08:09:09

500

ServiceMarketAppKeySubQuery

GET/POST

/service/market/subs/query

No Authorization Required

Description:Query user subscription info for specific App on Service Market

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
| access\_token | String | No | API interface call credentials |
| sign\_method | String | Yes | The HMAC hash algorithm you are using to calculate your signature |
| sign | String | Yes | Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details) |
## Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| articleCode | String | Yes | Service Market article code |
| shortCode | String | Yes | seller short code |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| data | Object\[\] | data |
| nick | String | seller nick |
| item\_name | String | item name |
| article\_name | String | article name |
| expire\_notice | Boolean | notice when subscription expired |
| item\_code | String | item code |
| autosub | Boolean | is auto sub |
| end\_time | Number | subscription end time |
| article\_code | String | article code |
| status | Number | 1=valid 2=expired |
| success | Boolean | is query success |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/service/market/subs/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/service/market/subs/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/service/market/subs/query");
request.addApiParameter("articleCode", "FW_GOODS-1000000281");
request.addApiParameter("shortCode", "A123BV12");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "data": [
      {
        "nick": "cn12345",
        "item_code": "FW_GOODS-1000000281-1",
        "expire_notice": "false",
        "end_time": "1669197617401",
        "article_name": "test",
        "item_name": "test-1",
        "autosub": "false",
        "article_code": "FW_GOODS-1000000281",
        "status": "1"
      }
    ],
    "success": "true"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
