<?php

use App\Company;
use App\CouponPkg\Coupon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ManualCouponSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		DB::beginTransaction();
		$faker = Faker::create();

		global $company_id;

		if (!$company_id) {
			$company_id = $this->command->ask("Enter company id", '3');
			$company = Company::findOrFail($company_id);
		}

		$admin = $company->admin();

		$no_of_items = $this->command->ask("Enter No of Coupon Code to Generate", '20');
		$prefix = $this->command->ask("Enter prefix", 'CL');
		$points = $this->command->ask("Enter points", '20');

		for ($i = 1; $i <= $no_of_items; $i++) {
			$coupon = Coupon::create([
				'company_id' => $company->id,
				'code' => $prefix . '-' . $i,
				'date' => date('d/m/Y'),
				'point' => $points,
				'status_id' => 7400,
				'created_by_id' => $admin->id,
			]);
		}

		DB::commit();
	}
}
