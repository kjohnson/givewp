<?php

namespace Give\DonorProfiles\Tabs\EditProfileTab;

use Give\DonorProfiles\Tabs\Contracts\Tab as TabAbstract;
use Give\DonorProfiles\Tabs\EditProfileTab\ProfileRoute;
use Give\DonorProfiles\Tabs\EditProfileTab\LocationRoute;

/**
 * @since 2.10.0
 */
class Tab extends TabAbstract {

	public static function id() {
		return 'edit-profile';
	}

	public function routes() {
		return [
			ProfileRoute::class,
			LocationRoute::class,
		];
	}
}