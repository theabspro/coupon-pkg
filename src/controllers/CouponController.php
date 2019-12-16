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
			DB::raw('DATE_FORMAT(coupons.date, "%d/%m/%Y") as printed_date'),
			DB::raw('DATE_FORMAT(coupons.created_at, "%d/%m/%Y") as uploaded_date'),
			DB::raw('IF((mpay_employee_details.employee_name) IS NULL,"--",mpay_employee_details.employee_name) as uploaded_by'),
			DB::raw('Count(coupons.id) as coupons_count')
		)
			->join('users', 'users.id', 'coupons.created_by_id')
			->join('mpay_employee_details', 'mpay_employee_details.id', 'users.entity_id')
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
			->groupBy('coupons.date')
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

		$this->data['coupon_code'] = $Coupon_code = Coupon::select(
			'coupons.code',
			'coupons.point',
			DB::raw('DATE_FORMAT(coupons.date,"%d/%m/%Y") as date'),
			DB::raw('DATE_FORMAT(coupons.updated_at,"%d/%m/%Y") as updated_date'),
			DB::raw('DATE_FORMAT(coupons.claimed_date,"%d/%m/%Y") as climed_date'),
			'configs.name as status',
			'mpay_employee_details.employee_name as clime_initiated_by',
			'mpay_employee_details.employee_code as clime_initiated_by_code',
			'mpay_customer_details.customer_name as claimed_to',
			'mpay_customer_details.customer_code as claimed_to_code'
			// 'payments.*'
		)
			->leftJoin('configs', 'configs.id', 'coupons.status_id')
			->leftJoin('users', 'users.id', 'coupons.claim_initiated_by_id')
			->leftJoin('mpay_employee_details', 'mpay_employee_details.id', 'users.entity_id')
			->leftJoin('users as customer', 'customer.id', 'coupons.claimed_to_id')
			->leftJoin('mpay_customer_details', 'mpay_customer_details.id', 'customer.entity_id')
			->leftJoin('payments', 'payments.id', 'coupons.payment_id')
			->where('coupons.id', $id)
			->first();
		return response()->json($this->data);
	}
}
