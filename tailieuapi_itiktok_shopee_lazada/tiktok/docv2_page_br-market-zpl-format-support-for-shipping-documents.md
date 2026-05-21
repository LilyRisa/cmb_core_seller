# BR market: ZPL format support for shipping documents

> Source: https://partner.tiktokshop.com/docv2/page/br-market-zpl-format-support-for-shipping-documents
> Section: Changelog
> Scraped: 2026-05-21T00:34:01.477Z

---

## Summary

To better support our fulfillment APIs in the BR market, we've added the Zebra Programming Language (ZPL) format to our [Get Package Shipping Document](get-package-shipping-document) endpoint.

## Affected markets and versions

This change applies to the following market(s):

-   Brazil (BR)

This change applies to the following API version(s):

-   202309 (and later)

## API changes

### Fulfillment APIs

| **Endpoint(s)** | **Change(s)** |
| --- | --- |
| 
\* Get Package Shipping Document

 | 

\* Added new query parameter: `document_format`.  
\* Shipping labels can be printed as `PDF`(default) or `ZPL`(BR market only).

 |
