<?php

class Ecwid_Integration_Polylang
{
	public $hreflang_items;

	public function __construct() {
		add_filter( 'ecwid_lang', array( $this, 'force_scriptjs_lang' ) );
		
		add_filter( 'pll_rel_hreflang_attributes', array( $this, 'set_hreflangs' ), 1, 1 );
		add_action( 'wp_head', array( $this, 'add_inline_js_config' ), 10, 1);
	}

	public function force_scriptjs_lang( $lang ) {
		$lang = pll_current_language();
		return $lang;
	}

	public function set_hreflangs( $hreflangs ) {
		$this->hreflang_items = $hreflangs;
		add_filter( 'ecwid_hreflangs', array( $this, 'get_hreflangs' ), 99, 1 );

		if( class_exists( 'Ecwid_Static_Page' ) && Ecwid_Static_Page::is_data_available() ) {
			
			$ecwid_hreflang_html = Ecwid_Static_Page::get_href_lang_html();

			if( $ecwid_hreflang_html ) {
				$ecwid_hreflangs = $this->get_hreflangs_array_from_html( $ecwid_hreflang_html );
				
				if( $ecwid_hreflangs ) {
					return $ecwid_hreflangs;
				}
			}
		}

		return $hreflangs;
	}

	public function get_hreflangs_array_from_html( $string ) {
		$pattern = "/<link rel='alternate' hreflang='(.*?)' href='(.*?)' \/>/i";

		preg_match_all( $pattern, $string, $matches );

		if( !empty( $matches[1] ) ) {
			return array_combine( $matches[1], $matches[2] );
		}

		return false;
	}

	public function get_hreflangs( $hreflangs ) {
		return $this->hreflang_items;
	}

	public function add_inline_js_config() {

		if( !is_array( $this->hreflang_items ) ) {
			return;
		}

		$js = '<script type="text/javascript">';
	
		$js .= 'window.ec = window.ec || Object();';
		$js .= 'window.ec.config = window.ec.config || Object();';

		$js .= 'window.ec.config.storefrontUrls.enableHreflangTags = true;';
		$js .= 'window.ec.config.storefrontUrls.internationalPages = {';
		
		foreach( $this->hreflang_items as $lang => $url ) {
			$js .= sprintf( '"%s": "%s",', $lang, $url );
		}

		$js .= '};';

		$js .= '</script>';

		echo $js;
	}
}

$ecwid_integration_polylang = new Ecwid_Integration_Polylang();