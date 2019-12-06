<?php
namespace Abs\CouponPkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class CouponPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			//MASTER > CouponS
			10000 => [
				'display_order' => 10,
				'parent_id' => null,
				'name' => 'coupons',
				'display_name' => 'Coupons',
			],
			10001 => [
				'display_order' => 1,
				'parent_id' => 10000,
				'name' => 'import-coupon',
				'display_name' => 'Import',
			],
			10002 => [
				'display_order' => 2,
				'parent_id' => 10000,
				'name' => 'view-all-coupon',
				'display_name' => 'View All',
			],

			10020 => [
				'display_order' => 2,
				'parent_id' => null,
				'name' => 'claimed-coupons',
				'display_name' => 'claimed-coupons',
			],
			10021 => [
				'display_order' => 2,
				'parent_id' => 10020,
				'name' => 'view-all-claimed-coupons',
				'display_name' => 'View All',
			],
		];

		foreach ($permissions as $permission_id => $permsion) {
			$permission = Permission::firstOrNew([
				'id' => $permission_id,
			]);
			$permission->fill($permsion);
			$permission->save();
		}
	}
}