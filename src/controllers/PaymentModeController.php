<?php

namespace Abs\PaymentModePkg;
use Abs\PaymentModePkg\PaymentMode;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class PaymentModeController extends Controller {

	private $company_id;
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
				'payment_modes.id',
				'payment_modes.question',
				DB::raw('payment_modes.deleted_at as status'),
			])
			->where('payment_modes.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->question)) {
					$query->where('payment_modes.question', 'LIKE', '%' . $request->question . '%');
				}
			})
			->orderby('payment_modes.id', 'desc');

		return Datatables::of($payment_modes)
			->addColumn('question', function ($payment_mode) {
				$status = $payment_mode->status ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $payment_mode->question;
			})
			->addColumn('action', function ($payment_mode) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img2 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/eye.svg');
				$img2_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/eye-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				$output .= '<a href="#!/payment-mode-pkg/payment_mode/edit/' . $payment_mode->id . '" id = "" ><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '"></a>
					<a href="#!/payment-mode-pkg/payment_mode/view/' . $payment_mode->id . '" id = "" ><img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '"></a>
					<a href="javascript:;"  data-toggle="modal" data-target="#payment_mode-delete-modal" onclick="angular.element(this).scope().deletePaymentModeconfirm(' . $payment_mode->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>
					';
				return $output;
			})
			->make(true);
	}

	public function getPaymentModeFormData(Request $r) {
		$id = $r->id;
		if (!$id) {
			$payment_mode = new PaymentMode;
			$action = 'Add';
		} else {
			$payment_mode = PaymentMode::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['payment_mode'] = $payment_mode;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function savePaymentMode(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'PaymentMode Code is Required',
				'code.max' => 'Maximum 255 Characters',
				'code.min' => 'Minimum 3 Characters',
				'code.unique' => 'PaymentMode Code is already taken',
				'name.required' => 'PaymentMode Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
			];
			$validator = Validator::make($request->all(), [
				'question' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:payment_modes,question,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'answer' => 'required|max:255|min:3',
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

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'FAQ Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'FAQ Updated Successfully',
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

	public function deletePaymentMode($id) {
		$delete_status = PaymentMode::withTrashed()->where('id', $id)->forceDelete();
		return response()->json(['success' => true]);
	}
}