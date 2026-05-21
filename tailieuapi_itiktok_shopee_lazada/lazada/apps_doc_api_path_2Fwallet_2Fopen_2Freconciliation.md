# GET/POSTReconciliation

> Source: https://open.lazada.com/apps/doc/api?path=%2Fwallet%2Fopen%2Freconciliation
> API path: /wallet/open/reconciliation
> Category: Lazada Wallet Corporate Top-up API
> Scraped: 2026-05-20T23:59:17.202Z

---

Latest update2022-07-29 12:47:16

2582

Reconciliation

GET/POST

/wallet/open/reconciliation

No Authorization Required

Description:Corporate TopUp - Reconciliation

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
| date | String | Yes | A date in the format of "yyyy-mm-dd" |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| res | String | The reconciliation file encoded by base64, user needs to decode it into a readable csv file. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| RECONCILIATION\_INPUT\_DATE\_INVALID | Invalid input format of local date. | Invalid input format of local date. |
| ECONCILIATION\_CSV\_ERROR\_FAILED | Error happens when creating reconciliation file. | Error happens when creating reconciliation file. |
| BIZ\_DEGRADATION\_ERROR | The service is not available now. | The service is not available now. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/wallet/open/reconciliation)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/wallet/open/reconciliation

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/wallet/open/reconciliation");
request.addApiParameter("date", "2022-04-01");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "res": "abcdefg",
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
