<?php
/**
 * Boolean helpers
 *
 * normalise_bool()
 */

/**
 * Normalises a boolean from various HTML input forms.
 *
 * @param mixed $v
 *
 * @return string "true" or "false"
 */
function normalise_bool( $v ): string {
	$truthy = [ '1', 1, true, 'true', 'on', 'yes' ];

	return in_array( $v, $truthy, true ) ? 'true' : 'false';
}
