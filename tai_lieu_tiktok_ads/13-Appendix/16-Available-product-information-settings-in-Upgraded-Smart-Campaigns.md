---
title: "Available product information settings in Upgraded Smart+ Campaigns"
doc_id: 1860735968798721
path: "Appendix / Available product information settings in Upgraded Smart+ Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Available product information settings in Upgraded Smart+ Campaigns

The availability of the "product information" feature in Upgraded Smart+ Campaigns, configured via the `product_info` parameter, depends on various campaign settings.

The following tables outline the scenarios where `product_info` can be used to enable the "product information" feature via the [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522) or [/smart_plus/ad/update/](https://business-api.tiktok.com/portal/docs?id=1843317411665921) endpoint.


## For App Promotion ads

<table>
    <thead>
        <tr>
            <th colspan="3" style="width:45%">Campaign level</th>
            <th colspan="3" style="width:55%">Ad level</th>
        </tr>
        <tr>
            <th rowspan="2" style="width:15%">Advertising objective<br>(<code>objective_type</code>)</th>
            <th rowspan="2" style="width:15%">App promotion type<br>(<code>app_promotion_type</code>)</th>
            <th rowspan="2" style="width:15%">Use catalog<br>(<code>catalog_enabled</code>)</th>
            <th rowspan="2" style="width:20%">Is there any product information setting<br>(<code>product_info</code>) available?</th>
            <th colspan="2" style="width:35%">Specific product information settings<br>(parameters within <code>product_info</code>) available</th>
        </tr>
        <tr>
            <th  style="width:15%">Add product information<br>· Selling points<br>(<code>selling_points</code>)</th>
            <th  style="width:20%">Required setting for<br><code>product_info_enabled</code></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="4">App promotion<br>(<code>APP_PROMOTION</code>)</td>
            <td>App install<br>(<code>APP_INSTALL</code>)</td>
            <td><code>false</code></td>
            <td>✅</td>
            <td>✅</td>
            <td><code>NON_CATALOG</code></td>
        </tr>
        <tr>
            <td>App retargeting<br>(<code>APP_RETARGETING</code>)</td>
            <td><code>false</code></td>
            <td>✅</td>
            <td>✅</td>
            <td><code>NON_CATALOG</code></td>
        </tr>
        <tr>
            <td rowspan="2">TikTok Minis<br>(<code>MINIS</code>)</td>
            <td><code>false</code></td>
            <td>✅</td>
            <td>✅</td>
            <td><code>NON_CATALOG</code></td>
        </tr>
        <tr>
            <td><code>true</code></td>
            <td>❌</td>
            <td>❌</td>
            <td>Not specified</td>
        </tr>
    </tbody>
</table>
## For Sales ads
<div style="overflow-x:auto;">
 <table style="width:2500px">
    <thead>
        <tr>
            <th colspan="4"  style="width:34%">Campaign level</th>
            <th colspan="7"  style="width:66%">Ad level</th>
        </tr>
        <tr>
            <th rowspan="2"  style="width:8%">Advertising objective<br>(<code>objective_type</code>)</th>
            <th rowspan="2"  style="width:8%">Sales destination<br>(<code>sales_destination</code>)</th>
            <th rowspan="2"  style="width:8%">Use catalog<br>(<code>catalog_enabled</code>)</th>
            <th rowspan="2"  style="width:10%">Catalog type<br>(<code>catalog_type</code>)</th>
            <th rowspan="2"  style="width:8%">Is there any product information setting<br>(<code>product_info</code>) available?</th>
            <th colspan="6"  style="width:58%">Specific product information settings<br>(parameters within <code>product_info</code>) available</th>
        </tr>
        <tr>
            <th  style="width:8%">Add product information<br>· Product name<br>(<code>product_titles</code>)</th>
            <th  style="width:10%">Add product information<br>· Product image<br>(<code>product_image_list</code>)</th>
            <th  style="width:10%">Add product information<br>· Selling points<br>(<code>selling_points</code>)</th>
            <th  style="width:10%">Automatic enhancements and display options<br>· Product card settings<br>&nbsp;&nbsp;· Product information<br>(<code>catalog_tag_list</code>)</th>
            <th  style="width:10%">Add promo code or offer<br>(<code>promo_info_list</code>)</th>
            <th  style="width:10%">Required setting for<br><code>product_info_enabled</code></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="8">Sales<br>(<code>WEB_CONVERSIONS</code>)</td>
            <td rowspan="4">Website<br>(<code>WEBSITE</code>)</td>
            <td><code>false</code></td>
            <td>Not specified</td>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>❌</td>
            <td>✅</td>
            <td><code>NON_CATALOG</code></td>
        </tr>
        <tr>
            <td><code>true</code></td>
            <td>E-commerce<br>(<code>ECOMMERCE</code>)</td>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>✅</td>
            <td>❌</td>
            <td><code>CATALOG</code></td>
        </tr>
        <tr>
            <td><code>true</code></td>
            <td>Travel and entertainment<br>(<code>TRAVEL_ENTERTAINTAINMENT</code>)</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td><code>true</code></td>
            <td>Mini series<br>(<code>MINI_SERIES</code>)</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td>App<br>(<code>APP</code>)</td>
            <td><code>true</code></td>
            <td>E-commerce<br>(<code>ECOMMERCE</code>)</td>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>✅</td>
            <td>❌</td>
            <td><code>CATALOG</code></td>
        </tr>
        <tr>
            <td rowspan="3">Website and app<br>(<code>WEB_AND_APP</code>)</td>
            <td><code>false</code></td>
            <td>Not specified</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td><code>true</code></td>
            <td>E-commerce<br>(<code>ECOMMERCE</code>)</td>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>✅</td>
            <td>❌</td>
            <td><code>CATALOG</code></td>
        </tr>
        <tr>
            <td><code>true</code></td>
            <td>Travel and entertainment<br>(<code>TRAVEL_ENTERTAINTAINMENT</code>)</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>Not specified</td>
        </tr>
    </tbody>
</table>
</div>
The availability of the "product information" feature in Upgraded Smart+ Campaigns, configured via the `product_info` parameter, depends on various campaign settings.

The following tables outline the scenarios where `product_info` can be used to enable the "product information" feature via the [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522) or [/smart_plus/ad/update/](https://business-api.tiktok.com/portal/docs?id=1843317411665921) endpoint.

## For App Promotion ads

<table>
    <thead>
        <tr>
            <th colspan="3" style="width:45%">Campaign level</th>
            <th colspan="3" style="width:55%">Ad level</th>
        </tr>
        <tr>
            <th rowspan="2" style="width:15%">Advertising objective<br>(<code>objective_type</code>)</th>
            <th rowspan="2" style="width:15%">App promotion type<br>(<code>app_promotion_type</code>)</th>
            <th rowspan="2" style="width:15%">Use catalog<br>(<code>catalog_enabled</code>)</th>
            <th rowspan="2" style="width:20%">Is there any product information setting<br>(<code>product_info</code>) available?</th>
            <th colspan="2" style="width:35%">Specific product information settings<br>(parameters within <code>product_info</code>) available</th>
        </tr>
        <tr>
            <th  style="width:15%">Add product information<br>· Selling points<br>(<code>selling_points</code>)</th>
            <th  style="width:20%">Required setting for<br><code>product_info_enabled</code></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="4">App promotion<br>(<code>APP_PROMOTION</code>)</td>
            <td>App install<br>(<code>APP_INSTALL</code>)</td>
            <td><code>false</code></td>
            <td>✅</td>
            <td>✅</td>
            <td><code>NON_CATALOG</code></td>
        </tr>
        <tr>
            <td>App retargeting<br>(<code>APP_RETARGETING</code>)</td>
            <td><code>false</code></td>
            <td>✅</td>
            <td>✅</td>
            <td><code>NON_CATALOG</code></td>
        </tr>
        <tr>
            <td rowspan="2">TikTok Minis<br>(<code>MINIS</code>)</td>
            <td><code>false</code></td>
            <td>✅</td>
            <td>✅</td>
            <td><code>NON_CATALOG</code></td>
        </tr>
        <tr>
            <td><code>true</code></td>
            <td>❌</td>
            <td>❌</td>
            <td>Not specified</td>
        </tr>
    </tbody>
</table>

## For Sales ads
<div style="overflow-x:auto;">
 <table style="width:2500px">
    <thead>
        <tr>
            <th colspan="4"  style="width:34%">Campaign level</th>
            <th colspan="7"  style="width:66%">Ad level</th>
        </tr>
        <tr>
            <th rowspan="2"  style="width:8%">Advertising objective<br>(<code>objective_type</code>)</th>
            <th rowspan="2"  style="width:8%">Sales destination<br>(<code>sales_destination</code>)</th>
            <th rowspan="2"  style="width:8%">Use catalog<br>(<code>catalog_enabled</code>)</th>
            <th rowspan="2"  style="width:10%">Catalog type<br>(<code>catalog_type</code>)</th>
            <th rowspan="2"  style="width:8%">Is there any product information setting<br>(<code>product_info</code>) available?</th>
            <th colspan="6"  style="width:58%">Specific product information settings<br>(parameters within <code>product_info</code>) available</th>
        </tr>
        <tr>
            <th  style="width:8%">Add product information<br>· Product name<br>(<code>product_titles</code>)</th>
            <th  style="width:10%">Add product information<br>· Product image<br>(<code>product_image_list</code>)</th>
            <th  style="width:10%">Add product information<br>· Selling points<br>(<code>selling_points</code>)</th>
            <th  style="width:10%">Automatic enhancements and display options<br>· Product card settings<br>&nbsp;&nbsp;· Product information<br>(<code>catalog_tag_list</code>)</th>
            <th  style="width:10%">Add promo code or offer<br>(<code>promo_info_list</code>)</th>
            <th  style="width:10%">Required setting for<br><code>product_info_enabled</code></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="8">Sales<br>(<code>WEB_CONVERSIONS</code>)</td>
            <td rowspan="4">Website<br>(<code>WEBSITE</code>)</td>
            <td><code>false</code></td>
            <td>Not specified</td>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>❌</td>
            <td>✅</td>
            <td><code>NON_CATALOG</code></td>
        </tr>
        <tr>
            <td><code>true</code></td>
            <td>E-commerce<br>(<code>ECOMMERCE</code>)</td>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>✅</td>
            <td>❌</td>
            <td><code>CATALOG</code></td>
        </tr>
        <tr>
            <td><code>true</code></td>
            <td>Travel and entertainment<br>(<code>TRAVEL_ENTERTAINTAINMENT</code>)</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td><code>true</code></td>
            <td>Mini series<br>(<code>MINI_SERIES</code>)</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td>App<br>(<code>APP</code>)</td>
            <td><code>true</code></td>
            <td>E-commerce<br>(<code>ECOMMERCE</code>)</td>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>✅</td>
            <td>❌</td>
            <td><code>CATALOG</code></td>
        </tr>
        <tr>
            <td rowspan="3">Website and app<br>(<code>WEB_AND_APP</code>)</td>
            <td><code>false</code></td>
            <td>Not specified</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td><code>true</code></td>
            <td>E-commerce<br>(<code>ECOMMERCE</code>)</td>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>✅</td>
            <td>❌</td>
            <td><code>CATALOG</code></td>
        </tr>
        <tr>
            <td><code>true</code></td>
            <td>Travel and entertainment<br>(<code>TRAVEL_ENTERTAINTAINMENT</code>)</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>Not specified</td>
        </tr>
    </tbody>
</table>
</div>

## For Lead Generation ads
<div style="overflow-x:auto;">
<table style="width:2500px">
    <thead>
        <tr>
            <th colspan="2"  style="width:16%">Campaign level</th>
            <th colspan="2"  style="width:23%">Ad group level</th>
            <th colspan="7"  style="width:61%">Ad level</th>
        </tr>
        <tr>
            <th rowspan="2" style="width:8%">Advertising objective<br>(<code>objective_type</code>)</th>
            <th rowspan="2"  style="width:8%">Use catalog<br>(<code>catalog_enabled</code>)</th>
            <th rowspan="2"  style="width:15%">Optimization Location<br>(<code>promotion_type</code> and <code>promotion_target_type</code>)</th>
            <th rowspan="2"  style="width:8%">Type of catalog<br>(<code>catalog_id</code>)</th>
            <th rowspan="2"  style="width:10%">Is there any product information setting<br>(<code>product_info</code>) available?</th>
            <th colspan="6"  style="width:51%">Specific product information settings (parameters within <code>product_info</code>) available</th>
        </tr>
        <tr>
            <th  style="width:8%">Add product information<br>· Product name<br>(<code>product_titles</code>)</th>
            <th  style="width:8%">Add product information<br>· Product image<br>(<code>product_image_list</code>)</th>
            <th  style="width:9%">Add product information<br>· Selling points<br>(<code>selling_points</code>)</th>
            <th  style="width:8%">Video product information<br>(<code>catalog_tag_list</code>)</th>
            <th  style="width:8%">Add promo code or offer<br>(<code>promo_info_list</code>)</th>
            <th  style="width:10%">Required setting for<br><code>product_info_enabled</code></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="9">Lead generation<br>(<code>LEAD_GENERATION</code>)</td>
            <td><code>false</code></td>
            <td>Instant Form<br>(<code>promotion_type</code> as <code>LEAD_GENERATION</code><br>and <code>promotion_target_type</code> as <code>INSTANT_PAGE</code>)</td>
            <td>Not specified</td>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td><code>NON_CATALOG</code></td>
        </tr>
        <tr>
            <td><code>false</code></td>
            <td rowspan="3">Website<br>(<code>promotion_type</code> as <code>LEAD_GENERATION</code><br>and <code>promotion_target_type</code> as <code>EXTERNAL_WEBSITE</code>)</td>
            <td>Not specified</td>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td><code>NON_CATALOG</code></td>
        </tr>
        <tr>
            <td rowspan="2"><code>true</code></td>
            <td>Auto-Inventory</td>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>✅</td>
            <td>❌</td>
            <td><code>CATALOG</code></td>
        </tr>
        <tr>
            <td>Auto-Model</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td><code>false</code></td>
            <td rowspan="3">TikTok direct messages<br>(<code>promotion_type</code> as <code>LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE</code><br>and <code>promotion_target_type</code> not specified)</td>
            <td>Not specified</td>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td><code>NON_CATALOG</code></td>
        </tr>
        <tr>
            <td rowspan="2"><code>true</code></td>
            <td>Auto-Inventory</td>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>✅</td>
            <td>❌</td>
            <td><code>CATALOG</code></td>
        </tr>
        <tr>
            <td>Auto-Model</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>❌</td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td><code>false</code></td>
            <td>Instant messaging apps<br>(<code>promotion_type</code> as <code>LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE</code><br>and <code>promotion_target_type</code> not specified)</td>
            <td>Not specified</td>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td><code>NON_CATALOG</code></td>
        </tr>
        <tr>
            <td><code>false</code></td>
            <td>Phone call<br>(<code>promotion_type</code> as <code>LEAD_GEN_CLICK_TO_CALL</code><br>and <code>promotion_target_type</code> not specified)</td>
            <td>Not specified</td>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td><code>NON_CATALOG</code></td>
        </tr>
    </tbody>
</table>
</div>


