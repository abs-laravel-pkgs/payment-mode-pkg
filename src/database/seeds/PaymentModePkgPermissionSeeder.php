<?php
namespace Abs\PaymentModePkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class PaymentModePkgPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			//FAQ
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'payment-modes',
				'display_name' => 'Payment Modes',
			],
			[
				'display_order' => 1,
				'parent' => 'payment-modes',
				'name' => 'add-payment-mode',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'payment-modes',
				'name' => 'delete-payment-mode',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'payment-modes',
				'name' => 'delete-payment-mode',
				'display_name' => 'Delete',
			],

		];
		Permission::createFromArrays($permissions);
	}
}