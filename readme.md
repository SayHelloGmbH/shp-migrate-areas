# Migrate Areas

Moves content from the _Arbeitsgebiete_ pages into the custom post type sht_areas. Then parse the content to remove old accordion blocks.

Be careful with this code and these commands. There is no undo. Always backup your database first!

## Procedure

1. Activate plugin.
2. Create taxonomies.
3. Run e.g. `wp sht migrate areas 23 --term_id=41` to migrate all accordion blocks on page 23 to the custom post type, and assign them to term 41.
4. Run `wp sht fix migrated areas --level=2` to migrate the formerly second-level nested accordions in the new posts to h2 / content blocks.
5. Run `wp sht fix migrated areas --level=3` to migrate the formerly third-level nested accordions in the new posts to h3 / content blocks.

## Additional

Added later, the command `wp sht switch-language` changes the language of all posts created after _today 17:30_ to French. This was because of the
omission of the language details when creating the initial posts.

## Author

[Mark Howells-Mead, Say Hello GmbH](https://sayhello.ch/), since 21st Feburary 2025.
