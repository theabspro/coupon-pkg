<?php

namespace Abs\CouponPkg;
use Abs\CouponPkg\Coupon;
use App\Address;
use App\Country;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class CouponController extends Controller {

	public function __construct() {
	}

	public function getCouponList(Request $request) {
		$Coupon_list = Coupon::withTrashed()
			->select(
				'Coupons.id',
				'Coupons.code',
				'Coupons.name',
				DB::raw('IF(Coupons.mobile_no IS NULL,"--",Coupons.mobile_no) as mobile_no'),
				DB::raw('IF(Coupons.email IS NULL,"--",Coupons.email) as email'),
				DB::raw('IF(Coupons.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('Coupons.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->Coupon_code)) {
					$query->where('Coupons.code', 'LIKE', '%' . $request->Coupon_code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->Coupon_name)) {
					$query->where('Coupons.name', 'LIKE', '%' . $request->Coupon_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->mobile_no)) {
					$query->where('Coupons.mobile_no', 'LIKE', '%' . $request->mobile_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->email)) {
					$query->where('Coupons.email', 'LIKE', '%' . $request->email . '%');
				}
			})
			->orderby('Coupons.id', 'desc');

		return Datatables::of($Coupon_list)
			->addColumn('code', function ($Coupon_list) {
				$status = $Coupon_list->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $Coupon_list->code;
			})
			->addColumn('action', function ($Coupon_list) {
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				return '
					<a href="#!/Coupon-pkg/Coupon/edit/' . $Coupon_list->id . '">
						<img src="' . $edit_img . '" alt="View" class="img-responsive">
					</a>
					<a href="javascript:;" data-toggle="modal" data-target="#delete_Coupon"
					onclick="angular.element(this).scope().deleteCoupon(' . $Coupon_list->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $delete_img . '" alt="delete" class="img-responsive">
					</a>
					';
			})
			->make(true);
	}

	public function getCouponFormData($id = NULL) {
		if (!$id) {
			$Coupon = new Coupon;
			$address = new Address;
			$action = 'Add';
		} else {
			$Coupon = Coupon::withTrashed()->find($id);
			$address = Address::where('address_of_id', 24)->where('entity_id', $id)->first();
			$action = 'Edit';
		}
		$this->data['country_list'] = $country_list = Collect(Country::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$this->data['Coupon'] = $Coupon;
		$this->data['address'] = $address;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveCoupon(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Coupon Code is Required',
				'code.max' => 'Maximum 255 Characters',
				'code.min' => 'Minimum 3 Characters',
				'name.required' => 'Coupon Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
				'gst_number.required' => 'GST Number is Required',
				'gst_number.max' => 'Maximum 191 Numbers',
				'mobile_no.max' => 'Maximum 25 Numbers',
				// 'email.required' => 'Email is Required',
				'address_line1.required' => 'Address Line 1 is Required',
				'address_line1.max' => 'Maximum 255 Characters',
				'address_line1.min' => 'Minimum 3 Characters',
				'address_line2.max' => 'Maximum 255 Characters',
				'pincode.required' => 'Pincode is Required',
				'pincode.max' => 'Maximum 6 Characters',
				'pincode.min' => 'Minimum 6 Characters',
			];
			$validator = Validator::make($request->all(), [
				'code' => 'required|max:255|min:3',
				'name' => 'required|max:255|min:3',
				'gst_number' => 'required|max:191',
				'mobile_no' => 'nullable|max:25',
				// 'email' => 'nullable',
				'address_line1' => 'required|max:255|min:3',
				'address_line2' => 'max:255',
				'pincode' => 'required|max:6|min:6',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$Coupon = new Coupon;
				$Coupon->created_by_id = Auth::user()->id;
				$Coupon->created_at = Carbon::now();
				$Coupon->updated_at = NULL;
				$address = new Address;
			} else {
				$Coupon = Coupon::withTrashed()->find($request->id);
				$Coupon->updated_by_id = Auth::user()->id;
				$Coupon->updated_at = Carbon::now();
				$address = Address::where('address_of_id', 24)->where('entity_id', $request->id)->first();
			}
			$Coupon->fill($request->all());
			$Coupon->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$Coupon->deleted_at = Carbon::now();
				$Coupon->deleted_by_id = Auth::user()->id;
			} else {
				$Coupon->deleted_by_id = NULL;
				$Coupon->deleted_at = NULL;
			}
			$Coupon->gst_number = $request->gst_number;
			$Coupon->save();

			if (!$address) {
				$address = new Address;

			}
			$address->fill($request->all());
			$address->company_id = Auth::user()->company_id;
			$address->address_of_id = 24;
			$address->entity_id = $Coupon->id;
			$address->address_type_id = 40;
			$address->name = 'Primary Address';
			$address->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['Coupon Details Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['Coupon Details Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteCoupon($id) {
		$delete_status = Coupon::withTrashed()->where('id', $id)->forceDelete();
		if ($delete_status) {
			$address_delete = Address::where('address_of_id', 24)->where('entity_id', $id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}
}
