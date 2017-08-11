<?php

final /**
* 
*/
class Utils {
	
	/**
	 * Transform an object and all it's fields recursively into an associative array. If any object's field is
	 * an array, individual elements of the array will be transformed as well.
	 *
	 * @param object|array $object The object or array of objects to transform.
	 * @return array
	 * @since 1.9
	 */
	public static function object_to_array_deep( $object ) {
		if ( is_array( $object ) || is_object( $object ) ) {
			$result = array();
			foreach ( $object as $key => $value ) {
				$result[ $key ] = self::object_to_array_deep( $value );
			}

			return $result;
		}

		return $object;
	}	
}
