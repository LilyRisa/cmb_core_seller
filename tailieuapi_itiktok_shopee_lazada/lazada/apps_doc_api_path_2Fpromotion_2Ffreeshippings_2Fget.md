# GETFreeShippingList

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpromotion%2Ffreeshippings%2Fget
> API path: /promotion/freeshippings/get
> Category: Free Shipping API
> Scraped: 2026-05-20T23:20:44.056Z

---

Latest update2022-08-01 01:01:42

3249

FreeShippingList

GET

/promotion/freeshippings/get

Authorization Required

Description:query free shipping promotion list

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
| curPage | Number | No | current page |
| name | String | No | promotion name |
| pageSize | Number | No | page size |
| status | String | No | NOT\_START | ONGOING | SUSPEND | FINISH |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | response body |
| total | Number | total |
| current | Number | current page |
| data\_list | Object\[\] | data list |
| budget\_type | String | UNLIMITED\_BUDGET | LIMITED\_BUDGET |
| template\_type | String | template type, MANUALLY | CAMPAIGN | TEMPLATE |
| used\_budget\_value | String | used budget value |
| apply | String | apply scope: ENTIRE\_SHOP | SPECIFIC\_PRODUCTS | CAMPAIGN\_PRODUCTS |
| period\_end\_time | Number | when specific period required, the period end time that this promotion takes effect (timestamp) |
| template\_code | String | template code, when TEMPLATE type not null |
| category\_name | String | category name |
| budget\_value | String | budget value |
| promotion\_name | String | promotion name |
| period\_type | String | LONG\_TERM | SPECIAL\_PERIOD |
| region\_type | String | ALL\_REGIONS | SPECIAL\_REGIONS |
| period\_start\_time | Number | when specific period required, the period start time that this promotion takes effect (timestamp) |
| platform\_channel | String | LAZADA | ZAL | ALL\_CHANNEL |
| campaign\_tag | String | when CAMPAIGN template type and CAMPAIGN\_PRODUCTS apply type not null |
| region\_value | String\[\] | when SPECIAL\_REGIONS not null |
| currency | String | currency |
| id | Number | promotion id |
| delivery\_option | String | delivery option |
| promo\_tier | Object | promotion tier |
| tiers | Object\[\] | promotion tier list |
| filter | String | deal criteria value |
| result | String | when partial subsidy discount type required，shipping fee subsidy value |
| discount\_type | String | shipping fee subsidy type,FULL\_SUBSIDY|PARTIAL\_SUBSIDY |
| deal\_criteria | String | the criteria that customer can enjoy shipping fee subsidy, MONEY\_VALUE\_FROM\_X|ITEM\_QUANTITY\_FROM\_X|NO\_CONDITION |
| status | String | status, NOT\_START | ONGOING | SUSPEND | FINISH |
| page\_size | Number | page size |
| success | Boolean | true | false |
| error\_code | Number | error code |
| error\_msg | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/promotion/freeshippings/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/promotion/freeshippings/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/promotion/freeshippings/get");
request.setHttpMethod("GET");
request.addApiParameter("curPage", "1");
request.addApiParameter("name", "test");
request.addApiParameter("pageSize", "10");
request.addApiParameter("status", "ONGOING");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "error message",
  "code": "0",
  "data": {
    "data_list": [
      {
        "period_end_time": "1626821505557",
        "category_name": "null",
        "apply": "ENTIRE_SHOP",
        "budget_value": "2345.00",
        "campaign_tag": "null",
        "region_type": "ALL_REGIONS",
        "region_value": [],
        "promo_tier": {
          "tiers": [
            {
              "filter": "1000.00",
              "result": "1.50"
            }
          ],
          "deal_criteria": "MONEY_VALUE_FROM_X",
          "discount_type": "PARTIAL_SUBSIDY"
        },
        "template_code": "null",
        "period_start_time": "1626789105557",
        "promotion_name": "test",
        "used_budget_value": "0.00",
        "platform_channel": "1",
        "template_type": "MANUALLY",
        "currency": "SGD",
        "id": "91471121124118",
        "budget_type": "LIMITED_BUDGET",
        "period_type": "SPECIAL_PERIOD",
        "delivery_option": "STANDARD",
        "status": "FINISH"
      }
    ],
    "total": "115",
    "current": "1",
    "page_size": "10"
  },
  "success": "true",
  "error_code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
