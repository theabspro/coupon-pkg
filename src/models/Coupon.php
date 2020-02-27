<?php

namespace Abs\CouponPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Abs\ImportCronJobPkg\ImportCronJob;
use App\Company;
use App\Config;
use DB;
use DNS2D;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use PHPExcel_Shared_Date;

class Coupon extends Model {
	use SoftDeletes;
	use SeederTrait;
	protected $table = 'coupons';
	protected $fillable = [
		'company_id',
		'code',
		'date',
		'point',
		// 'pack_size',
		'status_id',
		'created_by_id',
	];

	public static function importFromExcel($job) {
		try {
			$response = ImportCronJob::getRecordsFromExcel($job, 'G');
			$rows = $response['rows'];
			$header = $response['header'];

			$all_error_records = [];
			foreach ($rows as $k => $row) {
				$record = [];
				foreach ($header as $key => $column) {
					if (!$column) {
						continue;
					} else {
						$record[$column] = trim($row[$key]);
					}
				}

				$original_record = $record;
				$status = [];
				$status['errors'] = [];

				if (empty($record['Code'])) {
					$status['errors'][] = 'Date of printing is empty';
				} else {
					$coupon = Coupon::where([
						'company_id' => $job->company_id,
						'code' => $record['Code'],
					])->first();
					if ($coupon) {
						$status['errors'][] = 'Duplicate coupon code';
					}
				}

				if (empty($record['Date of Printing'])) {
					$status['errors'][] = 'Date of printing is empty';
				}

				if (empty($record['Point'])) {
					$status['errors'][] = 'Point is empty';
				}

				$pack_sizes = explode(',', $record['Pack Size']);
				if (empty($record['Pack Size'])) {
					$status['errors'][] = 'Pack Size is empty';
				} else {
					$pack_size_count = count($pack_sizes);
					$unique_pack_size_count = count(array_unique($pack_sizes));
					if ($pack_size_count != $unique_pack_size_count) {
						$status['errors'][] = 'Pack Sizes Should be Unique ' . $record['Pack Size'];
					}
				}

				if (count($status['errors']) > 0) {
					// dump($status['errors']);
					$original_record['Record No'] = $k + 1;
					$original_record['Error Details'] = implode(',', $status['errors']);
					$all_error_records[] = $original_record;
					$job->incrementError();
					continue;
				}

				DB::beginTransaction();
				$coupon = Coupon::create([
					'company_id' => $job->company_id,
					'code' => $record['Code'], //ITEM
					'date' => date('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($record['Date of Printing'])),
					'point' => $record['Point'],
					// 'pack_size' => $record['Pack Size'],
					'status_id' => 7400,
					'created_by_id' => $job->created_by_id,
					'updated_at' => NULL,
				]);
				if (!empty($record['Pack Size'])) {
					foreach ($pack_sizes as $pack_size) {
						$coupon_pack_size_insert = DB::table('coupon_pack_sizes')->insert([
							'coupon_id' => $coupon->id,
							'pack_size' => $pack_size,
						]);
					}
				}

				$encrypted_qr_code = $coupon->code . ', ' . date('d-m-Y', strtotime($coupon->date));
				$qr_code = base64_decode(DNS2D::getBarcodePNG($encrypted_qr_code, "QRCODE", 30, 30));
				$qr_destination = 'public/wad-qr-coupons/'; // . date('d-m-Y', strtotime($coupon->date)) . '/';
				Storage::makeDirectory($qr_destination, 0777);
				$result = Storage::put($qr_destination . $coupon->code . '.png', $qr_code);

				$job->incrementNew();

				DB::commit();
				//UPDATING PROGRESS FOR EVERY FIVE RECORDS
				if (($k + 1) % 5 == 0) {
					$job->save();
				}
			}

			//COMPLETED or completed with errors
			$job->status_id = $job->error_count == 0 ? 7202 : 7205;
			$job->save();

			ImportCronJob::generateImportReport([
				'job' => $job,
				'all_error_records' => $all_error_records,
			]);

		} catch (\Throwable $e) {
			$job->status_id = 7203; //Error
			$job->error_details = 'Error:' . $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(); //Error
			$job->save();
			dump($job->error_details);
		}

	}

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
