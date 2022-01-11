<?php

namespace Recrit\SvgStyleScoper;

/**
 * Scopes the styles of an SVG by altering the CSS selectors.
 *
 * Scopes the styles within an SVG style tag to eliminate them styling other
 * SVGs or any element on the entire page.
 * The original CSS selector specificity is preserved after the styles have
 * been scoped. This preserves any page level styling for the SVG,
 * example: changing the color of the SVG path.
 *
 * The scoped attribute for the style tag has been deprecated so it cannot be
 * used to limit the scope of the SVG styles.
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/style#attr-scoped
 */
class SvgStyleScoper {

  /**
   * Generate a unique ID for the SVG markup.
   *
   * @param string $svg_markup
   *   The SVG markup.
   *
   * @return string
   *   The unique ID.
   */
  protected static function generateUniqueId(string $svg_markup) {
    return md5($svg_markup);
  }

  /**
   * Scope the SVG styles.
   *
   * @param string $svg_markup
   *   The SVG markup.
   *
   * @return string
   *   The SVG markup with the styles scoped.
   */
  public static function scopeStyles(string $svg_markup) {
    if (stripos($svg_markup, '</style>') === FALSE) {
      return $svg_markup;
    }

    $svg_scoped = preg_replace_callback('@\<svg[^\>]*\>.+\<\/svg\>@Uis', function ($svg_match) {
      $id = static::generateUniqueId($svg_match[0]);
      $attr_replacements = [];
      $attr_selector_map = [
        '.' => 'class',
        '#' => 'id',
      ];

      // Update selectors in all style tags.
      $svg_new = preg_replace_callback('@(?<start>\<style[^\>]*\>)(?<style_tag_content>.+)(?<end>\<\/style\>)@Uis', function ($style_tag_match) use (&$attr_replacements, $attr_selector_map, $id) {
        $selector_replacements = [];
        $styles_new = preg_replace_callback('@(?<selector>[^\{]+)(?<styles>\{[^\}]+\})@', function ($style_match) use (&$attr_replacements, &$style_selector_replacements, $attr_selector_map, $id) {
          // Append ID to the first part of the selector to scope the styles.
          // ID, class, or tag selector.
          $selector = trim($style_match['selector']);
          if (empty($selector)) {
            return $style_match[0];
          }

          if (mb_substr($selector, 0, 1) === '*') {
            // All selector is not allowed.
            return preg_replace('@^(\s*)\*@', '$1removed-' . $id, $style_match[0]);
          }
          elseif (preg_match('@^(?<prefix>[\.#\[]{0,1})(?<name>[\w\-]+)@', $selector, $selector_matches)) {
            // Match the first part of the selector.
            // Matches "tag", ".class-name", "#id", "[attribute]", or
            // "tag[attribute]".
            $new_first_selector_name = $selector_matches['name'] . '-' . $id;
            $selector_replacement = $new_first_selector_name;
            if (!empty($selector_matches['prefix'])) {
              $selector_replacement = $selector_matches['prefix'] . $selector_replacement;
            }

            $new_full_selector = str_replace($selector_matches[0], $selector_replacement, $style_match['selector']);

            // Build attribute replacements.
            if (!empty($selector_matches['prefix']) &&
                !empty($attr_selector_map[$selector_matches['prefix']])) {
              $style_selector_replacements[$selector_matches[0]] = $selector_replacement;
              $attr_replacements[$selector_matches['prefix']][$selector_matches['name']] = $new_first_selector_name;
            }

            // Replace with new selector.
            return $new_full_selector . $style_match['styles'];
          }

          // Replace with original match.
          return $style_match[0];
        }, $style_tag_match['style_tag_content']);

        // Replace selectors in any nested selectors.
        // Example:
        // ".cls-1--HASH .cls-2" converted to  ".cls-1--HASH .cls-2-HASH".
        foreach ($style_selector_replacements as $style_selector_original => $style_selector_replacement) {
          $styles_new = preg_replace('@' . preg_quote($style_selector_original, '@') . '([^\w\-])@', $style_selector_replacement . '$1', $styles_new);
        }

        return $style_tag_match['start'] . $styles_new . $style_tag_match['end'];
      }, $svg_match[0]);

      // Replace attributes in all element attributes.
      foreach ($attr_replacements as $attr_suffix => $attr_replacement) {
        if (empty($attr_selector_map[$attr_suffix])) {
          continue;
        }

        $attr_name = $attr_selector_map[$attr_suffix];
        $attr_name_regex = preg_quote($attr_name, '@');
        if (preg_match_all('@' . $attr_name_regex . '\=([\'\"])(?<attr>([\w\-]+\s*)+)\1@i', $svg_match[0], $attr_matches)) {
          $attr_replacements = [];
          foreach ($attr_matches['attr'] as $a => $attr_match) {
            $attr_replacements[$attr_matches[0][$a]] =  $attr_name . '="' . strtr($attr_match, $attr_replacement) . '"';
          }

          $svg_new = strtr($svg_new, $attr_replacements);
        }
      }

      return $svg_new;
    }, $svg_markup);

    return $svg_scoped;
  }

}
