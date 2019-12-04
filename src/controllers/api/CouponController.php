<?php

namespace Abs\CouponPkg\Api;
use Abs\CouponPkg\Coupon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;

class CouponController extends Controller {

	private $successStatus = 200;
	public function __construct() {
	}

	public function scanForRedemption(Request $request) {
		$validator = Validator::make($request->all(), [
			'coupon_code' => 'required|string',
			'user_id' => 'required|numeric',
		]);
		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => $validator->errors(),
			], $this->successStatus);
		}

		$coupon = new Coupon;
		return response()->json([
			'success' => true,
			'coupon' => $coupon,
		]);
	}

}
