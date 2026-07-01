<?php

defined( 'ABSPATH' ) || exit;

/**
 * Entry point for H5P-related integrations. Owns initialization of
 * TL_H5P_AutoComplete (and any future H5P extensions), so the platform
 * bootstrap only has to hook one class.
 */
class TL_H5P_Extension {

	public function init() {
		TL_H5P_AutoComplete::init();
	}
}
