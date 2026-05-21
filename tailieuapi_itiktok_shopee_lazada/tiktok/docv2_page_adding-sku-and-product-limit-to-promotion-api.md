# Adding SKU and product limit to Promotion API

> Source: https://partner.tiktokshop.com/docv2/page/adding-sku-and-product-limit-to-promotion-api
> Section: Changelog
> Scraped: 2026-05-21T00:50:25.164Z

---

# What is changing?

When using [Update Activity Product](update-activity-product) and [Remove Activity Product](remove-activity-product) endpoints, a list of product\_id and sku\_id parameters is passed to determine which products and SKUs are updated or removed for certain promotional activity.  
Starting from **August 21, 2024**, we will enforce a limit of 300 SKUs and 300 products per API call for these two endpoints. Any API calls that exceed this limit will fail and return a specific error code "17029046".

# Which markets are affected?

The update applies to the local and cross-border sellers in **ALL** the markets

# Who is affected?

Developers with applications that use [Update Activity Product](update-activity-product) and [Remove Activity Product](remove-activity-product)

# Which version is applicable?

The limits apply to all versions of the related APIs

# What action is required?

Please ensure your usage complies with this new limit to avoid disruptions in service.
