---
title: "Mapping between Catalog Management features in Catalog Manager and API configurations"
doc_id: 1782141580104705
path: "Marketing API / Catalog Management / Guides / Mapping between Catalog Management features in Catalog Manager and API configurations"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Mapping between Catalog Management features in Catalog Manager and API configurations

This article provides the mapping between Catalog Management features in Catalog Manager, a part of TikTok Ads Manager, and the corresponding API configurations. If you are a Catalog Manager user seeking to integrate with TikTok API for Business, we recommend that you read this article beforehand to gain a clear understanding of how Catalog Management features in Catalog Manager are supported in the TikTok API for Business.

## Catalogs
The following table lists the features for creating and managing catalogs in Catalog Manager and the corresponding API configurations.
<table>
<thead>
  <tr>
    <th style="width:17%">Catalog Manager module</th>
    <th style="width:19%">Catalog Manager feature</th>
    <th style="width:24%">UI Reference</th>
    <th style="width:20%">API endpoint</th>
    <th style="width:20%">API configurations</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Product Catalog page</td>
    <td>Create catalog <ul><li>Catalog Name</li></ul></td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a1b962993f390be042e672c10d8d5f2b" alt="Product Catalog page" style="color:#808080" /><br><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/fdb69fc455fec60150c57da2afb0b362" alt="Create catalog Catalog Name" style="color:#808080" /></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740306481704961"  target="_blank">/catalog/create/</a><br></td>
    <td>Request parameter<br><code>name</code></td>
  </tr>
  <tr>
    <td>Product Catalog page</td>
    <td>Create catalog<ul><li>Business Center account</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a88265a96a0e66e6d0adfd769f3ce76c" alt="Business Center account" style="color:#808080" /></td>
    <td><code>/catalog/create/</code></td>
    <td>Request parameter<br><code>bc_id</code></td>
  </tr>
  <tr>
    <td>Product Catalog page</td>
    <td>Create catalog<ul><li>Industry</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/84527f9ad0109aeb96522f62c7c897ba"  alt="Industry" style="color:#808080" /></td>
    <td><code>/catalog/create/</code></td>
    <td>Request parameter<br><code>catalog_type</code></td>
  </tr>
  <tr>
    <td>Product Catalog page</td>
    <td>Create catalog<ul><li>Default location</li></ul> </td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/bfeb220425c0cda232f83e348c368ad1"  alt="Default location" style="color:#808080" /></td>
    <td><code>/catalog/create/</code></td>
    <td>Request parameter<br><code>region_code</code></td>
  </tr>
  <tr>
    <td>Product Catalog page</td>
    <td>Create catalog<ul><li>Default Currency</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4aa08c2b01bad948717a8c7d7948add8"  alt="Default location" style="color:#808080" /></td>
    <td><code>/catalog/create/</code></td>
    <td>Request parameter<br><code>currency</code></td>
  </tr>
  <tr>
    <td>Settings</td>
    <td>Catalog Name<ul><li>Update</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a46638ec31fa8ae0ea99f457a3a660a5"  alt="Catalog Name Update" style="color:#808080" /></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740306544966657"  target="_blank">/catalog/update/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Product Catalog page</td>
    <td>Catalog<ul><li>Delete</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/350a3b221ff028a55fa0292db94637a2"  alt="Catalog Delete" style="color:#808080" /></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740310064588801"  target="_blank">/catalog/delete/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Catalog List</td>
    <td>Catalog List</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c3c674a0c3bfffd53f892380b9381510"  alt="Catalog List" style="color:#808080" /><br></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610"  target="_blank">/catalog/get/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Product Catalog page</td>
    <td>Transfer catalog to Business Center account</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/db15408392a37df7cc1c8f9c7fb69d70"   alt="Transfer catalog to Business Center account" style="color:#808080" /><br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/47cf8bb6ebe27bc37b64babad3c47125"   alt="Warning reminder" style="color:#808080" /><br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/62f5e59af1e8dab57ac5cdb380925b0e"   alt="Select the Catalog and the Business Center" style="color:#808080" /></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740490222539778"  target="_blank">/catalog/capitalize/</a></td>
    <td></td>
  </tr>
</tbody>
</table>


## Products
The following table lists the features for adding and managing products in Catalog Manager and the corresponding API configurations.
<table>
<thead>
  <tr>
    <th style="width:17%">Catalog Manager module</th>
    <th style="width:19%">Catalog Manager feature</th>
    <th style="width:24%">UI Reference</th>
    <th style="width:20%">API endpoint</th>
    <th style="width:20%">API configurations</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Products<br></td>
    <td>Add products - Manually Add<ul><li>Main image</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/1abd8920ea4f32f47e198dec23dc7405" alt="Add products - Manually Add" style="color:#808080" ><br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2b5b7eb0e177bd23ff2eb1fc9a8ae3f5" alt="Main image" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740497429681153"  target="_blank">/catalog/product/upload/</a></td>
    <td>Request parameter<br><code>image_url</code></td>
  </tr>
  <tr>
    <td>Products<br></td>
    <td>Add products - Manually Add<ul><li>Video URL (Optional)</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c4dd811b4abe6624e4619466791d425b" alt="Video URL (Optional)" style="color:#808080" ></td>
	<td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>video_url</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Manually Add<ul><li>Basic Info - Title</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/23c51eccb616846a69a832e2b3c6b31d" alt="Basic Info - Title" style="color:#808080" ></td>
    <td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>title</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Manually Add<ul><li>Basic Info - SKU ID</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b4055acb5be05c6058827409f1b5270b" alt="Basic Info - SKU ID" style="color:#808080" ></td>
    <td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>sku_id</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Manually Add<ul><li>Basic Info - Description</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/06016a192936c198bcbbfb237b45598a" alt="Basic Info - Description" style="color:#808080" ></td>
    <td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>description</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Manually Add<ul><li>Basic Info - Brand</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5176c5d013adbab6ab77318624a70bbc" alt="Basic Info - Brand" style="color:#808080" ></td>
    <td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>brand</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Manually Add<ul><li>Basic Info - Link</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0fd34dbc27f845901d4217cbef39d4b8" alt="Basic Info - Link" style="color:#808080" ></td>
    <td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>landing_page_url</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Manually Add<ul><li>Basic Info - Condition</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/847c2b7e2c9d652e068ab1283bf06510" alt="Basic Info - Condition" style="color:#808080" ></td>
    <td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>condition</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Manually Add<ul><li>Sales Info - Availability</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/19ee453339d963ec02229ac080edf29c" alt="Sales Info - Availability" style="color:#808080" ></td>
    <td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>availability</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Manually Add<ul><li>Sales Info - Price</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/3764d85313f3bb4c059a6a23aa7160a1" alt="Sales Info - Price" style="color:#808080" ></td>
    <td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>price</code></td>
  </tr>

  <tr>
    <td>Products</td>
    <td>Add products - Manually Add<ul><li>App Links (Optional) -Android URL</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e3f9da31e4b5f509628055949911024e" alt="App Links (Optional) -Android URL" style="color:#808080" ></td>
    <td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>android_url</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Manually Add<ul><li>App Links (Optional) - Android Package ID</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ea11177509f435911e25399e29b542ff" alt="App Links (Optional) - Android Package ID" style="color:#808080" ></td>
   <td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>android_package</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Manually Add<ul><li>App Links (Optional) - Android App Name</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/913c6d6919130c208aacafb155987f65" alt="App Links (Optional) - Android App Name" style="color:#808080" ></td>
    <td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>android_app_name</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Manually Add<ul><li>App Links (Optional) - iOS URL</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5e1417db2e59b8eb11a24ea086c83771" alt="App Links (Optional) - iOS URL" style="color:#808080" ></td>
    <td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>ios_url</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Manually Add<ul><li>App Links (Optional) - iOS App Store ID</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/45e6b2b7c9de905995cfb74696f5edcb" alt="App Links (Optional) - iOS App Store ID" style="color:#808080" ></td>
    <td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>ios_app_store_id</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Manually Add<ul><li>App Links (Optional) - iOS App Name</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/23f63d63825c944e5cd4f91b68331765" alt="App Links (Optional) - iOS App Name" style="color:#808080" ></td>
    <td><code>/catalog/product/upload/</code></td>
    <td>Request parameter<br><code>ios_app_name</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Data Feed Schedule<ul><li>Data Feed Name <ul><li>Create New Product Source</li></ul></li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b900a82aa81364fafe396569aaf1a58f" alt="Data Feed Schedule" style="color:#808080" ><br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/f572ae3e8d057fd4e63c41dae3df3f7b" alt="Create New Product Source" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740665161957377"  target="_blank">/catalog/feed/create/</a></td>
    <td>Request parameter<br><code>feed_name</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Data Feed Schedule <ul><li>Data Feed URL</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b792397300f3a366b9cb046d9d567762" alt="Data Feed URL" style="color:#808080" ></td>
	<td><code>/catalog/feed/create/</code></td>
    <td>Request parameter<br><code>uri</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Data Feed Schedule <ul><li>Data Feed URL Login Details<ul><li>Username</li></ul></li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0440037a03c6ad3f74c9685016fa417a" alt="Data Feed URL Login Details Username" style="color:#808080" ></td>
    <td><code>/catalog/feed/create/</code></td>
    <td>Request parameter<br><code>username</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Data Feed Schedule<ul><li>Data Feed URL Login Details<ul><li>Password</li></ul></li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0fe5395b2540c2d00128890f9df7770b" alt="Data Feed URL Login Details Password" style="color:#808080" ></td>
    <td><code>/catalog/feed/create/</code></td>
    <td>Request parameter<br><code>password</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Data Feed Schedule<br><ul><li>Automatic Upload</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/424e5881ae270f2344e7c394d5d8faf5" alt="Automatic Upload" style="color:#808080" ></td>
    <td><code>/catalog/feed/create/</code></td>
    <td>Request parameters<br><code>interval_type</code><br><code>interval_count</code><br><code>timezone</code><br><code>day_of_month</code><br><code>hour</code><br><code>minute</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Data Feed Schedule <ul><li>Update Method</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/40cf7a303e08cd15160ade0f063f2bb5" alt="Update Method" style="color:#808080" ></td>
    <td><code>/catalog/feed/create/</code></td>
    <td>Request parameter<br><code>update_mode</code></td>
  </tr>
  <tr>
    <td>Products<br></td>
    <td>Add products - Data Feed Schedule <ul><li>Data Feed Name<ul><li>Upload to Existing Product Source</li></ul></li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c3f4bb8116e26b20d3a10018d1aa7c08" alt="Data Feed Schedule" style="color:#808080" ><br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/1fd53580dab0037379c078d2dd69289b" alt="Upload to Existing Product Source" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740665197662210"  target="_blank">/catalog/feed/update/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Product Sources</td>
    <td>Product Source list</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c8730eb1810bcc86167a85aaa153809a" alt="Product Source list" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740665183073281"  target="_blank">/catalog/feed/get/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Product Sources</td>
    <td>Product Source<ul><li>Delete</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/90cd37ad82051d0e8ffa03134ec055bd" alt="Product Source Delete" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740665210863617"  target="_blank">/catalog/feed/delete/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Product Sources</td>
    <td>Select a product source- Settings <br><ul><li>Schedule<ul><li>Set up a feed schedule</li></ul></li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e0cba5fbc246cca50c61c8c05012df11" alt="Set up a feed schedule" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1750349624999937"  target="_blank">/catalog/feed/switch/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Add products - Upload File</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e35202c5c5ca17c4d648802f1557365a" alt="Add products" style="color:#808080" ><br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/f9b86985ba9efbfdbd3d7d6306009e36" alt="Upload File" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740496787164161"  target="_blank">/catalog/product/file/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Edit products</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/675c920eae205df17d90b9b10010ed33" alt="Edit products" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740562287852546"  target="_blank">/catalog/product/update/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Delete products</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/161dbeec5dc2f9a0b5d977ebfc6c341e" alt="Delete products" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740562489236481"  target="_blank">/catalog/product/delete/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Product list </td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b32fde73bb764370839b20368bd78c49" alt="Product list " style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402"  target="_blank">/catalog/product/get/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Product Sources</td>
    <td>Upload log</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7b10153058045fa2957bf700bc56cffd" alt="Upload log" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740570027173889"  target="_blank">/catalog/product/log/</a></td>
    <td></td>
  </tr>
</tbody>
</table>


## Product sets 
The following table lists the features for creating and managing product sets in Catalog Manager and the corresponding API configurations.
<table>
<thead>
  <tr>
    <th style="width:17%">Catalog Manager module</th>
    <th style="width:19%">Catalog Manager feature</th>
    <th style="width:24%">UI Reference</th>
    <th style="width:20%">API endpoint</th>
    <th style="width:20%">API configurations</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Product Sets</td>
    <td>Create Product Set<ul><li>Use Filters<ul><li>Product set name</li></ul></li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7b2bda7f40f527e6237ac9c185be98fc" alt="Create Product Set Use Filters" style="color:#808080" ><br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4331ae9198ed5cdf0b928eab99df1113" alt="Product set name" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740572891104257"  target="_blank">/catalog/set/create/</a></td>
    <td>Request parameter<br><code>product_set_name</code></td>
  </tr>
  <tr>
    <td>Product Sets</td>
    <td>Create Product Set<ul><li>Use Filters<ul><li>Filter rules</li></ul></li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/33fe23c2d1f8d814d0bddff8205a0727" alt="Filter rules" style="color:#808080" ></td>
    <td><code>/catalog/set/create/</code></td>
    <td>Request parameter<br><code>conditions</code></td>
  </tr>
  <tr>
    <td>Product Sets</td>
    <td>Product set<ul><li>Edit</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/43ec155b678bc32236e2dbb110fad807" alt="Product set Edit" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740572974725122"  target="_blank">/catalog/set/update/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Product Sets</td>
    <td>Product set<ul><li>Delete</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/bc5157ffadfed58f14327891e6621a88" alt="Product set Delete" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740573143966722"  target="_blank">/catalog/set/delete/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Product Sets</td>
    <td>Product set list</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/73e070b70a81e9e7b24a9f92f73e400e" alt="Product set list" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740570556295169"  target="_blank">/catalog/set/get/</a></td>
    <td></td>
  </tr>
  
  <tr>
    <td>Product Sets</td>
    <td>Product list for product sets</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4f77bb1253a1d30474169cafa1397e4d" alt="Product list for product sets" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740571478441986"  target="_blank">/catalog/set/product/get/</a></td>
    <td></td>
  </tr>
</tbody>
</table>

## Event Sources
The following table lists the features for managing event sources in Catalog Manager and the corresponding API configurations.
<table>
<thead>
  <tr>
    <th style="width:17%">Catalog Manager module</th>
    <th style="width:19%">Catalog Manager feature</th>
    <th style="width:24%">UI Reference</th>
    <th style="width:20%">API endpoint</th>
    <th style="width:20%">API configurations</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Event Sources</td>
    <td><ul><li>Connect App Event Sources</li><li>Connect Pixel Event Sources</li></ul> </td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/61a033e1a417e8a83c142f4c1671db55" alt="Connect App Event or Pixel Event Sources" style="color:#808080" ><br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d7dc547e3a9d0d132424d36c8747b665" alt="Connect App Event or Pixel Event Sources" style="color:#808080" ><br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4b69727c67fcb6e45590849f5a56f933" alt="Connect" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740492491200513"  target="_blank">/catalog/eventsource/bind/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Event Sources</td>
    <td><ul><li>Disconnect App Event Sources</li><li>Disconnect Pixel Event Sources</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/02a5363f4c41eaf34292c7cb65817b7d" alt="Disconnect App Event or Pixel Event Sources" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740492512449538"  target="_blank">/catalog/eventsource/unbind/</a><br></td>
    <td></td>
  </tr>
  <tr>
    <td>Event Sources</td>
    <td>Event source list</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b85258e9c32c359aa5977e1ed271b0c3" alt="Event source list" style="color:#808080" ><br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/69d3f92c7d4651e0cf19a7635c86b3f5" alt="Event source list" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740492531343362"  target="_blank">/catalog/eventsource_bind/get/</a></td>
    <td></td>
  </tr>
</tbody>
</table>

## Videos
The following table lists the features for uploading and managing video packages in Catalog Manager and the corresponding API configurations.

<table>
<thead>
  <tr>
    <th style="width:17%">Catalog Manager module</th>
    <th style="width:19%">Catalog Manager feature</th>
    <th style="width:24%">UI Reference</th>
    <th style="width:20%">API endpoint</th>
    <th style="width:20%">API configurations</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Videos</td>
    <td>Custom templates & fonts<ul><li>My Templates<ul><li>Upload</li></ul></li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/fb8cd0cb6507d651906492133b065106" alt="Custom templates & fonts My Templates Upload" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740665346584577"  target="_blank">/catalog/template/upload/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Videos</td>
    <td>Custom templates & fonts<ul><li>My Templates<ul><li>Create</li></ul></li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/735ff0995d46558334319eb1db8cbf9d" alt="Custom templates & fonts My Templates Create" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740665273039873"  target="_blank">/catalog/video_package/create/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Videos</td>
    <td>Videos<ul><li>Name<ul><li>Edit</li></ul></li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b09df04db8278a7199bd3d2a3c02f459" alt="Videos Name Edit" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740665303669761"  target="_blank">/catalog/video_package/update/</a></td>
    <td></td>
  </tr>
  <tr>
    <td>Videos</td>
    <td>Videos<ul><li>Actions<ul><li>Delete</li></ul></li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/1d56740305ea99c3f3f532640663dac9" alt="Actions Delete" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740665332455426"  target="_blank">/catalog/video_package/delete/</a><br></td>
    <td></td>
  </tr>
  <tr>
    <td>Videos</td>
    <td>Videos<ul><li>Actions<ul><li>View</li></ul></li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b7589937c1866015949b8747a11681c6" alt="Actions View" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740665368159233"  target="_blank">/catalog/template_preview/create/</a><br></td>
    <td></td>
  </tr>
  <tr>
    <td>Videos</td>
    <td>Videos <ul><li>List</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/f4158232894c934538a0f56eb78d81c8" alt="Videos List" style="color:#808080" ></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1740574099715073"  target="_blank">/catalog/video_package/get/</a></td>
    <td></td>
  </tr>
</tbody>
</table>
