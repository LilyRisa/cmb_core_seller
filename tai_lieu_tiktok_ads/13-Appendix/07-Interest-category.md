---
title: "Interest category"
doc_id: 1738865030529025
path: "Appendix / Interest category"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Interest category

Interest category is used mainly in three ways on TikTok API for Business platform now.

One typical use case of interest categories is to include the specific interest categories when you set the targeting options. This way you can narrow down the scope of audience you want to target.

Another way to use interest categories is to pass in the accurate interest categories as grouping filter when you want to run a report. To see how to use interest categories as grouping filter for Audience reports, see [Audience report](https://ads.tiktok.com/marketing_api/docs?id=1738864928947201).

Finally, interest category is also used to classify returned data. Currently, the response of endpoint [/report/ad_benchmark/get/](https://ads.tiktok.com/marketing_api/docs?id=1738824501176321) will indicate the level 2 interest category (via `ad_category`) for gaming ads data to allow further analysis of ad performance.

## Gaming-related interest category

To help users analyze performance data of gaming-related ads, a list of gaming-related interest categories has been designed and launched. However, the returned data only show the corresponding interest category ID for the field `ad_category`. To determine the exact interest category, you need to get familiarized with the numbering rule for interest category ID and the correspondence between interest category IDs and interest category names.

TikTok API for Business platform now supports two levels of gaming-related interest category: level 1, and level 2. Interest categories related to gaming all belong to the same level-1 interest category of `Games`, and their category IDs all begin with `25`. Similarly, the IDs for interest categories grouped under the same level-2 category all start with `25xxx`. 

When you get the gaming interest category ID (via the field `ad_category` in the response of [/report/ad_benchmark/get/](https://ads.tiktok.com/marketing_api/docs?id=1738824501176321)), you need to refer to the below list to decide on the exact gaming interest categories. For instance, if `ad_category` is `25302`, then according to the list below, the interest category name is `Casino`.

```xtable
| Interest category ID{20%} | Interest category name{40%}|  Interest category full name{40%} |
| :---  | :----  | : ---|
|25	|Games (level 1) |Games|
#| 25300 | Hyper-Casual (level 2) | Games-Hyper-Casual |
#| 25301 | Action | Games-Action |
#| 25302 | Casino | Games-Casino |
#| 25303 | Match | Games-Match |
#| 25304 | Puzzle | Games-Puzzle |
#| 25305 | Racing | Games-Racing |
#| 25306 | RPG | Games-RPG |
#| 25307 | Shooting | Games-Shooting |
#| 25308 | Simulation | Games-Simulation |
#| 25309 | Sports | Games-Sports |
#| 25310 | Strategy | Games-Strategy |
#| 25311 | Party | Games-Party |
#| 25312 | Tabletop | Games-Tabletop |
#| 25313 | Kids | Games-Kids |
#| 25999 | Other | Games-Other |
```


