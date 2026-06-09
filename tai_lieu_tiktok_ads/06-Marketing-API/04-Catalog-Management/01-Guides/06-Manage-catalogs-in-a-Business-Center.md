---
title: "Manage catalogs in a Business Center"
doc_id: 1738964064361474
path: "Marketing API / Catalog Management / Guides / Manage catalogs in a Business Center"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Manage catalogs in a Business Center

This article discusses how to manage catalogs in a Business Center. 

Catalogs are now managed as assets under Business Centers. To manage catalogs, developers need to make sure that the catalogs have already been migrated to a Business Center. If not, developers will need to migrate the catalogs to a Business Center first, and then manage them via the Catalog API endpoints.

# Permissions and access

First of all, developers need to have all necessary permissions to use Catalog API and BC API.

# Common scenarios

There are some common scenarios in which developers manage catalogs via TikTok API for Business. Please see what you (developers) need to do in each of the scenarios below.

##  Scenario 1

**Developers want to manage their own catalogs (catalogs created under their own ad accounts)**

**Is the catalog already migrated to a Businee Center?**
* Yes. In this case, simply use the Catalog API endpoints to create/update/delete/get catalogs. For the list of Catalog API endpoints, see [Catalog Management - API Reference](https://ads.tiktok.com/marketing_api/docs?id=1739578477445121).
* No. In this case, migrate the catalogs to Business Center, and then start managing the catalogs with the Catalog API endpoints. See the  "Migrate catalogs to a Business Center" and "Manage your catalogs after the migration" sections below for details.


## Scenario 2

**Developers want to manage others' catalogs (catalogs created under other ad accounts)**
1. Ask the catalog owner to migrate the catalog to a BC. Follow the instructions in the  "Migrate catalogs to a Business Center" section below.
2. Ask the BC Admin user to add developer to this BC.
3. Ask the BC Admin user to assign the catalog to developer. They can assign assets in [Business Center UI](https://business.tiktok.com/) or use the [/bc/asset/assign/](https://ads.tiktok.com/marketing_api/docs?id=1739438211077121) endpoint.
4. Manage the catalogs as Business Center assets. For details, see the "Manage your catalogs after migration" section below.

## Scenario 3

**Developers want to manage their own catalogs, but are not the admin user of the BC**

In this case, developers will not be able to migrate the catalog to that BC, unless the BC owner is willing to set the developer as the Admin user of the BC. 

- If developers become Admins of the Business Center,  they can migrate and manage catalogs as in Scenario 1. See the  "Migrate catalogs to a Business Center" and "Manage your catalogs after the migration" sections below for details.
- If developers cannot become the Admins of that Business Center, we suggest that developers can create their own Business Center, and migrate the catalogs. After the migration, developers can manage the catalogs as Business Center assets. See the  "Migrate catalogs to a Business Center" and "Manage your catalogs after the migration" sections below for details.

# Migrate catalogs to a Business Center

## Prerequisites
* You are a Business Center Admin. If you do not have a Business Center, create one in TikTok Ads Manager. For details, see [How to Create a TikTok Business Center](https://ads.tiktok.com/help/article?aid=12789). 
* You are the owner of the ad account that the product catalogs are assigned to. 

## Migration procedure
1. Use the [/catalog/capitalize/](https://ads.tiktok.com/marketing_api/docs?id=1740490222539778)  endpoint to convert a product catalog (including products, product sets, and video templates in this catalog) to assets under your Business Center. Repeat this step until you have converted all catalogs to your Business Center. 
2. Use [/bc/asset/get/](https://ads.tiktok.com/marketing_api/docs?id=1739432717798401) to verify that the product catalog data has been successfully migrated. Set `asset_type` to `CATALOG`. You'll get all catalogs in the Business Center. 

# Manage your catalogs after the migration

* Get all catalogs in a Business Center <br/>
Use [/bc/asset/get/](https://ads.tiktok.com/marketing_api/docs?id=1739432717798401) and set `asset_type` to `CATALOG`. 
* Manage catalogs, products, product sets, or video templates <br/>
The endpoints for these operations are not changed. The only change is that you now need to pass in `bc_id` when making the requests.
* Query for countries and regions that a catalog can be delivered to  <br/>
Use [/catalog/available_countries/get/](https://ads.tiktok.com/marketing_api/docs?id=1740491257516034) to query for regions that a catalog can be delivered to. You need to specify `bc_id` in the request. 
* Preview your ads <br/>
For a catalog under a Business Center, if it is used in an ad, you can specify the `bc_id` when previewing the ad. For details, see [Preview ads](https://ads.tiktok.com/marketing_api/docs?id=1739403070695426).
* Create or update an ad group <br/>
For creating or updating an ad group, if the catalog being used is under Business Center, you need to specify `catalog_authorized_bc_id`, which is the ID of the Business Center that the catalog is assigned to. Do not pass in this parameter if no catalogs are used. For details about this field, see [Create an ad group](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114).


