# POSTFreeShippingCreate

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpromotion%2Ffreeshipping%2Fcreate
> API path: /promotion/freeshipping/create
> Category: Free Shipping API
> Scraped: 2026-05-20T23:19:50.155Z

---

Latest update2022-07-28 17:10:01

3862

FreeShippingCreate

POST

/promotion/freeshipping/create

Authorization Required

Description:create a new free shipping promotion

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
| budget\_type | String | Yes | UNLIMITED\_BUDGET | LIMITED\_BUDGET |
| template\_type | String | No | template type, MANUALLY | CAMPAIGN | TEMPLATE |
| apply | String | Yes | apply scope: ENTIRE\_SHOP | SPECIFIC\_PRODUCTS | CAMPAIGN\_PRODUCTS |
| period\_end\_time | Number | Yes | when specific period required, the period end time that this promotion takes effect (timestamp) |
| template\_code | String | No | template code |
| category\_name | String | No | product category id |
| budget\_value | String | No | when limited budget required |
| promotion\_name | String | Yes | promotion name |
| period\_type | String | Yes | LONG\_TERM | SPECIAL\_PERIOD |
| region\_type | String | Yes | ALL\_REGIONS | SPECIAL\_REGIONS, when regions query api return empty just support ALL\_REGIONS |
| period\_start\_time | Number | Yes | when specific period required, the period start time that this promotion takes effect (timestamp) |
| campaign\_tag | String | No | when CAMPAIGN template type and CAMPAIGN\_PRODUCTS apply type required |
| region\_value | String\[\] | No | when SPECIAL\_REGIONS required, data from regions query api |
| delivery\_option | String | Yes | data from delivery options query list api |
| tiers | Object\[\] | Yes | promotion tier list |
| filter | String | Yes | deal criteria value |
| result | String | No | when partial subsidy discount type required，shipping fee subsidy value |
| discount\_type | String | Yes | shipping fee subsidy type,FULL\_SUBSIDY|PARTIAL\_SUBSIDY |
| deal\_criteria | String | Yes | the criteria that customer can enjoy shipping fee subsidy, MONEY\_VALUE\_FROM\_X|ITEM\_QUANTITY\_FROM\_X|NO\_CONDITION |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Number | promotion ID |
| success | Boolean | true | false |
| error\_code | Number | error code |
| error\_msg | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/promotion/freeshipping/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/promotion/freeshipping/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/promotion/freeshipping/create");
request.addApiParameter("budget_type", "UNLIMITED_BUDGET");
request.addApiParameter("template_type", "MANUALLY");
request.addApiParameter("apply", "ENTIRE_SHOP");
request.addApiParameter("period_end_time", "1630339199000");
request.addApiParameter("template_code", "null");
request.addApiParameter("category_name", "null");
request.addApiParameter("budget_value", "10000");
request.addApiParameter("promotion_name", "test");
request.addApiParameter("period_type", "SPECIAL_PERIOD");
request.addApiParameter("region_type", "ALL_REGIONS");
request.addApiParameter("period_start_time", "1626969600000");
request.addApiParameter("campaign_tag", "11230");
request.addApiParameter("region_value", "[\"ALL\"]");
request.addApiParameter("delivery_option", "STANDARD");
request.addApiParameter("tiers", "[{\"filter\":\"100\",\"result\":\"1.5\"}]");
request.addApiParameter("discount_type", "PARTIAL_SUBSIDY");
request.addApiParameter("deal_criteria", "MONEY_VALUE_FROM_X");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "null",
  "code": "0",
  "data": "9616200353530",
  "success": "true",
  "error_code": "null",
  "request_id": "0ba2887315178178017221014"
}
```
