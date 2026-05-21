# GET/POSTGetBrandByPages

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcategory%2Fbrands%2Fquery
> API path: /category/brands/query
> Category: Product API
> Scraped: 2026-05-20T23:07:22.635Z

---

Latest update2022-07-28 16:54:33

16173

GetBrandByPages

GET/POST

/category/brands/query

No Authorization Required

Description:Use this API to retrieve all product brands by page index in the system.

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
| startRow | String | Yes | Number of brands to skip (i.e., an offset into the result set; together with the "limit" parameter, simple result set paging is possible; if you do page through results, note that the list of brands might change during paging). |
| pageSize | String | Yes | The maximum number of brands that can be returned. If you omit this parameter, the default of 40 is used. The Maximum is 200. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Response data |
| start\_row | Number | start row |
| page\_index | Number | page index |
| total\_page | Number | total page (no use) |
| module | Object\[\] | data module |
| global\_identifier | String | A unique string identifier for the brand across different systems. For example: ADIDAS, NIKE, APPLE. |
| name\_en | String | The English name of the brand. |
| brand\_id | Number | brand id |
| name | String | The actual name of the brand. |
| enable\_total | Boolean | enable total or not (no use) |
| page\_size | Number | page size |
| total\_record | Number | total number of record |
| success | Boolean | operation success or not |
| error\_code | String | error code |
| error\_msg | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/category/brands/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/category/brands/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/category/brands/query");
request.addApiParameter("startRow", "0");
request.addApiParameter("pageSize", "20");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "null",
  "code": "0",
  "data": {
    "enable_total": "true",
    "start_row": "1",
    "page_index": "0",
    "module": [
      {
        "name": "3M",
        "global_identifier": "3m",
        "name_en": "3M",
        "brand_id": "4"
      }
    ],
    "total_page": "819",
    "page_size": "100",
    "total_record": "81849"
  },
  "success": "true",
  "error_code": "null",
  "request_id": "0ba2887315178178017221014"
}
```
