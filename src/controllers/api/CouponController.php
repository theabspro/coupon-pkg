<?php

namespace Abs\CouponPkg\Api;
use Abs\CouponPkg\Coupon;
use App\Http\Controllers\Controller;
use App\ItemDetail;
use App\MpayCustomerDetail;
use App\User;
use DB;
use Illuminate\Http\Request;
use Validator;

class CouponController extends Controller {

	private $successStatus = 200;
	public function __construct() {
	}

	public function getCoupon(Request $request) {
		$validator = Validator::make($request->all(), [
			'user_id' => 'required|numeric',
			'coupon_code' => 'required|string',
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
				'error' => 'User ID not found',
			], $this->successStatus);
		}

		$coupon_code = $request->coupon_code;
		$coupon_code_values = explode(", ", $coupon_code);
		$date = $coupon_code_values[1];
		$coupon_code_date = date("Y-m-d", strtotime($date));
		$coupon = Coupon::where('code', $coupon_code_values[0])->where('date', $coupon_code_date)->first();
		if (!$coupon) {
			return response()->json([
				'success' => false,
				'error' => 'Coupon Code not valid',
			], $this->successStatus);
		}
		return response()->json([
			'success' => true,
			'coupon' => $coupon,
		]);
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
					'error' => 'User ID not found',
				], $this->successStatus);
			}

			$customer_validation = MpayCustomerDetail::where('id', $request->claimed_to_id)->first();
			if (!$customer_validation) {
				return response()->json([
					'success' => false,
					'error' => 'Customer ID not found',
				], $this->successStatus);
			}

			$coupon = Coupon::where('id', $request->coupon_id)->where('status_id', 7400)->first();
			if (!$coupon) {
				return response()->json([
					'success' => false,
					'error' => 'Coupon ID not found',
				], $this->successStatus);
			}

			$item_validation = ItemDetail::find($request->item_id);
			if (!$item_validation) {
				return response()->json([
					'success' => false,
					'error' => 'Item ID not found',
				], $this->successStatus);
			}
			$customer_user_id = MpayCustomerDetail::select('mpay_customer_details.*', 'users.id as customer_user_id')
				->leftJoin('users', 'users.entity_id', 'mpay_customer_details.id')
				->where('mpay_customer_details.id', $request->claimed_to_id)
				->first();
			// dd($customer_user_id->customer_user_id);
			$current_date = date('Y-m-d H:i:s');
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
		} catch (\Exception $e) {
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
			DB::beginTransaction();
			$user_validation = User::where('users.id', $request->claim_initiated_by_id)
				->where('users.user_type_id', 6)
				->first();
			if (!$user_validation) {
				return response()->json([
					'success' => false,
					'error' => 'User ID not found',
				], $this->successStatus);
			}

			$customer_validation = User::where('users.id', $request->claimed_to_id)
				->where('users.user_type_id', 7)->first();
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
					'executive.customer_name'
				)
					->join('users', 'users.id', 'coupons.claim_initiated_by_id')
					->join('mpay_customer_details as executive', 'executive.id', 'users.entity_id')
					->where('coupons.id', $coupon)
					->where('coupons.status_id', 7401)
					->first();
				if (!$coupon_id_check) {
					$errors[] = "Coupon ID: " . $coupon . " is doesn't Used";
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
				return response()->json([
					'success' => true,
					'message' => 'Thank you for using TVS Products ' . array_sum($total_points) . ' points redemption to your account by ' . $coupon_id_check->customer_name,
				], $this->successStatus);
			}

		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => [
					$e->getMessage(),
				],
			]);
		}
	}
}
