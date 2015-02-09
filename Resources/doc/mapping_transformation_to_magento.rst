Akeneo to Magento Mapping and Transformations Specifications
============================================================

This document descibes how *Akeneo entitities* are mapped and transformed into *Magento Entities*

Entities mapping overview
-------------------------

=================  ======================
 Akeneo Entity      Magento Entity
=================  ======================
attribute           attribute
family              attribute set
attribute group     attribute group
category            category
product             simple product
product group       grouped product
variant group       configurable product
associations        linked products
=================  ======================

Akeneo Attribute to Magento attribute
-------------------------------------

Please note that no validation rule (number min, max characters, validation regexp, etc...) are sent to Magento, as the
content of the attributes is already checked on Akeneo's side.

=======================  ====================
Akeneo property           Magento property
=======================  ====================
code                      code
attribute type            attribute type
scope                     scope
localizable               scope
locale specific           N.A.
usable as grid column     N.A.
usable as grid filter     N.A.
metric family             see below
created                   N.A.
updated                   N.A.
=======================  ====================

Attribute type mapping
^^^^^^^^^^^^^^^^^^^^^^

=====================  ========================
Akeneo Attribute type   Magento attribute type
=====================  ========================
Date                         Date
File                         N.A.
Identifier                   N.A.
Image                        Media image
Metric                       see below
Multi select                 Multiple select
Number                       Text field
Price                        Price
Simple Select                Dropdown
Text                         Text field
Text area                    Text Area
Yes/No                       Yes/No
=====================  ========================

Metric attribute transformation
'''''''''''''''''''''''''''''''
 - transformed into Text field
 - format : "VALUE UNIT"
 - UNIT: if defined: channel unit with conversion, else unit of the metric


Attribute scope mapping
^^^^^^^^^^^^^^^^^^^^^^^

Akeneo has only Channel as scope, but attribute content can be translated (localizable attribute).

On Magento, there's no localizable property on attribute, only scopes Global, Website and storeview.
As storeviews are usually used for translation on Magento, we map localizable to scope storeview.
Storeviews are children of website.

===================   ===========  ====================
           Akeneo side                Magento side
---------------------------------  --------------------
Scopable on channel   Localizable        Scope
===================   ===========  ====================
       -                 -              Global
       X                 -              Website
       -                 X              Storeview (see below)
       X                 X              Storeview
===================   ===========  ====================

In case of localizable only attribute, the value from the attribute needs to be sent to all storeviews
matching the locale in all website.

Akeneo attribute group to Magento attribute group
-------------------------------------------------
In Akeneo, attribute groups are attached to attribute whereas in Magento, attribute group is attached
to the couple Attribute set and attribute.

Adding attribute to attribute set procedure
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 - check if the attribute group of the Akeneo attribute already exists in the attribute set
 - if not create the attribute group in this attribute set
 - attach the attribute to the attribute set in this group

Akeneo category to Magento category
-----------------------------------

========================  ===========================
Akeneo category property   Magento category property
========================  ===========================
code                         url-key
title (localizable)          Name
N.A.                         Description
N.A.                         Thumbnail image
N.A.                         Page title
========================  ===========================

Category name
^^^^^^^^^^^^^
In Magento, the category name is scoped to storeview, allowing translation. The translations are provided by
the name in the different langages via the storeview to locale mapping.


Akeneo product to Magento product
---------------------------------

Magento mandatory attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^
Some attributes are mandatory in Magento and must be sent with products.

==================  ===========================
Magento attribute    Akeneo
==================  ===========================
   sku               identifier
   visibility        N.A. Magento Specific
   tax_class_id      defined by mapping
==================  ===========================


Magento specific
^^^^^^^^^^^^^^^^

Some attributes in Magento don't have their counterparts on Akeneo. Here is how we defined them:

==================  ===========================
Magento attribute    Origin
==================  ===========================
   visibility        defined by configuration (see visibility option in Magento)
==================  ===========================

Configurable product
^^^^^^^^^^^^^^^^^^^^
Configurable products are the variant group equivalent in Magento.
But contrary to Akeneo's variant groups, configurable products are real products with real attribute values.

So to generate the content of the configurable product, the attributes of the first product of the variant group
are extracted and applied to the configurable product, minus the variation axis that are removed.

Price calculation for configurable products
'''''''''''''''''''''''''''''''''''''''''''
In Magento, the price of a configurable is based on the following formula:
price = base_price + sum(axis_value_variations)

The base_price is the lowest price from products of the variant group (as it's used as the "from" price in Magento, used on display list and search engine in Magento.

Then each option value of the variant axis can have a variation (in fixed amount or percentage, negative or positive).

For example, we want to sell Akeneo t-shirt in different color:
 - base price: 20€
 - black: +2€
 - red: +5€
 - green: +1€

So the black Akeneo T-shirt will be sold at 22€.

Let's complexify the example with the size axis:
 - XS: -2€
 - S: 0€
 - M: 2€
 - XL: 3€

So the black XS Akeneo T-shirt will be sold at 20€ and the red Akeneo XL T-shirt will be sold at 28€.

Note: The price variation of each option value is defined by configurable product. Thus, the Black option on Akeneo T-shirt can have a different price than the same option Black on another product.

Price calculation from Akeneo
'''''''''''''''''''''''''''''
The main problem is that in most case products prices are given final in Akeneo (the aforementioned black XS Akeneo T-shirt will be at 20€ and the red XL one will be at 28€). But we still need to provides to Magento the base price and the variation for each option value.

So the price variation for each option must be calculated from the price of the different products. Moreover, it's possible than no solution exists for the variation option value.

For example:
Red XL T-shirt: 25€
Red XS T-shirt: 30€
Blue XL T-shirt: 15€
Blue XS T-shirt: 100€

Associations
^^^^^^^^^^^^
Magento support linking between products via static association types:
 - related products
 - cross-sell
 - up-sell

The configuration must provide a way to choose which Akeneo's association will map to these static Magento associations.

Stock Management
----------------
No stock management are done on Akeneo, so no inventory information will be send during the product export to Magento.
