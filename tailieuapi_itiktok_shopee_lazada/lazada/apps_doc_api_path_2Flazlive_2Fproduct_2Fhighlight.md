# GET/POSTHighlightProduct

> Source: https://open.lazada.com/apps/doc/api?path=%2Flazlive%2Fproduct%2Fhighlight
> API path: /lazlive/product/highlight
> Category: LazLive API
> Scraped: 2026-05-21T00:14:34.247Z

---

Latest update2026-05-21 08:14:21

500

HighlightProduct

GET/POST

/lazlive/product/highlight

No Authorization Required

Description:highlight product

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
| highLightRequest | Object | Yes | Request parameters |
| itemId | Number | Yes | item id |
| presenterId | Number | Yes | presenter id |
| action | String | Yes | highlight start：HIGHLIGHT\_START |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | data |
| success | Boolean | true |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| BIZ\_INVALID\_ARGUMENT | Please check whether the input parameter "action" is correct | 1 |
| BIZ\_USER\_NOT\_PERMITTED | No permission | 1 |
| BIZ\_LIVE\_NOT\_FOUND | The live room does not exist | 1 |
| BIZ\_INVALID\_PRODUCT | Invalid product | 1 |
| BIZ\_NOT\_LIVE\_PRODUCT | It is not a product of the live room | 1 |
| SYSTEM\_ERROR | We are experiencing a surge in traffic. Please try again. If you continue to get this message, try again later | 1 |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/lazlive/product/highlight)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/lazlive/product/highlight

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/lazlive/product/highlight");
request.addApiParameter("highLightRequest", "{\"itemId\":\"2774584032\",\"presenterId\":\"500209002194\",\"action\":\"HIGHLIGHT_START\"}");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {
    "success": "true"
  },
  "request_id": "0ba2887315178178017221014"
}
```
