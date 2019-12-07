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
			'coupon_code' => 'required|string',
			'user_id' => 'required|numeric',
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

	public function redeemCoupons(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				'user_id' => 'required|numeric',
				'customer_id' => 'required|numeric',
				'coupon_codes' => 'required|array',
				'item_ids' => 'required|array',
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
			//Validate customer id existance
			//Validate each coupon code existance and new status
			//Validate each item ids existance
			//update status of each coupon to claimed status and update other details
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
