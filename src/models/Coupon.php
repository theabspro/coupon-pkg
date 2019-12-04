<?php

namespace Abs\CouponPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model {
	use SoftDeletes;
	use SeederTrait;
	protected $table = 'coupons';
	protected $fillable = [
		'code',
		'name',
		'cust_group',
		'dimension',
		'mobile_no',
		'email',
		'company_id',
	];

	public static function createFromObject($record_data, $company = null) {

		$errors = [];
		if (!$company) {
			$company = Company::where('code', $record_data->company_code)->first();
		}
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company);
			return;
		}

		$admin = $company->admin();
		if (!$admin) {
			dump('Default Admin user not found');
			return;
		}

		$coupon = self::where([
			'company_id' => $company->id,
			'code' => $record_data->code,
		])->first();
		if ($coupon) {
			$errors[] = 'Duplicate coupon code: ' . $record_data->code;
		}

		$status = Config::where('name', $record_data->status)->where('config_type_id', 7017)->first();
		if (!$status) {
			$errors[] = 'Invalid Status : ' . $record_data->status;
		}

		$claim_initiated_by_id = null;
		if ($record_data->claim_initiated_by) {
			$claim_initiated_by = User::where([
				'company_id' => $company->id,
				'username' => $record_data->claim_initiated_by,
			])->first();
			if (!$claim_initiated_by) {
				$errors[] = 'Duplicate claim_initiated_by: ' . $record_data->claim_initiated_by;
			} else {
				$claim_initiated_by_id = $claim_initiated_by->id;

			}
		}

		$claimed_to_id = null;
		if ($record_data->claimed_to) {
			$claimed_to = User::where([
				'company_id' => $company->id,
				'username' => $record_data->claimed_to,
			])->first();
			if (!$claimed_to) {
				$errors[] = 'Duplicate claimed_to: ' . $record_data->claimed_to;
			} else {
				$claimed_to_id = $claimed_to->id;
			}
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'name' => $record_data->tax_name,
		]);
		$record->type_id = $type->id;
		$record->created_by_id = $admin->id;
		$record->save();
		return $record;
	}
}
