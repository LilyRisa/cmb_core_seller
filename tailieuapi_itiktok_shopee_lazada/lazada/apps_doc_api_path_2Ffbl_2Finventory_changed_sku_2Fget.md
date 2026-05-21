# GETGetInventoryChangedSKU

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Finventory_changed_sku%2Fget
> API path: /fbl/inventory_changed_sku/get
> Category: FBL API
> Scraped: 2026-05-20T23:39:55.758Z

---

Latest update2022-07-29 17:39:46

2288

GetInventoryChangedSKU

GET

/fbl/inventory\_changed\_sku/get

Authorization Required

Description:Use this API to get SKU list

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
| warehouse\_code | String | No | Warehouse code |
| page | Number | No | Sku list page index |
| per\_page | Number | No | Sku list per page size |
| market\_place | String | Yes | market place:LAZADA\_VN,LAZADA\_SG,LAZADA\_MY, LAZADA\_ID,LAZADA\_PH,LAZADA\_TH |
| operate\_Time\_From | String | No | Inventory operate time from. This param is Required |
| operate\_Time\_To | String | No | Inventory operate time to. This param is Required.We suggest that operate\_time\_to - operate\_time\_from < 6 months |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| per\_page | Number | Per page size |
| page | Number | Page index |
| total\_count | Number | Total count of sku |
| sku\_list | Object\[\] | Sku list |
| fulfillment\_sku\_id | String | Fulfillment sku id |
| operate\_log\_count | Number | Total count of operate log |
| success | String | The api request success or not |
| errMessage | String | Error message when success=false |
| errCode | String | Error code when success=false |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/inventory_changed_sku/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/inventory\_changed\_sku/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/inventory_changed_sku/get");
request.setHttpMethod("GET");
request.addApiParameter("warehouse_code", "OMS-LAZADA-MY-W-1");
request.addApiParameter("page", "1");
request.addApiParameter("per_page", "20");
request.addApiParameter("market_place", "LAZADA_SG");
request.addApiParameter("operate_Time_From", "2019-07-23");
request.addApiParameter("operate_Time_To", "2019-08-24");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "per_page": "10",
  "code": "0",
  "total_count": "100",
  "sku_list": [
    {
      "fulfillment_sku_id": "322302784_SGAMZ-648014148",
      "operate_log_count": "150"
    }
  ],
  "success": "true",
  "errCode": "INVALID PARAM",
  "page": "1",
  "request_id": "0ba2887315178178017221014",
  "errMessage": "invalid marketplace"
}
```
