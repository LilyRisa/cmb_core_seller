---
title: "Get the dynamic quota on active ad groups"
doc_id: 1768463039162369
path: "API Reference / Ad Groups / Get the dynamic quota on active ad groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the dynamic quota on active ad groups

Use this endpoint to get the dynamic quota on the number of active auction ad groups that an advertiser can have.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/adgroup/quota/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/adgroup/quota/?advertiser_id={{advertiser_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.<br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> If the advertiser (`advertiser_id`) is subject to fixed active ad group quota, you will receive `total_adgroup_quota` = 5000 and `used_adgroup_quota` = 0 in the response. </li><li> If the advertiser (`advertiser_id`) is being punished, there will be no active ad groups allowed to create. The returned `total_adgroup_quota` and `used_adgroup_quota` will be set to 0. You can check whether an advertiser account is being punished through the `status` returned by [/advertiser/info/](https://ads.tiktok.com/marketing_api/docs?id=1739593083610113). </li></ul>|
#| total_adgroup_quota | number | The maximum number of active ad groups that the advertiser (`advertiser_id`) can have. |
#| used_adgroup_quota | number | The number of active ad groups that the advertiser (`advertiser_id`) currently has. <br><p><span style="color:darkred"><b>Note</b></span>: When the advertiser has used up all your dynamic active ad group quota (`total_adgroup_quota` = `used_adgroup_quota`), the advertiser can still create additional ad groups, but the additional ad groups will be automatically deactivated after several seconds. Also, the advertiser cannot use [/adgroup/status/update/](https://ads.tiktok.com/marketing_api/docs?id=1739591716326402) to reactivate the deactivated ad groups. |
```
### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "total_adgroup_quota": 4980,
        "used_adgroup_quota": 20
    }
}
(/code)
```
