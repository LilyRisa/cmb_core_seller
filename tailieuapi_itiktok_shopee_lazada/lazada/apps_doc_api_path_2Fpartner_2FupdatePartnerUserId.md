# GET/POSTUpdatePartnerUserId

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpartner%2FupdatePartnerUserId
> API path: /partner/updatePartnerUserId
> Category: Membership API
> Scraped: 2026-05-20T23:34:23.249Z

---

Latest update2024-07-22 11:26:18

1860

UpdatePartnerUserId

GET/POST

/partner/updatePartnerUserId

Authorization Required

Description:Used to update the partner user id to new partner user id

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
| old\_p\_uid | String | Yes | the current partner user id to match up with a user |
| new\_p\_uid | String | Yes | the new partner user id to be placed |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | api result |
| success | Boolean | whether the call succeed |
| module | Object | result data |
| errorCode | Object | result error |
| displayMessage | String | result error detail |
| key | String | result error key |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/partner/updatePartnerUserId)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/partner/updatePartnerUserId

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/partner/updatePartnerUserId");
request.addApiParameter("old_p_uid", "abc-28754775-2938575-239374");
request.addApiParameter("new_p_uid", "abc-28754775-2938575-239374");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "success": "true",
    "module": {},
    "errorCode": {
      "displayMessage": "partnerUserId is invalid",
      "key": "LZD_MEMBER_USER_1001"
    }
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
