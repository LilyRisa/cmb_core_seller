---
title: "Supported dimensions"
doc_id: 1751617879104514
path: "Marketing API / Reporting / Guides / Report types / Playable ad reports / Supported dimensions"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported dimensions

A dimension is an attribute to group your data by. Playable ad reports support the following dimensions. Please note that currently, we don't support all dimensions available on TikTok Ads Manager.
# Supported dimensions

``` xtable
| Dimension Name{20%} | Description {80%}|
| --- | --- |
| playable_id| Group by playable ID.|
| country_code| Group by location code. For enum values, see [Appendix-Location IDs](https://ads.tiktok.com/marketing_api/docs?id=1739311040498689). <br>Note that `country_code` only works for playable synchronous reports.  <br>See [Supported metrics for a dimension in playable ad reports](https://ads.tiktok.com/marketing_api/docs?id=1762405483224065) to learn about the supported metrics for this dimension.|
```

# Dimension grouping
- playable_id
- playable_id + country_code
