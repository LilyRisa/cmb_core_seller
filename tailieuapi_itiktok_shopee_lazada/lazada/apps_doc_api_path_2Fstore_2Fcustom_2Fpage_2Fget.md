# GET/POSTGetStoreCustomPage

> Source: https://open.lazada.com/apps/doc/api?path=%2Fstore%2Fcustom%2Fpage%2Fget
> API path: /store/custom/page/get
> Category: Store Decoration API
> Scraped: 2026-05-20T23:15:01.263Z

---

Latest update2022-08-02 16:33:03

5511

GetStoreCustomPage

GET/POST

/store/custom/page/get

Authorization Required

Description:GetStoreCustomPagevice

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
| page | String | Yes | page |
| size | String | Yes | size |
| keyword | String | No | Support keyword search |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | ellipsis |
| result | Object | Response results |
| page\_info | Object | page info |
| total\_count | String | Last release time |
| current\_page | String | current page |
| page\_list | Object\[\] | page list |
| publish\_time | String | Last release time |
| wireless\_end\_time | String | Currently invalid |
| wireless\_page\_preview\_url | String | Wireless page preview link |
| pc\_page\_preview\_url | String | PC page preview link |
| qr\_url | String | QR code preview link |
| pc\_end\_time | String | Currently invalid |
| timed\_publish\_time | String | Currently invalid |
| relate\_page\_id | Number | Associated page ID Return to wireless page by default This is the wireless associated PC page ID |
| page\_id | Number | Page ID, Return to wireless page by default |
| page\_name | String | page name |
| path | String | Page path |
| client\_type | String | Page type: PC or wireless |
| decorate\_page\_url | String | Page decoration URL |
| wireless\_page\_view\_url | String | Wireless page address |
| page\_view\_url | String | PC page address |
| status\_key | String | Page status key |
| last\_edit\_time | String | Last edit time |
| success | Boolean | true or false |
| error | String | error |
| error\_message | String | message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/store/custom/page/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/store/custom/page/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/store/custom/page/get");
request.addApiParameter("page", "1");
request.addApiParameter("size", "10");
request.addApiParameter("keyword", "TestMM");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {
    "result": {
      "page_list": [
        {
          "decorate_page_url": "/site/decorate?pageId\u003d136495008",
          "wireless_page_preview_url": "/site/global/page/preview?pageId\u003d138575311\u0026pagePath\u003dcustom-1658828630680.htm\u0026clientType\u003dwireless",
          "wireless_end_time": "Currently invalid",
          "timed_publish_time": "Currently invalid",
          "relate_page_id": "138575310",
          "client_type": "wireless",
          "pc_end_time": "Currently invalid",
          "pc_page_preview_url": "/site/global/page/preview?pageId\u003d138575310\u0026pagePath\u003dcustom-1658828630680.htm\u0026clientType\u003dpc",
          "page_id": "138575311",
          "path": "custom-1655884010234.htm",
          "wireless_page_view_url": "https://shop-global-staging.lazada.sg/shop/nwsyydsw12/custom-1646991044777.htm?wh_weex\u003dtrue",
          "page_view_url": "https://shop-global-staging.lazada.sg/shop/nwsyydsw12/custom-1646991044777.htm?wh_weex\u003dtrue",
          "last_edit_time": "2022-03-11 16:48:48",
          "publish_time": "2022-03-11 14:45:22",
          "qr_url": "https://shop-global-staging.lazada.sg/shop/nwsyydsw12/custom-1658828630680.htm?wh_weex\u003dtrue",
          "page_name": "Customized Page (1658828630680)",
          "status_key": "editing"
        }
      ],
      "page_info": {
        "total_count": "46",
        "current_page": "1"
      }
    },
    "error_message": "message",
    "success": "true",
    "error": "error"
  },
  "request_id": "0ba2887315178178017221014"
}
```
