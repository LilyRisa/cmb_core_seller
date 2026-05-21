# GET/POSTBatchQueryFollowStatus

> Source: https://open.lazada.com/apps/doc/api?path=%2Fshop%2Ffollow%2Fstatus%2Fbatch%2Fquery
> API path: /shop/follow/status/batch/query
> Category: Seller API
> Scraped: 2026-05-20T23:02:40.060Z

---

Latest update2022-07-28 16:51:46

12294

BatchQueryFollowStatus

GET/POST

/shop/follow/status/batch/query

Authorization Required

Description:Query whether these customers follow this seller.

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
| buyer\_ids | String\[\] | Yes | buyerId array |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | Rensponse WrapperClass |
| success | Boolean | where this call succeeded |
| error | Object | error information |
| result | Object\[\] | { "followFlag": 0, "buyerId": 310008843475 };A followFlag of 1 indicates that the buyer is a fan |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| IllegalAccessToken | The specified access token is invalid or expired | access token is invalid or expired |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/shop/follow/status/batch/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/shop/follow/status/batch/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/shop/follow/status/batch/query");
request.addApiParameter("buyer_ids", "[\"111\",\"222\"]");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "result": [],
    "success": "true",
    "error": {}
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
