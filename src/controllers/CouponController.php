<?php

namespace Abs\CouponPkg;
use Abs\CouponPkg\Coupon;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class CouponController extends Controller {

	public function __construct() {
	}

	public function getCouponList(Request $request) {
		$Coupon_list = Coupon::select(
			'coupons.id',
			'coupons.code',
			'coupons.point',
			DB::raw('DATE_FORMAT(coupons.date, "%d/%m/%Y") as printed_date'),
			DB::raw('DATE_FORMAT(coupons.created_at, "%d/%m/%Y") as uploaded_date')
		)
			->where('coupons.company_id', Auth::user()->company_id)
		// ->where(function ($query) use ($request) {
		// 	if (!empty($request->Coupon_code)) {
		// 		$query->where('Coupons.code', 'LIKE', '%' . $request->Coupon_code . '%');
		// 	}
		// })
		// ->where(function ($query) use ($request) {
		// 	if (!empty($request->Coupon_name)) {
		// 		$query->where('Coupons.name', 'LIKE', '%' . $request->Coupon_name . '%');
		// 	}
		// })
		// ->where(function ($query) use ($request) {
		// 	if (!empty($request->mobile_no)) {
		// 		$query->where('Coupons.mobile_no', 'LIKE', '%' . $request->mobile_no . '%');
		// 	}
		// })
		// ->where(function ($query) use ($request) {
		// 	if (!empty($request->email)) {
		// 		$query->where('Coupons.email', 'LIKE', '%' . $request->email . '%');
		// 	}
		// })
			->orderby('coupons.id', 'desc');

		return Datatables::of($Coupon_list)
			->addColumn('action', function ($Coupon_list) {
				$img2 = asset('public/img/content/table/eye.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');

				return '<a href="#!/coupon-pkg/coupon/view/' . $Coupon_list->id . '" id = "" ><img src="' . $img2 . '" alt="View" class="img-responsive add" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '"></a>';
			})
			->make(true);
	}

	public function couponCodeView($id) {

		$Coupon_code = Coupon::select(
			'coupons.*',
			'payments.*'
		)
			->leftJoin('payments', 'payments.id', 'coupons.payment_id')
			->where('coupons.id', $id)
			->first();
		// dd($Coupon_code);
		return response()->json($Coupon_code);
	}
}
