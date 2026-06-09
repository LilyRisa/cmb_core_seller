---
title: "Get the quota for a SKAN Dedicated Campaign per ad network"
doc_id: 1752256376677378
path: "API Reference / Campaign / Get the quota for a SKAN Dedicated Campaign per ad network"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the quota for a SKAN Dedicated Campaign per ad network

Use this endpoint to get the quota for SKAN Dedicated Campaigns per ad network, the ad group quota under a campaign, and the ad quota for an ad group within a SKAN 4.0 Dedicated Campaign.

An iOS app can have up to 15 or 25 (allowlist-only) active SKAN Dedicated Campaigns per ad network and up to five active ad groups within each SKAN Dedicated Campaign.

To learn more about the SKAN Dedicated Campaign quota rules and ad networks, see [Dedicated Campaign quota limits](https://business-api.tiktok.com/portal/docs?id=1752256248260610).


## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/campaign/quota/info/>

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID|
|app_id {Required} |string|The Application ID of the promoted app. You can get `app_id` by using the [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) endpoint.|
|campaign_id|string|ID of a SKAN Dedicated Campaign.  <br>Specify this field if you want to get ad group quota (`adgroup_quota_info`) for a certain campaign. <br> <br><p><span style="color:darkred"><b>Note</b></span>: If the ID is not the ID of a SKAN Dedicated Campaign, an error will occur. To determine whether a Dedicated Campaign is a SKAN Dedicated Campaign, use [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986) and check whether the returned `disable_skan_campaign` is `false`.|
| adgroup_id | string | ID of an ad group within a SKAN 4.0 Dedicated Campaign. Specify this field if you want to get ad quota (`ad_quota_info`) for a certain group. <br><br><p><span style="color:darkred"><b>Note</b></span>: If the ID is not the ID of an ad group within a SKAN 4.0 Dedicated Campaign, the value of the returned `ad_quota_info` will be null. |
|has_advertiser_quota |boolean| Whether to return quota info via different advertiser IDs under the promoted app. <br> Set it to `true` if you want to get campaign quota via different advertiser IDs. <br>Default value: `false`. |
```
### Example
#### Query the Dedicated Campaign quota for an iOS app
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/campaign/quota/info/?advertiser_id={{advertiser_id}&app_id={{app_id}}'\
--header 'Access-Token: {{Access-Token}}' \
(/code)
```

#### Query the ad group quota within a Dedicated Campaign for an iOS app
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/campaign/quota/info/?advertiser_id={{advertiser_id}&app_id={{app_id}}&campaign_id={{campaign_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

#### Query the ad quota within a SKAN 4.0 ad group for an iOS app
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/campaign/quota/info/?advertiser_id={{advertiser_id}&app_id={{app_id}}&adgroup_id={{adgroup_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response
```xtable
| Field {40%}| Data Type{12%} | Description{48%}  |
|---|---|---|
| code | number | The response code. Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
| message | string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
| data | object | The returned data. |
#| split_test_quota | object | Split test quota information. |
##| max_test_number | number | Maximum test group number in one split-test. <br>The value of this field will be `2`. |
##| available_test_group | number | The number of split-test groups that you can launch now in this app. |
##| used_test_group | number | The number of split-test groups that have been launched. |
##| releasing_test_group | number | The number of split-test groups that are to be released. |
##| used_quota | number | The number of quota that has been launched. |
##| releasing_quota | number | The number of quota that is to be released. |
#| campaign_quota_info | object | Campaign quota. |
##| tiktok_campaign_quota_info | object | Campaign quota under the TikTok network. The placements under TikTok network are `PLACEMENT_TIKTOK`and `PLACEMENT_GLOBAL_APP_BUNDLE`. |
###| total_campaign_quota_info | object | Campaign quota under an app ID. |
####| total_campaign_quota | number | The total quota of active Dedicated Campaigns. <br>The value of this field will be `15` or `25`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Expanding the quota of active Dedicated Campaigns to 25 per ad network for an iOS app is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
####| used_campaign_quota | number | The number of Dedicated Campaigns that have been launched. |
####| releasing_campaign_quota | number | The number of Dedicated Campaigns that are to be released. |
####| available_campaign_quota | number | The number of Dedicated Campaigns that you can launch now. |
####| used_campaign_ids | string[] | The IDs of the Dedicated Campaigns that have been launched. |
####| releasing_campaign_ids | string[] | The IDs of the Dedicated Campaigns that are to be released. |
###| campaign_quota_by_adv | object[] | Campaign quota under each advertiser ID. |
####| used_campaign_quota | number | The number of Dedicated Campaigns that have been launched. |
####| releasing_campaign_quota | number | The number of Dedicated Campaigns that are to be released. |
####| advertiser_id | string | Advertiser ID. |
##| pangle_campaign_quota_info | object | Campaign quota under the Pangle network. The placement available for Pangle network is `PLACEMENT_PANGLE`. |
###| total_campaign_quota_info | object | Campaign quota under an app ID. |
####| total_campaign_quota | number | The total quota of active Dedicated Campaigns.<br>The value of this field will be `15` or `25`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Expanding the quota of active Dedicated Campaigns to 25 per ad network for an iOS app is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.  |
####| used_campaign_quota | number | The number of Dedicated Campaigns that have been launched. |
####| releasing_campaign_quota | number | The number of Dedicated Campaigns that are to be released. |
####| available_campaign_quota | number | The number of Dedicated Campaigns that you can launch now. |
####| used_campaign_ids | string[] | The IDs of the Dedicated Campaigns that have been launched. |
####| releasing_campaign_ids | string[] | The IDs of Dedicated Campaigns that are to be released. |
###| campaign_quota_by_adv | object[] | Campaign quota under each advertiser ID. |
####| used_campaign_quota | number | The number of Dedicated Campaigns that have been launched. |
####| releasing_campaign_quota | number | The number of Dedicated Campaigns that are to be released. |
####| advertiser_id | string | Advertiser ID. |
#| adgroup_quota_info | object | Ad group quota under a Dedicated Campaign. |
##| total_adgroup_quota | number | The quota of active ad groups. <br>The value of this field will be `5`. |
##| used_adgroup_quota | number | The number of ad groups that have been launched. |
##| available_adgroup_quota | number | The number of ad groups that you can launch now. |
##| placements | string[] | The allowed placements for ad groups. You cannot create ad groups with placements that are not listed here. |
##| campaign_id | string | Campaign ID. |
#| ad_quota_info | object | Ad quota under an ad group within a SKAN 4.0 Dedicated Campaign.  <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If the specified ad group (`adgroup_id`) is not within a SKAN 4.0 Dedicated Campaign, the value of this field will be null.</li><li>If you provide a SKAN 4.0 App ID (`app_id`) without specifying `campaign_id` and `adgroup_id` in the request, this field will represent the default ad quota for a regular SKAN 4.0 Dedicated Campaign (a SKAN 4.0 Dedicated Campaign where Smart+ Campaign, Automated Creative Optimization, and Smart Creative settings are not enabled). Example: `"ad_quota_info": &#123;"available_ad_quota": 9, "total_ad_quota": 9,"adgroup_id": "0","used_ad_quota": 0&#125;`. </li></ul> |
##| total_ad_quota | number | The quota of active ads. <br>For ad groups within regular SKAN 4.0 Dedicated Campaigns (SKAN 4.0 Dedicated Campaigns where Smart+ Campaign, Automated Creative Optimization, and Smart Creative settings are not enabled), the value of this field will be `9`. |
##| used_ad_quota | number | The number of ads that have been launched. |
##| available_ad_quota | number | The number of ads that you can launch now. |
##| adgroup_id | string | Ad group ID. |
| request_id | string | The log ID of a request, which uniquely identifies the request. |
```

### Example
#### Query the Dedicated Campaign quota for an iOS app
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_quota_info": {
            "available_ad_quota": 9,
            "total_ad_quota": 9,
            "adgroup_id": "0",
            "used_ad_quota": 0
        },
        "adgroup_quota_info": {
            "campaign_id": "0",
            "used_adgroup_quota": 0,
            "placements": [
                "PLACEMENT_PANGLE",
                "PLACEMENT_GLOBAL_APP_BUNDLE",
                "PLACEMENT_TIKTOK"
            ],
            "total_adgroup_quota": 5,
            "available_adgroup_quota": 5
        },
        "split_test_quota": {
            "max_test_number": 2,
            "used_test_group": 2,
            "releasing_test_group": 0,
            "used_quota": 4,
            "releasing_quota": 0,
            "available_test_group": 0
        },
        "campaign_quota_info": {
            "tiktok_campaign_quota_info": {
                "campaign_quota_by_adv": [],
                "total_campaign_quota_info": {
                    "releasing_campaign_ids": [
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}"
                    ],
                    "total_campaign_quota": 20,
                    "releasing_campaign_quota": 4,
                    "used_campaign_ids": [
                        "{{releasing_campaign_id}}"
                    ],
                    "available_campaign_quota": 15,
                    "used_campaign_quota": 1
                }
            },
            "pangle_campaign_quota_info": {
                "campaign_quota_by_adv": [],
                "total_campaign_quota_info": {
                    "releasing_campaign_ids": [
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}"
                    ],
                    "total_campaign_quota": 20,
                    "releasing_campaign_quota": 3,
                    "used_campaign_ids": [
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}"
                    ],
                    "available_campaign_quota": 12,
                    "used_campaign_quota": 5
                }
            }
        }
    }
}
(/code)
```

#### Query the ad group quota within a Dedicated Campaign for an iOS app
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_quota_info": null,
        "adgroup_quota_info": {
            "available_adgroup_quota": 5,
            "campaign_id": "{{campaign_id}}",
            "placements": [
                "PLACEMENT_GLOBAL_APP_BUNDLE",
                "PLACEMENT_TIKTOK",
                "PLACEMENT_PANGLE"
            ],
            "total_adgroup_quota": 5,
            "used_adgroup_quota": 0
        },
        "split_test_quota": {
            "releasing_test_group": 0,
            "releasing_quota": 0,
            "max_test_number": 2,
            "used_test_group": 2,
            "used_quota": 4,
            "available_test_group": 0
        },
        "campaign_quota_info": {
            "tiktok_campaign_quota_info": {
                "total_campaign_quota_info": {
                    "releasing_campaign_quota": 4,
                    "total_campaign_quota": 20,
                    "used_campaign_ids": [
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}"
                    ],
                    "releasing_campaign_ids": [
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}"
                    ],
                    "used_campaign_quota": 5,
                    "available_campaign_quota": 11
                },
                "campaign_quota_by_adv": []
            },
            "pangle_campaign_quota_info": {
                "total_campaign_quota_info": {
                    "releasing_campaign_quota": 3,
                    "total_campaign_quota": 20,
                    "used_campaign_ids": [
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}"
                    ],
                    "releasing_campaign_ids": [
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}"
                    ],
                    "used_campaign_quota": 5,
                    "available_campaign_quota": 12
                },
                "campaign_quota_by_adv": []
            }
        }
    }
}
(/code)
```

#### Query the ad quota within a SKAN 4.0 ad group for an iOS app
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_quota_info": {
            "available_ad_quota": 7,
            "adgroup_id": "{{adgroup_id}}",
            "total_ad_quota": 9,
            "used_ad_quota": 2
        },
        "adgroup_quota_info": {
            "available_adgroup_quota": 5,
            "campaign_id": "0",
            "placements": [
                "PLACEMENT_PANGLE",
                "PLACEMENT_GLOBAL_APP_BUNDLE",
                "PLACEMENT_TIKTOK"
            ],
            "total_adgroup_quota": 5,
            "used_adgroup_quota": 0
        },
        "split_test_quota": {
            "releasing_test_group": 0,
            "releasing_quota": 0,
            "max_test_number": 2,
            "used_test_group": 2,
            "used_quota": 4,
            "available_test_group": 0
        },
        "campaign_quota_info": {
            "tiktok_campaign_quota_info": {
                "total_campaign_quota_info": {
                    "releasing_campaign_quota": 4,
                    "total_campaign_quota": 20,
                    "used_campaign_ids": [
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}"
                    ],
                    "releasing_campaign_ids": [
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}"
                    ],
                    "used_campaign_quota": 5,
                    "available_campaign_quota": 11
                },
                "campaign_quota_by_adv": []
            },
            "pangle_campaign_quota_info": {
                "total_campaign_quota_info": {
                    "releasing_campaign_quota": 3,
                    "total_campaign_quota": 20,
                    "used_campaign_ids": [
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}",
                        "{{used_campaign_id}}"
                    ],
                    "releasing_campaign_ids": [
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}",
                        "{{releasing_campaign_id}}"
                    ],
                    "used_campaign_quota": 5,
                    "available_campaign_quota": 12
                },
                "campaign_quota_by_adv": []
            }
        }
    }
}
(/code)
```
