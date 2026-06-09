---
title: "Available targeting settings for different targeting optimization modes in Upgraded Smart+ Ad Groups"
doc_id: 1847839827327106
path: "Appendix / Available targeting settings for different targeting optimization modes in Upgraded Smart+ Ad Groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Available targeting settings for different targeting optimization modes in Upgraded Smart+ Ad Groups

The following table outlines the available targeting settings for different targeting optimization modes in [Upgraded Smart+ Ad Groups](https://business-api.tiktok.com/portal/docs?id=1843310524500993).

<table>
    <thead>
        <tr>
            <th style="width:21%">Targeting optimization mode</th>
            <th style="width:18%">Available targeting setting</th>
            <th style="width:18%">Requirement</th>
            <th style="width:22%">Parameter</th>
            <th style="width:21%">How to configure the parameter</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="10">Automatic targeting<br>(<code>targeting_optimization_mode</code><br> as <code>AUTOMATIC</code> or not specified)</td>
            <td>Audience controls<br>· Location</td>
            <td>Specify valid locations.<br>If a catalog is specified, the location should match or be the subset of the targeting location of your catalog.</td>
            <td><code>location_ids</code> or <code>zipcode_ids</code> or both</td>
            <td>Specify valid values.<br><br>If a catalog is specified, specify IDs of locations that match or are a subset of the targeting location of <code>catalog_id</code>.<br>To obtain the targeting location of a catalog, use <a href="https://business-api.tiktok.com/portal/docs/get-catalogs/v1.3"  target="_blank">/catalog/get/</a> and check the returned <code>country</code>.</td>
        </tr>
        <tr>
            <td>Audience controls<br>· Minimum age</td>
            <td>Any of the following options:
                <ul>
                    <li>18</li>
                    <li>25</li>
                </ul>
            </td>
            <td><code>spc_audience_age</code></td>
            <td>Any of the following values:
                <ul>
                    <li><code>OVER_EIGHTEEN</code> or unspecified</li>
                    <li><code>OVER_TWENTY_FIVE</code></li>
                </ul>
            </td>
        </tr>
        <tr>
            <td>Audience controls<br>· Languages<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>languages</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Audience controls<br>· Exclude audience<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>excluded_audience_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Audience suggestions<br>· Age<br> (Optional)<br><br><p><span style="color:darkred"><b>Note</b></span>: Audience suggestions guide automatic targeting by choosing additional audience settings. These serve as suggestions only, and delivery to those audiences is not guaranteed.</td>
            <td>Enabled or disabled</td>
            <td><code>age_groups</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Audience suggestions<br>· Gender<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>gender</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Audience suggestions<br>· Custom audience<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>audience_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Audience suggestions<br>· Interests & behaviors<br>&nbsp;&nbsp;· Interests<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>interest_category_ids</code><br><code>interest_keyword_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Audience suggestions<br>· Interests & behaviors<br>&nbsp;&nbsp;· Purchase intention<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>purchase_intention_keyword_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Audience suggestions<br>· Interests & behaviors<br>&nbsp;&nbsp;· Video interactions<br>&nbsp;&nbsp;· Creator interactions<br>&nbsp;&nbsp;· Hashtag interactions<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>actions</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td rowspan="23">Custom targeting<br>(<code>targeting_optimization_mode</code> <br>as <code>MANUAL</code>)</td>
            <td>Demographics<br>· Location</td>
            <td>Specify a valid value.<br>If a catalog is specified, the location should match or be the subset of the targeting location of your catalog.</td>
            <td><code>location_ids</code> or <code>zipcode_ids</code> or both</td>
            <td>Specify a valid value.<br>If a catalog is specified, specify IDs of locations that match or are a subset of the targeting location of <code>catalog_id</code>.<br>To obtain the targeting location of a catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610">/catalog/get/</a> and check the returned <code>country</code>.</td>
        </tr>
        <tr>
            <td>Demographics<br>· HFSS Product/Brand</td>
            <td>Enabled or disabled<br>
                <ul>
                    <li>You can enable this setting when your targeting locations include locations in the UK, Australia, New Zealand, and the European Union.</li>
                </ul>
            </td>
            <td><code>is_hfss</code></td>
            <td><code>true</code> or <code>false</code>.
                <ul>
                    <li>You can set it to <code>true</code> when your targeting locations include locations in the UK, Australia, New Zealand, and the European Union.</li>
                </ul>
            </td>
        </tr>
        <tr>
            <td>Demographics<br>· LHF (Less Healthy Foods) compliance</td>
            <td>Enabled or disabled<br>
                <ul>
                    <li>This setting must be enabled when your targeting locations include locations in the UK and HFSS Product/Brand is enabled.</li>
                </ul>
            </td>
            <td><code>is_lhf_compliance</code></td>
            <td><code>true</code> or <code>false</code>.
                <ul>
                    <li>You need to set it to <code>true</code> when your targeting locations include locations in the UK and <code>is_hfss</code> is <code>true</code>.</li>
                </ul>
            </td>
        </tr>
        <tr>
            <td>Demographics<br>· Age<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>age_groups</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Demographics<br>· Gender<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>gender</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Demographics<br>· Languages<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>languages</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Custom audience<br>· Include audience<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>audience_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Custom audience<br>· Smart audience<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>smart_audience_enabled</code></td>
            <td><code>true</code> or <code>false</code></td>
        </tr>
        <tr>
            <td>Custom audience<br>· Exclude audience<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>excluded_audience_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Detailed targeting<br>· Interests & behaviors<br>&nbsp;&nbsp;· Interests<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>interest_category_ids</code><br><code>interest_keyword_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Detailed targeting<br>· Interests & behaviors<br>&nbsp;&nbsp;· Purchase intention<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>purchase_intention_keyword_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Detailed targeting<br>· Interests & behaviors<br>&nbsp;&nbsp;· Video interactions<br>&nbsp;&nbsp;· Creator interactions<br>&nbsp;&nbsp;· Hashtag interactions<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>actions</code></td>
            <td><code>true</code> or <code>false</code></td>
        </tr>
        <tr>
            <td>Detailed targeting<br>· Interests & behaviors<br>&nbsp;&nbsp;· Smart interests & behaviors<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>smart_interest_behavior_enabled</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Detailed targeting<br>· Spending power<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>spending_power</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Detailed targeting<br>· Household income<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>household_income</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Device<br>· Operating system<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>operating_systems</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Device<br>· OS versions<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>min_android_version</code><br><code>min_ios_version</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Device<br>· Device model<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>device_model_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Device<br>· Connection type<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>network_types</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Device<br>· Carriers<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>carrier_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Device<br>· Internet service provider<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>isp_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Device<br>· Device price<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>device_price_ranges</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Use saved audience<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>saved_audience_id</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
    </tbody>
</table>


