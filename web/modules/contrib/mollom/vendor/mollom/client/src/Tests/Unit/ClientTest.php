<?php

namespace Mollom\Tests\Unit;

use Mollom\Client\Client as Mollom;
use \SimpleXMLIterator;

/**
 * Tests Mollom class functionality.
 * @group mollom
 */
class ClientTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests Mollom::httpBuildQuery().
   */
  function testHttpBuildQuery() {
    // Single parameter.
    $input = array('checks' => 'spam');
    $expected = 'checks=spam';
    $this->assertEquals($expected, Mollom::httpBuildQuery($input));

    // Multiple parameters, numbers.
    $input = array('foo' => 1, 'bar' => 2);
    $expected = 'bar=2&foo=1';
    $this->assertEquals($expected, Mollom::httpBuildQuery($input));

    // Parameter with multiple values.
    $input = array('checks' => array('spam', 'profanity'));
    $expected = 'checks=profanity&checks=spam';
    $this->assertEquals($expected, Mollom::httpBuildQuery($input));

    // Parameter with multiple values, empty.
    $input = array('checks' => array('spam', ''));
    $expected = 'checks=&checks=spam';
    $this->assertEquals($expected, Mollom::httpBuildQuery($input));

    // Parameter with multiple values, NULL.
    $input = array('checks' => array('spam', NULL));
    $expected = 'checks=&checks=spam';
    $this->assertEquals($expected, Mollom::httpBuildQuery($input));

    // Multiple parameters with NULL value.
    $input = array('foo' => 1, 'checks' => NULL);
    $expected = 'checks=&foo=1';
    $this->assertEquals($expected, Mollom::httpBuildQuery($input));

    // Multiple parameters with multiple values.
    // (official OAuth protocol example)
    // @see RFC 5849 3.4.1.3.1
    $input = array(
      'b5' => '=%3D',
      'a3' => array('a', '2 q'),
      'c@' => '',
      'a2' => 'r b',
      'oauth_consumer_key' => '9djdj82h48djs9d2',
      'oauth_token' => 'kkk9d7dh3k39sjv7',
      'oauth_signature_method' => 'HMAC-SHA1',
      'oauth_timestamp' => '137131201',
      'oauth_nonce' => '7d8f3e4a',
      'c2' => '',
    );
    $expected = 'a2=r%20b&a3=2%20q&a3=a&b5=%3D%253D&c%40=&c2=&oauth_consumer_key=9djdj82h48djs9d2&oauth_nonce=7d8f3e4a&oauth_signature_method=HMAC-SHA1&oauth_timestamp=137131201&oauth_token=kkk9d7dh3k39sjv7';
    $this->assertEquals($expected, Mollom::httpBuildQuery($input));

    // Parameter with recursive multiple values.
    $input = array('checks' => array(array('spam'), array('profanity')));
    $expected = 'checks=profanity&checks=spam';
    $this->assertEquals($expected, Mollom::httpBuildQuery($input));

    // Parameter with multiple named values.
    // @todo Drop support for this?
    $input = array('checks' => array('foo' => 'spam', 'bar' => 'profanity'));
    $expected = rawurlencode('checks[bar]') . '=profanity&' . rawurlencode('checks[foo]') . '=spam';
    $this->assertEquals($expected, Mollom::httpBuildQuery($input));

    // Prior to PHP 5.3.0, rawurlencode() encoded tildes (~) as per RFC 1738.
    $input = array(
      'reserved' => ':/?#[]@!$&\'()*+,;=',
      'unreserved' => '-._~',
    );
    $expected = 'reserved=%3A%2F%3F%23%5B%5D%40%21%24%26%27%28%29%2A%2B%2C%3B%3D&unreserved=-._~';
    $this->assertEquals($expected, Mollom::httpBuildQuery($input));
  }

  /**
   * Tests Mollom::httpParseQuery().
   */
  function testHttpParseQuery() {
    $input = 'foo=1&bar=2';
    $expected = array('foo' => 1, 'bar' => 2);
    $this->assertEquals($expected, Mollom::httpParseQuery($input));

    $input = 'checks=spam&checks=profanity';
    $expected = array('checks' => array('spam', 'profanity'));
    $this->assertEquals($expected, Mollom::httpParseQuery($input));

    // Mollom::httpParseQuery() does not attempt to work transparently. Thus,
    // multiple parameter names containing brackets itself (regular PHP syntax)
    // will lead to an "unexpected" result. Although it wouldn't be hard to add
    // support for this, there's currently no need for it.
    $input = 'checks[]=spam&checks[]=profanity';
    $expected = array('checks' => array(array('spam'), array('profanity')));
    $this->assertEquals($expected, Mollom::httpParseQuery($input));

    $input = 'checks=spam&checks=';
    $expected = array('checks' => array('spam', ''));
    $this->assertEquals($expected, Mollom::httpParseQuery($input));

    $input = 'checks=spam&checks';
    $expected = array('checks' => array('spam', ''));
    $this->assertEquals($expected, Mollom::httpParseQuery($input));

    $input = 'checks=spam&';
    $expected = array('checks' => 'spam');
    $this->assertEquals($expected, Mollom::httpParseQuery($input));

    $input = 'checks=spam';
    $expected = array('checks' => 'spam');
    $this->assertEquals($expected, Mollom::httpParseQuery($input));
  }

  /**
   * Tests Mollom::parseXML().
   */
  function testParseXML() {
    $header = '<?xml version="1.0"?>';

    $input = $header . <<<EOF
<response>
  <code>0</code>
  <message>Foo.</message>
  <content>
    <contentId>321</contentId>
    <languages>
      <language>
        <languageCode>en</languageCode>
        <languageScore>1.0</languageScore>
      </language>
      <language>
        <languageCode>de</languageCode>
        <languageScore>0.5</languageScore>
      </language>
    </languages>
  </content>
</response>
EOF;
    $expected = array(
      'code' => 0,
      'message' => 'Foo.',
      'content' => array(
        'contentId' => 321,
        'languages' => array(
          array('languageCode' => 'en', 'languageScore' => 1.0),
          array('languageCode' => 'de', 'languageScore' => 0.5),
        ),
      ),
    );
    $this->assertEquals($expected, Mollom::parseXML(new SimpleXmlIterator($input)));

    $input = $header . <<<EOF
<response>
  <code>0</code>
  <message></message>
  <site>
    <publicKey>321</publicKey>
    <servers>
      <server>http://foo</server>
      <server>http://bar</server>
    </servers>
  </site>
</response>
EOF;
    $expected = array(
      'code' => 0,
      'message' => '',
      'site' => array(
        'publicKey' => 321,
        'servers' => array(
          'http://foo',
          'http://bar',
        ),
      ),
    );
    $this->assertEquals($expected, Mollom::parseXML(new SimpleXmlIterator($input)));
  }

  /**
   * Test Mollom::addAuthentication().
   *
   * @todo Requires class instance for unit testing.
   *
   * - Reserved characters (such as '%') have to be double-encoded in signature base string.
   * - Compare signature base string with PECL OAuth result, if available?
   */
}

