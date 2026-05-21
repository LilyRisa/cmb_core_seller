# GET/POSTPartnerUnlink

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpartner%2Funlink
> API path: /partner/unlink
> Category: Membership API
> Scraped: 2026-05-20T23:34:00.092Z

---

Latest update2022-08-01 12:50:50

2429

PartnerUnlink

GET/POST

/partner/unlink

Authorization Required

Description:Used to remove a linked membership from Lazada. Please note that the link will not physically be removed, but deactivated.

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
| p\_uid | String | Yes | A unique identifier of the member on partner side, used to establish the link to partner ID. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | 1 |
| success | Boolean | 1 |
| data | Object | 1 |
| error\_code | Object | 1 |
| display\_message | String | 1 |
| key | String | 1 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/partner/unlink)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/partner/unlink

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/partner/unlink");
request.addApiParameter("p_uid", "123456");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "data": {},
    "success": "1",
    "error_code": {
      "display_message": "1",
      "key": "1"
    }
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
