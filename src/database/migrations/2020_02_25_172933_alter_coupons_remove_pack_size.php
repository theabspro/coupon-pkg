<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCouponsRemovePackSize extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		// Schema::table('coupons', function (Blueprint $table) {
		// 	$table->dropColumn('pack_size');
		// });

		if (!Schema::hasTable('coupon_pack_sizes')) {
			Schema::create('coupon_pack_sizes', function (Blueprint $table) {
				$table->unsignedInteger('coupon_id');
				$table->unsignedDecimal('pack_size', 8, 2)->nullable();
				$table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('CASCADE')->onUpdate('cascade');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		// Schema::table('coupons', function (Blueprint $table) {
		// 	$table->unsignedDecimal('pack_size', 8, 2)->nullable()->after('point');
		// });
		Schema::dropIfExists('coupon_pack_sizes');
	}
}
