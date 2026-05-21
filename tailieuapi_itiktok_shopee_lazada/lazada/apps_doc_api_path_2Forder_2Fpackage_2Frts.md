# POSTReadyToShip

> Source: https://open.lazada.com/apps/doc/api?path=%2Forder%2Fpackage%2Frts
> API path: /order/package/rts
> Category: Fulfillment API
> Scraped: 2026-05-20T23:27:40.821Z

---

Latest update2022-08-09 14:20:41

21373

ReadyToShip

POST

/order/package/rts

Authorization Required

Description:Use this API to mark an order item as being ready to ship.

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
| readyToShipReq | Object | Yes | request body |
| packages | Object\[\] | Yes | Batch size is limited to 20, to readyToShip packages |
| package\_id | String | Yes | packageId |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | resp body |
| data | Object | resp data |
| packages | Object\[\] | package readyToShip result |
| msg | String | err msg |
| item\_err\_code | String | 0=success other=error code |
| package\_id | String | packageId |
| retry | String | Determine if the package can be retried |
| success | Boolean | process result，If this is true, it doesn't mean that everything is processed successfully. It is necessary to judge that the item\_err\_code in packages is equal to 0 to determine that the processing is successful. Otherwise, if this is false, this batch must be unsuccessful. |
| error\_code | String | exists when success is false |
| error\_msg | String | exists when success is false |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/order/package/rts)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/order/package/rts

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/order/package/rts");
request.addApiParameter("readyToShipReq", "{\"packages\":[{\"package_id\":\"FP234234\"},{\"package_id\":\"FP234234\"}]}");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "error_msg": "package not found",
    "data": {
      "packages": [
        {
          "msg": "package already cancelled",
          "item_err_code": "600002",
          "package_id": "FP038524014",
          "retry": "false"
        }
      ]
    },
    "success": "true",
    "error_code": "11"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
