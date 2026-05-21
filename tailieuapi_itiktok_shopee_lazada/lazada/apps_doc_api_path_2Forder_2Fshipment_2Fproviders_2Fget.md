# GET/POSTGetShipmentProvider

> Source: https://open.lazada.com/apps/doc/api?path=%2Forder%2Fshipment%2Fproviders%2Fget
> API path: /order/shipment/providers/get
> Category: Fulfillment API
> Scraped: 2026-05-20T23:26:41.996Z

---

Latest update2022-08-09 14:20:31

20465

GetShipmentProvider

GET/POST

/order/shipment/providers/get

Authorization Required

Description:Use this API to get the list of all active shipping providers, which is needed when working with the PackOrder API.

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
| getShipmentProvidersReq | Object | Yes | req body |
| orders | Object\[\] | Yes | Batch size is limited to 20, to pack orders |
| order\_id | Number | Yes | order\_id |
| order\_item\_ids | Number\[\] | Yes | order\_item\_ids |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | resp body |
| data | Object | resp body |
| platform\_default | Number | 1==seller not need or can't choose transferring warehouses . 0=seller must choose transferring warehouses from shipment\_providers and pass to PACK API by self |
| shipment\_providers | Object\[\] | transferring warehouses list which seller can be choose |
| name | String | transferring warehouses name |
| provider\_code | String | transferring warehouses code |
| shipping\_allocate\_type | String | NTFS/TFS ，Directly pass through to the PACK API |
| success | Boolean | process result |
| error\_code | String | exists when success is false |
| error\_msg | String | exists when success is false |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/order/shipment/providers/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/order/shipment/providers/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/order/shipment/providers/get");
request.addApiParameter("getShipmentProvidersReq", "{\"orders\":[{\"order_id\":\"23423423\",\"order_item_ids\":[\"[2342342,23423]\",\"[2342342,23423]\"]},{\"order_id\":\"23423423\",\"order_item_ids\":[\"[2342342,23423]\",\"[2342342,23423]\"]}]}");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "error_msg": "seller not found",
    "data": {
      "platform_default": "1",
      "shipment_providers": [
        {
          "name": "Cainiao",
          "provider_code": "asc_xxx_xxx"
        }
      ],
      "shipping_allocate_type": "TFS"
    },
    "success": "true",
    "error_code": "70011"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
