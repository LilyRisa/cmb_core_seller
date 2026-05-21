# GET/POSTGetSellerPerformance

> Source: https://open.lazada.com/apps/doc/api?path=%2Fseller%2Fperformance%2Fget
> API path: /seller/performance/get
> Category: Seller API
> Scraped: 2026-05-20T23:03:21.406Z

---

Latest update2022-07-28 17:14:50

10063

GetSellerPerformance

GET/POST

/seller/performance/get

Authorization Required

Description:Provide the performance metrics of the current seller, such as positive seller rating, ship on time, etc.

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
| language | String | No | Optional ISO 639-1 standard language code (default: en-US, supported languages: en-US, zh-CN, ms-MY, th-TH, vi-VN, id-ID). |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Response payload. |
| seller\_id | Number | Seller ID. |
| main\_category\_id | Number | Seller's main category ID. |
| main\_category\_name | String | Seller's main category name. |
| indicators | Object\[\] | Performance indicators. |
| type | String | Indicator type (e.g. POSITIVE\_SELLER\_RATING, PRODUCT\_RATING\_COVERAGE, ...). |
| name | String | Name of the indicator is the seller's language. |
| tip | String | Longer description of the indicator is the seller's language. |
| score | Number | Raw score value. Note: if the indicator doesn't contain any value, a null value is set instead. |
| score\_format | String | Score format: INTEGER, DOUBLE, PERCENTAGE, MINUTES, HOURS. |
| formatted\_score | String | Score formatted in the seller's language and locale. Note: if the indicator doesn't contain any value, a "-" is set instead. |
| target | Number | Indicator target (raw value). Note: if the indicator doesn't contain any value, a null value is set instead. |
| target\_format | String | Target format: GREATER\_THAN\_DOUBLE ('≥' #.##), GREATER\_THAN\_PERCENTAGE ('≥' #.##'%'), LOWER\_THAN\_PERCENTAGE('≤' #.##'%'), LOWER\_THAN\_MINUTES('≤' #'min'), STRICTLY\_LOWER\_THAN\_HOURS('<' #'h'), GREATER\_THAN\_DOUBLE ('≥' #.##), EQUALS\_TO\_INTEGER(= #). |
| formatted\_target | String | Indicator target formatted in the seller's language and locale. |
| target\_respected | Boolean | true if the formattedScore respects the formattedTarget, false if not. |
| action\_url | String | Relative (from the Seller Portal) or absolute URL to redirect the seller to the page where he cans handle the task. |
| success | Boolean | true for success, false for error. |
| error\_code | String | Error code if success = false. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| IllegalAccessToken | The specified access token is invalid or expired | access token is invalid or expired |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/seller/performance/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/seller/performance/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/seller/performance/get");
request.addApiParameter("language", "en-US");
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
    "main_category_name": "Mobile \u0026 Tablet",
    "indicators": [
      {
        "action_url": "/apps/review/manage",
        "score": "92.0",
        "score_format": "PERCENTAGE",
        "formatted_score": "92%",
        "name": "Positive Seller Rating",
        "tip": "\u003cdiv style\u003d\u0027font-weight: bold\u0027 \u003ePositive Seller Rating\u003c/div\u003e\u003cbr /\u003eThe ratio of total positive ratings to total ratings from verified buyers. This is measured for period of last 8 weeks.",
        "type": "POSITIVE_SELLER_RATING",
        "formatted_target": "≥ 85%",
        "target": "85.0",
        "target_format": "GREATER_THAN_PERCENTAGE",
        "target_respected": "true"
      }
    ],
    "seller_id": "42",
    "main_category_id": "12356"
  },
  "success": "true",
  "error_code": "REQUEST_CANNOT_BE_NULL",
  "request_id": "0ba2887315178178017221014"
}
```
