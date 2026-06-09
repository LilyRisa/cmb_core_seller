---
title: "Audience targeting"
doc_id: 1739381236849665
path: "Marketing API / Campaign Management / Guides / Ad group / Targeting / Audience targeting"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Audience targeting

You can use the following targeting options to target a specific audience. 

## Manual targeting options

```xtable
|Option{25%}|Data Field to Use{27%}|Description{48%}|
|---|---|---|
|Demographics| | |
#|Gender|`gender` |Gender that you want to target.<br> Enum values: `GENDER_FEMALE`, `GENDER_MALE`, and `GENDER_UNLIMITED`. |
#|Age|`age_groups` |A list of age groups that you want to target. <br> For enum values, see [Enumeration - Age Group](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Age%20Group).|
#|Location|`location_ids` or `zipcode_ids` or both |A list of location IDs, zip code IDs, or postal code IDs that you want to target. <br> <br>Zip code targeting is currently only supported for the US and postal code targeting is currently only supported for Canada, Brazil, Indonesia, Thailand, and Vietnam.  <br> <br>Example: `&#91;"6252001"&#93;`, `&#91;"8840000435004"&#93;`, `&#91;"8124000001276"&#93;`.  <br> <br> To get available location IDs, use [/tool/region/](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713). <br> You can get the available zip code IDs or postal code IDs based on your placement, objective and keyword via `geo_id` (when `geo_type` is `ZIP_CODE`) returned from [/tool/targeting/search/](https://ads.tiktok.com/marketing_api/docs?id=1761236883355649).<br> <br><p><span style="color:darkred"><b>Note</b></span>: Targeting postal code areas in Brazil, Indonesia, Thailand, and Vietnam is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
#|Language|`languages` | A list of languages that you want to target. <br> To get available languages, use [/tool/language/](https://ads.tiktok.com/marketing_api/docs?id=1737188554152962) . |
#|Household income |`household_income`| Household income that you want to target. <br> Enum values: `TOP5`(Top 5% of ZIP codes), `TOP10`(Top 10% of ZIP codes), `TOP10_25`(Top 10% -25% of ZIP codes), `TOP25_50`(Top 25% - 50% of ZIP codes). <br><br><p><span style="color:darkred"><b>Note</b></span>: Household income targeting is only available in the US.|
#|Spending power|`spending_power`|Spending power that you want to target. <br> Enum values: `ALL`, `HIGH`.   If it is set to `HIGH`, you can target high spending users who typically spend more on purchases on TikTok ads than average users. |
|Interests & Behaviors | | |
#|Interests|` interest_category_ids`<br>`interest_keyword_ids` | `interest_category_ids` is a list of general interest category IDs. <br> To search for or list general interest category IDs, use [/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1796638342793218) (recommended) or [/tool/interest_category/](https://ads.tiktok.com/marketing_api/docs?id=1737174348712961).<br><br>`interest_keyword_ids` is a list of additional interest category IDs. <br>To search for additional interest categories, use [/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1796638342793218) (recommended) or [/tool/interest_keyword/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1763590884474882). <br><br>To get recommended interest category IDs based on your industry, use [/tool/targeting_category/recommend/](https://business-api.tiktok.com/portal/docs?id=1736275204260866).|
#|Purchase intention|`purchase_intention_keyword_ids` | This is a list of purchase intention category IDs.  <br>To search for or list purchase intention category IDs, use [/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1796638342793218).|
#|Video interactions <br>& Creator interactions <br>& Hashtag interactions|`actions` | This is a list of action objects. Each object includes four data fields: `action_scene`, `action_category_ids`, `action_period`, and `video_user_actions`. `action_category_ids` can be video interactions category IDs, creator interactions category IDs, hashtag IDs, or hashtag bundle IDs.<br><br> <ul><li>To search for or list video interactions category IDs or creator interactions category IDs, use [/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1796638342793218) (recommended) or [/tool/action_category/](https://ads.tiktok.com/marketing_api/docs?id=1737166752522241).</li><li>To get hashtag IDs or hashtag bundle IDs, use [/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1796638342793218) (recommended) or [/tool/hashtag/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1736271339521025).</li><li>To get recommended video interactions category IDs, creator interactions category IDs, hashtag IDs, or hashtag bundle IDs based on your industry, use [/tool/targeting_category/recommend/](https://business-api.tiktok.com/portal/docs?id=1736275204260866).</li></ul> |
#|Smart interests & behaviors | `smart_interest_behavior_enabled` | Whether to turn on Smart interests & behaviors. <br><br>To learn more about Smart audience and how to turn on Smart audience, see [Smart Targeting](https://business-api.tiktok.com/portal/docs?id=1783164662979586). |
|Devices| | |
#|Connection Type|`network_types` |`wifi`, `2G`, `3G`, or `4G`. |
#|Operation System|`operating_systems` |`android`, `iOS`, or `PC`. |
#|Operation System Version|`min_android_version`, `min_ios_version`, `ios14_targeting` | |
#|Device Model|`device_model_ids` |IDs of the device models that you want to target.<br> To get the enum values, use [/tool/device_model/](https://ads.tiktok.com/marketing_api/docs?id=1737172880570369) . |
#|Device Price|`device_price_ranges` | Targeting device price range. Example: [50, 250]. |
#|Carrier|`carrier_ids` | A list of carrier IDs that you want to target. <br>To get the enum values, use [/tool/carrier/](https://ads.tiktok.com/marketing_api/docs?id=1737168013095938). |
#|Internet Service Provider (ISP)|`isp_ids` | A list of ISP IDs that you want to target. Example: `&#91;"EG00098"&#93;`.<br> You can use [/tool/targeting/list/](https://ads.tiktok.com/marketing_api/docs?id=1762962378261506) to get the ISP IDs that you can target for a location ID.|
|Audience| | Custom Audience or Lookalike Audience.|
#|Include Audience|`audience_ids` | A list of audience IDs to target. |
#|Smart audience | `smart_audience_enabled` | Whether to turn on Smart audience.  <br>To learn more about Smart audience and how to turn on Smart audience, see [Smart Targeting](https://business-api.tiktok.com/portal/docs?id=1783164662979586). |
#|Exclude Audience|`excluded_audience_ids` |A list of audience IDs to exclude. |
```


## Saved Audience
You can use Saved Audience to store your targeting settings and reuse them to create future ad groups. See [Create a Saved Audience](https://business-api.tiktok.com/portal/docs?id=1780156510696449) to learn more about this feature and then how to apply the Saved Audience to your ad group. 

## Targeting requirements for ad groups in a campaign with special ad categories
If you've specified `special_industries` for your campaign, ensure that ad groups in the campaign meet the following targeting requirements. Otherwise, an error will occur.

```xtable
| Setting{20%} | Requirement {22%}| Parameter {20%}| How to configure the parameter {38%}|
|---|---|---|---|
| Automatic targeting | Disabled | `auto_targeting_enabled` | Not passed |
| Demographics - Location | Zip code targeting is disabled | `zipcode_ids` | Not passed |
| Demographics - Gender | Unlimited | `gender` | `GENDER_UNLIMITED` |
| Demographics - Age | Over 18 years old | `age_groups` | Not passed (Recommended) or `["AGE_18_24","AGE_25_34","AGE_35_44","AGE_45_54","AGE_55_100"]` |
| Demographics - Household income | Disabled | `household_income` | Not passed |
| Audience - Include | Lookalike audiences are not used | `audience_ids` | Pass the IDs of non-lookalike audiences. <br><br>To identify non-lookalike audiences, pass the audience IDs to the `custom_audience_ids` parameter in [/dmp/custom_audience/get/](https://business-api.tiktok.com/portal/docs?id=1739940507792385). The `type` for a lookalike audience will be `Lookalike Audience`. |
| Audience - Exclude | Lookalike audiences, Challenge Audiences, and Premium Audiences are not used | `excluded_audience_ids` | Pass the IDs of audiences that are not Lookalike audiences, Challenge Audiences, or Premium Audiences. <br><br> To identify such audiences, pass the audience IDs to the `custom_audience_ids` parameter in [/dmp/custom_audience/get/](https://business-api.tiktok.com/portal/docs?id=1739940507792385). Audiences with `type` as `Lookalike Audience`, `Challenge`, or `Preferred Population` are not supported. |
| Interests & Behaviours - Interests | Interest keywords are disabled | `interest_keyword_ids` | Not passed |
| Interests & Behaviours - Interests | Interest categories, if passed, are supported for the special ad categories | `interest_category_ids` | Pass the interest category IDs returned from [/tool/interest_category/](https://business-api.tiktok.com/portal/docs?id=1737174348712961) when `special_industries` is specified in the request |
| Interests & Behaviours - Video interactions <br>& Creator interactions <br>& Hashtag interactions | <ul><li> Hashtag interactions are disabled </li><li> Action categories, if passed, are supported for the special ad categories </li></ul> | <ul><li>`actions` </li><li>`action_scene`</li><li>`action_period`</li><li>`video_user_actions`</li><li> `action_category_ids`</li></ul>| <ul><li> Do not set `action_scene` to `HASHTAG_RELATED` </li><li> Pass the other parameters based on results returned from [/tool/action_category/](https://business-api.tiktok.com/portal/docs?id=1737166752522241) when `special_industries` is specified in the request </li></ul> |
| Device - Device price | Disabled | `device_price_ranges` | Not passed |
| Targeting expansion | Disabled | `targeting_expansion` | Not passed |
```


