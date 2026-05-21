# POSTLinkMembership

> Source: https://open.lazada.com/apps/doc/api?path=%2Fmembership%2Flink
> API path: /membership/link
> Category: Membership API
> Scraped: 2026-05-20T23:33:28.533Z

---

Latest update2022-07-29 16:04:43

3352

LinkMembership

POST

/membership/link

Authorization Required

Description:Used to push a new membership to Lazada for proactively linking memberships.

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
| p\_uid | String | Yes | A unique identifier of the member on partner side, generated and stored at partner side, that identifies that member and will be referenced by Lazada in further communications. |
| member\_name | String | No | Name of member on partner side, to easier identify the membership on My Account pages |
| tier | String | No | Customer’s tier in partner side, shown as-is |
| tier\_expiry | String | No | Expiry of the membership, shown as-is |
| balance | Number | No | Balance of the membership. |
| valid\_from | String | No | Valid from of this balance in RFC RFC3339 format. Ignore if this is no validity period for the balance |
| valid\_to | String | No | Valid to of this balance in RFC RFC3339 format. Ignore if this is no validity period for the balance |
| linking\_token | String | Yes | Linking token. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | 1 |
| success | Boolean | 1 |
| data | Object | 1 |
| error\_code | String | 1 |
| display\_message | String | 1 |
| key | String | 1 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/membership/link)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/membership/link

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/membership/link");
request.addApiParameter("p_uid", "123456");
request.addApiParameter("member_name", "Marie Curie");
request.addApiParameter("tier", "GOLD");
request.addApiParameter("tier_expiry", "2019-10-02T15:00:00Z");
request.addApiParameter("balance", "6000");
request.addApiParameter("valid_from", "2019-10-02T15:00:00Z");
request.addApiParameter("valid_to", "2019-10-02T15:00:00Z");
request.addApiParameter("linking_token", "ndi9ah0s9e7902377923470");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "data": {},
    "success": "1",
    "error_code": {
      "display_message": "1",
      "key": "1"
    }
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
