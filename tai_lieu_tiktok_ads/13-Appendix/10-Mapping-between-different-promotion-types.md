---
title: "Mapping between different promotion types"
doc_id: 1758485431181314
path: "Appendix / Mapping between different promotion types"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Mapping between different promotion types

See the table below to learn about the supported `promotion_type` value as an ad group filter (through [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922))  under a certain objective, and the corresponding `promotion_type` value as an ad group creation parameter.

<table>
<thead>
  <tr>
    <th><code>objective_type</code> value<br></th>
    <th><code>promotion_type</code> value for filtering</th>
    <th><code>promotion_type</code> value for ad group creation</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="3"><br><code>APP_PROMOTION</code><br><code>TRAFFIC</code></td>
    <td rowspan="2"><code>APP</code><br></td>
    <td><code>APP_ANDROID</code></td>
  </tr>
  <tr>
    <td><code>APP_IOS</code></td>
  </tr>
  <tr>
    <td><code>WEBSITE</code></td>
    <td><code>WEBSITE</code></td>
  </tr>
  <tr>
    <td><code>WEB_CONVERSIONS</code></td>
    <td><code>WEBSITE</code></td>
    <td><code>WEBSITE</code></td>
  </tr>
  <tr>
    <td rowspan="5"><code>LEAD_GENERATION</code></td>
    <td><code>WEBSITE</code></td>
    <td rowspan="2"><code>LEAD_GENERATION</code></td>
  </tr>
  <tr>
    <td><code>INSTANT_FORM</code></td>
  </tr>
  <tr>
    <td><code>LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE</code></td>
	<td><code>LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE</code></td>
  </tr>
  <tr>
    <td><code>LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE</code></td>
	<td><code>LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE</code></td>
  </tr>
  <tr>
    <td><code>LEAD_GEN_CLICK_TO_CALL</code></td>
	<td><code>LEAD_GEN_CLICK_TO_CALL</code></td>
  </tr>
</tbody>
</table>

