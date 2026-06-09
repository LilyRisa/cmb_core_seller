---
title: "Automatic Enhancements"
doc_id: 1855450880142402
path: "Marketing API / Campaign Management / Guides / Ad / Automatic Enhancements"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Automatic Enhancements

Automatic Enhancements is an advertising optimization product that provides creative optimization services. It utilizes TikTok's latest Symphony AIGC (AI-Generated Content) capabilities to automatically optimize videos and images that are uploaded when you create ad campaigns.

The following Automatic Enhancement options are available:

- **Translate and dub**: Connect with global audiences by delivering your ad in 50+ languages.
- **Music refresh**: Stay on-trend by swapping in music currently popular on TikTok.
- **Video quality**: Improve overall video quality with increased resolution and clarity.
- **Image quality**: Improve overall image quality with increased resolution and clarity.
- **Resize**: Resize your image to take advantage of full-screen capabilities.

## Availability
The availability of Automatic Enhancements, configured via the `creative_auto_enhancement_strategy_list` parameter, depends on the ad type (Upgraded Smart+ or Manual) and various campaign settings. The following tables detail the specific configurations that support this feature.

### For Upgraded Smart+ Ads
The following tables outline the scenarios where `creative_auto_enhancement_strategy_list` can be used to enable Automatic Enhancements via the [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522) endpoint.

> <span style="color:DodgerBlue">**Note**</span><br>
> All Automatic Enhancement strategies are available for Upgraded Smart+ Ads where supported.

#### For App Promotion ads
<div style="overflow-x:auto;">
    <table style="width:1300px">
        <thead>
            <tr>
                <th colspan="3" style="width:45%"><strong>Campaign level</strong></th>
                <th style="width:28%"><strong>Ad group level</strong></th>
                <th style="width:27%"><strong>Ad level</strong></th>
            </tr>
            <tr>
                <th rowspan="2" style="width:15%"><strong>Advertising objective</strong><br>(<code>objective_type</code>)</th>
                <th rowspan="2" style="width:16%"><strong>App promotion type</strong><br>(<code>app_promotion_type</code>)</th>
                <th rowspan="2" style="width:14%"><strong>Use catalog</strong><br>(<code>catalog_enabled</code>)</th>
                <th rowspan="2" style="width:28%"><strong>Placement </strong><br>(<code>placement_type</code> and <code>placements</code>)</th>
                <th rowspan="2" style="width:27%"><strong>Are Automatic Enhancements <br>(<code>creative_auto_enhancement_strategy_list</code>)  available?</strong></th>
            </tr>
            <tr>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td rowspan="4">App promotion<br>(<code>APP_PROMOTION</code>)</td>
                <td>App install<br>(<code>APP_INSTALL</code>)</td>
                <td><code>false</code></td>
                <td rowspan="2">Any of the following types:<ul><li><strong>Automatic Placement</strong><ul><li>To use Automatic Placement, do not specify <code>placement_type</code> and <code>placements</code>.</li></ul></li><li><strong>Select specific placements </strong>with TikTok included<ul><li>To use specific placements, set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and include <code>PLACEMENT_TIKTOK</code> in the value for <code>placements</code>.</li></ul></li></ul></td>
                <td>✅</td>
            </tr>
            <tr>
                <td>App retargeting<br>(<code>APP_RETARGETING</code>)</td>
                <td><code>false</code></td>
                <td>❌</td>
            </tr>
            <tr>
                <td rowspan="2">TikTok Minis<br>(<code>MINIS</code>)</td>
                <td><code>true</code></td>
                <td rowspan="2"><strong>Select specific placements </strong>with only TikTok placement included:<ul><li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and <code>placements</code> to <code>["PLACEMENT_TIKTOK"]</code>.</li></ul></td>
                <td>❌</td>
            </tr>
            <tr>
                <td><code>false</code></td>
                <td>❌</td>
            </tr>
        </tbody>
    </table>
</div>



#### For Lead Generation ads
<div style="overflow-x:auto;">
    <table style="width:1300px">
        <thead>
            <tr>
                <th colspan="2" style="width:29%"><strong>Campaign level</strong></th>
                <th colspan="2" style="width:50%"><strong>Ad group level</strong></th>
                <th><strong style="width:22%">Ad level</strong></th>
            </tr>
            <tr>
                <th style="width:15%"><strong>Advertising objective</strong><br>(<code>objective_type</code>)</th>
                <th style="width:13%"><strong>Use catalog</strong><br>(<code>catalog_enabled</code>)</th>
                <th style="width:25%"><strong>Optimization Location</strong><br>(<code>promotion_type</code> and <code>promotion_target_type</code>)</th>
                <th style="width:25%"><strong>Placement </strong><br>(<code>placement_type</code> and <code>placements</code>)</th>
                <th style="width:22%"><strong>Are Automatic Enhancements (<code>creative_auto_enhancement_strategy_list</code>) available?</strong></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td rowspan="7">Lead generation<br>(<code>LEAD_GENERATION</code>)</td>
                <td><code>false</code></td>
                <td>Instant Form<br>(<code>promotion_type</code> as <code>LEAD_GENERATION</code><br>and <code>promotion_target_type</code> as <code>INSTANT_PAGE</code>)</td>
                <td rowspan="2">Any of the following types:<ul><li><strong>Automatic Placement</strong><ul><li>To use Automatic Placement, do not specify <code>placement_type</code> and <code>placements</code>.</li></ul></li><li><strong>Select specific placements </strong>with TikTok included<ul><li>To use specific placements, set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and include <code>PLACEMENT_TIKTOK</code> in the value for <code>placements</code>.</li></ul></li></ul></td>
                <td>✅</td>
            </tr>
            <tr>
                <td><code>false</code></td>
                <td rowspan="2">Website<br>(<code>promotion_type</code> as <code>LEAD_GENERATION</code><br>and <code>promotion_target_type</code> as <code>EXTERNAL_WEBSITE</code>)</td>
                <td>✅</td>
            </tr>
            <tr>
                <td><code>true</code></td>
                <td rowspan="5"><strong>Select specific placements </strong>with only TikTok placement included:<ul><li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and <code>placements</code> to <code>["PLACEMENT_TIKTOK"]</code>.</li></ul></td>
                <td>❌</td>
            </tr>
            <tr>
                <td><code>false</code></td>
                <td rowspan="2">TikTok direct messages<br>(<code>promotion_type</code> as <code>LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE</code><br>and <code>promotion_target_type</code> not specified)</td>
                <td>❌</td>
            </tr>
            <tr>
                <td><code>true</code></td>
                <td>❌</td>
            </tr>
            <tr>
                <td><code>false</code></td>
                <td>Instant messaging apps<br>(<code>promotion_type</code> as <code>LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE</code><br>and <code>promotion_target_type</code> not specified)</td>
                <td>✅</td>
            </tr>
            <tr>
                <td><code>false</code></td>
                <td>Phone call<br>(<code>promotion_type</code> as <code>LEAD_GEN_CLICK_TO_CALL</code><br>and <code>promotion_target_type</code> not specified)</td>
                <td>❌</td>
            </tr>
        </tbody>
    </table>
</div>


#### For Sales ads
<div style="overflow-x:auto;">
    <table style="width:1400px">
        <thead>
            <tr>
                <th colspan="4" style="width:60%"><strong>Campaign level</strong></th>
                <th style="width:20%"><strong>Ad group level</strong></th>
                <th style="width:20%"><strong>Ad level</strong></th>
            </tr>
            <tr>
                <th style="width:17%"><strong>Advertising objective</strong><br>(<code>objective_type</code>)</th>
                <th style="width:13%"><strong>Sales destination</strong><br>(<code>sales_destination</code>)</th>
                <th style="width:13%"><strong>Use catalog</strong><br>(<code>catalog_enabled</code>)</th>
                <th style="width:17%"><strong>Catalog type</strong><br>(<code>catalog_type</code>)</th>
                <th style="width:20%"><strong>Placement </strong><br>(<code>placement_type</code> and <code>placements</code>)</th>
                <th style="width:20%"><strong>Are Automatic Enhancements (<code>creative_auto_enhancement_strategy_list</code>) available?</strong></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td rowspan="8">Sales<br>(<code>WEB_CONVERSIONS</code>)</td>
                <td rowspan="4">Website<br>(<code>WEBSITE</code>)</td>
                <td><code>false</code></td>
                <td>Not specified</td>
                <td rowspan="8">Any of the following types:<ul><li><strong>Automatic Placement</strong><ul><li>To use Automatic Placement, do not specify <code>placement_type</code> and <code>placements</code>.</li></ul></li><li><strong>Select specific placements </strong>with TikTok included<ul><li>To use specific placements, set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and include <code>PLACEMENT_TIKTOK</code> in the value for <code>placements</code>.</li></ul></li></ul></td>
                <td>✅</td>
            </tr>
            <tr>
                <td><code>true</code></td>
                <td>E-commerce<br>(<code>ECOMMERCE</code>)</td>
                <td>✅</td>
            </tr>
            <tr>
                <td><code>true</code></td>
                <td>Travel and entertainment<br>(<code>TRAVEL_ENTERTAINTAINMENT</code>)</td>
                <td>❌</td>
            </tr>
            <tr>
                <td><code>true</code></td>
                <td>Mini series<br>(<code>MINI_SERIES</code>)</td>
                <td>✅</td>
            </tr>
            <tr>
                <td>App<br>(<code>APP</code>)</td>
                <td><code>true</code></td>
                <td>E-commerce<br>(<code>ECOMMERCE</code>)</td>
                <td>✅</td>
            </tr>
            <tr>
                <td rowspan="3">Website and app<br>(<code>WEB_AND_APP</code>)</td>
                <td><code>false</code></td>
                <td>Not specified</td>
                <td>✅</td>
            </tr>
            <tr>
                <td><code>true</code></td>
                <td>E-commerce<br>(<code>ECOMMERCE</code>)</td>
                <td>✅</td>
            </tr>
            <tr>
                <td><code>true</code></td>
                <td>Travel and entertainment<br>(<code>TRAVEL_ENTERTAINTAINMENT</code>)</td>
                <td>❌</td>
            </tr>
        </tbody>
    </table>
</div>

### For Manual Ads
The following tables detail the scenarios where `creative_auto_enhancement_strategy_list` can be used to enable Automatic Enhancements via the [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354) endpoint.

> <span style="color:DodgerBlue">**Note**</span><br>
> For Manual Ads, only the following strategies are configurable via <code>creative_auto_enhancement_strategy_list</code>:</p>
> *   Video quality (`VIDEO_QUALITY`)
> *   Music refresh (`MUSIC_REFRESH`)
> *   Image quality (`IMAGE_QUALITY`)
> *   Resize (`IMAGE_RESIZE`)

#### For App Promotion ads

<div style="overflow-x:auto;">
  <table>
    <thead>
        <tr>
            <th colspan="2" style="width:35%"><strong>Campaign level</strong></th>
            <th style="width:25%"><strong>Ad group level</strong></th>
            <th style="width:30%"><strong>Ad level</strong></th>
        </tr>
        <tr>
            <th rowspan="2" style="width:17%"><strong>Advertising objective</strong><br>(<code>objective_type</code>)</th>
            <th rowspan="2" style="width:18%"><strong>App promotion type</strong><br>(<code>app_promotion_type</code>)</th>
            <th rowspan="2" style="width:25%"><strong>Placement </strong><br>(<code>placement_type</code> and <code>placements</code>)</th>
            <th rowspan="2" style="width:30%"><strong>Are Automatic Enhancements (<code>creative_auto_enhancement_strategy_list</code>)  available?</strong></th>
        </tr>
        <tr>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="3">App promotion<br>(<code>APP_PROMOTION</code>)</td>
            <td>App install<br>(<code>APP_INSTALL</code>)</td>
            <td rowspan="3">Any of the following types: <ul> <li><strong>Automatic Placement</strong> <ul> <li>To use Automatic Placement, do not specify <code>placement_type</code> and <code>placements</code>.</li> </ul> </li> <li><strong>Select specific placements </strong>with TikTok included <ul> <li>To use specific placements, set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and include <code>PLACEMENT_TIKTOK</code> in the value for <code>placements</code>.</li> </ul> </li> </ul> </td>
            <td>✅</td>
        </tr>
        <tr>
            <td>App retargeting<br>(<code>APP_RETARGETING</code>)</td>
            <td>✅</td>
        </tr>
        <tr>
            <td>App pre-registration<br>(<code>APP_PREREGISTRATION</code>)</td>
            <td>✅</td>
        </tr>
    </tbody>
</table>
</div>
  
#### For Lead Generation ads
<div style="overflow-x:auto;">
    <table style="width:1300px">
        <thead>
            <tr>
                <th colspan="2" style="width:29%"><strong>Campaign level</strong></th>
                <th colspan="2" style="width:50%"><strong>Ad group level</strong></th>
                <th><strong style="width:22%">Ad level</strong></th>
            </tr>
            <tr>
                <th style="width:15%"><strong>Advertising objective</strong><br>(<code>objective_type</code>)</th>
                <th style="width:13%"><strong>Use catalog</strong><br>(<code>catalog_enabled</code>)</th>
                <th style="width:25%"><strong>Optimization Location</strong><br>(<code>promotion_type</code> and <code>promotion_target_type</code>)</th>
                <th style="width:25%"><strong>Placement </strong><br>(<code>placement_type</code> and <code>placements</code>)</th>
                <th style="width:22%"><strong>Are Automatic Enhancements (<code>creative_auto_enhancement_strategy_list</code>) available?</strong></th>
            </tr>
        </thead>
    <tbody>
        <tr>
            <td rowspan="6">Lead generation<br>(<code>LEAD_GENERATION</code>)</td>
            <td><code>false</code></td>
            <td>Instant Form<br>(<code>promotion_type</code> as <code>LEAD_GENERATION</code><br>and <code>promotion_target_type</code> as <code>INSTANT_PAGE</code>)</td>
            <td rowspan="2">Any of the following types: <ul> <li><strong>Automatic Placement</strong> <ul> <li>To use Automatic Placement, do not specify <code>placement_type</code> and <code>placements</code>.</li> </ul> </li> <li><strong>Select specific placements </strong>with TikTok included <ul> <li>To use specific placements, set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and include <code>PLACEMENT_TIKTOK</code> in the value for <code>placements</code>.</li> </ul> </li> </ul> </td>
            <td>✅</td>
        </tr>
        <tr>
            <td><code>false</code></td>
            <td rowspan="2">Website<br>(<code>promotion_type</code> as <code>LEAD_GENERATION</code><br>and <code>promotion_target_type</code> as <code>EXTERNAL_WEBSITE</code>)</td>
            <td>✅</td>
        </tr>
        <tr>
            <td><code>true</code></td>
            <td rowspan="4"><strong>Select specific placements </strong>with only TikTok placement included: <ul> <li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and <code>placements</code> to <code>["PLACEMENT_TIKTOK"]</code>.</li> </ul> </td>
            <td>❌</td>
        </tr>
        <tr>
            <td><code>false</code></td>
            <td>TikTok direct messages<br>(<code>promotion_type</code> as <code>LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE</code><br>and <code>promotion_target_type</code> not specified)</td>
            <td>✅</td>
        </tr>
        <tr>
            <td><code>false</code></td>
            <td>Instant messaging apps<br>(<code>promotion_type</code> as <code>LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE</code><br>and <code>promotion_target_type</code> not specified)</td>
            <td>✅</td>
        </tr>
        <tr>
            <td><code>false</code></td>
            <td>Phone call<br>(<code>promotion_type</code> as <code>LEAD_GEN_CLICK_TO_CALL</code><br>and <code>promotion_target_type</code> not specified)</td>
            <td>✅</td>
        </tr>
    </tbody>
</table>
</div>

#### For Sales ads

<div style="overflow-x:auto;">
    <table style="width:1400px">
    <thead>
        <tr>
            <th colspan="3"  style="width:56%"><strong>Campaign level</strong></th>
            <th style="width:25%"><strong>Ad group level</strong></th>
            <th style="width:19%"><strong>Ad level</strong></th>
        </tr>
        <tr>
            <th style="width:21%"><strong>Advertising objective</strong><br>(<code>virtual_objective_type</code> <br>and <code>objective_type</code>)</th>
            <th style="width:13%"><strong>Sales destination</strong><br>(<code>sales_destination</code>)</th>
            <th style="width:22%"><strong>Use catalog</strong><br>(<code>catalog_enabled</code> <br>and <code>campaign_product_source</code>)</th>
            <th style="width:25%"><strong>Placement</strong><br>(<code>placement_type</code> and <code>placements</code>)</th>
            <th style="width:19%"><strong>Are Automatic Enhancements (<code>creative_auto_enhancement_strategy_list</code>)  available?</strong></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="2">Sales<br>(<code>virtual_objective_type</code> as <code>SALES</code> and <code>objective_type</code> as <code>WEB_CONVERSIONS</code>)</td>
            <td rowspan="2">Website<br>(<code>WEBSITE</code>)</td>
            <td>Not specified</td>
            <td rowspan="5">Any of the following types:<ul><li><strong>Automatic Placement</strong><ul><li>To use Automatic Placement, do not specify <code>placement_type</code> and <code>placements</code>.</li></ul></li><li><strong>Select specific placements </strong>with TikTok included<ul><li>To use specific placements, set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and include <code>PLACEMENT_TIKTOK</code> in the value for <code>placements</code>.</li></ul></li></ul></td>
            <td>✅</td>
        </tr>
        <tr>
            <td>Any of the following configurations:<ul><li><code>catalog_enabled</code> is set to <code>true</code></li><li><code>campaign_product_source</code> is set to <code>CATALOG</code></li></ul></td>
            <td>❌</td>
        </tr>
        <tr>
            <td>Sales<br>(<code>virtual_objective_type</code> as <code>SALES</code> and <code>objective_type</code> as  <code>PRODUCT_SALES</code>)</td>
            <td>App<br>(<code>APP</code>)</td>
            <td><code>campaign_product_source</code> as <code>CATALOG</code></td>
            <td>❌</td>
        </tr>
        <tr>
            <td rowspan="2">Sales<br>(<code>virtual_objective_type</code> as <code>SALES</code> and <code>objective_type</code> as  <code>PRODUCT_SALES</code>)</td>
            <td rowspan="2">Website and app<br>(<code>WEB_AND_APP</code>)</td>
            <td><code>campaign_product_source</code> as <code>false</code></td>
            <td>✅</td>
        </tr>
        <tr>
            <td><code>campaign_product_source</code> as <code>true</code></td>
            <td>❌</td>
        </tr>
    </tbody>
</table>
</div>

#### For other ads


<table>
    <thead>
        <tr>
            <th colspan="2" style="width:35%"><strong>Campaign level</strong></th>
            <th style="width:25%"><strong>Ad group level</strong></th>
            <th style="width:30%"><strong>Ad level</strong></th>
        </tr>
        <tr>
            <th style="width:17%"><strong>Advertising objective</strong><br>(<code>objective_type</code>)</th>
            <th style="width:18%"><strong>Campaign type</strong><br>(<code>rf_campaign_type</code>)</th>
            <th style="width:25%"><strong>Placement </strong><br>(<code>placement_type</code> and <code>placements</code>)</th>
            <th style="width:30%"><strong>Are Automatic Enhancements (<code>creative_auto_enhancement_strategy_list</code>)  available?</strong></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Traffic<br>(<code>TRAFFIC</code>)</td>
            <td>N/A</td>
            <td rowspan="6">Any of the following types:<ul><li><strong>Automatic Placement</strong><ul><li>To use Automatic Placement, do not specify <code>placement_type</code> and <code>placements</code>.</li></ul></li><li><strong>Select specific placements </strong>with TikTok included<ul><li>To use specific placements, set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and include <code>PLACEMENT_TIKTOK</code> in the value for <code>placements</code>.</li></ul></li></ul></td>
            <td>✅</td>
        </tr>
        <tr>
            <td>Auction Reach<br>(<code>REACH</code>)</td>
            <td>N/A</td>
            <td>✅</td>
        </tr>
        <tr>
            <td>Reach &amp; Frequency<br>(<code>RF_REACH</code>)</td>
            <td> Reach &amp; Frequency<br>(<code>STANDARD</code>)</td>
            <td>❌</td>
        </tr>
        <tr>
            <td>Reach &amp; Frequency<br>(<code>RF_REACH</code>)</td>
            <td>TikTok Pulse<br>(<code>PULSE</code>)</td>
            <td>❌</td>
        </tr>
        <tr>
            <td>Video views<br>(<code>VIDEO_VIEWS</code>)</td>
            <td>N/A</td>
            <td>✅</td>
        </tr>
        <tr>
            <td>Community interaction<br>(<code>ENGAGEMENT</code>)</td>
            <td>N/A</td>
            <td>✅</td>
        </tr>
    </tbody>
</table>

