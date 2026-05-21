# GET/POSTGetLinkMember

> Source: https://open.lazada.com/apps/doc/api?path=%2Fmembership%2Flinkmember%2Fget
> API path: /membership/linkmember/get
> Category: Membership API
> Scraped: 2026-05-20T23:32:25.861Z

---

Latest update2022-07-22 14:54:54

2685

GetLinkMember

GET/POST

/membership/linkmember/get

Authorization Required

Description:Query the linkmember relationship between buyers and sellers.

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
| seller\_id | String | Yes | seller id |
| buyer\_id | String | Yes | buyer id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| module | Object | data |
| seller\_id | Number | seller id |
| buyer\_id | Number | buyer id |
| partneruser\_id | String | partnerUser id |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| LZD\_MEMBER\_USER\_1011 | LZD\_MEMBER\_USER\_1011 | The buyer id does not exist, call the PartnerTransaction API to query if you are using the correct buyer id. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/membership/linkmember/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/membership/linkmember/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/membership/linkmember/get");
request.addApiParameter("seller_id", "1141746107123");
request.addApiParameter("buyer_id", "1002820096123");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "module": {
      "buyer_id": "1002820096123",
      "seller_id": "1141746107123",
      "partneruser_id": "LorealLANSG-B"
    }
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
