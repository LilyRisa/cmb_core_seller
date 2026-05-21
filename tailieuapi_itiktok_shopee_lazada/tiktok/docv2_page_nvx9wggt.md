# US market: New GTM for OTC Mandatory Attributes

> Source: https://partner.tiktokshop.com/docv2/page/nvx9wggt
> Section: Changelog
> Scraped: 2026-05-21T00:46:37.198Z

---

# Summary

### What are we launching

To meet legal requirements for over-the-counter (OTC) medicines, new mandatory compliance attributes will be introduced for 31 OTC medicine categories. Sellers will be required to complete these new attributes for all new and existing product listings in the affected categories.

### When are we launching it

The enforcement date is 4/17 and will be communicated in a follow-up notice.

### Impact

All new and existing listings in the affected categories must include the mandatory attributes to avoid listing errors or potential takedown after the enforcement date.

### Which markets are impacted?

Impacted markets: US.

# Affected Categories and Attributes

### Affected Categories

The following leaf categories are impacted by this update:

| Category Name | Leaf Category ID |
| --- | --- |
| 
Asthma

 | 

1291408

 |
| 

Thrush

 | 

1290384

 |
| 

Dandruff

 | 

1291024

 |
| 

Jock Itch

 | 

1290896

 |
| 

Nasal Treatment

 | 

1290640

 |
| 

OTC Acne Treatment

 | 

1290512

 |
| 

Yeast Infection

 | 

1290256

 |
| 

Rosacea Care

 | 

1289872

 |
| 

Psoriasis

 | 

1290000

 |
| 

Pain Relief Homeopathic Remedies

 | 

1289104

 |
| 

Aceaminophen

 | 

1289744

 |
| 

Ibuprofen

 | 

1289616

 |
| 

Muscle stimulators and accessories

 | 

1289232

 |
| 

Scar Tape, Patch

 | 

1288976

 |
| 

Joint and Muscle Pain Relief Rubs

 | 

1289360

 |
| 

Stretchmarks Removal Cream, gel, lotion, oil

 | 

1288848

 |
| 

Joint and Muscle Pain Relief Medication

 | 

1289488

 |
| 

Nail Fungus Treatments

 | 

1290768

 |
| 

Coughs & Colds

 | 

949640

 |
| 

Eczema

 | 

1290128

 |
| 

Sinus

 | 

1291280

 |
| 

Cuts & Wounds

 | 

950536

 |
| 

Digestion & Nausea

 | 

949768

 |
| 

Allergies

 | 

1291536

 |
| 

Antifungal Remedies

 | 

1889040

 |
| 

Acetaminophen

 | 

1070728

 |
| 

Ibuprofen

 | 

1070856

 |
| 

Aspirin

 | 

1064968

 |
| 

Baby & Child Cold & Flu Remedies

 | 

1063048

 |
| 

Baby & Child Allergy Medicine

 | 

1063176

 |
| 

Baby & Child Adhesive Bandages

 | 

1063304

 |

### Mandatory Compliance Attributes

The following attributes will become mandatory for the categories listed above:

| **attribute.name** | **attribute.id** | **Supported input type** |
| --- | --- | --- |
| 
Country of Origin

 | 

100336

 | 

Free form only

 |
| 

Manufacturer

 | 

100706

 | 

Free form only

 |
| 

Age Group

 | 

100662

 | 

Drop down only

 |
| 

Number of Items

 | 

101578

 | 

Free form only

 |
| 

Color

 | 

101328

 | 

Free form only

 |
| 

Function

 | 

101367

 | 

Free form only

 |
| 

Product Form

 | 

100335

 | 

Free form only

 |
| 

Quantity per Pack

 | 

100347

 | 

Free form only

 |
| 

Applicable Symptoms

 | 

102698

 | 

Free form only

 |
| 

Net Content Count

 | 

103361

 | 

Free form only

 |
| 

Net Weight

 | 

100342

 | 

Free form only

 |
| 

Is the Liquid Product Double Sealed

 | 

103362

 | 

Free form only

 |
| 

Is the Item Heath Sensitive?

 | 

102245

 | 

Free form only

 |
| 

Unity Count Type

 | 

102249

 | 

Free form only

 |
| 

Contains Liquid Contents?

 | 

101604

 | 

Drop down only

 |

# API Changes

### 1\. Get Attributes

The response will include the mandatory status for the listed attributes when querying affected categories.

### 2\. Get Category Rules

The `requirement_conditions` in the response will indicate that these attributes are now mandatory (market-specific once markets are confirmed).

### 3\. Create Product & Edit Product

Developers must include the mandatory attributes in the request body; missing attributes will return an error indicating the missing fields.

**For more information, please refer to the TikTok Shop Academy.**
