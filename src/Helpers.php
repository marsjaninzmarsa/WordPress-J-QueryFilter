<?php
namespace Marsjaninzmarsa\WordPressQueryFilter;
use Spyc;

class Helpers {

	public static function LoadYaml( $file ) {
		if ( extension_loaded( 'yaml' ) ) {
			return yaml_parse_file( $file );
		} else {
			return Spyc::YAMLLoad( $file );
		}
	}

	public static function ParseYaml( $yaml ) {
		if ( extension_loaded( 'yaml' ) ) {
			return yaml_parse( $yaml );
		} else {
			return Spyc::YAMLLoadString( $yaml );
		}
	}

	public static function EncodeYaml( $array ) {
		if ( extension_loaded('yaml') ) {
			return yaml_emit( $array );
		} else {
			return Spyc::YAMLDump( $array );
		}
	}

}