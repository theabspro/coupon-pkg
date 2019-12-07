<?php

namespace Abs\CouponPkg\Api;
use Abs\CouponPkg\Coupon;
use App\Http\Controllers\Controller;
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

		$coupon = new Coupon;
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
			//Validate user id existance and redeem permission
			//Validate customer user id existance and check its user type
			//Validate coupon id existance and new status
			//Validate item id existance
			//update status of coupon to claimed status and update other claimed details
			DB::commit();

			$coupon = new Coupon;
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

}
