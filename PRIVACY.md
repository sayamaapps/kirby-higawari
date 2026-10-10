# Higawari: privacy notes

This is an English summary of [DATENSCHUTZ.md](DATENSCHUTZ.md), the German text
block for privacy policies, which is the authoritative version. Both describe the
implementation. They are not legal advice. Each operator has to determine the legal
basis and the wording for their own site.

## What the visitor's browser does

Nothing towards any third party. The plugin sets no cookies, uses no `localStorage`,
`sessionStorage` or IndexedDB, and loads no script, font, image or embed from another
server. The page shows the menu that the server computed for that request, as plain HTML.

The plugin records nothing about the visitor. It does not read the visitor's IP address,
user agent or device clock.

## What the server computes

For each page request the server works out, from the content the operator entered,
whether the menu is valid on the day in the configured time zone, and which dishes to
show. Nothing is cached between requests inside the plugin.

The content is business information: dishes, prices and short notes. The field
"markers" is for information about the dish, not about a person.
