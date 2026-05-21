# GET/POSTGetChannelcodeByFirstMileNo

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fcngfc%2Ffulfill%2Fgetchannelcode
> API path: /logistics/cngfc/fulfill/getchannelcode
> Category: FirstMile Bigbag(only for CN)
> Scraped: 2026-05-20T23:29:51.812Z

---

Latest update2022-07-14 15:10:57

3385

GetChannelcodeByFirstMileNo

GET/POST

/logistics/cngfc/fulfill/getchannelcode

No Authorization Required

Description:get channelcode by first mile No

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
| firstMileNos | String\[\] | Yes | 首公里面单号 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| success | Boolean | success |
| module | Object\[\] | module |
| errorCode | String | errorCode |
| errorMsg | String | errorMsg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| IllegalAccessToken | The specified access token is invalid or expired | Token过期或输入有误 |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/cngfc/fulfill/getchannelcode)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/logistics/cngfc/fulfill/getchannelcode

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/cngfc/fulfill/getchannelcode");
request.addApiParameter("firstMileNos", "[\"xxx\",\"xxx\"]");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "success": "false",
    "module": [],
    "errorCode": "S01",
    "errorMsg": "sys error"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
