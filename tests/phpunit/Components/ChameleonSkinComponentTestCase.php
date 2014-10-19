<?php
/**
 * This file is part of the MediaWiki skin Chameleon.
 *
 * @copyright 2013 - 2014, Stephan Gambke
 * @license   GNU General Public License, version 3 (or any later version)
 *
 * The Chameleon skin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * The Chameleon skin is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @file
 * @ingroup Skins
 */

namespace Skins\Chameleon\Tests\Components;

use DOMDocument;
use DOMXPath;
use Skins\Chameleon\Components\Component;
use Skins\Chameleon\Tests\Util\DocumentElementFinder;
use Skins\Chameleon\Tests\Util\MockupFactory;
use Skins\Chameleon\Tests\Util\XmlFileProvider;

/**
 * @coversDefaultClass \Skins\Chameleon\Components\Component
 * @covers ::<private>
 * @covers ::<protected>
 *
 * @group   skins-chameleon
 * @group   mediawiki-databaseless
 *
 * @author Stephan Gambke
 * @since 1.0
 * @ingroup Skins
 * @ingroup Test
 */
class ChameleonSkinComponentTestCase extends \PHPUnit_Framework_TestCase {

	private $successColor = '';
	protected $classUnderTest;
	protected $componentUnderTest;

	private static $lastValidatorCallTime = 0;

	/**
	 * @covers ::__construct
	 */
	public function testCanConstruct() {

		$chameleonTemplate = $this->getChameleonSkinTemplateStub();

		/** @var $instance Component */
		$instance = new $this->classUnderTest ( $chameleonTemplate );

		$this->assertInstanceOf(
			$this->classUnderTest,
			$instance
		);

		$this->assertEquals( 0, $instance->getIndent() );
		$this->assertNull( $instance->getDomElement() );
	}

	/**
	 * @covers ::getHtml
	 */
	public function testGetHtml_withEmptyElement() {

		$chameleonTemplate = $this->getChameleonSkinTemplateStub();

		/** @var $instance Component */
		$instance = new $this->classUnderTest ( $chameleonTemplate );

		$this->assertValidHTML( $instance->getHtml() );
	}

	/**
	 * @covers ::getHtml
	 * @dataProvider domElementProviderFromSyntheticLayoutFiles
	 */
	public function testGetHtml_OnSyntheticLayoutXml( $domElement ) {

		$chameleonTemplate = $this->getChameleonSkinTemplateStub();

		/** @var $instance Component */
		$instance = new $this->classUnderTest ( $chameleonTemplate, $domElement );

		$this->assertValidHTML( $instance->getHtml() );
	}

	/**
	 * @covers ::getHtml
	 * @dataProvider domElementProviderFromDeployedLayoutFiles
	 */
	public function testGetHtml_OnDeployedLayoutXml( $domElement ) {

		if ( $domElement === null ) {
			$this->assertTrue( true );
			return;
		}

		$chameleonTemplate = $this->getChameleonSkinTemplateStub();

		/** @var $instance Component */
		$instance = new $this->classUnderTest( $chameleonTemplate, $domElement );

		$this->assertValidHTML( $instance->getHtml() );
	}

	public function domElementProviderFromSyntheticLayoutFiles() {
		$file = __DIR__ . '/../Util/Fixture/' . $this->getNameOfComponentUnderTest() . '.xml';
		$provider = array_chunk( $this->getDomElementsFromFile( $file ), 1 );
		return $provider;
	}

	public function domElementProviderFromDeployedLayoutFiles() {

		$xmlFileProvider = new XmlFileProvider( __DIR__ . '/../../../layouts' );
		$files = $xmlFileProvider->getFiles();

		$elements = array();
		foreach ( $files as $file ) {
			$elements = array_merge( $elements, $this->getDomElementsFromFile( $file ) );
		}

		if ( count( $elements ) === 0 ) {
			$elements[ ] = null;
		}

		$provider = array_chunk( $elements, 1 );

		return $provider;
	}

	protected function getDomElementsFromFile( $file ) {
		$elementFinder = new DocumentElementFinder( $file );
		return $elementFinder->getComponentsByTypeAttribute( $this->getNameOfComponentUnderTest() );
	}

	protected static function loadXML( $fragment, $isHtml = true ) {

		if ( $isHtml ) {
			$fragment = self::wrapHtmlFragment( $fragment );
		}

		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;

		if ( $isHtml === true ) {
			libxml_use_internal_errors( true );
			$result = $doc->loadHTML( $fragment );
			libxml_use_internal_errors( false );
		} else {
			$result = $doc->loadXML( $fragment );
		}

		if ( $result === true ) {
			return $doc;
		} else {
			return false;
		}

	}

	protected static function wrapHtmlFragment( $fragment ) {
		return '<!DOCTYPE html><html><head><meta charset="utf-8" /><title>SomeTitle</title></head><body>' . $fragment . '</body></html>';
	}

	/**
	 * Evaluate an HTML or XML string and assert its structure and/or contents.
	 *
	 * @todo: Currently only supports 'tag' and 'class'
	 *
	 * The first argument ($matcher) is an associative array that specifies the
	 * match criteria for the assertion:
	 *
	 *  - `id`           : the node with the given id attribute must match the
	 *                     corresponding value.
	 *  - `tag`          : the node type must match the corresponding value.
	 *  - `attributes`   : a hash. The node's attributes must match the
	 *                     corresponding values in the hash.
	 *  - `class`        : The node's class attribute must contain the given
	 *                     value.
	 *  - `content`      : The text content must match the given value.
	 *  - `parent`       : a hash. The node's parent must match the
	 *                     corresponding hash.
	 *  - `child`        : a hash. At least one of the node's immediate children
	 *                     must meet the criteria described by the hash.
	 *  - `ancestor`     : a hash. At least one of the node's ancestors must
	 *                     meet the criteria described by the hash.
	 *  - `descendant`   : a hash. At least one of the node's descendants must
	 *                     meet the criteria described by the hash.
	 *  - `children`     : a hash, for counting children of a node.
	 *                     Accepts the keys:
	 *    - `count`        : a number which must equal the number of children
	 *                       that match
	 *    - `less_than`    : the number of matching children must be greater
	 *                       than this number
	 *    - `greater_than` : the number of matching children must be less than
	 *                       this number
	 *    - `only`         : another hash consisting of the keys to use to match
	 *                       on the children, and only matching children will be
	 *                       counted
	 *
	 * @param array  $matcher
	 * @param string $actual
	 * @param string $message
	 * @param bool   $isHtml
	 */
	public static function assertTag( $matcher, $actual, $message = 'Failed asserting that the given fragment contained the described node.', $isHtml = true ) {

		$doc = self::loadXML( $actual, $isHtml );

		if ( $doc === false ) {
			self::fail( $message );
		}

		$query = '//';

		if ( array_key_exists( 'tag', $matcher ) ) {
			$query .= strtolower( $matcher[ 'tag' ] );
			unset( $matcher[ 'tag' ] );
		} else {
			$query .= '*';
		}

		if ( array_key_exists( 'class', $matcher ) ) {
			$query .= '[contains(concat(" ", normalize-space(@class), " "), " ' . $matcher[ 'class' ] . ' ")]';
			unset( $matcher[ 'class' ] );
		}

		if ( count( $matcher ) > 0 ) {
			trigger_error( 'Found unsupported matcher tags: ' . implode( ', ', array_keys( $matcher ) ), E_USER_WARNING );
		}

		$xpath = new DOMXPath( $doc );
		$entries = $xpath->query( $query );

		self::assertGreaterThan( 0, $entries->length, $message );

	}

	/**
	 * Asserts that $actual is a valid HTML fragment
	 *
	 * @todo Put this whole stuff in a \PHPUnit_Framework_Constraint and just call assertThat
	 *
	 * @param        $actual
	 * @param string $message
	 */
	public function assertValidHTML( $actual, $message = 'HTML text is not valid. ' ) {

		if ( !USE_EXTERNAL_HTML_VALIDATOR ) {

			$doc = $this->loadXML( $actual, true );
			$this->assertNotFalse( $doc, $message );

			return;
		}

		$actual = $this->wrapHtmlFragment( $actual );

		$curlVersion = curl_version();

		// cURL
		$curl = curl_init();

		curl_setopt_array( $curl, array(
			CURLOPT_CONNECTTIMEOUT => 1,
			CURLOPT_URL            => 'http://validator.w3.org/check',
			CURLOPT_USERAGENT      => 'cURL ' . $curlVersion[ 'version' ],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => array(
				'output'   => 'json',
				'fragment' => $actual,
			),
		) );

		@time_sleep_until( self::$lastValidatorCallTime + 1 );
		self::$lastValidatorCallTime = time();

		$response = curl_exec( $curl );
		$curlInfo = curl_getinfo( $curl );

		curl_close( $curl );

		if ( $response === false ) {
			$this->markTestIncomplete( 'Could not connect to validation service.' );
		}

		if ( $curlInfo[ 'http_code' ] != '200' ) {
			$this->markTestIncomplete( 'Error connecting to validation service. HTTP ' . $curlInfo[ 'http_code' ] );
		}

		$response = json_decode( $response, true );

		if ( $response === null ) {
			$this->markTestIncomplete( 'Validation service returned an invalid response (invalid JSON): ' . $response );
		}

		// fail if errors or warnings
		if ( array_key_exists( 'messages', $response ) ) {

			foreach ( $response[ 'messages' ] as $responseMessage ) {

				if ( $responseMessage[ 'type' ] === 'error' || $responseMessage[ 'type' ] === 'warning' ) {
					$this->fail( $message . ucfirst( $response[ 'messages' ][ 0 ][ 'type' ] ) . ': ' . $response[ 'messages' ][ 0 ][ 'message' ] );
				}

			}
		}

		// valid
		$this->successColor = 'bg-green,fg-black';
		$this->assertTrue( true );

	}

	public function getChameleonSkinTemplateStub() {
		return MockupFactory::makeFactory( $this )->getChameleonSkinTemplateStub();
	}

	public function getSuccessColor() {
		return $this->successColor;
	}

	public function getNameOfComponentUnderTest() {

		if ( !isset( $this->componentUnderTest ) ) {
			$components = explode( '\\', $this->classUnderTest );
			return array_pop( $components );
		}

		return $this->componentUnderTest;
	}

}
