---
title: "Brand safety"
doc_id: 1739381752981505
path: "Marketing API / Campaign Management / Guides / Ad group / Brand safety"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Brand safety

Brand Safety solutions are designed to keep the reputation of a brand safe when they advertise online.
This is achieved through first-party and third-party tools that help brands avoid placing ads next to inappropriate content. In building our solutions, our platform and partners subscribe to the risk categories and risk levels defined in the Brand Safety Floor and Suitability Framework by the Global Alliance for Responsible Media (GARM). Our community guidelines map directly to the Brand Safety Floor of GARM.

> <span style="color: DodgerBlue">**Note**</span><br>Brand Safety products (excluding pre-bid first-party Brand Safety solutions for `REACH`, `VIDEO_VIEWS`, `RF_REACH`and `ENGAGEMENT` objectives) are currently managed via allowlist. If you would like access to this feature, please reach out to your TikTok representative. Different brand safety products are managed separately. Please specify the brand safety product that you want to use in your application.

## Supported advertising objectives 
See the table below to learn about the supported advertising objectives for different Brand Safety features.

<table>
<thead>
  <tr>
    <th style="width:18%">Advertising objective</th>
    <th style="width:20%">Pre-bid first-party filtering <br>(TikTok Inventory Filter)</th>
    <th style="width:17%">Pre-bid third-party</th>
    <th style="width:20%">Post-bid third-party <br>brand safety measurement</th>
    <th style="width:25%">Post-bid third party <br>viewability measurement</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td><code>REACH</code><br><code>VIDEO_VIEWS</code></td>
    <td>supported</td>
    <td>supported</td>
    <td>supported</td>
    <td>supported</td>
  </tr>
  <tr>
    <td><code>RF_REACH</code></td>
    <td>supported</td>
    <td>supported</td>
    <td>supported</td>
    <td>supported</td>
  </tr>
  <tr>
    <td><code>APP_PROMOTION</code><br><code>WEB_CONVERSIONS</code><br><code>TRAFFIC</code></td>
    <td>supported</td>
    <td>unsupported</td>
    <td>supported</td>
    <td>supported for non-Carousel Ads<br>unsupported for Carousel Ads</td>
  </tr>
  <tr>
    <td><code>ENGAGEMENT</code></td>
    <td>supported</td>
    <td>unsupported</td>
    <td>supported</td>
    <td>supported</td>
  </tr>
  <tr>
    <td><code>LEAD_GENERATION</code></td>
    <td>supported</td>
    <td>unsupported</td>
    <td>supported</td>
    <td>unsupported</td>
  </tr>
  <tr>
    <td><code>PRODUCT_SALES</code></td>
    <td>supported for Video Shopping Ads and Live Shopping Ads</td>
    <td>unsupported for Video Shopping Ads and Live Shopping Ads</td>
    <td>supported for Video Shopping Ads and Live Shopping Ads</td>
    <td>only supported for Video Shopping Ads<br> not supported for Live Shopping Ads</td>
  </tr>
</tbody>
</table>

## Supported market
Different countries support different Brand Safety features and partners. See the following table for the available countries and the corresponding supported features & partners.

<table>
<thead>
  <tr>
    <th style="width:30%">Product</th>
    <th style="width:30%">Feature</th>
    <th style="width:40%">Supported Market</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="4">Pre-bid first-party brand safety filtering<br>(TikTok Inventory Filter)</td>
    <td>Limited Inventory </td>
    <td> All markets</td>
  </tr>
  <tr>
    <td>Standard Inventory</td>
    <td>All markets</td>
  </tr>
  <tr>
    <td>Full Inventory</td>
    <td>All markets</td>
  </tr>
    <tr>
    <td>Expanded Inventory</td>
    <td>All markets</td>
  </tr>
  <tr>
    <td rowspan="2">Pre-bid third-party brand safety </td>
    <td>IAS</td>
    <td>Australia<br>Canada<br>France<br>Germany<br>the United Kingdom<br>the United States</td>
  </tr>
  <tr>
    <td>OpenSlate<br> (The partner is named <strong>DoubleVerify</strong> on TikTok Ads Manager because the partner has been acquired by DoubleVerify.)</td>
    <td> Australia<br>Canada<br>Indonesia<br>the Kingdom of Saudi Arabia<br>the United Kingdom<br>the United States</td>
  </tr>
  <tr>
    <td rowspan="3">Post-bid third-party brand safety measurement</td>
    <td>IAS</td> <td>All markets excluding Belarus</td>
  </tr>
  <tr>
    <td>Zefr</td>
<td>All markets excluding:<br>Bangladesh<br>Costa Rica<br>Dominican Republic<br>Guatemala<br>Panama</td>
  </tr>
  <tr>
    <td>DoubleVerify</td>
    <td>Argentina<br>Australia<br>Austria<br>Belarus<br>Belgium<br>Brazil<br>Canada<br>Chile<br>Colombia<br>Costa Rica<br>Denmark<br>Dominican Republic<br>Ecuador<br>Finland<br>France<br>Germany<br>Guatemala<br>Indonesia<br>Ireland<br>Italy<br>Mexico<br>Netherlands<br>New Zealand<br>Norway<br>Panama<br>Peru<br>Poland<br>Portugal<br>Singapore<br>South Africa<br>Spain<br>Sweden<br>Switzerland<br>United Kingdom<br>United States<br>Uruguay</td>
  </tr>
  <tr>
    <td rowspan="3">Post-bid third-party viewability measurement</td>
    <td>Moat</td>
    <td>All markets</td>
  </tr>
  <tr>
    <td>DoubleVerify</td>
    <td>All markets</td>
  </tr>
  <tr>
    <td>IAS</td>
    <td>All markets</td>
  </tr>
</tbody>
</table>

Based on the ad delivery stage, brand safety products are divided into two groups: pre-bid filtering solutions and post-bid measurement solutions.

## Pre-bid brand safety filtering
Pre-bid Brand Safety filtering solutions refer to the first party or third-party solutions that place ads with safe and suitable content within the TikTok For You feed.   
><span style="color:DodgerBlue">**Note**</span><br>
><ul><li> Pre-bid first-party Brand Safety filtering for App Promotion (<code>APP_PROMOTION</code>), Website Conversions (<code>WEB_CONVERSIONS</code>), Traffic (<code>TRAFFIC</code>), Leads Generation (<code>LEAD_GENERATION</code>) and Product sales (<code>PRODUCT_SALES</code>) objectives in Auction ads are currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li> Pre-bid third-party Brand Safety solutions are currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li> Only when <code>placements</code> is <code>&#91;"PLACEMENT_TIKTOK"&#93;</code> at the ad group level, can you use the pre-bid Brand Safety filtering solutions. The placement for Reach & Frequency ad groups is TikTok by default and doesn't need to be set. </li></ul>

You can use the following parameters to configure brand safety settings when you create an Auction ad group via [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114) or an R&F ad group via [/adgroup/rf/create/](https://business-api.tiktok.com/portal/docs?id=1738235338194945). For R&F ad groups, you can specify the `brand_safety_type` field in [/rf/inventory/estimate/](https://business-api.tiktok.com/portal/docs?id=1738235487941633) to estimate the inventory. If you use `THIRD_PARTY`, you do not need to specify the brand safety partner, because this is only for estimation purposes.

- `brand_safety_type`: Brand safety type. Once specified, this field cannot be updated. To get the countries and regions that your ads can be deployed to based on your brand safety settings, use the [/tool/region/](https://business-api.tiktok.com/portal/docs?id=1737189539571713) endpoint. You need to pass in the `brand_safety_type` field.
  - `THIRD_PARTY`: Use a third-party brand safety solution. You also need to pass in a value to the `brand_safety_partner` field. This option is valid only if the targeting region is supported by a particular third party. If this option is used, you need to pass in the supported locations to the `location_ids` field.  
  - `LIMITED_INVENTORY`: Use TikTok's brand safety solution. This solution corresponds to the <strong>Limited Inventory</strong> option on TikTok Ads Manager, and with this solution, your ads will appear next to content that doesn't contain mature themes. 
  - `STANDARD_INVENTORY`:  Use TikTok's brand safety solution. This solution corresponds to the <strong>Standard Inventory</strong> option on TikTok Ads Manager, and with this solution, your ads will appear next to content that is appropriate for most brands. 
   - `EXPANDED_INVENTORY`: Use TikTok's brand safety solution. This solution corresponds to the <b>Expanded Inventory</b> option on TikTok Ads Manager, and with this solution, your ads will appear next to content where most inappropriate content has been removed, and  that does not contain mature themes. In the next API version, `EXPANDED_INVENTORY` will replace `NO_BRAND_SAFETY` and will be the default brand safety option. 
  - `NO_BRAND_SAFETY`(to be deprecated in the next API version) : It is the default value for `brand_safety_type`. To not use any brand safety solution. This solution corresponds to the <strong>Full Inventory</strong> option on TikTok Ads Manager, and with this solution, your ads might appear next to some content featuring mature themes.
- `brand_safety_partner`: Brand safety partner. Available only when `brand_safety_type` is `THIRD_PARTY`.  Once specified, this field cannot be updated. Different partners are supported for different countries. Please see the supported markets list above.

### Eligibility for updating pre-bid brand safety filtering settings

The `brand_safety_type` of an ad group can only be updated when any of the following conditions is met:
- `schedule_start_time` of the ad group has not been reached.
- `schedule_start_time` of the ad group has been reached, but the `operation_status` is `DISABLE` and the cost is 0. To retrieve the `schedule_start_time` and `operation_status` of an ad group, use [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922). 

> <span style="color:DodgerBlue">**Note**</span><br>
> Updating Brand Safety settings, including `brand_safety_type`, `category_exclusion_ids`, and `vertical_sensitivity_id`, is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.

Once the eligibility for update is met, you can make the following allowed updates for `brand_safety_type` by using [/adgroup/update/](https://business-api.tiktok.com/portal/docs?id=1739586761631745) or [/adgroup/rf/update/](https://business-api.tiktok.com/portal/docs?id=1738235402874882):

```xtable
| Original value {20%}| Can it be updated? {20%} | Allowed new values  {60%}|
|---|---|-|
| `NO_BRAND_SAFETY` | ✅ | `EXPANDED_INVENTORY`<br>`STANDARD_INVENTORY` <br>`LIMITED_INVENTORY` |
| `EXPANDED_INVENTORY` | ✅ | `STANDARD_INVENTORY`<br> `LIMITED_INVENTORY` |
| `STANDARD_INVENTORY` | ✅ | `LIMITED_INVENTORY` |
| `LIMITED_INVENTORY` | ✅ | `STANDARD_INVENTORY` |
| `THIRD_PARTY` | ❌   | |
```
## Post-bid measurement
Post-bid measurement solutions refer to the third-party solutions for Brand Safety and Viewability measurement after ads have been delivered. 

><span style="color:DodgerBlue">**Note**</span><br>
><ul><li> Post-bid third-party measurement (including post-bid third-party brand safety measurement and post-bid third-party viewability measurement) is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li> Post-bid third-party measurement is not supported for ACO ads.</li><li> Post-bid third-party measurement is not supported for ad groups where <code>promotion_type</code> is set as <code>LIVE_SHOPPING</code>.</li></ul>

To enable post-bid third-party measurement, you need to specify `brand_safety_postbid_partner` or `viewability_postbid_partner` or both when you create an ad via [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354). Once specified, these two fields cannot be updated.

The pre-bid Brand Safety filtering setting at the ad group level will determine whether you can use both types of post-bid third-party measurement:
- If you have specified `brand_safety_type` as `NO_BRAND_SAFETY`/`EXPANDED_INVENTORY` at the ad group level, you can only pass in `viewability_postbid_partner`, but not `brand_safety_postbid_partner` at the ad level. 
- If you have specified `brand_safety_type` as a value other than `NO_BRAND_SAFETY`/`EXPANDED_INVENTORY` at the ad group level, you can pass in both `viewability_postbid_partner` and `brand_safety_postbid_partner` at the ad level.

When you want to enable both post-bid third-party brand safety measurement and post-bid third-party viewability measurement by passing in both `brand_safety_postbid_partner` and `viewability_postbid_partner`, note: 
- If you set either of these two fields as `IAS`, you need to specify both as `IAS`, and assign the same value (the wrapped VAST URL that you obtain from the measurement partner IAS) to `brand_safety_vast_url` and `viewability_vast_url`. 
- If you set two different post-bid third-party measurement partners, only the post-bid third-party brand safety measurement setting will become valid.

## Category Exclusion
Category Exclusion allows you to specify the types of content that you want to avoid showing your ads next to. You can use [/tool/content_exclusion/get/](https://business-api.tiktok.com/portal/docs?rid=7xusz43oh3h&id=1769738074686465) to get the available content exclusion categories. 
<br>To enable Category Exclusion, the following conditions should be met and you need to specify `category_exclusion_ids` when you create an ad group via [/adgroup/create/](https://business-api.tiktok.com/portal/docs?rid=7xusz43oh3h&id=1739499616346114).

- At the campaign level: 
	- `objective_type` is `REACH`, `VIDEO_VIEWS`, `ENGAGEMENT` , `RF_REACH`, `APP_PROMOTION`,`WEB_CONVERSIONS`,`TRAFFIC`, or `LEAD_GENERATION`. 
- At the ad group level: 
	- `placements` is set to `["PLACEMENT_TIKTOK"]`. 
	- `brand_safety_type` is set to `STANDARD_INVENTORY` or `LIMITED_INVENTORY`. 

## Vertical Sensitivity
Vertical Sensitivity allows you to select the vertical types that you want to avoid showing your ads next to. You can use [/tool/content_exclusion/get/](https://business-api.tiktok.com/portal/docs?rid=7xusz43oh3h&id=1769738074686465) to get the available vertical sensitivity categories. 
<br>To enable Vertical Sensitivity, the following conditions should be met and you need to specify `vertical_sensitivity_id` when you create an ad group via [/adgroup/create/](https://business-api.tiktok.com/portal/docs?rid=7xusz43oh3h&id=1739499616346114).

- At the campaign level: 
	- `objective_type` is `REACH`, `VIDEO_VIEWS`, or `ENGAGEMENT`. 
- At the ad group level: 
	- `placements` is set to `["PLACEMENT_TIKTOK"]`. 
	- `brand_safety_type` is set to `STANDARD_INVENTORY` or `LIMITED_INVENTORY`. 

## Brand Safety Hub
With Brand Safety Hub, you can define the default ad account-level first-party brand safety settings, including TikTok Inventory Filter and Suitability Controls such as Category Exclusion and Vertical Sensitivity.

Once configured, these settings will automatically apply to your future [Smart+ Campaigns](https://business-api.tiktok.com/portal/docs?id=1767959274087425) but will not apply to future regular campaigns created using [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602).

- To retrieve the current Brand Safety Hub settings of your ad account, use [/tiktok_inventory_filters/get/](https://business-api.tiktok.com/portal/docs?id=1830112550443073).
- To set or update the Brand Safety Hub settings of your ad account, use [/tiktok_inventory_filters/update/](https://business-api.tiktok.com/portal/docs?id=1830112569774274).


