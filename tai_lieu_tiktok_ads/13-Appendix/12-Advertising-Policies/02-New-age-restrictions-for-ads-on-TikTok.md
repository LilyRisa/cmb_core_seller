---
title: "New age restrictions for ads on TikTok"
doc_id: 1788755983247362
path: "Appendix / Advertising Policies / New age restrictions for ads on TikTok"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# New age restrictions for ads on TikTok

To help keep our community safe, including our younger users under the age of 18, the following new age restrictions are in place for any campaigns delivering to the United States, Latin America, the European Economic Area, the UK, Switzerland, or Canada:
- Lead Generation objective campaigns can be used to target only ages 18 and older
- Product Sales - Shop objective campaigns can be used to target only ages 18 and older 

New campaigns created using a placement that includes TikTok, Pangle, or Global App Bundle and targeting the United States, Latin America, the European Economic Area, the UK, Switzerland, or Canada will have the following updates: 

**Key changes**
- Advertisers can still reach users from 13-17 but cannot combine this with personalized targeting tools.
- Advertisers can continue to reach users aged 13-17 with location, device, or language targeting (For device targeting, device price is not allowed. For location targeting, restrict selections to country, DMA, city, or state — zip code targeting is not permitted.) 
- Advertisers will not be able to reach users aged 13-17 with gender, interest & behaviors, or any other personalization-based targeting. 

**Recommendations**

If you are affected by the above changes, adjust your integration using one of the following methods: 
- Remove `AGE_13_17` from the `age_groups` field in the impacted endpoints.
> <span style="color:DodgerBlue">**Note**</span><br>
> If you typically leave the `age_groups` field unspecified or set it as `[]`, explicitly specify the exact age groups you need, such as `AGE_18_24`, `AGE_25_34`, `AGE_35_44`, `AGE_45_54`, and `AGE_55_100`, directly in the field.
- Continue reaching users aged 13-17 using the targeting options for the device (excluding device price), language, or location (excluding zip code).

If you are unable to adjust your integration to accommodate the above changes, the system will provide the following compatibility measures:
- For certain ad group creation scenarios, if you trigger the new validation rules without manually specifying target age groups, the system will automatically set the age targeting to 18+ to pass validation.
- For ad group update scenarios, we have introduced a new field, `exclude_age_under_eighteen`, in [/adgroup/update/](https://business-api.tiktok.com/portal/docs?id=1739586761631745). If you trigger the validation rules, you can set `exclude_age_under_eighteen` to `true`, and the system will set the age targeting to 18+ to pass validation.

To learn about the product change details, see [About advertising to people under the age of 18](https://ads.tiktok.com/help/article/about-advertising-to-people-under-the-age-of-18?lang=en). 

To learn about the corresponding scenarios where your ad group creation or update will not be allowed, see the following tables.

## For all objectives
<table>
  <thead>
    <tr>
      <th colspan="2" style="width:40%">Parameters</th>
      <th colspan="3" style="width:60%">Configurations</th>
    </tr></thead>
  <tbody>
    <tr>
      <td style="width:20%">Campaign settings</td>
      <td style="width:20%"><code>objective_type</code></td>
      <td colspan="2" ><code>REACH</code><br><code>TRAFFIC</code><br><code>VIDEO_VIEWS</code><br><code>ENGAGEMENT</code><br><code>APP_PROMOTION</code><br><code>WEB_CONVERSIONS</code><br><code>LEAD_GENERATION</code><br><code>PRODUCT_SALES</code>(with <code>campaign_product_source</code> as <code>CATALOG</code>)</td>
      <td><code>RF_TRAFFIC</code></td>
    </tr>
    <tr>
      <td rowspan="4">Ad group settings</td>
      <td><ul><li><code>placement_type</code></li><li><code>placements</code></li></ul> </td>
      <td colspan="3">Any of the following:<ul><li><code>placement_type</code> is set to <code>PLACEMENT_TYPE_AUTOMATIC</code>.</li><li><code>placement_type</code> is set to <code>PLACEMENT_TYPE_NORMAL</code> and the value of <code>placements</code> contains <code>PLACEMENT_TIKTOK</code>, <code>PLACEMENT_PANGLE</code>, or <code>PLACEMENT_GLOBAL_APP_BUNDLE</code>.</li></ul></td>
    </tr>
    <tr>
      <td><code>age_groups</code></td>
      <td style="width:17%; vertical-align: top;">A value that contains <code>AGE_13_17</code></td>
      <td style="width:25%; vertical-align: top;">Any of the following:<ul><li>An empty array value (<code>[]</code>)</li><li>Not passed</li></ul></td>
      <td style="width:18%; vertical-align: top;">Any of the following:<ul><li>A value that contains <code>AGE_13_17</code></li><li>An empty array value (<code>[]</code>)</li><li>Not passed</li></ul></td>
    </tr>
    <tr>
      <td><code>location_ids</code></td>
      <td colspan="3">The targeted locations include locations in the US, Latin America, the European Economic Area, the UK, Switzerland, or Canada.</td>
    </tr>
    <tr>
      <td>Other settings</td>
      <td colspan="3">Any targeting options outside the <a href="#item-link-Supported targeting options for ages younger than 18 ">supported targeting options for ages younger than 18</a> </td>
    </tr>
    <tr>
      <td colspan="2">Is ad group creation allowed?</td>
      <td style="vertical-align: top;">❌</td>
      <td style="vertical-align: top;">✅<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The <code>age_groups</code> field will default to <code>["AGE_18_24", "AGE_25_34", "AGE_35_44", "AGE_45_54", "AGE_55_100"]</code>.</li><li>If you typically leave the <code>age_groups</code> field unspecified or set it as <code>[]</code>, we recommend that you explicitly specify the exact age groups that you want to target, such as <code>AGE_18_24</code>, in the field.</li></ul></td>
      <td style="vertical-align: top;">❌</td>
    </tr>
    <tr>
      <td colspan="2">Is ad group update allowed?</td>
      <td>❌</td>
      <td>❌</td>
      <td>❌</td>
    </tr>
  </tbody>
</table>



### Supported targeting options for ages younger than 18 
```xtable
| Targeting setting{40%} | API field {60%}|
|---|---|
| Demographics - Location | `location_ids` <br><br><p><span style="color:darkred"><b>Note</b></span>: `zipcode_ids` is not supported. |
| Demographics - Languages | `languages` |
| Device | `operating_systems`<br> `min_android_version` <br>`min_ios_version`<br> `ios14_targeting` <br>`device_model_ids` <br>`network_types`<br> `carrier_ids`<br>`isp_ids` <br><br><p><span style="color:darkred"><b>Note</b></span>: `device_price_ranges` is not supported. |
```
## For Lead Generation objective

<table>
   <thead>
    <tr>
      <th colspan="2" style="width:40%">Parameters</th>
      <th style="width:60%">Configurations</th>
    </tr>
   </thead>
  <tbody>
    <tr>
      <td style="width:20%">Campaign setting</td>
      <td style="width:20%"><code>objective_type</code></td>
      <td><code>LEAD_GENERATION</code></td>
    </tr>
    <tr>
      <td rowspan="3" >Ad group settings</td>
      <td><ul><li><code>placement_type</code></li><li><code>placements</code></li></ul></td>
      <td>Any of the following:<ul><li><code>placement_type</code> is set to <code>PLACEMENT_TYPE_AUTOMATIC</code>.</li><li><code>placement_type</code> is set to <code>PLACEMENT_TYPE_NORMAL</code> and the value of <code>placements</code> contains <code>PLACEMENT_TIKTOK</code>, <code>PLACEMENT_PANGLE</code>, or <code>PLACEMENT_GLOBAL_APP_BUNDLE</code>.</li></ul></td>
    </tr>
    <tr>
        <td><code>age_groups</code></td>
        <td>A value that contains <code>AGE_13_17</code></td>
    </tr>
    <tr>
      <td><code>location_ids</code><br><code>zipcode_ids</code></td>
      <td>The targeted locations include locations in the US, Latin America, the European Economic Area, the UK, Switzerland, or Canada.</td>
    </tr>
    <tr>
      <td colspan="2">Is ad group creation or update allowed?</td>
      <td>❌</td>
    </tr>
  </tbody>
</table>


## For Product Sales objective with `campaign_product_source` as `STORE`

<table>
  <thead>
    <tr>
        <th colspan="2" style="width:40%">Parameters</th>
        <th style="width:60%">Configurations</th>
    </tr>
   </thead>
  <tbody>
    <tr>
      <td rowspan="2"  style="width:20%">Campaign setting</td>
      <td  style="width:20%"><code>objective_type</code></td>
      <td><code>PRODUCT_SALES</code></td>
    </tr>
    <tr>
      <td><code>campaign_product_source</code></td>
      <td><code>STORE</code></td>
    </tr>
    <tr>
      <td rowspan="3">Ad group settings</td>
      <td><ul><li><code>placement_type</code></li><li><code>placements</code></li></ul> </td>
      <td>Any of the following:<ul><li><code>placement_type</code> is set to <code>PLACEMENT_TYPE_AUTOMATIC</code>.</li><li><code>placement_type</code> is set to <code>PLACEMENT_TYPE_NORMAL</code> and the value of <code>placements</code> contains <code>PLACEMENT_TIKTOK</code>, <code>PLACEMENT_PANGLE</code>, or <code>PLACEMENT_GLOBAL_APP_BUNDLE</code>.</li></ul></td>
    </tr>
    <tr>
      <td><code>age_groups</code></td>
      <td>A value that contains <code>AGE_13_17</code></td>
    </tr>
    <tr>
      <td><code>location_ids</code><br><code>zipcode_ids</code></td>
      <td>The targeted locations include locations in the US, Latin America, the European Economic Area, the UK, Switzerland, or Canada.</td>
    </tr>
    <tr>
      <td colspan="2">Is ad group creation or update allowed?</td>
      <td>❌</td>
    </tr>
  </tbody>
</table>
