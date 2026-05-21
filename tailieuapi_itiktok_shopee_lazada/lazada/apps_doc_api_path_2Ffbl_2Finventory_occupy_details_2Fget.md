# GETGetInventoryOccupyDetails

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Finventory_occupy_details%2Fget
> API path: /fbl/inventory_occupy_details/get
> Category: FBL API
> Scraped: 2026-05-20T23:40:03.848Z

---

Latest update2023-02-22 09:36:01

2205

GetInventoryOccupyDetails

GET

/fbl/inventory\_occupy\_details/get

Authorization Required

Description:Use this API to get a sku's inventory occupy details

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
| fulfillmentSku | String | Yes | Fulfillment Sku Id |
| storeCode | String | Yes | Warehouse code |
| marketplace | String | Yes | market place:LAZADA\_VN,LAZADA\_SG,LAZADA\_MY, LAZADA\_ID,LAZADA\_PH,LAZADA\_TH |
| pageNum | Number | No | pageNum |
| pageSize | Number | No | pageSize |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| inventoryOccupyDetails | Object\[\] | inventory occupy detail list |
| orderCode | String | main order code |
| quantity | Number | reversed number eg: |
| orderType | String | order type |
| inventoryType | String | Inventory type:GOOD,Damaged,ONWAY,TRANSFER\_WAY,Expired,Damaged A,Damaged B,Damaged C. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/inventory_occupy_details/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/inventory\_occupy\_details/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/inventory_occupy_details/get");
request.setHttpMethod("GET");
request.addApiParameter("fulfillmentSku", "322302784_SGAMZ-648014149");
request.addApiParameter("storeCode", "OMS-LAZADA-MY-W-1");
request.addApiParameter("marketplace", "LAZADA_SG");
request.addApiParameter("pageNum", "1");
request.addApiParameter("pageSize", "50");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "request_id": "0ba2887315178178017221014",
  "inventoryOccupyDetails": [
    {
      "orderType": "OUTBOUND",
      "inventoryType": "SELLABLE",
      "quantity": "1",
      "orderCode": "DIFF20221229449164_1_OCCUPY_STG"
    }
  ]
}
```
