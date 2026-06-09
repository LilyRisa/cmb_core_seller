---
title: "Object ID uniqueness"
doc_id: 1792139804568578
path: "Appendix / Object ID uniqueness"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Object ID uniqueness

Each object within the TikTok API for Business is usually assigned an ID. The following table illustrates the uniqueness of common ID fields. 

To learn more about these common IDs, see [Concepts](https://business-api.tiktok.com/portal/docs?id=1738928364967937).

```xtable
| ID type {20%}| Scope of Uniqueness {20%}| Globally Unique? {40%}|
|---|---|-|
| `app_id` | Global | Yes |
| `core_user_id` | Global | Yes |
| `advertiser_id` | Global | Yes |
| `campaign_id` | Global | Yes |
| `adgroup_id` | Global | Yes |
| `ad_id` | Global | Yes |
| `identity_id` | Global | Yes |
| `material_id` | Global | Yes |
| `tiktok_item_id` | Global | Yes |
| `video_id` | Global | Yes <br><p><span style="color:darkred"><b>Note</b></span>: If the same video is uploaded multiple times, different video IDs will be generated, but these video IDs correspond to the same material ID  (`material_id`).|
| `image_id` | Global | Yes |
| `music_id` | Global | Yes |
| `file_id` | Global | Yes |
| `page_id` | Global | Yes |
| `creative_portfolio_id` | Global | Yes |
| `card_id` | Global | Yes |
| `call_to_action_id` | Global | Yes |
```

