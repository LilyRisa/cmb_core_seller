# GET/POSTGetLinkMemberList

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpartner%2Flist
> API path: /partner/list
> Category: Membership API
> Scraped: 2026-05-20T23:33:12.616Z

---

Latest update2022-10-11 19:38:26

3419

GetLinkMemberList

GET/POST

/partner/list

Authorization Required

Description:Query all linkmembers of the seller

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
| page\_num | String | Yes | page number |
| page\_size | String | Yes | page size |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| model\_list | Object\[\] | data list |
| seller\_id | Number | seller id |
| buyer\_id | Number | buyer id |
| partneruser\_id | String | partneruser id |
| total\_count | Number | total count |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/partner/list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/partner/list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/partner/list");
request.addApiParameter("page_num", "1");
request.addApiParameter("page_size", "10");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "model_list": [
      {
        "buyer_id": "1013016816",
        "seller_id": "1141746107",
        "partneruser_id": "LorealLANSG-47C76E2D-2249-EC11-95A3-0050569FADEB"
      }
    ],
    "total_count": "2289"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
