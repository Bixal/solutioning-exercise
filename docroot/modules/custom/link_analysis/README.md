# Link Analysis

## Installation
Normal module install procedures. When the module is installed it will create
a table link_analysis that will store a target_id and referenced id to the target_id.

When module is uninstalled it will drop the table from the working database connection.

## Setup
You will want ot navigate to /admin/config/content/link-analysis and select the
regions that you want to parse for links and then save configuration.

## How the reference sync works
On /admin/config/content/link-analysis you will notice a button label sync at top of the page.
When this button is pressed it will drop all rows in link_analysis database table and initiate
a batch process of all nodes. It will then use the link analysis service to find all internal links.

## How it works
When an entity is saved we use hook_entity_insert and hook_entity_update to initiate the
link analysis service. This service will get the render arrays for the regions you have selected
and parse them looking for internal arrays.


