# GET/POSTGetChoiceSeller

> Source: https://open.lazada.com/apps/doc/api?path=%2Fchoice%2Fseller%2Fget
> API path: /choice/seller/get
> Category: Choice Customized API
> Scraped: 2026-05-21T00:10:05.629Z

---

Latest update2026-05-21 08:09:59

500

GetChoiceSeller

GET/POST

/choice/seller/get

Authorization Required

Description:Get choice seller information by seller ID and site

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
| site | String | Yes | The country site of the queried merchant |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Response data |
| name\_company | String | Company name |
| name | String | Shop name |
| seller\_id | String | Seller's ID |
| verified | String | Whether the seller is verified |
| email | String | Seller's email |
| short\_code | String | Seller's short code |
| cb | String | Whether the seller is a Cross Border seller or not |
| location | String | location of seller |
| status | String | three status :Active\\Deleted\\Inactive |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/choice/seller/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/choice/seller/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/choice/seller/get");
request.addApiParameter("site", "SG");
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
    "name": "abc shop",
    "verified": "true",
    "location": "Singapore",
    "seller_id": "10",
    "email": "Beanbagmart.sg@gmail.com",
    "short_code": "SG1015W",
    "cb": "false",
    "status": "Active"
  },
  "request_id": "0ba2887315178178017221014"
}
```
