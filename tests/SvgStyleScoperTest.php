<?php
namespace Recrit\SvgStyleScoper\Tests;

use Recrit\SvgStyleScoper\SvgStyleScoper;
use PHPUnit\Framework\TestCase;

/**
 * Class SvgStyleScoperTest
 */
class SvgStyleScoperTest extends TestCase {

  /**
   * Test that class styles are replaced in the SVG.
   */
  public function testSvgStyleScoperReplaceClass() {
    $data_dir = __DIR__ . '/data';
    $input = file_get_contents($data_dir . '/test-1-replace-class--input.svg');
    $expected = file_get_contents($data_dir . '/test-1-replace-class--expected.svg');

    $scoped_input = SvgStyleScoper::scopeStyles($input);
    self::assertXmlStringEqualsXmlString($expected, $scoped_input);
  }

  /**
   * Test that ID styles are replaced in the SVG.
   */
  public function testSvgStyleScoperReplaceId() {
    $data_dir = __DIR__ . '/data';
    $input = file_get_contents($data_dir . '/test-2-replace-id--input.svg');
    $expected = file_get_contents($data_dir . '/test-2-replace-id--expected.svg');

    $scoped_input = SvgStyleScoper::scopeStyles($input);
    self::assertXmlStringEqualsXmlString($expected, $scoped_input);
  }

  /**
   * Test that global non-class and non-ID styles are replaced in the SVG.
   */
  public function testSvgStyleScoperDisableGlobalStyles() {
    $data_dir = __DIR__ . '/data';
    $input = file_get_contents($data_dir . '/test-3-disable-global-styles--input.svg');
    $expected = file_get_contents($data_dir . '/test-3-disable-global-styles--expected.svg');

    $scoped_input = SvgStyleScoper::scopeStyles($input);
    self::assertXmlStringEqualsXmlString($expected, $scoped_input);
  }

  /**
   * Test that multiple style tags are processed in the SVG.
   */
  public function testSvgStyleScoperMultipleStyleTags() {
    $data_dir = __DIR__ . '/data';
    $input = file_get_contents($data_dir . '/test-4-multiple-style-tags--input.svg');
    $expected = file_get_contents($data_dir . '/test-4-multiple-style-tags--expected.svg');

    $scoped_input = SvgStyleScoper::scopeStyles($input);
    self::assertXmlStringEqualsXmlString($expected, $scoped_input);
  }

}
