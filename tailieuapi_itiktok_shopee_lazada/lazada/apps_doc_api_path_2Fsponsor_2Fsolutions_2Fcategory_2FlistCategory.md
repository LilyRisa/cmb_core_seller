# GET/POSTlistCategory

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Fcategory%2FlistCategory
> API path: /sponsor/solutions/category/listCategory
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:06:32.085Z

---

Latest update2026-05-21 08:06:28

500

listCategory

GET/POST

/sponsor/solutions/category/listCategory

Authorization Required

Description:list category

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
| parentId | Number | No | The category parent id. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object\[\] | \- |
| label | String | The category name. |
| value | Number | The category id. |
| isLeaf | Boolean | Is the category leaf node. |
| selectable | Boolean | Can the category be selected. |
| success | Boolean | System result for this api call. |
| errorMsg | String | If the api call failed, this field will show the detail reason. |
| analyseTraceId | String | If the api call failed, you could find us with this. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/category/listCategory)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/category/listCategory

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/category/listCategory");
request.addApiParameter("parentId", "8229");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": [
    {
      "selectable": "true",
      "label": "Women Bags",
      "value": "23981",
      "isLeaf": "true"
    }
  ],
  "code": "0",
  "success": "true",
  "analyseTraceId": "-",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "invalid param"
}
```
