<?php
/**
 * Template helpers
 *
 * build_url_name(), resolve_template_html(), render_item_html()
 *
 * @author  Pawel Osmolski
 * @version 1.0
 */

/**
 * Build the URL/display name for a folder using column rules and special cases.
 *
 * @param string $folderName The original folder name.
 * @param array<string,mixed> $column The column definition (may contain urlRules and specialCases).
 * @param array<int,string> $errors Reference to an array that accumulates human-readable errors.
 *
 * @return string The transformed name, or "__SKIP__" sentinel if excluded.
 */
function build_url_name( $folderName, array $column, array &$errors ) {
	$urlName = $folderName;
	if ( isset( $column['urlRules'] ) && is_array( $column['urlRules'] ) ) {
		$match       = isset( $column['urlRules']['match'] ) ? (string) $column['urlRules']['match'] : '';
		$replace     = isset( $column['urlRules']['replace'] ) ? (string) $column['urlRules']['replace'] : '';
		$matchTrim   = trim( $match );
		$replaceTrim = trim( $replace );

		if ( $matchTrim === '' && $replaceTrim === '' ) {
			// no rule
		} elseif ( ( $matchTrim === '' ) !== ( $replaceTrim === '' ) ) {
			$errors[] = 'Both urlRules.match and urlRules.replace must be set (or both empty) for column "' . htmlspecialchars( (string) ( $column['title'] ?? '' ) ) . '".';
		} else {
			set_error_handler( function () {
			}, E_WARNING );
			$ok = @preg_match( $matchTrim, '' );
			restore_error_handler();

			if ( $ok === false ) {
				$errors[] = 'Invalid regex in urlRules.match for column "' . htmlspecialchars( (string) ( $column['title'] ?? '' ) ) . '".';
			} else {
				if ( preg_match( $matchTrim, $folderName ) ) {
					$newName = @preg_replace( $replaceTrim, '', $folderName );
					if ( $newName === null ) {
						$errors[] = 'Invalid regex in urlRules.replace for column "' . htmlspecialchars( (string) ( $column['title'] ?? '' ) ) . '".';
					} else {
						$urlName = $newName;
					}
				} else {
					return '__SKIP__';
				}
			}
		}
	}

	if ( ! empty( $column['specialCases'] ) && is_array( $column['specialCases'] ) ) {
		if ( array_key_exists( $urlName, $column['specialCases'] ) ) {
			$urlName = (string) $column['specialCases'][ $urlName ];
		}
	}

	return $urlName;
}

/**
 * Resolve a template by name to HTML.
 *
 * @param string $templateName
 * @param array<string, array<string,mixed>> $templatesByName
 *
 * @return string
 */
function resolve_template_html( $templateName, array $templatesByName ) {
	if ( isset( $templatesByName[ $templateName ]['html'] ) ) {
		return (string) $templatesByName[ $templateName ]['html'];
	}
	if ( isset( $templatesByName['basic']['html'] ) ) {
		return (string) $templatesByName['basic']['html'];
	}

	return '<li><a href="/{urlName}">{urlName}</a></li>';
}

/**
 * Render one list item from a template, substituting placeholders safely.
 *
 * @param string $templateHtml
 * @param string $urlName
 * @param bool $disableLinks
 *
 * @return string
 */
function render_item_html( $templateHtml, $urlName, $disableLinks ) {
	$safe = htmlspecialchars( $urlName, ENT_QUOTES, 'UTF-8' );
	$html = str_replace( '{urlName}', $safe, $templateHtml );
	if ( $disableLinks ) {
		$html = strip_tags( $html, '<li><div><span>' );
	}

	return $html;
}
