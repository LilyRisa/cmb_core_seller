# GET/POSTOpenServiceBalanceQuery

> Source: https://open.lazada.com/apps/doc/api?path=%2Fwallet%2Fopen%2Fservice%2Fbalance%2Fquery
> API path: /wallet/open/service/balance/query
> Category: LazPay API
> Scraped: 2026-05-20T23:56:36.027Z

---

Latest update2024-08-08 15:21:58

1932

OpenServiceBalanceQuery

GET/POST

/wallet/open/service/balance/query

No Authorization Required

Description:Open Service Account Balance Info Query

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
| No Data |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| date\_time | Number | date time |
| available\_amount | String | amount |
| available\_amount\_cent | Number | cent |
| currency | String | currency |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/wallet/open/service/balance/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/wallet/open/service/balance/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/wallet/open/service/balance/query");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "available_amount_cent": "963",
  "code": "0",
  "date_time": "1723100336000",
  "currency": "PHP",
  "available_amount": "9.63",
  "request_id": "0ba2887315178178017221014"
}
```
