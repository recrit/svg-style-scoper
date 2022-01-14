# svg-style-scoper
Scope the styles within an SVG's inline markup to avoid the styles affecting other inline SVGs or other elements on the page. 

## Problem

A website wants to allow users to upload SVG files and display them as inline SVG markup on the site. Content editors often use the same app to create the SVGs which leads to the same HTML classes being used in the SVG markup for multiple SVGs on the same page. The last inline SVG displayed on the page will override the styles of all other SVGs above it that use the same classes.

Example: All elements on the page using the class "cls-0" would have a white fill.
```
<style type="text/css">
  .cls-0 {fill-rule:evenodd;clip-rule:evenodd;fill:#FFFFFF;}
</style>
```

## Solution

The style tag ["scoped"](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/style#attr-scoped) attribute has been deprecated, so it cannot be used to limit the scope of the SVG styles.

This library alters the SVG styles so that they only apply to the contents within the SVG.

- All style declarations within the SVG's style tag are appended with a hash. 
- The appended hash makes the styles unique to the SVG and any other placement of the exact same inline SVG displayed on the page.
- The appended hash preserves the original CSS selector specificity. Keeping the same specificity lets any page level styles (not in the SVG) to continue to work as expected. Page level style example: a site's theme changing the color of the SVG path.

### Example alterations to the SVG styles

|#| Original CSS   | Altered CSS | Updated SVG inner element attributes|Goal|
| ----------- | ----------- | ----------- | ----------- | ----------- |
|1| .cls-0 {} | .cls-0-HASH {} | class="cls-0-HASH" | Replace the class "cls-0". |
|2| .cls-1 + .cls-x {} | .cls-1-HASH + .cls-x {} | class="cls-1-HASH" | Replace the class "cls-1" only. |
|3| .cls-1 .cls-0 {} | .cls-1-HASH .cls-0-HASH {} | class="cls-0-HASH", class="cls-1-HASH" | Replace the class "cls-1" and "cls-0" since "cls-0" was replaced in #1 above. |
|4| #id-0 {} | #id-0-HASH {} | id="id-0-HASH" | Replace the ID "id-0". |
|5| path {} | REMOVED | none | Bad practice, eliminate the style. |
|6| * path {} | REMOVED | none | Bad practice, eliminate the style. |
