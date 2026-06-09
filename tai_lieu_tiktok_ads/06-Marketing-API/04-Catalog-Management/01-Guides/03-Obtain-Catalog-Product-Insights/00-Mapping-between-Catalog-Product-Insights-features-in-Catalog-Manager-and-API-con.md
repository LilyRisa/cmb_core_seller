---
title: "Mapping between Catalog Product Insights features in Catalog Manager and API configurations"
doc_id: 1805642902406145
path: "Marketing API / Catalog Management / Guides / Obtain Catalog Product Insights / Mapping between Catalog Product Insights features in Catalog Manager and API configurations"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Mapping between Catalog Product Insights features in Catalog Manager and API configurations

This article provides the mapping between Catalog Product Insights features in Catalog Manager, a part of TikTok Ads Manager, and the corresponding API configurations. If you are a Catalog Manager user seeking to integrate with TikTok API for Business, we recommend that you read this article beforehand to gain a clear understanding of how Catalog Product Insights features in Catalog Manager are supported in the TikTok API for Business.
<table>
  <thead>
  <tr>
    <th style="width:17%">Catalog Manager <br>module</th>
    <th style="width:16%">Catalog Manager <br>feature</th>
    <th style="width:17%">UI Reference</th>
    <th style="width:28%">API endpoint</th>
    <th style="width:20%">API configurations</th>
  </tr>
  </thead>
<tbody>
  <tr>
    <td>Insights > Your trending products</td>
    <td>Category (filter)</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/606ff24c203f5465103e6bc494ba9d14" alt="Category (filter)" width="60%" style="color:#808080" /></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1805640886872066" target="_blank">/catalog/insight/product/get/</a></td>
    <td>Request parameter<br><code>category_ids</code></td>
  </tr>
  <tr>
    <td>Insights > Your trending products</td>
    <td>Brand (filter)</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/6e247a14bd5a29528aedf64e6937aa8f" alt="Brand (filter)" width="60%" style="color:#808080" /></td>
    <td><code>/catalog/insight/product/get/</code></td>
    <td>Request parameter<br><code>brands</code></td>
  </tr>
  <tr>
    <td>Insights > Your trending products</td>
    <td>Availability (filter)</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/bee0b7aaeada1a02c1941b4aa8709798" alt="Availability (filter)" width="60%" style="color:#808080" /></td>
    <td><code>/catalog/insight/product/get/</code></td>
    <td>Request parameter<br><code>availabilities</code></td>
  </tr>
  <tr>
    <td>Insights > Your trending products</td>
    <td>Rank</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e9ef640199e23b68382d4ba8b188469e" alt="Rank" width="60%" style="color:#808080" /></td>
    <td><code>/catalog/insight/product/get/</code></td>
    <td>No corresponding parameter. <br>Indicated by the position of the product within the <code>product_insights</code> object array in the response.</td>
  </tr>
  <tr>
    <td>Insights > Your trending products</td>
    <td>Product</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/13a2eb31c6a2edc6e2c4f542698d5319" alt="Product" width="60%" style="color:#808080" /></td>
    <td><code>/catalog/insight/product/get/</code></td>
    <td>Response parameters<br><code>image_url</code> <br><code>description</code></td>
  </tr>
  <tr>
    <td>Insights > Your trending products</td>
    <td>SKU ID</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/eb3b649bbd133933c4ad9edcc3dc5703" alt="SKU ID" width="60%" style="color:#808080" /></td>
    <td><code>/catalog/insight/product/get/</code></td>
    <td>Response parameter<br><code>sku_id</code></td>
  </tr>
  <tr>
    <td>Insights > Your trending products</td>
    <td>Category</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/64f8158f5fec7b8c73a3e7e2d10e8d95" alt="Category" width="60%" style="color:#808080" /></td>
    <td><code>/catalog/insight/product/get/</code></td>
    <td>Response parameter<br><code>category_info</code></td>
  </tr>
  <tr>
    <td>Insights > Your trending products</td>
    <td>Brand</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/aa291c9dbd4e719ea9a4d438a9d89f1c" alt="Brand" width="60%" style="color:#808080" /></td>
    <td><code>/catalog/insight/product/get/</code></td>
    <td>Response parameter<br><code>brand</code></td>
  </tr>
  <tr>
    <td>Insights > Your trending products</td>
    <td>Price</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/635233c1f9e915425d6a67af5fe804cc" alt="Price" width="60%" style="color:#808080" /></td>
    <td><code>/catalog/insight/product/get/</code></td>
    <td>Response parameter<br><code>price</code></td>
  </tr>
  <tr>
    <td>Insights > Your trending products</td>
    <td>Availability</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/6a15f7c3a288af70a9cad504bce43fcc" alt="Availability" width="60%" style="color:#808080" /></td>
    <td><code>/catalog/insight/product/get/</code></td>
    <td>Response parameter<br><code>availability</code></td>
  </tr>
  <tr>
    <td>Insights > Trending categories on TikTok</td>
    <td>Category (filter)</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ada30bbe1878cd2b94555341efa6c285" alt="Category (filter)" width="60%" style="color:#808080" /></td>
    <td><code>/catalog/insight/product/get/</code></td>
    <td>Request parameter<br><code>category_ids</code></td>
  </tr>
  <tr>
    <td>Insights > Trending categories on TikTok</td>
    <td>Rank</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/9305e8a762117ca5826d3dd7d7a879ab" alt="Rank" width="60%" style="color:#808080" /></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1805640900563969" target="_blank">/catalog/insight/category/get/</a></td>
    <td>No corresponding parameter. <br>Indicated by the position of the category within the <code>category_insights</code> object array in the response.</td>
  </tr>
  <tr>
    <td>Insights > Trending categories on TikTok</td>
    <td>Category</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0d0c64e37d21f1fbacdb4d33292dfb2f" alt="Category" width="60%" style="color:#808080" /></td>
    <td><code>/catalog/insight/category/get/</code></td>
    <td>Response parameters<br><code>category_id</code> <br><code>level_info</code></td>
  </tr>
  <tr>
    <td>Insights > Trending categories on TikTok</td>
    <td>Your matching products</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ecffc4c89c1a57eda6e933f3cc7d4357" alt="Your matching products" width="60%" style="color:#808080" /></td>
    <td><code>/catalog/insight/category/get/</code></td>
    <td>Response parameter<br><code>total_products_count</code></td>
  </tr>
  <tr>
    <td>Insights > Trending categories on TikTok</td>
    <td>Available now</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d3771cfa1353dce08e0449f2a490656f" alt="Available now" width="60%" style="color:#808080" /></td>
    <td><code>/catalog/insight/category/get/</code></td>
    <td>Response parameter<br><code>product_availability_rate</code></td>
  </tr>
</tbody>
</table>
