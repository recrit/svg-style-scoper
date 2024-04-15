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
   * @param string|null $svg_markup
   *   The SVG markup.
   *
   * @return string
   *   The SVG markup with the styles scoped.
   */
  /**
   * Scope the SVG styles.
   *
   * @param string|null $svg_original
   *   The SVG markup.
   * @param array $options
   *   The options for this scoping process.
   *   - "allow_huge_files" (bool): Set to TRUE to allow huge files.
   *
   * @return string
   *   The SVG markup with the styles scoped.
   *   Upon any error, the original $svg_original is returned.
   */
  public static function scopeStyles(?string $svg_original, array $options = []): string {
    // Exit early if there is nothing to process.
    if (empty($svg_original) || !trim($svg_original)) {
      return $svg_original;
    }

    $dom = new \DOMDocument();
    $libxml_errors_previous_value = libxml_use_internal_errors(TRUE);
    $loaded = @$dom->loadXML($svg_original, !empty($options['allow_huge_files']) ? LIBXML_PARSEHUGE : 0);
    $libxml_errors = libxml_get_errors();
    libxml_use_internal_errors($libxml_errors_previous_value);
    libxml_clear_errors();
    if (!$loaded || $libxml_errors) {
      return $svg_original;
    }

    $svg_base_id = static::generateUniqueId($svg_original);
    $xpath = new \DOMXPath($dom);
    $svgs = $dom->getElementsByTagName('svg');
    $id_needs_suffix = $svgs->count() > 1;
    foreach ($svgs as $s => $svg) {
      $id_replacements = [];
      $svg_id = $svg_base_id . ($id_needs_suffix ? "-{$s}" : '');

      $svg_html_id = $svg->getAttribute('id');
      if ($svg_html_id) {
        $id_replacements[$svg_html_id] = "{$svg_html_id}-$svg_id";
        $svg->setAttribute('id', $id_replacements[$svg_html_id]);
      }

      foreach ($xpath->query('//*[@id]', $svg) as $node_with_id) {
        if ($node_with_id instanceof \DOMElement && strtoupper($node_with_id->tagName) !== 'SVG') {
          $node_id = $node_with_id->getAttribute('id');
          if ($node_id) {
            $new_node_id = "{$node_id}-{$svg_id}";
            $node_with_id->setAttribute('id', $new_node_id);
            $id_replacements[$node_id] = $new_node_id;
          }
        }
      }

      // Process each style tag.
      foreach ($svg->getElementsByTagName('style') as $style) {
        if (!$style->nodeValue) {
          continue;
        }

        $new_style = $style->nodeValue;

        // Replace IDs.
        foreach ($id_replacements as $id_original => $id_replacement) {
          $new_style = str_replace("#{$id_original}", "#{$id_replacement}", $new_style);
        }

        // Process each style declaration.
        $class_replacements = [];
        $new_style = preg_replace_callback('@(?<selector>[^\{]+)(?<styles>\{[^\}]+\})@U', function ($style_match) use ($svg_id, &$class_replacements) {
          // Append ID to the first part of the selector to scope the styles.
          // ID, class, or tag selector.
          $selector = trim($style_match['selector']);
          if (empty($selector)) {
            return $style_match[0];
          }

          // Remove the style, the all selector is not allowed.
          if (mb_substr($selector, 0, 1) === '*') {
            return '';
          }

          if (str_starts_with($selector, '.')) {
            // Replace root classes.
            if (preg_match('@^(?<prefix>[\.])(?<name>[\w\-]+)@', $selector, $selector_matches)) {
              $new_selector_name = "{$selector_matches['name']}-{$svg_id}";
              $class_replacements[$selector_matches['name']] = $new_selector_name;
              return "{$selector_matches['prefix']}{$new_selector_name} {$style_match['styles']}";
            }
          }
          elseif (str_starts_with($selector, '#')) {
            // Allow root ID selectors since IDs have been scoped above already.
            return $style_match[0];
          }

          // Remove the style by default.
          return '';
        }, $new_style);

        // Replace classes in any element.
        if ($class_replacements) {
          foreach ($xpath->query('//*[@class]', $svg) as $node_with_class) {
            if (!($node_with_class instanceof \DOMElement)) {
              continue;
            }

            $original_class_attr = $node_with_class->getAttribute('class');
            if (!$original_class_attr) {
              continue;
            }

            $classes = array_map('trim', explode(' ', $original_class_attr));
            foreach (array_keys($classes) as $class_key) {
              if (isset($class_replacements[$classes[$class_key]])) {
                $classes[$class_key] = $class_replacements[$classes[$class_key]];
              }
            }

            $new_class_attr = implode(' ', $classes);
            if ($new_class_attr !== $original_class_attr) {
              $node_with_class->setAttribute('class', $new_class_attr);
            }
          }
        }

        $style->nodeValue = $new_style;
      }
    }

    $svg_scoped = $dom->saveXML($dom->documentElement);
    return $svg_scoped ?: $svg_original;
  }

}
