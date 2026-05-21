# GET/POSTGetHistoryReviewIdList

> Source: https://open.lazada.com/apps/doc/api?path=%2Freview%2Fseller%2Fhistory%2Flist
> API path: /review/seller/history/list
> Category: Product Review API
> Scraped: 2026-05-20T23:14:21.976Z

---

Latest update2022-09-28 14:39:15

12274

GetHistoryReviewIdList

GET/POST

/review/seller/history/list

Authorization Required

Description:Get history review id list for one seller(reviews within 3 months can be get)

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
| item\_id | String | Yes | Product Item ID |
| order\_id | Number | No | Order ID |
| start\_time | Number | Yes | Start Time, timestamp in millisecond, this is the same with "create\_time" in the response data of interface (/review/seller/list/v2)；The time range cannot exceed 7 days |
| end\_time | Number | Yes | End Time, timestamp in millisecond, this is the same with "create\_time" in the response data of interface (/review/seller/list/v2)；The time range cannot exceed 7 days |
| current | Number | Yes | The current pageNo, default value = 1, max value = 50 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | response data |
| current | Number | current pageNo |
| total | Number | total number |
| page\_size | Number | page size |
| id\_list | Number\[\] | id list |
| success | Boolean | success or fail |
| error\_code | String | error code |
| error\_msg | String | error msg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| PARAMS\_VALIDATE\_ERROR | NULL\_SELLERID | Cannot recognize "seller\_id" |
| PARAMS\_VALIDATE\_ERROR | NULL\_ITEMID | Cannot recognize "item\_id" |
| PARAMS\_VALIDATE\_ERROR | NULL\_CURRENT | Cannot recognize "current" |
| PARAMS\_VALIDATE\_ERROR | CURRENT\_ABOVE\_LIMIT | "current" is above the limit, the max value is 50 |
| PARAMS\_VALIDATE\_ERROR | NULL\_STARTTIME\_OR\_ENDTIME | Cannot recognize "start\_time" or "end\_time" |
| PARAMS\_VALIDATE\_ERROR | STARTTIME\_OVER\_LIMIT | Only support checking 90 days of history data |
| PARAMS\_VALIDATE\_ERROR | TIMESPAN\_ABOVE\_LIMIT | Only support checking 7days data at one time |
| PARAMS\_VALIDATE\_ERROR | WRONG\_ORDER\_ID | Cannot recognize "order\_id" |
| TRAFFIC\_CONTROL | TRAFFIC\_CONTROL | Traffic control |
| PARAMS\_VALIDATE\_ERROR | PARAMS\_VALIDATE\_ERROR | start\_time&end\_time range cannot exceed 7 days. |
| PARAMS\_VALIDATE\_ERROR | PARAMS\_VALIDATE\_ERROR | start\_time&end\_time range cannot exceed 7 days. |
| Mp3SellerApiLimit | Mp3 Seller not support the api -apipath | MP3 sellers cannot call the current API, please readthis document for a list of APIs that can be called by MP3 sellers, and you can call the GetSeller API and check the marketplaceEaseMode field to confirm that the current seller is of type MP3. |
| PARAMS\_VALIDATE\_ERROR | PARAMS\_VALIDATE\_ERROR | start\_time&end\_time range cannot exceed 7 days. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/review/seller/history/list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/review/seller/history/list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/review/seller/history/list");
request.addApiParameter("item_id", "2419854443");
request.addApiParameter("order_id", "1000000000");
request.addApiParameter("start_time", "1662134400000");
request.addApiParameter("end_time", "1662739200000");
request.addApiParameter("current", "1");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "error",
  "code": "0",
  "data": {
    "current": "1",
    "total": "18",
    "id_list": [
      1000000000,
      1000000001
    ],
    "page_size": "10"
  },
  "success": "true",
  "error_code": "error",
  "request_id": "0ba2887315178178017221014"
}
```
