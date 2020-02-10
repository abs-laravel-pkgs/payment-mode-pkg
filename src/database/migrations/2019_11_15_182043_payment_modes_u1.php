<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PaymentModesU1 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('payment_modes', function (Blueprint $table) {
			$table->string('code', 191)->after('name');
			$table->string('description', 255)->after('code');

			$table->unique(["company_id", "code"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('payment_modes', function (Blueprint $table) {
			$table->dropUnique('payment_modes_company_id_code_unique');

			$table->dropColumn('code');
			$table->dropColumn('description');
		});
	}
}
