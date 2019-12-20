<?php

namespace Abs\CouponPkg;
use Abs\CouponPkg\Coupon;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class CouponController extends Controller {

	public function __construct() {
	}

	public function getCouponList(Request $request) {
		$Coupon_list = Coupon::select(
			'coupons.id',
			'coupons.date',
			DB::raw('DATE_FORMAT(coupons.date, "%d/%m/%Y") as printed_date'),
			DB::raw('DATE_FORMAT(coupons.created_at, "%d/%m/%Y") as uploaded_date'),
			DB::raw('IF((mpay_employee_details.employee_name) IS NULL,"--",mpay_employee_details.employee_name) as uploaded_by'),
			DB::raw('COUNT(coupons.id) as coupons_count')
		)
			->leftJoin('users', 'users.id', 'coupons.created_by_id')
			->leftJoin('mpay_employee_details', 'mpay_employee_details.id', 'users.entity_id')
			->where('coupons.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->print_start_date) && !empty($request->print_end_date)) {
					$from = date('Y-m-d', strtotime($request->print_start_date));
					$to = date('Y-m-d', strtotime($request->print_end_date));
					$query->whereBetween('coupons.date', [$from, $to]);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->upload_start_date) && !empty($request->upload_end_date)) {
					$from = date('Y-m-d', strtotime($request->upload_start_date));
					$to = date('Y-m-d', strtotime($request->upload_end_date));
					$query->whereBetween('coupons.created_at', [$from, $to]);
				}
			})
			->groupBy('coupons.date')
			->orderby('coupons.date', 'desc');

		if (!Entrust::can('view-all-coupon')) {
			$Coupon_list = $Coupon_list->where('coupons.created_by_id', Auth::user()->id);
		}

		return Datatables::of($Coupon_list)
			->addColumn('action', function ($Coupon_list) {
				$img2 = asset('public/img/content/table/eye.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');

				return '<a href="#!/coupon-pkg/coupon/view/' . $Coupon_list->date . '" id = "" ><img src="' . $img2 . '" alt="View" class="img-responsive add" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '"></a>';
			})
			->make(true);
	}

	public function couponCodeListView($date) {
		$this->data['coupon_code'] = $Coupon_code = Coupon::select(
			'coupons.id',
			DB::raw('DATE_FORMAT(coupons.date, "%d/%m/%Y") as printed_date'),
			DB::raw('DATE_FORMAT(coupons.created_at, "%d/%m/%Y") as uploaded_date'),
			DB::raw('IF((mpay_employee_details.employee_name) IS NULL,"--",mpay_employee_details.employee_name) as uploaded_by'),
			DB::raw('COUNT(coupons.id) as coupons_count')
		)
			->leftJoin('users', 'users.id', 'coupons.created_by_id')
			->leftJoin('mpay_employee_details', 'mpay_employee_details.id', 'users.entity_id')
			->where('coupons.company_id', Auth::user()->company_id)
			->where('coupons.date', $date)
			->first();

		return response()->json($this->data);
	}

	public function getCouponCodeList($date) {
		$Coupon_code_list = Coupon::select(
			'coupons.id',
			'coupons.code',
			'coupons.date',
			'coupons.point'
		)
			->where('coupons.company_id', Auth::user()->company_id)
			->where('coupons.date', $date)
			->orderby('coupons.id', 'desc');
		// dd($Coupon_code_list);
		return Datatables::of($Coupon_code_list)
			->addColumn('action', function ($Coupon_code_list) {
				$img2 = asset('public/img/content/table/eye.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');

				return '<a href="#!/coupon-pkg/coupon/view/' . $Coupon_code_list->date . '/' . $Coupon_code_list->id . '" id = "" ><img src="' . $img2 . '" alt="View" class="img-responsive add" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '"></a>';
			})
			->addColumn('qr_image', function ($coupon_code) {
				$qr_image = asset('storage/app/public/wad-qr-coupons/' . $coupon_code->code . '.png');

				return '<img src="' . $qr_image . '" alt="Coupon QR Image" class="img-responsive" style="width:70px;">';
			})
			->make(true);
	}

	public function couponCodeView($date, $id) {
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

	public function importCouponCodes(Request $r) {
		return ImportJob::createImportJob($r);
	}
}
