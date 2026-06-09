---
title: "Get Search Ads Campaign Health diagnoses"
doc_id: 1848591212970082
path: "API Reference / Tools / Get Search Ads Campaign Health diagnoses"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Search Ads Campaign Health diagnoses

Use this endpoint to retrieve health diagnoses for your ad groups or ads within a Search Ads Campaign. It provides insights on search volume, keyword relevance, as well as bid and budget recommendations.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/diagnosis/search/health/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|adgroup_id|string|Ad group ID.<br>Specify this field if you want to get the diagnoses for an existing ad group.<br><br>To get the IDs of ad groups within a Search Ads Campaign, use [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922) with the filter field `campaign_ids` set to the ID of a Search Ads Campaign.<br><br><p><span style="color:darkred"><b>Note</b></span>: If both `adgroup id` and `ad_ids` are specified, `adgroup id` will be ignored.|
|ad_ids|string[]|A list of ad IDs. <br>Specify this field if you want to get the diagnoses for existing ads<br><br>The ad IDs must belong to the same ad group.<br><br>Max size: 50.<br><br>To get the IDs of ads within a Search Ads Group, use [/ad/get/](https://business-api.tiktok.com/portal/docs?id=1735735588640770) with the filter field `adgroup_ids` set to the ID of a Search Ads Group within a Search Ads Campaign.<br><br><p><span style="color:darkred"><b>Note</b></span>: If both `adgroup id` and `ad_ids` are specified, `adgroup id` will be ignored.|
```

### Example
#### Get diagnoses for an existing ad group

```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tool/diagnosis/search/health/?advertiser_id={{advertiser_id}}&adgroup_id={{adgroup_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

#### Get diagnoses for existing ads

```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tool/diagnosis/search/health/?advertiser_id={{advertiser_id}}&ad_ids=["{{ad_id}}","{{ad_id}}"]' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code|number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data.|
#|search_health_status|string|The overall search health status. This status provides an overview of how well your ad groups or ads are set up.<br><br>Enum values:<ul><li>`GOOD`: Good. You’re set up for better performance.</li><li>`NEED_IMPROVEMENT`: Need improvement. </li><li>`NO_DATA`: No data.</li></ul>|
#|search_volume|object|Detailed metrics of search volume for your keywords.|
##|diagnosis_result|string|The result from search volume analysis.<br><br>Enum values:<ul><li>`HIGH`: high.</li><li>`MEDIUM`: medium.</li><li>`LOW`: low.</li><li>`INVALID`: invalid.</li></ul>|
##|total_monthly_searches|number|The estimated number of searches that your keywords get each month. <br><br>This number is subject to change according to your match type setting. Broad matches can help maximize the search volume.|
#|total_keyword_count|number|The total number of search keywords.|
#|total_relevant_keyword_count|number|The total number of search keywords found to be relevant to the ad group or ads.|
#|keyword_relevance|object[]|Details on the relevance of keywords to the ad group or ads.|
##|adgroup_id|string|Ad group ID.|
##|ad_id|string|Ad ID.|
##|keyword_relevance_status|string|The result from keyword relevance analysis.<br><br>Enum values:<ul><li>`TO_BE_CALCULATED`: to be calculated.</li><li>`PARTIALLY_RELEVANT`: partially relevant.</li><li>`RELEVANT`: relevant.</li><li>`IRRELEVANT`:  irrelevant.</li></ul>|
##|relevant_keyword_count|number|The number of relevant search keywords.|
##|relevant_keywords|string[]|A list of relevant search keywords.|
##|irrelevant_keyword_count|number|The number of irrelevant search keywords.|
##|irrelevant_keywords|string[]|A list of irrelevant search keywords.|
#|bid_budget|object|Details on bid and budget diagnosis.|
##|bid_budget_status|string|The result from bid and budget analysis.<br><br>Enum values:<ul><li>`GOOD`: Good.</li><li>`LOW_BID_AND_BUDGET`: Low bid and budget.</li><li>`LOW_BUDGET`: Low budget.</li><li>`LOW_BID`: Low bid.</li><li>`NO_DATA`: No data.</li></ul>|
##|bid_suggestion_status|string|The diagnostic result specifically for the bid.<br><br>Enum values:<ul><li>`GOOD`: Good.</li><li>`LOW`: Low bid.</li><li>`NO_DATA`: No data.</li></ul>|
##|suggested_value|string|The recommended bid price. <br><br>Based on historical data, your ad groups or ads might get more conversions by increasing your bid to at least this value.|
```

### Example
#### Get diagnoses for an existing ad group

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "bid_budget": {
            "bid_budget_status": "GOOD",
            "bid_suggestion_status": "GOOD"
        },
        "keyword_relevance": [
            {
                "ad_id": "{{ad_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "irrelevant_keyword_count": 0,
                "keyword_relevance_status": "RELEVANT",
                "relevant_keyword_count": 1,
                "relevant_keywords": [
                    "{{relevant_keyword}}"
                ]
            }
        ],
        "search_health_status": "NEED_IMPROVEMENT",
        "search_volume": {
            "diagnosis_result": "LOW",
            "total_monthly_searches": 52689
        },
        "total_keyword_count": 1,
        "total_relevant_keyword_count": 1
    }
}
(/code)
```

#### Get diagnoses for existing ads

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "bid_budget": {
            "bid_budget_status": "LOW_BUDGET",
            "bid_suggestion_status": "GOOD"
        },
        "keyword_relevance": [
            {
                "ad_id": "{{ad_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "irrelevant_keyword_count": 0,
                "keyword_relevance_status": "RELEVANT",
                "relevant_keyword_count": 2,
                "relevant_keywords": [
                    "drink",
                    "food"
                ]
            },
            {
                "ad_id": "{{ad_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "irrelevant_keyword_count": 0,
                "keyword_relevance_status": "RELEVANT",
                "relevant_keyword_count": 2,
                "relevant_keywords": [
                    "drink",
                    "food"
                ]
            }
        ],
        "search_health_status": "NEED_IMPROVEMENT",
        "search_volume": {
            "diagnosis_result": "HIGH",
            "total_monthly_searches": 52472817
        },
        "total_keyword_count": 2,
        "total_relevant_keyword_count": 4
    }
}
(/code)
```

