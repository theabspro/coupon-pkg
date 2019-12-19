<?php

namespace Abs\CouponPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use DB;
use DNS2D;
use Excel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use PHPExcel_IOFactory;
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
		'status_id',
		'created_by_id',
	];

	public static function importFromExcel($job) {

		try {

			$new_count = $updated_count = $success_count = $error_count = $processed_count = 0;

			//READING EXCEL FILE
			$objPHPExcel = PHPExcel_IOFactory::load('storage/app/' . $job->src_file);
			$sheet = $objPHPExcel->getSheet(0);
			$highestRow = $sheet->getHighestDataRow();

			// $job->status_id = 7204; //Calculating Total Records
			// $job->save();

			$header = $sheet->rangeToArray('A1:F1', NULL, TRUE, FALSE);
			$header = $header[0];

			foreach ($header as $key => $column) {
				$empty_columns = [];
				if ($column == NULL) {
					$empty_columns[] = $key;
					unset($header[$key]);
				}
			}
			$rows = $sheet->rangeToArray('A2:' . 'F' . $highestRow, NULL, TRUE, FALSE);
			$total_records = $highestRow - 1;
			$job->total_record_count = $total_records;
			$job->remaining_count = $total_records;
			$job->status_id = 7201; //Inprogress
			$job->save();

			$all_error_records = [];
			$error_msg = $status = $records = '';
			$sr_no = 0;
			foreach ($rows as $k => $row) {
				DB::beginTransaction();
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

				if (count($status['errors']) > 0) {
					// dump($status['errors']);
					$original_record['Record No'] = $k + 1;
					$original_record['Error Details'] = implode(',', $status['errors']);
					$all_error_records[] = $original_record;
					$error_count++;
					continue;
				}

				$coupon = Coupon::create([
					'company_id' => $job->company_id,
					'code' => $record['Code'], //ITEM
					'date' => date('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($record['Date of Printing'])),
					'point' => $record['Point'],
					'status_id' => 7400,
					'created_by_id' => $job->created_by_id,
				]);

				$encrypted_qr_code = $coupon->code . ', ' . date('d-m-Y', strtotime($coupon->date));
				$qr_code = base64_decode(DNS2D::getBarcodePNG($encrypted_qr_code, "QRCODE", 30, 30));
				$result = Storage::put('public/wad-qr-coupons/' . date('d-m-Y', strtotime($coupon->date) . '/' . $coupon->code . '.png', $qr_code));

				$new_count++;

				//UPDATING PROGRESS FOR EVERY FIVE RECORDS
				if (($k + 1) % 5 == 0) {
					$job->new_count = $new_count;
					$job->error_count = $error_count;
					$job->remaining_count = $total_records - ($k + 1);
					$job->processed_count = $k + 1;
					$job->save();
				}
				DB::commit();
			}
			if (count($all_error_records) > 0) {
				$job->error_details = 'Error occured during import. Check the error report';
			}

			$job->processed_count = $total_records;
			$job->new_count = $new_count;
			$job->updated_count = $updated_count;
			$job->error_count = $error_count;
			$job->status_id = 7202; //COMPLETED
			$job->save();
			if (count($all_error_records) > 0) {
				Excel::load('storage/app/' . $job->output_file, function ($excel) use ($all_error_records, $job) {
					$excel->sheet('Error Details', function ($sheet) use ($all_error_records) {
						// dd($sheet);
						foreach ($all_error_records as $error_record) {
							$sheet->appendRow($error_record, null, 'A1', false, false);

							if (isset($error_record['Record No'])) {
								$sheet->row($sheet->getHighestRow(), function ($row) {
									//get last row at the moment and style it
									$row->setFontColor('#FF0000');
								});
							}
						}
					});
				})->store('xlsx', storage_path('app/' . $job->type->folder_path));
			}
			dump($all_error_records, 'Success. Total Record : ' . $new_count);
		} catch (\Throwable $e) {
			DB::rollback();
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
