# GET/POSTOpenServiceKycQuery

> Source: https://open.lazada.com/apps/doc/api?path=%2Fwallet%2Fopen%2Fservice%2Fkyc%2Fquery
> API path: /wallet/open/service/kyc/query
> Category: LazPay API
> Scraped: 2026-05-20T23:56:43.871Z

---

Latest update2023-02-02 19:23:25

2280

OpenServiceKycQuery

GET/POST

/wallet/open/service/kyc/query

Authorization Required

Description:Open Service User KYC Info Query

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
| need\_cert\_info | Boolean | No | True means need KYC Info photo |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| phone | String | phone number |
| prefix | String | phone number prefix |
| userId | String | open platform user id |
| birthday | String | birthday, format is yyyy-MM-dd |
| full\_name | String | full name |
| cert\_front\_image | String | certificate front image |
| cert\_type | String | certificate type |
| full\_kyc\_status | Boolean | whether user has passed full kyc or not |
| kyc\_jump\_url | String | redirect url to let user finish kyc in lazada app |
| extend\_info | String | extend infos |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/wallet/open/service/kyc/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/wallet/open/service/kyc/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/wallet/open/service/kyc/query");
request.addApiParameter("need_cert_info", "true");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "birthday": "1999-01-29",
  "full_kyc_status": "false",
  "cert_type": "passport",
  "full_name": "Bob Davis",
  "code": "0",
  "phone": "09123123156",
  "prefix": "63",
  "cert_front_image": "base64 string",
  "kyc_jump_url": "kycJumpUrl",
  "userId": "500101968946",
  "extend_info": "\"{\\\"AGE_VERIFY_PASS\\\":true}\"",
  "request_id": "0ba2887315178178017221014"
}
```
