---
title: "(Deprecated) Get the quota for an iOS 14 Dedicated Campaign"
doc_id: 1739324098894850
path: "API Reference / Campaign / (Deprecated) Get the quota for an iOS 14 Dedicated Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Get the quota for an iOS 14 Dedicated Campaign

>  <span style="color:DodgerBlue">**Note**</span><br> To get the quota details, use [/campaign/quota/info/](https://ads.tiktok.com/marketing_api/docs?id=1752256376677378) instead of this endpoint, because the quota returned by this endpoint is inaccurate.


Use this endpoint to get the quota for an iOS 14 Dedicated Campaign(DC) and ad groups. Each iOS app can have up to 11 DCs and each DC can have up to two ad groups.

With Apple's App Tracking Transparency (ATT) enforcement, you can only create up to 11 iOS 14 DCs under an iOS app. However, with DC quota optimization, you can create up to 11 iOS DCs for each ad network in an iOS app. See [iOS 14 Dedicated Campaign Quota Optimization](https://ads.tiktok.com/marketing_api/docs?id=1752256248260610) to learn about the detailed changes at the campaign and ad group levels. See [iOS 14 Dedicated Campaigns - iOS 14 Dedicated Campaign quota](https://ads.tiktok.com/marketing_api/docs?id=1740029173531649) to find out the quota rules. 



## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/campaign/quota/get/|/v1.3/campaign/quota/get/|
|Request parameter data type |`advertiser_id`:number<br>`campaign_ids`: number<br>`app_id`: number|`advertiser_id`: string<br>`campaign_ids`: string<br>`app_id`: string|
|Response parameter data type|`campaign_id`: number|`campaign_id`: string|
|New response parameter|/|`split_test_quota`<br>`max_test_number`<br>`available_test_group`<br>`used_test_group`<br> `releasing_test_group` <br>`used_quota`<br>`releasing_quota`|
```

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/campaign/quota/get/>

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID|
|app_id {Required} |string|The Application ID of the promoted app. You can get `app_id` by using the [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) endpoint.|
|campaign_id|string|Campaign ID|
```

## Response

```xtable
|Field|Type|Description|
|---|---|---|
|code |number|The response code. Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| The returned data |
#|campaign_quota|object| Campaign quota under an advertiser ID|
##|used_quota |number| The number of iOS 14 campaigns that have been launched|
##|releasing_quota|number|The number of iOS 14 campaigns that wait to be released|
##|available_quota|number| The number of iOS 14 campaigns that you can launch now |
##|total_quota|number| The quota of active iOS 14 campaigns|
#|adgroup_quota|object| Ad group quota under a campaign|
##|campaign_id |number|Campaign ID|
##|used_quota |number| The number of ad groups that have been launched|
##|available_quota|number| The number of ad groups that you can launch now |
##|total_quota|number| The quota of active ad groups|
#| split_test_quota | object | Split test quota information. |
##| max_test_number | integer | Maximum test group number in one split-test. It can only be `2`. |
##| available_test_group | integer | The number of iOS 14 split-test groups that you can launch now in this app. |
##| used_test_group | integer | The number of iOS 14 split-test groups that have been launched. |
##| releasing_test_group | integer | The number of iOS 14 split-test groups that wait to be released. |
##| used_quota | integer | The number of quota that have been launched. |
##| releasing_quota | integer | The number of quota that wait to be released. |
|request_id |string|The unique log id of the request|
```

### Example

```xcodeblock
(code Success http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "campaign_quota": {
            "used_quota": 5,
            "releasing_quota": 0,
            "available_quota": 6,
            "total_quota": 11
        }
    },
    "request_id": "2021012712121901023101600706029954"
}
(/code);
```

