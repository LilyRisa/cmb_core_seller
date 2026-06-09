---
title: "Get the TikTok Shop exclusive authorization status of an ad account"
doc_id: 1822001184635905
path: "API Reference / GMV Max / Get the TikTok Shop exclusive authorization status of an ad account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the TikTok Shop exclusive authorization status of an ad account

Use this endpoint to check whether an ad account is exclusively authorized to create GMV Max Campaigns for a specific TikTok Shop.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/gmv_max/exclusive_authorization/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| store_id {Required} | string | The ID of the TikTok Shop.<br><br> To obtain a TikTok Shop that is available for GMV Max Campaigns, use [/gmv_max/store/list/](https://business-api.tiktok.com/portal/docs?id=1822001044479041) and confirm that the returned `is_gmv_max_available` is `true`. |
|store_authorized_bc_id {Required} | string | ID of the Business Center that is authorized to access the TikTok Shop (`store_id`). |
| advertiser_id {Required} | string | ID of the ad account that you want to check the GMV Max exclusive authorization status for. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/exclusive_authorization/get/?store_id={{store_id}}&store_authorized_bc_id={{store_authorized_bc_id}}&advertiser_id={{advertiser_id}}' \
--header 'Access-Token:{{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| store_id | string | The ID of the TikTok Shop. |
#| advertiser_id | string | The ID of the ad account. |
#| authorization_status | string | The status of exclusive authorization for the ad account to create GMV Max ads for the TikTok Shop.<br><br>Enum values:<ul><li>`EFFECTIVE`: The ad account is currently granted exclusive GMV Max authorization.</li><li>`INEFFECTIVE`: The ad account was granted exclusive GMV Max authorization but the authorization has been cancelled.</li><li>`UNAUTHORIZED`: The ad account has never been granted GMV Max exclusive authorization.</li></ul>|
#| advertiser_name | string | The name of the ad account.  <br><br><p><span style="color:darkred"><b>Note</b></span>: When `authorization_status` is `UNAUTHORIZED`, this field is not returned. |
#| advertiser_status | string | The status of the ad account.  <br><br> Enum values:<ul><li>`STATUS_ENABLE`: Approved.</li><li>`STATUS_CONFIRM_FAIL`: Not approved.</li><li>`STATUS_PENDING_CONFIRM`: In review.</li><li>`STATUS_LIMIT`: Suspended.</li><li>`STATUS_CONTRACT_PENDING`: Contract has not taken effect.</li><li>`STATUS_DISABLE`: Disabled.</li><li>`STATUS_PENDING_CONFIRM_MODIFY`: Modifications pending review.</li><li>`STATUS_PENDING_VERIFIED`: Pending verification.</li><li>`STATUS_SELF_SERVICE_UNAUDITED`: Pending verification of qualifications for the self-service account.</li><li> `STATUS_WAIT_FOR_BPM_AUDIT`: Pending CRM system review.</li><li>`STATUS_CONFIRM_FAIL_END`: CRM system review failed.</li><li>`STATUS_CONFIRM_MODIFY_FAIL`: Review of modifications failed. </li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When `authorization_status` is `UNAUTHORIZED`, this field is not returned. |
#| identity_id | string | Returned only when an official TikTok account is set for the TikTok Shop. <br><br> The ID of the identity associated with the official TikTok account for the TikTok Shop. |
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
        "advertiser_id": "{{advertiser_id}}",
        "advertiser_name": "{{advertiser_name}}",
        "advertiser_status": "STATUS_ENABLE",
        "authorization_status": "EFFECTIVE",
        "identity_id": "{{identity_id}}",
        "store_id": "{{store_id}}"
    }
}
(/code)
```
