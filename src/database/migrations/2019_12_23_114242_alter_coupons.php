<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCoupons extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('coupons', function (Blueprint $table) {
			$table->unsignedDecimal('pack_size', 8, 2)->nullable()->after('point');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('coupons', function (Blueprint $table) {
			$table->dropColumn('pack_size');
		});
	}
}
