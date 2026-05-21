# GET/POSTMCNQueryTagInfoByName

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcontent%2Fmcn%2Fcontent%2FqueryTagInfosByName
> API path: /content/mcn/content/queryTagInfosByName
> Category: LazLike API
> Scraped: 2026-05-21T00:11:33.845Z

---

Latest update2024-06-11 15:20:45

908

MCNQueryTagInfoByName

GET/POST

/content/mcn/content/queryTagInfosByName

No Authorization Required

Description:MCNQueryTagInfoByName

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
| tagNames | String | Yes | The tag name you want to query, multiple tags are split according to, |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| api\_result | Object | result |
| success | String | whether the operation succeeds |
| resultCode | String | error code provided when the operation fails |
| resultMessage | String | error message provided when the operation fails |
| tagDTOList | Object\[\] | result |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/content/mcn/content/queryTagInfosByName)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/content/mcn/content/queryTagInfosByName

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/content/mcn/content/queryTagInfosByName");
request.addApiParameter("tagNames", "Neo-Chinese,Sexy Style");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "request_id": "0ba2887315178178017221014",
  "api_result": {
    "success": "true",
    "resultCode": "error",
    "tagDTOList": [
      {
        "owner": "lazada_content",
        "gmtModified": 1716518646000,
        "creator": "343236",
        "tagCode": "Neo-Chinese_1716518645925",
        "modifier": "343236",
        "description": "Neo-Chinese",
        "gmtCreate": 1716518646000,
        "tagName": "Neo-Chinese",
        "parentTagId": 7697,
        "isDeleted": "0",
        "tagPath": "7697-7703",
        "id": 7703,
        "isSetDeadline": "0",
        "class": "com.lazada.tag.client.response.TagDTO",
        "parentTagCode": "Fashion Style_1716518581934",
        "tagCategoryCode": "content_property",
        "entityAttrVersion": "1.0"
      },
      {
        "owner": "lazada_content",
        "gmtModified": 1716885895000,
        "creator": "343236",
        "tagCode": "Sexy Style_1716885895073",
        "modifier": "343236",
        "description": "Sexy Style",
        "gmtCreate": 1716885895000,
        "tagName": "Sexy Style",
        "parentTagId": 7697,
        "isDeleted": "0",
        "tagPath": "7697-7709",
        "id": 7709,
        "isSetDeadline": "0",
        "class": "com.lazada.tag.client.response.TagDTO",
        "parentTagCode": "Fashion Style_1716518581934",
        "tagCategoryCode": "content_property",
        "entityAttrVersion": "1.0"
      }
    ],
    "resultMessage": "10001"
  }
}
```
