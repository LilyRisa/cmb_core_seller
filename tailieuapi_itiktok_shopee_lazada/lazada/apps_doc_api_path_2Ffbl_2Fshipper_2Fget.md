# GETGetShipperInfo

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Fshipper%2Fget
> API path: /fbl/shipper/get
> Category: FBL API
> Scraped: 2026-05-20T23:41:13.744Z

---

Latest update2026-05-21 07:41:08

500

GetShipperInfo

GET

/fbl/shipper/get

Authorization Required

Description:Get Shipper Info for LAZADA Partner

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
| No Data |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| error\_message | String | Error Message |
| data | Object | Result Data |
| shipper\_id | String | Shipper ID |
| is\_mcl | Boolean | Whether Shipper Support MCL |
| partner\_name | String | MCL Partner Name |
| is\_cb | Boolean | is cb seller |
| main\_seller\_id | String | main seller id for cb seller |
| main\_seller\_site | String | main site for cb seller |
| main\_shipper\_id | String | main shipper id for cb seller |
| success | Boolean | Whether Success |
| error\_code | String | Error Code |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/shipper/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/shipper/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/shipper/get");
request.setHttpMethod("GET");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "Internal system error",
  "code": "0",
  "data": {
    "main_seller_site": "TH",
    "main_shipper_id": "2000000000000",
    "partner_name": "TEST",
    "is_cb": "true",
    "main_seller_id": "2000000000000",
    "shipper_id": "2000000000000",
    "is_mcl": "true"
  },
  "success": "true",
  "error_code": "PARAM_VALID_ERROR",
  "request_id": "0ba2887315178178017221014"
}
```
