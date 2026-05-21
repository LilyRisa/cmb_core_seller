# GET/POSTclickserver

> Source: https://open.lazada.com/apps/doc/api?path=%2Fgproject%2Fads%2Faidc%2Fclick
> API path: /gproject/ads/aidc/click
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:03:25.632Z

---

Latest update2026-05-21 08:03:12

500

clickserver

GET/POST

/gproject/ads/aidc/click

No Authorization Required

Description:aidc click server interface

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
| cpcClickDO | Object | No | cookie section |
| ext | String | No | //扩展参数 |
| referer | String | No | referer |
| e | String | Yes | 加密串 |
| utdId | String | Yes | usertrack section |
| ip | String | Yes | ip |
| utkey | String | No | //友盟电商墙app标识 |
| utsid | String | No | //友盟电商墙设备标识 |
| clickid | String | No | clickid |
| userAgent | String | No | 使用默认值 |
| accept | String | No | //不能为空,反作弊加密串 |
| cna | String | No | cookie section |
| host | String | No | host |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | Result |
| headers | Object | headers |
| success | Boolean | success true / false |
| model | Object | model |
| biz\_ext\_map | Object | biz\_ext\_map |
| mapping\_code | String | mapping\_code |
| msg\_info | String | msg\_info |
| msg\_code | String | msg\_code |
| http\_status\_code | Number | http\_status\_code |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/gproject/ads/aidc/click)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/gproject/ads/aidc/click

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/gproject/ads/aidc/click");
request.addApiParameter("cpcClickDO", "{\"ext\":\"{ \\\"cookie\\\":\\\"u\\u003dtest\\u0026s\\u003d3Ns\\\",  \\\"usertrack\\\":\\\"utdid\\u003d3mfj45\\\",  \\\"queryString\\\":\\\"sid\\u003d23454434\\u0026k\\u003d\\u0026u\\u003dmeda20023\\\" }\",\"referer\":\"referer\",\"e\":\"e\",\"utdId\":\"usertrack section\",\"ip\":\"ip\",\"utkey\":\"//\u53CB\u76DF\u7535\u5546\u5899app\u6807\u8BC6\",\"cna\":\"cookie section\",\"utsid\":\"//\u53CB\u76DF\u7535\u5546\u5899\u8BBE\u5907\u6807\u8BC6\",\"host\":\"host\",\"clickid\":\"clickid\",\"userAgent\":\"\u4F7F\u7528\u9ED8\u8BA4\u503C\",\"accept\":\"//\u4E0D\u80FD\u4E3A\u7A7A,\u53CD\u4F5C\u5F0A\u52A0\u5BC6\u4E32\"}");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "biz_ext_map": {},
    "headers": {},
    "msg_code": "msg_code",
    "http_status_code": "http_status_code",
    "success": "success",
    "msg_info": "msg_info",
    "model": {},
    "mapping_code": "mapping_code"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
