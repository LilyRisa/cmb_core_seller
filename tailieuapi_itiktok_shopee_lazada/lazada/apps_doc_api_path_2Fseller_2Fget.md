# GETGetSeller

> Source: https://open.lazada.com/apps/doc/api?path=%2Fseller%2Fget
> API path: /seller/get
> Category: Seller API
> Scraped: 2026-05-20T23:03:01.922Z

---

Latest update2022-07-29 12:49:54

33172

GetSeller

GET

/seller/get

Authorization Required

Description:Get seller information by current seller ID.

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
| No Data |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Response data |
| name\_company | String | Company name |
| seller\_id | Number | Seller's ID |
| name | String | Shop name |
| short\_code | String | Seller's short code |
| logo\_url | String | Logo URL |
| email | String | Seller's email |
| cb | Boolean | Whether the seller is a Cross Border seller or not |
| location | String | location of seller |
| status | String | three status ACTIVE INACTIVE DELETED |
| verified | Boolean | Whether the seller is verified |
| marketplaceEaseMode | Boolean | Whether the seller is MarketplaceEaseMode |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| IllegalAccessToken | The specified access token is invalid or expired | access token is invalid or expired |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/seller/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/seller/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/seller/get");
request.setHttpMethod("GET");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {
    "name_company": "alibaba group",
    "logo_url": "http://xxxx.com/abc.jpg",
    "name": "abc shop",
    "verified": "true",
    "location": "Hangzhou",
    "marketplaceEaseMode": "true",
    "seller_id": "10",
    "email": "Beanbagmart.sg@gmail.com",
    "short_code": "SG1015W",
    "cb": "false",
    "status": "ACTIVE"
  },
  "request_id": "0ba2887315178178017221014"
}
```
