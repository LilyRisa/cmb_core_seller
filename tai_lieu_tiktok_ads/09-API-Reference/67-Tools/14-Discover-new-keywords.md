---
title: "Discover new keywords"
doc_id: 1855460985594881
path: "API Reference / Tools / Discover new keywords"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Discover new keywords

Use this endpoint to discover new keywords and explore keyword ideas to reach people interested in your business.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/search_keyword/keyword_idea/

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
|keywords {Required}|string[]|A list of keywords.<br>You can specify products or services closely related to your business.<br><br>Max size: 10.<br>Length limit for each keyword: 80 characters.<br><br><ul><li>Only English words are supported.</li><li>Emojis and special symbols such as # are not supported.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: For optimal results, provide all keywords in lowercase. Providing uppercase keyword is not recommended.|
|order_field|string|Sorting logic.<br><br>Enum values:<ul><li>`AVG_MONTHLY_SEARCHES`: Sort the results by the range of average monthly searches as indicated by the response parameters `avg_monthly_searches_lower` and `avg_monthly_searches_upper`.</li><li>`THREE_MONTH_CHANGE`: Sort the results by the response parameter `three_month_change`.</li><li>`YEAR_OVER_YEAR_CHANGE`: Sort the results by the response parameter `year_over_year_change`.</li><li>`COMPETITION`: Sort the results by the response parameter `competition`.</li></ul>Default value: `AVG_MONTHLY_SEARCHES`.|
|order_type|string|Sorting order.<br><br>Enum values:<ul><li>`ASC`: ascending.</li><li>`DESC`: descending.</li></ul>Default value: `DESC`.|
|brand_type|string|Brand type.<br><br>Enum values:<ul><li>`BRAND`: Brand.</li><li>`NON_BRAND`: Non-brand.</li><li>`ALL`: All.</li></ul>Default value: `ALL`.<br><br><p><span style="color:darkred"><b>Note</b></span>: This filter is only available when `country_codes` is set to `["US"]`. For all other countries or regions, `ALL` is the default and only allowed value.</p>|
|country_codes|string[]|The location code of the country or region that you want to target.<br><br>Max size: 1.<br><br>Default value: `US`.<br>For enum values, see <a href="#item-link-List of values for country_codes">List of values for `country_codes`</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: Discovering new keywords for supported non-US locations outside of your home country is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p>|
```

### List of values for `country_codes`

```xtable
|<code>country_codes</code> value{30%}|Description{70%}|
|---|---|
|`AU`|Australia|
|`BR`|Brazil|
|`CA`|Canada|
|`FR`|France|
|`DE`|Germany|
|`IT`|Italy|
|`MX`|Mexico|
|`SA`|Saudi Arabia|
|`ES`|Spain|
|`AE`|United Arab Emirates|
|`GB`|United Kingdom|
|`US`|United States|
```

### Example

```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tool/search_keyword/keyword_idea/?advertiser_id={{advertiser_id}}&keywords=["{{keyword}}","{{keyword}}"]&order_field=AVG_MONTHLY_SEARCHES&order_type=DESC&brand_type=ALL&country_codes=["US"]' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code|number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data.|
#|total_search_volume_lower|number|The start of the range of total search volume for all the suggested keywords (`keywords`).|
#|total_search_volume_upper|number|The end of the range of total search volume for all the suggested keywords (`keywords`).|
#|keywords|object[]|A list of suggested keywords relevant to the specified keyword or keywords based on the specified targeting country or region (`country_codes`).|
##|recommended_keyword|string|Keyword.<br><br>A suggested keyword relevant to the specified keyword or keywords.|
##|avg_monthly_searches_lower|number|Average monthly searches (Low).<br><br>This is the start of the range of the average number of monthly searches for the past month of a keyword and similar terms based on the specified targeting country or region.|
##|avg_monthly_searches_upper|number|Average monthly searches (High).<br><br>This is the end of the range of the average number of monthly searches for the past month of a keyword and similar terms based on the specified targeting country or region.|
##|three_month_change|float|3 month change.<br><br>This data compares the monthly search volume of the most recent month's data to the data from two months before.|
##|year_over_year_change|float|YoY change.<br><br>This data compares the monthly search volume of the last month to the same month last year.|
##|competition|string|Competition.<br><br>This shows how competitive ad placement is for a keyword, based on unique advertiser count bidding on the keyword in the last month.<br><br>Enum values: <ul><li>`HIGH`: High.</li><li>`MEDIUM`: Medium.</li><li>`LOW`: Low.</li></ul>|
##|estimated_cpc_lower|float|Estimated CPC (Low), in USD.<br><br>This is the start of the range of CPC of what advertisers paid in the last month for the keyword’s top of search page ad based on specified targeting country or region. The average CPC of your keywords may vary.|
##|estimated_cpc_upper|float|Estimated CPC (High), in USD.<br><br>This is the end of the range of CPC of what advertisers have paid in the last month for the keyword’s top of search page ad based on specified targeting country or region. The average CPC of your keywords may vary.|
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
        "keywords": [
            {
                "avg_monthly_searches_lower": 500000,
                "avg_monthly_searches_upper": 1000000,
                "competition": "HIGH",
                "estimated_cpc_lower": 0.5062341041327454,
                "estimated_cpc_upper": 0.6947784896270967,
                "recommended_keyword": "shoes",
                "three_month_change": -0.3566219295724846,
                "year_over_year_change": -0.9556371579502115
            },
            {
                "avg_monthly_searches_lower": 100000,
                "avg_monthly_searches_upper": 250000,
                "competition": "HIGH",
                "estimated_cpc_lower": 0.6854101014434859,
                "estimated_cpc_upper": 0.8316111534680811,
                "recommended_keyword": "bear claw shoes",
                "three_month_change": -0.000005560127215710696,
                "year_over_year_change": 0.00040605635839757926
            },
            ...
            {
                "avg_monthly_searches_lower": 1000,
                "avg_monthly_searches_upper": 5000,
                "competition": "MEDIUM",
                "estimated_cpc_lower": 0,
                "estimated_cpc_upper": 0,
                "recommended_keyword": "mid dunks",
                "three_month_change": -0.9375,
                "year_over_year_change": -0.9999778408083673
            },
            {
                "avg_monthly_searches_lower": 1000,
                "avg_monthly_searches_upper": 5000,
                "competition": "HIGH",
                "estimated_cpc_lower": 0.0375,
                "estimated_cpc_upper": 0.07250000000000001,
                "recommended_keyword": "midnight ballerina shoes",
                "three_month_change": -0.7937971500419112,
                "year_over_year_change": -0.7861799217731421
            }
        ],
        "total_search_volume_lower": 2500000,
        "total_search_volume_upper": 5000000
    }
}
(/code)
```


