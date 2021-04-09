<?php

namespace Abs\PaymentModePkg;
use Abs\BasicPkg\Attachment;
use Abs\PaymentModePkg\PaymentMode;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use File;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class PaymentModeController extends Controller {

	public $company_id;
	public function __construct() {
		$this->data['theme'] = config('custom.admin_theme');
		$this->company_id = config('custom.company_id');
	}

	public function getPaymentModes(Request $request) {
		$this->data['payment_modes'] = PaymentMode::
			select([
			'payment_modes.question',
			'payment_modes.answer',
		])
			->where('payment_modes.company_id', $this->company_id)
			->orderby('payment_modes.display_order', 'asc')
			->get()
		;
		$this->data['success'] = true;

		return response()->json($this->data);

	}

	public function getPaymentModeList(Request $request) {
		$payment_modes = PaymentMode::withTrashed()
			->select([
				'payment_modes.*',
				DB::raw('IF(payment_modes.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('payment_modes.company_id', $this->company_id)
		/*->where(function ($query) use ($request) {
				if (!empty($request->question)) {
					$query->where('payment_modes.question', 'LIKE', '%' . $request->question . '%');
				}
			})*/
			->orderby('payment_modes.id', 'desc');

		return Datatables::of($payment_modes)
			->addColumn('name', function ($payment_modes) {
				$status = $payment_modes->status == "Active" ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $payment_modes->name;
			})
			->addColumn('action', function ($payment_modes) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				$output .= '<a href="#!/payment-mode-pkg/payment-mode/edit/' . $payment_modes->id . '" id = "" ><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '"></a>
					<a href="javascript:;"  data-toggle="modal" data-target="#payment-mode-delete-modal" onclick="angular.element(this).scope().deletePaymentMode(' . $payment_modes->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>
					';
				return $output;
			})
			->make(true);
	}

	public function getPaymentModeFormData(Request $r) {
		$id = $r->id;
		if (!$id) {
			$payment_mode = new PaymentMode;
			$attachment = new Attachment;
			$action = 'Add';
		} else {
			$payment_mode = PaymentMode::withTrashed()->find($id);
			$attachment = Attachment::where('id', $payment_mode->logo_id)->first();
			$action = 'Edit';
		}
		$this->data['payment_mode'] = $payment_mode;
		$this->data['attachment'] = $attachment;
		$this->data['action'] = $action;
		$this->data['theme'];

		return response()->json($this->data);
	}

	public function savePaymentMode(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'name.required' => 'PaymentMode Name is Required',
				'name.unique' => 'PaymentMode Name is already taken',
				'code.required' => 'PaymentMode Code is Required',
				'code.unique' => 'PaymentMode Code is already taken',
				'description.required' => 'Description is Required',
				'display_order.required' => 'Display Order is Required',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'required:true',
					'unique:payment_modes,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'code' => [
					'required:true',
					'unique:payment_modes,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'description' => 'required|max:255|min:3',
				'display_order' => 'required',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$payment_mode = new PaymentMode;
				$payment_mode->created_by_id = Auth::user()->id;
				$payment_mode->created_at = Carbon::now();
				$payment_mode->updated_at = NULL;
			} else {
				$payment_mode = PaymentMode::withTrashed()->find($request->id);
				$payment_mode->updated_by_id = Auth::user()->id;
				$payment_mode->updated_at = Carbon::now();
			}
			$payment_mode->fill($request->all());
			$payment_mode->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$payment_mode->deleted_at = Carbon::now();
				$payment_mode->deleted_by_id = Auth::user()->id;
			} else {
				$payment_mode->deleted_by_id = NULL;
				$payment_mode->deleted_at = NULL;
			}
			$payment_mode->save();

			if (!empty($request->logo_id)) {
				if (!File::exists(public_path() . '/themes/' . config('custom.admin_theme') . '/img/payment_mode_logo')) {
					File::makeDirectory(public_path() . '/themes/' . config('custom.admin_theme') . '/img/payment_mode_logo', 0777, true);
				}

				$attacement = $request->logo_id;
				$remove_previous_attachment = Attachment::where([
					'entity_id' => $request->id,
					'attachment_of_id' => 20,
				])->first();
				if (!empty($remove_previous_attachment)) {
					$remove = $remove_previous_attachment->forceDelete();
					$img_path = public_path() . '/themes/' . config('custom.admin_theme') . '/img/payment_mode_logo/' . $remove_previous_attachment->name;
					if (File::exists($img_path)) {
						File::delete($img_path);
					}
				}
				$random_file_name = $payment_mode->id . '_payment_mode_file_' . rand(0, 1000) . '.';
				$extension = $attacement->getClientOriginalExtension();
				$attacement->move(public_path() . '/themes/' . config('custom.admin_theme') . '/img/payment_mode_logo', $random_file_name . $extension);

				$attachment = new Attachment;
				$attachment->company_id = Auth::user()->company_id;
				$attachment->attachment_of_id = 20; //User
				$attachment->attachment_type_id = 40; //Primary
				$attachment->entity_id = $payment_mode->id;
				$attachment->name = $random_file_name . $extension;
				$attachment->save();
				$payment_mode->logo_id = $attachment->id;
				$payment_mode->save();
			}

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Payment Mode Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Payment Mode Updated Successfully',
				]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function deletePaymentMode(Request $request) {
		DB::beginTransaction();
		try {
			$payment_mode = PaymentMode::withTrashed()->where('id', $request->id)->first();
			if (!is_null($payment_mode->logo_id)) {
				Attachment::where('company_id', Auth::user()->company_id)->where('attachment_of_id', 20)->where('entity_id', $request->id)->forceDelete();
			}
			PaymentMode::withTrashed()->where('id', $request->id)->forceDelete();

			DB::commit();
			return response()->json(['success' => true, 'message' => 'Payment Mode Deleted Successfully']);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
