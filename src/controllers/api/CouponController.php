<?php

namespace Abs\CouponPkg\Api;
use Abs\CouponPkg\Coupon;
use App\Http\Controllers\Controller;
use App\ItemDetail;
use App\MpayCustomerDetail;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;

class CouponController extends Controller {

	private $successStatus = 200;
	public function __construct() {
	}

	public function getCoupon(Request $request) {

		if (!empty($request->date)) {
			$coupon_code_validate = 'required|string';
			$date_validate = 'required|date';
			$coupon_code = $request->coupon_code;
			$coupon_code_date = date('Y-m-d', strtotime($request->date));
		} else {
			$coupon_code_validate = 'required|string';
			$date_validate = 'nullable';
			$coupon_code_values = explode(", ", $request->coupon_code);
			$coupon_code = $coupon_code_values[0];
			$coupon_code_date = date('Y-m-d', strtotime($coupon_code_values[1]));
		}

		$validator = Validator::make($request->all(), [
			'user_id' => 'required|numeric',
			'coupon_code' => $coupon_code_validate,
			'date' => $date_validate,
		]);
		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => $validator->errors()->all(),
			], $this->successStatus);
		}

		$user_validation = User::where('id', $request->user_id)->where('user_type_id', 6)->first();
		if (!$user_validation) {
			return response()->json([
				'success' => false,
				'error' => 'User not found',
			], $this->successStatus);
		}

		$coupon_validation = Coupon::where([
			'code' => $coupon_code,
			'date' => $coupon_code_date,
			'company_id' => $user_validation->company_id,
		])
			->first();
		if (!$coupon_validation) {
			return response()->json([
				'success' => false,
				'error' => 'Coupon not found',
			], $this->successStatus);
		} else {
			$coupon = Coupon::where([
				'code' => $coupon_code,
				'date' => $coupon_code_date,
				'company_id' => $user_validation->company_id,
				'status_id' => 7400])
				->first();
			if (!$coupon) {
				return response()->json([
					'success' => false,
					'error' => 'Coupon Already Redeemed',
				], $this->successStatus);
			} else {
				return response()->json([
					'success' => true,
					'coupon' => $coupon,
				]);
			}
		}
	}

	public function redeemCoupon(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				'claim_initiated_by_id' => 'required|numeric',
				'claimed_to_id' => 'required|numeric',
				'coupon_id' => 'required|numeric',
				'item_id' => 'required|numeric',
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			DB::beginTransaction();
			// Validate user id existance and redeem permission
			// Validate customer user id existance and check its user type
			// Validate coupon id existance and new status
			// Validate item id existance
			// update status of coupon to claimed status and update other claimed details
			$user_validation = User::where('id', $request->claim_initiated_by_id)->where('user_type_id', 6)->first();
			if (!$user_validation) {
				return response()->json([
					'success' => false,
					'error' => 'User not found',
				], $this->successStatus);
			}

			$customer_validation = MpayCustomerDetail::where('id', $request->claimed_to_id)->first();
			if (!$customer_validation) {
				return response()->json([
					'success' => false,
					'error' => 'Customer not found',
				], $this->successStatus);
			}

			$coupon = Coupon::where('id', $request->coupon_id)->where('status_id', 7400)->first();
			if (!$coupon) {
				return response()->json([
					'success' => false,
					'error' => 'Coupon not found',
				], $this->successStatus);
			}

			$item_validation = ItemDetail::find($request->item_id);
			if (!$item_validation) {
				return response()->json([
					'success' => false,
					'error' => 'Item not found',
				], $this->successStatus);
			}
			$customer_user_id = MpayCustomerDetail::select('mpay_customer_details.*', 'users.id as customer_user_id')
				->leftJoin('users', 'users.entity_id', 'mpay_customer_details.id')
				->where('mpay_customer_details.id', $request->claimed_to_id)
				->first();
			// dd($customer_user_id->customer_user_id);
			$current_date = Carbon::now();
			$coupon->status_id = 7401; //Claimed
			$coupon->claim_initiated_by_id = $request->claim_initiated_by_id;
			$coupon->claimed_to_id = $customer_user_id->customer_user_id;
			$coupon->claimed_date = $current_date;
			$coupon->updated_by_id = $request->claim_initiated_by_id;
			$coupon->updated_at = $current_date;
			$coupon->save();
			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Coupons redeemed successfully!',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => [
					$e->getMessage(),
				],
			]);
		}
	}

	public function sendCoupon(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				'claim_initiated_by_id' => 'required|numeric',
				'claimed_to_id' => 'required|numeric',
				'coupon_id.*' => 'required',
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}
			$user_validation = User::where('users.id', $request->claim_initiated_by_id)
				->where('users.user_type_id', 6)
				->first();
			if (!$user_validation) {
				return response()->json([
					'success' => false,
					'error' => 'User ID not found',
				], $this->successStatus);
			}

			// $customer_validation = User::where('users.id', $request->claimed_to_id)
			// 	->where('users.user_type_id', 7)->first();
			$customer_validation = MpayCustomerDetail::join('users', 'users.entity_id', 'mpay_customer_details.id')
				->where('mpay_customer_details.id', $request->claimed_to_id)
				->where('users.user_type_id', 7)
				->first();

			if (!$customer_validation) {
				return response()->json([
					'success' => false,
					'error' => 'Customer ID not found',
				], $this->successStatus);
			}

			$errors = [];
			$total_points = [];
			foreach ($request->coupon_id as $coupon) {
				$coupon_id_check = Coupon::select(
					'coupons.point',
					'executive.employee_name',
					'executive.id as employee_id',
					'mpay_customer_details.mobile_number',
					'mpay_customer_details.id as customer_id'
				)
					->join('users', 'users.id', 'coupons.claim_initiated_by_id')
					->join('mpay_employee_details as executive', 'executive.id', 'users.entity_id')
					->join('users as customer', 'customer.id', 'coupons.claimed_to_id')
					->join('mpay_customer_details', 'mpay_customer_details.id', 'customer.entity_id')
					->where('coupons.id', $coupon)
					->where('coupons.status_id', 7401)
					->first();
				if (!$coupon_id_check) {
					$errors[] = "Coupon ID: " . $coupon . " already scanned";
				} else {
					$total_points[] = $coupon_id_check->point;
				}
			}
			if (count($errors) > 0) {
				return response()->json([
					'success' => false,
					'error' => $errors,
				], $this->successStatus);
			}
			if ($coupon_id_check) {
				$mobile_number = $coupon_id_check->mobile_number;

				$message = config('custom.SMS_TEMPLATES.COUPON_ALERT');
				$args = ['total_points' => array_sum($total_points), 'employee_name' => $coupon_id_check->employee_name];
				$message = vsprintf($message, $args);

				if (!empty($mobile_number) && $mobile_number != 7777777777) {
					$res = $this->sendsms($coupon_id_check->mobile_number, $message, $coupon_id_check->customer_id, $coupon_id_check->employee_id);
				}
				return response()->json([
					'success' => true,
					'message' => 'Coupon Redeemed Successfully',
					// 'message' => 'Thank you for using TVS Products ' . array_sum($total_points) . ' points redemption added to your account by ' . $coupon_id_check->employee_name,
				], $this->successStatus);
			}
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => [
					$e->getMessage(),
				],
			]);
		}
	}

	public function sendsms($mobile_number, $message, $customer_id, $employee_id) {
		$sms_url = config('custom.sms_url');
		$sms_user = config('custom.sms_user');
		$sms_password = config('custom.sms_password');
		$sms_sender_id = config('custom.sms_sender_id_tvsrpt');

		$file_content = $sms_url . 'uname=' . $sms_user . '&pass=' . $sms_password . '&send=' . $sms_sender_id . '&dest=91' . $mobile_number . '&msg=' . urlencode($message);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $file_content);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$message_id = curl_exec($ch);
		curl_close($ch);
		$sms_insert = '';
		if (!empty($message_id)) {
			$sms_insert = DB::table('mpay_sent_sms_details')->insert(
				['customer_id' => $customer_id, 'employee_id' => $employee_id, 'sender_id' => $sms_sender_id, 'message' => $message, 'message_id' => $message_id, 'mobile_number' => $mobile_number, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
			);
			if ($sms_insert) {
				return true;
			}
		}

	}
}
